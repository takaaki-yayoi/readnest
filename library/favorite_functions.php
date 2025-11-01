<?php
/**
 * お気に入り機能関連の関数
 */

if(!defined('CONFIG')) {
    die('Direct access not allowed');
}

/**
 * 本がお気に入りに登録されているかチェック
 * 
 * @param int $user_id ユーザーID
 * @param int $book_id 本ID
 * @return bool お気に入りに登録されている場合true
 */
function isFavoriteBook($user_id, $book_id) {
    global $g_db;
    
    if (!$user_id || !$book_id) {
        return false;
    }
    
    $sql = sprintf(
        "SELECT 1 FROM b_book_favorites WHERE user_id = %d AND book_id = %d",
        (int)$user_id,
        (int)$book_id
    );
    $result = $g_db->getOne($sql);
    
    return !DB::isError($result) && $result;
}

/**
 * ユーザーのお気に入り本リストを取得
 * 
 * @param int $user_id ユーザーID
 * @param int $limit 取得件数制限
 * @param int $offset オフセット
 * @param bool $public_only 公開のみ取得するか
 * @return array お気に入り本のリスト
 */
function getUserFavoriteBooks($user_id, $limit = 100, $offset = 0, $public_only = false) {
    global $g_db;
    
    $sql = sprintf(
        "SELECT
            bl.*,
            COALESCE(bl.author, br.author, '') as author,
            f.created_at as favorite_date,
            f.is_public,
            f.sort_order
        FROM b_book_favorites f
        INNER JOIN b_book_list bl ON f.book_id = bl.book_id
        LEFT JOIN b_book_repository br ON bl.amazon_id = br.asin
        WHERE f.user_id = %d",
        (int)$user_id
    );
    
    if ($public_only) {
        $sql .= " AND f.is_public = 1";
    }
    
    // sort_order列が存在する場合はそれで並べ替え、存在しない場合は created_at で並べ替え
    $sql .= " ORDER BY COALESCE(f.sort_order, 999999), f.created_at DESC";
    
    if ($limit > 0) {
        $sql .= sprintf(" LIMIT %d OFFSET %d", (int)$limit, (int)$offset);
    }
    
    $result = $g_db->getAll($sql, null, DB_FETCHMODE_ASSOC);
    
    if (DB::isError($result)) {
        error_log("Error getting favorite books: " . $result->getMessage());
        return [];
    }
    
    return $result;
}

/**
 * お気に入り数を取得
 * 
 * @param int $user_id ユーザーID
 * @return int お気に入り数
 */
function getUserFavoriteCount($user_id) {
    global $g_db;
    
    $sql = sprintf(
        "SELECT COUNT(*) FROM b_book_favorites WHERE user_id = %d",
        (int)$user_id
    );
    
    $result = $g_db->getOne($sql);
    
    if (DB::isError($result)) {
        return 0;
    }
    
    return (int)$result;
}

/**
 * 複数の本のお気に入り状態を一括取得
 * 
 * @param int $user_id ユーザーID
 * @param array $book_ids 本IDの配列
 * @return array book_id => is_favorite の連想配列
 */
function getBulkFavoriteStatus($user_id, $book_ids) {
    global $g_db;
    
    if (!$user_id || empty($book_ids)) {
        return [];
    }
    
    // sprintf を使用してクエリを構築
    $book_ids_safe = array_map('intval', $book_ids);
    $placeholders = implode(',', $book_ids_safe);
    $sql = sprintf(
        "SELECT book_id FROM b_book_favorites 
         WHERE user_id = %d AND book_id IN (%s)",
        (int)$user_id,
        $placeholders
    );
    
    $result = $g_db->getAll($sql, null, DB_FETCHMODE_ASSOC);
    
    if (DB::isError($result)) {
        return [];
    }
    
    // 結果からbook_idの配列を作成
    $favorite_book_ids = [];
    foreach ($result as $row) {
        $favorite_book_ids[] = $row['book_id'];
    }
    
    $favorites = [];
    foreach ($book_ids as $book_id) {
        $favorites[$book_id] = in_array($book_id, $favorite_book_ids);
    }
    
    return $favorites;
}

/**
 * お気に入りの公開設定を更新
 * 
 * @param int $user_id ユーザーID
 * @param int $book_id 本ID
 * @param bool $is_public 公開するか
 * @return bool 成功した場合true
 */
function updateFavoritePrivacy($user_id, $book_id, $is_public) {
    global $g_db;
    
    $sql = sprintf(
        "UPDATE b_book_favorites 
         SET is_public = %d 
         WHERE user_id = %d AND book_id = %d",
        $is_public ? 1 : 0,
        (int)$user_id,
        (int)$book_id
    );
    
    $result = $g_db->query($sql);
    
    return !DB::isError($result);
}

/**
 * 全お気に入りの公開設定を一括更新
 * 
 * @param int $user_id ユーザーID
 * @param bool $is_public 公開するか
 * @return bool 成功した場合true
 */
function updateAllFavoritesPrivacy($user_id, $is_public) {
    global $g_db;
    
    $sql = sprintf(
        "UPDATE b_book_favorites 
         SET is_public = %d 
         WHERE user_id = %d",
        $is_public ? 1 : 0,
        (int)$user_id
    );
    
    $result = $g_db->query($sql);
    
    return !DB::isError($result);
}

?>