<?php
session_start();
require_once 'db_connect.php';

// ログインチェック
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// お知らせをすべて取得（ユーザー情報も結合）
try {
    $stmt = $pdo->query(
        "SELECT p.*, u.username
         FROM posts p
         JOIN users u ON p.user_id = u.id
         ORDER BY p.created_at DESC"
    );
    $posts = $stmt->fetchAll();
} catch (PDOException $e) {
    $posts = [];
    $db_error = "データベースから情報を取得できませんでした: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>管理画面 - 院内ポータル</title>
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
                <h1>投稿管理</h1>
            </header>

            <?php if (isset($db_error)): ?>
                <p class="error-message"><?php echo $db_error; ?></p>
            <?php endif; ?>

            <table class="admin-table">
                <thead>
                    <tr>
                        <th>タイトル</th>
                        <th>投稿者</th>
                        <th>重要度</th>
                        <th>表示期間</th>
                        <th>表示状態</th>
                        <th>作成日</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($posts)): ?>
                        <tr>
                            <td colspan="7" style="text-align:center;">投稿はありません。</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($posts as $post): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($post['username'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>
                                    <?php
                                        if ($post['importance'] == 'important') echo '重要';
                                        elseif ($post['importance'] == 'notice') echo '周知';
                                        else echo '連絡';
                                    ?>
                                </td>
                                <td>
                                    <?php
                                        $start = $post['start_date'] ? date('Y/m/d', strtotime($post['start_date'])) : '指定なし';
                                        $end = $post['end_date'] ? date('Y/m/d', strtotime($post['end_date'])) : '指定なし';
                                        echo $start . ' ～ ' . $end;
                                    ?>
                                </td>
                                <td><?php echo $post['is_visible'] ? '表示' : '非表示'; ?></td>
                                <td><?php echo date('Y/m/d H:i', strtotime($post['created_at'])); ?></td>
                                <td class="actions">
                                    <a href="edit.php?id=<?php echo $post['id']; ?>" class="edit-btn">編集</a>
                                    <form action="delete.php" method="POST" style="display:inline;" onsubmit="return confirm('本当に削除しますか？');">
                                        <input type="hidden" name="id" value="<?php echo $post['id']; ?>">
                                        <button type="submit" class="delete-btn">削除</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </main>
    </div>
</body>
</html>
