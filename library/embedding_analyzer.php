<?php
/**
 * Embeddingベースの分析クラス
 * レビューembeddingを使用してクラスタリングや類似度計算を行う
 */

declare(strict_types=1);

require_once(__DIR__ . '/../config.php');
require_once(__DIR__ . '/database.php');

class EmbeddingAnalyzer {
    private $db;
    
    public function __construct() {
        global $g_db;
        $this->db = $g_db;
    }
    
    /**
     * コサイン類似度を計算
     */
    private function cosineSimilarity(array $vec1, array $vec2): float {
        $dotProduct = 0.0;
        $norm1 = 0.0;
        $norm2 = 0.0;
        
        for ($i = 0; $i < count($vec1); $i++) {
            $dotProduct += $vec1[$i] * $vec2[$i];
            $norm1 += $vec1[$i] * $vec1[$i];
            $norm2 += $vec2[$i] * $vec2[$i];
        }
        
        if ($norm1 == 0 || $norm2 == 0) {
            return 0;
        }
        
        return $dotProduct / (sqrt($norm1) * sqrt($norm2));
    }
    
    /**
     * ユーザーのレビューembeddingを取得してクラスタリング
     */
    public function analyzeUserReadingClusters(int $userId, int $numClusters = 5, bool $useLLM = false): array {
        // レビューembeddingを取得
        $sql = "
            SELECT 
                re.book_id,
                re.review_embedding,
                bl.name as title,
                bl.author,
                bl.rating,
                bl.amazon_id,
                bl.image_url,
                LEFT(bl.memo, 200) as review_snippet,
                bl.memo as full_review
            FROM review_embeddings re
            JOIN b_book_list bl ON re.book_id = bl.book_id AND re.user_id = bl.user_id
            WHERE re.user_id = ? 
                AND re.review_embedding IS NOT NULL
            ORDER BY bl.update_date DESC
            LIMIT 200
        ";
        
        $reviews = $this->db->getAll($sql, array($userId), DB_FETCHMODE_ASSOC);
        
        if (DB::isError($reviews) || count($reviews) === 0) {
            return [];
        }
        
        // embeddingをデコード
        $embeddings = [];
        $validReviews = [];
        foreach ($reviews as $review) {
            $embedding = json_decode($review['review_embedding'], true);
            if ($embedding && is_array($embedding)) {
                $embeddings[] = $embedding;
                $validReviews[] = $review;
            }
        }
        
        if (count($embeddings) < $numClusters) {
            $numClusters = max(1, count($embeddings));
        }
        
        // K-means++でクラスタリング
        $clusters = $this->kMeansPlusPlus($embeddings, $validReviews, $numClusters);
        
        // 各クラスタの特徴を分析
        return $this->analyzeClusterCharacteristics($clusters, $useLLM);
    }
    
    /**
     * K-means++アルゴリズムでクラスタリング
     */
    private function kMeansPlusPlus(array $embeddings, array $reviews, int $k): array {
        $n = count($embeddings);
        if ($n === 0 || $k === 0) return [];
        
        // 初期中心点を選択（K-means++）
        $centroids = [];
        $centroids[] = $embeddings[rand(0, $n - 1)];
        
        for ($c = 1; $c < $k; $c++) {
            $distances = [];
            for ($i = 0; $i < $n; $i++) {
                $minDist = INF;
                foreach ($centroids as $centroid) {
                    $dist = 1 - $this->cosineSimilarity($embeddings[$i], $centroid);
                    $minDist = min($minDist, $dist);
                }
                $distances[$i] = $minDist;
            }
            
            // 距離に基づいて確率的に次の中心点を選択
            $sum = array_sum($distances);
            if ($sum > 0) {
                $r = mt_rand() / mt_getrandmax() * $sum;
                $cumSum = 0;
                for ($i = 0; $i < $n; $i++) {
                    $cumSum += $distances[$i];
                    if ($cumSum >= $r) {
                        $centroids[] = $embeddings[$i];
                        break;
                    }
                }
            } else {
                $centroids[] = $embeddings[rand(0, $n - 1)];
            }
        }
        
        // クラスタ割り当てと中心点更新を繰り返す
        $maxIterations = 20;
        $assignments = array_fill(0, $n, 0);
        
        for ($iter = 0; $iter < $maxIterations; $iter++) {
            $changed = false;
            
            // 各点を最も近い中心点に割り当て
            for ($i = 0; $i < $n; $i++) {
                $minDist = INF;
                $bestCluster = 0;
                
                for ($c = 0; $c < $k; $c++) {
                    $dist = 1 - $this->cosineSimilarity($embeddings[$i], $centroids[$c]);
                    if ($dist < $minDist) {
                        $minDist = $dist;
                        $bestCluster = $c;
                    }
                }
                
                if ($assignments[$i] !== $bestCluster) {
                    $assignments[$i] = $bestCluster;
                    $changed = true;
                }
            }
            
            if (!$changed) break;
            
            // 中心点を更新
            for ($c = 0; $c < $k; $c++) {
                $clusterPoints = [];
                for ($i = 0; $i < $n; $i++) {
                    if ($assignments[$i] === $c) {
                        $clusterPoints[] = $embeddings[$i];
                    }
                }
                
                if (count($clusterPoints) > 0) {
                    // 平均を計算
                    $dim = count($embeddings[0]);
                    $newCentroid = array_fill(0, $dim, 0);
                    foreach ($clusterPoints as $point) {
                        for ($d = 0; $d < $dim; $d++) {
                            $newCentroid[$d] += $point[$d];
                        }
                    }
                    for ($d = 0; $d < $dim; $d++) {
                        $newCentroid[$d] /= count($clusterPoints);
                    }
                    $centroids[$c] = $newCentroid;
                }
            }
        }
        
        // クラスタを構築
        $clusters = array_fill(0, $k, []);
        for ($i = 0; $i < $n; $i++) {
            $clusters[$assignments[$i]][] = $reviews[$i];
        }
        
        return array_filter($clusters); // 空のクラスタを除去
    }
    
    /**
     * クラスタの特徴を分析
     */
    private function analyzeClusterCharacteristics(array $clusters, bool $useLLM = false): array {
        $result = [];
        
        foreach ($clusters as $idx => $books) {
            if (empty($books)) continue;
            
            // 評価の統計
            $ratings = array_column($books, 'rating');
            $avgRating = array_sum($ratings) / count($ratings);
            
            // レビューの特徴語を抽出（簡易版）
            $allText = implode(' ', array_column($books, 'full_review'));
            $keywords = $this->extractKeywords($allText);
            
            // LLMを使用した高度な分析を試みる（フラグがtrueの場合のみ）
            $llmAnalysis = null;
            if ($useLLM) {
                $llmAnalysis = $this->analyzeClustersWithLLM($books, $keywords, $avgRating);
            }
            
            // LLM分析が成功した場合はその結果を使用
            if ($llmAnalysis) {
                $clusterName = $llmAnalysis['name'];
                $description = $llmAnalysis['description'];
                $themes = $llmAnalysis['themes'];
                $reading_suggestions = $llmAnalysis['suggestions'];
            } else {
                // 従来のクラスタ名生成（高速）
                $clusterName = $this->generateClusterName($keywords, $avgRating);
                $description = null;
                $themes = [];
                $reading_suggestions = null;
            }
            
            $result[] = [
                'id' => $idx,
                'name' => $clusterName,
                'size' => count($books),
                'avg_rating' => round($avgRating, 1),
                'keywords' => array_slice($keywords, 0, 5),
                'books' => array_slice($books, 0, 5), // 代表的な本を5冊
                'description' => $description, // LLMによる説明
                'themes' => $themes, // LLMが抽出したテーマ
                'reading_suggestions' => $reading_suggestions, // LLMによる読書提案
                'characteristics' => [
                    'review_length_avg' => round(array_sum(array_map('strlen', array_column($books, 'full_review'))) / count($books)),
                    'rating_variance' => $this->calculateVariance($ratings)
                ]
            ];
        }
        
        return $result;
    }
    
    /**
     * テキストからキーワードを抽出（簡易版）
     */
    private function extractKeywords(string $text): array {
        // 簡易的な頻出語抽出
        $words = [];
        
        // 日本語の一般的な感想語をカウント
        $emotionWords = [
            '面白い', '面白かった', '感動', '感動した', '泣いた', '泣ける',
            '楽しい', '楽しかった', '難しい', '難しかった', '素晴らしい',
            '良い', '良かった', '最高', '素敵', 'おすすめ', 'オススメ',
            '深い', '考えさせられる', '勉強になった', 'わかりやすい',
            '読みやすい', '重い', '軽い', '暗い', '明るい', '切ない'
        ];
        
        foreach ($emotionWords as $word) {
            $count = mb_substr_count($text, $word);
            if ($count > 0) {
                $words[$word] = $count;
            }
        }
        
        arsort($words);
        return array_keys($words);
    }
    
    /**
     * クラスタ名を自動生成
     */
    private function generateClusterName(array $keywords, float $avgRating): string {
        if (empty($keywords)) {
            if ($avgRating >= 4.5) return "高評価作品群";
            if ($avgRating >= 3.5) return "好評作品群";
            if ($avgRating >= 2.5) return "普通評価作品群";
            return "その他作品群";
        }
        
        $keyword = $keywords[0];
        
        // キーワードに基づいた名前生成
        $nameMap = [
            '感動' => '感動系作品',
            '泣ける' => '感動系作品',
            '面白い' => 'エンターテイメント系',
            '難しい' => '挑戦的作品',
            '勉強になった' => '学習・教養系',
            'わかりやすい' => '入門・解説系',
            '考えさせられる' => '思索的作品',
            '深い' => '深遠な作品',
            '切ない' => '切ない物語',
            '楽しい' => '楽しい読書体験'
        ];
        
        return $nameMap[$keyword] ?? "「{$keyword}」系作品";
    }
    
    /**
     * LLMを使用してクラスタを分析
     */
    private function analyzeClustersWithLLM(array $books, array $keywords, float $avgRating): ?array {
        // OpenAI APIキーが存在しない場合はnullを返す
        if (!defined('OPENAI_API_KEY') || empty(OPENAI_API_KEY)) {
            return null;
        }
        
        try {
            // クラスタ内の本の情報を整理
            $bookTitles = array_slice(array_column($books, 'title'), 0, 10);
            $authors = array_unique(array_filter(array_column($books, 'author')));
            $reviewSnippets = array_slice(array_filter(array_column($books, 'review_snippet')), 0, 3);
            
            // プロンプトを構築
            $prompt = $this->buildLLMPrompt($bookTitles, $authors, $reviewSnippets, $keywords, $avgRating);
            
            // OpenAI APIを呼び出し
            $response = $this->callOpenAI($prompt);
            
            if ($response) {
                // JSONレスポンスをパース
                $analysis = json_decode($response, true);
                if ($analysis && isset($analysis['name'])) {
                    return $analysis;
                }
            }
        } catch (Exception $e) {
            error_log("LLM analysis failed: " . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * LLM用のプロンプトを構築
     */
    private function buildLLMPrompt(array $titles, array $authors, array $reviews, array $keywords, float $avgRating): string {
        $titlesStr = implode("\n- ", array_slice($titles, 0, 10));
        $authorsStr = implode(", ", array_slice($authors, 0, 5));
        $reviewsStr = implode("\n\n", array_slice($reviews, 0, 3));
        $keywordsStr = implode(", ", array_slice($keywords, 0, 10));
        
        return <<< PROMPT
以下の本のグループを分析して、魅力的なクラスタ名と説明を生成してください。

【本のタイトル】
- $titlesStr

【著者】
$authorsStr

【レビューの例】
$reviewsStr

【频出キーワード】
$keywordsStr

【平均評価】
$avgRating / 5.0

以下のJSON形式で回答してください：
{
    "name": "クラスタ名（15文字以内、具体的で魅力的な名前）",
    "description": "このグループの特徴を説明（50文字以内）",
    "themes": ["テーマ1", "テーマ2", "テーマ3"],
    "suggestions": "このグループが好きな人への読書提案（30文字以内）"
}

注意：
- クラスタ名は「〜系」「〜群」という形式を避け、より創造的な名前を付けてください
- 本の内容や読者の感想から本質を捉えてください
PROMPT;
    }
    
    /**
     * OpenAI APIを呼び出し
     */
    private function callOpenAI(string $prompt): ?string {
        $url = 'https://api.openai.com/v1/chat/completions';
        $headers = [
            'Authorization: Bearer ' . OPENAI_API_KEY,
            'Content-Type: application/json'
        ];
        
        $data = [
            'model' => 'gpt-4o-mini',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'あなたは読書分析の専門家です。本のグループを分析し、洞察に富んだ分析結果を提供します。'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.7,
            'max_tokens' => 500,
            'response_format' => ['type' => 'json_object']
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $result = json_decode($response, true);
            if (isset($result['choices'][0]['message']['content'])) {
                return $result['choices'][0]['message']['content'];
            }
        }
        
        return null;
    }
    
    /**
     * 分散を計算
     */
    private function calculateVariance(array $values): float {
        $n = count($values);
        if ($n === 0) return 0;
        
        $mean = array_sum($values) / $n;
        $variance = 0;
        
        foreach ($values as $value) {
            $variance += pow($value - $mean, 2);
        }
        
        return $variance / $n;
    }
    
    /**
     * レビューの多様性をembeddingベースで計算
     */
    public function calculateEmbeddingDiversity(int $userId): float {
        $sql = "
            SELECT review_embedding
            FROM review_embeddings
            WHERE user_id = ? AND review_embedding IS NOT NULL
            LIMIT 100
        ";
        
        $results = $this->db->getAll($sql, array($userId), DB_FETCHMODE_ASSOC);
        
        if (DB::isError($results) || count($results) < 2) {
            return 0;
        }
        
        $embeddings = [];
        foreach ($results as $result) {
            $embedding = json_decode($result['review_embedding'], true);
            if ($embedding) {
                $embeddings[] = $embedding;
            }
        }
        
        if (count($embeddings) < 2) {
            return 0;
        }
        
        // 全ペアの類似度の平均を計算（多様性は1-類似度）
        $similarities = [];
        for ($i = 0; $i < count($embeddings) - 1; $i++) {
            for ($j = $i + 1; $j < count($embeddings); $j++) {
                $similarities[] = $this->cosineSimilarity($embeddings[$i], $embeddings[$j]);
            }
        }
        
        $avgSimilarity = array_sum($similarities) / count($similarities);
        
        // 多様性スコア（0-100）
        return (1 - $avgSimilarity) * 100;
    }
}
?>