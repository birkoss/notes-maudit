<?php

include(__DIR__ . "/../config.php");

require_once(__DIR__ . "/../includes/auth.php");

$user = Auth::requireUser();

$pageTitle = 'Notes';
include(__DIR__ . '/../includes/header.php');
?>
    <h1>Home</h1>
    <p class="text-muted">Content goes here.</p>
<?php

include(__DIR__ . '/../includes/footer.php');