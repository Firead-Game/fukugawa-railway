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

// 画面上部のヘッダー用に運行情報を取得 👈 追加
$status_rows = $pdo->query("SELECT * FROM train_status")->fetchAll();
$status = [];
foreach ($status_rows as $row) {
    $status[$row['line_key']] = $row;
}

$hibara_stations = $pdo->query("SELECT * FROM stations WHERE line_key = 'hibara' ORDER BY station_index ASC")->fetchAll();
$rinkai_stations = $pdo->query("SELECT * FROM stations WHERE line_key = 'rinkai' ORDER BY station_index ASC")->fetchAll();

$fare_adult = null;
$fare_child = null;
$start_station = null;
$end_station = null;
$selected_line = '';

if (isset($_POST['start_id']) && isset($_POST['end_id']) && isset($_POST['line_key'])) {
    $start_id = $_POST['start_id'];
    $end_id = $_POST['end_id'];
    $line_key = $_POST['line_key'];
    $selected_line = $line_key;
    
    $stmt = $pdo->prepare("SELECT * FROM stations WHERE id = ? OR id = ?");
    $stmt->execute([$start_id, $end_id]);
    $selected_stations = $stmt->fetchAll();
    
    $start_st = null;
    $end_st = null;
    foreach ($selected_stations as $st) {
        if ($st['id'] == $start_id) $start_st = $st;
        if ($st['id'] == $end_id) $end_st = $st;
    }
    
    if ($start_id == $end_id && count($selected_stations) > 0) {
        $start_st = $selected_stations[0];
        $end_st = $selected_stations[0];
    }

    if ($start_st && $end_st) {
        $start_station = $start_st;
        $end_station = $end_st;
        
        $station_diff = abs($start_st['station_index'] - $end_st['station_index']);
        
        if ($station_diff === 0) {
            $fare_adult = 0;
            $fare_child = 0;
        } else {
            $per_station_price = ($line_key === 'hibara') ? 130 : 120;
            $fare_adult = $station_diff * $per_station_price;
            $raw_child_fare = $fare_adult / 2;
            $fare_child = floor($raw_child_fare / 10) * 10;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>乗車券・運賃のご案内 | 福川急行電鉄</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: "Helvetica Neue", Arial, sans-serif; }
        body { background-color: #f5f7fa; color: #333; }
        header { background-color: #ffffff; border-bottom: 3px solid #005bac; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .header-top { max-width: 1200px; margin: 0 auto; padding: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
        .logo-text { font-size: 26px; font-weight: bold; color: #005bac; border-left: 5px solid #005bac; padding-left: 12px; text-decoration: none; }
        .header-right { display: flex; align-items: center; gap: 15px; }
        
        /* 運行情報用スタイル 👈 追加 */
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
        
        .fare-box { background-color: #ffffff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px; }
        .fare-box h2 { font-size: 22px; color: #005bac; border-bottom: 2px solid #005bac; padding-bottom: 10px; margin-bottom: 20px; }
        
        .line-selector { display: flex; gap: 10px; margin-bottom: 20px; }
        .line-tab { flex: 1; padding: 12px; text-align: center; background-color: #e0e0e0; font-weight: bold; border-radius: 4px; cursor: pointer; border: none; font-size: 15px; }
        .line-tab.active-hibara { background-color: #005bac; color: white; }
        .line-tab.active-rinkai { background-color: #0093dd; color: white; }
        
        .form-section { display: none; }
        .form-section.show { display: block; }

        .form-row { display: flex; gap: 20px; margin-bottom: 20px; flex-wrap: wrap; }
        .form-group { flex: 1; min-width: 200px; }
        .form-group label { display: block; font-weight: bold; margin-bottom: 8px; font-size: 14px; color: #555; }
        select { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 4px; font-size: 16px; background-color: #fff; }
        
        .calc-btn { width: 100%; background-color: #ff9800; color: white; border: none; padding: 15px; border-radius: 4px; font-weight: bold; cursor: pointer; font-size: 16px; transition: background 0.2s; }
        .calc-btn:hover { background-color: #e68a00; }
        
        .result-box { margin-top: 30px; padding: 25px; background-color: #e8f5e9; border: 1px solid #c8e6c9; border-radius: 6px; }
        .result-box .route { font-size: 16px; color: #555; margin-bottom: 15px; text-align: center; }
        .result-prices { display: flex; justify-content: space-around; flex-wrap: wrap; gap: 15px; text-align: center; }
        .price-item { background: white; padding: 15px 25px; border-radius: 6px; border: 1px solid #c8e6c9; min-width: 160px; }
        .price-label { font-size: 14px; color: #666; font-weight: bold; margin-bottom: 5px; }
        .price-value { font-size: 28px; font-weight: bold; color: #2e7d32; }

        .info-list { padding-left: 20px; font-size: 14px; color: #555; line-height: 1.8; }
        
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
                <li><a href="fare.php" class="active">乗車券・運賃</a></li>
                <li><a href="route_map.php">路線図</a></li>
                <li><a href="news_list.php">ニュース</a></li>
                <li><a href="recruit.php">採用情報</a></li>
                <li><a href="company.php">企業情報</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <div class="fare-box">
            <h2>普通運賃・経路検索</h2>
            <p style="font-size:14px; color:#666; margin-bottom: 15px;">路線を選択してから、乗車駅と降車駅を選んでください。</p>
            
            <div class="line-selector">
                <button type="button" id="tab_hibara" class="line-tab <?php echo ($selected_line !== 'rinkai') ? 'active-hibara' : ''; ?>" onclick="switchLine('hibara')">日原線 (FN)</button>
                <button type="button" id="tab_rinkai" class="line-tab <?php echo ($selected_line === 'rinkai') ? 'active-rinkai' : ''; ?>" onclick="switchLine('rinkai')">臨海線 (FR)</button>
            </div>

            <form action="fare.php" method="POST" id="form_hibara" class="form-section <?php echo ($selected_line !== 'rinkai') ? 'show' : ''; ?>">
                <input type="hidden" name="line_key" value="hibara">
                <div class="form-row">
                    <div class="form-group">
                        <label>発駅（のる駅）</label>
                        <select name="start_id">
                            <?php foreach ($hibara_stations as $st): ?>
                                <option value="<?php echo $st['id']; ?>" <?php if(isset($_POST['start_id']) && $_POST['start_id'] == $st['id'] && $selected_line === 'hibara') echo 'selected'; ?>>
                                    <?php echo $st['station_number'] . ' ' . $st['station_name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>着駅（降りる駅）</label>
                        <select name="end_id">
                            <?php foreach ($hibara_stations as $st): ?>
                                <option value="<?php echo $st['id']; ?>" <?php if(isset($_POST['end_id']) && $_POST['end_id'] == $st['id'] && $selected_line === 'hibara') echo 'selected'; ?>>
                                    <?php echo $st['station_number'] . ' ' . $st['station_name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <button type="submit" class="calc-btn">日原線の運賃を計算する</button>
            </form>

            <form action="fare.php" method="POST" id="form_rinkai" class="form-section <?php echo ($selected_line === 'rinkai') ? 'show' : ''; ?>">
                <input type="hidden" name="line_key" value="rinkai">
                <div class="form-row">
                    <div class="form-group">
                        <label>発駅（のる駅）</label>
                        <select name="start_id">
                            <?php foreach ($rinkai_stations as $st): ?>
                                <option value="<?php echo $st['id']; ?>" <?php if(isset($_POST['start_id']) && $_POST['start_id'] == $st['id'] && $selected_line === 'rinkai') echo 'selected'; ?>>
                                    <?php echo $st['station_number'] . ' ' . $st['station_name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>着駅（降りる駅）</label>
                        <select name="end_id">
                            <?php foreach ($rinkai_stations as $st): ?>
                                <option value="<?php echo $st['id']; ?>" <?php if(isset($_POST['end_id']) && $_POST['end_id'] == $st['id'] && $selected_line === 'rinkai') echo 'selected'; ?>>
                                    <?php echo $st['station_number'] . ' ' . $st['station_name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <button type="submit" class="calc-btn" style="background-color: #0093dd;">臨海線の運賃を計算する</button>
            </form>
            
            <?php if ($fare_adult !== null): ?>
                <div class="result-box">
                    <div class="route">
                        [<?php echo ($selected_line === 'hibara') ? '日原線' : '臨海線'; ?>] <br>
                        <strong><?php echo $start_station['station_number'] . ' ' . htmlspecialchars($start_station['station_name']); ?></strong> 
                        ➔ 
                        <strong><?php echo $end_station['station_number'] . ' ' . htmlspecialchars($end_station['station_name']); ?></strong>
                    </div>
                    <div class="result-prices">
                        <div class="price-item">
                            <div class="price-label">大人運賃</div>
                            <div class="price-value"><?php echo number_format($fare_adult); ?>円</div>
                        </div>
                        <div class="price-item">
                            <div class="price-label">小児運賃</div>
                            <div class="price-value"><?php echo number_format($fare_child); ?>円</div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="fare-box">
            <h2>運賃のご案内</h2>
            <ul class="info-list" style="margin-top: 10px;">
                <li><strong>日原線（FN）：</strong> 1駅ごとの乗車区間に応じて <strong>130円</strong> ずつ加算されます。</li>
                <li><strong>臨海線（FR）：</strong> 1駅ごとの乗車区間に応じて <strong>120円</strong> ずつ加算されます。</li>
                <li><strong>小児（子供）料金：</strong> 大人の半額です。ただし、10円未満の端数は切り捨てとなります。</li>
                <li>※ 異なる路線（日原線と臨海線）を跨いでの移動（乗り継ぎ運賃）には現在対応しておりません。各路線内での検索をお願いいたします。</li>
            </ul>
        </div>
    </main>

    <footer>
        <p>&copy; 2026 Fukugawa Rapid Railway Co., Ltd. All Rights Reserved.</p>
    </footer>

    <script>
        function switchLine(line) {
            const tabHibara = document.getElementById('tab_hibara');
            const tabRinkai = document.getElementById('tab_rinkai');
            const formHibara = document.getElementById('form_hibara');
            const formRinkai = document.getElementById('form_rinkai');

            if (line === 'hibara') {
                tabHibara.classList.add('active-hibara');
                tabRinkai.classList.remove('active-rinkai');
                formHibara.classList.add('show');
                formRinkai.classList.remove('show');
            } else {
                tabHibara.classList.remove('active-hibara');
                tabRinkai.classList.add('active-rinkai');
                formHibara.classList.remove('show');
                formRinkai.classList.add('show');
            }
        }
    </script>
</body>
</html>