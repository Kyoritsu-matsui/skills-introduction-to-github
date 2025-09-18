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
    $post_id = $_POST['id'];

    $pdo->beginTransaction();
    try {
        // 1. 投稿に関連する添付ファイルを取得
        $stmt_files = $pdo->prepare("SELECT file_path FROM attachments WHERE post_id = :post_id");
        $stmt_files->bindParam(':post_id', $post_id, PDO::PARAM_INT);
        $stmt_files->execute();
        $files_to_delete = $stmt_files->fetchAll();

        // 2. サーバーから添付ファイルを削除
        if ($files_to_delete) {
            foreach ($files_to_delete as $file) {
                $file_path_on_server = 'uploads/' . $file['file_path'];
                if (file_exists($file_path_on_server)) {
                    unlink($file_path_on_server);
                }
            }
        }

        // 3. データベースの投稿レコードを削除
        // ON DELETE CASCADEにより、attachmentsテーブルの関連レコードも自動的に削除される
        $stmt_delete = $pdo->prepare("DELETE FROM posts WHERE id = :id");
        $stmt_delete->bindParam(':id', $post_id, PDO::PARAM_INT);
        $stmt_delete->execute();

        $pdo->commit();

        // 4. 管理ページにリダイレクト
        header('Location: admin.php');
        exit;

    } catch (PDOException $e) {
        $pdo->rollBack();
        // エラーハンドリング
        die("エラーが発生しました: " . $e->getMessage());
    }
} else {
    // 不正なアクセスの場合はトップページへ
    header('Location: index.php');
    exit;
}
?>
