#!/usr/bin/php
<?php
/**
 * 作家クラウド更新スクリプト（cron用）
 * 毎日深夜に実行することを推奨
 * 
 * crontab設定例:
 * 0 3 * * * /usr/bin/php /path/to/readnest/cron/update_sakka_cloud.php
 */

require_once(dirname(__DIR__) . '/modern_config.php');
require_once(dirname(__DIR__) . '/library/sakka_cloud_generator.php');

// 実行時間制限を解除
set_time_limit(0);

echo date('Y-m-d H:i:s') . " - 作家クラウド更新開始\n";

try {
    $generator = new SakkaCloudGenerator();
    
    // データ生成
    $result = $generator->generate();
    if ($result['success']) {
        echo "b_author_stats_cache: " . $result['count'] . "件生成\n";
    } else {
        echo "エラー: " . $result['error'] . "\n";
    }
    
    // 簡易版も生成
    $simple_result = $generator->generateSimple();
    if ($simple_result['success']) {
        echo "b_sakka_cloud: " . $simple_result['count'] . "件生成\n";
    }
    
    // キャッシュをクリア
    $generator->clearCache();
    echo "キャッシュをクリアしました\n";
    
} catch (Exception $e) {
    echo "エラー: " . $e->getMessage() . "\n";
    exit(1);
}

echo date('Y-m-d H:i:s') . " - 作家クラウド更新完了\n";
exit(0);