<?php

require_once(__DIR__ . '/db.php');

class Skill {
    public static function all($userId) {
        $db = DB::getConnection();

        $stmt = $db->prepare(
            'SELECT s.*,
                    GROUP_CONCAT(c.name ORDER BY c.name SEPARATOR ", ") AS competency_names
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
