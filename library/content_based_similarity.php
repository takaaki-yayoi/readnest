<?php
/**
 * コンテンツベース類似性分析
 * 本の内容に基づいた高精度な類似本推薦
 */

declare(strict_types=1);

require_once(dirname(__FILE__) . '/database.php');
require_once(dirname(__FILE__) . '/book_description_updater.php');
require_once(dirname(__FILE__) . '/advanced_similarity_analyzer.php');
require_once(dirname(__FILE__) . '/embedding_similarity.php');

class ContentBasedSimilarity {
    private $db;
    private $userId;
    private $descriptionUpdater;
    
    public function __construct(int $userId) {
        global $g_db;
        $this->db = $g_db;
        $this->userId = $userId;
        $this->descriptionUpdater = new BookDescriptionUpdater();
    }
    
    /**
     * コンテンツに基づく類似本を取得
     */
    public function findSimilarBooks(int $limit = 20): array {
        // 1. エンベディングベースの検索を最優先（最も高精度）
        if (defined('OPENAI_API_KEY') && !empty(OPENAI_API_KEY)) {
            try {
                $embeddingAnalyzer = new EmbeddingSimilarity($this->userId);
                $embeddingResults = $embeddingAnalyzer->findSimilarBooks($limit);
                
                // エンベディング結果が1件でもあれば優先的に使用
                if (!empty($embeddingResults)) {
                    error_log("Using embedding-based similarity: " . count($embeddingResults) . " books found");
                    
                    // 結果が少ない場合は、高度な説明文分析で補完
                    if (count($embeddingResults) < $limit) {
                        $advancedAnalyzer = new AdvancedSimilarityAnalyzer($this->userId);
                        $additionalResults = $advancedAnalyzer->findSimilarBooks($limit - count($embeddingResults));
                        
                        // 重複を除外して追加
                        $existingAsins = array_column($embeddingResults, 'asin');
                        foreach ($additionalResults as $book) {
                            if (!in_array($book['asin'], $existingAsins)) {
                                $embeddingResults[] = $book;
                            }
                        }
                    }
                    
                    return array_slice($embeddingResults, 0, $limit);
                }
            } catch (Exception $e) {
                error_log("Embedding similarity error: " . $e->getMessage());
            }
        }
        
        // 2. 高度な説明文ベース分析（フォールバック）
        $advancedAnalyzer = new AdvancedSimilarityAnalyzer($this->userId);
        $advancedResults = $advancedAnalyzer->findSimilarBooks($limit);
        
        // 十分な結果が得られた場合はそれを返す
        if (count($advancedResults) >= $limit * 0.7) {
            return $advancedResults;
        }
        
        // 不足分は従来のロジックで補完
        // 1. ユーザーの高評価本を分析
        $userProfile = $this->analyzeUserPreferences();
        
        if (empty($userProfile['base_books'])) {
            return [];
        }
        
        // 既読本のASINリストを取得
        $readBooks = $this->getUserReadBooks();
        
        // ベース本のASINも既読リストに追加（重複推薦を防ぐ）
        foreach ($userProfile['base_books'] as $book) {
            if (!in_array($book['asin'], $readBooks)) {
                $readBooks[] = $book['asin'];
            }
        }
        
        // 2. 各ベース本に対して類似本を検索（多様性を確保）
        $allSimilar = [];
        $seenAsins = [];
        $seenAuthors = [];
        $maxPerAuthor = 2; // 同じ著者からは最大2冊まで
        
        // ベース本を多様化（同じ著者を避ける + ランダム選択）
        $diverseBaseBooks = [];
        $baseAuthors = [];
        $baseTitles = [];
        
        // 高評価本をシャッフルして多様性を増す
        $shuffledBooks = $userProfile['base_books'];
        shuffle($shuffledBooks);
        
        foreach ($shuffledBooks as $book) {
            // 同じ著者の本は1冊まで（完全な多様性）
            // 同じタイトルの異なる版も除外
            if (!in_array($book['author'], $baseAuthors) && 
                !in_array($book['title'], $baseTitles)) {
                $diverseBaseBooks[] = $book;
                $baseAuthors[] = $book['author'];
                $baseTitles[] = $book['title'];
            }
            if (count($diverseBaseBooks) >= 8) break; // 8冊まで（より多様に）
        }
        
        // 結果を格納（ベース本ごとの結果を均等に）
        $resultsPerBook = [];
        $maxPerBook = (int)ceil($limit / max(1, count($diverseBaseBooks)));
        
        foreach ($diverseBaseBooks as $baseBook) {
            // タイトルと著者から特徴を抽出
            $features = $this->extractBookFeatures($baseBook);
            
            // 類似本を検索（各ベース本から均等に取得）
            $similar = $this->searchSimilarByFeatures($baseBook, $features, $maxPerBook);
            
            foreach ($similar as $book) {
                // 既読本は除外
                if (in_array($book['asin'], $readBooks)) {
                    continue;
                }
                
                // 重複除外
                if (in_array($book['asin'], $seenAsins)) {
                    continue;
                }
                
                // 同じ著者の本を制限
                $authorCount = isset($seenAuthors[$book['author']]) ? $seenAuthors[$book['author']] : 0;
                if ($authorCount >= $maxPerAuthor) {
                    continue;
                }
                
                $book['base_book'] = $baseBook['title'];
                $book['base_author'] = $baseBook['author'];
                $allSimilar[] = $book;
                $seenAsins[] = $book['asin'];
                
                if (!empty($book['author'])) {
                    $seenAuthors[$book['author']] = $authorCount + 1;
                }
            }
        }
        
        // 3. スコアでソート
        usort($allSimilar, function($a, $b) {
            return ($b['similarity_score'] ?? 0) - ($a['similarity_score'] ?? 0);
        });
        
        return array_slice($allSimilar, 0, $limit);
    }
    
    /**
     * ユーザーの既読本のASINリストを取得
     */
    private function getUserReadBooks(): array {
        $sql = "
            SELECT amazon_id
            FROM b_book_list
            WHERE user_id = ?
        ";
        
        $books = $this->db->getAll($sql, [$this->userId], DB_FETCHMODE_ASSOC);
        
        if (DB::isError($books)) {
            return [];
        }
        
        return array_column($books, 'amazon_id');
    }
    
    /**
     * ユーザーの読書傾向を分析
     */
    private function analyzeUserPreferences(): array {
        // 高評価本（4-5つ星）を取得
        $sql = "
            SELECT 
                br.asin,
                br.title,
                br.author,
                bl.rating,
                bl.update_date
            FROM b_book_list bl
            INNER JOIN b_book_repository br ON bl.amazon_id = br.asin
            WHERE bl.user_id = ?
            AND bl.rating >= 4
            ORDER BY bl.rating DESC, bl.update_date DESC
            LIMIT 20
        ";
        
        $books = $this->db->getAll($sql, [$this->userId], DB_FETCHMODE_ASSOC);
        
        if (DB::isError($books) || empty($books)) {
            return ['base_books' => []];
        }
        
        // パターンを分析
        $patterns = [
            'authors' => [],
            'title_patterns' => []
        ];
        
        foreach ($books as $book) {
            // 著者
            if (!empty($book['author'])) {
                $patterns['authors'][$book['author']] = 
                    ($patterns['authors'][$book['author']] ?? 0) + 1;
            }
            
            // キーワード抽出は削除（内容に基づかないため）
        }
        
        return [
            'base_books' => $books,
            'patterns' => $patterns
        ];
    }
    
    /**
     * 本の特徴を抽出（簡略化版）
     */
    private function extractBookFeatures(array $book): array {
        // 説明文ベースの分析に集中するため、基本情報のみ返す
        return [
            'title' => $book['title'],
            'author' => $book['author'],
            'asin' => $book['asin']
        ];
    }
    
    
    
    
    
    
    /**
     * 特徴に基づいて類似本を検索
     */
    private function searchSimilarByFeatures(array $baseBook, array $features, int $limit): array {
        $similar = [];
        
        // 1. 説明文ベースの類似本検索（最優先）
        if (!empty($baseBook['asin'])) {
            // ベース本の説明文を取得（なければ自動取得）
            $baseDescription = $this->descriptionUpdater->getDescription($baseBook['asin']);
            
            if (!empty($baseDescription)) {
                // 説明文で類似本を検索
                $descriptionSimilar = $this->descriptionUpdater->findSimilarBooksByDescription(
                    $baseBook['asin'],
                    $this->userId,
                    $limit - 2  // 著者の本のための余地を残す
                );
                
                foreach ($descriptionSimilar as &$book) {
                    // similarity_scoreは既に設定済み
                    $book['reason'] = "内容が『{$baseBook['title']}』に類似";
                }
                
                $similar = array_merge($similar, $descriptionSimilar);
            }
        }
        
        // 2. 同じ著者の他の作品（制限付き、フォールバック）
        if (count($similar) < $limit && !empty($baseBook['author'])) {
            $authorBooks = $this->searchByAuthor($baseBook['author'], $baseBook['asin'], 2); // 最大2冊に制限
            foreach ($authorBooks as &$book) {
                $book['similarity_score'] = 65; // スコアを低めにして多様性を促進
                $book['reason'] = "『{$baseBook['title']}』と同じ{$baseBook['author']}の作品";
            }
            $similar = array_merge($similar, $authorBooks);
        }
        
        // 重複除去とソート（ベース本自体も除外）
        $uniqueBooks = [];
        $seenAsins = [$baseBook['asin']]; // ベース本のASINを最初から除外リストに追加
        
        foreach ($similar as $book) {
            // ベース本と同じ本は除外
            if ($book['asin'] === $baseBook['asin']) {
                continue;
            }
            
            // タイトルと著者が完全一致する本も除外（異なるASINでも同じ本の可能性）
            if ($book['title'] === $baseBook['title'] && $book['author'] === $baseBook['author']) {
                continue;
            }
            
            if (!in_array($book['asin'], $seenAsins)) {
                $uniqueBooks[] = $book;
                $seenAsins[] = $book['asin'];
            }
        }
        
        usort($uniqueBooks, function($a, $b) {
            return ($b['similarity_score'] ?? 0) - ($a['similarity_score'] ?? 0);
        });
        
        return array_slice($uniqueBooks, 0, $limit);
    }
    
    
    
    /**
     * 著者で検索
     */
    private function searchByAuthor(string $author, string $excludeAsin, int $limit): array {
        $sql = "
            SELECT DISTINCT
                br.asin,
                br.title,
                br.author,
                br.image_url
            FROM b_book_repository br
            LEFT JOIN b_book_list bl ON bl.amazon_id = br.asin AND bl.user_id = ?
            WHERE bl.book_id IS NULL
            AND br.author = ?
            AND br.asin != ?
            ORDER BY br.title
            LIMIT ?
        ";
        
        $books = $this->db->getAll($sql, [$this->userId, $author, $excludeAsin, $limit], DB_FETCHMODE_ASSOC);
        
        return DB::isError($books) ? [] : $books;
    }
    
    
}