<?php

require_once(__DIR__ . '/db.php');

class Year {
    public static function all() {
        $db = DB::getConnection();

        $stmt = $db->prepare('SELECT * FROM years ORDER BY id DESC');
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function current() {
        $years = self::all();
        return $years[0] ?? null;
    }
}