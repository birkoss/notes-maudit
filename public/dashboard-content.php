<?php

include(__DIR__ . '/../config.php');

require_once(__DIR__ . '/../includes/auth.php');
require_once(__DIR__ . '/../includes/year.php');
require_once(__DIR__ . '/../includes/student.php');
require_once(__DIR__ . '/../includes/skill.php');
require_once(__DIR__ . '/../includes/task.php');
require_once(__DIR__ . '/../includes/note.php');

$user = Auth::requireUser();

$currentYear = Year::current();
if (!$currentYear) {
    echo '<p class="app-page-lead">Aucune année en cours.</p>';
    exit;
}

$groupId = (int) ($_GET['group_id'] ?? 0);
$termId = (int) ($_GET['term_id'] ?? 0);
$competencyId = (int) ($_GET['competency_id'] ?? 0);
$skillId = (int) ($_GET['skill_id'] ?? 0);

if ($groupId < 1) {
    echo '<p class="app-page-lead">Choisissez un groupe pour afficher le tableau.</p>';
    exit;
}

$students = Student::allByGroup($user['id'], $groupId);
if (empty($students)) {
    echo '<p class="app-page-lead">Aucun élève dans ce groupe.</p>';
    exit;
}

function formatNoteCell($value) {
    if ($value === null) {
        return 'N/E';
    }
    return htmlspecialchars((string) $value);
}

function formatTaskNoteCell(array $notes, $studentId, $taskId) {
    if (!isset($notes[$studentId]) || !array_key_exists($taskId, $notes[$studentId])) {
        return '—';
    }
    if ($notes[$studentId][$taskId] === null) {
        return 'N/E';
    }
    return htmlspecialchars((string) $notes[$studentId][$taskId]);
}

function formatAverageCell(array $averages, $studentId, $skillId) {
    if (!isset($averages[$studentId][$skillId])) {
        return '—';
    }
    $value = $averages[$studentId][$skillId];
    if ($value === 'N/E') {
        return 'N/E';
    }
    return htmlspecialchars((string) $value);
}

// Vue 3 : une habileté → colonnes = tâches
if ($skillId > 0) {
    $tasks = Task::forSkill($user['id'], $skillId, $termId);
    $notes = Note::notesByTaskForSkill($user['id'], $groupId, $skillId, $termId);

    if (empty($tasks)) {
        echo '<p class="app-page-lead">Aucune tâche pour cette habileté' . ($termId > 0 ? ' dans cette étape' : '') . '.</p>';
        exit;
    }
    ?>
    <div class="table-responsive">
        <table class="table table-hover align-middle app-table dashboard-grid">
            <thead>
                <tr>
                    <th scope="col">Élève</th>
                    <?php foreach ($tasks as $task): ?>
                        <th scope="col" class="text-center"><?= htmlspecialchars($task['name']) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $student): ?>
                    <?php $sid = (int) $student['id']; ?>
                    <tr>
                        <td class="fw-semibold"><?= htmlspecialchars($student['name']) ?></td>
                        <?php foreach ($tasks as $task): ?>
                            <?php
                            $tid = (int) $task['id'];
                            $display = formatTaskNoteCell($notes, $sid, $tid);
                            $cellTerm = $termId > 0 ? $termId : (int) $task['term_id'];
                            ?>
                            <td class="text-center">
                                <button
                                    type="button"
                                    class="note-cell"
                                    data-student-id="<?= $sid ?>"
                                    data-skill-id="<?= $skillId ?>"
                                    data-task-id="<?= $tid ?>"
                                    data-term-id="<?= $cellTerm ?>"
                                    data-mode="task"
                                ><?= $display ?></button>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
    exit;
}

// Vue 2 : une étape → colonnes = habiletés (moyenne)
if ($termId > 0) {
    $skills = Skill::forTerm($user['id'], $termId, $competencyId);
    $averages = Note::averagesBySkillForTerm($user['id'], $groupId, $termId, $competencyId);

    if (empty($skills)) {
        echo '<p class="app-page-lead">Aucune habileté liée à des tâches de cette étape.</p>';
        exit;
    }
    ?>
    <div class="table-responsive">
        <table class="table table-hover align-middle app-table dashboard-grid">
            <thead>
                <tr>
                    <th scope="col">Élève</th>
                    <?php foreach ($skills as $skill): ?>
                        <th scope="col" class="text-center"><?= htmlspecialchars($skill['name']) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $student): ?>
                    <?php $sid = (int) $student['id']; ?>
                    <tr>
                        <td class="fw-semibold"><?= htmlspecialchars($student['name']) ?></td>
                        <?php foreach ($skills as $skill): ?>
                            <?php
                            $skid = (int) $skill['id'];
                            $display = formatAverageCell($averages, $sid, $skid);
                            ?>
                            <td class="text-center">
                                <button
                                    type="button"
                                    class="note-cell"
                                    data-student-id="<?= $sid ?>"
                                    data-skill-id="<?= $skid ?>"
                                    data-term-id="<?= $termId ?>"
                                    data-mode="skill"
                                ><?= $display ?></button>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
    exit;
}

// Vue 1 : groupe seulement → liste des élèves
?>
<div class="table-responsive">
    <table class="table table-hover align-middle app-table">
        <thead>
            <tr>
                <th scope="col">Élève</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($students as $student): ?>
                <tr>
                    <td class="fw-semibold"><?= htmlspecialchars($student['name']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
