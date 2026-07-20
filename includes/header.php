<?php
if (!isset($pageTitle)) {
    $pageTitle = 'Notes';
}
if (!isset($currentNav)) {
    $currentNav = '';
}
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
</head>
<body>
<nav class="navbar navbar-expand-lg app-nav sticky-top">
    <div class="container">
        <a class="navbar-brand" href="/">Notes</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto gap-lg-1">
                <?php
                $navItems = [
                    'home' => ['label' => 'Accueil', 'href' => '/'],
                    'skills' => ['label' => 'Habiletés', 'href' => '/skills.php'],
                    'groups' => ['label' => 'Groupes', 'href' => '/groups.php'],
                    'students' => ['label' => 'Élèves', 'href' => '/students.php'],
                    'tasks' => ['label' => 'Tâches', 'href' => '/tasks.php'],
                ];
                foreach ($navItems as $key => $item) {
                    $active = $currentNav === $key ? ' active' : '';
                    echo '<li class="nav-item"><a class="nav-link' . $active . '" href="' . htmlspecialchars($item['href']) . '">' . htmlspecialchars($item['label']) . '</a></li>';
                }
                ?>
            </ul>
            <ul class="navbar-nav align-items-lg-center gap-lg-1">
                <li class="nav-item">
                    <a class="nav-link nav-link-logout" href="/logout.php">Logout</a>
                </li>
            </ul>
        </div>
    </div>
</nav>
<main class="container app-main">
