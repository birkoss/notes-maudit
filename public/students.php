<?php

include(__DIR__ . "/../config.php");

require_once(__DIR__ . "/../includes/auth.php");
require_once(__DIR__ . "/../includes/year.php");
require_once(__DIR__ . "/../includes/student.php");

$user = Auth::requireUser();

$currentYear = Year::current();
$students = $currentYear ? Student::all($user['id'], $currentYear['id']) : [];

$pageTitle = 'Élèves — Notes';
$currentNav = 'students';

include(__DIR__ . '/../includes/header.php');
?>
    <div class="app-page-header">
        <div>
            <h1 class="app-page-title">Élèves</h1>
            <?php if ($currentYear): ?>
                <p class="app-page-lead"><?= htmlspecialchars($currentYear['name']) ?></p>
            <?php endif; ?>
        </div>
        <div class="app-page-actions">
            <a class="app-btn" href="/student.php">Ajouter un nouvel élève</a>
            <a class="app-btn-secondary" href="/students-import.php">Importer des élèves</a>
        </div>
    </div>

    <?php if (empty($students)): ?>
        <p class="app-page-lead">Aucun élève pour cette année.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle app-table">
                <thead>
                    <tr>
                        <th scope="col">Nom</th>
                        <th scope="col">Groupe</th>
                        <th scope="col" class="text-end">
                            <span class="visually-hidden">Actions</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student): ?>
                        <tr>
                            <td class="fw-semibold"><?= htmlspecialchars($student['name']) ?></td>
                            <td><?= htmlspecialchars($student['group_name']) ?></td>
                            <td class="text-end text-nowrap">
                                <div class="app-table-actions">
                                    <a class="app-btn-secondary app-btn-sm" href="/student.php?id=<?= (int) $student['id'] ?>">Modifier</a>
                                    <form method="post" action="/student.php?id=<?= (int) $student['id'] ?>" onsubmit="return confirm('Supprimer cet élève ?');">
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
