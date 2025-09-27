<?php
require_once('modern_config.php');

// ログインチェック
$login_flag = checkLogin();
$mine_user_id = null;
$user_info = [];
if ($login_flag) {
    $mine_user_id = $_SESSION['AUTH_USER'];
    
    // デフォルトのユーザー情報を先に設定
    $user_info = [
        'user_id' => $mine_user_id,
        'nickname' => 'ユーザー' . $mine_user_id,  // デバッグ用にIDを追加
        'photo' => null,
        'photo_url' => '/img/no-photo.png'
    ];
    
    // ユーザー情報を取得（データベースから）
    try {
        $user_sql = "SELECT user_id, nickname, photo FROM b_user WHERE user_id = ?";
        $user_result = $g_db->getRow($user_sql, [$mine_user_id], DB_FETCHMODE_ASSOC);
        
        if (!DB::isError($user_result) && $user_result && !empty($user_result['user_id'])) {
            // データベースから取得できた場合は上書き
            $user_info = $user_result;
            $user_info['photo_url'] = getProfilePhotoURL($mine_user_id);
        }
    } catch (Exception $e) {
        // エラーが発生してもデフォルト値を使用
        error_log("User info fetch error: " . $e->getMessage());
    }
}

// ユーザーの読書傾向を取得（ログインユーザーのみ）
$reading_preferences = [];
$recent_books = [];
$favorite_genres = [];
$reading_stats = [];
if ($login_flag && $mine_user_id) {
    // 最近読んだ本を取得
    $sql = "SELECT bl.name as title, bl.author, bl.amazon_id, bl.image_url, bl.rating, bl.status, bl.update_date
            FROM b_book_list bl
            WHERE bl.user_id = ? 
            AND bl.status IN (2, 3, 4) -- 読書中、読了、既読
            ORDER BY bl.update_date DESC
            LIMIT 10";
    $result = $g_db->getAll($sql, [$mine_user_id], DB_FETCHMODE_ASSOC);
    if (!DB::isError($result)) {
        $recent_books = $result;
    }
    
    // よく読むジャンル（タグから推定）
    $sql = "SELECT bt.tag_name, COUNT(*) as count
            FROM b_book_tags bt
            WHERE bt.user_id = ?
            GROUP BY bt.tag_name
            ORDER BY count DESC
            LIMIT 10";
    $result = $g_db->getAll($sql, [$mine_user_id], DB_FETCHMODE_ASSOC);
    if (!DB::isError($result)) {
        $favorite_genres = $result;
    }
    
    // 読書統計を取得
    $sql = "SELECT 
            COUNT(CASE WHEN status = 3 THEN 1 END) as finished_count,
            COUNT(CASE WHEN status = 2 THEN 1 END) as reading_count,
            COUNT(CASE WHEN status = 1 THEN 1 END) as want_to_read_count,
            AVG(CASE WHEN rating > 0 THEN rating END) as avg_rating
            FROM b_book_list
            WHERE user_id = ?";
    $result = $g_db->getRow($sql, [$mine_user_id], DB_FETCHMODE_ASSOC);
    if (!DB::isError($result)) {
        $reading_stats = $result;
    }
}

// ページメタ情報
$d_site_title = '読書アシスタント - ReadNest';
$g_meta_description = 'ReadNestの読書アシスタントがあなたの読書をサポート。気分や状況に合わせた本の提案、読書相談、本探しのお手伝いをします。';
$g_meta_keyword = '読書アシスタント,AI,本,推薦,読書相談,ReadNest';

// テンプレートに渡す変数を明示的に設定
// これらの変数はテンプレート内で使用される
$template_user_info = $user_info;
$template_recent_books = $recent_books;
$template_favorite_genres = $favorite_genres;
$template_reading_stats = $reading_stats;

// テンプレートを読み込み
include(getTemplatePath('t_reading_assistant.php'));
?>