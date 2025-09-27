<?php
/**
 * OpenAI API クライアントライブラリ
 * PHP 8.2.28対応版
 */

declare(strict_types=1);

class OpenAIClient {
    private string $apiKey;
    private string $apiUrl = 'https://api.openai.com/v1';
    private int $timeout = 30;
    
    public function __construct(string $apiKey) {
        if (empty($apiKey)) {
            throw new Exception('OpenAI API key is required');
        }
        $this->apiKey = $apiKey;
    }
    
    /**
     * ChatGPT APIを呼び出す
     * 
     * @param string $prompt ユーザーからのプロンプト
     * @param string $model 使用するモデル (default: gpt-4o-mini)
     * @param float $temperature 創造性の度合い (0-2, default: 0.7)
     * @param int $maxTokens 最大トークン数
     * @return array レスポンスデータ
     */
    public function chat(
        string $prompt, 
        string $model = 'gpt-4o-mini',
        float $temperature = 0.7,
        int $maxTokens = 1000
    ): array {
        $messages = [
            ['role' => 'user', 'content' => $prompt]
        ];
        
        return $this->chatCompletion($messages, $model, $temperature, $maxTokens);
    }
    
    /**
     * システムプロンプト付きでChatGPT APIを呼び出す
     * 
     * @param string $systemPrompt システムプロンプト
     * @param string $userPrompt ユーザープロンプト
     * @param string $model 使用するモデル
     * @param float $temperature 創造性の度合い
     * @param int $maxTokens 最大トークン数
     * @return array レスポンスデータ
     */
    public function chatWithSystem(
        string $systemPrompt,
        string $userPrompt,
        string $model = 'gpt-4o-mini',
        float $temperature = 0.7,
        int $maxTokens = 1000
    ): array {
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt]
        ];
        
        return $this->chatCompletion($messages, $model, $temperature, $maxTokens);
    }
    
    /**
     * Chat Completion APIを呼び出す
     * 
     * @param array $messages メッセージの配列
     * @param string $model 使用するモデル
     * @param float $temperature 創造性の度合い
     * @param int $maxTokens 最大トークン数
     * @return array レスポンスデータ
     */
    private function chatCompletion(
        array $messages,
        string $model,
        float $temperature,
        int $maxTokens
    ): array {
        $data = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => $temperature,
            'max_tokens' => $maxTokens
        ];
        
        $response = $this->makeRequest('/chat/completions', $data);
        
        if (isset($response['error'])) {
            throw new Exception('OpenAI API Error: ' . $response['error']['message']);
        }
        
        return $response;
    }
    
    /**
     * Embeddings APIを呼び出す（類似性検索用）
     * 
     * @param string $text エンベディングを生成するテキスト
     * @param string $model 使用するモデル (default: text-embedding-3-small)
     * @param int|null $dimensions 次元数（text-embedding-3モデルのみ）
     * @return array エンベディングベクトルまたは完全なレスポンス
     */
    public function createEmbedding(
        string $text,
        string $model = 'text-embedding-3-small',
        ?int $dimensions = null
    ): array {
        $data = [
            'model' => $model,
            'input' => $text
        ];
        
        // text-embedding-3モデルの場合、次元数を指定可能
        if ($dimensions !== null && strpos($model, 'text-embedding-3') === 0) {
            $data['dimensions'] = $dimensions;
        }
        
        $response = $this->makeRequest('/embeddings', $data);
        
        if (isset($response['error'])) {
            throw new Exception('OpenAI API Error: ' . $response['error']['message']);
        }
        
        // 完全なレスポンスを返す（後方互換性のため）
        // 既存のコードは response['data'][0]['embedding'] でアクセス
        return $response;
    }
    
    /**
     * APIリクエストを実行
     * 
     * @param string $endpoint エンドポイント
     * @param array $data リクエストデータ
     * @return array レスポンスデータ
     */
    private function makeRequest(string $endpoint, array $data): array {
        $ch = curl_init($this->apiUrl . $endpoint);
        
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->apiKey,
                'Content-Type: application/json'
            ]
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception('CURL Error: ' . $error);
        }
        
        if ($httpCode !== 200) {
            $errorData = json_decode($response, true);
            throw new Exception('HTTP Error ' . $httpCode . ': ' . ($errorData['error']['message'] ?? 'Unknown error'));
        }
        
        return json_decode($response, true);
    }
    
    /**
     * レスポンスからテキストを抽出
     * 
     * @param array $response ChatGPTのレスポンス
     * @return string 生成されたテキスト
     */
    public static function extractText(array $response): string {
        return $response['choices'][0]['message']['content'] ?? '';
    }
    
    /**
     * 使用トークン数を取得
     * 
     * @param array $response ChatGPTのレスポンス
     * @return int 使用トークン数
     */
    public static function getUsedTokens(array $response): int {
        return $response['usage']['total_tokens'] ?? 0;
    }
}

/**
 * グローバルなOpenAIクライアントインスタンスを取得
 * 
 * @return OpenAIClient|null
 */
function getOpenAIClient(): ?OpenAIClient {
    static $client = null;
    
    if ($client === null) {
        // 環境変数からAPIキーを取得
        $apiKey = getenv('OPENAI_API_KEY') ?: '';
        
        // config.phpで定義されている場合はそちらを使用
        if (defined('OPENAI_API_KEY')) {
            $apiKey = OPENAI_API_KEY;
        }
        
        if (!empty($apiKey)) {
            try {
                $client = new OpenAIClient($apiKey);
            } catch (Exception $e) {
                error_log('Failed to initialize OpenAI client: ' . $e->getMessage());
                return null;
            }
        }
    }
    
    return $client;
}