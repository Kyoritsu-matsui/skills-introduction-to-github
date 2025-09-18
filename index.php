<?php
session_start();
require_once 'db_connect.php';

// 表示するお知らせを取得
try {
    $today = date('Y-m-d');
    // usersテーブルと結合して投稿者名を取得
    $stmt = $pdo->prepare(
        "SELECT p.*, u.username FROM posts p
         JOIN users u ON p.user_id = u.id
         WHERE p.is_visible = 1
         AND (p.start_date IS NULL OR p.start_date <= :today1)
         AND (p.end_date IS NULL OR p.end_date >= :today2)
         ORDER BY p.created_at DESC"
    );
    $stmt->bindParam(':today1', $today, PDO::PARAM_STR);
    $stmt->bindParam(':today2', $today, PDO::PARAM_STR);
    $stmt->execute();
    $posts = $stmt->fetchAll();

    // 各投稿に添付ファイル情報を追加
    foreach ($posts as $key => $post) {
        $stmt_files = $pdo->prepare("SELECT * FROM attachments WHERE post_id = :post_id");
        $stmt_files->bindParam(':post_id', $post['id'], PDO::PARAM_INT);
        $stmt_files->execute();
        $posts[$key]['attachments'] = $stmt_files->fetchAll();
    }

} catch (PDOException $e) {
    // エラーの場合は空の配列をセットし、エラーメッセージを表示
    $posts = [];
    $db_error = "データベースから情報を取得できませんでした: " . $e->getMessage();
}

// 重要度とCSSクラスのマッピング
$importance_classes = [
    'important' => 'importance-high',   // 重要 (赤)
    'notice' => 'importance-medium', // 周知 (黄)
    'contact' => 'importance-low',    // 連絡 (青)
];

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>院内ポータルサイト</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <aside class="sidebar-left">
            <h2>メニュー</h2>
            <nav>
                <ul>
                    <li><a href="index.php">トップページ</a></li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li><a href="post.php">新規投稿</a></li>
                        <li><a href="admin.php">管理画面</a></li>
                        <li><a href="logout.php">ログアウト</a></li>
                    <?php else: ?>
                        <li><a href="login.php">ログイン</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </aside>

        <main class="main-content">
            <header>
                <h1>周知掲示板</h1>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <p style="text-align: right;">ようこそ、<?php echo htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8'); ?>さん。</p>
                <?php endif; ?>
            </header>

            <section id="posts-section">
                <?php if (isset($db_error)): ?>
                    <p class="error-message"><?php echo $db_error; ?></p>
                <?php elseif (empty($posts)): ?>
                    <p>現在、新しいお知らせはありません。</p>
                <?php else: ?>
                    <?php foreach ($posts as $index => $post): ?>
                        <article class="notification <?php echo $importance_classes[$post['importance']] ?? ''; ?>" <?php if ($index >= 10) echo 'style="display: none;"'; ?>>
                            <div class="notification-header">
                                <span class="notification-title"><?php echo htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8'); ?></span>
                                <span class="notification-date">投稿日: <?php echo date('Y/m/d', strtotime($post['created_at'])); ?></span>
                            </div>
                            <div class="notification-content">
                                <?php echo nl2br(htmlspecialchars($post['content'], ENT_QUOTES, 'UTF-8')); ?>
                            </div>
                            <?php if (!empty($post['attachments'])): ?>
                                <div class="attachments">
                                    <?php foreach ($post['attachments'] as $file): ?>
                                        <a href="uploads/<?php echo htmlspecialchars($file['file_path'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" class="attachment">
                                            <?php echo htmlspecialchars($file['file_name'], ENT_QUOTES, 'UTF-8'); ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>

                    <?php if (count($posts) > 10): ?>
                        <button id="toggle-posts">もっと見る</button>
                    <?php endif; ?>
                <?php endif; ?>
            </section>
        </main>

        <aside class="sidebar-right">
            <h3>リンク</h3>
            <ul>
                <li><a href="#posts-section">周知掲示板へ</a></li>
            </ul>
        </aside>
    </div>

    <?php if (count($posts) > 10): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggleButton = document.getElementById('toggle-posts');
            const posts = document.querySelectorAll('#posts-section .notification');
            let isFolded = true;

            if (toggleButton) {
                toggleButton.addEventListener('click', function() {
                    isFolded = !isFolded;
                    for (let i = 10; i < posts.length; i++) {
                        posts[i].style.display = isFolded ? 'none' : 'block';
                    }
                    toggleButton.textContent = isFolded ? 'もっと見る' : '折りたたむ';
                });
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>
