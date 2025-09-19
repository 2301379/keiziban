<?php
session_start();

// エラー表示（開発中のみ有効）
ini_set('display_errors', 1);
error_reporting(E_ALL);

// ログインチェック
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
$loggedInUsername = $_SESSION['username'];

// DB接続情報（必要に応じて変更）
$servername = "mysql320.phy.lolipop.lan";
$dbUsername = "LAA1557214";
$dbPassword = "0331";
$dbname = "LAA1557214-keiziban";

// DB接続
$conn = new mysqli($servername, $dbUsername, $dbPassword, $dbname);

// 接続チェック
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// スレッド削除処理
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_thread_id'])) {
    $deleteThreadId = $_POST['delete_thread_id'];
    $userId = $_SESSION['user_id'];

    // スレッドの作成者か確認
    $stmt = $conn->prepare("SELECT user_id FROM threads WHERE id = ?");
    $stmt->bind_param("i", $deleteThreadId);
    $stmt->execute();
    $stmt->bind_result($threadOwnerId);
    $stmt->fetch();
    $stmt->close();

    if ($threadOwnerId == $userId) {
        // まずコメントを削除
        $stmt = $conn->prepare("DELETE FROM comments WHERE thread_id = ?");
        $stmt->bind_param("i", $deleteThreadId);
        $stmt->execute();
        $stmt->close();

        // 次にスレッドを削除
        $stmt = $conn->prepare("DELETE FROM threads WHERE id = ?");
        $stmt->bind_param("i", $deleteThreadId);
        $stmt->execute();
        $stmt->close();
    }
    // 削除後リダイレクト
    header("Location: chat.php");
    exit();
}

// スレッド作成処理
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['thread_title'])) {
    $threadTitle = $_POST['thread_title'];
    $userId = $_SESSION['user_id'];
    $createdAt = date('Y-m-d H:i:s');

    $stmt = $conn->prepare("INSERT INTO threads (title, user_id, created_at) VALUES (?, ?, ?)");
    $stmt->bind_param("sis", $threadTitle, $userId, $createdAt);

    if ($stmt->execute()) {
        $threadId = $conn->insert_id;
        header("location: thread.php?id=" . $threadId);
        exit;
    } else {
        echo "エラー: " . $stmt->error;
    }
    $stmt->close();
}

// スレッド一覧取得
$threads = [];
$sql = "SELECT t.id, t.title, t.user_id, t.created_at, u.username, 
        (SELECT COUNT(*) FROM comments WHERE thread_id = t.id) as reply_count 
        FROM threads t 
        JOIN user u ON t.user_id = u.id 
        ORDER BY t.created_at DESC 
        LIMIT 20";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $threads[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, maximum-scale=1.0">
    <title>掲示板</title>
    <style>
        body {
            background-color: #1A1A2E;
            color: #E0E0E0;
            font-family: Arial, sans-serif;
            margin: 20px;
            font-size: 16px;
        }
        .thread {
            border: 1px solid #404060;
            padding: 15px;
            margin-bottom: 10px;
            background-color: #282846;
            border-radius: 8px;
        }
        .thread-meta {
            font-size: 0.9em;
            color: #A0A0C0;
            margin-bottom: 5px;
        }
        .thread-title {
            font-size: 1.2em;
            color: #FFD700;
            text-decoration: none;
            word-break: break-all;
        }
        .thread-title:hover {
            text-decoration: underline;
        }
        .new-thread-form {
            margin-bottom: 30px;
            padding: 20px;
            background-color: #282846;
            border: 1px solid #404060;
            border-radius: 8px;
        }
        input[type="text"] {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            background-color: #3A3A5E;
            color: #E0E0E0;
            border: 1px solid #6A6A8E;
            border-radius: 6px;
            font-size: 1em;
            box-sizing: border-box;
        }
        input[type="submit"] {
            padding: 8px 16px;
            background-color: #008B8B;
            color: #FFFFFF;
            border: none;
            cursor: pointer;
            border-radius: 6px;
            font-size: 1em;
        }
        input[type="submit"]:hover {
            background-color: #00CED1;
        }
        a {
            color: #FFD700;
            word-break: break-all;
        }
        .reply-count {
            color: #A0A0C0;
            font-size: 0.9em;
        }
        @media (max-width: 600px) {
            body {
                margin: 4vw;
                font-size: 15px;
            }
            .thread, .new-thread-form {
                padding: 4vw;
                margin-bottom: 3vw;
                border-radius: 6vw;
            }
            .thread-title {
                font-size: 1.1em;
            }
            h1, h2, h3 {
                font-size: 1.2em;
            }
            input[type="text"], input[type="submit"] {
                font-size: 0.9em;
                border-radius: 6vw;
                padding: 6px 12px;
            }
            .reply-count, .thread-meta {
                font-size: 0.95em;
            }
            form[style*="display:inline"] input[type="submit"] {
                font-size: 0.85em;
                padding: 4px 8px;
            }
        }
    </style>
</head>
<body>
    <h1>掲示板</h1>
    <p>ようこそ、<?php echo htmlspecialchars($loggedInUsername); ?>さん</p>

    <!-- 新規スレッド作成フォーム -->
    <div class="new-thread-form">
        <h3>新規スレッド作成</h3>
        <form action="chat.php" method="post">
            <input type="text" name="thread_title" placeholder="スレッドタイトル" required>
            <input type="submit" value="スレッドを作成">
        </form>
    </div>

    <!-- スレッド一覧 -->
    <div>
        <h2>スレッド一覧</h2>
        <?php if (!empty($threads)): ?>
            <?php foreach ($threads as $thread): ?>
                <div class="thread">
                    <div class="thread-meta">
                        <?php echo htmlspecialchars($thread['username']); ?> さん (<?php echo $thread['created_at']; ?>)
                    </div>
                    <a href="thread.php?id=<?php echo $thread['id']; ?>" class="thread-title">
                        <?php echo htmlspecialchars($thread['title']); ?>
                    </a>
                    <span class="reply-count">(<?php echo $thread['reply_count']; ?> レス)</span>
                    <?php if ($thread['user_id'] == $_SESSION['user_id']): ?>
                        <form action="chat.php" method="post" style="display:inline;">
                            <input type="hidden" name="delete_thread_id" value="<?php echo $thread['id']; ?>">
                            <input type="submit" value="削除" onclick="return confirm('本当に削除しますか？');">
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>まだスレッドはありません。</p>
        <?php endif; ?>
    </div>

    <br>
    <p><a href="logout.php">ログアウト</a></p>
</body>
</html>
