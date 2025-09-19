<?php
session_start();
header('Content-Type: application/json');

if (!isset($_POST['comment_id']) || !isset($_FILES['image'])) {
    echo json_encode(['error' => '必要なパラメータが不足しています']);
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

$comment_id = $_POST['comment_id'];
$upload_dir = 'uploads/';

// アップロードディレクトリが存在しない場合は作成
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$file = $_FILES['image'];
$file_name = time() . '_' . basename($file['name']);
$target_path = $upload_dir . $file_name;

// 画像の種類をチェック
$allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
if (!in_array($file['type'], $allowed_types)) {
    echo json_encode(['error' => '許可されていないファイル形式です']);
    exit();
}

// ファイルサイズをチェック（5MB以下）
if ($file['size'] > 5 * 1024 * 1024) {
    echo json_encode(['error' => 'ファイルサイズが大きすぎます']);
    exit();
}

if (move_uploaded_file($file['tmp_name'], $target_path)) {
    // データベースに画像情報を保存
    $stmt = $conn->prepare("INSERT INTO images (comment_id, image_path) VALUES (?, ?)");
    $stmt->bind_param("is", $comment_id, $target_path);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'image_path' => $target_path]);
    } else {
        echo json_encode(['error' => 'データベースへの保存に失敗しました']);
    }
    $stmt->close();
} else {
    echo json_encode(['error' => 'ファイルのアップロードに失敗しました']);
}

$conn->close();
?> 