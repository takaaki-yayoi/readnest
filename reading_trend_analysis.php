<?php
// 読書傾向分析から読書インサイトへリダイレクト
header('Location: /reading_insights.php?mode=clusters');
exit;

$mine_user_id = $_SESSION['AUTH_USER'];

// 分析器のインスタンス作成
$analyzer = new ReadingTrendAnalyzer();

// ユーザーの読書傾向サマリーを取得
$summary = $analyzer->getUserReadingSummary((int)$mine_user_id);

// 多様性スコアを計算
$diversityScore = $analyzer->calculateDiversityScore((int)$mine_user_id);

// レビュークラスタを取得
$clusters = $analyzer->getReviewClusters((int)$mine_user_id, 5);

// 類似した読者を探す
$similarReaders = $analyzer->findSimilarReaders((int)$mine_user_id, 5);

// ページメタ情報
$d_site_title = '読書傾向分析 - ReadNest';
$g_meta_description = 'あなたの読書傾向を詳しく分析。ジャンル分布、読書ペース、レビュー特性などを可視化します。';
$g_meta_keyword = '読書傾向,読書分析,読書統計,レビュー分析,ReadNest';

// テンプレートを読み込み
include(getTemplatePath('t_reading_trend_analysis.php'));
?>