<?php
/**
 * AI類似性分析クラス
 * OpenAI APIを使用して本の内容の類似性を判定
 */

declare(strict_types=1);

require_once(dirname(__FILE__) . '/database.php');

class AISimilarityAnalyzer {
    private $apiKey;
    private $db;
    
    public function __construct() {
        global $g_db;
        $this->db = $g_db;
        
        if (!defined('OPENAI_API_KEY') || empty(OPENAI_API_KEY)) {
            throw new Exception('OpenAI API key is not configured');
        }
        $this->apiKey = OPENAI_API_KEY;
    }
    
    /**
     * 高評価本に類似した本を見つける
     * @param int $userId ユーザーID
     * @param int $limit 取得件数
     * @return array 類似本の配列
     */
    public function findSimilarBooks(int $userId, int $limit = 15): array {
        // ユーザーの高評価本を取得（多様性を重視）
        $highRatedSql = "
            (
                SELECT 
                    br.asin,
                    br.title,
                    br.author,
                    bl.rating,
                    bl.update_date
                FROM b_book_list bl
                INNER JOIN b_book_repository br ON bl.amazon_id = br.asin
                WHERE bl.user_id = ?
                AND bl.rating = 5
                ORDER BY bl.update_date DESC
                LIMIT 5
            )
            UNION
            (
                SELECT 
                    br.asin,
                    br.title,
                    br.author,
                    bl.rating,
                    bl.update_date
                FROM b_book_list bl
                INNER JOIN b_book_repository br ON bl.amazon_id = br.asin
                WHERE bl.user_id = ?
                AND bl.rating = 4
                ORDER BY bl.update_date DESC
                LIMIT 5
            )
            UNION
            (
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
                ORDER BY RAND()
                LIMIT 5
            )
            ORDER BY rating DESC, update_date DESC
            LIMIT 15
        ";
        
        $highRatedBooks = $this->db->getAll($highRatedSql, [$userId, $userId, $userId], DB_FETCHMODE_ASSOC);
        
        if (DB::isError($highRatedBooks) || empty($highRatedBooks)) {
            // 高評価本がない場合は読了本から取得
            $alternativeSql = "
                SELECT 
                    br.asin,
                    br.title,
                    br.author,
                    3 as rating
                FROM b_book_list bl
                INNER JOIN b_book_repository br ON bl.amazon_id = br.asin
                WHERE bl.user_id = ?
                AND bl.status IN (3, 4)
                ORDER BY bl.update_date DESC
                LIMIT 10
            ";
            
            $highRatedBooks = $this->db->getAll($alternativeSql, [$userId], DB_FETCHMODE_ASSOC);
            
            if (DB::isError($highRatedBooks) || empty($highRatedBooks)) {
                return [];
            }
        }
        
        // データベースから候補となる本を取得（より多く取得）
        $candidatesSql = "
            SELECT DISTINCT
                br.asin,
                br.title,
                br.author,
                br.image_url
            FROM b_book_repository br
            LEFT JOIN b_book_list bl ON bl.amazon_id = br.asin AND bl.user_id = ?
            INNER JOIN b_book_list bl_others ON bl_others.amazon_id = br.asin
            WHERE bl.book_id IS NULL
            AND br.author IS NOT NULL
            AND br.author != ''
            GROUP BY br.asin, br.title, br.author, br.image_url
            HAVING COUNT(DISTINCT bl_others.user_id) >= 2
            ORDER BY COUNT(DISTINCT bl_others.user_id) DESC, RAND()
            LIMIT 200
        ";
        
        $candidates = $this->db->getAll($candidatesSql, [$userId], DB_FETCHMODE_ASSOC);
        
        if (DB::isError($candidates) || empty($candidates)) {
            // フォールバック：ランダムに取得
            $fallbackSql = "
                SELECT DISTINCT
                    br.asin,
                    br.title,
                    br.author,
                    br.image_url
                FROM b_book_repository br
                LEFT JOIN b_book_list bl ON bl.amazon_id = br.asin AND bl.user_id = ?
                WHERE bl.book_id IS NULL
                AND br.author IS NOT NULL
                AND br.author != ''
                ORDER BY RAND()
                LIMIT 200
            ";
            
            $candidates = $this->db->getAll($fallbackSql, [$userId], DB_FETCHMODE_ASSOC);
            
            if (DB::isError($candidates) || empty($candidates)) {
                return [];
            }
        }
        
        // AIを使って類似性を分析
        $similarBooks = $this->analyzeSimilarity($highRatedBooks, $candidates);
        
        // 基準本の情報を追加
        foreach ($similarBooks as &$book) {
            if (isset($book['base_book_index']) && isset($highRatedBooks[$book['base_book_index']])) {
                $baseBook = $highRatedBooks[$book['base_book_index']];
                $book['similar_to'] = $baseBook['title'];
                $book['similar_to_author'] = $baseBook['author'];
            }
        }
        
        // スコアでソートして上位を返す
        usort($similarBooks, function($a, $b) {
            return ($b['score'] ?? 0) - ($a['score'] ?? 0);
        });
        
        return array_slice($similarBooks, 0, $limit);
    }
    
    /**
     * AIを使って本の類似性を分析
     */
    private function analyzeSimilarity(array $baseBooks, array $candidates): array {
        $prompt = $this->buildSimilarityPrompt($baseBooks, $candidates);
        
        try {
            $response = $this->callOpenAI($prompt);
            return $this->parseSimilarityResponse($response, $candidates);
        } catch (Exception $e) {
            error_log('AI Similarity Analysis Error: ' . $e->getMessage());
            // フォールバック：著者マッチングを使用
            return $this->fallbackAuthorMatching($baseBooks, $candidates);
        }
    }
    
    /**
     * 類似性分析用のプロンプトを構築
     */
    private function buildSimilarityPrompt(array $baseBooks, array $candidates): string {
        $baseBookList = [];
        foreach ($baseBooks as $baseIndex => $book) {
            $baseBookList[] = sprintf('%d. 「%s」(%s)', $baseIndex, $book['title'], $book['author']);
        }
        
        $candidateList = [];
        foreach ($candidates as $index => $book) {
            $candidateList[] = sprintf('%d. 「%s」(%s)', $index, $book['title'], $book['author']);
        }
        
        // 候補を80冊に増やす
        $candidatesForPrompt = array_slice($candidateList, 0, 80);
        
        return "あなたは読書専門家です。以下のユーザーが高評価した本に基づいて、候補リストから本質的に類似している本を見つけてください。

【ユーザーが高評価した本】（番号付き）
" . implode("\n", $baseBookList) . "

【候補となる本】
" . implode("\n", $candidatesForPrompt) . "

【重要な判定基準】
1. まず各本のジャンルを正確に判定してください：
   - 実用書（ビジネス、自己啓発、学習法、語学、技術書など）
   - 小説（ミステリー、ファンタジー、恋愛、文学など）
   - ノンフィクション（伝記、ドキュメンタリー、歴史など）
   - 教育書（教科書、参考書、問題集など）
   - その他（エッセイ、詩集、漫画など）

2. 同じジャンル内で以下を評価：
   - 実用書の場合：扱うスキル・知識領域、難易度、実践的アプローチ
   - 小説の場合：テーマ、雰囲気、文体、ストーリー構造
   - 教育書の場合：対象科目、学習レベル、教授法

3. 類似性の判定：
   - 異なるジャンル間での類似性は原則として認めない
   - 例：英語学習書には英語関連の本を、料理本には料理本を推薦
   - 表面的なキーワードの一致ではなく、本質的な内容の類似性を重視

選んだ本について、以下のJSON形式で返してください：
{
  \"similar_books\": [
    {
      \"index\": 候補リストの番号,
      \"base_book_index\": どの高評価本に類似しているか（高評価本リストの番号）,
      \"similarity_score\": 類似度(1-100),
      \"reason\": \"類似している理由（具体的かつ簡潔に）\"
    }
  ]
}

推薦の注意事項：
- 「Word Power Made Easy」のような語学書には語学・言語学習の本を
- プログラミング本にはプログラミング関連の本を
- 料理本には料理・レシピ本を
- 小説には同じジャンルの小説を
- reasonは「『○○』と同様に△△を扱う実用書」のように具体的に記載
- 最大20冊まで選択可能だが、質の高い類似性がある本のみを選ぶ";
    }
    
    /**
     * OpenAI APIを呼び出す
     */
    private function callOpenAI(string $prompt): string {
        $url = 'https://api.openai.com/v1/chat/completions';
        
        $data = [
            'model' => 'gpt-4o-mini',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'あなたは書籍の内容を深く理解し、読者の好みに合った本を推薦できる専門家です。'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.3,
            'max_tokens' => 2000,
            'response_format' => ['type' => 'json_object']
        ];
        
        $options = [
            'http' => [
                'header' => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $this->apiKey
                ],
                'method' => 'POST',
                'content' => json_encode($data),
                'timeout' => 30
            ]
        ];
        
        $context = stream_context_create($options);
        $result = @file_get_contents($url, false, $context);
        
        if ($result === false) {
            throw new Exception('Failed to call OpenAI API');
        }
        
        $response = json_decode($result, true);
        
        if (!isset($response['choices'][0]['message']['content'])) {
            throw new Exception('Invalid response from OpenAI API');
        }
        
        return $response['choices'][0]['message']['content'];
    }
    
    /**
     * AI応答を解析
     */
    private function parseSimilarityResponse(string $response, array $candidates): array {
        $parsed = json_decode($response, true);
        
        if (!isset($parsed['similar_books']) || !is_array($parsed['similar_books'])) {
            return [];
        }
        
        $similarBooks = [];
        
        foreach ($parsed['similar_books'] as $item) {
            $index = $item['index'] ?? -1;
            
            if ($index >= 0 && $index < count($candidates)) {
                $book = $candidates[$index];
                $book['score'] = 70 + min(30, intval($item['similarity_score'] ?? 0) * 0.3);
                $book['reason'] = $item['reason'] ?? 'AIが類似性を検出';
                $book['base_book_index'] = $item['base_book_index'] ?? null;
                $book['ai_similarity'] = true;
                $similarBooks[] = $book;
            }
        }
        
        return $similarBooks;
    }
    
    /**
     * フォールバック：著者マッチング
     */
    private function fallbackAuthorMatching(array $baseBooks, array $candidates): array {
        $baseAuthors = array_unique(array_column($baseBooks, 'author'));
        $similarBooks = [];
        
        foreach ($candidates as $candidate) {
            if (in_array($candidate['author'], $baseAuthors)) {
                $candidate['score'] = 75;
                $candidate['reason'] = '同じ著者の作品';
                $similarBooks[] = $candidate;
            }
        }
        
        return $similarBooks;
    }
}