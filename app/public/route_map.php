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
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>時刻表・路線図 | 福川急行電鉄</title>
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
        
        .map-box { background-color: #ffffff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .map-box h2 { font-size: 22px; color: #005bac; border-bottom: 2px solid #005bac; padding-bottom: 10px; margin-bottom: 25px; }
        
        /* 路線切り替えタブ */
        .line-selector { display: flex; gap: 10px; margin-bottom: 25px; }
        .line-tab { flex: 1; padding: 12px; text-align: center; background-color: #e0e0e0; font-weight: bold; border-radius: 4px; cursor: pointer; border: none; font-size: 15px; }
        .line-tab.active-hibara { background-color: #005bac; color: white; }
        .line-tab.active-rinkai { background-color: #0093dd; color: white; }
        
        /* 画像エリアの表示・非表示制御 */
        .map-content { display: none; text-align: center; }
        .map-content.show { display: block; }
        
        .route-map-img { max-width: 100%; height: auto; border: 1px solid #ddd; border-radius: 4px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        
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
                <li><a href="route_map.php" class="active">路線図</a></li>
                <li><a href="news_list.php">ニュース</a></li>
                <li><a href="recruit.php">採用情報</a></li>
                <li><a href="company.php">企業情報</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <div class="map-box">
            <h2>福川急行電鉄 路線図</h2>
            
            <div class="line-selector">
                <button type="button" id="tab_hibara" class="line-tab active-hibara" onclick="switchMap('hibara')">日原線 路線図</button>
                <button type="button" id="tab_rinkai" class="line-tab" onclick="switchMap('rinkai')">臨海線 路線図</button>
            </div>

            <div id="map_hibara" class="map-content show">
                <img src="route_map_nichihara.png" alt="日原線 路線図" class="route-map-img">
            </div>

            <div id="map_rinkai" class="map-content">
                <div style="padding: 60px 20px; background-color: #fafafa; border: 1px dashed #ccc; border-radius: 4px;">
                    <p style="font-size: 18px; font-weight: bold; color: #555; margin-bottom: 10px;">臨海線の路線図は現在調整中です</p>
                    <p style="font-size: 14px; color: #777;">データの修正のため、後日公開いたします。恐れ入りますが、今しばらくお待ちください。</p>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <p>&copy; 2026 Fukugawa Rapid Railway Co., Ltd. All Rights Reserved.</p>
    </footer>

    <script>
        function switchMap(line) {
            const tabHibara = document.getElementById('tab_hibara');
            const tabRinkai = document.getElementById('tab_rinkai');
            const mapHibara = document.getElementById('map_hibara');
            const mapRinkai = document.getElementById('map_rinkai');

            if (line === 'hibara') {
                tabHibara.classList.add('active-hibara');
                tabRinkai.classList.remove('active-rinkai');
                mapHibara.classList.add('show');
                mapRinkai.classList.remove('show');
            } else {
                tabHibara.classList.remove('active-hibara');
                tabRinkai.classList.add('active-rinkai');
                mapHibara.classList.remove('show');
                mapRinkai.classList.add('show');
            }
        }
    </script>
</body>
</html>