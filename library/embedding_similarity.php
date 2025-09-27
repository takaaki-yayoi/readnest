<?php
/**
 * エンベディングベースの類似本検索
 * 内容の類似度に基づいた高精度な推薦
 */

declare(strict_types=1);

require_once(dirname(__FILE__) . '/database.php');
require_once(dirname(__FILE__) . '/embedding_generator.php');

class EmbeddingSimilarity {
    private $db;
    private $userId;
    private $generator;
    
    public function __construct(int $userId) {
        global $g_db;
        $this->db = $g_db;
        $this->userId = $userId;
        
        // OpenAI APIキーが設定されている場合のみ初期化
        if (defined('OPENAI_API_KEY') && !empty(OPENAI_API_KEY)) {
            $this->generator = new EmbeddingGenerator();
        }
    }
    
    /**
     * エンベディングベースで類似本を検索
     */
    public function findSimilarBooks(int $limit = 20): array {
        // 1. ユーザーの高評価本を取得
        $baseBooks = $this->getUserHighRatedBooksWithEmbedding();
        
        if (empty($baseBooks)) {
            error_log("No base books with embeddings found for user: " . $this->userId);
            return [];
        }
        
        // 2. 既読本を除外するためのリストを取得
        $readBooks = $this->getUserReadBooks();
        
        // 3. 各ベース本に対して類似本を検索
        $allSimilar = [];
        $seenAsins = array_merge($readBooks, array_column($baseBooks, 'asin'));
        
        // ベース本をシャッフルして多様性を確保
        shuffle($baseBooks);
        $selectedBaseBooks = array_slice($baseBooks, 0, 5); // 最大5冊のベース本
        
        foreach ($selectedBaseBooks as $baseBook) {
            $similarBooks = $this->findSimilarByEmbedding(
                $baseBook,
                $limit,
                $seenAsins
            );
            
            foreach ($similarBooks as $book) {
                if (!in_array($book['asin'], $seenAsins)) {
                    $book['base_book'] = $baseBook['title'];
                    $book['base_author'] = $baseBook['author'];
                    $allSimilar[] = $book;
                    $seenAsins[] = $book['asin'];
                }
            }
            
            // 十分な数が集まったら終了
            if (count($allSimilar) >= $limit) {
                break;
            }
        }
        
        // 4. スコアでソートして上位を返す
        usort($allSimilar, function($a, $b) {
            return ($b['similarity_score'] ?? 0) <=> ($a['similarity_score'] ?? 0);
        });
        
        return array_slice($allSimilar, 0, $limit);
    }
    
    /**
     * ユーザーの高評価本（エンベディング付き）を取得
     */
    private function getUserHighRatedBooksWithEmbedding(): array {
        $sql = "
            SELECT 
                br.asin,
                br.title,
                br.combined_embedding,
                bl.rating
            FROM b_book_list bl
            INNER JOIN b_book_repository br ON bl.amazon_id = br.asin
            WHERE bl.user_id = ?
            AND bl.rating >= 4
            AND br.combined_embedding IS NOT NULL
            ORDER BY bl.rating DESC, bl.update_date DESC
            LIMIT 20
        ";
        
        $books = $this->db->getAll($sql, [$this->userId], DB_FETCHMODE_ASSOC);
        
        if (DB::isError($books)) {
            error_log("Error fetching base books: " . $books->getMessage());
            return [];
        }
        
        return $books;
    }
    
    /**
     * エンベディングによる類似本検索
     */
    private function findSimilarByEmbedding(array $baseBook, int $limit, array $excludeAsins): array {
        // ベース本のエンベディングをデコード
        $baseEmbedding = json_decode($baseBook['combined_embedding'], true);
        if (!$baseEmbedding) {
            return [];
        }
        
        // 候補となる本を取得（エンベディング付き、未読本のみ）
        $placeholders = array();
        foreach ($excludeAsins as $asin) {
            $placeholders[] = '?';
        }
        
        $sql = "
            SELECT 
                br.asin,
                br.title,
                br.author,
                br.image_url,
                br.combined_embedding
            FROM b_book_repository br
            WHERE br.combined_embedding IS NOT NULL
            AND br.asin NOT IN (" . implode(',', $placeholders) . ")
            LIMIT 500
        ";
        
        $candidates = $this->db->getAll($sql, $excludeAsins, DB_FETCHMODE_ASSOC);
        
        if (DB::isError($candidates) || empty($candidates)) {
            return [];
        }
        
        // 類似度を計算
        $scoredBooks = [];
        foreach ($candidates as $candidate) {
            $candidateEmbedding = json_decode($candidate['combined_embedding'], true);
            if (!$candidateEmbedding) {
                continue;
            }
            
            // コサイン類似度を計算
            try {
                $similarity = EmbeddingGenerator::cosineSimilarity($baseEmbedding, $candidateEmbedding);
                
                // 類似度が閾値以上の本のみ選択（0.7以上）
                if ($similarity >= 0.7) {
                    $candidate['similarity_score'] = round($similarity * 100);
                    $candidate['reason'] = $this->generateSimilarityReason($baseBook, $candidate, $similarity);
                    unset($candidate['combined_embedding']); // 大きなデータは除去
                    $scoredBooks[] = $candidate;
                }
            } catch (Exception $e) {
                error_log("Similarity calculation error: " . $e->getMessage());
                continue;
            }
        }
        
        // スコアでソート
        usort($scoredBooks, function($a, $b) {
            return $b['similarity_score'] <=> $a['similarity_score'];
        });
        
        return array_slice($scoredBooks, 0, $limit);
    }
    
    /**
     * ユーザーの既読本リストを取得
     */
    private function getUserReadBooks(): array {
        $sql = "SELECT amazon_id FROM b_book_list WHERE user_id = ?";
        $books = $this->db->getAll($sql, [$this->userId], DB_FETCHMODE_ASSOC);
        
        if (DB::isError($books)) {
            return [];
        }
        
        return array_column($books, 'amazon_id');
    }
    
    /**
     * 類似度に基づく推薦理由を生成
     */
    private function generateSimilarityReason(array $baseBook, array $targetBook, float $similarity): string {
        $percentage = round($similarity * 100);
        
        // 同じ著者の場合
        if (!empty($baseBook['author']) && $baseBook['author'] === $targetBook['author']) {
            return "『{$baseBook['title']}』と同じ{$baseBook['author']}の作品（類似度{$percentage}%）";
        }
        
        // 類似度レベルに応じた理由
        if ($similarity >= 0.9) {
            return "『{$baseBook['title']}』と非常に似た内容（類似度{$percentage}%）";
        } else if ($similarity >= 0.8) {
            return "『{$baseBook['title']}』と内容が類似（類似度{$percentage}%）";
        } else {
            return "『{$baseBook['title']}』の読者におすすめ（類似度{$percentage}%）";
        }
    }
    
    /**
     * 特定の本に対する類似本を検索
     */
    public function findSimilarToBook(string $asin, int $limit = 10): array {
        // 対象本の情報とエンベディングを取得
        $sql = "
            SELECT asin, title, author, description, combined_embedding
            FROM b_book_repository
            WHERE asin = ?
        ";
        
        $book = $this->db->getRow($sql, [$asin], DB_FETCHMODE_ASSOC);
        
        if (DB::isError($book) || empty($book)) {
            return [];
        }
        
        // エンベディングがない場合は生成を試みる
        if (empty($book['combined_embedding']) && $this->generator) {
            $this->generator->generateBookEmbedding($asin);
            
            // 再取得
            $book = $this->db->getRow($sql, [$asin], DB_FETCHMODE_ASSOC);
            if (DB::isError($book) || empty($book['combined_embedding'])) {
                return [];
            }
        }
        
        // 既読本を除外リストに含める
        $excludeAsins = array_merge([$asin], $this->getUserReadBooks());
        
        return $this->findSimilarByEmbedding($book, $limit, $excludeAsins);
    }
}
?>