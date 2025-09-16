<?php
session_start();
require_once 'includes/db_connect.php';
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
            <header>
                <h1>院内ポータルサイト</h1>
            </header>

            <?php if(isset($_GET['success'])): ?>
                <p style="color: green; border: 1px solid green; padding: 10px;"><?php echo htmlspecialchars($_GET['success']); ?></p>
            <?php endif; ?>

            <section id="board">
                <h2>周知掲示板</h2>
                <?php
                $sql = "
                    SELECT
                        p.id, p.title, p.content, p.importance, p.created_at,
                        GROUP_CONCAT(a.file_name SEPARATOR '||') as attachment_names,
                        GROUP_CONCAT(a.file_path SEPARATOR '||') as attachment_paths
                    FROM posts p
                    LEFT JOIN attachments a ON p.id = a.post_id
                    WHERE p.is_visible = 1
                      AND (p.start_date IS NULL OR p.start_date <= CURDATE())
                      AND (p.end_date IS NULL OR p.end_date >= CURDATE())
                    GROUP BY p.id
                    ORDER BY p.created_at DESC
                ";
                $result = $conn->query($sql);
                $posts = [];
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $posts[] = $row;
                    }
                }

                if (!empty($posts)) {
                    foreach ($posts as $index => $post) {
                        $visibility_class = $index >= 10 ? 'hidden-post' : '';
                        $importance_class = 'importance-' . htmlspecialchars($post['importance']);

                        echo "<div class='post-item " . $importance_class . " " . $visibility_class . "'>";
                        echo "<h3>" . htmlspecialchars($post['title']) . "</h3>";
                        echo "<div>" . nl2br(htmlspecialchars($post['content'])) . "</div>";

                        if ($post['attachment_names']) {
                            echo "<div class='attachments'><strong>添付ファイル:</strong> ";
                            $names = explode('||', $post['attachment_names']);
                            $paths = explode('||', $post['attachment_paths']);
                            foreach ($names as $i => $name) {
                                // Ideally, this should be a download script like download.php?path=...
                                echo "<a href='" . htmlspecialchars($paths[$i]) . "' target='_blank'>📎 " . htmlspecialchars($name) . "</a> ";
                            }
                            echo "</div>";
                        }

                        echo "<small>投稿日: " . date('Y-m-d', strtotime($post['created_at'])) . "</small>";
                        echo "</div>";
                    }

                    if (count($posts) > 10) {
                        echo '<button id="show-more-btn" class="button">もっと見る</button>';
                    }
                } else {
                    echo "<p>表示する投稿はありません。</p>";
                }
                ?>
            </section>
        </main>
    </div>

    <style>
        .hidden-post { display: none; }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const showMoreBtn = document.getElementById('show-more-btn');
            if (showMoreBtn) {
                showMoreBtn.addEventListener('click', function() {
                    const hiddenPosts = document.querySelectorAll('.hidden-post');
                    hiddenPosts.forEach(function(post) {
                        post.style.display = 'block';
                    });
                    showMoreBtn.style.display = 'none'; // Hide the button after clicking
                });
            }
        });
    </script>
</body>
</html>
