<?php
session_start();
header('Content-Type: application/json');

if (!isset($_GET['thread_id'])) {
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

$thread_id = $_GET['thread_id'];

// コメント一覧を取得
$stmt = $conn->prepare("SELECT c.*, u.username, u.display_id, DATE_FORMAT(c.created_at, '%Y-%m-%d %H:%i:%s') as formatted_date,
                       (SELECT t.user_id FROM threads t WHERE t.id = c.thread_id) as thread_owner_id,
                       i.image_path
                       FROM comments c 
                       JOIN user u ON c.user_id = u.id 
                       LEFT JOIN images i ON c.id = i.comment_id
                       WHERE c.thread_id = ? 
                       ORDER BY c.created_at ASC");
$stmt->bind_param("i", $thread_id);
$stmt->execute();
$result = $stmt->get_result();

$comments = [];
while ($comment = $result->fetch_assoc()) {
    $comments[] = [
        'id' => $comment['id'],
        'content' => nl2br(htmlspecialchars($comment['content'])),
        'username' => htmlspecialchars($comment['username']),
        'display_id' => htmlspecialchars($comment['display_id']),
        'created_at' => $comment['formatted_date'],
        'is_thread_owner' => ($comment['user_id'] == $comment['thread_owner_id']),
        'image_path' => $comment['image_path']
    ];
}

echo json_encode($comments);

$stmt->close();
$conn->close();
?> 