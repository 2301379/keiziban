<?php
session_start();
header('Content-Type: application/json');

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

// スレッド一覧を取得（コメント数も含む）
$query = "SELECT t.*, u.username, 
          (SELECT COUNT(*) FROM comments WHERE thread_id = t.id) as comment_count,
          DATE_FORMAT(t.created_at, '%Y-%m-%d %H:%i:%s') as formatted_date
          FROM threads t 
          JOIN user u ON t.user_id = u.id 
          ORDER BY t.created_at DESC";

$result = $conn->query($query);
$threads = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $threads[] = [
            'id' => $row['id'],
            'title' => htmlspecialchars($row['title']),
            'username' => htmlspecialchars($row['username']),
            'created_at' => $row['formatted_date'],
            'comment_count' => $row['comment_count']
        ];
    }
}

echo json_encode($threads);
$conn->close();
?> 