<?php
session_start();

// ログインチェック
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>新規投稿 - 院内ポータルサイト</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <aside id="sidebar">
            <h2>メニュー</h2>
            <nav>
                <ul>
                    <li><a href="index.php">トップページ</a></li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li><a href="new_post.php">新規投稿</a></li>
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                            <li><a href="admin.php">管理画面</a></li>
                        <?php endif; ?>
                        <li><a href="logout.php">ログアウト</a></li>
                    <?php else: ?>
                        <li><a href="login.php">ログイン</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </aside>

        <main id="main-content">
            <h1>新規投稿</h1>

            <?php if(isset($_GET['error'])): ?>
                <p style="color: red; border: 1px solid red; padding: 10px;"><?php echo htmlspecialchars($_GET['error']); ?></p>
            <?php endif; ?>

            <form action="process/post_process.php" method="post" enctype="multipart/form-data">
                <div style="margin-bottom: 15px;">
                    <label for="title">タイトル:</label>
                    <input type="text" id="title" name="title" required style="width: 100%; padding: 8px; box-sizing: border-box;">
                </div>
                <div style="margin-bottom: 15px;">
                    <label for="content">内容:</label>
                    <textarea id="content" name="content" rows="10" required style="width: 100%; padding: 8px; box-sizing: border-box;"></textarea>
                </div>
                <div style="margin-bottom: 15px;">
                    <label for="importance">重要度:</label>
                    <select id="importance" name="importance" required>
                        <option value="high" style="color: red;">重要</option>
                        <option value="medium" style="color: orange;">周知</option>
                        <option value="low" style="color: blue;">連絡</option>
                    </select>
                </div>
                <div style="margin-bottom: 15px;">
                    <label for="attachment">添付ファイル:</label>
                    <input type="file" id="attachment" name="attachment">
                </div>
                <button type="submit" name="submit_post" class="button">投稿する</button>
            </form>
        </main>
    </div>
</body>
</html>
