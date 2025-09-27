<?php
/**
 * レビューembedding修正バッチ処理
 * review_embeddingsテーブルに存在しないレビューを処理
 */

declare(strict_types=1);

// CLIからの実行のみ許可
if (php_sapi_name() !== 'cli') {
    die("This script can only be run from the command line.\n");
}

require_once(__DIR__ . '/../config.php');
require_once(__DIR__ . '/../library/review_embedding_generator.php');

// 引数処理
$limit = isset($argv[1]) ? (int)$argv[1] : 100;

echo "=== Review Embedding Fix Batch ===\n";
echo "Processing limit: $limit\n\n";

// 未処理レビューを直接取得（review_embeddingsテーブルを経由しない）
$sql = "
    SELECT 
        bl.book_id,
        bl.user_id,
        bl.memo as review_text
    FROM b_book_list bl
    WHERE bl.memo IS NOT NULL 
        AND bl.memo != ''
        AND LENGTH(bl.memo) >= 10
        AND NOT EXISTS (
            SELECT 1 
            FROM review_embeddings re 
            WHERE re.book_id = bl.book_id 
                AND re.user_id = bl.user_id 
                AND re.review_embedding IS NOT NULL
        )
    ORDER BY bl.update_date DESC
    LIMIT ?
";

$reviews = $g_db->getAll($sql, [$limit]);

if (DB::isError($reviews)) {
    echo "Database error: " . $reviews->getMessage() . "\n";
    exit(1);
}

echo "Found " . count($reviews) . " unprocessed reviews\n\n";

if (count($reviews) === 0) {
    echo "No unprocessed reviews found.\n";
    exit(0);
}

$generator = new ReviewEmbeddingGenerator();
$success = 0;
$failed = 0;
$errors = [];

foreach ($reviews as $index => $review) {
    $bookId = (int)$review['book_id'];
    $userId = (int)$review['user_id'];
    
    echo sprintf(
        "[%d/%d] Processing book_id: %d, user_id: %d... ",
        $index + 1,
        count($reviews),
        $bookId,
        $userId
    );
    
    try {
        $embedding = $generator->generateReviewEmbedding(
            $bookId,
            $userId,
            $review['review_text']
        );
        
        if ($embedding !== null) {
            echo "SUCCESS\n";
            $success++;
        } else {
            echo "FAILED (no embedding returned)\n";
            $failed++;
            $errors[] = "Book $bookId, User $userId: No embedding returned";
        }
    } catch (Exception $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
        $failed++;
        $errors[] = "Book $bookId, User $userId: " . $e->getMessage();
    }
    
    // レート制限対策
    if (($index + 1) % 10 === 0) {
        echo "Pausing for rate limit...\n";
        sleep(1);
    }
}

echo "\n=== Summary ===\n";
echo "Total processed: " . count($reviews) . "\n";
echo "Success: $success\n";
echo "Failed: $failed\n";

if (count($errors) > 0) {
    echo "\n=== Errors ===\n";
    foreach (array_slice($errors, 0, 10) as $error) {
        echo "- $error\n";
    }
    if (count($errors) > 10) {
        echo "... and " . (count($errors) - 10) . " more errors\n";
    }
}

// 残件数を確認
$sql = "
    SELECT COUNT(*) as remaining
    FROM b_book_list bl
    WHERE bl.memo IS NOT NULL 
        AND bl.memo != ''
        AND LENGTH(bl.memo) >= 10
        AND NOT EXISTS (
            SELECT 1 
            FROM review_embeddings re 
            WHERE re.book_id = bl.book_id 
                AND re.user_id = bl.user_id 
                AND re.review_embedding IS NOT NULL
        )
";

$remaining = $g_db->getOne($sql);

if (!DB::isError($remaining)) {
    echo "\nRemaining unprocessed reviews: " . number_format($remaining) . "\n";
}

echo "\nDone!\n";
?>