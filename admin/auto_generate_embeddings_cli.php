<?php
/**
 * 自動バッチエンベディング生成 - CLIバージョン
 * コマンドラインから実行するためのスクリプト
 */

// CLIから実行されているかチェック
if (php_sapi_name() !== 'cli') {
    die("This script can only be run from the command line.\n");
}

require_once(dirname(__DIR__) . '/config.php');
require_once(dirname(__DIR__) . '/library/database.php');
require_once(dirname(__DIR__) . '/library/title_embedding_generator.php');

// コマンドライン引数を解析
$options = getopt("b:t:h", ["batch-size:", "batch-type:", "help"]);

// ヘルプ表示
if (isset($options['h']) || isset($options['help'])) {
    echo "Usage: php auto_generate_embeddings_cli.php [options]\n";
    echo "\nOptions:\n";
    echo "  -b, --batch-size=SIZE   バッチサイズ (default: 50)\n";
    echo "  -t, --batch-type=TYPE   処理タイプ: 'all' または 'popular' (default: all)\n";
    echo "  -h, --help              このヘルプを表示\n";
    echo "\nExample:\n";
    echo "  php auto_generate_embeddings_cli.php --batch-size=100 --batch-type=all\n";
    echo "  php auto_generate_embeddings_cli.php -b 25 -t popular\n";
    exit(0);
}

// パラメータ設定
$batch_size = isset($options['b']) ? intval($options['b']) : 
              (isset($options['batch-size']) ? intval($options['batch-size']) : 50);
              
$batch_type = isset($options['t']) ? $options['t'] : 
              (isset($options['batch-type']) ? $options['batch-type'] : 'all');

// バッチタイプの検証
if (!in_array($batch_type, ['all', 'popular'])) {
    echo "Error: Invalid batch type. Use 'all' or 'popular'.\n";
    exit(1);
}

// バッチサイズの検証
if ($batch_size < 1 || $batch_size > 500) {
    echo "Error: Batch size must be between 1 and 500.\n";
    exit(1);
}

// 実行開始
echo "========================================\n";
echo " Embedding Generator CLI\n";
echo "========================================\n";
echo "Batch Size: $batch_size\n";
echo "Batch Type: $batch_type\n";
echo "========================================\n\n";

try {
    // ジェネレーターを初期化
    $generator = new TitleEmbeddingGenerator();
    
    // 統計情報を取得
    $stats = $generator->getStatistics();
    echo "Current Statistics:\n";
    echo "  Total Books: " . number_format($stats['total_books']) . "\n";
    echo "  With Embedding: " . number_format($stats['with_embedding']) . "\n";
    echo "  Need Embedding: " . number_format($stats['need_embedding']) . "\n";
    echo "  Coverage: " . $stats['coverage'] . "%\n";
    echo "\n";
    
    if ($stats['need_embedding'] == 0) {
        echo "All books already have embeddings. Nothing to process.\n";
        exit(0);
    }
    
    // 処理開始時刻
    $start_time = time();
    $total_processed = 0;
    $total_success = 0;
    $total_failed = 0;
    $batch_count = 0;
    
    // 未処理がある限り繰り返し
    while (true) {
        $batch_count++;
        echo "Batch #$batch_count: Processing $batch_size books...\n";
        
        // バッチ処理を実行
        if ($batch_type === 'popular') {
            $result = $generator->generatePopularBooks($batch_size);
        } else {
            $offset = $total_processed;
            $result = $generator->generateBatch($batch_size, $offset);
        }
        
        // 結果がない場合は終了
        if (!$result || $result['total'] == 0) {
            echo "No more books to process.\n";
            break;
        }
        
        // 統計を更新
        $total_processed += $result['total'];
        $total_success += $result['success'];
        $total_failed += $result['failed'];
        
        // 進捗を表示
        echo "  Processed: {$result['total']}\n";
        echo "  Success: {$result['success']}\n";
        echo "  Failed: {$result['failed']}\n";
        
        // 処理済みの本の詳細（最初の5件のみ）
        if (!empty($result['books'])) {
            echo "  Books:\n";
            $display_count = min(5, count($result['books']));
            for ($i = 0; $i < $display_count; $i++) {
                $book = $result['books'][$i];
                $status = $book['success'] ? '✓' : '✗';
                echo "    [$status] {$book['title']}\n";
                if (!$book['success'] && !empty($book['error'])) {
                    echo "        Error: {$book['error']}\n";
                }
            }
            if (count($result['books']) > 5) {
                echo "    ... and " . (count($result['books']) - 5) . " more\n";
            }
        }
        
        // 経過時間と速度を計算
        $elapsed = time() - $start_time;
        $rate = $total_processed > 0 ? round($total_processed / max(1, $elapsed) * 60, 1) : 0;
        
        echo "\n";
        echo "Current Progress:\n";
        echo "  Total Processed: $total_processed\n";
        echo "  Total Success: $total_success\n";
        echo "  Total Failed: $total_failed\n";
        echo "  Elapsed Time: " . gmdate("H:i:s", $elapsed) . "\n";
        echo "  Processing Rate: $rate books/minute\n";
        
        // 残り時間の推定
        $stats = $generator->getStatistics();
        if ($stats['need_embedding'] > 0 && $rate > 0) {
            $estimated_remaining = ceil($stats['need_embedding'] / $rate / 60);
            echo "  Estimated Time Remaining: ~$estimated_remaining hours\n";
        }
        
        echo "  Remaining: " . number_format($stats['need_embedding']) . " books\n";
        echo "\n";
        
        // 全て処理完了したら終了
        if ($stats['need_embedding'] == 0) {
            echo "All books have been processed!\n";
            break;
        }
        
        // 少し待機（API制限対策）
        sleep(1);
    }
    
    // 最終統計を表示
    echo "========================================\n";
    echo " Processing Complete\n";
    echo "========================================\n";
    $final_elapsed = time() - $start_time;
    echo "Total Processed: $total_processed\n";
    echo "Total Success: $total_success\n";
    echo "Total Failed: $total_failed\n";
    if ($total_processed > 0) {
        $success_rate = round(($total_success / $total_processed) * 100, 1);
        echo "Success Rate: $success_rate%\n";
    }
    echo "Total Time: " . gmdate("H:i:s", $final_elapsed) . "\n";
    
    // 最新の統計を表示
    $final_stats = $generator->getStatistics();
    echo "\nFinal Statistics:\n";
    echo "  Total Books: " . number_format($final_stats['total_books']) . "\n";
    echo "  With Embedding: " . number_format($final_stats['with_embedding']) . "\n";
    echo "  Coverage: " . $final_stats['coverage'] . "%\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\nDone!\n";
exit(0);