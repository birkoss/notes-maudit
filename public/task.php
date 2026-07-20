<?php

include(__DIR__ . "/../config.php");

require_once(__DIR__ . "/../includes/auth.php");
require_once(__DIR__ . "/../includes/year.php");
require_once(__DIR__ . "/../includes/term.php");
require_once(__DIR__ . "/../includes/skill.php");
require_once(__DIR__ . "/../includes/task.php");

$user = Auth::requireUser();

$currentYear = Year::current();
$terms = $currentYear ? Term::allForYear($currentYear['id']) : [];
$skills = Skill::all($user['id']);

$taskId = (int) ($_GET['id'] ?? 0);
$task = null;
$selectedSkillIds = [];

if ($taskId > 0) {
    $task = Task::find($user['id'], $taskId);
    if (!$task) {
        header('Location: /tasks.php');
        exit;
    }
    $selectedSkillIds = Task::skillIds($taskId);
}

$errors = [];
$name = $task['name'] ?? '';
$termId = isset($task['term_id']) ? (int) $task['term_id'] : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete'])) {
        if ($taskId > 0) {
            Task::delete($user['id'], $taskId);
        }
        header('Location: /tasks.php');
        exit;
    }

    $name = trim($_POST['name'] ?? '');
    $termId = (int) ($_POST['term_id'] ?? 0);
    $postedIds = isset($_POST['skill_ids']) && is_array($_POST['skill_ids'])
        ? $_POST['skill_ids']
        : [];
    $selectedSkillIds = Skill::findIds($user['id'], $postedIds);

    if ($name === '') {
        $errors['name'] = 'Le nom est obligatoire';
    }

    if ($termId === 0 || !$currentYear || !Term::find($termId, $currentYear['id'])) {
        $errors['term_id'] = 'L\'étape est invalide';
    }

    if (empty($selectedSkillIds)) {
        $errors['skill_ids'] = 'Sélectionnez au moins une habileté';
    }

    if (empty($errors)) {
        if ($taskId > 0) {
            Task::update($user['id'], $taskId, $name, $termId, $selectedSkillIds);
        } else {
            Task::create($user['id'], $name, $termId, $selectedSkillIds);
        }
        header('Location: /tasks.php');
        exit;
    }
}

$isEdit = $taskId > 0;
$pageTitle = ($isEdit ? 'Modifier la tâche' : 'Nouvelle tâche') . ' — Notes';
$currentNav = 'tasks';

include(__DIR__ . '/../includes/header.php');

$formAction = $isEdit ? '/task.php?id=' . $taskId : '/task.php';
?>
    <h1 class="app-page-title"><?= $isEdit ? 'Modifier la tâche' : 'Nouvelle tâche' ?></h1>

    <?php if (!$currentYear): ?>
        <p class="app-page-lead">Aucune année en cours n’est configurée.</p>
    <?php elseif (empty($terms)): ?>
        <p class="app-page-lead">Aucune étape n’est disponible pour l’année <?= htmlspecialchars($currentYear['name']) ?>.</p>
    <?php elseif (empty($skills)): ?>
        <p class="app-page-lead">Aucune habileté disponible. <a href="/skill.php">Créez une habileté</a> avant d’ajouter une tâche.</p>
    <?php else: ?>
        <form class="app-form" method="post" action="<?= htmlspecialchars($formAction) ?>">
            <div class="app-field">
                <label for="name">Nom</label>
                <input type="text" id="name" name="name" value="<?= htmlspecialchars($name) ?>" required maxlength="120" autocomplete="off" placeholder="ex. Présentation orale" class="<?= isset($errors['name']) ? 'is-invalid' : '' ?>">
                <?php if (isset($errors['name'])): ?>
                    <p class="app-field-error"><?= htmlspecialchars($errors['name']) ?></p>
                <?php endif; ?>
            </div>

            <div class="app-field">
                <label for="term_id">Étape</label>
                <select id="term_id" name="term_id" required class="<?= isset($errors['term_id']) ? 'is-invalid' : '' ?>">
                    <option value="">Choisir une étape</option>
                    <?php foreach ($terms as $term): ?>
                        <option value="<?= (int) $term['id'] ?>"<?= ((int) ($termId ?? 0) === (int) $term['id']) ? ' selected' : '' ?>>
                            Étape <?= htmlspecialchars($term['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['term_id'])): ?>
                    <p class="app-field-error"><?= htmlspecialchars($errors['term_id']) ?></p>
                <?php endif; ?>
            </div>

            <fieldset class="app-field app-check-group<?= isset($errors['skill_ids']) ? ' is-invalid' : '' ?>">
                <legend>Habiletés</legend>
                <div class="app-check-list">
                    <?php foreach ($skills as $skill): ?>
                        <?php $sid = (int) $skill['id']; ?>
                        <label class="app-check">
                            <input type="checkbox" name="skill_ids[]" value="<?= $sid ?>"<?= in_array($sid, $selectedSkillIds, true) ? ' checked' : '' ?>>
                            <span><?= htmlspecialchars($skill['name']) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <?php if (isset($errors['skill_ids'])): ?>
                    <p class="app-field-error"><?= htmlspecialchars($errors['skill_ids']) ?></p>
                <?php endif; ?>
            </fieldset>

            <div class="app-form-actions">
                <button type="submit" class="app-btn"><?= $isEdit ? 'Enregistrer' : 'Créer la tâche' ?></button>
                <a class="app-btn-secondary" href="/tasks.php">Annuler</a>
            </div>
        </form>
    <?php endif; ?>
<?php

include(__DIR__ . '/../includes/footer.php');
