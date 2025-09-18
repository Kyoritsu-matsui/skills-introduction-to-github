<?php
session_start();
require_once 'db_connect.php';

// ログインチェック
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$error_message = '';
$success_message = '';

// フォームが送信された場合の処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';
    $importance = $_POST['importance'] ?? '';
    $user_id = $_SESSION['user_id'];

    // バリデーション
    if (empty($title) || empty($content) || empty($importance) || empty($user_id)) {
        $error_message = 'すべての必須項目を入力してください。';
    } else {
        $pdo->beginTransaction();
        try {
            // 投稿をpostsテーブルに挿入
            $stmt = $pdo->prepare(
                "INSERT INTO posts (user_id, title, content, importance)
                 VALUES (:user_id, :title, :content, :importance)"
            );
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':title', $title, PDO::PARAM_STR);
            $stmt->bindParam(':content', $content, PDO::PARAM_STR);
            $stmt->bindParam(':importance', $importance, PDO::PARAM_STR);
            $stmt->execute();

            $post_id = $pdo->lastInsertId();

            // ファイルアップロード処理
            if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = 'uploads/';
                $original_file_name = basename($_FILES['attachment']['name']);
                // ファイル名を一意にする
                $unique_file_name = uniqid('', true) . '_' . $original_file_name;
                $file_path_on_server = $upload_dir . $unique_file_name;

                if (move_uploaded_file($_FILES['attachment']['tmp_name'], $file_path_on_server)) {
                    // 添付ファイルをattachmentsテーブルに挿入
                    $stmt_file = $pdo->prepare(
                        "INSERT INTO attachments (post_id, file_name, file_path)
                         VALUES (:post_id, :file_name, :file_path)"
                    );
                    $stmt_file->bindParam(':post_id', $post_id, PDO::PARAM_INT);
                    $stmt_file->bindParam(':file_name', $original_file_name, PDO::PARAM_STR);
                    $stmt_file->bindParam(':file_path', $unique_file_name, PDO::PARAM_STR);
                    $stmt_file->execute();
                } else {
                    throw new Exception('ファイルのアップロードに失敗しました。');
                }
            }

            $pdo->commit();
            // 成功したらトップページへリダイレクト
            header('Location: index.php');
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $error_message = 'エラーが発生しました: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>新規投稿 - 院内ポータル</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <aside class="sidebar-left">
            <h2>メニュー</h2>
            <nav>
                <ul>
                    <li><a href="index.php">トップページ</a></li>
                    <li><a href="post.php">新規投稿</a></li>
                    <li><a href="admin.php">管理画面</a></li>
                    <li><a href="logout.php">ログアウト</a></li>
                </ul>
            </nav>
        </aside>

        <main class="main-content">
            <header>
                <h1>新規投稿</h1>
            </header>

            <?php if (!empty($error_message)): ?>
                <p class="error-message"><?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>

            <form action="post.php" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="title">タイトル <span class="required">*</span></label>
                    <input type="text" id="title" name="title" required>
                </div>
                <div class="form-group">
                    <label for="content">内容 <span class="required">*</span></label>
                    <textarea id="content" name="content" rows="10" required></textarea>
                </div>
                <div class="form-group">
                    <label for="importance">重要度 <span class="required">*</span></label>
                    <select id="importance" name="importance" required>
                        <option value="">選択してください</option>
                        <option value="important">重要 (赤)</option>
                        <option value="notice">周知 (黄)</option>
                        <option value="contact">連絡 (青)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="attachment">添付ファイル</label>
                    <input type="file" id="attachment" name="attachment">
                </div>
                <button type="submit">投稿する</button>
            </form>
        </main>
    </div>
</body>
</html>
