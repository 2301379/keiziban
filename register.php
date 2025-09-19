<?php
session_start();

// DB接続情報
$servername = "mysql320.phy.lolipop.lan";
$dbUsername = "LAA1557214";
$dbPassword = "0331";
$dbname = "LAA1557214-keiziban";
// DB接続
$conn = new mysqli($servername, $dbUsername, $dbPassword, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// POSTで送信されたときの処理
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    // バリデーション
    if (empty($username) || empty($password)) {
        $_SESSION['error'] = "ユーザー名とパスワードを入力してください。";
    } elseif (strlen($password) < 6) {
        $_SESSION['error'] = "パスワードは6文字以上必要です。";
    } elseif ($password !== $confirm_password) {
        $_SESSION['error'] = "パスワードが一致しません。";
    } else {
        // 既にそのユーザー名が使われていないかチェック
        $stmt = $conn->prepare("SELECT id FROM user WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $_SESSION['error'] = "そのユーザー名は既に使われています。";
        } else {
            // パスワードをハッシュ化して保存
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $insertStmt = $conn->prepare("INSERT INTO user (username, password) VALUES (?, ?)");
            $insertStmt->bind_param("ss", $username, $hashedPassword);

            if ($insertStmt->execute()) {
                $_SESSION['success'] = "登録が完了しました。ログインしてください。";
                header("Location: index.php");
                exit();
            } else {
                $_SESSION['error'] = "登録エラー: " . $insertStmt->error;
            }

            $insertStmt->close();
        }
        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>新規登録</title>
    <link rel="stylesheet" href="./css/login.css">
</head>
<body>
    <div class="wrapper">
        <form id="registerForm" action="register_process.php" method="post">
            <h1>新規登録</h1>
            <?php
            if(isset($_SESSION['error'])) {
                echo '<div class="error-message">' . $_SESSION['error'] . '</div>';
                unset($_SESSION['error']);
            }
            ?>
            <div id="error-message" class="error-message" style="display: none;"></div>
            <label for="username">ユーザー名:</label>
            <div class="input-box">
                <input type="text" id="username" name="username" required>
            </div>
            <label for="password">パスワード:</label>
            <div class="input-box">
                <input type="password" id="password" name="password" required>
            </div>
            <label for="confirm_password">パスワード（確認）:</label>
            <div class="input-box">
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            <button type="submit" class="btn">登録</button>
            <div class="register-link">
                <p>すでにアカウントをお持ちですか？<a href="index.php">ログイン</a></p>
            </div>
        </form>
    </div>

    <script>
    document.getElementById('registerForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (!validateForm()) {
            return;
        }

        const formData = new FormData(this);
        
        fetch('register_process.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = 'index.php';
            } else {
                const errorMessage = document.getElementById('error-message');
                errorMessage.textContent = data.message;
                errorMessage.style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            const errorMessage = document.getElementById('error-message');
            errorMessage.textContent = 'エラーが発生しました。もう一度お試しください。';
            errorMessage.style.display = 'block';
        });
    });

    function validateForm() {
        const username = document.getElementById('username').value;
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        const errorMessage = document.getElementById('error-message');

        if (username.length < 3) {
            errorMessage.textContent = 'ユーザー名は3文字以上必要です。';
            errorMessage.style.display = 'block';
            return false;
        }

        if (password.length < 6) {
            errorMessage.textContent = 'パスワードは6文字以上必要です。';
            errorMessage.style.display = 'block';
            return false;
        }

        if (password !== confirmPassword) {
            errorMessage.textContent = 'パスワードが一致しません。';
            errorMessage.style.display = 'block';
            return false;
        }

        return true;
    }
    </script>
</body>
</html>
