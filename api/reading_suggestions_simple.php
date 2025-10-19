<?php
/**
 * AI読書提案API（シンプル版）
 * エラーの原因を特定するための最小構成
 */

// すべてのエラー出力を抑制
error_reporting(0);
ini_set('display_errors', 0);

// 出力バッファリング開始
ob_start();

// JSONヘッダー設定
header('Content-Type: application/json; charset=utf-8');

try {
    // 設定ファイルの読み込み
    $config_file = dirname(__FILE__) . '/../config.php';
    if (!file_exists($config_file)) {
        throw new Exception('Config file not found');
    }
    require_once $config_file;
    
    // データベースライブラリの読み込み
    $db_file = dirname(__FILE__) . '/../library/database.php';
    if (!file_exists($db_file)) {
        throw new Exception('Database library not found');
    }
    require_once $db_file;
    
    // セッション確認
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $g_login_id = isset($_SESSION['AUTH_USER']) ? $_SESSION['AUTH_USER'] : null;
    
    if (!$g_login_id) {
        ob_clean();
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    
    // ユーザーID取得
    $user_id = isset($_GET['user']) ? $_GET['user'] : $g_login_id;

    // 他人のデータの場合は公開設定を確認
    if ($user_id != $g_login_id) {
        $target_user = getUserInformation($user_id);
        if (!$target_user || $target_user['diary_policy'] != 1 || $target_user['status'] != 1) {
            ob_clean();
            echo json_encode(['error' => 'Access denied']);
            exit;
        }
    }

    // データベース接続確認
    global $g_db;
    if (!isset($g_db) || !$g_db) {
        throw new Exception('Database connection failed');
    }
    
    // シンプルなフォールバック提案を返す
    $suggestions = [
        [
            'type' => 'genre_exploration',
            'title' => '新しいジャンルを探索してみませんか？',
            'description' => '読書の幅を広げて新たな発見をしましょう。',
            'action_text' => 'おすすめ本を探す',
            'action_url' => '/add_book.php'
        ],
        [
            'type' => 'reading_pace',
            'title' => '読書習慣を作りませんか？',
            'description' => '毎日少しずつ読書時間を作って、充実した読書ライフを。',
            'action_text' => '読書計画を立てる',
            'action_url' => '/bookshelf.php'
        ],
        [
            'type' => 'unread_focus',
            'title' => '積読本に挑戦しませんか？',
            'description' => '本棚で眠っている本を読み始めてみましょう。',
            'action_text' => '未読本を見る',
            'action_url' => '/bookshelf.php?status=1,2'
        ]
    ];
    
    // 基本的な統計情報
    $stats_sql = "SELECT 
        COUNT(*) as total_books,
        SUM(CASE WHEN status = 3 THEN 1 ELSE 0 END) as finished_books
    FROM b_book_list 
    WHERE user_id = ?";
    
    $stats_result = $g_db->getRow($stats_sql, [$user_id], DB_FETCHMODE_ASSOC);
    
    if (DB::isError($stats_result)) {
        $total_books = 0;
        $finished_books = 0;
    } else {
        $total_books = $stats_result['total_books'] ?? 0;
        $finished_books = $stats_result['finished_books'] ?? 0;
    }
    
    // レスポンス作成
    $response = [
        'suggestions' => $suggestions,
        'analysis' => [
            'total_genres' => 3,
            'most_read_genre' => '文学・小説',
            'recent_activity' => $finished_books,
            'reading_pace' => 2,
            'unread_books' => $total_books - $finished_books
        ]
    ];
    
    // 出力バッファをクリアしてJSONを出力
    ob_clean();
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    // エラー時も確実にJSONを返す
    ob_clean();
    echo json_encode([
        'error' => 'Error: ' . $e->getMessage(),
        'suggestions' => [
            [
                'type' => 'genre_exploration',
                'title' => '読書を始めてみませんか？',
                'description' => '新しい本との出会いを楽しみましょう。',
                'action_text' => '本を探す',
                'action_url' => '/add_book.php'
            ]
        ],
        'analysis' => [
            'total_genres' => 0,
            'most_read_genre' => null,
            'recent_activity' => 0,
            'reading_pace' => 0,
            'unread_books' => 0
        ]
    ], JSON_UNESCAPED_UNICODE);
}

// スクリプトを終了
exit;
?>