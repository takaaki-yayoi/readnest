<?php
/**
 * 拡張版読書マップAPI
 * タグとレビューembeddingを組み合わせた意味的なグループ化
 */

require_once '../config.php';
require_once '../library/database.php';
require_once '../library/embedding_analyzer.php';

// セッション確認
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$g_login_id = isset($_SESSION['AUTH_USER']) ? $_SESSION['AUTH_USER'] : null;

if (!$g_login_id) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// ユーザーID取得
$user_id = isset($_GET['user']) ? $_GET['user'] : $g_login_id;

// 他人のデータの場合は公開設定を確認
if ($user_id != $g_login_id) {
    $target_user = getUserInformation($user_id);
    if (!$target_user || $target_user['diary_policy'] != 1 || $target_user['status'] != 1) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Access denied']);
        exit;
    }
}

// データベース接続
global $g_db;

// EmbeddingAnalyzerインスタンス作成
$analyzer = new EmbeddingAnalyzer();

// 基本統計を取得
$stats_sql = "SELECT 
    COUNT(*) as total_books,
    SUM(CASE WHEN status = " . READING_FINISH . " THEN 1 ELSE 0 END) as finished_books,
    SUM(CASE WHEN status = " . READING_NOW . " THEN 1 ELSE 0 END) as reading_books
FROM b_book_list 
WHERE user_id = ?";

$stats_result = $g_db->getRow($stats_sql, [$user_id], DB_FETCHMODE_ASSOC);

if (DB::isError($stats_result) || $stats_result['total_books'] == 0) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'data' => ['name' => '読書マップ', 'children' => []],
        'stats' => [
            'total_books' => 0,
            'finished_books' => 0,
            'reading_books' => 0,
            'categories' => 0
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// embedding分析を実行（クラスタ数を8に設定）
$clusters = $analyzer->analyzeUserReadingClusters($user_id, 8);

// タグデータも取得して補強
$tag_sql = "SELECT 
    bt.book_id,
    bt.tag_name,
    bl.name as book_title,
    bl.author,
    bl.status,
    bl.rating
FROM b_book_tags bt
JOIN b_book_list bl ON bt.book_id = bl.book_id AND bt.user_id = bl.user_id
WHERE bt.user_id = ?";

$tag_data = $g_db->getAll($tag_sql, [$user_id], DB_FETCHMODE_ASSOC);

// タグ別の集計
$tag_counts = [];
if (!DB::isError($tag_data)) {
    foreach ($tag_data as $tag) {
        if (!isset($tag_counts[$tag['tag_name']])) {
            $tag_counts[$tag['tag_name']] = [
                'count' => 0,
                'books' => []
            ];
        }
        $tag_counts[$tag['tag_name']]['count']++;
        $tag_counts[$tag['tag_name']]['books'][] = $tag['book_id'];
    }
}

// データ構造を準備
$map_data = [
    'name' => '読書マップ',
    'children' => []
];

// カラーパレット（意味のあるグループ分けのための色）
$semantic_colors = [
    '#8B5CF6', // 紫 - 文学・フィクション
    '#3B82F6', // 青 - ビジネス・実用
    '#10B981', // 緑 - 自己啓発・成長
    '#F59E0B', // オレンジ - エンターテイメント
    '#EF4444', // 赤 - ミステリー・サスペンス
    '#EC4899', // ピンク - ロマンス・人間ドラマ
    '#14B8A6', // ティール - 科学・技術
    '#6366F1', // インディゴ - 哲学・思想
];

// 1. Embeddingベースのクラスタを主カテゴリとして追加
foreach ($clusters as $index => $cluster) {
    $category = [
        'name' => $cluster['name'],
        'type' => 'semantic', // 意味的グループ
        'color' => $semantic_colors[$index % count($semantic_colors)],
        'value' => $cluster['size'],
        'avgRating' => $cluster['avg_rating'],
        'keywords' => array_slice($cluster['keywords'], 0, 5),
        'children' => []
    ];
    
    // クラスタ内の本を追加
    foreach ($cluster['books'] as $book) {
        $book_node = [
            'name' => mb_substr($book['title'], 0, 30) . (mb_strlen($book['title']) > 30 ? '...' : ''),
            'fullTitle' => $book['title'],
            'author' => $book['author'],
            'value' => 1,
            'rating' => $book['rating'],
            'bookId' => $book['book_id'],
            'imageUrl' => $book['image_url'] ?? null,
            'type' => 'book'
        ];
        
        // タグ情報も追加
        $book_tags = [];
        foreach ($tag_data as $tag) {
            if ($tag['book_id'] == $book['book_id']) {
                $book_tags[] = $tag['tag_name'];
            }
        }
        if (!empty($book_tags)) {
            $book_node['tags'] = $book_tags;
        }
        
        $category['children'][] = $book_node;
    }
    
    $map_data['children'][] = $category;
}

// 2. タグベースのカテゴリも追加（embeddingクラスタに含まれない本がある場合）
$clustered_books = [];
foreach ($clusters as $cluster) {
    foreach ($cluster['books'] as $book) {
        $clustered_books[$book['book_id']] = true;
    }
}

// タグのみの本を収集
$tag_only_books = [];
$top_tags = array_slice($tag_counts, 0, 10, true); // 上位10タグ

foreach ($top_tags as $tag_name => $tag_info) {
    $tag_books = [];
    
    foreach ($tag_info['books'] as $book_id) {
        if (!isset($clustered_books[$book_id])) {
            // embeddingクラスタに含まれていない本
            $book_sql = "SELECT book_id, name, author, rating, status, image_url 
                        FROM b_book_list 
                        WHERE book_id = ? AND user_id = ?";
            $book_info = $g_db->getRow($book_sql, [$book_id, $user_id], DB_FETCHMODE_ASSOC);
            
            if (!DB::isError($book_info)) {
                $tag_books[] = [
                    'name' => mb_substr($book_info['name'], 0, 30) . (mb_strlen($book_info['name']) > 30 ? '...' : ''),
                    'fullTitle' => $book_info['name'],
                    'author' => $book_info['author'],
                    'value' => 1,
                    'rating' => $book_info['rating'],
                    'bookId' => $book_info['book_id'],
                    'imageUrl' => $book_info['image_url'] ?? null,
                    'type' => 'book'
                ];
            }
        }
    }
    
    if (!empty($tag_books)) {
        $map_data['children'][] = [
            'name' => '📌 ' . $tag_name,
            'type' => 'tag',
            'color' => '#94A3B8', // グレー系（タグベース）
            'value' => count($tag_books),
            'children' => $tag_books
        ];
    }
}

// 3. 読書状況別のサマリーを追加
$status_summary = [
    'name' => '📊 読書状況',
    'type' => 'status',
    'color' => '#64748B',
    'children' => [
        [
            'name' => '読了',
            'value' => $stats_result['finished_books'],
            'color' => '#10B981'
        ],
        [
            'name' => '読書中',
            'value' => $stats_result['reading_books'],
            'color' => '#F59E0B'
        ],
        [
            'name' => '未読',
            'value' => $stats_result['total_books'] - $stats_result['finished_books'] - $stats_result['reading_books'],
            'color' => '#94A3B8'
        ]
    ]
];

// 統計情報
$stats = [
    'total_books' => $stats_result['total_books'],
    'finished_books' => $stats_result['finished_books'],
    'reading_books' => $stats_result['reading_books'],
    'semantic_clusters' => count($clusters),
    'tag_groups' => count($top_tags),
    'avg_cluster_size' => count($clusters) > 0 ? round(array_sum(array_column($clusters, 'size')) / count($clusters), 1) : 0,
    'diversity_score' => $analyzer->calculateEmbeddingDiversity($user_id)
];

// レスポンスを返す
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'data' => $map_data,
    'stats' => $stats,
    'status_summary' => $status_summary,
    'user_id' => $user_id,
    'type' => 'enhanced' // 拡張版であることを示すフラグ
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>