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

// お知らせ一覧を全件取得（新しい順）
$news_list = $pdo->query("SELECT * FROM news ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ニュース一覧 | 福川急行電鉄</title>
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
        
        main { max-width: 1000px; margin: 40px auto; padding: 0 20px; }
        
        .news-box { background-color: #ffffff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .news-box h2 { font-size: 22px; color: #005bac; border-bottom: 2px solid #005bac; padding-bottom: 10px; margin-bottom: 20px; }
        
        /* リスト形式のデザイン */
        .news-list-table { width: 100%; border-collapse: collapse; }
        .news-list-table tr { border-bottom: 1px solid #e0e0e0; transition: background 0.2s; }
        .news-list-table tr:hover { background-color: #f9fbfd; }
        .news-list-table tr:last-child { border-bottom: none; }
        .news-list-table td { padding: 20px 10px; vertical-align: middle; }
        .news-date { color: #666; font-weight: bold; width: 120px; font-size: 15px; }
        
        .news-title-link { display: block; color: #333; text-decoration: none; font-size: 16px; font-weight: bold; }
        .news-title-link:hover { color: #005bac; text-decoration: underline; }
        
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
                <li><a href="index.php">ホーム</a></li>
                <li><a href="fare.php">乗車券・運賃</a></li>
                <li><a href="route_map.php">路線図</a></li>
                <li><a href="news_list.php" class="active">ニュース</a></li>
                <li><a href="recruit.php">採用情報</a></li>
                <li><a href="company.php">企業情報</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <div class="news-box">
            <h2>重要なお知らせ・ニュース一覧</h2>
            
            <table class="news-list-table">
                <tbody>
                    <?php if (empty($news_list)): ?>
                        <tr>
                            <td style="text-align: center; color: #777;">現在、新しいお知らせはありません。</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($news_list as $item): ?>
                            <tr>
                                <td class="news-date"><?php echo str_replace('-', '.', htmlspecialchars($item['created_at'], ENT_QUOTES, 'UTF-8')); ?></td>
                                <td>
                                    <a href="news_detail.php?id=<?php echo $item['id']; ?>" class="news-title-link">
                                        <?php echo htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <footer>
        <p>&copy; 2026 Fukugawa Rapid Railway Co., Ltd. All Rights Reserved.</p>
    </footer>

</body>
</html>