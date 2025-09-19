<?php
session_start();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>掲示板ログイン</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, maximum-scale=1.0">
    <link rel="stylesheet" href="./css/login.css">
</head>
<body>
    <div class="wrapper">
    <form action="login.php" method="post">
    <h1>ログイン</h1>
    <?php
    if(isset($_SESSION['error'])) {
        echo '<div class="error-message">' . $_SESSION['error'] . '</div>';
        unset($_SESSION['error']);
    }
    ?>
    <div id="error-message" class="error-message" style="display: none;"></div>
        <label for="username">ユーザー名:</label>
        <div class="input-box">
        <input type="text" id="username" name="username" required><br><br>
        </div>
        <label for="password">パスワード:</label>
        <div class="input-box">
        <input type="password" id="password" name="password" required><br><br>
        </div>
        <button type="submit" class="btn">ログイン</button>
        <div class="register-link">
            <p>アカウントをお持ちでない方はこちら　<a href="register.php">新規登録</a></p>
        </div>
    </form>
    </div>

    <script>
    function validateForm() {
        const username = document.getElementById('username').value;
        const password = document.getElementById('password').value;
        const errorMessage = document.getElementById('error-message');

        // ここでパスワードの検証を行う
        if (password.length < 6) {
            errorMessage.textContent = 'パスワードは6文字以上必要です。';
            errorMessage.style.display = 'block';
            return false;
        }

        // その他の検証ルールを追加できます
        // 例: 特定の文字を含むかどうかなど

        return true;
    }
    </script>
</body>
</html>
