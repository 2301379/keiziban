<?php
session_start();
header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'スレッドIDが指定されていません']);
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
    echo json_encode(['error' => 'データベース接続エラー']);
    exit();
}

$thread_id = $_GET['id'];

// スレッド情報を取得
$stmt = $conn->prepare("SELECT t.*, u.username, DATE_FORMAT(t.created_at, '%Y-%m-%d %H:%i:%s') as formatted_date 
                       FROM threads t 
                       JOIN user u ON t.user_id = u.id 
                       WHERE t.id = ?");
$stmt->bind_param("i", $thread_id);
$stmt->execute();
$result = $stmt->get_result();

if ($thread = $result->fetch_assoc()) {
    echo json_encode([
        'id' => $thread['id'],
        'title' => htmlspecialchars($thread['title']),
        'content' => nl2br(htmlspecialchars($thread['content'])),
        'username' => htmlspecialchars($thread['username']),
        'created_at' => $thread['formatted_date']
    ]);
} else {
    echo json_encode(['error' => 'スレッドが見つかりません']);
}

$stmt->close();
$conn->close();
?> 