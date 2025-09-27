<?php
/**
 * X API v2 OAuth 1.0a implementation
 * Updated for current X API requirements
 */

class XOAuthV2 {
    private $consumer_key;
    private $consumer_secret;
    private $access_token;
    private $access_token_secret;
    
    public function __construct($consumer_key, $consumer_secret, $access_token, $access_token_secret) {
        $this->consumer_key = $consumer_key;
        $this->consumer_secret = $consumer_secret;
        $this->access_token = $access_token;
        $this->access_token_secret = $access_token_secret;
    }
    
    /**
     * Post a tweet using X API v2 with OAuth 1.0a
     */
    public function postTweet($text) {
        $url = 'https://api.twitter.com/2/tweets';
        $method = 'POST';
        
        // Truncate if too long
        if (mb_strlen($text) > 280) {
            $text = mb_substr($text, 0, 277) . '...';
        }
        
        // Request body (JSON)
        $body = json_encode(['text' => $text]);
        
        // OAuth parameters (no body parameters for OAuth 1.0a with JSON body)
        $oauth = [
            'oauth_consumer_key' => $this->consumer_key,
            'oauth_nonce' => $this->generateNonce(),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => time(),
            'oauth_token' => $this->access_token,
            'oauth_version' => '1.0'
        ];
        
        // Generate signature
        $oauth['oauth_signature'] = $this->buildSignature($method, $url, $oauth);
        
        // Build Authorization header
        $header = $this->buildAuthorizationHeader($oauth);
        
        // Make request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            $header,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($httpCode === 201) {
            error_log('[X OAuth v2] Tweet posted successfully');
            $result = json_decode($response, true);
            return ['success' => true, 'data' => $result];
        } else {
            error_log('[X OAuth v2] Failed to post tweet. HTTP: ' . $httpCode . ', Response: ' . $response);
            
            $error_message = 'HTTPステータス: ' . $httpCode;
            
            if ($error) {
                error_log('[X OAuth v2] CURL Error: ' . $error);
                $error_message .= ', CURLエラー: ' . $error;
            }
            
            // Parse error details
            $error_data = json_decode($response, true);
            if (isset($error_data['errors'])) {
                $api_errors = [];
                foreach ($error_data['errors'] as $api_error) {
                    $msg = ($api_error['message'] ?? 'Unknown') . ' (Code: ' . ($api_error['code'] ?? 'N/A') . ')';
                    error_log('[X OAuth v2] API Error: ' . $msg);
                    $api_errors[] = $msg;
                }
                $error_message .= ', APIエラー: ' . implode(', ', $api_errors);
            } elseif (isset($error_data['error'])) {
                $error_message .= ', エラー: ' . $error_data['error'];
            } elseif (isset($error_data['detail'])) {
                $error_message .= ', 詳細: ' . $error_data['detail'];
            }
            
            // Common error patterns
            if ($httpCode === 401) {
                $error_message .= ' (認証エラー: APIキーまたはトークンが無効です)';
            } elseif ($httpCode === 403) {
                $error_message .= ' (権限エラー: アプリの権限設定を確認してください)';
            } elseif ($httpCode === 429) {
                $error_message .= ' (レート制限: しばらく待ってから再試行してください)';
            }
            
            return ['success' => false, 'error' => $error_message, 'http_code' => $httpCode, 'response' => $response];
        }
    }
    
    private function generateNonce() {
        return md5(microtime() . mt_rand());
    }
    
    private function buildSignature($method, $url, $oauth) {
        // Sort OAuth parameters
        ksort($oauth);
        
        // Build parameter string (OAuth params only for JSON body requests)
        $param_parts = [];
        foreach ($oauth as $key => $value) {
            $param_parts[] = rawurlencode($key) . '=' . rawurlencode((string)$value);
        }
        $param_string = implode('&', $param_parts);
        
        // Build signature base string
        $signature_base = $method . '&' . rawurlencode($url) . '&' . rawurlencode($param_string);
        
        // Build signing key
        $signing_key = rawurlencode($this->consumer_secret) . '&' . rawurlencode($this->access_token_secret);
        
        // Generate signature
        return base64_encode(hash_hmac('sha1', $signature_base, $signing_key, true));
    }
    
    private function buildAuthorizationHeader($oauth) {
        $header_parts = [];
        foreach ($oauth as $key => $value) {
            $header_parts[] = rawurlencode($key) . '="' . rawurlencode((string)$value) . '"';
        }
        return 'Authorization: OAuth ' . implode(', ', $header_parts);
    }
}