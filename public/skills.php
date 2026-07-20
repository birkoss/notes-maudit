<?php

include(__DIR__ . "/../config.php");

require_once(__DIR__ . "/../includes/auth.php");
require_once(__DIR__ . "/../includes/skill.php");

$user = Auth::requireUser();

$skills = Skill::all($user['id']);

$pageTitle = 'Habiletés — Notes';
$currentNav = 'skills';

include(__DIR__ . '/../includes/header.php');
?>
    <div class="app-page-header">
        <div>
            <h1 class="app-page-title">Habiletés</h1>
            <p class="app-page-lead">Gérez vos habiletés et leurs compétences.</p>
        </div>
        <a class="app-btn" href="/skill.php">Ajouter une habileté</a>
    </div>

    <?php if (empty($skills)): ?>
        <p class="app-page-lead">Aucune habileté pour le moment.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle app-table">
                <thead>
                    <tr>
                        <th scope="col">Nom</th>
                        <th scope="col">Compétences</th>
                        <th scope="col" class="text-end">
                            <span class="visually-hidden">Actions</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($skills as $skill): ?>
                        <tr>
                            <td class="fw-semibold"><?= htmlspecialchars($skill['name']) ?></td>
                            <td><?= htmlspecialchars($skill['competency_names'] ?? '') ?></td>
                            <td class="text-end text-nowrap">
                                <div class="app-table-actions">
                                    <a class="app-btn-secondary app-btn-sm" href="/skill.php?id=<?= (int) $skill['id'] ?>">Modifier</a>
                                    <form method="post" action="/skill.php?id=<?= (int) $skill['id'] ?>" onsubmit="return confirm('Supprimer cette habileté ?');">
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
