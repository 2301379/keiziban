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
    $thread_id = $_POST['thread_id'];
    $content = trim($_POST['comment']);
    $user_id = $_SESSION['user_id'];

    // バリデーション
    if (empty($content)) {
        echo json_encode(['success' => false, 'message' => 'コメントを入力してください']);
        exit();
    }

    // スレッドの存在確認
    $checkStmt = $conn->prepare("SELECT id FROM threads WHERE id = ?");
    $checkStmt->bind_param("i", $thread_id);
    $checkStmt->execute();
    $checkStmt->store_result();

    if ($checkStmt->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'スレッドが見つかりません']);
        exit();
    }
    $checkStmt->close();

    // ユーザーのdisplay_idを確認し、なければ生成・保存
    $userDisplayId = null;
    $stmt_user = $conn->prepare("SELECT display_id FROM user WHERE id = ?");
    $stmt_user->bind_param("i", $user_id);
    $stmt_user->execute();
    $stmt_user->bind_result($userDisplayId);
    $stmt_user->fetch();
    $stmt_user->close();

    if (empty($userDisplayId)) {
        // display_idが設定されていない場合、新しいIDを生成
        $newDisplayId = '';
        do {
            $newDisplayId = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyz"), 0, 6); // 6桁のランダムな英数字
            $checkIdStmt = $conn->prepare("SELECT id FROM user WHERE display_id = ?");
            $checkIdStmt->bind_param("s", $newDisplayId);
            $checkIdStmt->execute();
            $checkIdStmt->store_result();
        } while ($checkIdStmt->num_rows > 0); // 重複があれば再生成
        $checkIdStmt->close();

        // ユーザーテーブルにdisplay_idを保存
        $updateStmt = $conn->prepare("UPDATE user SET display_id = ? WHERE id = ?");
        $updateStmt->bind_param("si", $newDisplayId, $user_id);
        $updateStmt->execute();
        $updateStmt->close();
    }

    // コメントを作成
    $createdAt = date('Y-m-d H:i:s');
    $stmt = $conn->prepare("INSERT INTO comments (thread_id, user_id, content, created_at) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiss", $thread_id, $user_id, $content, $createdAt);

    if ($stmt->execute()) {
        $comment_id = $conn->insert_id;
        
        // 画像がアップロードされた場合の処理
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
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
                echo json_encode(['success' => false, 'message' => '許可されていないファイル形式です']);
                exit();
            }

            // ファイルサイズをチェック（5MB以下）
            if ($file['size'] > 5 * 1024 * 1024) {
                echo json_encode(['success' => false, 'message' => 'ファイルサイズが大きすぎます']);
                exit();
            }

            if (move_uploaded_file($file['tmp_name'], $target_path)) {
                // データベースに画像情報を保存
                $imgStmt = $conn->prepare("INSERT INTO images (comment_id, image_path) VALUES (?, ?)");
                $imgStmt->bind_param("is", $comment_id, $target_path);
                $imgStmt->execute();
                $imgStmt->close();
            }
        }

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'コメントの投稿に失敗しました']);
    }

    $stmt->close();
}

$conn->close();
?> 