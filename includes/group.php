<?php

require_once(__DIR__ . '/db.php');

class Group {
    public static function find($userId, $groupId) {
        $db = DB::getConnection();

        $stmt = $db->prepare(
            'SELECT * FROM student_groups WHERE id = :id AND user_id = :user_id AND deleted_at IS NULL LIMIT 1'
        );
        $stmt->execute([
            'id' => $groupId,
            'user_id' => $userId,
        ]);
        $group = $stmt->fetch(PDO::FETCH_ASSOC);
        return $group ?: null;
    }

    public static function create($userId, $name, $yearId) {
        $db = DB::getConnection();

        $stmt = $db->prepare(
            'INSERT INTO student_groups (user_id, name, year_id) VALUES (:user_id, :name, :year_id)'
        );
        $stmt->execute([
            'user_id' => $userId,
            'name' => $name,
            'year_id' => $yearId,
        ]);
        return (int) $db->lastInsertId();
    }

    public static function update($userId, $groupId, $name, $yearId) {
        $db = DB::getConnection();

        $stmt = $db->prepare(
            'UPDATE student_groups SET name = :name, year_id = :year_id WHERE id = :id AND user_id = :user_id'
        );
        $stmt->execute([
            'name' => $name,
            'year_id' => $yearId,
            'id' => $groupId,
            'user_id' => $userId,
        ]);
        return $stmt->rowCount() > 0;
    }

    public static function all($userId, $yearId) {
        $db = DB::getConnection();

        $stmt = $db->prepare(
            'SELECT * FROM student_groups WHERE user_id = :user_id AND year_id = :year_id AND deleted_at IS NULL ORDER BY name ASC'
        );
        $stmt->execute([
            'user_id' => $userId,
            'year_id' => $yearId,
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function delete($userId, $groupId) {
        $db = DB::getConnection();

        $stmt = $db->prepare(
            'UPDATE student_groups SET deleted_at = NOW() WHERE id = :id AND user_id = :user_id'
        );
        $stmt->execute([
            'id' => $groupId,
            'user_id' => $userId,
        ]);
        return $stmt->rowCount() > 0;
    }
}
