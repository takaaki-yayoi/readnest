<?php
/**
 * 高度な類似性分析クラス
 * 説明文を使った内容ベースの類似本検索
 */

declare(strict_types=1);

require_once(dirname(__FILE__) . '/database.php');
require_once(dirname(__FILE__) . '/book_description_updater.php');

class AdvancedSimilarityAnalyzer {
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
     * 説明文ベースの高精度類似本検索
     */
    public function findSimilarBooks(int $limit = 20): array {
        // 1. ユーザーの高評価本を取得
        $baseBooks = $this->getUserHighRatedBooks();
        if (empty($baseBooks)) {
            return [];
        }
        
        // 2. 各本の説明文を確保（なければ取得）
        $this->ensureDescriptions($baseBooks);
        
        // 3. 説明文がある本を対象に類似本を検索
        $allSimilar = [];
        $seenAsins = [];
        $processedAuthors = [];
        
        foreach ($baseBooks as $baseBook) {
            // 同じ著者からは1冊まで（多様性確保）
            if (isset($processedAuthors[$baseBook['author']]) && 
                $processedAuthors[$baseBook['author']] >= 1) {
                continue;
            }
            
            // 説明文がない場合はスキップ
            if (empty($baseBook['description'])) {
                continue;
            }
            
            // 類似本を検索
            $similar = $this->findSimilarByDescription(
                $baseBook,
                5, // 各ベース本から最大5冊
                $seenAsins
            );
            
            foreach ($similar as $book) {
                if (!in_array($book['asin'], $seenAsins)) {
                    $allSimilar[] = $book;
                    $seenAsins[] = $book['asin'];
                }
            }
            
            if (!empty($baseBook['author'])) {
                $processedAuthors[$baseBook['author']] = 
                    ($processedAuthors[$baseBook['author']] ?? 0) + 1;
            }
            
            // 十分な数が集まったら終了
            if (count($allSimilar) >= $limit * 2) {
                break;
            }
        }
        
        // 4. スコアでソートして上位を返す
        usort($allSimilar, function($a, $b) {
            return ($b['similarity_score'] ?? 0) - ($a['similarity_score'] ?? 0);
        });
        
        return array_slice($allSimilar, 0, $limit);
    }
    
    /**
     * ユーザーの高評価本を取得
     */
    private function getUserHighRatedBooks(): array {
        $sql = "
            SELECT 
                br.asin,
                br.title,
                br.author,
                br.description,
                br.google_categories,
                bl.rating
            FROM b_book_list bl
            INNER JOIN b_book_repository br ON bl.amazon_id = br.asin
            WHERE bl.user_id = ?
            AND bl.rating >= 4
            ORDER BY bl.rating DESC, bl.update_date DESC
            LIMIT 20
        ";
        
        $books = $this->db->getAll($sql, [$this->userId], DB_FETCHMODE_ASSOC);
        
        if (DB::isError($books)) {
            return [];
        }
        
        return $books;
    }
    
    /**
     * 説明文を確保（なければ取得）
     */
    private function ensureDescriptions(array &$books): void {
        $updateCount = 0;
        $maxUpdates = 3; // API制限
        
        foreach ($books as &$book) {
            if (empty($book['description']) && $updateCount < $maxUpdates) {
                // 説明文を取得
                $description = $this->descriptionUpdater->getDescription($book['asin']);
                if ($description) {
                    $book['description'] = $description;
                    $updateCount++;
                }
            }
        }
    }
    
    /**
     * 説明文による類似本検索
     */
    private function findSimilarByDescription(array $baseBook, int $limit, array $excludeAsins): array {
        // 既読本を除外
        $excludeAsins[] = $baseBook['asin'];
        
        // IN句のプレースホルダーを作成
        $placeholders = array();
        foreach ($excludeAsins as $asin) {
            $placeholders[] = '?';
        }
        
        // 説明文がある未読本を取得
        $sql = "
            SELECT 
                br.asin,
                br.title,
                br.author,
                br.image_url,
                br.description,
                br.google_categories
            FROM b_book_repository br
            LEFT JOIN b_book_list bl ON bl.amazon_id = br.asin AND bl.user_id = ?
            WHERE br.description IS NOT NULL
            AND br.description != ''
            AND br.asin NOT IN (" . implode(',', $placeholders) . ")
            AND bl.book_id IS NULL
            ORDER BY 
                CASE 
                    WHEN br.author = ? THEN 0
                    ELSE 1
                END,
                LENGTH(br.description) DESC
            LIMIT 200
        ";
        
        $params = array_merge(
            [$this->userId],
            $excludeAsins,
            [$baseBook['author']]
        );
        
        $candidates = $this->db->getAll($sql, $params, DB_FETCHMODE_ASSOC);
        
        if (DB::isError($candidates) || empty($candidates)) {
            return [];
        }
        
        // 類似度を計算
        $scoredBooks = [];
        foreach ($candidates as $candidate) {
            $score = $this->calculateAdvancedSimilarity($baseBook, $candidate);
            
            if ($score >= 30) { // 閾値
                $candidate['similarity_score'] = $score;
                $candidate['reason'] = $this->generateDetailedReason($baseBook, $candidate, $score);
                $candidate['base_book'] = $baseBook['title'];
                $scoredBooks[] = $candidate;
            }
        }
        
        // スコアでソート
        usort($scoredBooks, function($a, $b) {
            return $b['similarity_score'] - $a['similarity_score'];
        });
        
        return array_slice($scoredBooks, 0, $limit);
    }
    
    /**
     * 高度な類似度計算
     */
    private function calculateAdvancedSimilarity(array $book1, array $book2): int {
        $score = 0;
        
        // 1. 同じ著者（30点）
        if (!empty($book1['author']) && $book1['author'] === $book2['author']) {
            $score += 30;
        }
        
        // 2. 説明文の類似度（最大50点）
        if (!empty($book1['description']) && !empty($book2['description'])) {
            $textSimilarity = $this->calculateTextSimilarity(
                $book1['description'],
                $book2['description']
            );
            $score += min(50, $textSimilarity * 50);
        }
        
        // 3. カテゴリの一致（20点）
        if (!empty($book1['google_categories']) && !empty($book2['google_categories'])) {
            $cat1 = json_decode($book1['google_categories'], true) ?: [];
            $cat2 = json_decode($book2['google_categories'], true) ?: [];
            
            if (!empty(array_intersect($cat1, $cat2))) {
                $score += 20;
            }
        }
        
        // 4. タイトルの類似性（最大20点）
        $titleSimilarity = $this->calculateTitleSimilarity(
            $book1['title'],
            $book2['title']
        );
        $score += min(20, $titleSimilarity * 20);
        
        return (int)min(100, $score);
    }
    
    /**
     * テキストの類似度計算（改良版）
     */
    private function calculateTextSimilarity(string $text1, string $text2): float {
        // 重要な単語を抽出
        $words1 = $this->extractImportantWords($text1);
        $words2 = $this->extractImportantWords($text2);
        
        if (empty($words1) || empty($words2)) {
            return 0.0;
        }
        
        // TF-IDFスコア計算（簡易版）
        $common = array_intersect($words1, $words2);
        $union = array_unique(array_merge($words1, $words2));
        
        // Jaccard係数
        $jaccard = count($common) / count($union);
        
        // 重要語の重み付け
        $importantTerms = ['ミステリー', '推理', '殺人', '事件', '探偵', 
                          'ファンタジー', '魔法', '恋愛', 'ビジネス', 
                          'プログラミング', '料理', '歴史', '心理'];
        
        $importantMatches = 0;
        foreach ($importantTerms as $term) {
            if (in_array($term, $common)) {
                $importantMatches++;
            }
        }
        
        // 重要語マッチによるボーナス
        $bonus = min(0.3, $importantMatches * 0.1);
        
        return min(1.0, $jaccard + $bonus);
    }
    
    /**
     * タイトルの類似度計算
     */
    private function calculateTitleSimilarity(string $title1, string $title2): float {
        // シリーズものの検出
        $series1 = $this->extractSeriesName($title1);
        $series2 = $this->extractSeriesName($title2);
        
        if ($series1 && $series2 && $series1 === $series2) {
            return 0.8; // 同じシリーズ
        }
        
        // 共通キーワードの検出
        $words1 = $this->extractImportantWords($title1);
        $words2 = $this->extractImportantWords($title2);
        
        if (empty($words1) || empty($words2)) {
            return 0.0;
        }
        
        $common = array_intersect($words1, $words2);
        return count($common) / max(count($words1), count($words2));
    }
    
    /**
     * 重要な単語を抽出
     */
    private function extractImportantWords(string $text): array {
        // 小文字化
        $text = mb_strtolower($text);
        
        // 不要な文字を除去
        $text = preg_replace('/[。、！？「」『』（）\[\]【】〜ー・]/u', ' ', $text);
        
        // 単語に分割
        $words = preg_split('/[\s　]+/u', $text);
        
        // ストップワードを除去
        $stopWords = ['の', 'は', 'が', 'を', 'に', 'で', 'と', 'から', 'まで', 
                     'より', 'も', 'や', 'など', 'こと', 'もの', 'これ', 'それ', 
                     'あれ', 'です', 'ます', 'する', 'なる', 'ある', 'いる', 'ない',
                     'という', 'ような', 'ように', 'において', 'について'];
        
        // 出版社名も除去
        $publishers = ['文庫', '新書', '集英社', '角川', '新潮', '講談社', '文春'];
        
        $important = [];
        foreach ($words as $word) {
            if (mb_strlen($word) >= 2 && 
                !in_array($word, $stopWords) && 
                !in_array($word, $publishers)) {
                $important[] = $word;
            }
        }
        
        return array_unique($important);
    }
    
    /**
     * シリーズ名を抽出
     */
    private function extractSeriesName(string $title): ?string {
        // 「〜シリーズ」パターン
        if (preg_match('/(.+?)(?:シリーズ|series)/ui', $title, $matches)) {
            return trim($matches[1]);
        }
        
        // 巻数表記を除去してベースタイトルを取得
        if (preg_match('/^(.+?)[\s　]*(?:\d+|[一二三四五六七八九十]+)[\s　]*巻?/u', $title, $matches)) {
            return trim($matches[1]);
        }
        
        // 上中下巻
        if (preg_match('/^(.+?)[\s　]*[上中下]巻?/u', $title, $matches)) {
            return trim($matches[1]);
        }
        
        return null;
    }
    
    /**
     * 詳細な推薦理由を生成
     */
    private function generateDetailedReason(array $baseBook, array $targetBook, int $score): string {
        $reasons = [];
        
        // 同じ著者
        if (!empty($baseBook['author']) && $baseBook['author'] === $targetBook['author']) {
            $reasons[] = "同じ{$baseBook['author']}の作品";
        }
        
        // 高スコア
        if ($score >= 70) {
            $reasons[] = "内容が非常に類似";
        } else if ($score >= 50) {
            $reasons[] = "テーマや雰囲気が類似";
        }
        
        // カテゴリ一致
        if (!empty($baseBook['google_categories']) && !empty($targetBook['google_categories'])) {
            $cat1 = json_decode($baseBook['google_categories'], true) ?: [];
            $cat2 = json_decode($targetBook['google_categories'], true) ?: [];
            $common = array_intersect($cat1, $cat2);
            
            if (!empty($common)) {
                $reasons[] = "同じ「" . reset($common) . "」ジャンル";
            }
        }
        
        if (empty($reasons)) {
            return "『{$baseBook['title']}』の読者におすすめ";
        }
        
        return implode('、', array_slice($reasons, 0, 2));
    }
}
?>