<?php

require_once(__DIR__ . '/db.php');

class Student {
    /**
     * Pseudonymise un nom pour la conservation (Loi 25).
     * Ex. "Alexandre Tremblay" → "Al. Tr."
     */
    public static function abbreviateName($name) {
        $name = trim(preg_replace('/\s+/u', ' ', $name));
        if ($name === '') {
            return '';
        }

        $parts = preg_split('/\s+/u', $name);
        $first = self::abbreviatePart($parts[0]);
        if (count($parts) === 1) {
            return $first;
        }

        $last = self::abbreviatePart($parts[count($parts) - 1]);
        return $first . ' ' . $last;
    }

    private static function abbreviatePart($part) {
        $part = rtrim($part, '.');
        $part = mb_substr($part, 0, 2, 'UTF-8');
        if ($part === '') {
            return '';
        }
        $first = mb_strtoupper(mb_substr($part, 0, 1, 'UTF-8'), 'UTF-8');
        $rest = mb_strtolower(mb_substr($part, 1, null, 'UTF-8'), 'UTF-8');
        return $first . $rest . '.';
    }

    public static function all($userId, $yearId) {
        $db = DB::getConnection();

        $stmt = $db->prepare(
            'SELECT s.*, g.name AS group_name
             FROM students s
             INNER JOIN student_groups g ON g.id = s.group_id
             WHERE s.user_id = :user_id
               AND g.user_id = :group_user_id
               AND g.year_id = :year_id
               AND s.deleted_at IS NULL
               AND g.deleted_at IS NULL
             ORDER BY g.name ASC, s.name ASC'
        );
        $stmt->execute([
            'user_id' => $userId,
            'group_user_id' => $userId,
            'year_id' => $yearId,
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function find($userId, $studentId) {
        $db = DB::getConnection();

        $stmt = $db->prepare(
            'SELECT * FROM students WHERE id = :id AND user_id = :user_id AND deleted_at IS NULL LIMIT 1'
        );
        $stmt->execute([
            'id' => $studentId,
            'user_id' => $userId,
        ]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        return $student ?: null;
    }

    public static function create($userId, $name, $groupId) {
        $db = DB::getConnection();
        $name = self::abbreviateName($name);

        $stmt = $db->prepare(
            'INSERT INTO students (user_id, name, group_id) VALUES (:user_id, :name, :group_id)'
        );
        $stmt->execute([
            'user_id' => $userId,
            'name' => $name,
            'group_id' => $groupId,
        ]);
        return (int) $db->lastInsertId();
    }

    public static function update($userId, $studentId, $name, $groupId) {
        $db = DB::getConnection();
        $name = self::abbreviateName($name);

        $stmt = $db->prepare(
            'UPDATE students SET name = :name, group_id = :group_id WHERE id = :id AND user_id = :user_id AND deleted_at IS NULL'
        );
        $stmt->execute([
            'name' => $name,
            'group_id' => $groupId,
            'id' => $studentId,
            'user_id' => $userId,
        ]);
        return $stmt->rowCount() > 0;
    }

    public static function delete($userId, $studentId) {
        $db = DB::getConnection();

        $stmt = $db->prepare(
            'UPDATE students SET deleted_at = NOW() WHERE id = :id AND user_id = :user_id AND deleted_at IS NULL'
        );
        $stmt->execute([
            'id' => $studentId,
            'user_id' => $userId,
        ]);
        return $stmt->rowCount() > 0;
    }
}
