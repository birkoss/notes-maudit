<?php

require_once(__DIR__ . '/db.php');

class Group {
    public static function create($userId, $name, $yearId) {
        $db = DB::getConnection();

        $stmt = $db->prepare('INSERT INTO student_groups (user_id, name, year_id) VALUES (:user_id, :name, :year_id)');
        $stmt->execute([
            'user_id' => $userId,
            'name' => $name,
            'year_id' => $yearId,
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}