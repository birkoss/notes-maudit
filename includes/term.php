<?php

require_once(__DIR__ . '/db.php');

class Term {
    public static function allForYear($yearId) {
        $db = DB::getConnection();

        $stmt = $db->prepare(
            'SELECT * FROM terms WHERE year_id = :year_id ORDER BY id ASC'
        );
        $stmt->execute(['year_id' => $yearId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function find($termId, $yearId = null) {
        $db = DB::getConnection();

        if ($yearId === null) {
            $stmt = $db->prepare('SELECT * FROM terms WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $termId]);
        } else {
            $stmt = $db->prepare(
                'SELECT * FROM terms WHERE id = :id AND year_id = :year_id LIMIT 1'
            );
            $stmt->execute([
                'id' => $termId,
                'year_id' => $yearId,
            ]);
        }

        $term = $stmt->fetch(PDO::FETCH_ASSOC);
        return $term ?: null;
    }
}
