<?php
include(__DIR__ . "/../config.php");

require_once(__DIR__ . "/../includes/user.php");
require_once(__DIR__ . "/../includes/auth.php");

require_once __DIR__.'/../vendor/autoload.php';

$client = new Google_Client();
$client->setApplicationName("Notes");
$client->setClientId(GOOGLE_CLIENT_ID);
$client->setClientSecret(GOOGLE_SECRET);
$client->setRedirectUri("https://notes.maudit.ca/login.php");
$client->addScope("email");

$error = false;

if (isset($_GET['code'])) {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);

    if (!isset($token['error'])) {
         $client->setAccessToken($token['access_token']);

         $google_oauth = new Google_Service_Oauth2($client);
         $google_account_info = $google_oauth->userinfo->get();

         $email = $google_account_info->email;
         $google_id = $google_account_info->id;

         $token = User::getTokenByEmail($email);
         if ($token == "") {
            $token = User::create($email, $google_id);
         }

         if ($token != '') {
            Auth::login($token);
            header('location: /');
            exit;
         }
   }
   
   $error = true;
}
?><!doctype html>
<html lang="en">
   <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <title>Notes — Sign in</title>
      <link rel="preconnect" href="https://fonts.googleapis.com">
      <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
      <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600&family=Source+Sans+3:wght@400;600&display=swap" rel="stylesheet">
      <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
      <link href="assets/style.css?v=<?php echo filemtime(__DIR__ . '/assets/style.css'); ?>" rel="stylesheet" />
      <link rel="icon" href="/favicon.ico" sizes="any">
      <link rel="icon" type="image/png" href="/assets/favicon.png" />
   </head>
   <body class="login">
      <main class="login-stage">
         <div class="login-brand">
            <h1 class="login-title">Notes</h1>
         </div>
         <?php if ($error) { ?>
            <div class="login-error" role="alert">
               There was an unexpected error with the login session.
               Please try again later. If it persists, write
               <a href="mailto:admin@birkoss.com">admin@birkoss.com</a>.
            </div>
         <?php } ?>
         <a class="login-cta" href="<?php echo htmlspecialchars($client->createAuthUrl()); ?>">
            Sign in with Google
         </a>
      </main>
   </body>
</html>