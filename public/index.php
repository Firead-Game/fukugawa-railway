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

// 画面に表示する運行情報の取得
$status_rows = $pdo->query("SELECT * FROM train_status")->fetchAll();
$status = [];
foreach ($status_rows as $row) {
    $status[$row['line_key']] = $row;
}

// お知らせ一覧の取得
$news_list = $pdo->query("SELECT * FROM news ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>福川急行電鉄 公式サイト</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: "Helvetica Neue", Arial, sans-serif; }
        body { background-color: #f5f7fa; color: #333; }
        header { background-color: #ffffff; border-bottom: 3px solid #005bac; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .header-top { max-width: 1200px; margin: 0 auto; padding: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
        .logo-text { font-size: 26px; font-weight: bold; color: #005bac; border-left: 5px solid #005bac; padding-left: 12px; }
        .header-right { display: flex; align-items: center; gap: 15px; }
        
        .status-container { display: flex; flex-direction: column; gap: 6px; background-color: #f0f4f8; border: 1px solid #d0daf0; padding: 10px 16px; border-radius: 6px; min-width: 240px; }
        .status-row { display: flex; justify-content: space-between; font-size: 14px; gap: 20px; }
        .status-line-name { font-weight: bold; color: #444; }
        .status-text { font-weight: bold; }
        .status-text.normal { color: #2e7d32; }
        .status-text.delay { color: #e65100; }
        .status-text.stop { color: #d32f2f; }

        .emp-login-btn { background-color: #444; color: #fff; text-decoration: none; font-size: 12px; padding: 8px 12px; border-radius: 4px; font-weight: bold; cursor: pointer; border: none; }
        .emp-login-btn:hover { background-color: #222; }

        nav { background-color: #005bac; }
        nav ul { max-width: 1200px; margin: 0 auto; display: flex; list-style: none; }
        nav ul li { flex: 1; text-align: center; }
        nav ul li a { display: block; padding: 15px 0; color: #ffffff; text-decoration: none; font-weight: bold; border-right: 1px solid rgba(255, 255, 255, 0.2); }
        nav ul li:last-child a { border-right: none; }
        
        .hero { height: 250px; background: linear-gradient(135deg, #005bac, #0093dd); color: #ffffff; display: flex; justify-content: center; align-items: center; text-shadow: 1px 1px 5px rgba(0,0,0,0.3); }
        .hero h2 { font-size: 32px; letter-spacing: 2px; }
        main { max-width: 1200px; margin: 40px auto; padding: 0 20px; }
        
        .news { background-color: #ffffff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .news h3 { font-size: 20px; color: #005bac; border-bottom: 2px solid #005bac; padding-bottom: 10px; margin-bottom: 20px; }
        .news ul { list-style: none; }
        .news li { padding: 15px 0; border-bottom: 1px solid #e0e0e0; display: flex; gap: 20px; align-items: center; }
        .news li:last-child { border-bottom: none; }
        .news .date { color: #666; font-weight: bold; min-width: 100px; }
        .news a { color: #333; text-decoration: none; flex-grow: 1; }

        /* ログイン用ポップアップ（モーダルウィンドウ） */
        .modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.6); display: flex; justify-content: center; align-items: center;
            opacity: 0; pointer-events: none; transition: opacity 0.3s ease; z-index: 9999;
        }
        .modal-overlay.active { opacity: 1; pointer-events: auto; }
        
        .modal-box {
            background: white; padding: 30px; border-radius: 8px; width: 380px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2); position: relative;
        }
        .modal-box h4 { margin-bottom: 20px; color: #333; font-size: 18px; border-bottom: 2px solid #005bac; padding-bottom: 8px; }
        .close-btn { position: absolute; top: 15px; right: 15px; font-size: 20px; cursor: pointer; color: #999; border: none; background: none; }
        
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-size: 13px; font-weight: bold; margin-bottom: 5px; color: #555; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; }
        
        .login-submit-btn { width: 100%; background-color: #005bac; color: white; border: none; padding: 12px; border-radius: 4px; font-weight: bold; cursor: pointer; font-size: 14px; margin-top: 10px; }
        .login-submit-btn:hover { background-color: #004480; }

        footer { background-color: #333; color: #fff; text-align: center; padding: 20px 0; margin-top: 60px; font-size: 14px; }
    </style>
</head>
<body>

    <header>
        <div class="header-top">
            <h1 class="logo-text">福川急行電鉄</h1>
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
                <button class="emp-login-btn" onclick="toggleModal(true)">社員用ログイン</button>
            </div>
        </div>
        <nav>
            <ul>
                <li><a href="index.php">ホーム</a></li>
                <li><a href="fare.php">乗車券・運賃</a></li>
                <li><a href="route_map.php">路線図</a></li>
                <li><a href="news_list.php">ニュース</a></li>
                <li><a href="recruit.php">採用情報</a></li>
                <li><a href="company.php">企業情報</a></li>
            </ul>
        </nav>
    </header>

    <section class="hero">
        <h2>安全と安心をのせて、未来へつなぐ。</h2>
    </section>

    <main>
        <section class="news">
            <h3>重要なお知らせ</h3>
            <ul>
                <?php if (empty($news_list)): ?>
                    <li>現在、新しいお知らせはありません。</li>
                <?php else: ?>
                    <?php foreach ($news_list as $item): ?>
                        <li>
                            <span class="date"><?php echo str_replace('-', '.', $item['created_at']); ?></span>
                            <a href="#"><?php echo htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8'); ?></a>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </section>
    </main>

    <div class="modal-overlay" id="loginModal">
        <div class="modal-box">
            <button class="close-btn" onclick="toggleModal(false)">×</button>
            <h4>運行管理システム ログイン</h4>
            
            <form action="admin.php" method="POST">
                <div class="form-group">
                    <label>社員番号</label>
                    <input type="text" name="staff_id" placeholder="例: admin" required>
                </div>
                <div class="form-group">
                    <label>パスワード</label>
                    <input type="password" name="password" placeholder="••••••••" required>
                </div>
                <button type="submit" class="login-submit-btn">認証してログイン</button>
            </form>
        </div>
    </div>

    <footer>
        <p>&copy; 2026 Fukugawa Rapid Railway Co., Ltd. All Rights Reserved.</p>
    </footer>

    <script>
        function toggleModal(show) {
            const modal = document.getElementById('loginModal');
            if (show) {
                modal.classList.add('active');
            } else {
                modal.classList.remove('active');
            }
        }
    </script>
</body>
</html>