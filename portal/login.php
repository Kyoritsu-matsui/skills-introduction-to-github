<?php
session_start();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>ログイン - 院内ポータルサイト</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="login-container" style="width: 300px; margin: 50px auto; padding: 20px; border: 1px solid #ccc; background-color: #fff;">
        <h1>ログイン</h1>
        <?php
        if (isset($_GET['error'])) {
            echo '<p style="color: red;">' . htmlspecialchars($_GET['error']) . '</p>';
        }
        ?>
        <form action="process/login_process.php" method="post">
            <div style="margin-bottom: 15px;">
                <label for="username">ユーザー名:</label>
                <input type="text" id="username" name="username" required style="width: 100%; padding: 8px; box-sizing: border-box;">
            </div>
            <div style="margin-bottom: 15px;">
                <label for="password">パスワード:</label>
                <input type="password" id="password" name="password" required style="width: 100%; padding: 8px; box-sizing: border-box;">
            </div>
            <button type="submit" class="button">ログイン</button>
        </form>
    </div>
</body>
</html>
