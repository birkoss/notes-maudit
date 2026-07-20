<?php

include(__DIR__ . '/../config.php');

require_once(__DIR__ . '/../includes/auth.php');
require_once(__DIR__ . '/../includes/student.php');
require_once(__DIR__ . '/../includes/skill.php');
require_once(__DIR__ . '/../includes/note.php');

header('Content-Type: application/json; charset=utf-8');

$user = Auth::requireUser();

$studentId = (int) ($_GET['student_id'] ?? 0);
$skillId = (int) ($_GET['skill_id'] ?? 0);
$termId = (int) ($_GET['term_id'] ?? 0);

if ($studentId < 1 || $skillId < 1 || $termId < 1) {
    http_response_code(400);
    echo json_encode(['error' => 'Paramètres invalides']);
    exit;
}

$student = Student::find($user['id'], $studentId);
$skill = Skill::find($user['id'], $skillId);

if (!$student || !$skill) {
    http_response_code(404);
    echo json_encode(['error' => 'Introuvable']);
    exit;
}

$tasks = Note::detailsForStudentSkill($user['id'], $studentId, $skillId, $termId);
$competencies = Skill::competencyNames($skillId);
$average = Note::averageForStudentSkill($studentId, $skillId, $termId);

echo json_encode([
    'student' => [
        'id' => (int) $student['id'],
        'name' => $student['name'],
    ],
    'skill' => [
        'id' => (int) $skill['id'],
        'name' => $skill['name'],
        'competencies' => $competencies,
    ],
    'term_id' => $termId,
    'average' => $average,
    'scale' => Note::scaleOptions(),
    'tasks' => array_map(function ($row) {
        $hasNote = !empty($row['has_note']);
        return [
            'task_id' => (int) $row['task_id'],
            'task_name' => $row['task_name'],
            'has_note' => $hasNote,
            'note' => (!$hasNote || $row['note'] === null) ? null : (int) $row['note'],
            'is_ne' => $hasNote && $row['note'] === null,
        ];
    }, $tasks),
], JSON_UNESCAPED_UNICODE);
