<?php
/**
 * レビューembedding生成バッチ処理
 * 既存のレビューに対してembeddingを生成
 * 
 * 使用方法:
 * php batch/generate_review_embeddings.php [options]
 * 
 * オプション:
 * --limit=100  : 1回の実行で処理するレビュー数（デフォルト: 100）
 * --dry-run    : 実際の処理は行わず、対象レビューの確認のみ
 * --force      : エラーで停止したレビューも再処理
 */

declare(strict_types=1);

// CLIからの実行のみ許可
if (php_sapi_name() !== 'cli') {
    die("This script can only be run from the command line.\n");
}

require_once(__DIR__ . '/../config.php');
require_once(__DIR__ . '/../library/review_embedding_generator.php');

class ReviewEmbeddingBatch {
    private $generator;
    private $db;
    private $options = [];
    private $batchId;
    
    public function __construct() {
        global $g_db;
        $this->db = $g_db;
        $this->generator = new ReviewEmbeddingGenerator();
        $this->batchId = 'batch_' . date('YmdHis') . '_' . uniqid();
        
        // コマンドラインオプションを解析
        $this->parseOptions();
    }
    
    /**
     * バッチ実行
     */
    public function run(): void {
        $this->printHeader();
        
        if ($this->options['dry-run']) {
            $this->dryRun();
            return;
        }
        
        $this->executeBatch();
    }
    
    /**
     * ドライラン実行
     */
    private function dryRun(): void {
        echo "=== DRY RUN MODE ===\n\n";
        
        // 対象レビューを取得
        $sql = $this->buildTargetQuery();
        $result = $this->db->getAll($sql, [$this->options['limit']]);
        
        // DB_Errorチェック
        if (DB::isError($result)) {
            echo "Database error: " . $result->getMessage() . "\n";
            echo "SQL: " . $sql . "\n";
            return;
        }
        
        $reviews = $result;
        
        echo "Target reviews: " . count($reviews) . "\n";
        echo str_repeat("-", 80) . "\n";
        echo sprintf("%-10s %-10s %-50s %s\n", "BookID", "UserID", "Review (excerpt)", "Length");
        echo str_repeat("-", 80) . "\n";
        
        foreach ($reviews as $review) {
            $excerpt = mb_substr($review['review_text'], 0, 45) . '...';
            echo sprintf(
                "%-10d %-10d %-50s %d chars\n",
                $review['book_id'],
                $review['user_id'],
                $excerpt,
                mb_strlen($review['review_text'])
            );
        }
        
        echo str_repeat("-", 80) . "\n";
        
        // 統計情報
        $this->printStatistics();
    }
    
    /**
     * バッチ処理実行
     */
    private function executeBatch(): void {
        echo "Starting batch processing...\n";
        echo "Batch ID: {$this->batchId}\n\n";
        
        $startTime = microtime(true);
        
        // バッチ処理を実行
        $results = $this->generator->generateBatch(
            $this->options['limit'],
            $this->batchId
        );
        
        $executionTime = microtime(true) - $startTime;
        
        // 結果を表示
        echo "\n" . str_repeat("=", 80) . "\n";
        echo "BATCH PROCESSING COMPLETED\n";
        echo str_repeat("=", 80) . "\n";
        echo "Batch ID: {$this->batchId}\n";
        echo "Total Reviews: {$results['total']}\n";
        echo "Successful: {$results['success']}\n";
        echo "Failed: {$results['failed']}\n";
        echo "Skipped: {$results['skipped']}\n";
        echo "Execution Time: " . number_format($executionTime, 2) . " seconds\n";
        
        if ($results['total'] > 0) {
            $successRate = ($results['success'] / $results['total']) * 100;
            echo "Success Rate: " . number_format($successRate, 1) . "%\n";
        }
        
        // 詳細ログ
        if ($results['failed'] > 0) {
            echo "\nFailed reviews:\n";
            $this->showFailedReviews();
        }
        
        echo "\n";
        $this->printStatistics();
    }
    
    /**
     * 対象レビュー取得クエリを構築
     */
    private function buildTargetQuery(): string {
        $sql = "
            SELECT DISTINCT bl.book_id, bl.user_id, bl.memo as review_text, br.title
            FROM b_book_list bl
            LEFT JOIN b_book_repository br ON bl.amazon_id = br.asin
            LEFT JOIN review_embeddings re ON bl.book_id = re.book_id AND bl.user_id = re.user_id
            WHERE bl.memo IS NOT NULL 
              AND bl.memo != ''
              AND LENGTH(bl.memo) >= 10
        ";
        
        if (!$this->options['force']) {
            // 強制モードでない場合は、未処理または更新が必要なもののみ
            $sql .= " AND (re.review_embedding IS NULL OR re.updated_at < bl.update_date)";
        } else {
            // 強制モードの場合は、エラーになったものも含める
            $sql .= " AND (re.review_embedding IS NULL OR re.updated_at < bl.update_date OR re.last_error_message IS NOT NULL)";
        }
        
        $sql .= " ORDER BY bl.update_date DESC LIMIT ?";
        
        return $sql;
    }
    
    /**
     * 失敗したレビューを表示
     */
    private function showFailedReviews(): void {
        $sql = "
            SELECT rbl.book_id, rbl.user_id, br.title, re.last_error_message
            FROM review_embedding_batch_log rbl
            LEFT JOIN b_book_list bl ON rbl.book_id = bl.book_id AND rbl.user_id = bl.user_id
            LEFT JOIN b_book_repository br ON bl.amazon_id = br.asin
            LEFT JOIN review_embeddings re ON rbl.book_id = re.book_id AND rbl.user_id = re.user_id
            WHERE rbl.batch_id = ? AND rbl.status = 'failed'
            LIMIT 10
        ";
        
        $result = $this->db->getAll($sql, [$this->batchId]);
        
        if (DB::isError($result)) {
            echo "  Failed to retrieve error details: " . $result->getMessage() . "\n";
            return;
        }
        
        $failed = $result;
        
        foreach ($failed as $item) {
            echo sprintf(
                "  Book #%d (User #%d): %s - Error: %s\n",
                $item['book_id'],
                $item['user_id'],
                mb_substr($item['title'], 0, 30),
                mb_substr($item['last_error_message'], 0, 50)
            );
        }
    }
    
    /**
     * 統計情報を表示
     */
    private function printStatistics(): void {
        // 全体の統計
        $sql = "
            SELECT 
                COUNT(DISTINCT bl.book_id, bl.user_id) as total_reviews,
                COUNT(DISTINCT CASE WHEN re.review_embedding IS NOT NULL THEN CONCAT(bl.book_id, '-', bl.user_id) END) as with_embedding,
                COUNT(DISTINCT CASE WHEN re.review_embedding IS NULL AND bl.memo IS NOT NULL THEN CONCAT(bl.book_id, '-', bl.user_id) END) as need_embedding
            FROM b_book_list bl
            LEFT JOIN review_embeddings re ON bl.book_id = re.book_id AND bl.user_id = re.user_id
            WHERE bl.memo IS NOT NULL AND bl.memo != ''
        ";
        
        $result = $this->db->getRow($sql);
        
        if (DB::isError($result)) {
            echo "\n=== OVERALL STATISTICS ===\n";
            echo "Error retrieving statistics: " . $result->getMessage() . "\n";
            return;
        }
        
        $stats = $result;
        
        echo "\n=== OVERALL STATISTICS ===\n";
        echo "Total Reviews: " . number_format((float)$stats['total_reviews']) . "\n";
        echo "With Embeddings: " . number_format((float)$stats['with_embedding']) . "\n";
        echo "Need Embeddings: " . number_format((float)$stats['need_embedding']) . "\n";
        
        if ((int)$stats['total_reviews'] > 0) {
            $coverage = ((float)$stats['with_embedding'] / (float)$stats['total_reviews']) * 100;
            echo "Coverage: " . number_format($coverage, 2) . "%\n";
        }
        
        // 最近の処理状況
        $sql = "
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as processed,
                SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as success
            FROM review_embedding_batch_log
            WHERE created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY DATE(created_at)
            ORDER BY date DESC
            LIMIT 7
        ";
        
        $result = $this->db->getAll($sql);
        
        if (DB::isError($result)) {
            // エラーの場合はスキップ（統計表示は必須ではない）
            return;
        }
        
        $recent = $result;
        
        if (count($recent) > 0) {
            echo "\n=== RECENT ACTIVITY (Last 7 days) ===\n";
            foreach ($recent as $day) {
                echo sprintf(
                    "%s: %d processed, %d success\n",
                    $day['date'],
                    (int)$day['processed'],
                    (int)$day['success']
                );
            }
        }
    }
    
    /**
     * ヘッダー表示
     */
    private function printHeader(): void {
        echo str_repeat("=", 80) . "\n";
        echo "REVIEW EMBEDDING GENERATOR BATCH\n";
        echo str_repeat("=", 80) . "\n";
        echo "Date: " . date('Y-m-d H:i:s') . "\n";
        echo "Options:\n";
        echo "  Limit: {$this->options['limit']}\n";
        echo "  Dry Run: " . ($this->options['dry-run'] ? 'Yes' : 'No') . "\n";
        echo "  Force: " . ($this->options['force'] ? 'Yes' : 'No') . "\n";
        echo str_repeat("-", 80) . "\n\n";
    }
    
    /**
     * コマンドラインオプションを解析
     */
    private function parseOptions(): void {
        global $argv;
        
        // デフォルト値
        $this->options = [
            'limit' => 100,
            'dry-run' => false,
            'force' => false
        ];
        
        foreach ($argv as $arg) {
            if (strpos($arg, '--limit=') === 0) {
                $this->options['limit'] = (int)substr($arg, 8);
            } elseif ($arg === '--dry-run') {
                $this->options['dry-run'] = true;
            } elseif ($arg === '--force') {
                $this->options['force'] = true;
            }
        }
        
        // 制限値の検証
        if ($this->options['limit'] < 1) {
            $this->options['limit'] = 1;
        } elseif ($this->options['limit'] > 1000) {
            $this->options['limit'] = 1000;
        }
    }
}

// メイン処理
try {
    $batch = new ReviewEmbeddingBatch();
    $batch->run();
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
?>