<?php

include(__DIR__ . "/../config.php");

require_once(__DIR__ . "/../includes/auth.php");
require_once(__DIR__ . "/../includes/group.php");
require_once(__DIR__ . "/../includes/year.php");

$user = Auth::requireUser();

$years = Year::all();

$errors = [];
$name = '';
$yearId = null;

// Form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']) ?? '';
    $yearId = (int)($_POST['year_id'] ?? 0);

    if ($name === '') {
        $errors['name'] = 'Le nom est obligatoire';
    }
    if ($yearId === 0) {
        $errors['year_id'] = 'L\'année est requise';
    }
    $year_ok = false;
    foreach ($years as $single_year) {
        if ((int)$single_year['id'] === $yearId) {
            $year_ok = true;
            break;
        }
    }
    if (!$year_ok) {
        $errors['year_id'] = 'L\'année est invalide';
    }
    
    if (empty($errors)) {
        $group = Group::create($user['id'], $name, $yearId);
        header('Location: /groups.php');
        exit;
    }
}

// Show the page

$pageTitle = 'Nouveau groupe — Notes';
$currentNav = 'groups';

include(__DIR__ . '/../includes/header.php');
?>
    <h1 class="app-page-title">Nouveau groupe</h1>

    <form class="app-form" method="post" action="/group.php">
        <div class="app-field">
            <label for="name">Nom</label>
            <input type="text" id="name" name="name" value="<?= htmlspecialchars($name ?? '') ?>" required maxlength="120" autocomplete="off" placeholder="ex. G06" class="<?= isset($errors['name']) ? 'is-invalid' : '' ?>">
            <?php if (isset($errors['name'])): ?>
                <p class="app-field-error"><?= htmlspecialchars($errors['name']) ?></p>
            <?php endif; ?>
        </div>

        <div class="app-field">
            <label for="year_id">Année</label>
            <select id="year_id" name="year_id" required class="<?= isset($errors['year_id']) ? 'is-invalid' : '' ?>">
                <?php foreach ($years as $single_year): ?>
                    <option value="<?= htmlspecialchars($single_year['id']) ?>" <?= ((int)($yearId ?? 0) === (int)$single_year['id']) ? ' selected' : '' ?>>
                        <?= htmlspecialchars($single_year['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['year_id'])): ?>
                <p class="app-field-error"><?= htmlspecialchars($errors['year_id']) ?></p>
            <?php endif; ?>
        </div>

        <div class="app-form-actions">
            <button type="submit" class="app-btn">Créer le groupe</button>
            <a class="app-btn-secondary" href="/groups.php">Annuler</a>
        </div>
    </form>
<?php

include(__DIR__ . '/../includes/footer.php');
