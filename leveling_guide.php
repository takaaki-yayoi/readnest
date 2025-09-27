<?php
/**
 * レベリングシステム説明ページ
 */

declare(strict_types=1);

// モダン設定を読み込み
require_once('modern_config.php');
require_once('library/achievement_system.php');

// ログインチェック
$login_flag = checkLogin();
$mine_user_id = null;
$d_nickname = '';

if ($login_flag) {
    $mine_user_id = $_SESSION['AUTH_USER'];
    $d_nickname = getNickname($mine_user_id);
}

// ページタイトル設定
$d_site_title = 'レベリングシステムについて - ReadNest';

// メタ情報
$g_meta_description = 'ReadNestのレベリングシステムは、読書の成果を可視化し、モチベーションを高める仕組みです。読了した本のページ数に応じてレベルアップし、特別な称号を獲得できます。';
$g_meta_keyword = 'レベリング,読書レベル,称号,読書記録,ReadNest';

// SEOヘルパーを読み込み
require_once('library/seo_helpers.php');

// SEOデータの準備
$canonical_url = getBaseUrl() . '/leveling_guide.php';
$og_image = getBaseUrl() . '/img/og-image.jpg';

$seo_data = [
    'title' => $d_site_title,
    'description' => $g_meta_description,
    'canonical_url' => $canonical_url,
    'og' => [
        'title' => 'レベリングシステムについて',
        'description' => $g_meta_description,
        'url' => $canonical_url,
        'image' => $og_image,
        'type' => 'article'
    ],
    'twitter' => [
        'title' => 'レベリングシステムについて',
        'description' => $g_meta_description,
        'image' => $og_image
    ]
];

// パンくずリストの構造化データ
$breadcrumb_schema = generateBreadcrumbSchema([
    ['name' => 'ホーム', 'url' => getBaseUrl()],
    ['name' => 'レベリングシステム', 'url' => $canonical_url]
]);

$seo_data['schema'] = [$breadcrumb_schema];

// SEOタグの生成
$g_seo_tags = generateSEOTags($seo_data);

// ユーザーレベル情報を取得
$current_level_info = null;
$user_stats = null;

if ($login_flag) {
    try {
        global $g_db;
        
        // 総読書ページ数を取得
        $total_pages = $g_db->getOne("SELECT SUM(total_page) FROM b_book_list 
                                    WHERE user_id = ? AND status IN (?, ?) AND total_page > 0", 
                                   [$mine_user_id, READING_FINISH, READ_BEFORE]);
        
        if (DB::isError($total_pages)) {
            $total_pages = 0;
        } else {
            $total_pages = intval($total_pages ?? 0);
        }
        
        $user_stats = ['total_pages' => $total_pages];
        $current_level_info = getReadingLevel($total_pages);
        
    } catch (Exception $e) {
        error_log("Error getting user level data: " . $e->getMessage());
        $user_stats = ['total_pages' => 0];
        $current_level_info = getReadingLevel(0);
    }
}

// Analytics設定
$g_analytics = '<!-- Google Analytics code would go here -->';

// モダンテンプレートを使用してページを表示
include(getTemplatePath('t_leveling_guide.php'));