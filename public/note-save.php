<?php

include(__DIR__ . '/../config.php');

require_once(__DIR__ . '/../includes/auth.php');
require_once(__DIR__ . '/../includes/student.php');
require_once(__DIR__ . '/../includes/skill.php');
require_once(__DIR__ . '/../includes/task.php');
require_once(__DIR__ . '/../includes/note.php');

header('Content-Type: application/json; charset=utf-8');

$user = Auth::requireUser();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit;
}

$studentId = (int) ($_POST['student_id'] ?? 0);
$taskId = (int) ($_POST['task_id'] ?? 0);
$skillId = (int) ($_POST['skill_id'] ?? 0);
$termId = (int) ($_POST['term_id'] ?? 0);
$rawNote = $_POST['note'] ?? '';

$note = Note::parseInput($rawNote);
if ($note === false) {
    http_response_code(400);
    echo json_encode(['error' => 'Note invalide']);
    exit;
}

$student = Student::find($user['id'], $studentId);
$skill = Skill::find($user['id'], $skillId);
$task = Task::find($user['id'], $taskId);

if (!$student || !$skill || !$task) {
    http_response_code(404);
    echo json_encode(['error' => 'Introuvable']);
    exit;
}

$linked = Task::forSkill($user['id'], $skillId, $termId > 0 ? $termId : (int) $task['term_id']);
$ok = false;
foreach ($linked as $item) {
    if ((int) $item['id'] === $taskId) {
        $ok = true;
        break;
    }
}
if (!$ok) {
    http_response_code(400);
    echo json_encode(['error' => 'Tâche non liée à cette habileté']);
    exit;
}

Note::save($studentId, $taskId, $skillId, $note);

$effectiveTermId = $termId > 0 ? $termId : (int) $task['term_id'];
$average = Note::averageForStudentSkill($studentId, $skillId, $effectiveTermId);

echo json_encode([
    'ok' => true,
    'note' => $note === 'clear' ? null : $note,
    'cleared' => $note === 'clear',
    'average' => $average,
    'student_id' => $studentId,
    'task_id' => $taskId,
    'skill_id' => $skillId,
], JSON_UNESCAPED_UNICODE);
