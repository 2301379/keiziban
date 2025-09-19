<?php
session_start();
header('Content-Type: application/json');

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
    echo json_encode(['success' => false, 'message' => 'データベース接続エラー']);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    // バリデーション
    if (empty($username) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'ユーザー名とパスワードを入力してください。']);
        exit();
    }
    
    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'message' => 'パスワードは6文字以上必要です。']);
        exit();
    }
    
    if ($password !== $confirm_password) {
        echo json_encode(['success' => false, 'message' => 'パスワードが一致しません。']);
        exit();
    }

    // 既にそのユーザー名が使われていないかチェック
    $stmt = $conn->prepare("SELECT id FROM user WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'そのユーザー名は既に使われています。']);
        exit();
    }

    // パスワードをハッシュ化して保存
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $insertStmt = $conn->prepare("INSERT INTO user (username, password) VALUES (?, ?)");
    $insertStmt->bind_param("ss", $username, $hashedPassword);

    if ($insertStmt->execute()) {
        $_SESSION['success'] = "登録が完了しました。ログインしてください。";
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => '登録エラー: ' . $insertStmt->error]);
    }

    $insertStmt->close();
    $stmt->close();
}

$conn->close();
?> 