<?php
/**
 * AI自然言語検索エンジン
 * PHP 8.2.28対応版
 */

declare(strict_types=1);

require_once(dirname(__FILE__) . '/book_search.php');

class AISearchEngine {
    
    // OpenAI APIキー
    private string $apiKey;
    
    // 検索意図のタイプ
    const INTENT_GENRE = 'genre';           // ジャンル検索
    const INTENT_MOOD = 'mood';             // 気分・雰囲気検索
    const INTENT_SIMILAR = 'similar';       // 類似本検索
    const INTENT_AUTHOR = 'author';         // 著者検索
    const INTENT_SPECIFIC = 'specific';     // 特定の本検索
    const INTENT_THEME = 'theme';           // テーマ検索
    
    public function __construct() {
        // config.phpで定義されているOPENAI_API_KEYを使用
        $this->apiKey = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : '';
    }
    
    /**
     * クエリの解析のみ実行（進捗表示用）
     */
    public function analyzeQuery(string $naturalQuery): array {
        // Step 1: 検索意図を解析
        $intent = $this->analyzeSearchIntent($naturalQuery);
        
        // Step 2: 検索キーワードを生成
        $keywords = $this->generateSearchKeywords($naturalQuery, $intent);
        
        return [
            'intent' => $intent,
            'keywords' => $keywords
        ];
    }
    
    /**
     * 自然言語クエリを解析して検索を実行
     */
    public function search(string $naturalQuery, int $page = 1, int $limit = 20): array {
        // Step 1: 検索意図を解析
        $intent = $this->analyzeSearchIntent($naturalQuery);
        
        // Step 2: 検索キーワードを生成
        $keywords = $this->generateSearchKeywords($naturalQuery, $intent);
        
        // Step 3: 既存の検索機能を活用
        $results = $this->executeSearch($keywords, $intent, $page, $limit);
        
        // Step 4: 結果をAIでランキング調整
        $rankedResults = $this->rankResults($results, $naturalQuery, $intent);
        
        return [
            'success' => true,
            'query' => $naturalQuery,
            'intent' => $intent,
            'keywords' => $keywords,
            'results' => $rankedResults,
            'total' => count($results)
        ];
    }
    
    /**
     * 検索意図を解析
     */
    private function analyzeSearchIntent(string $query): array {
        // 簡易的なルールベース判定（将来的にはAI化）
        $patterns = [
            self::INTENT_MOOD => [
                '泣ける', '感動する', '元気が出る', '癒される', 'ほっこり',
                '怖い', 'ドキドキする', 'わくわくする', '切ない'
            ],
            self::INTENT_GENRE => [
                'ミステリー', 'SF', 'ファンタジー', '恋愛小説', 'ビジネス書',
                '自己啓発', '歴史小説', 'エッセイ', '詩集'
            ],
            self::INTENT_SIMILAR => [
                'みたいな', 'ような', '似た', '系の', 'っぽい'
            ],
            self::INTENT_THEME => [
                'についての本', 'に関する本', 'がテーマ', 'を扱った'
            ]
        ];
        
        $detectedIntents = [];
        
        foreach ($patterns as $intent => $keywords) {
            foreach ($keywords as $keyword) {
                if (mb_strpos($query, $keyword) !== false) {
                    $detectedIntents[] = $intent;
                    break;
                }
            }
        }
        
        // AIによる詳細な意図解析（OpenAI API使用）
        if ($this->apiKey) {
            $aiAnalysis = $this->callOpenAIForIntentAnalysis($query);
            $detectedIntents = array_merge($detectedIntents, $aiAnalysis);
        }
        
        return array_unique($detectedIntents) ?: [self::INTENT_SPECIFIC];
    }
    
    /**
     * 検索キーワードを生成
     */
    private function generateSearchKeywords(string $query, array $intents): array {
        $keywords = [];
        
        // OpenAI APIが利用可能な場合は、AIによるキーワード拡張を優先
        if ($this->apiKey) {
            $aiKeywords = $this->callOpenAIForKeywordExpansion($query, $intents);
            if (!empty($aiKeywords)) {
                $keywords = array_merge($keywords, $aiKeywords);
            }
        }
        
        // AIが使えない場合のフォールバック処理
        if (empty($keywords)) {
            // 基本的なキーワード抽出
            $stopWords = ['本', 'おすすめ', '教えて', 'ください', '探してる', '読みたい', 'を', 'が', 'の'];
            $cleanQuery = $query;
            foreach ($stopWords as $word) {
                $cleanQuery = str_replace($word, ' ', $cleanQuery);
            }
            $cleanQuery = trim(preg_replace('/\s+/', ' ', $cleanQuery));
            
            // クエリを分割して重要語を抽出
            $words = explode(' ', $cleanQuery);
            foreach ($words as $word) {
                if (mb_strlen($word) >= 2) { // 2文字以上の単語のみ
                    $keywords[] = $word;
                }
            }
        }
        
        // 元のクエリも含める（完全一致用）
        $keywords[] = $query;
        
        // 重複を除去して最大10個まで
        $keywords = array_unique($keywords);
        return array_slice($keywords, 0, 10);
    }
    
    /**
     * 検索を実行
     */
    private function executeSearch(array $keywords, array $intents, int $page, int $limit): array {
        $allResults = [];
        $searchedCount = 0;
        $maxSearches = 5; // API呼び出し回数を制限
        
        // 最初のキーワードはより多くの結果を取得
        foreach ($keywords as $index => $keyword) {
            if ($searchedCount >= $maxSearches) break;
            
            // 最初の2つのキーワードは多めに、後のキーワードは少なめに取得
            $searchLimit = $index < 2 ? $limit : max(10, $limit / 2);
            
            $results = searchBooks($keyword, 1, $searchLimit); // 常に1ページ目から
            if (!empty($results['books'])) {
                foreach ($results['books'] as $book) {
                    // 重複を避けるためISBNまたはASINをキーに
                    $key = $book['ISBN'] ?? $book['ASIN'] ?? md5($book['Title'] . $book['Author']);
                    if (!isset($allResults[$key])) {
                        // どのキーワードでヒットしたかを記録
                        $book['matched_keyword'] = $keyword;
                        $book['keyword_index'] = $index;
                        $allResults[$key] = $book;
                    }
                }
            }
            $searchedCount++;
        }
        
        return array_values($allResults);
    }
    
    /**
     * 結果をランキング
     */
    private function rankResults(array $results, string $query, array $intents): array {
        if (empty($results)) {
            return [];
        }
        
        // スコアリング
        foreach ($results as &$book) {
            $score = 0;
            
            // キーワードマッチングの優先度（早いキーワードほど高スコア）
            $keywordBonus = isset($book['keyword_index']) ? (10 - $book['keyword_index']) * 10 : 0;
            $score += $keywordBonus;
            
            // 元のクエリとの完全一致チェック
            $title = mb_strtolower($book['Title'] ?? '');
            $queryLower = mb_strtolower($query);
            if (mb_strpos($title, $queryLower) !== false) {
                $score += 100; // 完全一致は最高優先
            }
            
            // タイトルの関連性（部分一致）
            $queryWords = explode(' ', str_replace(['を', 'が', 'の', 'に'], ' ', $query));
            foreach ($queryWords as $word) {
                if (mb_strlen($word) >= 2 && mb_strpos($title, mb_strtolower($word)) !== false) {
                    $score += 30;
                }
            }
            
            // 著者名の一致
            $author = mb_strtolower($book['Author'] ?? '');
            foreach ($queryWords as $word) {
                if (mb_strlen($word) >= 2 && mb_strpos($author, mb_strtolower($word)) !== false) {
                    $score += 20;
                }
            }
            
            // 説明文の関連性
            $description = mb_strtolower($book['Description'] ?? '');
            if (!empty($description)) {
                foreach ($queryWords as $word) {
                    if (mb_strlen($word) >= 2 && mb_strpos($description, mb_strtolower($word)) !== false) {
                        $score += 10;
                    }
                }
            }
            
            // カテゴリの関連性
            if (!empty($book['Categories']) && in_array(self::INTENT_GENRE, $intents)) {
                $score += 15;
            }
            
            $book['ai_score'] = $score;
        }
        
        // スコアでソート
        usort($results, function($a, $b) {
            return $b['ai_score'] <=> $a['ai_score'];
        });
        
        return $results;
    }
    
    /**
     * 文字列の類似度を計算
     */
    private function calculateSimilarity(string $str1, string $str2): float {
        if (empty($str1) || empty($str2)) {
            return 0.0;
        }
        
        // 簡易的な実装（本来はTF-IDFやコサイン類似度を使用）
        $str1Lower = mb_strtolower($str1);
        $str2Lower = mb_strtolower($str2);
        
        similar_text($str1Lower, $str2Lower, $percent);
        return $percent / 100;
    }
    
    /**
     * OpenAI APIを使用した意図解析
     */
    private function callOpenAIForIntentAnalysis(string $query): array {
        if (empty($this->apiKey)) {
            return [];
        }
        
        try {
            $messages = [
                [
                    'role' => 'system',
                    'content' => 'あなたは本の検索意図を分析する専門家です。ユーザーの検索クエリから、どのような本を探しているかを分析してください。'
                ],
                [
                    'role' => 'user',
                    'content' => "以下の検索クエリを分析して、該当する検索意図をJSON形式で返してください。

検索クエリ: \"{$query}\"

選択肢:
- genre: 特定のジャンルを探している
- mood: 気分や雰囲気で探している  
- similar: 似た本を探している
- author: 著者で探している
- theme: 特定のテーマで探している
- specific: 特定の本を探している

レスポンス形式:
{\"intents\": [\"mood\", \"genre\"], \"confidence\": 0.9}"
                ]
            ];
            
            $ch = curl_init('https://api.openai.com/v1/chat/completions');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $this->apiKey,
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                'model' => 'gpt-3.5-turbo',
                'messages' => $messages,
                'temperature' => 0.3,
                'max_tokens' => 100
            ]));
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200 && $response) {
                $data = json_decode($response, true);
                $content = $data['choices'][0]['message']['content'] ?? '';
                $result = json_decode($content, true);
                
                return $result['intents'] ?? [];
            }
        } catch (Exception $e) {
            error_log('OpenAI API Error: ' . $e->getMessage());
        }
        
        return [];
    }
    
    /**
     * OpenAI APIを使用したキーワード拡張
     */
    private function callOpenAIForKeywordExpansion(string $query, array $intents): array {
        if (empty($this->apiKey)) {
            return [];
        }
        
        try {
            $intentStr = implode('、', $intents);
            $messages = [
                [
                    'role' => 'system',
                    'content' => 'あなたは日本の書籍検索の専門家です。日本で人気の本、著者、ジャンルに精通しています。ユーザーの自然言語クエリから、Google Books APIで効果的に検索できるキーワードを生成してください。'
                ],
                [
                    'role' => 'user',
                    'content' => "以下の検索クエリから、日本の本を検索するための最適なキーワードを生成してください。

クエリ: \"{$query}\"

重要な指示：
1. 「{$query}」のニュアンスを捉えた具体的な検索キーワードを生成
2. 日本で人気の作品名、著者名、出版社名を含める
3. 類似のテーマの本のタイトルや著者も提案
4. 一般的すぎるキーワードは避ける

例：
- 「泣ける恋愛小説」→ [\"君の膵臓をたべたい\", \"恋空\", \"いま、会いにゆきます\", \"住野よる\", \"純愛小説\"]
- 「元気が出るビジネス書」→ [\"嫌われる勇気\", \"7つの習慣\", \"GRIT やり抜く力\", \"岸見一郎\", \"モチベーション革命\"]
- 「夏に読みたい本」→ [\"夏の庭\", \"西の魔女が死んだ\", \"夏目漱石\", \"ひと夏の冒険\", \"青春小説 夏\"]

7個以内で提案してください。
JSON形式で回答: {\"keywords\": [...]}"
                ]
            ];
            
            $ch = curl_init('https://api.openai.com/v1/chat/completions');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $this->apiKey,
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                'model' => 'gpt-3.5-turbo',
                'messages' => $messages,
                'temperature' => 0.5,  // より一貫性のある結果のため低めに設定
                'max_tokens' => 200   // キーワードリストのため少し増やす
            ]));
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200 && $response) {
                $data = json_decode($response, true);
                $content = $data['choices'][0]['message']['content'] ?? '';
                $result = json_decode($content, true);
                
                return $result['keywords'] ?? [];
            }
        } catch (Exception $e) {
            error_log('OpenAI API Keyword Expansion Error: ' . $e->getMessage());
        }
        
        return [];
    }
}
?>