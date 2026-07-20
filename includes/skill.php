<?php

require_once(__DIR__ . '/db.php');

class Skill {
    public static function all($userId) {
        $db = DB::getConnection();

        $stmt = $db->prepare(
            'SELECT s.*,
                    GROUP_CONCAT(c.name ORDER BY c.name SEPARATOR ", ") AS competency_names,
                    GROUP_CONCAT(c.id ORDER BY c.id) AS competency_ids
             FROM skills s
             LEFT JOIN skill_competencies sc ON sc.skill_id = s.id
             LEFT JOIN competencies c ON c.id = sc.competency_id
             WHERE s.user_id = :user_id
               AND s.deleted_at IS NULL
             GROUP BY s.id
             ORDER BY s.name ASC'
        );
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function forTerm($userId, $termId, $competencyId = 0) {
        $db = DB::getConnection();

        $sql = 'SELECT DISTINCT s.*
                FROM skills s
                INNER JOIN task_skills ts ON ts.skill_id = s.id
                INNER JOIN tasks t ON t.id = ts.task_id
                WHERE s.user_id = :user_id
                  AND s.deleted_at IS NULL
                  AND t.user_id = :task_user_id
                  AND t.deleted_at IS NULL
                  AND t.term_id = :term_id';

        $params = [
            'user_id' => $userId,
            'task_user_id' => $userId,
            'term_id' => $termId,
        ];

        if ($competencyId > 0) {
            $sql .= ' AND EXISTS (
                SELECT 1 FROM skill_competencies sc
                WHERE sc.skill_id = s.id AND sc.competency_id = :competency_id
            )';
            $params['competency_id'] = $competencyId;
        }

        $sql .= ' ORDER BY s.name ASC';

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function competencyNames($skillId) {
        $db = DB::getConnection();

        $stmt = $db->prepare(
            'SELECT c.name
             FROM competencies c
             INNER JOIN skill_competencies sc ON sc.competency_id = c.id
             WHERE sc.skill_id = :skill_id
             ORDER BY c.name ASC'
        );
        $stmt->execute(['skill_id' => $skillId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public static function find($userId, $skillId) {
        $db = DB::getConnection();

        $stmt = $db->prepare(
            'SELECT * FROM skills WHERE id = :id AND user_id = :user_id AND deleted_at IS NULL LIMIT 1'
        );
        $stmt->execute([
            'id' => $skillId,
            'user_id' => $userId,
        ]);
        $skill = $stmt->fetch(PDO::FETCH_ASSOC);
        return $skill ?: null;
    }

    public static function competencyIds($skillId) {
        $db = DB::getConnection();

        $stmt = $db->prepare(
            'SELECT competency_id FROM skill_competencies WHERE skill_id = :skill_id'
        );
        $stmt->execute(['skill_id' => $skillId]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    public static function findIds($userId, array $ids) {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        $ids = array_filter($ids, function ($id) {
            return $id > 0;
        });
        if (empty($ids)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $db = DB::getConnection();
        $stmt = $db->prepare(
            "SELECT id FROM skills WHERE user_id = ? AND deleted_at IS NULL AND id IN ($placeholders)"
        );
        $stmt->execute(array_merge([$userId], $ids));
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    public static function create($userId, $name, array $competencyIds) {
        $db = DB::getConnection();

        $stmt = $db->prepare(
            'INSERT INTO skills (user_id, name) VALUES (:user_id, :name)'
        );
        $stmt->execute([
            'user_id' => $userId,
            'name' => $name,
        ]);
        $skillId = (int) $db->lastInsertId();
        self::syncCompetencies($skillId, $competencyIds);
        return $skillId;
    }

    public static function update($userId, $skillId, $name, array $competencyIds) {
        $db = DB::getConnection();

        $stmt = $db->prepare(
            'UPDATE skills SET name = :name WHERE id = :id AND user_id = :user_id AND deleted_at IS NULL'
        );
        $stmt->execute([
            'name' => $name,
            'id' => $skillId,
            'user_id' => $userId,
        ]);

        if ($stmt->rowCount() === 0 && !self::find($userId, $skillId)) {
            return false;
        }

        self::syncCompetencies($skillId, $competencyIds);
        return true;
    }

    public static function delete($userId, $skillId) {
        $db = DB::getConnection();

        $stmt = $db->prepare(
            'UPDATE skills SET deleted_at = NOW() WHERE id = :id AND user_id = :user_id AND deleted_at IS NULL'
        );
        $stmt->execute([
            'id' => $skillId,
            'user_id' => $userId,
        ]);
        return $stmt->rowCount() > 0;
    }

    private static function syncCompetencies($skillId, array $competencyIds) {
        $db = DB::getConnection();

        $stmt = $db->prepare('DELETE FROM skill_competencies WHERE skill_id = :skill_id');
        $stmt->execute(['skill_id' => $skillId]);

        if (empty($competencyIds)) {
            return;
        }

        $stmt = $db->prepare(
            'INSERT INTO skill_competencies (skill_id, competency_id) VALUES (:skill_id, :competency_id)'
        );
        foreach ($competencyIds as $competencyId) {
            $stmt->execute([
                'skill_id' => $skillId,
                'competency_id' => $competencyId,
            ]);
        }
    }
}
