<?php

include(__DIR__ . "/../config.php");

require_once(__DIR__ . "/../includes/auth.php");

$user = Auth::requireUser();

$pageTitle = 'Habiletés — Notes';
$currentNav = 'skills';

include(__DIR__ . '/../includes/header.php');
?>
    <h1 class="app-page-title">Habiletés</h1>
    <p class="app-page-lead">Content goes here.</p>
<?php

include(__DIR__ . '/../includes/footer.php');
