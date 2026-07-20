<?php

require_once(__DIR__ . '/db.php');

class Competency {
    public static function all() {
        $db = DB::getConnection();

        $stmt = $db->prepare('SELECT * FROM competencies ORDER BY name ASC');
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findIds(array $ids) {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        $ids = array_filter($ids, function ($id) {
            return $id > 0;
        });
        if (empty($ids)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $db = DB::getConnection();
        $stmt = $db->prepare("SELECT id FROM competencies WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }
}
