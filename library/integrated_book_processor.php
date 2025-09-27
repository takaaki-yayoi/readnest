<?php
/**
 * 統合書籍処理クラス
 * 説明文取得とエンベディング生成を一括で処理
 */

declare(strict_types=1);

require_once(dirname(__FILE__) . '/database.php');
require_once(dirname(__FILE__) . '/book_description_updater.php');
require_once(dirname(__FILE__) . '/embedding_generator.php');

class IntegratedBookProcessor {
    private $db;
    private $descriptionUpdater;
    private $embeddingGenerator;
    private $hasEmbeddingSupport = false;
    
    public function __construct() {
        global $g_db;
        $this->db = $g_db;
        $this->descriptionUpdater = new BookDescriptionUpdater();
        
        // OpenAI APIキーが設定されている場合のみエンベディング生成を有効化
        if (defined('OPENAI_API_KEY') && !empty(OPENAI_API_KEY)) {
            try {
                $this->embeddingGenerator = new EmbeddingGenerator();
                $this->hasEmbeddingSupport = true;
            } catch (Exception $e) {
                error_log('Embedding generator initialization failed: ' . $e->getMessage());
                $this->hasEmbeddingSupport = false;
            }
        }
    }
    
    /**
     * 単一の本を処理（説明文取得 + エンベディング生成）
     */
    public function processBook(string $asin): array {
        $result = [
            'asin' => $asin,
            'description_fetched' => false,
            'description_status' => '',
            'embedding_generated' => false,
            'embedding_status' => '',
            'success' => false
        ];
        
        // 1. 現在の状態を確認
        $bookInfo = $this->getBookStatus($asin);
        if (!$bookInfo) {
            $result['description_status'] = 'Book not found';
            return $result;
        }
        
        // 2. 説明文の取得（必要な場合）
        if (empty($bookInfo['description'])) {
            error_log("Fetching description for ASIN: $asin");
            $descSuccess = $this->descriptionUpdater->updateBookDescription($asin);
            
            if ($descSuccess) {
                $result['description_fetched'] = true;
                $result['description_status'] = 'Successfully fetched';
                
                // 更新後の情報を再取得
                $bookInfo = $this->getBookStatus($asin);
            } else {
                $result['description_status'] = 'Failed to fetch description';
                // 説明文がない場合はエンベディング生成もスキップ
                return $result;
            }
        } else {
            $result['description_status'] = 'Already exists';
        }
        
        // 3. エンベディングの生成（説明文があり、APIキーが設定されている場合）
        if ($this->hasEmbeddingSupport && !empty($bookInfo['description'])) {
            // エンベディングが未生成の場合のみ
            if (empty($bookInfo['combined_embedding'])) {
                error_log("Generating embedding for ASIN: $asin");
                $embSuccess = $this->embeddingGenerator->generateBookEmbedding($asin);
                
                if ($embSuccess) {
                    $result['embedding_generated'] = true;
                    $result['embedding_status'] = 'Successfully generated';
                } else {
                    $result['embedding_status'] = 'Failed to generate embedding';
                }
            } else {
                $result['embedding_status'] = 'Already exists';
            }
        } else if (!$this->hasEmbeddingSupport) {
            $result['embedding_status'] = 'OpenAI API not configured';
        } else {
            $result['embedding_status'] = 'No description available';
        }
        
        // 全体の成功判定
        $result['success'] = ($result['description_fetched'] || $result['description_status'] === 'Already exists') &&
                            ($result['embedding_generated'] || $result['embedding_status'] === 'Already exists' || !$this->hasEmbeddingSupport);
        
        return $result;
    }
    
    /**
     * 複数の本を一括処理
     */
    public function processBatch(int $limit = 10): array {
        $results = [
            'total' => 0,
            'success' => 0,
            'failed' => 0,
            'description_fetched' => 0,
            'embedding_generated' => 0,
            'processed' => []
        ];
        
        // 処理対象の本を取得（説明文またはエンベディングがない本）
        $books = $this->getBooksToProcess($limit);
        $results['total'] = count($books);
        
        foreach ($books as $book) {
            $processResult = $this->processBook($book['asin']);
            
            $results['processed'][] = [
                'asin' => $book['asin'],
                'title' => $book['title'],
                'description_status' => $processResult['description_status'],
                'embedding_status' => $processResult['embedding_status'],
                'success' => $processResult['success']
            ];
            
            if ($processResult['success']) {
                $results['success']++;
            } else {
                $results['failed']++;
            }
            
            if ($processResult['description_fetched']) {
                $results['description_fetched']++;
            }
            
            if ($processResult['embedding_generated']) {
                $results['embedding_generated']++;
            }
            
            // API制限対策
            usleep(500000); // 0.5秒待機
        }
        
        return $results;
    }
    
    /**
     * 処理対象の本を取得
     */
    private function getBooksToProcess(int $limit): array {
        // 優先順位：
        // 1. 説明文がない本
        // 2. 説明文はあるがエンベディングがない本
        
        $sql = "
            SELECT 
                br.asin,
                br.title,
                br.author,
                br.description,
                br.combined_embedding,
                CASE 
                    WHEN br.description IS NULL OR br.description = '' THEN 1
                    WHEN br.combined_embedding IS NULL THEN 2
                    ELSE 3
                END as priority
            FROM b_book_repository br
            WHERE (
                (br.description IS NULL OR br.description = '') OR
                (br.combined_embedding IS NULL AND br.description IS NOT NULL AND br.description != '')
            )
            ORDER BY priority, br.title
            LIMIT ?
        ";
        
        $books = $this->db->getAll($sql, [$limit], DB_FETCHMODE_ASSOC);
        
        if (DB::isError($books)) {
            error_log("Error fetching books to process: " . $books->getMessage());
            return [];
        }
        
        return $books;
    }
    
    /**
     * 本の現在の状態を取得
     */
    private function getBookStatus(string $asin): ?array {
        $sql = "
            SELECT 
                asin,
                title,
                author,
                description,
                combined_embedding,
                google_data_updated_at,
                embedding_generated_at
            FROM b_book_repository
            WHERE asin = ?
        ";
        
        $book = $this->db->getRow($sql, [$asin], DB_FETCHMODE_ASSOC);
        
        if (DB::isError($book) || empty($book)) {
            return null;
        }
        
        return $book;
    }
    
    /**
     * 統計情報を取得
     */
    public function getStatistics(): array {
        $sql = "
            SELECT 
                COUNT(*) as total_books,
                SUM(CASE WHEN description IS NOT NULL AND description != '' THEN 1 ELSE 0 END) as books_with_description,
                SUM(CASE WHEN combined_embedding IS NOT NULL THEN 1 ELSE 0 END) as books_with_embedding,
                SUM(CASE WHEN description IS NOT NULL AND combined_embedding IS NOT NULL THEN 1 ELSE 0 END) as fully_processed,
                SUM(CASE WHEN description IS NULL OR description = '' THEN 1 ELSE 0 END) as needs_description,
                SUM(CASE WHEN description IS NOT NULL AND combined_embedding IS NULL THEN 1 ELSE 0 END) as needs_embedding
            FROM b_book_repository
        ";
        
        $stats = $this->db->getRow($sql, [], DB_FETCHMODE_ASSOC);
        
        if (DB::isError($stats)) {
            return [
                'total_books' => 0,
                'books_with_description' => 0,
                'books_with_embedding' => 0,
                'fully_processed' => 0,
                'needs_description' => 0,
                'needs_embedding' => 0,
                'description_coverage' => 0,
                'embedding_coverage' => 0,
                'full_coverage' => 0
            ];
        }
        
        $total = $stats['total_books'] ?? 0;
        
        return [
            'total_books' => $total,
            'books_with_description' => $stats['books_with_description'] ?? 0,
            'books_with_embedding' => $stats['books_with_embedding'] ?? 0,
            'fully_processed' => $stats['fully_processed'] ?? 0,
            'needs_description' => $stats['needs_description'] ?? 0,
            'needs_embedding' => $stats['needs_embedding'] ?? 0,
            'description_coverage' => $total > 0 ? round(($stats['books_with_description'] / $total) * 100, 2) : 0,
            'embedding_coverage' => $total > 0 ? round(($stats['books_with_embedding'] / $total) * 100, 2) : 0,
            'full_coverage' => $total > 0 ? round(($stats['fully_processed'] / $total) * 100, 2) : 0
        ];
    }
    
    /**
     * 推薦用の本を優先的に処理
     * ユーザーの高評価本とその関連本を優先
     */
    public function processForUser(int $userId, int $limit = 20): array {
        // ユーザーの高評価本のASINを取得
        $sql = "
            SELECT br.asin
            FROM b_book_list bl
            INNER JOIN b_book_repository br ON bl.amazon_id = br.asin
            WHERE bl.user_id = ?
            AND bl.rating >= 4
            AND (br.description IS NULL OR br.combined_embedding IS NULL)
            ORDER BY bl.rating DESC, bl.update_date DESC
            LIMIT ?
        ";
        
        $books = $this->db->getAll($sql, [$userId, $limit], DB_FETCHMODE_ASSOC);
        
        if (DB::isError($books)) {
            return [
                'total' => 0,
                'success' => 0,
                'failed' => 0,
                'processed' => []
            ];
        }
        
        $results = [
            'total' => count($books),
            'success' => 0,
            'failed' => 0,
            'processed' => []
        ];
        
        foreach ($books as $book) {
            $processResult = $this->processBook($book['asin']);
            
            if ($processResult['success']) {
                $results['success']++;
            } else {
                $results['failed']++;
            }
            
            $results['processed'][] = $processResult;
            
            // API制限対策
            usleep(500000); // 0.5秒待機
        }
        
        return $results;
    }
}
?>