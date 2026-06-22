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

$message = '';
$edit_news = null;

// ------------------------------------------
// 1. 編集対象データの読み込み処理 (GET)
// ------------------------------------------
if (isset($_GET['edit_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM news WHERE id = ?");
    $stmt->execute([$_GET['edit_id']]);
    $edit_news = $stmt->fetch();
}

// ------------------------------------------
// 2. フォームからの送信処理 (POST)
// ------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 運行情報の更新処理
    if (isset($_POST['action']) && $_POST['action'] === 'update_status') {
        $stmt = $pdo->prepare("UPDATE train_status SET status_text = ?, status_val = ? WHERE line_key = ?");
        $stmt->execute([$_POST['hibara_text'], $_POST['hibara_val'], 'hibara']);
        $stmt->execute([$_POST['rinkai_text'], $_POST['rinkai_val'], 'rinkai']);
        $message = "運行情報を更新しました。";
    }
    
    // ニュースの「新規追加」または「編集保存」処理
    if (isset($_POST['action']) && $_POST['action'] === 'save_news') {
        $title   = trim($_POST['title']);
        $content = trim($_POST['content']); // 👈 本文を取得
        $news_id = isset($_POST['news_id']) ? $_POST['news_id'] : '';

        if ($title === '') {
            $message = "エラー：ニュースのタイトルを入力してください。";
        } else {
            if ($news_id !== '') {
                // 既存ニュースの編集 (タイトルと本文を更新)
                $stmt = $pdo->prepare("UPDATE news SET title = ?, content = ? WHERE id = ?");
                $stmt->execute([$title, $content, $news_id]);
                $message = "ニュースを修正しました。";
            } else {
                // 新規ニュースの追加 (タイトルと本文を挿入)
                $stmt = $pdo->prepare("INSERT INTO news (title, content, created_at) VALUES (?, ?, ?)");
                $stmt->execute([$title, $content, date('Y-m-d')]);
                $message = "ニュースを新規投稿しました。";
            }
        }
    }

    // ニュースの「削除」処理
    if (isset($_POST['action']) && $_POST['action'] === 'delete_news') {
        $stmt = $pdo->prepare("DELETE FROM news WHERE id = ?");
        $stmt->execute([$_POST['delete_id']]);
        $message = "ニュースを削除しました。";
    }
}

// 最新の運行情報を取得
$status_rows = $pdo->query("SELECT * FROM train_status")->fetchAll();
$status = [];
foreach ($status_rows as $row) {
    $status[$row['line_key']] = $row;
}

// ニュース一覧を取得（新しい順）
$news_list = $pdo->query("SELECT * FROM news ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>駅務管理システム | 福川急行電鉄</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: "Helvetica Neue", Arial, sans-serif; }
        body { background-color: #f0f2f5; color: #333; padding-bottom: 60px; }
        header { background-color: #2c3e50; color: #fff; padding: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .header-container { max-width: 1000px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; }
        .logo { font-size: 22px; font-weight: bold; letter-spacing: 1px; }
        .site-link { color: #ebd4b4; text-decoration: none; font-size: 14px; font-weight: bold; }
        .site-link:hover { text-decoration: underline; }
        
        main { max-width: 1000px; margin: 30px auto; padding: 0 20px; }
        
        .alert-msg { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; padding: 15px; border-radius: 4px; margin-bottom: 25px; font-weight: bold; text-align: center; }
        
        .admin-box { background-color: #ffffff; padding: 25px; border-radius: 6px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); margin-bottom: 30px; }
        .admin-box h2 { font-size: 18px; color: #2c3e50; border-left: 4px solid #2c3e50; padding-left: 10px; margin-bottom: 20px; }
        
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-weight: bold; margin-bottom: 8px; font-size: 14px; color: #555; }
        .form-row { display: flex; gap: 20px; flex-wrap: wrap; margin-bottom: 15px; }
        .form-col { flex: 1; min-width: 250px; }
        
        input[type="text"], select, textarea { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 15px; }
        textarea { resize: vertical; min-height: 120px; font-family: inherit; }
        
        .btn { background-color: #2c3e50; color: white; border: none; padding: 12px 20px; border-radius: 4px; font-weight: bold; cursor: pointer; font-size: 15px; }
        .btn:hover { opacity: 0.9; }
        .btn-orange { background-color: #e67e22; }
        .btn-red { background-color: #c0392b; padding: 6px 12px; font-size: 13px; border-radius: 4px; font-weight: bold; cursor: pointer; }
        .btn-green { background-color: #27ae60; padding: 6px 12px; font-size: 13px; text-decoration: none; color: white; border-radius: 4px; font-weight: bold; }
        .btn-cancel { background-color: #7f8c8d; text-decoration: none; color: white; padding: 12px 20px; border-radius: 4px; font-weight: bold; font-size: 15px; display: inline-block; }
        
        .admin-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .admin-table th, .admin-table td { padding: 12px; border: 1px solid #ddd; text-align: left; font-size: 14px; vertical-align: top; }
        .admin-table th { background-color: #f8f9fa; color: #555; font-weight: bold; }
        .action-cols { display: flex; gap: 8px; }
        .text-muted { color: #888; font-size: 12px; margin-top: 4px; white-space: pre-wrap; }
    </style>
</head>
<body>

    <header>
        <div class="header-container">
            <div class="logo">福川急行電鉄 社員専用管理ページ</div>
            <a href="index.php" target="_blank" class="site-link">一般公開サイトを確認 ➔</a>
        </div>
    </header>

    <main>
        <?php if ($message !== ''): ?>
            <div class="alert-msg"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <div class="admin-box">
            <h2>運行情報の更新</h2>
            <form action="admin.php" method="POST">
                <input type="hidden" name="action" value="update_status">
                <div class="form-row">
                    <div class="form-col">
                        <label>【日原線】運行状況文</label>
                        <input type="text" name="hibara_text" value="<?php echo htmlspecialchars($status['hibara']['status_text'], ENT_QUOTES, 'UTF-8'); ?>">
                        <label style="margin-top:10px;">色設定</label>
                        <select name="hibara_val">
                            <option value="normal" <?php if($status['hibara']['status_val'] === 'normal') echo 'selected'; ?>>緑（平常運行）</option>
                            <option value="delay" <?php if($status['hibara']['status_val'] === 'delay') echo 'selected'; ?>>オレンジ（遅延）</option>
                            <option value="stop" <?php if($status['hibara']['status_val'] === 'stop') echo 'selected'; ?>>赤（運転見合わせ）</option>
                        </select>
                    </div>
                    <div class="form-col">
                        <label>【臨海線】運行状況文</label>
                        <input type="text" name="rinkai_text" value="<?php echo htmlspecialchars($status['rinkai']['status_text'], ENT_QUOTES, 'UTF-8'); ?>">
                        <label style="margin-top:10px;">色設定</label>
                        <select name="rinkai_val">
                            <option value="normal" <?php if($status['rinkai']['status_val'] === 'normal') echo 'selected'; ?>>緑（平常運行）</option>
                            <option value="delay" <?php if($status['rinkai']['status_val'] === 'delay') echo 'selected'; ?>>オレンジ（遅延）</option>
                            <option value="stop" <?php if($status['rinkai']['status_val'] === 'stop') echo 'selected'; ?>>赤（運転見合わせ）</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn">運行情報を反映する</button>
            </form>
        </div>

        <div class="admin-box">
            <h2><?php echo $edit_news ? 'ニュースの編集' : '重要なお知らせの新規投稿'; ?></h2>
            <form action="admin.php" method="POST">
                <input type="hidden" name="action" value="save_news">
                <?php if ($edit_news): ?>
                    <input type="hidden" name="news_id" value="<?php echo $edit_news['id']; ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label>お知らせのタイトル</label>
                    <input type="text" name="title" placeholder="例：ダイヤ改正のお知らせ" value="<?php echo $edit_news ? htmlspecialchars($edit_news['title'], ENT_QUOTES, 'UTF-8') : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label>お知らせの本文</label>
                    <textarea name="content" placeholder="詳細な案内文をここに入力してください（改行対応）"><?php echo $edit_news && isset($edit_news['content']) ? htmlspecialchars($edit_news['content'], ENT_QUOTES, 'UTF-8') : ''; ?></textarea>
                </div>
                
                <button type="submit" class="btn btn-orange"><?php echo $edit_news ? '修正内容を保存する' : 'この内容で新規投稿する'; ?></button>
                <?php if ($edit_news): ?>
                    <a href="admin.php" class="btn-cancel">編集をキャンセル</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="admin-box">
            <h2>投稿済みお知らせ一覧</h2>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th style="width: 100px;">日付</th>
                        <th>内容（タイトル・本文）</th>
                        <th style="width: 130px;">操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($news_list)): ?>
                        <tr>
                            <td colspan="3" style="text-align: center; color: #777;">現在、投稿されているニュースはありません。</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($news_list as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <div class="text-muted"><?php echo isset($item['content']) ? htmlspecialchars($item['content'], ENT_QUOTES, 'UTF-8') : ''; ?></div>
                                </td>
                                <td>
                                    <div class="action-cols">
                                        <a href="admin.php?edit_id=<?php echo $item['id']; ?>" class="btn-green">編集</a>
                                        <form action="admin.php" method="POST" style="display:inline;" onsubmit="return confirm('本当にこのニュースを削除しますか？');">
                                            <input type="hidden" name="action" value="delete_news">
                                            <input type="hidden" name="delete_id" value="<?php echo $item['id']; ?>">
                                            <button type="submit" class="btn btn-red">削除</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

</body>
</html>