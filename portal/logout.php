<?php
session_start();

// セッション変数をすべて解除する
$_SESSION = array();

// セッションを破壊する
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

// ログインページにリダイレクト
header("Location: login.php");
exit;
?>
