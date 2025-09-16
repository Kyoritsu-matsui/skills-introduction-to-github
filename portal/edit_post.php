<?php
session_start();
require_once 'includes/db_connect.php';

// Admin check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php?error=アクセス権限がありません。');
    exit();
}

// Check for post ID
if (!isset($_GET['id'])) {
    header('Location: admin.php');
    exit();
}
$post_id = $_GET['id'];

// Fetch post data
$stmt = $conn->prepare("SELECT * FROM posts WHERE id = ?");
$stmt->bind_param("i", $post_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    header('Location: admin.php?error=投稿が見つかりません。');
    exit();
}
$post = $result->fetch_assoc();
$stmt->close();

// Fetch attachments
$stmt_att = $conn->prepare("SELECT * FROM attachments WHERE post_id = ?");
$stmt_att->bind_param("i", $post_id);
$stmt_att->execute();
$attachments = $stmt_att->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_att->close();

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>投稿編集 - 院内ポータルサイト</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <aside id="sidebar">
            <!-- Sidebar -->
            <h2>メニュー</h2>
            <nav>
                <ul>
                    <li><a href="index.php">トップページ</a></li>
                    <li><a href="new_post.php">新規投稿</a></li>
                    <li><a href="admin.php">管理画面</a></li>
                    <li><a href="logout.php">ログアウト</a></li>
                </ul>
            </nav>
        </aside>
        <main id="main-content">
            <h1>投稿編集</h1>
            <form action="process/admin_process.php" method="post" enctype="multipart/form-data">
                <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">

                <!-- Title -->
                <div style="margin-bottom: 15px;">
                    <label for="title">タイトル:</label>
                    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($post['title']); ?>" required style="width: 100%;">
                </div>

                <!-- Content -->
                <div style="margin-bottom: 15px;">
                    <label for="content">内容:</label>
                    <textarea id="content" name="content" rows="10" required style="width: 100%;"><?php echo htmlspecialchars($post['content']); ?></textarea>
                </div>

                <!-- Importance -->
                <div style="margin-bottom: 15px;">
                    <label for="importance">重要度:</label>
                    <select id="importance" name="importance">
                        <option value="high" <?php if($post['importance'] == 'high') echo 'selected'; ?>>重要</option>
                        <option value="medium" <?php if($post['importance'] == 'medium') echo 'selected'; ?>>周知</option>
                        <option value="low" <?php if($post['importance'] == 'low') echo 'selected'; ?>>連絡</option>
                    </select>
                </div>

                <!-- Visibility -->
                <div style="margin-bottom: 15px;">
                    <label>表示状態:</label>
                    <input type="radio" id="visible_true" name="is_visible" value="1" <?php if($post['is_visible']) echo 'checked'; ?>>
                    <label for="visible_true">表示</label>
                    <input type="radio" id="visible_false" name="is_visible" value="0" <?php if(!$post['is_visible']) echo 'checked'; ?>>
                    <label for="visible_false">非表示</label>
                </div>

                <!-- Display Period -->
                <div style="margin-bottom: 15px;">
                    <label for="start_date">表示開始日:</label>
                    <input type="date" id="start_date" name="start_date" value="<?php echo htmlspecialchars($post['start_date']); ?>">
                </div>
                <div style="margin-bottom: 15px;">
                    <label for="end_date">表示終了日:</label>
                    <input type="date" id="end_date" name="end_date" value="<?php echo htmlspecialchars($post['end_date']); ?>">
                </div>

                <!-- Attachments Management -->
                <div style="margin-bottom: 15px;">
                    <h3>添付ファイル管理</h3>
                    <?php if (!empty($attachments)): ?>
                        <ul>
                        <?php foreach ($attachments as $att): ?>
                            <li>
                                <?php echo htmlspecialchars($att['file_name']); ?>
                                <button type="submit" name="delete_attachment" value="<?php echo $att['id']; ?>" class="button" style="background-color: #dc3545;" onclick="return confirm('この添付ファイルを削除しますか？');">削除</button>
                            </li>
                        <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p>添付ファイルはありません。</p>
                    <?php endif; ?>
                    <label for="add_attachment">添付ファイルを追加:</label>
                    <input type="file" id="add_attachment" name="add_attachment">
                </div>

                <button type="submit" name="update_post" class="button">更新する</button>
                <a href="admin.php" class="button" style="background-color: #6c757d;">キャンセル</a>
            </form>
        </main>
    </div>
</body>
</html>
