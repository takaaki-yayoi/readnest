<?php
/**
 * Enhanced Book Image Helper
 * 本の画像URLの検証と複数のフォールバック戦略を提供
 * PHP 8.2.28対応
 */

// declare(strict_types=1);

if(!defined('CONFIG')) {
    error_log('direct access detected. file:' . __FILE__ . ' line:' . __LINE__ . ' ip:' . $_SERVER['REMOTE_ADDR'] . ' agent:' . $_SERVER['HTTP_USER_AGENT']);
    die('reference for this file is not allowed.');
}

// キャッシュ機能を利用
require_once(dirname(__FILE__) . '/cache.php');

class BookImageHelper {
    
    private $cache;
    private $timeout;
    private $cacheTtl;
    private $userAgent;
    
    /**
     * コンストラクタ
     * 
     * @param int $timeout HTTP リクエストのタイムアウト（秒）
     * @param int $cacheTtl キャッシュの有効期限（秒）
     */
    public function __construct(int $timeout = 10, int $cacheTtl = 86400) {
        $this->cache = getCache();
        $this->timeout = $timeout;
        $this->cacheTtl = $cacheTtl; // デフォルト24時間
        $this->userAgent = 'ReadNest BookImageHelper/1.0 (https://readnest.jp)';
    }
    
    /**
     * 画像URLの有効性をチェック
     * キャッシュを使用して重複チェックを避ける
     * 
     * @param string $url 画像URL
     * @return bool URLが有効かどうか
     */
    public function isImageUrlValid(string $url): bool {
        if (empty($url)) {
            return false;
        }
        
        // URLの基本的な妥当性チェック
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }
        
        // キャッシュキーを生成
        $cacheKey = 'image_valid_' . md5($url);
        
        // キャッシュから結果を取得
        $cachedResult = $this->cache->get($cacheKey);
        if ($cachedResult !== false) {
            return (bool)$cachedResult;
        }
        
        // 主要な画像サービスのURLは信頼して高速化
        if (strpos($url, 'books.google.com') !== false || 
            strpos($url, 'covers.openlibrary.org') !== false ||
            strpos($url, 'iss.ndl.go.jp') !== false) {
            $this->cache->set($cacheKey, true, $this->cacheTtl);
            return true;
        }
        
        // その他のURLはHTTP HEADリクエストで画像の存在確認
        $isValid = $this->checkImageExists($url);
        
        // 結果をキャッシュ（有効な場合は長めに、無効な場合は短めに）
        $ttl = $isValid ? $this->cacheTtl : 3600; // 無効な場合は1時間
        $this->cache->set($cacheKey, $isValid, $ttl);
        
        return $isValid;
    }
    
    /**
     * HTTP HEADリクエストで画像の存在を確認
     * 
     * @param string $url 画像URL
     * @return bool
     */
    private function checkImageExists(string $url): bool {
        try {
            // HEADリクエストのコンテキストを作成
            $context = stream_context_create([
                'http' => [
                    'method' => 'HEAD',
                    'timeout' => $this->timeout,
                    'user_agent' => $this->userAgent,
                    'follow_location' => true,
                    'max_redirects' => 3,
                    'ignore_errors' => false
                ],
                'https' => [
                    'method' => 'HEAD',
                    'timeout' => $this->timeout,
                    'user_agent' => $this->userAgent,
                    'follow_location' => true,
                    'max_redirects' => 3,
                    'ignore_errors' => false
                ]
            ]);
            
            // HEADリクエストで画像の存在を確認
            $headers = @file_get_contents($url, false, $context, 0, 0);
            
            // レスポンスヘッダーを確認
            if (isset($http_response_header) && is_array($http_response_header)) {
                foreach ($http_response_header as $header) {
                    // ステータスコードを確認
                    if (preg_match('/HTTP\/\d+\.\d+\s+(\d+)/', $header, $matches)) {
                        $statusCode = (int)$matches[1];
                        if ($statusCode < 200 || $statusCode >= 300) {
                            return false;
                        }
                    }
                    // Content-Typeを確認
                    if (stripos($header, 'Content-Type:') === 0) {
                        $contentType = trim(substr($header, 13));
                        if (strpos($contentType, 'image/') !== 0) {
                            return false;
                        }
                    }
                }
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("BookImageHelper: Error checking image URL {$url}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * OpenLibraryの画像が有効かチェック
     * @param string $url OpenLibraryの画像URL
     * @return bool 有効な画像か
     */
    private function checkOpenLibraryImage(string $url): bool {
        try {
            // 画像の最初の数バイトを取得
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'timeout' => $this->timeout,
                    'user_agent' => $this->userAgent,
                    'follow_location' => true,
                    'max_redirects' => 3,
                    'header' => "Range: bytes=0-1024\r\n"
                ],
                'https' => [
                    'method' => 'GET',
                    'timeout' => $this->timeout,
                    'user_agent' => $this->userAgent,
                    'follow_location' => true,
                    'max_redirects' => 3,
                    'header' => "Range: bytes=0-1024\r\n"
                ]
            ]);
            
            $data = @file_get_contents($url, false, $context, 0, 1024);
            
            if ($data === false) {
                return false;
            }
            
            // レスポンスヘッダーをチェック
            if (isset($http_response_header) && is_array($http_response_header)) {
                $contentLength = 0;
                foreach ($http_response_header as $header) {
                    if (stripos($header, 'content-type:') === 0) {
                        // 画像のContent-Typeかチェック
                        if (!preg_match('/image\/(jpeg|jpg|png|gif)/i', $header)) {
                            return false;
                        }
                    }
                    // Content-Lengthを取得
                    if (stripos($header, 'content-length:') === 0) {
                        $contentLength = (int)trim(substr($header, 15));
                    }
                }
                
                // Content-Lengthが小さすぎる場合（2KB未満）はプレースホルダー画像の可能性
                if ($contentLength > 0 && $contentLength < 2000) {
                    return false;
                }
            }
            
            // 画像データの簡易チェック（JPEGまたはPNGのマジックナンバー）
            if (strlen($data) >= 4) {
                $hex = bin2hex(substr($data, 0, 4));
                
                // JPEG: FFD8FF, PNG: 89504E47
                if (substr($hex, 0, 6) === 'ffd8ff' || $hex === '89504e47') {
                    // 追加チェック：黒一色の画像でないか確認（最初の100バイトがほぼ同じ値でないか）
                    if (strlen($data) >= 100) {
                        $bytes = array_map('ord', str_split(substr($data, 50, 50)));
                        $uniqueBytes = count(array_unique($bytes));
                        
                        // ユニークなバイト数が5未満の場合は単色画像の可能性が高い
                        if ($uniqueBytes < 5) {
                            return false;
                        }
                    }
                    return true;
                }
            }
            
            // それ以外は無効とする
            return false;
            
        } catch (Exception $e) {
            error_log("BookImageHelper: Error checking OpenLibrary image at $url - " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * OpenLibrary APIから画像URLを取得
     * 
     * @param string $isbn ISBN番号
     * @param string $size サイズ (S, M, L)
     * @return string|null 画像URL
     */
    public function getOpenLibraryImageUrl(string $isbn, string $size = 'M'): ?string {
        if (empty($isbn)) {
            return null;
        }
        
        // ISBNを正規化（ハイフンを除去）
        $cleanIsbn = preg_replace('/[^0-9X]/i', '', $isbn);
        
        if (empty($cleanIsbn)) {
            return null;
        }
        
        $url = "https://covers.openlibrary.org/b/isbn/{$cleanIsbn}-{$size}.jpg";
        
        // OpenLibraryの画像は特別な検証が必要
        if ($this->checkOpenLibraryImage($url)) {
            return $url;
        }
        
        return null;
    }
    
    /**
     * Google Books APIから画像URLを取得
     * 
     * @param string $isbn ISBN番号
     * @param int $zoom ズームレベル (1-6)
     * @return string|null 画像URL
     */
    public function getGoogleBooksImageUrl(string $isbn, int $zoom = 1): ?string {
        if (empty($isbn)) {
            return null;
        }
        
        $cacheKey = 'google_books_' . md5($isbn . '_' . $zoom);
        $cachedResult = $this->cache->get($cacheKey);
        
        if ($cachedResult !== false) {
            return $cachedResult ?: null;
        }
        
        try {
            $apiUrl = "https://www.googleapis.com/books/v1/volumes?q=isbn:{$isbn}";
            
            $context = stream_context_create([
                'http' => [
                    'timeout' => $this->timeout,
                    'user_agent' => $this->userAgent
                ]
            ]);
            
            $response = @file_get_contents($apiUrl, false, $context);
            
            if ($response === false) {
                $this->cache->set($cacheKey, false, 3600); // 1時間キャッシュ
                return null;
            }
            
            $data = json_decode($response, true);
            
            if (!isset($data['items'][0]['volumeInfo']['imageLinks'])) {
                $this->cache->set($cacheKey, false, 3600);
                return null;
            }
            
            $imageLinks = $data['items'][0]['volumeInfo']['imageLinks'];
            $imageUrl = null;
            
            // 利用可能な画像サイズから選択
            $sizePreference = ['extraLarge', 'large', 'medium', 'small', 'thumbnail'];
            foreach ($sizePreference as $sizeType) {
                if (isset($imageLinks[$sizeType])) {
                    $imageUrl = $imageLinks[$sizeType];
                    break;
                }
            }
            
            if ($imageUrl) {
                // HTTPSに変更
                $imageUrl = str_replace('http://', 'https://', $imageUrl);
                
                // ズームパラメータを追加
                if ($zoom > 1 && $zoom <= 6) {
                    $imageUrl .= "&zoom={$zoom}";
                }
            }
            
            $this->cache->set($cacheKey, $imageUrl ?: false, $this->cacheTtl);
            return $imageUrl;
            
        } catch (Exception $e) {
            error_log("BookImageHelper: Error fetching Google Books API for ISBN {$isbn}: " . $e->getMessage());
            $this->cache->set($cacheKey, false, 3600);
            return null;
        }
    }
    
    /**
     * 国立国会図書館（NDL）APIから画像URLを取得
     * 日本の書籍に特化
     * 
     * @param string $isbn ISBN番号
     * @return string|null 画像URL
     */
    public function getNdlImageUrl(string $isbn): ?string {
        if (empty($isbn)) {
            return null;
        }
        
        $cacheKey = 'ndl_image_' . md5($isbn);
        $cachedResult = $this->cache->get($cacheKey);
        
        if ($cachedResult !== false) {
            return $cachedResult ?: null;
        }
        
        try {
            // NDL Search APIを使用
            $apiUrl = "https://iss.ndl.go.jp/api/sru?operation=searchRetrieve&version=1.2&recordSchema=dcndl&query=isbn={$isbn}";
            
            $context = stream_context_create([
                'http' => [
                    'timeout' => $this->timeout,
                    'user_agent' => $this->userAgent
                ]
            ]);
            
            $response = @file_get_contents($apiUrl, false, $context);
            
            if ($response === false) {
                $this->cache->set($cacheKey, false, 3600);
                return null;
            }
            
            // XMLをパース
            $xml = @simplexml_load_string($response);
            
            if ($xml === false || !isset($xml->records->record)) {
                $this->cache->set($cacheKey, false, 3600);
                return null;
            }
            
            // 書誌ID (JP番号) を取得
            $jpNumber = null;
            foreach ($xml->records->record as $record) {
                $recordData = $record->recordData->children('http://www.loc.gov/zing/srw/diagnostic/');
                if (isset($recordData->srw_diagnostic)) {
                    continue;
                }
                
                $dcndl = $record->recordData->children('http://ndl.go.jp/dcndl/terms/');
                if (isset($dcndl->BibAdminResource->BibResource->jpno)) {
                    $jpNumber = (string)$dcndl->BibAdminResource->BibResource->jpno;
                    break;
                }
            }
            
            if (!$jpNumber) {
                $this->cache->set($cacheKey, false, 3600);
                return null;
            }
            
            // 画像URLを構築（NDLデジタルコレクション）
            $imageUrl = "https://iss.ndl.go.jp/thumbnail/{$jpNumber}.jpg";
            
            // 画像の存在確認
            if ($this->checkImageExists($imageUrl)) {
                $this->cache->set($cacheKey, $imageUrl, $this->cacheTtl);
                return $imageUrl;
            }
            
            $this->cache->set($cacheKey, false, 3600);
            return null;
            
        } catch (Exception $e) {
            error_log("BookImageHelper: Error fetching NDL API for ISBN {$isbn}: " . $e->getMessage());
            $this->cache->set($cacheKey, false, 3600);
            return null;
        }
    }
    
    /**
     * 国立国会図書館デジタルコレクションから画像URLを取得
     * 
     * @param string $isbn ISBN番号
     * @return string|null 画像URLまたはnull
     */
    public function getNationalDietLibraryImageUrl(string $isbn): ?string {
        $isbn = preg_replace('/[^0-9Xx]/', '', $isbn);
        if (empty($isbn)) {
            return null;
        }
        
        try {
            // NDLの書誌APIを使用
            $apiUrl = "https://iss.ndl.go.jp/api/opensearch?isbn={$isbn}";
            
            $context = stream_context_create([
                'http' => [
                    'timeout' => $this->timeout,
                    'user_agent' => $this->userAgent
                ]
            ]);
            
            $response = @file_get_contents($apiUrl, false, $context);
            if (!$response) {
                return null;
            }
            
            // XMLをパース
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($response);
            
            if (!$xml) {
                return null;
            }
            
            // 名前空間を登録
            $xml->registerXPathNamespace('dc', 'http://purl.org/dc/elements/1.1/');
            $xml->registerXPathNamespace('dcndl', 'http://ndl.go.jp/dcndl/terms/');
            
            // サムネイルURLを取得
            $thumbnails = $xml->xpath('//channel/item/enclosure[@type="image/jpeg"]/@url');
            
            if (!empty($thumbnails)) {
                $url = (string)$thumbnails[0];
                if ($this->isImageUrlValid($url)) {
                    return $url;
                }
            }
            
            return null;
            
        } catch (\Exception $e) {
            error_log("NDL API error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * 複数のフォールバック戦略で信頼できる画像URLを取得
     * 
     * @param string $originalUrl 元の画像URL
     * @param string|null $isbn ISBN番号
     * @param string|null $title 書籍タイトル（将来の拡張用）
     * @return string 最終的な画像URL（フォールバック含む）
     */
    public function getReliableImageUrl(string $originalUrl = '', ?string $isbn = null, ?string $title = null): string {
        // 元のURLが有効な場合はそれを使用
        if (!empty($originalUrl) && $this->isImageUrlValid($originalUrl)) {
            return $originalUrl;
        }
        
        // ISBNが提供されている場合、各種APIを順次試行
        if (!empty($isbn)) {
            // 1. Google Books API （品質が高い・優先）
            $googleBooksUrl = $this->getGoogleBooksImageUrl($isbn, 1);
            if ($googleBooksUrl) {
                return $googleBooksUrl;
            }
            
            // 2. OpenLibrary API （フォールバック）
            $openLibraryUrl = $this->getOpenLibraryImageUrl($isbn, 'L');
            if ($openLibraryUrl) {
                return $openLibraryUrl;
            }
            
            // 3. NDL API （日本の書籍に特化）
            $ndlUrl = $this->getNdlImageUrl($isbn);
            if ($ndlUrl) {
                return $ndlUrl;
            }
        }
        
        // すべて失敗した場合はデフォルト画像
        return '/img/no-image-book.png';
    }
    
    /**
     * 画像URLのバッチ検証
     * 複数の画像URLを効率的に検証
     * 
     * @param array $urls 画像URLの配列
     * @return array 各URLの有効性を示す連想配列
     */
    public function validateImageUrls(array $urls): array {
        $results = [];
        
        foreach ($urls as $url) {
            if (empty($url)) {
                $results[$url] = false;
                continue;
            }
            
            $results[$url] = $this->isImageUrlValid($url);
        }
        
        return $results;
    }
    
    /**
     * キャッシュ統計情報を取得
     * 
     * @return array キャッシュ統計
     */
    public function getCacheStats(): array {
        return $this->cache->getStats();
    }
    
    /**
     * 画像関連のキャッシュをクリア
     * 
     * @return bool
     */
    public function clearImageCache(): bool {
        // 画像関連のキャッシュキーのパターンに基づいてクリア
        $patterns = ['image_valid_', 'google_books_', 'ndl_image_'];
        
        // 現在のSimpleCacheクラスには選択的削除機能がないため、
        // 将来的な拡張として全クリアを実行
        return $this->cache->clear();
    }
}

/**
 * グローバルなBookImageHelperインスタンスを取得
 * 
 * @return BookImageHelper
 */
function getBookImageHelper(): BookImageHelper {
    static $helper = null;
    
    if ($helper === null) {
        $helper = new BookImageHelper();
    }
    
    return $helper;
}

/**
 * 簡単な画像URL取得関数（後方互換性のため）
 * 
 * @param string $originalUrl 元の画像URL
 * @param string|null $isbn ISBN番号
 * @return string 信頼できる画像URL
 */
function getReliableBookImageUrl(string $originalUrl = '', ?string $isbn = null): string {
    return getBookImageHelper()->getReliableImageUrl($originalUrl, $isbn);
}

/**
 * 画像URL有効性チェック関数（簡易版）
 * 
 * @param string $url 画像URL
 * @return bool URLが有効かどうか
 */
function isBookImageUrlValid(string $url): bool {
    return getBookImageHelper()->isImageUrlValid($url);
}