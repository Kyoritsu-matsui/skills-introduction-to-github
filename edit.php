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
    $display_start_date = !empty($_POST['display_start_date']) ? $_POST['display_start_date'] : null;
    $display_end_date = !empty($_POST['display_end_date']) ? $_POST['display_end_date'] : null;
    $is_visible = isset($_POST['is_visible']) ? 1 : 0;
    $delete_attachment = isset($_POST['delete_attachment']) ? 1 : 0;

    // バリデーション
    if (empty($title) || empty($content) || empty($importance)) {
        $error_message = 'タイトル、内容、重要度は必須です。';
    } else {
        try {
            // 現在のファイル情報を取得
            $stmt_current = $pdo->prepare("SELECT file_path, file_name FROM notifications WHERE id = :id");
            $stmt_current->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt_current->execute();
            $current_notification = $stmt_current->fetch();
            $current_file_path = $current_notification['file_path'];
            $current_file_name = $current_notification['file_name'];

            // 添付ファイル削除の処理
            if ($delete_attachment && !empty($current_file_path)) {
                if (file_exists($current_file_path)) {
                    unlink($current_file_path);
                }
                $current_file_path = null;
                $current_file_name = null;
            }

            // 新しいファイルのアップロード処理
            if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
                // 既存のファイルを削除
                if (!empty($current_file_path) && file_exists($current_file_path)) {
                    unlink($current_file_path);
                }
                // 新しいファイルをアップロード
                $upload_dir = 'uploads/';
                $original_file_name = basename($_FILES['attachment']['name']);
                $unique_file_name = time() . '_' . $original_file_name;
                $new_file_path = $upload_dir . $unique_file_name;
                if (move_uploaded_file($_FILES['attachment']['tmp_name'], $new_file_path)) {
                    $current_file_path = $new_file_path;
                    $current_file_name = $original_file_name;
                } else {
                    $error_message = 'ファイルのアップロードに失敗しました。';
                }
            }

            // データベースを更新
            if (empty($error_message)) {
                $sql = "UPDATE notifications SET
                            title = :title,
                            content = :content,
                            importance = :importance,
                            display_start_date = :display_start_date,
                            display_end_date = :display_end_date,
                            is_visible = :is_visible,
                            file_path = :file_path,
                            file_name = :file_name
                        WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':title', $title, PDO::PARAM_STR);
                $stmt->bindParam(':content', $content, PDO::PARAM_STR);
                $stmt->bindParam(':importance', $importance, PDO::PARAM_STR);
                $stmt->bindParam(':display_start_date', $display_start_date);
                $stmt->bindParam(':display_end_date', $display_end_date);
                $stmt->bindParam(':is_visible', $is_visible, PDO::PARAM_INT);
                $stmt->bindParam(':file_path', $current_file_path, PDO::PARAM_STR);
                $stmt->bindParam(':file_name', $current_file_name, PDO::PARAM_STR);
                $stmt->bindParam(':id', $id, PDO::PARAM_INT);

                if ($stmt->execute()) {
                    header('Location: admin.php');
                    exit;
                } else {
                    $error_message = '更新に失敗しました。';
                }
            }
        } catch (PDOException $e) {
            $error_message = "データベースエラー: " . $e->getMessage();
        }
    }
}

// 編集対象のデータを取得
try {
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE id = :id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $notification = $stmt->fetch();

    if (!$notification) {
        header('Location: admin.php');
        exit;
    }
} catch (PDOException $e) {
    die("データベースから情報を取得できませんでした: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>投稿編集 - 院内ポータル</title>
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
                <h1>投稿編集</h1>
            </header>

            <?php if (!empty($error_message)): ?>
                <p class="error-message"><?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>

            <form action="edit.php?id=<?php echo $id; ?>" method="POST" enctype="multipart/form-data">

                <fieldset class="form-section">
                    <legend>基本情報</legend>
                    <div class="form-group">
                        <label for="title">タイトル <span class="required">*</span></label>
                        <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($notification['title'], ENT_QUOTES, 'UTF-8'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="content">内容 <span class="required">*</span></label>
                        <textarea id="content" name="content" rows="10" required><?php echo htmlspecialchars($notification['content'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="importance">重要度 <span class="required">*</span></label>
                        <select id="importance" name="importance" required>
                            <option value="high" <?php if ($notification['importance'] == 'high') echo 'selected'; ?>>重要 (赤)</option>
                            <option value="medium" <?php if ($notification['importance'] == 'medium') echo 'selected'; ?>>周知 (黄)</option>
                            <option value="low" <?php if ($notification['importance'] == 'low') echo 'selected'; ?>>連絡 (青)</option>
                        </select>
                    </div>
                </fieldset>

                <fieldset class="form-section">
                    <legend>表示設定</legend>
                    <div class="form-grid">
                        <div class="form-grid-item">
                            <label for="display_start_date">表示開始日</label>
                            <input type="date" id="display_start_date" name="display_start_date" value="<?php echo htmlspecialchars($notification['display_start_date'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="form-grid-item">
                            <label for="display_end_date">表示終了日</label>
                            <input type="date" id="display_end_date" name="display_end_date" value="<?php echo htmlspecialchars($notification['display_end_date'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="is_visible" value="1" <?php if ($notification['is_visible']) echo 'checked'; ?>>
                            この投稿を表示する
                        </label>
                    </div>
                </fieldset>

                <fieldset class="form-section">
                    <legend>添付ファイル</legend>
                    <div class="form-group">
                        <?php if (!empty($notification['file_path']) && !empty($notification['file_name'])): ?>
                            <div class="attachment-info">
                                <p>現在のファイル:
                                    <a href="<?php echo htmlspecialchars($notification['file_path'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank">
                                        <?php echo htmlspecialchars($notification['file_name'], ENT_QUOTES, 'UTF-8'); ?>
                                    </a>
                                </p>
                                <label class="checkbox-label">
                                    <input type="checkbox" name="delete_attachment" value="1">
                                    添付ファイルを削除する
                                </label>
                            </div>
                        <?php endif; ?>
                        <label for="attachment">新しいファイルをアップロード</label>
                        <input type="file" id="attachment" name="attachment">
                        <p class="form-hint">新しいファイルをアップロードすると、既存の添付ファイルは上書きされます。</p>
                    </div>
                </fieldset>

                <button type="submit">更新する</button>
            </form>
        </main>
    </div>
</body>
</html>
