<?php

include(__DIR__ . "/../config.php");

require_once(__DIR__ . "/../includes/auth.php");

$user = Auth::requireUser();

$pageTitle = 'Élèves — Notes';
$currentNav = 'tasks';

include(__DIR__ . '/../includes/header.php');
?>
    <h1 class="app-page-title">Élèves</h1>
    <p class="app-page-lead">Content goes here.</p>
<?php

include(__DIR__ . '/../includes/footer.php');
