<?php

include(__DIR__ . "/../config.php");

require_once(__DIR__ . "/../includes/auth.php");

$user = Auth::requireUser();

$pageTitle = 'Groupes — Notes';
$currentNav = 'groups';

include(__DIR__ . '/../includes/header.php');
?>
    <div class="app-page-header">
        <div>
            <h1 class="app-page-title">Groupes</h1>
            <p class="app-page-lead">Content goes here.</p>
        </div>
        <a class="app-btn" href="/group.php">Ajouter un groupe</a>
    </div>
<?php

include(__DIR__ . '/../includes/footer.php');
