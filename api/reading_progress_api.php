<?php
/**
 * 読書進捗データAPI
 * Chart.js v4用のデータを返す
 * PHP 8.2.28対応版
 */

declare(strict_types=1);

require_once('../config.php');
require_once('../library/database.php');
require_once('../library/session.php');

// セッション管理
$con = new SessionClass();
$con->Session();

// JSONレスポンスヘッダー
header('Content-Type: application/json; charset=UTF-8');

// 必須パラメータチェック
if (!isset($_GET['book_id'])) {
    http_response_code(400);
    echo json_encode(['error' => '本が指定されていません'], JSON_UNESCAPED_UNICODE);
    exit;
}

$book_id = (int)$_GET['book_id'];
if ($book_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => '無効な本IDです'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ユーザーIDを取得（ログインしている場合）
$mine_user_id = null;
if (isset($_SESSION['AUTH_USER'])) {
    $mine_user_id = $_SESSION['AUTH_USER'];
}

$db = DB_Connect();

// 本の情報を取得（finished_dateも含む）
$book_sql = "
    SELECT 
        b.book_id, 
        b.title, 
        b.total_page, 
        b.status,
        bl.finished_date
    FROM b_book b
    LEFT JOIN b_book_list bl ON b.book_id = bl.book_id AND bl.user_id = ?
    WHERE b.book_id = ?
";
$book_result = DB_Query($book_sql, [$mine_user_id ?? 0, $book_id]);

if (!$book_result || count($book_result) === 0) {
    http_response_code(404);
    echo json_encode(['error' => '本が見つかりません'], JSON_UNESCAPED_UNICODE);
    exit;
}

$book = $book_result[0];

// 読書イベントを取得
$event_sql = "SELECT event_date, page, comment FROM b_book_event WHERE book_id = ? ORDER BY event_date ASC";
$events = DB_Query($event_sql, [$book_id]);

// データを整形
$labels = [];
$data = [];
$annotations = [];

if ($events && count($events) > 0) {
    // 開始日
    $start_date = date('Y-m-d', strtotime($events[0]['event_date']));
    $labels[] = $start_date;
    $data[] = 0;
    
    // 各イベント
    foreach ($events as $event) {
        $date = date('Y-m-d', strtotime($event['event_date']));
        $labels[] = $date;
        $data[] = (int)$event['page'];
        
        // コメントがある場合はアノテーション追加
        if (!empty($event['comment'])) {
            $annotations[] = [
                'type' => 'point',
                'xValue' => $date,
                'yValue' => (int)$event['page'],
                'backgroundColor' => 'rgba(255, 99, 132, 0.25)',
                'borderColor' => 'rgb(255, 99, 132)',
                'borderWidth' => 2,
                'radius' => 6,
                'label' => [
                    'enabled' => true,
                    'content' => mb_substr($event['comment'] ?? '', 0, 20) . (mb_strlen($event['comment'] ?? '') > 20 ? '...' : ''),
                    'position' => 'top'
                ]
            ];
        }
    }
    
    // 読了している場合は読了日まで、していない場合は現在まで
    if ($book['status'] == READING_FINISH || $book['status'] == READ_BEFORE) {
        // 読了日がある場合はそれを使用
        if (!empty($book['finished_date'])) {
            $finished_date = date('Y-m-d', strtotime($book['finished_date']));
            // 最後のイベント日と読了日が異なる場合のみ追加
            if (empty($labels) || end($labels) != $finished_date) {
                $labels[] = $finished_date;
                $data[] = (int)$book['total_page'];
            }
        }
    } else {
        // 読了していない場合は現在まで
        $labels[] = date('Y-m-d');
        $data[] = end($data); // 最後のページ数を維持
    }
}

// レスポンスデータ
$response = [
    'book' => [
        'title' => $book['title'],
        'total_page' => (int)$book['total_page'],
        'status' => (int)$book['status']
    ],
    'chart' => [
        'labels' => $labels,
        'datasets' => [
            [
                'label' => '読書進捗',
                'data' => $data,
                'borderColor' => 'rgb(34, 197, 94)',
                'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                'tension' => 0.1,
                'fill' => true
            ]
        ]
    ],
    'options' => [
        'responsive' => true,
        'plugins' => [
            'legend' => [
                'display' => false
            ],
            'title' => [
                'display' => true,
                'text' => '読書進捗グラフ'
            ],
            'annotation' => [
                'annotations' => $annotations
            ]
        ],
        'scales' => [
            'x' => [
                'type' => 'time',
                'time' => [
                    'unit' => 'day',
                    'displayFormats' => [
                        'day' => 'MM/dd'
                    ]
                ],
                'title' => [
                    'display' => true,
                    'text' => '日付'
                ]
            ],
            'y' => [
                'beginAtZero' => false,
                'min' => max(0, min($data) - 50), // 最小値から50ページ下を下限に
                'max' => min((int)$book['total_page'], max($data) + 50), // 最大値から50ページ上を上限に
                'title' => [
                    'display' => true,
                    'text' => 'ページ数'
                ]
            ]
        ]
    ]
];

echo json_encode($response, JSON_UNESCAPED_UNICODE);