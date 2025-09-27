<?php
/**
 * ナビゲーションヘルパー関数
 */

/**
 * 現在のページがアクティブかどうかを判定
 * 
 * @param string $page_path チェックするページのパス
 * @param string $current_page 現在のページ（デフォルトは$_SERVER['REQUEST_URI']）
 * @return bool アクティブな場合true
 */
function isActivePage($page_path, $current_page = null) {
    if ($current_page === null) {
        $current_page = $_SERVER['REQUEST_URI'];
    }
    
    // クエリパラメータを除いたパスで比較
    $current_path = parse_url($current_page, PHP_URL_PATH);
    $check_path = parse_url($page_path, PHP_URL_PATH);
    
    // 完全一致チェック
    if ($current_path === $check_path) {
        return true;
    }
    
    // ホームページの特別処理
    if ($check_path === '/' && ($current_path === '/index.php' || $current_path === '/')) {
        return true;
    }
    
    return false;
}

/**
 * アクティブページのCSSクラスを取得
 * 
 * @param string $page_path チェックするページのパス
 * @param string $active_class アクティブ時のクラス
 * @param string $inactive_class 非アクティブ時のクラス
 * @return string CSSクラス
 */
function getNavClass($page_path, $active_class = 'text-readnest-primary dark:text-white border-b-2 border-readnest-primary dark:border-white', $inactive_class = 'text-gray-700 dark:text-gray-300 hover:text-readnest-primary dark:hover:text-white') {
    return isActivePage($page_path) ? $active_class : $inactive_class;
}

/**
 * 読書中の本の数を取得
 * 
 * @param int $user_id ユーザーID
 * @return int 読書中の本の数
 */
function getReadingCount($user_id) {
    global $g_db;
    
    $sql = "SELECT COUNT(*) FROM b_book_list WHERE user_id = ? AND status = ?";
    $count = $g_db->getOne($sql, array($user_id, READING_NOW));
    
    return DB::isError($count) ? 0 : intval($count);
}

/**
 * 今週追加した本の数を取得
 * 
 * @param int $user_id ユーザーID
 * @return int 今週追加した本の数
 */
function getWeeklyAddedCount($user_id) {
    global $g_db;
    
    $sql = "SELECT COUNT(*) FROM b_book_list 
            WHERE user_id = ? 
            AND create_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
    $count = $g_db->getOne($sql, array($user_id));
    
    return DB::isError($count) ? 0 : intval($count);
}

/**
 * 未読の通知数を取得（将来の実装用）
 * 
 * @param int $user_id ユーザーID
 * @return int 未読通知数
 */
function getUnreadNotificationCount($user_id) {
    // 将来の実装のためのプレースホルダー
    return 0;
}

/**
 * パンくずリストを生成
 * 
 * @param string $page_type ページタイプ
 * @param array $extra_data 追加データ
 * @return array パンくずリストの配列
 */
function generateBreadcrumbs($page_type, $extra_data = []) {
    $breadcrumbs = [
        ['label' => 'ホーム', 'url' => '/']
    ];
    
    switch ($page_type) {
        case 'bookshelf':
            $breadcrumbs[] = ['label' => '本棚', 'url' => null];
            break;
            
        case 'book_detail':
            $breadcrumbs[] = ['label' => '本棚', 'url' => '/bookshelf.php'];
            $book_title = isset($extra_data['title']) ? $extra_data['title'] : '本の詳細';
            $breadcrumbs[] = ['label' => mb_strimwidth($book_title, 0, 30, '...'), 'url' => null];
            break;
            
        case 'reading_history':
            $breadcrumbs[] = ['label' => '読書統計', 'url' => null];
            break;
            
        case 'popular':
            $breadcrumbs[] = ['label' => '発見', 'url' => '#'];
            $breadcrumbs[] = ['label' => '人気の本', 'url' => null];
            break;
            
        case 'search_review':
            $breadcrumbs[] = ['label' => '発見', 'url' => '#'];
            $breadcrumbs[] = ['label' => 'レビュー検索', 'url' => null];
            break;
            
        case 'recommendations':
            $breadcrumbs[] = ['label' => 'AI推薦', 'url' => null];
            break;
            
        case 'favorites':
            $breadcrumbs[] = ['label' => 'お気に入り', 'url' => null];
            break;
            
        case 'add_book':
            $breadcrumbs[] = ['label' => '本を追加', 'url' => null];
            break;
            
        case 'account':
            $breadcrumbs[] = ['label' => 'アカウント設定', 'url' => null];
            break;
            
        case 'reading_calendar':
            $breadcrumbs[] = ['label' => '発見', 'url' => '#'];
            $breadcrumbs[] = ['label' => '読書カレンダー', 'url' => null];
            break;
            
        case 'ranking':
            $breadcrumbs[] = ['label' => '発見', 'url' => '#'];
            $breadcrumbs[] = ['label' => 'ランキング', 'url' => null];
            break;
    }
    
    return $breadcrumbs;
}