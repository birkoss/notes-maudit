<?php

include(__DIR__ . "/../config.php");

require_once(__DIR__ . "/../includes/auth.php");
require_once(__DIR__ . "/../includes/skill.php");
require_once(__DIR__ . "/../includes/competency.php");

$user = Auth::requireUser();

$competencies = Competency::all();
$skillId = (int) ($_GET['id'] ?? 0);
$skill = null;
$selectedCompetencyIds = [];

if ($skillId > 0) {
    $skill = Skill::find($user['id'], $skillId);
    if (!$skill) {
        header('Location: /skills.php');
        exit;
    }
    $selectedCompetencyIds = Skill::competencyIds($skillId);
}

$errors = [];
$name = $skill['name'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete'])) {
        if ($skillId > 0) {
            Skill::delete($user['id'], $skillId);
        }
        header('Location: /skills.php');
        exit;
    }

    $name = trim($_POST['name'] ?? '');
    $postedIds = isset($_POST['competency_ids']) && is_array($_POST['competency_ids'])
        ? $_POST['competency_ids']
        : [];
    $selectedCompetencyIds = Competency::findIds($postedIds);

    if ($name === '') {
        $errors['name'] = 'Le nom est obligatoire';
    }

    if (empty($selectedCompetencyIds)) {
        $errors['competency_ids'] = 'Sélectionnez au moins une compétence';
    }

    if (empty($errors)) {
        if ($skillId > 0) {
            Skill::update($user['id'], $skillId, $name, $selectedCompetencyIds);
        } else {
            Skill::create($user['id'], $name, $selectedCompetencyIds);
        }
        header('Location: /skills.php');
        exit;
    }
}

$isEdit = $skillId > 0;
$pageTitle = ($isEdit ? 'Modifier l\'habileté' : 'Nouvelle habileté') . ' — Notes';
$currentNav = 'skills';

include(__DIR__ . '/../includes/header.php');

$formAction = $isEdit ? '/skill.php?id=' . $skillId : '/skill.php';
?>
    <h1 class="app-page-title"><?= $isEdit ? 'Modifier l\'habileté' : 'Nouvelle habileté' ?></h1>

    <?php if (empty($competencies)): ?>
        <p class="app-page-lead">Aucune compétence n’est disponible pour le moment.</p>
    <?php else: ?>
        <form class="app-form" method="post" action="<?= htmlspecialchars($formAction) ?>">
            <div class="app-field">
                <label for="name">Nom</label>
                <input type="text" id="name" name="name" value="<?= htmlspecialchars($name) ?>" required maxlength="120" autocomplete="off" placeholder="ex. Lecture à voix haute" class="<?= isset($errors['name']) ? 'is-invalid' : '' ?>">
                <?php if (isset($errors['name'])): ?>
                    <p class="app-field-error"><?= htmlspecialchars($errors['name']) ?></p>
                <?php endif; ?>
            </div>

            <fieldset class="app-field app-check-group<?= isset($errors['competency_ids']) ? ' is-invalid' : '' ?>">
                <legend>Compétences</legend>
                <div class="app-check-list">
                    <?php foreach ($competencies as $competency): ?>
                        <?php $cid = (int) $competency['id']; ?>
                        <label class="app-check">
                            <input type="checkbox" name="competency_ids[]" value="<?= $cid ?>"<?= in_array($cid, $selectedCompetencyIds, true) ? ' checked' : '' ?>>
                            <span><?= htmlspecialchars($competency['name']) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <?php if (isset($errors['competency_ids'])): ?>
                    <p class="app-field-error"><?= htmlspecialchars($errors['competency_ids']) ?></p>
                <?php endif; ?>
            </fieldset>

            <div class="app-form-actions">
                <button type="submit" class="app-btn"><?= $isEdit ? 'Enregistrer' : 'Créer l\'habileté' ?></button>
                <a class="app-btn-secondary" href="/skills.php">Annuler</a>
            </div>
        </form>
    <?php endif; ?>
<?php

include(__DIR__ . '/../includes/footer.php');
