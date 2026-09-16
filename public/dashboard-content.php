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

if ($termId < 1 && $competencyId < 1 && $skillId < 1) {
    echo '<p class="app-page-lead">Choisissez une étape, une compétence ou une habileté.</p>';
    exit;
}

$students = Student::allByGroup($user['id'], $groupId);
if (empty($students)) {
    echo '<p class="app-page-lead">Aucun élève dans ce groupe.</p>';
    exit;
}

function formatTaskNoteCell(array $notes, $studentId, $taskId, $skillId) {
    if (!isset($notes[$studentId][$taskId]) || !array_key_exists($skillId, $notes[$studentId][$taskId])) {
        return '—';
    }
    if ($notes[$studentId][$taskId][$skillId] === null) {
        return 'N/E';
    }
    return htmlspecialchars((string) $notes[$studentId][$taskId][$skillId]);
}

$columns = Task::dashboardColumns($user['id'], (int) $currentYear['id'], $termId, $skillId, $competencyId);
if (empty($columns)) {
    echo '<p class="app-page-lead">Aucune tâche pour ces filtres.</p>';
    exit;
}

$notes = Note::notesByTaskForSkill($user['id'], $groupId, $skillId, $termId);
$taskIds = array_values(array_unique(array_map(function ($column) {
    return (int) $column['task_id'];
}, $columns)));
$rates = Note::successRatesByTask($groupId, $taskIds);

$skillIdsInColumns = array_unique(array_map(function ($column) {
    return (int) $column['skill_id'];
}, $columns));
$showSkillInHeader = count($skillIdsInColumns) > 1;
?>
    <div class="table-responsive">
        <table class="table table-hover align-middle app-table dashboard-grid">
            <thead>
                <tr>
                    <th scope="col">Élève</th>
                    <?php foreach ($columns as $column): ?>
                        <th scope="col" class="text-center">
                            <?= htmlspecialchars($column['task_name']) ?>
                            <?php if ($showSkillInHeader): ?>
                                <span class="dashboard-col-skill"><?= htmlspecialchars($column['skill_name']) ?></span>
                            <?php endif; ?>
                        </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $student): ?>
                    <?php $sid = (int) $student['id']; ?>
                    <tr class="student-row" data-student-id="<?= $sid ?>">
                        <td class="fw-semibold"><a class="filter-student" href="#" data-student-id="<?= $sid ?>"><?= htmlspecialchars($student['name']) ?></a></td>
                        <?php foreach ($columns as $column): ?>
                            <?php
                            $tid = (int) $column['task_id'];
                            $cellSkillId = (int) $column['skill_id'];
                            $display = formatTaskNoteCell($notes, $sid, $tid, $cellSkillId);
                            $cellTerm = $termId > 0 ? $termId : (int) $column['term_id'];
                            ?>
                            <td class="text-center">
                                <button
                                    type="button"
                                    class="note-cell"
                                    data-student-id="<?= $sid ?>"
                                    data-skill-id="<?= $cellSkillId ?>"
                                    data-task-id="<?= $tid ?>"
                                    data-term-id="<?= $cellTerm ?>"
                                    data-mode="task"
                                ><?= $display ?></button>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="dashboard-success-row">
                    <th scope="row">Réussite</th>
                    <?php foreach ($columns as $column): ?>
                        <?php
                        $taskId = (int) $column['task_id'];
                        $rate = $rates[$taskId] ?? null;
                        ?>
                        <td class="text-center fw-semibold" data-task-rate="<?= $taskId ?>">
                            <?= $rate === null ? '—' : ((int) $rate) . ' %' ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            </tfoot>
        </table>
    </div>
