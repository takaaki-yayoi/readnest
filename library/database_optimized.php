<?php
/**
 * 最適化されたデータベース関数
 * N+1問題を解決し、パフォーマンスを向上
 */

/**
 * 最適化された新着レビュー取得関数
 * ユーザー情報を同時に取得してN+1問題を解決
 */
function getNewReviewOptimized($user_id = '', $limit = '') {
    global $g_db;

    $limit_clause = '';
    if($limit != '') {
        $limit_clause = "LIMIT " . intval($limit);
    }

    if($user_id != '') {
        $select_sql = "
            SELECT bl.*,
                   bu.user_id, bu.diary_policy, bu.nickname
            FROM b_book_list bl
            INNER JOIN b_user bu ON bl.user_id = bu.user_id
            WHERE bu.user_id = ?
                AND bl.memo IS NOT NULL
                AND bl.memo != ''
                AND bu.diary_policy = ?
                AND bu.status = 1
            ORDER BY bl.update_date DESC
            $limit_clause
        ";

        if(defined('DEBUG')) { d($select_sql); }
        $result = $g_db->getAll($select_sql, array($user_id, '1'), DB_FETCHMODE_ASSOC);
    } else {
        $select_sql = "
            SELECT bl.*,
                   bu.user_id, bu.diary_policy, bu.nickname
            FROM b_book_list bl
            INNER JOIN b_user bu ON bl.user_id = bu.user_id
            WHERE bl.memo IS NOT NULL
                AND bl.memo != ''
                AND bu.diary_policy = ?
                AND bu.status = 1
            ORDER BY bl.update_date DESC
            $limit_clause
        ";

        if(defined('DEBUG')) { d($select_sql); }
        $result = $g_db->getAll($select_sql, array('1'), DB_FETCHMODE_ASSOC);
    }
    
    if(DB::isError($result)) {
        trigger_error($result->getMessage());
        return array();
    }
    
    // プロフィール画像URLを整形
    foreach ($result as &$row) {
        if (!empty($row['photo'])) {
            // データベースから画像を表示
            $row['user_photo_url'] = '/display_profile_photo.php?user_id=' . $row['user_id'] . '&mode=thumbnail';
        } else {
            $row['user_photo_url'] = '/img/no-image-user.png';
        }
    }
    
    return $result;
}

/**
 * 統計情報を1つのクエリで効率的に取得
 */
function getSiteStatisticsOptimized() {
    global $g_db;
    
    // 1つのクエリで複数の統計を取得
    $stats_sql = "
        SELECT 
            (SELECT COUNT(*) FROM b_user WHERE regist_date IS NOT NULL) as total_users,
            (SELECT COUNT(DISTINCT book_id) FROM b_book_list) as total_books,
            (SELECT COUNT(*) FROM b_book_list WHERE memo IS NOT NULL AND memo != '') as total_reviews,
            (SELECT SUM(CASE WHEN status = ? THEN total_page ELSE current_page END) 
             FROM b_book_list WHERE current_page > 0) as total_pages_read
    ";
    
    $result = $g_db->getRow($stats_sql, array(READING_FINISH), DB_FETCHMODE_ASSOC);
    
    if(DB::isError($result)) {
        trigger_error($result->getMessage());
        return array(
            'total_users' => 0,
            'total_books' => 0,
            'total_reviews' => 0,
            'total_pages_read' => 0
        );
    }
    
    return array(
        'total_users' => intval(isset($result['total_users']) ? $result['total_users'] : 0),
        'total_books' => intval(isset($result['total_books']) ? $result['total_books'] : 0),
        'total_reviews' => intval(isset($result['total_reviews']) ? $result['total_reviews'] : 0),
        'total_pages_read' => intval(isset($result['total_pages_read']) ? $result['total_pages_read'] : 0)
    );
}

/**
 * 最適化された最新活動取得関数
 * インデックスヒントを使用して高速化
 */
function getRecentActivitiesOptimized($limit = 10) {
    global $g_db;
    
    $activities_sql = "
        SELECT 
            be.book_id, be.event_date, be.event, be.memo, be.page, be.user_id,
            bl.name as book_name, bl.image_url as book_image_url,
            u.nickname, u.photo as user_photo, u.photo_url as user_photo_url
        FROM b_book_event be USE INDEX (idx_event_type_date)
        INNER JOIN b_user u ON be.user_id = u.user_id
        LEFT JOIN b_book_list bl ON be.book_id = bl.book_id AND be.user_id = bl.user_id
        WHERE u.diary_policy = 1 
            AND u.status = 1
            AND be.event IN (?, ?)
        ORDER BY be.event_date DESC
        LIMIT ?
    ";
    
    $result = $g_db->getAll(
        $activities_sql, 
        array(READING_NOW, READING_FINISH, intval($limit)), 
        DB_FETCHMODE_ASSOC
    );
    
    if(DB::isError($result)) {
        trigger_error($result->getMessage());
        return array();
    }
    
    // 活動データを整形
    $formatted_activities = array();
    
    foreach ($result as $activity) {
        $activity_type = '';
        switch ($activity['event']) {
            case READING_NOW:
                $activity_type = '読書開始';
                break;
            case READING_FINISH:
                $activity_type = '読了';
                break;
            default:
                $activity_type = '更新';
                break;
        }
        
        // プロフィール画像URLを決定
        $user_photo_url = '';
        if (!empty($activity['user_photo_url'])) {
            $user_photo_url = $activity['user_photo_url'];
        } elseif (!empty($activity['user_photo'])) {
            // データベースから画像を表示（thumbnailモードで適切なサイズ）
            $user_photo_url = '/display_profile_photo.php?user_id=' . $activity['user_id'] . '&mode=thumbnail';
        } else {
            $user_photo_url = '/img/no-image-user.png';
        }
        
        $formatted_activities[] = array(
            'type' => $activity_type,
            'user_id' => $activity['user_id'],
            'user_name' => (!empty($activity['nickname']) && trim($activity['nickname']) !== '') ? $activity['nickname'] : '読書家' . substr((string)$activity['user_id'], -4),
            'user_photo' => $user_photo_url,
            'book_id' => $activity['book_id'],
            'book_title' => $activity['book_name'] ?: '不明な本',
            'book_image' => $activity['book_image_url'] ?: '/img/no-image-book.png',
            'activity_date' => date('Y年n月j日 H:i', strtotime($activity['event_date'])),
            'memo' => $activity['memo'],
            'page' => $activity['page']
        );
    }
    
    return $formatted_activities;
}
?>