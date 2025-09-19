<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>スレッド一覧</title>
    <link rel="stylesheet" href="./css/threads.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>掲示板</h1>
            <div class="user-info">
                ようこそ、<?php echo htmlspecialchars($_SESSION['username']); ?>さん
                <a href="logout.php" class="logout-btn">ログアウト</a>
            </div>
        </header>

        <div class="new-thread-form">
            <h2>新規スレッド作成</h2>
            <form id="newThreadForm">
                <div class="form-group">
                    <label for="title">タイトル:</label>
                    <input type="text" id="title" name="title" required>
                </div>
                <button type="submit" class="btn">スレッドを作成</button>
            </form>
        </div>

        <div class="threads-list">
            <h2>スレッド一覧</h2>
            <div id="threadsContainer">
                <!-- スレッド一覧がここに動的に追加されます -->
            </div>
        </div>
    </div>

    <script>
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

    // スレッド一覧の取得
    function loadThreads() {
        fetch('get_threads.php')
            .then(response => response.json())
            .then(data => {
                const container = document.getElementById('threadsContainer');
                container.innerHTML = '';
                
                data.forEach(thread => {
                    const threadElement = document.createElement('div');
                    threadElement.className = 'thread-item';
                    threadElement.innerHTML = `
                        <h3><a href="thread.php?id=${thread.id}">${thread.title}</a></h3>
                        <p class="thread-meta">
                            投稿者: ${thread.username} | 
                            投稿日時: ${formatDate(thread.created_at)} | 
                            コメント数: ${thread.comment_count}
                        </p>
                    `;
                    container.appendChild(threadElement);
                });
            })
            .catch(error => console.error('Error:', error));
    }

    // 新規スレッド作成
    document.getElementById('newThreadForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        fetch('create_thread.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.reset();
                loadThreads();
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('エラーが発生しました。もう一度お試しください。');
        });
    });

    // 初期ロード
    loadThreads();
    </script>
</body>
</html> 