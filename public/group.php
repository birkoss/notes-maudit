<?php

include(__DIR__ . "/../config.php");

require_once(__DIR__ . "/../includes/auth.php");
require_once(__DIR__ . "/../includes/group.php");
require_once(__DIR__ . "/../includes/year.php");

$user = Auth::requireUser();

$years = Year::all();
$groupId = (int) ($_GET['id'] ?? 0);
$group = null;

if ($groupId > 0) {
    $group = Group::find($user['id'], $groupId);
    if (!$group) {
        header('Location: /groups.php');
        exit;
    }
}

$errors = [];
$name = $group['name'] ?? '';
$yearId = isset($group['year_id']) ? (int) $group['year_id'] : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Delete
    if (isset($_POST['delete'])) {
        if ($groupId > 0) {
            Group::delete($user['id'], $groupId);
        }
        header('Location: /groups.php');
        exit;
    }

    // Create / update
    $name = trim($_POST['name'] ?? '');
    $yearId = (int) ($_POST['year_id'] ?? 0);

    if ($name === '') {
        $errors['name'] = 'Le nom est obligatoire';
    }
    if ($yearId === 0) {
        $errors['year_id'] = 'L\'année est requise';
    }

    $year_ok = false;
    foreach ($years as $single_year) {
        if ((int) $single_year['id'] === $yearId) {
            $year_ok = true;
            break;
        }
    }
    if (!$year_ok) {
        $errors['year_id'] = 'L\'année est invalide';
    }

    if (empty($errors)) {
        if ($groupId > 0) {
            Group::update($user['id'], $groupId, $name, $yearId);
        } else {
            Group::create($user['id'], $name, $yearId);
        }
        header('Location: /groups.php');
        exit;
    }
}

$isEdit = $groupId > 0;
$pageTitle = ($isEdit ? 'Modifier le groupe' : 'Nouveau groupe') . ' — Notes';
$currentNav = 'groups';

include(__DIR__ . '/../includes/header.php');

$formAction = $isEdit ? '/group.php?id=' . $groupId : '/group.php';
?>
    <h1 class="app-page-title"><?= $isEdit ? 'Modifier le groupe' : 'Nouveau groupe' ?></h1>

    <form class="app-form" method="post" action="<?= htmlspecialchars($formAction) ?>">
        <div class="app-field">
            <label for="name">Nom</label>
            <input type="text" id="name" name="name" value="<?= htmlspecialchars($name) ?>" required maxlength="120" autocomplete="off" placeholder="ex. G06" class="<?= isset($errors['name']) ? 'is-invalid' : '' ?>">
            <?php if (isset($errors['name'])): ?>
                <p class="app-field-error"><?= htmlspecialchars($errors['name']) ?></p>
            <?php endif; ?>
        </div>

        <div class="app-field">
            <label for="year_id">Année</label>
            <select id="year_id" name="year_id" required class="<?= isset($errors['year_id']) ? 'is-invalid' : '' ?>">
                <?php foreach ($years as $single_year): ?>
                    <option value="<?= (int) $single_year['id'] ?>"<?= ((int) ($yearId ?? 0) === (int) $single_year['id']) ? ' selected' : '' ?>>
                        <?= htmlspecialchars($single_year['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['year_id'])): ?>
                <p class="app-field-error"><?= htmlspecialchars($errors['year_id']) ?></p>
            <?php endif; ?>
        </div>

        <div class="app-form-actions">
            <button type="submit" class="app-btn"><?= $isEdit ? 'Enregistrer' : 'Créer le groupe' ?></button>
            <a class="app-btn-secondary" href="/groups.php">Annuler</a>
        </div>
    </form>
<?php

include(__DIR__ . '/../includes/footer.php');
