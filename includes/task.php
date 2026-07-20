<?php

require_once(__DIR__ . '/db.php');

class Task {
    public static function all($userId, $yearId) {
        $db = DB::getConnection();

        $stmt = $db->prepare(
            'SELECT t.*,
                    terms.name AS term_name,
                    GROUP_CONCAT(s.name ORDER BY s.name SEPARATOR ", ") AS skill_names
             FROM tasks t
             INNER JOIN terms ON terms.id = t.term_id
             LEFT JOIN task_skills ts ON ts.task_id = t.id
             LEFT JOIN skills s ON s.id = ts.skill_id AND s.deleted_at IS NULL
             WHERE t.user_id = :user_id
               AND t.deleted_at IS NULL
               AND terms.year_id = :year_id
             GROUP BY t.id
             ORDER BY terms.id ASC, t.name ASC'
        );
        $stmt->execute([
            'user_id' => $userId,
            'year_id' => $yearId,
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function find($userId, $taskId) {
        $db = DB::getConnection();

        $stmt = $db->prepare(
            'SELECT * FROM tasks WHERE id = :id AND user_id = :user_id AND deleted_at IS NULL LIMIT 1'
        );
        $stmt->execute([
            'id' => $taskId,
            'user_id' => $userId,
        ]);
        $task = $stmt->fetch(PDO::FETCH_ASSOC);
        return $task ?: null;
    }

    public static function skillIds($taskId) {
        $db = DB::getConnection();

        $stmt = $db->prepare(
            'SELECT skill_id FROM task_skills WHERE task_id = :task_id'
        );
        $stmt->execute(['task_id' => $taskId]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    public static function create($userId, $name, $termId, array $skillIds) {
        $db = DB::getConnection();

        $stmt = $db->prepare(
            'INSERT INTO tasks (user_id, name, term_id) VALUES (:user_id, :name, :term_id)'
        );
        $stmt->execute([
            'user_id' => $userId,
            'name' => $name,
            'term_id' => $termId,
        ]);
        $taskId = (int) $db->lastInsertId();
        self::syncSkills($taskId, $skillIds);
        return $taskId;
    }

    public static function update($userId, $taskId, $name, $termId, array $skillIds) {
        $db = DB::getConnection();

        $stmt = $db->prepare(
            'UPDATE tasks SET name = :name, term_id = :term_id WHERE id = :id AND user_id = :user_id AND deleted_at IS NULL'
        );
        $stmt->execute([
            'name' => $name,
            'term_id' => $termId,
            'id' => $taskId,
            'user_id' => $userId,
        ]);

        if ($stmt->rowCount() === 0 && !self::find($userId, $taskId)) {
            return false;
        }

        self::syncSkills($taskId, $skillIds);
        return true;
    }

    public static function delete($userId, $taskId) {
        $db = DB::getConnection();

        $stmt = $db->prepare(
            'UPDATE tasks SET deleted_at = NOW() WHERE id = :id AND user_id = :user_id AND deleted_at IS NULL'
        );
        $stmt->execute([
            'id' => $taskId,
            'user_id' => $userId,
        ]);
        return $stmt->rowCount() > 0;
    }

    private static function syncSkills($taskId, array $skillIds) {
        $db = DB::getConnection();

        $stmt = $db->prepare('DELETE FROM task_skills WHERE task_id = :task_id');
        $stmt->execute(['task_id' => $taskId]);

        if (empty($skillIds)) {
            return;
        }

        $stmt = $db->prepare(
            'INSERT INTO task_skills (task_id, skill_id) VALUES (:task_id, :skill_id)'
        );
        foreach ($skillIds as $skillId) {
            $stmt->execute([
                'task_id' => $taskId,
                'skill_id' => $skillId,
            ]);
        }
    }
}
