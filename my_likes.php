<?php
/**
 * いいね一覧ページ
 * ユーザーがいいねした活動とレビューを表示
 */

declare(strict_types=1);

// モダン設定を読み込み
require_once('modern_config.php');

// ログインチェック
$login_flag = checkLogin();
if (!$login_flag) {
    header('Location: /');
    exit;
}

$mine_user_id = (int)$_SESSION['AUTH_USER'];

// いいね機能のヘルパーを読み込み
require_once(dirname(__FILE__) . '/library/like_helpers.php');

// レベル表示関連
require_once(dirname(__FILE__) . '/library/achievement_system.php');
require_once(dirname(__FILE__) . '/library/level_display_helper.php');

// タブ設定（gave: いいねした, received: いいねされた）
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'gave';

// 「いいねされた」タブにアクセスした場合、最終確認時刻を更新
if ($tab === 'received') {
    $update_sql = "UPDATE b_user SET last_like_check = NOW() WHERE user_id = ?";
    $result = $g_db->query($update_sql, [$mine_user_id]);

    if (DB::isError($result)) {
        error_log('Failed to update last_like_check: ' . $result->getMessage());
    }
}

// フィルター設定
$filter_type = isset($_GET['type']) ? $_GET['type'] : 'all'; // all, activity, review

// ページネーション設定
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 30;
$offset = ($page - 1) * $per_page;

// タブに応じてデータを取得
if ($tab === 'received') {
    // いいねされた投稿を取得
    $received_likes = getReceivedLikes($mine_user_id, 1000); // 大きめの数で全件取得

    // タイプでフィルタリング
    $filtered_likes = [];
    foreach ($received_likes as $like) {
        if ($filter_type === 'all' || $like['target_type'] === $filter_type) {
            $filtered_likes[] = $like;
        }
    }

    $total_count = count($filtered_likes);
    $total_pages = ceil($total_count / $per_page);

    // ページネーション適用
    $liked_items = array_slice($filtered_likes, $offset, $per_page);

} else {
    // いいねした投稿を取得
    $target_type_filter = null;
    if ($filter_type === 'activity') {
        $target_type_filter = 'activity';
    } else if ($filter_type === 'review') {
        $target_type_filter = 'review';
    }

    $liked_items = getUserLikedItems($mine_user_id, $target_type_filter, $per_page, $offset);
    $total_count = getUserLikedCount($mine_user_id, $target_type_filter);
    $total_pages = ceil($total_count / $per_page);
}

// いいね情報を詳細データに変換
$formatted_likes = [];

foreach ($liked_items as $item) {
    $formatted_item = [
        'like_id' => $item['like_id'] ?? null,
        'target_type' => $item['target_type'],
        'target_id' => $item['target_id'],
        'target_user_id' => $item['target_user_id'] ?? ($item['liker_user_id'] ?? null),
        'created_at' => $item['created_at'],
        'like_count' => $item['like_count'] ?? 0
    ];

    // 「いいねされた」タブの場合は、いいねした人の情報を追加
    if ($tab === 'received') {
        $formatted_item['liker_user_id'] = $item['liker_user_id'] ?? null;
        $formatted_item['liker_nickname'] = $item['liker_nickname'] ?? null;
        $formatted_item['liker_photo'] = $item['liker_photo'] ?? '/img/no-image-user.png';
    }

    if ($item['target_type'] === 'activity') {
        // 読書活動の詳細を取得
        $activity_sql = "SELECT
                be.event_id,
                be.book_id,
                be.user_id,
                be.event_date,
                be.event,
                be.page,
                be.memo,
                bl.name as book_title,
                bl.author,
                bl.image_url,
                u.nickname,
                u.photo
            FROM b_book_event be
            LEFT JOIN b_book_list bl ON be.book_id = bl.book_id AND be.user_id = bl.user_id
            INNER JOIN b_user u ON be.user_id = u.user_id
            WHERE be.event_id = ?";

        $activity = $g_db->getRow($activity_sql, [$item['target_id']]);

        if ($activity && !DB::isError($activity)) {
            // 活動タイプの判定
            $activity_type_text = '';
            $activity_color = '';

            switch ($activity['event']) {
                case NOT_STARTED:
                    $activity_type_text = '未読';
                    $activity_color = 'blue';
                    break;
                case READING_NOW:
                    $activity_type_text = '読書中';
                    $activity_color = 'yellow';
                    break;
                case READING_FINISH:
                    $activity_type_text = '読了';
                    $activity_color = 'green';
                    break;
                case READ_BEFORE:
                    $activity_type_text = '既読';
                    $activity_color = 'green';
                    break;
                default:
                    $activity_type_text = '更新';
                    $activity_color = 'gray';
            }

            // プロフィール画像URL
            $user_photo_url = '/img/no-image-user.png';
            if (!empty($activity['photo'])) {
                $user_photo_url = '/display_profile_photo.php?user_id=' . $activity['user_id'] . '&mode=thumbnail';
            }

            $formatted_item['activity'] = [
                'event_id' => $activity['event_id'],
                'book_id' => $activity['book_id'],
                'user_id' => $activity['user_id'],
                'user_name' => $activity['nickname'],
                'user_photo' => $user_photo_url,
                'book_title' => $activity['book_title'] ?: 'タイトル不明',
                'author' => $activity['author'] ?: '著者不明',
                'book_image' => $activity['image_url'] ?: '/img/no-image-book.png',
                'type' => $activity_type_text,
                'type_color' => $activity_color,
                'page' => $activity['page'],
                'memo' => $activity['memo'],
                'activity_date' => formatRelativeTime($activity['event_date'])
            ];

            $formatted_likes[] = $formatted_item;
        }

    } else if ($item['target_type'] === 'review') {
        // レビューの詳細を取得
        // target_idから book_id と user_id を分解
        $parsed = parseReviewTargetId($item['target_id']);
        $book_id = $parsed['book_id'];
        $review_user_id = $parsed['user_id'];

        $review_sql = "SELECT
                bl.book_id,
                bl.user_id,
                bl.name as book_title,
                bl.author,
                bl.image_url,
                bl.rating,
                bl.memo,
                bl.update_date,
                u.nickname,
                u.photo
            FROM b_book_list bl
            INNER JOIN b_user u ON bl.user_id = u.user_id
            WHERE bl.book_id = ? AND bl.user_id = ?
            AND (bl.rating > 0 OR (bl.memo IS NOT NULL AND bl.memo != ''))";

        $review = $g_db->getRow($review_sql, [$book_id, $review_user_id]);

        if ($review && !DB::isError($review)) {
            // プロフィール画像URL
            $user_photo_url = '/img/no-image-user.png';
            if (!empty($review['photo'])) {
                $user_photo_url = '/display_profile_photo.php?user_id=' . $review['user_id'] . '&mode=thumbnail';
            }

            $formatted_item['review'] = [
                'book_id' => $review['book_id'],
                'user_id' => $review['user_id'],
                'user_name' => $review['nickname'],
                'user_photo' => $user_photo_url,
                'book_title' => $review['book_title'] ?: 'タイトル不明',
                'author' => $review['author'] ?: '著者不明',
                'book_image' => $review['image_url'] ?: '/img/no-image-book.png',
                'rating' => $review['rating'],
                'memo' => $review['memo'],
                'update_date' => formatRelativeTime($review['update_date'])
            ];

            $formatted_likes[] = $formatted_item;
        }
    }
}

// 統計情報（タブに応じて変更）
if ($tab === 'received') {
    // いいねされた統計
    $all_received = getReceivedLikes($mine_user_id, 10000);
    $stats = [
        'total' => count($all_received),
        'activity' => count(array_filter($all_received, function($like) { return $like['target_type'] === 'activity'; })),
        'review' => count(array_filter($all_received, function($like) { return $like['target_type'] === 'review'; }))
    ];
} else {
    // いいねした統計
    $stats = [
        'total' => getUserLikedCount($mine_user_id),
        'activity' => getUserLikedCount($mine_user_id, 'activity'),
        'review' => getUserLikedCount($mine_user_id, 'review')
    ];
}

// ページタイトル
$d_site_title = 'いいねした投稿 - ReadNest';

// メタ情報
$g_meta_description = 'あなたがいいねした読書活動とレビューの一覧です。';
$g_meta_keyword = 'いいね,読書活動,レビュー,ReadNest';

// テンプレートを読み込み
include(getTemplatePath('t_my_likes.php'));
?>