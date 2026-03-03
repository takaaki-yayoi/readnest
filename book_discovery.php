<?php
/**
 * 本の発見 - 対話型ディスカバリーページ
 *
 * ユーザーの「気分」入力からLLMが読書プロファイルと知識を組み合わせて
 * 「なぜこの本?」を言語化した推薦カードを生成する。
 */

require_once('modern_config.php');

// ログインチェック
$login_flag = checkLogin();
if (!$login_flag) {
    header('Location: /');
    exit;
}

$mine_user_id = $_SESSION['AUTH_USER'];

global $g_db;

// よく読むジャンル（サジェストチップ用）
$genres_sql = "SELECT tag_name, COUNT(*) as count
        FROM b_book_tags
        WHERE user_id = ?
        GROUP BY tag_name
        ORDER BY count DESC
        LIMIT 5";
$top_genres = $g_db->getAll($genres_sql, [$mine_user_id], DB_FETCHMODE_ASSOC);
if (DB::isError($top_genres)) {
    $top_genres = [];
}

// 読書統計（ヘッダー表示用）
$stats_sql = "SELECT
        COUNT(*) as total,
        COUNT(CASE WHEN status IN (3, 4) THEN 1 END) as finished,
        COUNT(CASE WHEN rating >= 4 THEN 1 END) as highly_rated,
        AVG(CASE WHEN rating > 0 THEN rating END) as avg_rating
        FROM b_book_list
        WHERE user_id = ?";
$user_stats = $g_db->getRow($stats_sql, [$mine_user_id], DB_FETCHMODE_ASSOC);
if (DB::isError($user_stats)) {
    $user_stats = ['total' => 0, 'finished' => 0, 'highly_rated' => 0, 'avg_rating' => 0];
}

// テンプレートに渡すデータ
$discovery_data = [
    'top_genres' => $top_genres,
    'user_stats' => $user_stats
];

// ページメタ情報
$d_site_title = '本の発見 - ReadNest';
$g_meta_description = 'AIがあなたの読書傾向を分析して、まだ出会っていない本を提案します。気分を入力するだけで、なぜこの本があなたに合うかを説明付きで推薦。';
$g_meta_keyword = '本の発見,AI推薦,読書提案,パーソナライズ,ReadNest';

// テンプレートを読み込み
include(getTemplatePath('t_book_discovery.php'));
?>
