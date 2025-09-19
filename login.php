<?php
// セッション開始
session_start();

// エラー表示（開発中のみ有効）
ini_set('display_errors', 1);
error_reporting(E_ALL);

// DB接続情報
$servername = "mysql320.phy.lolipop.lan";
$dbUsername = "LAA1557214";
$dbPassword = "0331";
$dbname = "LAA1557214-keiziban";

// DB接続
$conn = new mysqli($servername, $dbUsername, $dbPassword, $dbname);

// 接続チェック
if ($conn->connect_error) {
    $_SESSION['error'] = "データベース接続に失敗しました。";
    header("Location: index.php");
    exit();
}

// フォームからの値を取得
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = isset($_POST['username']) ? preg_replace('/\s/u', '', trim($_POST['username'])) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';

    if (empty($username) || empty($password)) {
        $_SESSION['error'] = "ユーザー名とパスワードを入力してください。";
        header("Location: index.php");
        exit();
    }

    // ユーザー名で検索
    $stmt = $conn->prepare("SELECT id, username, password FROM user WHERE username = ?");
    if (!$stmt) {
        $_SESSION['error'] = "SQLエラー: " . $conn->error;
        header("Location: index.php");
        exit();
    }
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header("Location: chat.php");
            exit();
        } else {
            $_SESSION['error'] = "パスワードが間違っています。";
            header("Location: index.php");
            exit();
        }
    } else {
        $_SESSION['error'] = "ユーザー名が見つかりません。";
        header("Location: index.php");
        exit();
    }

    $stmt->close();
} else {
    // POST以外のアクセスはログイン画面へ
    header("Location: index.php");
    exit();
}

$conn->close();
?>
