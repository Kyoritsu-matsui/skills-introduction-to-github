<?php
session_start();
require_once 'includes/db_connect.php';

// ログインチェックと管理者ロールチェック
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php?error=アクセス権限がありません。');
    exit();
}

// 全ての投稿を取得 (ユーザー名も結合)
$sql = "
    SELECT p.*, u.username
    FROM posts p
    JOIN users u ON p.user_id = u.id
    ORDER BY p.created_at DESC
";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>管理画面 - 院内ポータルサイト</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <div class="container">
        <aside id="sidebar">
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
            <h1>投稿管理</h1>

            <?php if(isset($_GET['success'])): ?>
                <p style="color: green; border: 1px solid green; padding: 10px;"><?php echo htmlspecialchars($_GET['success']); ?></p>
            <?php endif; ?>
            <?php if(isset($_GET['error'])): ?>
                <p style="color: red; border: 1px solid red; padding: 10px;"><?php echo htmlspecialchars($_GET['error']); ?></p>
            <?php endif; ?>

            <table>
                <thead>
                    <tr>
                        <th>タイトル</th>
                        <th>投稿者</th>
                        <th>重要度</th>
                        <th>表示状態</th>
                        <th>表示期間</th>
                        <th>作成日</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while($post = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($post['title']); ?></td>
                                <td><?php echo htmlspecialchars($post['username']); ?></td>
                                <td><?php echo htmlspecialchars($post['importance']); ?></td>
                                <td><?php echo $post['is_visible'] ? '表示' : '非表示'; ?></td>
                                <td>
                                    <?php echo htmlspecialchars($post['start_date'] ?? '未設定'); ?> ~
                                    <?php echo htmlspecialchars($post['end_date'] ?? '未設定'); ?>
                                </td>
                                <td><?php echo date('Y-m-d', strtotime($post['created_at'])); ?></td>
                                <td>
                                    <a href="edit_post.php?id=<?php echo $post['id']; ?>" class="button">編集</a>
                                    <a href="delete_post.php?id=<?php echo $post['id']; ?>" class="button" onclick="return confirm('本当に削除しますか？');">削除</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7">投稿はありません。</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </main>
    </div>
</body>
</html>
