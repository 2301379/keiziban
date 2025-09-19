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

// スレッドIDの取得
if (!isset($_GET['id'])) {
    header("Location: threads.php");
    exit();
}
$thread_id = $_GET['id'];

// DB接続情報
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

// コメント投稿処理
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['comment'])) {
    $commentContent = $_POST['comment'];
    $userId = $_SESSION['user_id'];
    $createdAt = date('Y-m-d H:i:s');

    $stmt = $conn->prepare("INSERT INTO comments (thread_id, user_id, content, created_at) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiss", $thread_id, $userId, $commentContent, $createdAt);

    if ($stmt->execute()) {
        header("location: thread.php?id=" . $thread_id);
        exit;
    } else {
        echo "エラー: " . $stmt->error;
    }
    $stmt->close();
}

// スレッド情報取得
$stmt = $conn->prepare("SELECT t.*, u.username FROM threads t JOIN user u ON t.user_id = u.id WHERE t.id = ?");
$stmt->bind_param("i", $thread_id);
$stmt->execute();
$thread = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$thread) {
    header("Location: chat.php");
    exit();
}

// コメント取得（ページネーション対応）
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

$comments = [];
$sql = "SELECT c.*, u.username 
        FROM comments c 
        JOIN user u ON c.user_id = u.id 
        WHERE c.thread_id = ? 
        ORDER BY c.created_at ASC 
        LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $thread_id, $perPage, $offset);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $comments[] = $row;
    }
}

// 総コメント数を取得
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM comments WHERE thread_id = ?");
$stmt->bind_param("i", $thread_id);
$stmt->execute();
$totalComments = $stmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalComments / $perPage);

$conn->close();
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($thread['title']); ?> - 掲示板</title>
    <link rel="stylesheet" href="./css/thread.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>スレッド詳細</h1>
            <div class="user-info">
                ようこそ、<?php echo htmlspecialchars($_SESSION['username']); ?>さん
                <a href="chat.php" class="back-btn">スレッド一覧に戻る</a>
                <a href="logout.php" class="logout-btn">ログアウト</a>
            </div>
        </header>

        <div id="threadContent"> </div>
        <div class="comments-section">
            <h2>コメント</h2>
            <div class="haruchan" id="commentsContainer">
                <!-- コメント一覧がここに動的に追加されます -->
            </div>

            <div class="new-comment-form">
                <h3>コメントを投稿</h3>
                <form id="commentForm" enctype="multipart/form-data">
                    <input type="hidden" name="thread_id" value="<?php echo $thread_id; ?>">
                    <div class="form-group">
                        <label for="comment">コメント:</label>
                        <textarea id="comment" name="comment" required></textarea>
                    </div>
                    <div class="form-actions">
                        <div class="file-input-container">
                            <label for="image" class="file-input-label">
                                <i class="fas fa-image"></i> 画像を選択
                            </label>
                            <input type="file" id="image" name="image" accept="image/*">
                            <div class="file-name"></div>
                        </div>
                        <button type="submit" class="btn">コメントを投稿</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- モーダルウィンドウ -->
    <div id="imageModal" class="modal">
        <span class="modal-close">&times;</span>
        <img class="modal-content" id="modalImage">
    </div>

    <script>
    // スレッドの内容を取得
    function loadThread() {
        fetch(`get_thread.php?id=<?php echo $thread_id; ?>`)
            .then(response => response.json())
            .then(data => {
                const container = document.getElementById('threadContent');
                container.innerHTML = `
                    <div class="thread-detail">
                        <h2>${data.title}</h2>
                        <div class="thread-meta">
                            投稿者: ${data.username} | 投稿日時: ${formatDate(data.created_at)}
                        </div>
                        <div class="thread-body">
                            ${data.content}
                        </div>
                    </div>
                `;
            })
            .catch(error => console.error('Error:', error));
    }

    let lastCommentId = 0; // 最後に表示したコメントのIDを保持

    // コメント一覧を取得
    function loadComments() {
        fetch(`get_comments.php?thread_id=<?php echo $thread_id; ?>`)
            .then(response => response.json())
            .then(data => {
                const container = document.getElementById('commentsContainer');
                
                // 新しいコメントがある場合のみ更新
                if (data.length > 0 && data[data.length - 1].id > lastCommentId) {
                    container.innerHTML = '';
                    
                    data.forEach((comment, index) => {
                        const commentElement = document.createElement('div');
                        commentElement.className = 'comment-item';
                        if (comment.is_thread_owner) {
                            commentElement.classList.add('thread-owner-comment');
                        }
                        commentElement.innerHTML = `
                            <div class="comment-number">${index + 1}</div>
                            <div class="comment-content">
                                <div class="comment-meta">
                                    投稿者: ${comment.username} ${comment.display_id ? `(ID: ${comment.display_id})` : ''} | 投稿日時: ${formatDate(comment.created_at)}
                                </div>
                                <div class="comment-body">${comment.content}</div>
                                ${comment.image_path ? `<img src="${comment.image_path}" alt="コメント画像" class="comment-image" onclick="openModal(this.src)">` : ''}
                            </div>
                        `;
                        container.appendChild(commentElement);
                    });

                    // 最後のコメントIDを更新
                    lastCommentId = data[data.length - 1].id;
                    
                    // 新しいコメントがある場合は自動スクロール
                    container.scrollTop = container.scrollHeight;
                }
            })
            .catch(error => console.error('Error:', error));
    }

    // 日時フォーマット関数
    function formatDate(dateString) {
        const date = new Date(dateString);
        if (isNaN(date.getTime())) {
            return '日時不明';
        }
        return date.toLocaleString('ja-JP', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: false
        });
    }

    // コメント投稿
    document.getElementById('commentForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        fetch('create_comment.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.reset();
                document.querySelector('.file-name').textContent = ''; // ファイル名の表示をクリア
                loadComments(); // コメント投稿後すぐに更新
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('エラーが発生しました。もう一度お試しください。');
        });
    });

    document.getElementById('image').addEventListener('change', function(e) {
        const fileName = e.target.files[0] ? e.target.files[0].name : '';
        document.querySelector('.file-name').textContent = fileName;
    });

    // モーダル関連の処理
    const modal = document.getElementById('imageModal');
    const modalImg = document.getElementById('modalImage');
    const closeBtn = document.getElementsByClassName('modal-close')[0];

    function openModal(imgSrc) {
        modal.style.display = "flex";
        modalImg.src = imgSrc;
    }

    // モーダルを閉じる処理
    closeBtn.onclick = function() {
        modal.style.display = "none";
    }

    // モーダルの外側をクリックしても閉じる
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }

    // 初期ロード
    loadThread();
    loadComments();

    // 2秒ごとにコメントを自動更新
    setInterval(loadComments, 2000);
    </script>
</body>
</html> 