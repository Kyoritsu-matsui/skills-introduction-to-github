<?php
session_start();
require_once 'db_connect.php';

// ログインチェック
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// IDの存在をチェック
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: admin.php');
    exit;
}
$id = $_GET['id'];

$error_message = '';

// フォームが送信された場合の更新処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // フォームデータの取得
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';
    $importance = $_POST['importance'] ?? '';
    $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
    $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
    $is_visible = isset($_POST['is_visible']) ? 1 : 0;
    $delete_attachments = $_POST['delete_attachments'] ?? [];

    // バリデーション
    if (empty($title) || empty($content) || empty($importance)) {
        $error_message = 'タイトル、内容、重要度は必須です。';
    } else {
        $pdo->beginTransaction();
        try {
            // 1. 投稿内容の更新
            $sql_update_post = "UPDATE posts SET
                                title = :title,
                                content = :content,
                                importance = :importance,
                                start_date = :start_date,
                                end_date = :end_date,
                                is_visible = :is_visible
                            WHERE id = :id";
            $stmt_update_post = $pdo->prepare($sql_update_post);
            $stmt_update_post->bindParam(':title', $title, PDO::PARAM_STR);
            $stmt_update_post->bindParam(':content', $content, PDO::PARAM_STR);
            $stmt_update_post->bindParam(':importance', $importance, PDO::PARAM_STR);
            $stmt_update_post->bindParam(':start_date', $start_date);
            $stmt_update_post->bindParam(':end_date', $end_date);
            $stmt_update_post->bindParam(':is_visible', $is_visible, PDO::PARAM_INT);
            $stmt_update_post->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt_update_post->execute();

            // 2. 添付ファイル削除の処理
            if (!empty($delete_attachments)) {
                foreach ($delete_attachments as $attachment_id) {
                    // ファイルパスを取得してサーバーから削除
                    $stmt_get_file = $pdo->prepare("SELECT file_path FROM attachments WHERE id = :id");
                    $stmt_get_file->bindParam(':id', $attachment_id, PDO::PARAM_INT);
                    $stmt_get_file->execute();
                    $file = $stmt_get_file->fetch();
                    if ($file && file_exists('uploads/' . $file['file_path'])) {
                        unlink('uploads/' . $file['file_path']);
                    }
                    // データベースから削除
                    $stmt_delete_file = $pdo->prepare("DELETE FROM attachments WHERE id = :id");
                    $stmt_delete_file->bindParam(':id', $attachment_id, PDO::PARAM_INT);
                    $stmt_delete_file->execute();
                }
            }

            // 3. 新しいファイルのアップロード処理
            if (isset($_FILES['new_attachment']) && $_FILES['new_attachment']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = 'uploads/';
                $original_file_name = basename($_FILES['new_attachment']['name']);
                $unique_file_name = uniqid('', true) . '_' . $original_file_name;
                $file_path_on_server = $upload_dir . $unique_file_name;

                if (move_uploaded_file($_FILES['new_attachment']['tmp_name'], $file_path_on_server)) {
                    $stmt_add_file = $pdo->prepare(
                        "INSERT INTO attachments (post_id, file_name, file_path)
                         VALUES (:post_id, :file_name, :file_path)"
                    );
                    $stmt_add_file->bindParam(':post_id', $id, PDO::PARAM_INT);
                    $stmt_add_file->bindParam(':file_name', $original_file_name, PDO::PARAM_STR);
                    $stmt_add_file->bindParam(':file_path', $unique_file_name, PDO::PARAM_STR);
                    $stmt_add_file->execute();
                } else {
                    throw new Exception('新しいファイルのアップロードに失敗しました。');
                }
            }

            $pdo->commit();
            header('Location: admin.php');
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $error_message = "更新処理中にエラーが発生しました: " . $e->getMessage();
        }
    }
}

// 編集対象の投稿と添付ファイルを取得
try {
    // 投稿データを取得
    $stmt_post = $pdo->prepare("SELECT * FROM posts WHERE id = :id");
    $stmt_post->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt_post->execute();
    $post = $stmt_post->fetch();

    if (!$post) {
        // 投稿が存在しない場合はリダイレクト
        header('Location: admin.php');
        exit;
    }

    // 添付ファイルを取得
    $stmt_files = $pdo->prepare("SELECT * FROM attachments WHERE post_id = :post_id");
    $stmt_files->bindParam(':post_id', $id, PDO::PARAM_INT);
    $stmt_files->execute();
    $attachments = $stmt_files->fetchAll();

} catch (PDOException $e) {
    die("データベースから情報を取得できませんでした: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>投稿編集 - 院内ポータル</title>
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
                <h1>投稿編集</h1>
            </header>

            <?php if (!empty($error_message)): ?>
                <p class="error-message"><?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>

            <form action="edit.php?id=<?php echo $id; ?>" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="title">タイトル <span class="required">*</span></label>
                    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>
                <div class="form-group">
                    <label for="content">内容 <span class="required">*</span></label>
                    <textarea id="content" name="content" rows="10" required><?php echo htmlspecialchars($post['content'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="importance">重要度 <span class="required">*</span></label>
                    <select id="importance" name="importance" required>
                        <option value="important" <?php if ($post['importance'] == 'important') echo 'selected'; ?>>重要 (赤)</option>
                        <option value="notice" <?php if ($post['importance'] == 'notice') echo 'selected'; ?>>周知 (黄)</option>
                        <option value="contact" <?php if ($post['importance'] == 'contact') echo 'selected'; ?>>連絡 (青)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="start_date">表示開始日</label>
                    <input type="date" id="start_date" name="start_date" value="<?php echo htmlspecialchars($post['start_date'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="form-group">
                    <label for="end_date">表示終了日</label>
                    <input type="date" id="end_date" name="end_date" value="<?php echo htmlspecialchars($post['end_date'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_visible" value="1" <?php if ($post['is_visible']) echo 'checked'; ?>>
                        この投稿を表示する
                    </label>
                </div>

                <div class="form-group">
                    <label>現在の添付ファイル</label>
                    <?php if (empty($attachments)): ?>
                        <p>添付ファイルはありません。</p>
                    <?php else: ?>
                        <?php foreach ($attachments as $file): ?>
                            <div>
                                <input type="checkbox" name="delete_attachments[]" value="<?php echo $file['id']; ?>" id="delete_file_<?php echo $file['id']; ?>">
                                <label for="delete_file_<?php echo $file['id']; ?>">
                                    <a href="uploads/<?php echo htmlspecialchars($file['file_path'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank">
                                        <?php echo htmlspecialchars($file['file_name'], ENT_QUOTES, 'UTF-8'); ?>
                                    </a>
                                    (削除する)
                                </label>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="new_attachment">新しい添付ファイルを追加</label>
                    <input type="file" id="new_attachment" name="new_attachment">
                </div>

                <button type="submit">更新する</button>
            </form>
        </main>
    </div>
</body>
</html>
