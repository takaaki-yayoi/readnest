<?php
/**
 * 未使用ファイルのクリーンアップスクリプト
 * 
 * 使用されていないファイルを backup/cleanup_YYYYMMDD/ に移動します
 */

$dry_run = false; // false に設定して実際にファイルを移動

// バックアップディレクトリの作成
$backup_dir = __DIR__ . '/backup/cleanup_' . date('Ymd');

// 削除対象ファイル
$files_to_remove = [
    // バックアップファイル
    'template/modern/t_book_detail.php.bak',
    'library/x_api.php.bak',
    
    // 完了した移行・修正スクリプト
    'run_migrations.php',
    'add_indexes_safely.php',
    'database_health_check.php',
    'fix_user_dates_immediately.php',
    'fix_all_timestamp_issues.php',
    'force_fix_datetime.php',
    'update_date_fixer.php',
    'verify_date_format.php',
    'check_db_structure.php',
    
    // データ補完スクリプト
    'supplement_height.php',
    'supplement_rep_image.php',
    'supplement_image.php',
    
    // 使用されていない画像
    'img/noimage.0.jpg',
    'img/twitter-16x16.png',
    'img/s04b.gif',
    'img/title.gif',
    'img/calendar.png',
    'img/mobile-phone.png',
    'img/notice.jpg',
    'img/list_marker_white.gif',
    'img/page_white_edit.png',
    'img/24-book-green-add.png',
    'img/24-book-blue.png',
    'img/24-book-orange-remove.png',
    
    // ヘルプ画像（ドキュメントが移動した場合）
    'img/help/twitter_cooperate_1.jpg',
    'img/help/twitter_cooperate_2.jpg',
    'img/help/twitter_cooperate_3.jpg',
    'img/help/twitter_cooperate_4.jpg',
    'img/help/twitter_cooperate_5.jpg',
    'img/help/bookshelf1.jpg',
    'img/help/book_detail.jpg',
    'img/help/add_book1.jpg',
    'img/help/add_book2.jpg',
    'img/help/new_pager.jpg',
    'img/help/memory.jpg',
    
    // テスト用管理ファイル
    'admin/test_x_api_direct.php',
    'admin/test_x_api.php',
    'admin/test_x_share.php',
    'admin/verify_x_tokens.php',
    'admin/x_api_access_guide.php',
    'admin/debug_popular_books.php',
    
    // 使用されていないライブラリ
    'library/x_oauth.php',
    'library/session_mobile.php',
    
    // 使用されていない機能
    'similar_book.php',
    'best_books.php',
    'diary.php',
    'disclosed_diary.php',
    'toc.php',
    'stat.php',
    'mod_evaluate.php',
    'ga.php',
];

// 調査が必要なファイル（コメントアウトして保留）
$files_to_investigate = [
    // モダンテーマファイル（使用中の可能性）
    // 'bookshelf_modern.php',
    // 'add_book_modern.php',
    // 'ranking_modern.php',
    // 'index_modern.php',
    
    // データベースバリアント（どれを使用しているか確認が必要）
    // 'library/database_fixed.php',
    // 'library/database_optimized.php',
    // 'library/database_optimized_v2.php',
    // 'library/database_pdo.php',
    
    // セキュリティライブラリ（新規作成したが使用されていない可能性）
    // 'library/csrf_protection.php',
    // 'library/security_headers.php',
];

echo "未使用ファイルのクリーンアップ\n";
echo "==============================\n\n";

if ($dry_run) {
    echo "【ドライランモード】実際のファイル移動は行いません\n\n";
} else {
    // バックアップディレクトリの作成
    if (!file_exists($backup_dir)) {
        mkdir($backup_dir, 0755, true);
        echo "バックアップディレクトリを作成: $backup_dir\n\n";
    }
}

$moved_count = 0;
$not_found_count = 0;
$total_size = 0;

foreach ($files_to_remove as $file) {
    $full_path = __DIR__ . '/' . $file;
    
    if (file_exists($full_path)) {
        $size = filesize($full_path);
        $total_size += $size;
        $size_kb = round($size / 1024, 2);
        
        echo "✓ $file ({$size_kb} KB)\n";
        
        if (!$dry_run) {
            $backup_path = $backup_dir . '/' . $file;
            $backup_file_dir = dirname($backup_path);
            
            if (!file_exists($backup_file_dir)) {
                mkdir($backup_file_dir, 0755, true);
            }
            
            if (rename($full_path, $backup_path)) {
                echo "  → バックアップに移動しました\n";
                $moved_count++;
            } else {
                echo "  → 移動に失敗しました\n";
            }
        }
    } else {
        echo "✗ $file (ファイルが見つかりません)\n";
        $not_found_count++;
    }
}

echo "\n==============================\n";
echo "対象ファイル数: " . count($files_to_remove) . "\n";
echo "存在するファイル: " . ($moved_count + ($dry_run ? count($files_to_remove) - $not_found_count : 0)) . "\n";
echo "見つからないファイル: $not_found_count\n";
echo "合計サイズ: " . round($total_size / 1024 / 1024, 2) . " MB\n";

if ($dry_run) {
    echo "\n実際にファイルを移動するには、\$dry_run = false; に変更してください。\n";
} else {
    echo "\nファイルは $backup_dir に移動されました。\n";
    echo "問題がなければ、後でこのディレクトリを削除できます。\n";
}

echo "\n【調査が必要なファイル】\n";
echo "以下のファイルは使用状況の確認が必要です：\n";
foreach ($files_to_investigate as $file) {
    if (file_exists(__DIR__ . '/' . $file)) {
        echo "? $file\n";
    }
}