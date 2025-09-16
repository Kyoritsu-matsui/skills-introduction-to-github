<?php
session_start();
require_once '../includes/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        header('Location: ../login.php?error=ユーザー名とパスワードを入力してください。');
        exit();
    }

    // ユーザーをデータベースから検索
    $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // パスワードを検証
        if (password_verify($password, $user['password'])) {
            // ログイン成功
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            header('Location: ../index.php');
            exit();
        } else {
            // パスワードが違う
            header('Location: ../login.php?error=ユーザー名またはパスワードが正しくありません。');
            exit();
        }
    } else {
        // ユーザーが存在しない
        header('Location: ../login.php?error=ユーザー名またはパスワードが正しくありません。');
        exit();
    }

    $stmt->close();
    $conn->close();
} else {
    header('Location: ../login.php');
    exit();
}
?>
