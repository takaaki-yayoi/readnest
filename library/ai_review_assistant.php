<?php
/**
 * AI書評アシスタントライブラリ
 * PHP 8.2.28対応版
 */

declare(strict_types=1);

require_once(dirname(__FILE__) . '/openai_client.php');

class AIReviewAssistant {
    private ?OpenAIClient $client;
    
    public function __construct() {
        $this->client = getOpenAIClient();
    }
    
    /**
     * AIが書評の作成を支援
     * 
     * @param array $bookInfo 本の情報 (title, author, etc.)
     * @param string $userInput ユーザーの感想やキーワード
     * @param int $rating 評価（1-5）
     * @return array 生成された書評と関連情報
     */
    public function generateReview(array $bookInfo, string $userInput, int $rating): array {
        if (!$this->client) {
            return [
                'success' => false,
                'error' => 'OpenAI client is not available'
            ];
        }
        
        try {
            $systemPrompt = $this->createReviewSystemPrompt();
            $userPrompt = $this->createReviewUserPrompt($bookInfo, $userInput, $rating);
            
            $response = $this->client->chatWithSystem(
                $systemPrompt,
                $userPrompt,
                'gpt-4o-mini',
                0.7,
                800
            );
            
            $reviewText = OpenAIClient::extractText($response);
            
            return [
                'success' => true,
                'review' => $reviewText,
                'tokens_used' => OpenAIClient::getUsedTokens($response),
                'original_input' => $userInput
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 書評を改善・洗練する
     * 
     * @param string $originalReview 元の書評
     * @param string $improveDirection 改善の方向性（もっと詳しく、もっと簡潔に、など）
     * @return array 改善された書評
     */
    public function improveReview(string $originalReview, string $improveDirection = ''): array {
        if (!$this->client) {
            return [
                'success' => false,
                'error' => 'OpenAI client is not available'
            ];
        }
        
        try {
            $systemPrompt = "あなたは優れた書評編集者です。読者の書評を改善し、より魅力的で読みやすいものにしてください。";
            
            $userPrompt = "以下の書評を改善してください。\n\n";
            if (!empty($improveDirection)) {
                $userPrompt .= "改善の方向性: {$improveDirection}\n\n";
            }
            $userPrompt .= "元の書評:\n{$originalReview}\n\n";
            $userPrompt .= "改善のポイント:\n";
            $userPrompt .= "- 文章の流れを自然にする\n";
            $userPrompt .= "- 具体的な例や印象的な場面を含める\n";
            $userPrompt .= "- 読者が興味を持つような書き出しにする\n";
            $userPrompt .= "- 元の意図や感情は保持する";
            
            $response = $this->client->chatWithSystem(
                $systemPrompt,
                $userPrompt,
                'gpt-4o-mini',
                0.6,
                800
            );
            
            $improvedReview = OpenAIClient::extractText($response);
            
            return [
                'success' => true,
                'improved_review' => $improvedReview,
                'tokens_used' => OpenAIClient::getUsedTokens($response)
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 書評からタグを自動生成
     * 
     * @param string $review 書評テキスト
     * @param array $bookInfo 本の情報
     * @return array 生成されたタグ
     */
    public function generateTags(string $review, array $bookInfo = []): array {
        if (!$this->client) {
            return [
                'success' => false,
                'error' => 'OpenAI client is not available'
            ];
        }
        
        try {
            $systemPrompt = "あなたは本のタグ付けの専門家です。書評や本の情報から適切なタグを生成してください。";
            
            $userPrompt = "以下の書評から、この本を表す適切なタグを5-8個生成してください。\n\n";
            if (!empty($bookInfo['title'])) {
                $userPrompt .= "本のタイトル: {$bookInfo['title']}\n";
            }
            if (!empty($bookInfo['author'])) {
                $userPrompt .= "著者: {$bookInfo['author']}\n";
            }
            $userPrompt .= "\n書評:\n{$review}\n\n";
            $userPrompt .= "タグの条件:\n";
            $userPrompt .= "- ジャンル（ミステリー、恋愛、SF等）\n";
            $userPrompt .= "- テーマ（友情、成長、冒険等）\n";
            $userPrompt .= "- 雰囲気（感動的、スリリング、ほのぼの等）\n";
            $userPrompt .= "- 読者層（初心者向け、専門的等）\n";
            $userPrompt .= "カンマ区切りでタグのみを出力してください。";
            
            $response = $this->client->chatWithSystem(
                $systemPrompt,
                $userPrompt,
                'gpt-4o-mini',
                0.5,
                100
            );
            
            $tagsText = OpenAIClient::extractText($response);
            $tags = array_map('trim', explode(',', $tagsText));
            $tags = array_filter($tags); // 空のタグを除去
            
            return [
                'success' => true,
                'tags' => $tags,
                'tokens_used' => OpenAIClient::getUsedTokens($response)
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 書評作成用のシステムプロンプトを生成
     */
    private function createReviewSystemPrompt(): string {
        return "あなたは読書愛好家のための書評作成アシスタントです。
ユーザーの感想やメモを基に、魅力的で読みやすい書評を作成してください。

書評作成の原則:
1. ユーザーの感情や意見を尊重し、それを軸に展開する
2. ネタバレは避ける（重要な展開は明かさない）
3. 具体的な印象や場面を含める
4. 読んでみたくなるような魅力的な表現を使う
5. 400-600文字程度にまとめる
6. 日本語で自然な文章にする";
    }
    
    /**
     * 書評作成用のユーザープロンプトを生成
     */
    private function createReviewUserPrompt(array $bookInfo, string $userInput, int $rating): string {
        $prompt = "以下の情報を基に書評を作成してください。\n\n";
        
        // 本の情報
        if (!empty($bookInfo['title'])) {
            $prompt .= "本のタイトル: {$bookInfo['title']}\n";
        }
        if (!empty($bookInfo['author'])) {
            $prompt .= "著者: {$bookInfo['author']}\n";
        }
        
        // 評価
        $ratingStars = str_repeat('★', $rating) . str_repeat('☆', 5 - $rating);
        $prompt .= "評価: {$ratingStars}\n\n";
        
        // ユーザーの入力
        $prompt .= "読者の感想・メモ:\n{$userInput}\n\n";
        
        // 書評作成の指示
        $prompt .= "上記の感想を基に、他の読者にとって参考になる書評を作成してください。";
        
        return $prompt;
    }
    
    /**
     * 複数の書評を要約
     * 
     * @param array $reviews 書評の配列
     * @return array 要約結果
     */
    public function summarizeReviews(array $reviews): array {
        if (!$this->client) {
            return [
                'success' => false,
                'error' => 'OpenAI client is not available'
            ];
        }
        
        if (empty($reviews)) {
            return [
                'success' => false,
                'error' => 'No reviews to summarize'
            ];
        }
        
        try {
            $systemPrompt = "あなたは書評分析の専門家です。複数の書評から共通点や重要なポイントを抽出し、簡潔にまとめてください。";
            
            $reviewsText = "";
            foreach ($reviews as $i => $review) {
                $reviewsText .= "書評" . ($i + 1) . ":\n" . $review . "\n\n";
            }
            
            $userPrompt = "以下の書評を分析し、要約してください。\n\n{$reviewsText}";
            $userPrompt .= "要約には以下を含めてください:\n";
            $userPrompt .= "- 共通して評価されているポイント\n";
            $userPrompt .= "- 賛否が分かれているポイント\n";
            $userPrompt .= "- 特に印象的な意見\n";
            $userPrompt .= "- 総合的な評価\n";
            
            $response = $this->client->chatWithSystem(
                $systemPrompt,
                $userPrompt,
                'gpt-4o-mini',
                0.5,
                500
            );
            
            $summary = OpenAIClient::extractText($response);
            
            return [
                'success' => true,
                'summary' => $summary,
                'review_count' => count($reviews),
                'tokens_used' => OpenAIClient::getUsedTokens($response)
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}