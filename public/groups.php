<?php

include(__DIR__ . "/../config.php");

require_once(__DIR__ . "/../includes/auth.php");
require_once(__DIR__ . "/../includes/year.php");
require_once(__DIR__ . "/../includes/group.php");

$user = Auth::requireUser();

$currentYear = Year::current();
$groups = Group::all($user['id'], $currentYear['id']);

$pageTitle = 'Groupes — Notes';
$currentNav = 'groups';

include(__DIR__ . '/../includes/header.php');
?>
    <div class="app-page-header">
        <div>
            <h1 class="app-page-title">Groupes</h1>
            <?php if ($currentYear): ?>
                <p class="app-page-lead"><?= htmlspecialchars($currentYear['name']) ?></p>
            <?php endif; ?>
        </div>
        <a class="app-btn" href="/group.php">Ajouter un groupe</a>
    </div>

    <?php if (empty($groups)): ?>
        <p class="app-page-lead">Aucun groupe pour cette année.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle app-table">
                <thead>
                    <tr>
                        <th scope="col">Nom</th>
                        <th scope="col" class="text-end">
                            <span class="visually-hidden">Actions</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($groups as $group): ?>
                        <tr>
                            <td class="fw-semibold"><?= htmlspecialchars($group['name']) ?></td>
                            <td class="text-end text-nowrap">
                                <div class="app-table-actions">
                                    <a class="app-btn-secondary app-btn-sm" href="/group.php?id=<?= (int) $group['id'] ?>">Modifier</a>
                                    <form method="post" action="/group.php?id=<?= (int) $group['id'] ?>" onsubmit="return confirm('Supprimer ce groupe ?');">
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
