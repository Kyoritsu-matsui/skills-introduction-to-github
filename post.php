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
    $file_path = null;
    $file_name_to_store = null;

    // バリデーション
    if (empty($title) || empty($content) || empty($importance)) {
        $error_message = 'すべての必須項目を入力してください。';
    } else {
        // ファイルアップロード処理
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/';
            $original_file_name = basename($_FILES['attachment']['name']);
            // ファイル名を一意にするためにタイムスタンプを先頭に付与
            $unique_file_name = time() . '_' . $original_file_name;
            $file_path = $upload_dir . $unique_file_name;

            if (move_uploaded_file($_FILES['attachment']['tmp_name'], $file_path)) {
                $file_name_to_store = $original_file_name;
            } else {
                $error_message = 'ファイルのアップロードに失敗しました。';
                $file_path = null;
            }
        }

        // エラーがなければデータベースに挿入
        if (empty($error_message)) {
            try {
                $stmt = $pdo->prepare(
                    "INSERT INTO notifications (title, content, importance, file_path, file_name)
                     VALUES (:title, :content, :importance, :file_path, :file_name)"
                );
                $stmt->bindParam(':title', $title, PDO::PARAM_STR);
                $stmt->bindParam(':content', $content, PDO::PARAM_STR);
                $stmt->bindParam(':importance', $importance, PDO::PARAM_STR);
                $stmt->bindParam(':file_path', $file_path, PDO::PARAM_STR);
                $stmt->bindParam(':file_name', $file_name_to_store, PDO::PARAM_STR);

                if ($stmt->execute()) {
                    // 成功したらトップページへリダイレクト
                    header('Location: index.php');
                    exit;
                } else {
                    $error_message = '投稿の保存に失敗しました。';
                }
            } catch (PDOException $e) {
                $error_message = 'データベースエラー: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>新規投稿 - 院内ポータル</title>
    <link rel="stylesheet" href="style.css">
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
                        <option value="high">重要 (赤)</option>
                        <option value="medium">周知 (黄)</option>
                        <option value="low">連絡 (青)</option>
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
