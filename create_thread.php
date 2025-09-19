<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'ログインが必要です']);
    exit();
}

// DB接続情報
$servername = "mysql320.phy.lolipop.lan";
$dbUsername = "LAA1557214";
$dbPassword = "0331";
$dbname = "LAA1557214-keiziban";

// DB接続
$conn = new mysqli($servername, $dbUsername, $dbPassword, $dbname);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'データベース接続エラー']);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = trim($_POST['title']);
    // 内容の取得は削除
    $user_id = $_SESSION['user_id'];

    // バリデーション（タイトルのみ）
    if (empty($title)) {
        echo json_encode(['success' => false, 'message' => 'タイトルを入力してください']);
        exit();
    }

    // スレッドを作成（内容カラムは挿入しない）
    $createdAt = date('Y-m-d H:i:s');
    $stmt = $conn->prepare("INSERT INTO threads (user_id, title, created_at) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $title, $createdAt);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'スレッドの作成に失敗しました']);
    }

    $stmt->close();
}

$conn->close();
?> 