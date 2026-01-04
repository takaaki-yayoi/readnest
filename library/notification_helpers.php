<?php
/**
 * 統合通知システム ヘルパー関数
 */

if (!defined('CONFIG')) {
    die('Direct access not allowed');
}

/**
 * 通知を作成
 *
 * @param int $user_id 通知先ユーザーID
 * @param string $type 通知タイプ ('like', 'monthly_report')
 * @param string $title 通知タイトル
 * @param array $options オプション（message, data, link_url, actor_user_id, target_type, target_id）
 * @return int|false 作成された通知ID、失敗時はfalse
 */
function createNotification($user_id, $type, $title, $options = []) {
    global $g_db;

    $sql = "INSERT INTO b_notifications (
                user_id,
                notification_type,
                title,
                message,
                data,
                link_url,
                actor_user_id,
                target_type,
                target_id,
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

    $params = [
        $user_id,
        $type,
        $title,
        $options['message'] ?? null,
        isset($options['data']) ? json_encode($options['data']) : null,
        $options['link_url'] ?? null,
        $options['actor_user_id'] ?? null,
        $options['target_type'] ?? null,
        $options['target_id'] ?? null
    ];

    $result = $g_db->query($sql, $params);

    if (DB::isError($result)) {
        // 重複エラーの場合は無視（既に同じ通知が存在する）
        if (strpos($result->getMessage(), 'Duplicate entry') !== false) {
            return false;
        }
        error_log('Failed to create notification: ' . $result->getMessage());
        return false;
    }

    // 最後に挿入されたIDを取得
    $id = $g_db->getOne("SELECT LAST_INSERT_ID()");
    return DB::isError($id) ? false : (int)$id;
}

/**
 * いいね通知を作成（既存互換性用ラッパー）
 *
 * @param int $target_user_id 通知先ユーザーID（投稿者）
 * @param int $liker_user_id いいねしたユーザーID
 * @param string $target_type 対象タイプ ('activity', 'review')
 * @param int $target_id 対象ID
 * @return int|false 作成された通知ID、失敗時はfalse
 */
function createLikeNotification($target_user_id, $liker_user_id, $target_type, $target_id) {
    // 自分自身へのいいね通知は作成しない
    if ($target_user_id == $liker_user_id) {
        return false;
    }

    // いいねしたユーザーのニックネームを取得
    $liker_name = getNickname($liker_user_id);
    if (!$liker_name) {
        $liker_name = 'ユーザー';
    }

    // 対象タイプの日本語表記
    $target_type_label = $target_type === 'review' ? 'レビュー' : '読書活動';

    $title = "{$liker_name}さんがあなたの{$target_type_label}にいいねしました";

    // リンクURLを生成
    if ($target_type === 'review') {
        // レビューの場合はbook_idを抽出
        $parsed = parseReviewTargetId($target_id);
        $link_url = "/book.php?id={$parsed['book_id']}#review-{$parsed['user_id']}";
    } else {
        // アクティビティの場合
        $link_url = "/activities.php";
    }

    return createNotification($target_user_id, 'like', $title, [
        'actor_user_id' => $liker_user_id,
        'target_type' => $target_type,
        'target_id' => $target_id,
        'link_url' => $link_url
    ]);
}

/**
 * 月間レポート通知を作成
 *
 * @param int $user_id ユーザーID
 * @param int $year 年
 * @param int $month 月
 * @param array $report_data レポートデータ
 * @return int|false 作成された通知ID、失敗時はfalse
 */
function createMonthlyReportNotification($user_id, $year, $month, $report_data) {
    $books_finished = $report_data['statistics']['books_finished'] ?? 0;
    $pages_read = $report_data['statistics']['pages_read'] ?? 0;

    $title = "{$year}年{$month}月の読書レポートができました";

    $message = "";
    if ($books_finished > 0) {
        $message = "{$books_finished}冊読みました！";
        if ($pages_read > 0) {
            $message .= "（{$pages_read}ページ）";
        }
    } else {
        $message = "今月の読書記録を確認しましょう";
    }

    return createNotification($user_id, 'monthly_report', $title, [
        'message' => $message,
        'data' => [
            'year' => $year,
            'month' => $month,
            'books_finished' => $books_finished,
            'pages_read' => $pages_read
        ],
        'link_url' => "/report/{$year}/{$month}"
    ]);
}

/**
 * ユーザーの未読通知件数を取得
 *
 * @param int $user_id ユーザーID
 * @param string|null $type 通知タイプ（nullの場合は全て）
 * @return int 未読件数
 */
function getUnreadNotificationCount($user_id, $type = null) {
    global $g_db;

    $sql = "SELECT COUNT(*) FROM b_notifications WHERE user_id = ? AND is_read = 0";
    $params = [$user_id];

    if ($type !== null) {
        $sql .= " AND notification_type = ?";
        $params[] = $type;
    }

    $count = $g_db->getOne($sql, $params);

    if (DB::isError($count)) {
        error_log('Failed to get unread notification count: ' . $count->getMessage());
        return 0;
    }

    return (int)$count;
}

/**
 * ユーザーの通知一覧を取得
 *
 * @param int $user_id ユーザーID
 * @param int $limit 取得件数
 * @param int $offset オフセット
 * @param string|null $type 通知タイプ（nullの場合は全て）
 * @return array 通知の配列
 */
function getNotifications($user_id, $limit = 50, $offset = 0, $type = null) {
    global $g_db;

    $sql = "SELECT
                n.*,
                u.nickname as actor_nickname,
                u.photo as actor_photo
            FROM b_notifications n
            LEFT JOIN b_user u ON n.actor_user_id = u.user_id
            WHERE n.user_id = ?";

    $params = [$user_id];

    if ($type !== null) {
        $sql .= " AND n.notification_type = ?";
        $params[] = $type;
    }

    $sql .= " ORDER BY n.created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;

    $results = $g_db->getAll($sql, $params);

    if (DB::isError($results)) {
        error_log('Failed to get notifications: ' . $results->getMessage());
        return [];
    }

    // dataフィールドをデコード
    foreach ($results as &$row) {
        if (!empty($row['data'])) {
            $row['data'] = json_decode($row['data'], true);
        }
    }

    return $results ?: [];
}

/**
 * 通知の総件数を取得
 *
 * @param int $user_id ユーザーID
 * @param string|null $type 通知タイプ（nullの場合は全て）
 * @return int 通知件数
 */
function getNotificationCount($user_id, $type = null) {
    global $g_db;

    $sql = "SELECT COUNT(*) FROM b_notifications WHERE user_id = ?";
    $params = [$user_id];

    if ($type !== null) {
        $sql .= " AND notification_type = ?";
        $params[] = $type;
    }

    $count = $g_db->getOne($sql, $params);

    if (DB::isError($count)) {
        error_log('Failed to get notification count: ' . $count->getMessage());
        return 0;
    }

    return (int)$count;
}

/**
 * 通知を既読にする
 *
 * @param int $notification_id 通知ID
 * @param int $user_id ユーザーID（セキュリティチェック用）
 * @return bool 成功時true
 */
function markNotificationAsRead($notification_id, $user_id) {
    global $g_db;

    $sql = "UPDATE b_notifications
            SET is_read = 1, read_at = NOW()
            WHERE notification_id = ? AND user_id = ? AND is_read = 0";

    $result = $g_db->query($sql, [$notification_id, $user_id]);

    if (DB::isError($result)) {
        error_log('Failed to mark notification as read: ' . $result->getMessage());
        return false;
    }

    return true;
}

/**
 * 特定タイプの通知を一括既読にする
 *
 * @param int $user_id ユーザーID
 * @param string $type 通知タイプ
 * @return bool 成功時true
 */
function markNotificationsAsReadByType($user_id, $type) {
    global $g_db;

    $sql = "UPDATE b_notifications
            SET is_read = 1, read_at = NOW()
            WHERE user_id = ? AND notification_type = ? AND is_read = 0";

    $result = $g_db->query($sql, [$user_id, $type]);

    if (DB::isError($result)) {
        error_log('Failed to mark notifications as read by type: ' . $result->getMessage());
        return false;
    }

    return true;
}

/**
 * すべての通知を既読にする
 *
 * @param int $user_id ユーザーID
 * @return bool 成功時true
 */
function markAllNotificationsAsRead($user_id) {
    global $g_db;

    $sql = "UPDATE b_notifications
            SET is_read = 1, read_at = NOW()
            WHERE user_id = ? AND is_read = 0";

    $result = $g_db->query($sql, [$user_id]);

    if (DB::isError($result)) {
        error_log('Failed to mark all notifications as read: ' . $result->getMessage());
        return false;
    }

    return true;
}

/**
 * 古い通知を削除（cron用）
 *
 * @param int $days 削除対象の日数（デフォルト90日）
 * @return int 削除した件数
 */
function cleanOldNotifications($days = 90) {
    global $g_db;

    $sql = "DELETE FROM b_notifications
            WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)";

    $result = $g_db->query($sql, [$days]);

    if (DB::isError($result)) {
        error_log('Failed to clean old notifications: ' . $result->getMessage());
        return 0;
    }

    // 削除した行数を取得
    $affected = $g_db->getOne("SELECT ROW_COUNT()");
    return DB::isError($affected) ? 0 : (int)$affected;
}

/**
 * いいね削除時に通知も削除
 *
 * @param int $target_user_id 通知先ユーザーID
 * @param int $liker_user_id いいねしたユーザーID
 * @param string $target_type 対象タイプ
 * @param int $target_id 対象ID
 * @return bool 成功時true
 */
function deleteLikeNotification($target_user_id, $liker_user_id, $target_type, $target_id) {
    global $g_db;

    $sql = "DELETE FROM b_notifications
            WHERE user_id = ?
            AND notification_type = 'like'
            AND actor_user_id = ?
            AND target_type = ?
            AND target_id = ?";

    $result = $g_db->query($sql, [$target_user_id, $liker_user_id, $target_type, $target_id]);

    if (DB::isError($result)) {
        error_log('Failed to delete like notification: ' . $result->getMessage());
        return false;
    }

    return true;
}

/**
 * 特定タイプを除外して通知を取得
 *
 * @param int $user_id ユーザーID
 * @param int $limit 取得件数
 * @param int $offset オフセット
 * @param string $exclude_type 除外する通知タイプ
 * @return array 通知の配列
 */
function getNotificationsExcludingType($user_id, $limit = 50, $offset = 0, $exclude_type = null) {
    global $g_db;

    $sql = "SELECT n.*,
                   u.nickname as actor_nickname,
                   u.photo as actor_photo
            FROM b_notifications n
            LEFT JOIN b_user u ON n.actor_user_id = u.user_id
            WHERE n.user_id = ?";
    $params = [$user_id];

    if ($exclude_type !== null) {
        $sql .= " AND n.notification_type != ?";
        $params[] = $exclude_type;
    }

    $sql .= " ORDER BY n.created_at DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

    $result = $g_db->getAll($sql, $params);

    if (DB::isError($result)) {
        error_log('Failed to get notifications excluding type: ' . $result->getMessage());
        return [];
    }

    return $result ?: [];
}

/**
 * 特定タイプを除外した通知の総件数を取得
 *
 * @param int $user_id ユーザーID
 * @param string $exclude_type 除外する通知タイプ
 * @return int 通知件数
 */
function getNotificationCountExcludingType($user_id, $exclude_type = null) {
    global $g_db;

    $sql = "SELECT COUNT(*) FROM b_notifications WHERE user_id = ?";
    $params = [$user_id];

    if ($exclude_type !== null) {
        $sql .= " AND notification_type != ?";
        $params[] = $exclude_type;
    }

    $count = $g_db->getOne($sql, $params);

    if (DB::isError($count)) {
        error_log('Failed to get notification count excluding type: ' . $count->getMessage());
        return 0;
    }

    return (int)$count;
}

/**
 * 特定タイプを除外した未読通知件数を取得
 *
 * @param int $user_id ユーザーID
 * @param string $exclude_type 除外する通知タイプ
 * @return int 未読通知件数
 */
function getUnreadNotificationCountExcludingType($user_id, $exclude_type = null) {
    global $g_db;

    $sql = "SELECT COUNT(*) FROM b_notifications WHERE user_id = ? AND is_read = 0";
    $params = [$user_id];

    if ($exclude_type !== null) {
        $sql .= " AND notification_type != ?";
        $params[] = $exclude_type;
    }

    $count = $g_db->getOne($sql, $params);

    if (DB::isError($count)) {
        error_log('Failed to get unread notification count excluding type: ' . $count->getMessage());
        return 0;
    }

    return (int)$count;
}

/**
 * 特定タイプを除外した通知を一括既読にする
 *
 * @param int $user_id ユーザーID
 * @param string $exclude_type 除外する通知タイプ
 * @return bool 成功時true
 */
function markNotificationsAsReadExcludingType($user_id, $exclude_type = null) {
    global $g_db;

    $sql = "UPDATE b_notifications
            SET is_read = 1, read_at = NOW()
            WHERE user_id = ? AND is_read = 0";
    $params = [$user_id];

    if ($exclude_type !== null) {
        $sql .= " AND notification_type != ?";
        $params[] = $exclude_type;
    }

    $result = $g_db->query($sql, $params);

    if (DB::isError($result)) {
        error_log('Failed to mark notifications as read excluding type: ' . $result->getMessage());
        return false;
    }

    return true;
}
