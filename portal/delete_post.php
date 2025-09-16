<?php
session_start();
require_once 'includes/db_connect.php';

// ログインチェックと管理者ロールチェック
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php?error=アクセス権限がありません。');
    exit();
}

if (isset($_GET['id'])) {
    $post_id = $_GET['id'];

    // 添付ファイルの実ファイルを削除 (これはDBのCASCADE制約ではできない)
    $stmt_select_files = $conn->prepare("SELECT file_path FROM attachments WHERE post_id = ?");
    $stmt_select_files->bind_param("i", $post_id);
    $stmt_select_files->execute();
    $result = $stmt_select_files->get_result();
    while ($row = $result->fetch_assoc()) {
        if (file_exists($row['file_path'])) {
            unlink($row['file_path']);
        }
    }
    $stmt_select_files->close();

    // 投稿を削除 (attachmentsテーブルのレコードもCASCADEで削除される)
    $stmt_delete_post = $conn->prepare("DELETE FROM posts WHERE id = ?");
    $stmt_delete_post->bind_param("i", $post_id);

    if ($stmt_delete_post->execute()) {
        header('Location: admin.php?success=投稿を削除しました。');
    } else {
        header('Location: admin.php?error=削除に失敗しました。');
    }
    $stmt_delete_post->close();
    $conn->close();
} else {
    header('Location: admin.php');
}
exit();
?>
