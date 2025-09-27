<?php
/**
 * 検索候補API
 * 通常検索用のタイトル候補を返す
 */

declare(strict_types=1);

// エラーハンドリング設定
error_reporting(0);
ini_set('display_errors', '0');

// 設定読み込み
require_once(dirname(__DIR__) . '/config.php');
require_once(dirname(__DIR__) . '/library/database.php');
require_once(dirname(__DIR__) . '/library/book_search.php');

// データベース接続を初期化
global $g_db;
if (!isset($g_db) || !$g_db) {
    $g_db = DB_Connect();
}

// レスポンスヘッダー設定
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// パラメータ取得
$query = trim($_GET['q'] ?? '');

// バリデーション
if (empty($query) || mb_strlen($query) < 2) {
    echo json_encode([
        'success' => false,
        'suggestions' => []
    ]);
    exit;
}

try {
    // Google Books APIで検索（少数の結果のみ）
    $results = searchBooksWithGoogleAPI($query, 1, 8);
    
    $suggestions = [];
    
    if (!empty($results['books'])) {
        foreach ($results['books'] as $book) {
            $title = $book['Title'] ?? '';
            $author = $book['Author'] ?? '';
            
            if (!empty($title)) {
                $suggestions[] = [
                    'title' => $title,
                    'author' => $author,
                    'type' => 'book'
                ];
            }
        }
    }
    
    // よくある検索パターンの候補を追加
    if (count($suggestions) < 5) {
        // ジャンルやカテゴリの候補
        $patterns = [
            'ミステリー' => ['ミステリー小説', 'ミステリー 新刊', 'ミステリー ベストセラー'],
            'ビジネス' => ['ビジネス書', 'ビジネス書 新刊', 'ビジネス 自己啓発'],
            '小説' => ['小説 新刊', '小説 ベストセラー', '恋愛小説'],
            'プログラミング' => ['プログラミング入門', 'プログラミング Python', 'プログラミング 初心者']
        ];
        
        foreach ($patterns as $key => $values) {
            if (mb_strpos($query, $key) !== false) {
                foreach ($values as $value) {
                    if (count($suggestions) < 8) {
                        $suggestions[] = [
                            'title' => $value,
                            'author' => '',
                            'type' => 'suggestion'
                        ];
                    }
                }
                break;
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'suggestions' => $suggestions
    ]);
    
} catch (Exception $e) {
    error_log('Search suggestions API error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'suggestions' => []
    ]);
}
?>