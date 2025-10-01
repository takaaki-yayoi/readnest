<?php
/**
 * いいね機能のヘルパー関数
 */

if (!defined('CONFIG')) {
    die('Direct access not allowed');
}

/**
 * 複数の対象のいいね数を一括取得
 *
 * @param string $target_type 'activity' または 'review'
 * @param array $target_ids 対象IDの配列
 * @return array [target_id => like_count] の連想配列
 */
function getLikeCounts($target_type, $target_ids) {
    global $g_db;

    if (empty($target_ids)) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($target_ids), '?'));
    $sql = "SELECT target_id, like_count
            FROM b_like_count
            WHERE target_type = ? AND target_id IN ($placeholders)";

    $params = array_merge([$target_type], $target_ids);
    $results = $g_db->getAll($sql, $params);

    if (DB::isError($results)) {
        error_log('Failed to get like counts: ' . $results->getMessage());
        return [];
    }

    $counts = [];
    foreach ($results as $row) {
        $counts[$row['target_id']] = (int)$row['like_count'];
    }

    return $counts;
}

/**
 * ユーザーが複数の対象にいいねしているかを一括確認
 *
 * @param int $user_id ユーザーID
 * @param string $target_type 'activity' または 'review'
 * @param array $target_ids 対象IDの配列
 * @return array [target_id => bool] の連想配列
 */
function getUserLikeStates($user_id, $target_type, $target_ids) {
    global $g_db;

    if (empty($target_ids)) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($target_ids), '?'));
    $sql = "SELECT target_id
            FROM b_like
            WHERE user_id = ? AND target_type = ? AND target_id IN ($placeholders)";

    $params = array_merge([$user_id, $target_type], $target_ids);
    $results = $g_db->getAll($sql, $params);

    if (DB::isError($results)) {
        error_log('Failed to get user like states: ' . $results->getMessage());
        return [];
    }

    $states = [];
    // 全てfalseで初期化
    foreach ($target_ids as $id) {
        $states[$id] = false;
    }

    // いいね済みのものをtrueに
    foreach ($results as $row) {
        $states[$row['target_id']] = true;
    }

    return $states;
}

/**
 * 単一の対象のいいね数を取得
 *
 * @param string $target_type 'activity' または 'review'
 * @param int $target_id 対象ID
 * @return int いいね数
 */
function getLikeCount($target_type, $target_id) {
    $counts = getLikeCounts($target_type, [$target_id]);
    return $counts[$target_id] ?? 0;
}

/**
 * ユーザーが特定の対象にいいねしているかを確認
 *
 * @param int $user_id ユーザーID
 * @param string $target_type 'activity' または 'review'
 * @param int $target_id 対象ID
 * @return bool いいね済みならtrue
 */
function isUserLiked($user_id, $target_type, $target_id) {
    $states = getUserLikeStates($user_id, $target_type, [$target_id]);
    return $states[$target_id] ?? false;
}

/**
 * ユーザーがいいねした対象を取得
 *
 * @param int $user_id ユーザーID
 * @param string $target_type 'activity' または 'review' (nullの場合は全て)
 * @param int $limit 取得件数
 * @param int $offset オフセット
 * @return array いいね情報の配列
 */
function getUserLikedItems($user_id, $target_type = null, $limit = 20, $offset = 0) {
    global $g_db;

    $sql = "SELECT
                l.like_id,
                l.target_type,
                l.target_id,
                l.target_user_id,
                l.created_at,
                lc.like_count
            FROM b_like l
            LEFT JOIN b_like_count lc ON l.target_type = lc.target_type AND l.target_id = lc.target_id
            WHERE l.user_id = ?";

    $params = [$user_id];

    if ($target_type !== null) {
        $sql .= " AND l.target_type = ?";
        $params[] = $target_type;
    }

    $sql .= " ORDER BY l.created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;

    $results = $g_db->getAll($sql, $params);

    if (DB::isError($results)) {
        error_log('Failed to get user liked items: ' . $results->getMessage());
        return [];
    }

    return $results ?: [];
}

/**
 * ユーザーがいいねした件数を取得
 *
 * @param int $user_id ユーザーID
 * @param string $target_type 'activity' または 'review' (nullの場合は全て)
 * @return int いいね件数
 */
function getUserLikedCount($user_id, $target_type = null) {
    global $g_db;

    $sql = "SELECT COUNT(*) FROM b_like WHERE user_id = ?";
    $params = [$user_id];

    if ($target_type !== null) {
        $sql .= " AND target_type = ?";
        $params[] = $target_type;
    }

    $count = $g_db->getOne($sql, $params);

    if (DB::isError($count)) {
        error_log('Failed to get user liked count: ' . $count->getMessage());
        return 0;
    }

    return (int)$count;
}

/**
 * 特定ユーザーの投稿が受けたいいねを取得（通知用）
 *
 * @param int $user_id 投稿者のユーザーID
 * @param int $limit 取得件数
 * @param int $since_timestamp この時刻以降のいいねのみ取得（任意）
 * @return array いいね情報の配列
 */
function getReceivedLikes($user_id, $limit = 20, $since_timestamp = null) {
    global $g_db;

    $sql = "SELECT
                l.like_id,
                l.user_id as liker_user_id,
                l.target_type,
                l.target_id,
                l.target_user_id,
                l.created_at,
                u.nickname as liker_nickname,
                u.photo as liker_photo,
                lc.like_count
            FROM b_like l
            INNER JOIN b_user u ON l.user_id = u.user_id
            LEFT JOIN b_like_count lc ON l.target_type = lc.target_type AND l.target_id = lc.target_id
            WHERE l.target_user_id = ?";

    $params = [$user_id];

    if ($since_timestamp !== null) {
        $sql .= " AND l.created_at >= ?";
        $params[] = date('Y-m-d H:i:s', $since_timestamp);
    }

    $sql .= " ORDER BY l.created_at DESC LIMIT ?";
    $params[] = $limit;

    $results = $g_db->getAll($sql, $params);

    if (DB::isError($results)) {
        error_log('Failed to get received likes: ' . $results->getMessage());
        return [];
    }

    return $results ?: [];
}

/**
 * いいねボタンのHTMLを生成
 *
 * @param string $target_type 'activity' または 'review'
 * @param int $target_id 対象ID
 * @param int $like_count いいね数
 * @param bool $is_liked ユーザーがいいね済みか
 * @param array $options オプション（review_user_id等）
 * @return string HTML
 */
function generateLikeButton($target_type, $target_id, $like_count = 0, $is_liked = false, $options = []) {
    $liked_class = $is_liked ? 'text-red-500' : 'text-gray-400';
    $icon_class = $is_liked ? 'fas fa-heart' : 'far fa-heart';

    $data_attrs = 'data-target-type="' . html($target_type) . '" data-target-id="' . $target_id . '"';

    // レビューの場合はreview_user_idも渡す
    if ($target_type === 'review' && isset($options['review_user_id'])) {
        $data_attrs .= ' data-review-user-id="' . (int)$options['review_user_id'] . '"';
    }

    $html = '<button class="like-button inline-flex items-center gap-1 px-3 py-1 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors" ' . $data_attrs . '>';
    $html .= '<i class="' . $icon_class . ' ' . $liked_class . ' like-icon"></i>';
    $html .= '<span class="text-sm like-count">' . number_format($like_count) . '</span>';
    $html .= '</button>';

    return $html;
}

/**
 * レビュー用のtarget_idを生成
 * book_id * 1000000 + user_id の形式
 *
 * @param int $book_id 本ID
 * @param int $user_id ユーザーID
 * @return int target_id
 */
function generateReviewTargetId($book_id, $user_id) {
    return $book_id * 1000000 + $user_id;
}

/**
 * レビュー用のtarget_idを分解
 *
 * @param int $target_id target_id
 * @return array ['book_id' => int, 'user_id' => int]
 */
function parseReviewTargetId($target_id) {
    $book_id = intval($target_id / 1000000);
    $user_id = $target_id % 1000000;

    return [
        'book_id' => $book_id,
        'user_id' => $user_id
    ];
}