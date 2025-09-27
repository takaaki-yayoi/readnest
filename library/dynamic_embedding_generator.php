<?php
/**
 * 動的Embedding生成ライブラリ
 * OpenAI APIを使用してリアルタイムでembeddingを生成
 */

class DynamicEmbeddingGenerator {
    
    private $api_key;
    private $model = 'text-embedding-3-small'; // 1536次元
    
    public function __construct() {
        // config.phpからAPIキーを取得
        if (defined('OPENAI_API_KEY')) {
            $this->api_key = OPENAI_API_KEY;
        } else {
            error_log("OpenAI API key not found in config");
        }
    }
    
    /**
     * 本の情報からembeddingを生成
     * @param array $book_info 本の情報（title, author, description等）
     * @return string|null JSON形式のembedding、失敗時はnull
     */
    public function generateBookEmbedding($book_info) {
        if (empty($this->api_key)) {
            error_log("OpenAI API key is not set");
            return null;
        }
        
        // embeddingのためのテキストを構築
        $text = $this->buildEmbeddingText($book_info);
        
        if (empty($text)) {
            error_log("No text to generate embedding from");
            return null;
        }
        
        // OpenAI APIを呼び出し
        $embedding = $this->callOpenAIAPI($text);
        
        if ($embedding) {
            // 生成したembeddingをDBに保存（キャッシュ）
            $this->cacheEmbedding($book_info, $embedding);
        }
        
        return $embedding;
    }
    
    /**
     * Embedding用のテキストを構築
     * @param array $book_info 本の情報
     * @return string embedding用のテキスト
     */
    private function buildEmbeddingText($book_info) {
        $parts = [];
        
        // タイトル（必須）
        if (!empty($book_info['title'])) {
            $parts[] = "Title: " . $book_info['title'];
        }
        
        // 著者
        if (!empty($book_info['author'])) {
            $parts[] = "Author: " . $book_info['author'];
        }
        
        // 説明文（最も重要）
        if (!empty($book_info['description'])) {
            // 説明文が長すぎる場合は切り詰める（トークン制限対策）
            $description = mb_substr($book_info['description'], 0, 2000);
            $parts[] = "Description: " . $description;
        }
        
        // ジャンル/カテゴリ
        if (!empty($book_info['google_categories'])) {
            $parts[] = "Categories: " . $book_info['google_categories'];
        }
        
        // タグ
        if (!empty($book_info['tags'])) {
            if (is_array($book_info['tags'])) {
                $parts[] = "Tags: " . implode(', ', $book_info['tags']);
            } else {
                $parts[] = "Tags: " . $book_info['tags'];
            }
        }
        
        // 日本語と英語のバランスを保つ
        return implode("\n", $parts);
    }
    
    /**
     * OpenAI APIを呼び出してembeddingを取得
     * @param string $text embedding対象のテキスト
     * @return string|null JSON形式のembedding
     */
    private function callOpenAIAPI($text) {
        $url = 'https://api.openai.com/v1/embeddings';
        
        $data = [
            'input' => $text,
            'model' => $this->model,
            'encoding_format' => 'float'
        ];
        
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->api_key
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code !== 200) {
            error_log("OpenAI API error: HTTP $http_code - $response");
            return null;
        }
        
        $result = json_decode($response, true);
        
        if (isset($result['data'][0]['embedding'])) {
            // embedding配列をJSON形式で返す
            return json_encode($result['data'][0]['embedding']);
        } else {
            error_log("Unexpected OpenAI API response format: " . $response);
            return null;
        }
    }
    
    /**
     * 生成したembeddingをDBにキャッシュ
     * @param array $book_info 本の情報
     * @param string $embedding JSON形式のembedding
     */
    private function cacheEmbedding($book_info, $embedding) {
        global $g_db;
        
        // ASINがある場合はb_book_repositoryに保存
        if (!empty($book_info['asin'])) {
            // 説明文の有無を判定
            $has_description = !empty($book_info['description']) ? 1 : 0;
            $embedding_type = $has_description ? 'dynamic_with_desc' : 'dynamic_no_desc';
            
            $sql = "UPDATE b_book_repository 
                    SET combined_embedding = ?,
                        embedding_has_description = ?,
                        embedding_generated_at = NOW(),
                        embedding_type = ?
                    WHERE asin = ?";
            
            $result = $g_db->query($sql, [
                $embedding, 
                $has_description,
                $embedding_type,
                $book_info['asin']
            ]);
            
            if (DB::isError($result)) {
                error_log("Failed to cache embedding: " . $result->getMessage());
            } else {
                error_log("Embedding cached for ASIN: " . $book_info['asin'] . 
                         " (has_description: $has_description)");
            }
        }
    }
    
    /**
     * バッチ処理で複数の本のembeddingを生成
     * @param array $books 本の情報の配列
     * @param int $delay_ms APIコール間の遅延（ミリ秒）
     * @return array 成功/失敗の結果
     */
    public function generateBatchEmbeddings($books, $delay_ms = 500) {
        $results = [
            'success' => 0,
            'failed' => 0,
            'details' => []
        ];
        
        foreach ($books as $book) {
            $embedding = $this->generateBookEmbedding($book);
            
            if ($embedding) {
                $results['success']++;
                $results['details'][] = [
                    'asin' => $book['asin'] ?? 'unknown',
                    'title' => $book['title'] ?? 'unknown',
                    'status' => 'success'
                ];
            } else {
                $results['failed']++;
                $results['details'][] = [
                    'asin' => $book['asin'] ?? 'unknown',
                    'title' => $book['title'] ?? 'unknown',
                    'status' => 'failed'
                ];
            }
            
            // API rate limitを考慮して遅延
            usleep($delay_ms * 1000);
        }
        
        return $results;
    }
}

/**
 * 動的にembeddingを取得または生成
 * @param array $book_info 本の情報
 * @return string|null embedding（JSON形式）
 */
function getOrGenerateEmbedding($book_info) {
    global $g_db;
    
    // 1. まずDBから既存のembeddingを確認
    if (!empty($book_info['asin'])) {
        $sql = "SELECT combined_embedding FROM b_book_repository WHERE asin = ?";
        $existing = $g_db->getOne($sql, [$book_info['asin']]);
        
        if (!DB::isError($existing) && !empty($existing)) {
            return $existing;
        }
    }
    
    // 2. 存在しない場合は動的生成
    $generator = new DynamicEmbeddingGenerator();
    return $generator->generateBookEmbedding($book_info);
}

/**
 * 説明文なしで生成されたembeddingを更新する必要があるかチェック
 * @param string $asin 本のASIN
 * @return bool 更新が必要な場合true
 */
function needsEmbeddingUpdate($asin) {
    global $g_db;
    
    $sql = "SELECT embedding_has_description, embedding_type, description 
            FROM b_book_repository 
            WHERE asin = ?";
    
    $result = $g_db->getRow($sql, [$asin], DB_FETCHMODE_ASSOC);
    
    if (DB::isError($result) || !$result) {
        return false;
    }
    
    // 説明文なしで生成され、今は説明文がある場合は更新が必要
    if ($result['embedding_has_description'] == 0 && 
        !empty($result['description']) &&
        in_array($result['embedding_type'], ['dynamic_no_desc'])) {
        return true;
    }
    
    return false;
}

/**
 * バックグラウンド処理用：説明文込みでembeddingを再生成
 * @param string $asin 本のASIN
 * @return bool 成功した場合true
 */
function regenerateEmbeddingWithDescription($asin) {
    global $g_db;
    
    // 本の情報を取得
    $sql = "SELECT asin, title, author, description, google_categories 
            FROM b_book_repository 
            WHERE asin = ?";
    
    $book_info = $g_db->getRow($sql, [$asin], DB_FETCHMODE_ASSOC);
    
    if (DB::isError($book_info) || !$book_info || empty($book_info['description'])) {
        return false;
    }
    
    // embeddingを再生成
    $generator = new DynamicEmbeddingGenerator();
    $new_embedding = $generator->generateBookEmbedding($book_info);
    
    if ($new_embedding) {
        // フラグを更新（embedding_typeをbackgroundに）
        $update_sql = "UPDATE b_book_repository 
                      SET combined_embedding = ?,
                          embedding_has_description = 1,
                          embedding_generated_at = NOW(),
                          embedding_type = 'background'
                      WHERE asin = ?";
        
        $result = $g_db->query($update_sql, [$new_embedding, $asin]);
        
        if (!DB::isError($result)) {
            error_log("Embedding regenerated with description for ASIN: $asin");
            return true;
        }
    }
    
    return false;
}
?>