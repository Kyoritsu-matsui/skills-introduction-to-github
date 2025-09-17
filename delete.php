<?php
session_start();
require_once 'db_connect.php';

// ログインチェック
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// POSTリクエストとIDの存在をチェック
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = $_POST['id'];

    try {
        // 1. 削除前にファイルパスを取得
        $stmt = $pdo->prepare("SELECT file_path FROM notifications WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $notification = $stmt->fetch();

        if ($notification && !empty($notification['file_path'])) {
            // 2. 添付ファイルが存在すれば削除
            if (file_exists($notification['file_path'])) {
                unlink($notification['file_path']);
            }
        }

        // 3. データベースのレコードを削除
        $stmt_delete = $pdo->prepare("DELETE FROM notifications WHERE id = :id");
        $stmt_delete->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt_delete->execute();

        // 4. 管理ページにリダイレクト
        header('Location: admin.php');
        exit;

    } catch (PDOException $e) {
        // エラーハンドリング（実際にはエラーページにリダイレクトする方が親切）
        die("エラーが発生しました: " . $e->getMessage());
    }
} else {
    // 不正なアクセスの場合はトップページへ
    header('Location: index.php');
    exit;
}
?>
