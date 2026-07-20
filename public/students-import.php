<?php

include(__DIR__ . "/../config.php");

require_once(__DIR__ . "/../includes/auth.php");
require_once(__DIR__ . "/../includes/year.php");
require_once(__DIR__ . "/../includes/group.php");
require_once(__DIR__ . "/../includes/student.php");

$user = Auth::requireUser();

$currentYear = Year::current();
$groups = $currentYear ? Group::all($user['id'], $currentYear['id']) : [];

$errors = [];
$groupId = null;
$namesText = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $groupId = (int) ($_POST['group_id'] ?? 0);
    $namesText = (string) ($_POST['names'] ?? '');

    if ($groupId === 0) {
        $errors['group_id'] = 'Le groupe est requis';
    } else {
        $group_ok = false;
        foreach ($groups as $single_group) {
            if ((int) $single_group['id'] === $groupId) {
                $group_ok = true;
                break;
            }
        }
        if (!$group_ok) {
            $errors['group_id'] = 'Le groupe est invalide';
        }
    }

    $lines = preg_split('/\R/u', $namesText) ?: [];
    $names = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line !== '') {
            $names[] = $line;
        }
    }

    if (empty($names)) {
        $errors['names'] = 'Ajoutez au moins un élève (un nom par ligne).';
    }

    if (empty($errors)) {
        foreach ($names as $name) {
            Student::create($user['id'], $name, $groupId);
        }
        header('Location: /students.php');
        exit;
    }
}

$pageTitle = 'Importer des élèves — Notes';
$currentNav = 'students';

include(__DIR__ . '/../includes/header.php');
?>
    <h1 class="app-page-title">Importer des élèves</h1>

    <?php if (empty($groups)): ?>
        <p class="app-page-lead">Aucun groupe pour l’année en cours. <a href="/group.php">Créez un groupe</a> avant d’importer des élèves.</p>
    <?php else: ?>
        <p class="app-page-lead">Un nom par ligne. Seuls les abrégés seront conservés (ex. Al. Tr.).</p>

        <form class="app-form" method="post" action="/students-import.php">
            <div class="app-field">
                <label for="group_id">Groupe</label>
                <select id="group_id" name="group_id" required class="<?= isset($errors['group_id']) ? 'is-invalid' : '' ?>">
                    <option value="">Choisir un groupe</option>
                    <?php foreach ($groups as $single_group): ?>
                        <option value="<?= (int) $single_group['id'] ?>"<?= ((int) ($groupId ?? 0) === (int) $single_group['id']) ? ' selected' : '' ?>>
                            <?= htmlspecialchars($single_group['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['group_id'])): ?>
                    <p class="app-field-error"><?= htmlspecialchars($errors['group_id']) ?></p>
                <?php endif; ?>
            </div>

            <div class="app-field">
                <label for="names">Élèves</label>
                <textarea id="names" name="names" rows="12" required placeholder="Alexandre Tremblay&#10;Marie Gagnon&#10;Jean-Philippe Roy" class="<?= isset($errors['names']) ? 'is-invalid' : '' ?>"><?= htmlspecialchars($namesText) ?></textarea>
                <p class="app-field-hint">Un élève par ligne. Abrégé à l’enregistrement (Loi 25).</p>
                <?php if (isset($errors['names'])): ?>
                    <p class="app-field-error"><?= htmlspecialchars($errors['names']) ?></p>
                <?php endif; ?>
            </div>

            <div class="app-form-actions">
                <button type="submit" class="app-btn">Importer les élèves</button>
                <a class="app-btn-secondary" href="/students.php">Annuler</a>
            </div>
        </form>
    <?php endif; ?>
<?php

include(__DIR__ . '/../includes/footer.php');
