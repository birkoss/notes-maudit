<?php

include(__DIR__ . "/../config.php");

require_once(__DIR__ . "/../includes/auth.php");
require_once(__DIR__ . "/../includes/year.php");
require_once(__DIR__ . "/../includes/group.php");
require_once(__DIR__ . "/../includes/student.php");

$user = Auth::requireUser();

$currentYear = Year::current();
$groups = $currentYear ? Group::all($user['id'], $currentYear['id']) : [];

$studentId = (int) ($_GET['id'] ?? 0);
$student = null;

if ($studentId > 0) {
    $student = Student::find($user['id'], $studentId);
    if (!$student) {
        header('Location: /students.php');
        exit;
    }
}

$errors = [];
$name = $student['name'] ?? '';
$groupId = isset($student['group_id']) ? (int) $student['group_id'] : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete'])) {
        if ($studentId > 0) {
            Student::delete($user['id'], $studentId);
        }
        header('Location: /students.php');
        exit;
    }

    $name = trim($_POST['name'] ?? '');
    $groupId = (int) ($_POST['group_id'] ?? 0);

    if ($name === '') {
        $errors['name'] = 'Le nom est obligatoire';
    }

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

    if (empty($errors)) {
        if ($studentId > 0) {
            Student::update($user['id'], $studentId, $name, $groupId);
        } else {
            Student::create($user['id'], $name, $groupId);
        }
        header('Location: /students.php');
        exit;
    }
}

$isEdit = $studentId > 0;
$pageTitle = ($isEdit ? 'Modifier l\'élève' : 'Nouvel élève') . ' — Notes';
$currentNav = 'students';

include(__DIR__ . '/../includes/header.php');

$formAction = $isEdit ? '/student.php?id=' . $studentId : '/student.php';
?>
    <h1 class="app-page-title"><?= $isEdit ? 'Modifier l\'élève' : 'Nouvel élève' ?></h1>

    <?php if (empty($groups)): ?>
        <p class="app-page-lead">Aucun groupe pour l’année en cours. <a href="/group.php">Créez un groupe</a> avant d’ajouter un élève.</p>
    <?php else: ?>
        <form class="app-form" method="post" action="<?= htmlspecialchars($formAction) ?>">
            <div class="app-field">
                <label for="name">Nom</label>
                <input type="text" id="name" name="name" value="<?= htmlspecialchars($name) ?>" required maxlength="120" autocomplete="off" placeholder="ex. Alexandre Tremblay" class="<?= isset($errors['name']) ? 'is-invalid' : '' ?>">
                <p class="app-field-hint">Seul un abrégé est conservé (ex. Al. Tr.), conformément à la Loi 25.</p>
                <?php if (isset($errors['name'])): ?>
                    <p class="app-field-error"><?= htmlspecialchars($errors['name']) ?></p>
                <?php endif; ?>
            </div>

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

            <div class="app-form-actions">
                <button type="submit" class="app-btn"><?= $isEdit ? 'Enregistrer' : 'Créer l\'élève' ?></button>
                <a class="app-btn-secondary" href="/students.php">Annuler</a>
            </div>
        </form>
    <?php endif; ?>
<?php

include(__DIR__ . '/../includes/footer.php');
