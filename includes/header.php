<?php
if (!isset($pageTitle)) {
    $pageTitle = 'Notes';
}
if (!isset($currentNav)) {
    $currentNav = '';
}

$navItems = [
    ['id' => 'home', 'label' => 'Accueil', 'href' => '/'],
    ['type' => 'separator'],
    ['id' => 'skills', 'label' => 'Habiletés', 'href' => '/skills.php'],
    ['id' => 'tasks', 'label' => 'Tâches', 'href' => '/tasks.php'],
    ['type' => 'separator'],
    ['id' => 'groups', 'label' => 'Groupes', 'href' => '/groups.php'],
    ['id' => 'students', 'label' => 'Élèves', 'href' => '/students.php'],
];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600&family=Source+Sans+3:wght@400;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/style.css?v=<?php echo filemtime(__DIR__ . '/../public/assets/style.css'); ?>" rel="stylesheet">
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" type="image/png" href="/assets/favicon.png">
</head>
<body>
<nav class="navbar navbar-expand-lg app-nav sticky-top">
    <div class="container">
        <a class="navbar-brand" href="/">Notes</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto gap-lg-1 align-items-lg-center">
                <?php foreach ($navItems as $item): ?>
                    <?php if (($item['type'] ?? 'link') === 'separator'): ?>
                        <li class="nav-item nav-separator" aria-hidden="true"></li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link<?= $currentNav === $item['id'] ? ' active' : '' ?>" href="<?= htmlspecialchars($item['href']) ?>">
                                <?= htmlspecialchars($item['label']) ?>
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
            <ul class="navbar-nav align-items-lg-center gap-lg-1">
                <li class="nav-item">
                    <a class="nav-link nav-link-logout" href="/logout.php">Déconnexion</a>
                </li>
            </ul>
        </div>
    </div>
</nav>
<main class="container app-main">
