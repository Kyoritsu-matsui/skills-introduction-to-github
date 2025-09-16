<?php
session_start();
require_once '../includes/db_connect.php';

// ログインチェック
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

if (isset($_POST['submit_post'])) {
    // フォームデータの受け取り
    $user_id = $_SESSION['user_id'];
    $title = $_POST['title'];
    $content = $_POST['content'];
    $importance = $_POST['importance'];

    // 簡単なバリデーション
    if (empty($title) || empty($content) || empty($importance)) {
        // エラーハンドリング (実際にはもっと丁寧に行う)
        header('Location: ../new_post.php?error=必須項目が未入力です。');
        exit();
    }

    // 投稿をデータベースに挿入
    $stmt_post = $conn->prepare("INSERT INTO posts (user_id, title, content, importance, start_date, end_date, is_visible) VALUES (?, ?, ?, ?, NULL, NULL, 1)");
    $stmt_post->bind_param("isss", $user_id, $title, $content, $importance);

    if ($stmt_post->execute()) {
        $post_id = $stmt_post->insert_id;

        // ファイルアップロード処理
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == 0) {
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt'];
            $file_name = basename($_FILES['attachment']['name']);
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            if (in_array($file_ext, $allowed_ext)) {
                $upload_dir = '../uploads/';
                $file_path = $upload_dir . time() . '_' . $file_name;

                if (move_uploaded_file($_FILES['attachment']['tmp_name'], $file_path)) {
                    // 添付ファイル情報をデータベースに挿入
                    $stmt_attachment = $conn->prepare("INSERT INTO attachments (post_id, file_name, file_path) VALUES (?, ?, ?)");
                    $stmt_attachment->bind_param("iss", $post_id, $file_name, $file_path);
                    $stmt_attachment->execute();
                    $stmt_attachment->close();
                }
            } else {
                // 許可されていないファイルタイプの場合、投稿は作成されたがリダイレクトしてエラー表示
                $stmt_post->close();
                $conn->close();
                header('Location: ../new_post.php?error=許可されていないファイル形式です。');
                exit();
            }
        }

        $stmt_post->close();
        $conn->close();

        // 成功したらトップページにリダイレクト
        header('Location: ../index.php?success=投稿が完了しました。');
        exit();
    } else {
        // データベースエラー
        $stmt_post->close();
        $conn->close();
        header('Location: ../new_post.php?error=データベースエラーが発生しました。');
        exit();
    }
} else {
    // 直接アクセスされた場合
    header('Location: ../new_post.php');
    exit();
}
?>
