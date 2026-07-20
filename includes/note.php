<?php

require_once(__DIR__ . '/db.php');

class Note {
    public static function scaleOptions() {
        return [
            '5' => '5 — De manière exemplaire',
            '4' => '4 — Bien',
            '3' => '3 — Maladroitement',
            '2' => '2 — De manière insuffisante',
            '1' => '1 — Ne le fait pas / Plagiat',
            'ne' => 'N/E',
        ];
    }

    public static function parseInput($value) {
        if ($value === '' || $value === null) {
            return 'clear';
        }
        if ($value === 'ne') {
            return null;
        }
        if ($value === 'plagiat') {
            return 1;
        }
        $note = (int) $value;
        if ($note < 1 || $note > 5) {
            return false;
        }
        return $note;
    }

    public static function save($studentId, $taskId, $skillId, $note) {
        $db = DB::getConnection();

        if ($note === 'clear') {
            $stmt = $db->prepare(
                'DELETE FROM student_notes
                 WHERE student_id = :student_id AND task_id = :task_id AND skill_id = :skill_id'
            );
            $stmt->execute([
                'student_id' => $studentId,
                'task_id' => $taskId,
                'skill_id' => $skillId,
            ]);
            return true;
        }

        $stmt = $db->prepare(
            'INSERT INTO student_notes (student_id, task_id, skill_id, note)
             VALUES (:student_id, :task_id, :skill_id, :note)
             ON DUPLICATE KEY UPDATE note = VALUES(note)'
        );
        $stmt->execute([
            'student_id' => $studentId,
            'task_id' => $taskId,
            'skill_id' => $skillId,
            'note' => $note,
        ]);
        return true;
    }

    public static function averageForStudentSkill($studentId, $skillId, $termId) {
        $db = DB::getConnection();

        $stmt = $db->prepare(
            'SELECT
                AVG(sn.note) AS average,
                SUM(CASE WHEN sn.note IS NULL THEN 1 ELSE 0 END) AS ne_count,
                SUM(CASE WHEN sn.note IS NOT NULL THEN 1 ELSE 0 END) AS note_count
             FROM student_notes sn
             INNER JOIN tasks t ON t.id = sn.task_id
             INNER JOIN task_skills ts ON ts.task_id = t.id AND ts.skill_id = sn.skill_id
             WHERE sn.student_id = :student_id
               AND sn.skill_id = :skill_id
               AND t.term_id = :term_id
               AND t.deleted_at IS NULL'
        );
        $stmt->execute([
            'student_id' => $studentId,
            'skill_id' => $skillId,
            'term_id' => $termId,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row || ((int) $row['note_count'] === 0 && (int) $row['ne_count'] === 0)) {
            return null;
        }
        if ((int) $row['note_count'] === 0) {
            return 'N/E';
        }
        return round((float) $row['average'], 1);
    }

    /**
     * Moyennes / N/E par élève et habileté pour les tâches d'une étape.
     * Retourne [student_id => [skill_id => number|'N/E']]
     */
    public static function averagesBySkillForTerm($userId, $groupId, $termId, $competencyId = 0) {
        $db = DB::getConnection();

        $sql = 'SELECT sn.student_id, sn.skill_id,
                       AVG(sn.note) AS average,
                       SUM(CASE WHEN sn.note IS NULL THEN 1 ELSE 0 END) AS ne_count,
                       SUM(CASE WHEN sn.note IS NOT NULL THEN 1 ELSE 0 END) AS note_count
                FROM student_notes sn
                INNER JOIN students st ON st.id = sn.student_id
                INNER JOIN tasks t ON t.id = sn.task_id
                INNER JOIN task_skills ts ON ts.task_id = t.id AND ts.skill_id = sn.skill_id
                INNER JOIN skills sk ON sk.id = sn.skill_id
                WHERE st.user_id = :user_id
                  AND st.group_id = :group_id
                  AND st.deleted_at IS NULL
                  AND t.user_id = :task_user_id
                  AND t.deleted_at IS NULL
                  AND t.term_id = :term_id
                  AND sk.user_id = :skill_user_id
                  AND sk.deleted_at IS NULL';

        $params = [
            'user_id' => $userId,
            'group_id' => $groupId,
            'task_user_id' => $userId,
            'term_id' => $termId,
            'skill_user_id' => $userId,
        ];

        if ($competencyId > 0) {
            $sql .= ' AND EXISTS (
                SELECT 1 FROM skill_competencies sc
                WHERE sc.skill_id = sn.skill_id AND sc.competency_id = :competency_id
            )';
            $params['competency_id'] = $competencyId;
        }

        $sql .= ' GROUP BY sn.student_id, sn.skill_id';

        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        $result = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $studentId = (int) $row['student_id'];
            $skillId = (int) $row['skill_id'];
            if (!isset($result[$studentId])) {
                $result[$studentId] = [];
            }
            if ((int) $row['note_count'] > 0) {
                $result[$studentId][$skillId] = round((float) $row['average'], 1);
            } else {
                $result[$studentId][$skillId] = 'N/E';
            }
        }
        return $result;
    }

    /**
     * Notes par élève et tâche pour une habileté.
     * Retourne [student_id => [task_id => note]]
     */
    public static function notesByTaskForSkill($userId, $groupId, $skillId, $termId = 0) {
        $db = DB::getConnection();

        $sql = 'SELECT sn.student_id, sn.task_id, sn.note
                FROM student_notes sn
                INNER JOIN students st ON st.id = sn.student_id
                INNER JOIN tasks t ON t.id = sn.task_id
                WHERE st.user_id = :user_id
                  AND st.group_id = :group_id
                  AND st.deleted_at IS NULL
                  AND t.user_id = :task_user_id
                  AND t.deleted_at IS NULL
                  AND sn.skill_id = :skill_id';

        $params = [
            'user_id' => $userId,
            'group_id' => $groupId,
            'task_user_id' => $userId,
            'skill_id' => $skillId,
        ];

        if ($termId > 0) {
            $sql .= ' AND t.term_id = :term_id';
            $params['term_id'] = $termId;
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        $result = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $studentId = (int) $row['student_id'];
            $taskId = (int) $row['task_id'];
            if (!isset($result[$studentId])) {
                $result[$studentId] = [];
            }
            // Keep null notes as null for display
            $result[$studentId][$taskId] = $row['note'] === null ? null : (int) $row['note'];
        }
        return $result;
    }

    public static function detailsForStudentSkill($userId, $studentId, $skillId, $termId) {
        $db = DB::getConnection();

        $stmt = $db->prepare(
            'SELECT t.id AS task_id,
                    t.name AS task_name,
                    sn.note,
                    (sn.student_id IS NOT NULL) AS has_note
             FROM tasks t
             INNER JOIN task_skills ts ON ts.task_id = t.id
             LEFT JOIN student_notes sn
               ON sn.task_id = t.id
              AND sn.skill_id = ts.skill_id
              AND sn.student_id = :student_id
             WHERE t.user_id = :user_id
               AND t.deleted_at IS NULL
               AND t.term_id = :term_id
               AND ts.skill_id = :skill_id
             ORDER BY t.name ASC'
        );
        $stmt->execute([
            'student_id' => $studentId,
            'user_id' => $userId,
            'term_id' => $termId,
            'skill_id' => $skillId,
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
