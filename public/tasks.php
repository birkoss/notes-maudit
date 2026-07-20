<?php

include(__DIR__ . "/../config.php");

require_once(__DIR__ . "/../includes/auth.php");
require_once(__DIR__ . "/../includes/year.php");
require_once(__DIR__ . "/../includes/task.php");

$user = Auth::requireUser();

$currentYear = Year::current();
$tasks = $currentYear ? Task::all($user['id'], $currentYear['id']) : [];

$pageTitle = 'Tâches — Notes';
$currentNav = 'tasks';

include(__DIR__ . '/../includes/header.php');
?>
    <div class="app-page-header">
        <div>
            <h1 class="app-page-title">Tâches</h1>
            <?php if ($currentYear): ?>
                <p class="app-page-lead"><?= htmlspecialchars($currentYear['name']) ?></p>
            <?php endif; ?>
        </div>
        <a class="app-btn" href="/task.php">Ajouter une tâche</a>
    </div>

    <?php if (empty($tasks)): ?>
        <p class="app-page-lead">Aucune tâche pour cette année.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle app-table">
                <thead>
                    <tr>
                        <th scope="col">Nom</th>
                        <th scope="col">Étape</th>
                        <th scope="col">Habiletés</th>
                        <th scope="col" class="text-end">
                            <span class="visually-hidden">Actions</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tasks as $task): ?>
                        <tr>
                            <td class="fw-semibold"><?= htmlspecialchars($task['name']) ?></td>
                            <td>Étape <?= htmlspecialchars($task['term_name']) ?></td>
                            <td><?= htmlspecialchars($task['skill_names'] ?? '') ?></td>
                            <td class="text-end text-nowrap">
                                <div class="app-table-actions">
                                    <a class="app-btn-secondary app-btn-sm" href="/task.php?id=<?= (int) $task['id'] ?>">Modifier</a>
                                    <form method="post" action="/task.php?id=<?= (int) $task['id'] ?>" onsubmit="return confirm('Supprimer cette tâche ?');">
                                        <button type="submit" name="delete" value="1" class="app-btn-danger">Supprimer</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
<?php

include(__DIR__ . '/../includes/footer.php');
