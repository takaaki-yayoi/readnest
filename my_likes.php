<?php
/**
 * いいね一覧ページ
 * ユーザーがいいねした活動/いいねされた通知を表示
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
require_once(dirname(__FILE__) . '/library/notification_helpers.php');

// レベル表示関連
require_once(dirname(__FILE__) . '/library/achievement_system.php');
require_once(dirname(__FILE__) . '/library/level_display_helper.php');

// タブ設定: gave(いいねした) / received(いいねされた)
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'gave';
if (!in_array($tab, ['gave', 'received'])) {
    $tab = 'gave';
}

// フィルター設定（gaveタブのみ）
$filter_type = isset($_GET['type']) ? $_GET['type'] : 'all'; // all, activity, review

// ページネーション設定
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 30;
$offset = ($page - 1) * $per_page;

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

// いいね情報を詳細データに変換
$formatted_likes = [];

foreach ($liked_items as $item) {
    $formatted_item = [
        'like_id' => $item['like_id'] ?? null,
        'target_type' => $item['target_type'],
        'target_id' => $item['target_id'],
        'target_user_id' => $item['target_user_id'] ?? null,
        'created_at' => $item['created_at'],
        'like_count' => $item['like_count'] ?? 0
    ];

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

// 統計情報（両タブ用）
$stats = [
    // いいねしたタブ用
    'gave_total' => getUserLikedCount($mine_user_id),
    'gave_activity' => getUserLikedCount($mine_user_id, 'activity'),
    'gave_review' => getUserLikedCount($mine_user_id, 'review'),
    // いいねされたタブ用
    'received_total' => getNotificationCount($mine_user_id, 'like'),
    'received_unread' => getUnreadNotificationCount($mine_user_id, 'like')
];

// いいねされたタブの場合、通知データを取得して詳細情報を付加
$received_items = [];
if ($tab === 'received') {
    $received_notifications = getNotifications($mine_user_id, $per_page, $offset, 'like');
    $total_count = $stats['received_total'];
    $total_pages = ceil($total_count / $per_page);

    // いいねされたタブを開いた時に既読にする
    markNotificationsAsReadByType($mine_user_id, 'like');

    // 各通知の詳細情報を取得
    foreach ($received_notifications as $notification) {
        $item = [
            'notification_id' => $notification['notification_id'],
            'target_type' => $notification['target_type'],
            'target_id' => $notification['target_id'],
            'actor_user_id' => $notification['actor_user_id'],
            'actor_nickname' => $notification['actor_nickname'],
            'actor_photo' => $notification['actor_photo'],
            'created_at' => $notification['created_at'],
            'is_read' => $notification['is_read']
        ];

        if ($notification['target_type'] === 'activity') {
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
                    bl.image_url
                FROM b_book_event be
                LEFT JOIN b_book_list bl ON be.book_id = bl.book_id AND be.user_id = bl.user_id
                WHERE be.event_id = ?";

            $activity = $g_db->getRow($activity_sql, [$notification['target_id']]);

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

                $item['activity'] = [
                    'event_id' => $activity['event_id'],
                    'book_id' => $activity['book_id'],
                    'book_title' => $activity['book_title'] ?: 'タイトル不明',
                    'author' => $activity['author'] ?: '著者不明',
                    'book_image' => $activity['image_url'] ?: '/img/no-image-book.png',
                    'type' => $activity_type_text,
                    'type_color' => $activity_color,
                    'page' => $activity['page'],
                    'memo' => $activity['memo'],
                    'activity_date' => formatRelativeTime($activity['event_date'])
                ];

                $received_items[] = $item;
            }

        } else if ($notification['target_type'] === 'review') {
            // レビューの詳細を取得
            $parsed = parseReviewTargetId($notification['target_id']);
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
                    bl.update_date
                FROM b_book_list bl
                WHERE bl.book_id = ? AND bl.user_id = ?";

            $review = $g_db->getRow($review_sql, [$book_id, $review_user_id]);

            if ($review && !DB::isError($review)) {
                $item['review'] = [
                    'book_id' => $review['book_id'],
                    'book_title' => $review['book_title'] ?: 'タイトル不明',
                    'author' => $review['author'] ?: '著者不明',
                    'book_image' => $review['image_url'] ?: '/img/no-image-book.png',
                    'rating' => $review['rating'],
                    'memo' => $review['memo'],
                    'update_date' => formatRelativeTime($review['update_date'])
                ];

                $received_items[] = $item;
            }
        }
    }
}

// ページタイトル
if ($tab === 'received') {
    $d_site_title = 'いいねされた投稿 - ReadNest';
    $g_meta_description = 'あなたがいいねされた読書活動とレビューの一覧です。';
} else {
    $d_site_title = 'いいねした投稿 - ReadNest';
    $g_meta_description = 'あなたがいいねした読書活動とレビューの一覧です。';
}
$g_meta_keyword = 'いいね,読書活動,レビュー,ReadNest';

// テンプレートを読み込み
include(getTemplatePath('t_my_likes.php'));
?>
