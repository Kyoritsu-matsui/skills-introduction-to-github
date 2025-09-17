<?php
session_start();
require_once 'db_connect.php';

// 表示するお知らせを取得
try {
    $today = date('Y-m-d');
    $stmt = $pdo->prepare(
        "SELECT * FROM notifications
         WHERE is_visible = 1
         AND (display_start_date IS NULL OR display_start_date <= :today1)
         AND (display_end_date IS NULL OR display_end_date >= :today2)
         ORDER BY created_at DESC"
    );
    $stmt->bindParam(':today1', $today, PDO::PARAM_STR);
    $stmt->bindParam(':today2', $today, PDO::PARAM_STR);
    $stmt->execute();
    $notifications = $stmt->fetchAll();
} catch (PDOException $e) {
    // エラーの場合は空の配列をセットし、エラーメッセージを表示
    $notifications = [];
    $db_error = "データベースから情報を取得できませんでした: " . $e->getMessage();
}

// 重要度とCSSクラスのマッピング
$importance_classes = [
    'high' => 'importance-high',   // 重要 (赤)
    'medium' => 'importance-medium', // 周知 (黄)
    'low' => 'importance-low',    // 連絡 (青)
];

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>院内ポータルサイト</title>
    <link rel="stylesheet" href="style.css">
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

            <section id="notifications-section">
                <?php if (isset($db_error)): ?>
                    <p class="error-message"><?php echo $db_error; ?></p>
                <?php elseif (empty($notifications)): ?>
                    <p>現在、新しいお知らせはありません。</p>
                <?php else: ?>
                    <?php foreach ($notifications as $index => $notification): ?>
                        <article class="notification <?php echo $importance_classes[$notification['importance']] ?? ''; ?>" <?php if ($index >= 10) echo 'style="display: none;"'; ?>>
                            <div class="notification-header">
                                <span class="notification-title"><?php echo htmlspecialchars($notification['title'], ENT_QUOTES, 'UTF-8'); ?></span>
                                <span class="notification-date">投稿日: <?php echo date('Y/m/d', strtotime($notification['created_at'])); ?></span>
                            </div>
                            <div class="notification-content">
                                <?php echo nl2br(htmlspecialchars($notification['content'], ENT_QUOTES, 'UTF-8')); ?>
                            </div>
                            <?php if (!empty($notification['file_path']) && !empty($notification['file_name'])): ?>
                                <div class="attachment">
                                    <a href="<?php echo htmlspecialchars($notification['file_path'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank">
                                        <?php echo htmlspecialchars($notification['file_name'], ENT_QUOTES, 'UTF-8'); ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>

                    <?php if (count($notifications) > 10): ?>
                        <button id="toggle-notifications">もっと見る</button>
                    <?php endif; ?>
                <?php endif; ?>
            </section>
        </main>

        <aside class="sidebar-right">
            <h3>リンク</h3>
            <ul>
                <li><a href="#notifications-section">周知掲示板へ</a></li>
            </ul>
        </aside>
    </div>

    <?php if (count($notifications) > 10): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggleButton = document.getElementById('toggle-notifications');
            const notifications = document.querySelectorAll('#notifications-section .notification');
            let isFolded = true;

            toggleButton.addEventListener('click', function() {
                isFolded = !isFolded;
                for (let i = 10; i < notifications.length; i++) {
                    notifications[i].style.display = isFolded ? 'none' : 'block';
                }
                toggleButton.textContent = isFolded ? 'もっと見る' : '折りたたむ';
            });
        });
    </script>
    <?php endif; ?>
</body>
</html>
