<?php
/**
 * Embedding列の型を修正してデータを再生成するスクリプト
 */

// CLIチェック
if (php_sapi_name() !== 'cli') {
    die("This script must be run from command line\n");
}

// データベース設定
define('DB_HOST', 'localhost');
define('DB_NAME', 'icotfeels_book');
define('DB_USER', 'icotfeels_book');
define('DB_PASS', 'dokushonoteigi');

echo "Embedding Column Fix Script\n";
echo str_repeat("=", 60) . "\n\n";

try {
    // データベース接続
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8";
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Database connected\n\n";
    
    // 1. 現在の状況を確認
    echo "1. 現在の状況を確認...\n";
    $check_sql = "
        SELECT 
            COUNT(*) as total_embeddings,
            COUNT(CASE WHEN RIGHT(combined_embedding, 1) != ']' THEN 1 END) as truncated_combined,
            COUNT(CASE WHEN RIGHT(title_embedding, 1) != ']' THEN 1 END) as truncated_title,
            COUNT(CASE WHEN RIGHT(description_embedding, 1) != ']' THEN 1 END) as truncated_description
        FROM b_book_repository
        WHERE combined_embedding IS NOT NULL 
           OR title_embedding IS NOT NULL 
           OR description_embedding IS NOT NULL
    ";
    
    $stmt = $pdo->query($check_sql);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "  Total embeddings: " . $result['total_embeddings'] . "\n";
    echo "  Truncated combined: " . $result['truncated_combined'] . "\n";
    echo "  Truncated title: " . $result['truncated_title'] . "\n";
    echo "  Truncated description: " . $result['truncated_description'] . "\n\n";
    
    if ($result['truncated_combined'] > 0 || $result['truncated_title'] > 0 || $result['truncated_description'] > 0) {
        echo "⚠️ 切り捨てられたembeddingが見つかりました\n\n";
        
        // 2. カラム型を変更
        echo "2. カラム型をMEDIUMTEXTに変更...\n";
        
        try {
            $pdo->exec("ALTER TABLE b_book_repository MODIFY COLUMN combined_embedding MEDIUMTEXT");
            echo "  ✓ combined_embedding changed to MEDIUMTEXT\n";
        } catch (Exception $e) {
            echo "  ⚠ combined_embedding: " . $e->getMessage() . "\n";
        }
        
        try {
            $pdo->exec("ALTER TABLE b_book_repository MODIFY COLUMN title_embedding MEDIUMTEXT");
            echo "  ✓ title_embedding changed to MEDIUMTEXT\n";
        } catch (Exception $e) {
            echo "  ⚠ title_embedding: " . $e->getMessage() . "\n";
        }
        
        try {
            $pdo->exec("ALTER TABLE b_book_repository MODIFY COLUMN description_embedding MEDIUMTEXT");
            echo "  ✓ description_embedding changed to MEDIUMTEXT\n";
        } catch (Exception $e) {
            echo "  ⚠ description_embedding: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
        
        // 3. 切り捨てられたデータをクリア
        echo "3. 切り捨てられたデータをクリア...\n";
        
        $clear_combined = $pdo->exec("
            UPDATE b_book_repository 
            SET combined_embedding = NULL 
            WHERE combined_embedding IS NOT NULL 
              AND RIGHT(combined_embedding, 1) != ']'
        ");
        echo "  Cleared $clear_combined truncated combined embeddings\n";
        
        $clear_title = $pdo->exec("
            UPDATE b_book_repository 
            SET title_embedding = NULL 
            WHERE title_embedding IS NOT NULL 
              AND RIGHT(title_embedding, 1) != ']'
        ");
        echo "  Cleared $clear_title truncated title embeddings\n";
        
        $clear_desc = $pdo->exec("
            UPDATE b_book_repository 
            SET description_embedding = NULL 
            WHERE description_embedding IS NOT NULL 
              AND RIGHT(description_embedding, 1) != ']'
        ");
        echo "  Cleared $clear_desc truncated description embeddings\n\n";
        
        // 4. 結果を確認
        echo "4. クリア後の統計...\n";
        $final_sql = "
            SELECT 
                COUNT(CASE WHEN combined_embedding IS NOT NULL THEN 1 END) as valid_combined,
                COUNT(CASE WHEN title_embedding IS NOT NULL THEN 1 END) as valid_title,
                COUNT(CASE WHEN description_embedding IS NOT NULL THEN 1 END) as valid_description
            FROM b_book_repository
        ";
        
        $stmt = $pdo->query($final_sql);
        $final = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "  Valid combined embeddings: " . $final['valid_combined'] . "\n";
        echo "  Valid title embeddings: " . $final['valid_title'] . "\n";
        echo "  Valid description embeddings: " . $final['valid_description'] . "\n\n";
        
        echo str_repeat("=", 60) . "\n";
        echo "✓ 修正完了！\n\n";
        echo "次のステップ:\n";
        echo "1. generate_enhanced_embeddings_cli.php を実行して、クリアされたembeddingを再生成してください\n";
        echo "   php generate_enhanced_embeddings_cli.php all 100\n\n";
        
    } else {
        echo "✓ 切り捨てられたembeddingはありません\n";
        
        // カラム型だけ念のため確認・変更
        echo "\n2. カラム型を念のため確認...\n";
        $describe_sql = "DESCRIBE b_book_repository";
        $stmt = $pdo->query($describe_sql);
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($columns as $col) {
            if (in_array($col['Field'], ['combined_embedding', 'title_embedding', 'description_embedding'])) {
                echo "  " . $col['Field'] . ": " . $col['Type'] . "\n";
                
                if (strtolower($col['Type']) == 'text') {
                    echo "    → MEDIUMTEXTに変更します\n";
                    try {
                        $pdo->exec("ALTER TABLE b_book_repository MODIFY COLUMN {$col['Field']} MEDIUMTEXT");
                        echo "    ✓ 変更完了\n";
                    } catch (Exception $e) {
                        echo "    ⚠ " . $e->getMessage() . "\n";
                    }
                }
            }
        }
    }
    
} catch (Exception $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>