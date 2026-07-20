<?php

require_once(__DIR__ . "/db.php");

class User {
    public static function getTokenByEmail($email) {
        $db = DB::getConnection();

        $stmt = $db->prepare("SELECT token FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetchColumn();
    }

    public static function create($email, $google_id) {
        $token = bin2hex(random_bytes(16));

        $db = DB::getConnection();
        $stmt = $db->prepare("INSERT INTO users (created_at, email, google_id, token) VALUES (NOW(), ?, ?, ?)");
        $stmt->execute([$email, $google_id, $token]);
        return $token;
    }

    public static function getUserByToken($token) {
        $db = DB::getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE token = ?");
        $stmt->execute([$token]);
        return $stmt->fetch();
    }
}
