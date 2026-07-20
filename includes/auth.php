<?php

require_once(__DIR__ . "/../includes/user.php");

class Auth {
    public static function login($token) {
        setcookie('LOGIN_TOKEN', $token, strtotime('+30 days'));
    }

    public static function logout() {
        setcookie('LOGIN_TOKEN', '', time() - 3600);
    }

    public static function requireUser() {
        if (!isset($_COOKIE['LOGIN_TOKEN'])) {
            header('location: /login.php');
            exit;
        }

        $token = $_COOKIE['LOGIN_TOKEN'];
        $user = User::getUserByToken($token);

        return $user;
    }
}
