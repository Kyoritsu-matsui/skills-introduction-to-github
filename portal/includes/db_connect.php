<?php
// --- データベース接続設定 ---
// ユーザーの環境に合わせて変更してください
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); // XAMPPのデフォルトは空パスワード
define('DB_NAME', 'portal_db');

// データベースに接続
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// 接続チェック
if ($conn->connect_error) {
    die("データベース接続失敗: " . $conn->connect_error);
}

// 文字コード設定
$conn->set_charset("utf8");
?>
