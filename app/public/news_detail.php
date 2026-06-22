<?php
// ==========================================
// データベース接続設定 (LocalのPort: 10011)
// ==========================================
$host     = '127.0.0.1;port=10011'; 
$dbname   = 'local'; 
$username = 'root';
$password = 'root';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die("データベース接続失敗: " . $e->getMessage());
}

// 画面上部のヘッダー用に運行情報を取得
$status_rows = $pdo->query("SELECT * FROM train_status")->fetchAll();
$status = [];
foreach ($status_rows as $row) {
    $status[$row['line_key']] = $row;
}

// URLの「?id=〇〇」から記事IDを取得
$news_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// 指定されたIDの記事をデータベースから取得
$stmt = $pdo->prepare("SELECT * FROM news WHERE id = ?");
$stmt->execute([$news_id]);
$news = $stmt->fetch();

// 記事が存在しない場合はニュース一覧に返す
if (!$news) {
    header("Location: news_list.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($news['title'], ENT_QUOTES, 'UTF-8'); ?> | 福川急行電鉄</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: "Helvetica Neue", Arial, sans-serif; }
        body { background-color: #f5f7fa; color: #333; }
        header { background-color: #ffffff; border-bottom: 3px solid #005bac; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .header-top { max-width: 1200px; margin: 0 auto; padding: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
        .logo-text { font-size: 26px; font-weight: bold; color: #005bac; border-left: 5px solid #005bac; padding-left: 12px; text-decoration: none; }
        .header-right { display: flex; align-items: center; gap: 15px; }
        
        .status-container { display: flex; flex-direction: column; gap: 6px; background-color: #f0f4f8; border: 1px solid #d0daf0; padding: 10px 16px; border-radius: 6px; min-width: 240px; }
        .status-row { display: flex; justify-content: space-between; font-size: 14px; gap: 20px; }
        .status-line-name { font-weight: bold; color: #444; }
        .status-text { font-weight: bold; }
        .status-text.normal { color: #2e7d32; }
        .status-text.delay { color: #e65100; }
        .status-text.stop { color: #d32f2f; }

        nav { background-color: #005bac; }
        nav ul { max-width: 1200px; margin: 0 auto; display: flex; list-style: none; }
        nav ul li { flex: 1; text-align: center; }
        nav ul li a { display: block; padding: 15px 0; color: #ffffff; text-decoration: none; font-weight: bold; border-right: 1px solid rgba(255, 255, 255, 0.2); }
        nav ul li a.active { background-color: #004480; }
        nav ul li:last-child a { border-right: none; }
        
        main { max-width: 800px; margin: 40px auto; padding: 0 20px; }
        
        .detail-box { background-color: #ffffff; padding: 40px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .news-meta { color: #666; font-weight: bold; font-size: 14px; margin-bottom: 10px; }
        .news-title { font-size: 24px; color: #2c3e50; line-height: 1.4; border-bottom: 2px solid #005bac; padding-bottom: 15px; margin-bottom: 25px; }
        .news-content { font-size: 16px; color: #444; line-height: 1.8; white-space: pre-wrap; margin-bottom: 40px; }
        
        .back-link { display: inline-block; background-color: #f0f4f8; color: #005bac; padding: 10px 20px; border-radius: 4px; text-decoration: none; font-weight: bold; font-size: 14px; border: 1px solid #d0daf0; }
        .back-link:hover { background-color: #e2ecf5; }
        
        footer { background-color: #333; color: #fff; text-align: center; padding: 20px 0; margin-top: 60px; font-size: 14px; }
    </style>
</head>
<body>

    <header>
        <div class="header-top">
            <a href="index.php" class="logo-text">福川急行電鉄</a>
            <div class="header-right">
                <div class="status-container">
                    <div class="status-row">
                        <span class="status-line-name">日原線：</span>
                        <span class="status-text <?php echo $status['hibara']['status_val']; ?>"><?php echo $status['hibara']['status_text']; ?></span>
                    </div>
                    <div class="status-row">
                        <span class="status-line-name">臨海線：</span>
                        <span class="status-text <?php echo $status['rinkai']['status_val']; ?>"><?php echo $status['rinkai']['status_text']; ?></span>
                    </div>
                </div>
            </div>
        </div>
        <nav>
            <ul>
                <li><a href="fare.php">乗車券・運賃</a></li>
                <li><a href="route_map.php">時刻表・路線図</a></li>
                <li><a href="news_list.php" class="active">ニュース</a></li>
                <li><a href="#">採用情報</a></li>
                <li><a href="#">企業情報</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <div class="detail-box">
            <div class="news-meta"><?php echo str_replace('-', '.', htmlspecialchars($news['created_at'], ENT_QUOTES, 'UTF-8')); ?></div>
            <h1 class="news-title"><?php echo htmlspecialchars($news['title'], ENT_QUOTES, 'UTF-8'); ?></h1>
            
            <div class="news-content"><?php echo !empty($news['content']) ? htmlspecialchars($news['content'], ENT_QUOTES, 'UTF-8') : '詳細な本文はありません。'; ?></div>
            
            <a href="news_list.php" class="back-link">➔ ニュース一覧に戻る</a>
        </div>
    </main>

    <footer>
        <p>&copy; 2026 Fukugawa Express Railway Co., Ltd. All Rights Reserved.</p>
    </footer>

</body>
</html>