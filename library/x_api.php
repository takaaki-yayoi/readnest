<?php
/**
 * X (Twitter) API Integration Library
 * 
 * Handles posting reading activities to X for users with public diaries
 */

require_once dirname(__DIR__) . '/config/x_api.php';
require_once 'database.php';
require_once 'x_oauth_v2.php';

/**
 * Xの文字数カウント方式に基づいてツイートの長さを計算
 * 日本語・中国語・韓国語は2文字として計算される
 * 
 * @param string $text ツイートテキスト
 * @return int 計算された文字数
 */
function calculateTweetLength($text) {
    // URLは23文字として計算（短縮URL）
    $text = preg_replace_callback(
        '/https?:\/\/[^\s]+/i',
        function($matches) {
            return str_repeat('x', 23);
        },
        $text
    );
    
    // 各文字の重みを計算
    $length = 0;
    $chars = mb_str_split($text);
    
    foreach ($chars as $char) {
        // CJK文字（日本語、中国語、韓国語）の判定
        if (preg_match('/[\x{3000}-\x{303F}\x{3040}-\x{309F}\x{30A0}-\x{30FF}\x{FF00}-\x{FFEF}\x{4E00}-\x{9FAF}\x{2605}-\x{2606}\x{2190}-\x{2195}\x{203B}]/u', $char)) {
            $length += 2;
        } else {
            $length += 1;
        }
    }
    
    return $length;
}

/**
 * ツイートテキストを指定文字数以内に短縮
 * 
 * @param string $text ツイートテキスト
 * @param int $max_length 最大文字数
 * @return string 短縮されたテキスト
 */
function truncateTweetText($text, $max_length) {
    // 現在の長さをチェック
    if (calculateTweetLength($text) <= $max_length) {
        return $text;
    }
    
    // ハッシュタグを一時的に削除
    $hashtags = '';
    if (preg_match('/(#[^\s]+\s*)+$/u', $text, $matches)) {
        $hashtags = $matches[0];
        $text = preg_replace('/(#[^\s]+\s*)+$/u', '', $text);
    }
    
    // テキストを短縮
    while (calculateTweetLength($text . '...' . $hashtags) > $max_length && mb_strlen($text) > 10) {
        $text = mb_substr($text, 0, mb_strlen($text) - 1);
        $text = rtrim($text);
    }
    
    return $text . '...' . $hashtags;
}

class XApiClient {
    private $api_key;
    private $api_secret;
    private $access_token;
    private $access_token_secret;
    
    public function __construct() {
        $this->api_key = X_API_KEY;
        $this->api_secret = X_API_SECRET;
        $this->access_token = X_ACCESS_TOKEN;
        $this->access_token_secret = X_ACCESS_TOKEN_SECRET;
    }
    
    /**
     * Post a tweet using X API
     * 
     * @param string $text The tweet text
     * @return array|false Returns response array on success, false on failure
     */
    public function postTweet($text) {
        if (!X_POST_ENABLED) {
            error_log('[X API] Posting is disabled');
            return ['success' => false, 'error' => 'X投稿機能が無効になっています'];
        }
        
        // Check OAuth 1.0a credentials
        if (empty($this->api_key) || empty($this->api_secret) || 
            empty($this->access_token) || empty($this->access_token_secret) ||
            $this->access_token === 'your_access_token_here' ||
            $this->access_token_secret === 'your_access_token_secret_here') {
            error_log('[X API] Missing or invalid API credentials. Please configure X API access tokens.');
            return ['success' => false, 'error' => 'X API認証情報が設定されていないか無効です'];
        }
        
        // Use OAuth 1.0a with API v2
        $oauth = new XOAuthV2($this->api_key, $this->api_secret, $this->access_token, $this->access_token_secret);
        $result = $oauth->postTweet($text);
        
        // OAuth結果がfalseまたは配列でない場合の処理
        if ($result === false) {
            return ['success' => false, 'error' => 'OAuth認証エラーが発生しました'];
        }
        
        // 結果が既に配列形式の場合はそのまま返す
        if (is_array($result)) {
            return $result;
        }
        
        // それ以外の場合（想定外）
        return ['success' => false, 'error' => '予期しないレスポンス形式: ' . var_export($result, true)];
    }
}

/**
 * Post reading event to X for users with public diaries
 * 
 * @param int $user_id User ID
 * @param int $book_id Book ID
 * @param int $event_type Event type (READING_NOW, READING_FINISH)
 * @param int $rating Rating (for reviews)
 * @param string $review_text Review text (for reviews)
 * @return bool Success status
 */
function postReadingEventToX($user_id, $book_id, $event_type, $rating = 0, $review_text = '') {
    global $g_db;
    
    // X投稿抑制フラグをチェック
    if (isset($_SESSION['suppress_x_post']) && $_SESSION['suppress_x_post'] === true) {
        unset($_SESSION['suppress_x_post']); // フラグをクリア
        return false;
    }
    
    // Get user information
    $user_info = getUserInformation($user_id);
    if (!$user_info) {
        error_log('[X API] User not found: ' . $user_id);
        return false;
    }
    
    // Check if user has public diary
    if ($user_info['diary_policy'] != 1) {
        // User's diary is not public
        return false;
    }
    
    // Check if user has X posting enabled
    if (!isset($user_info['x_post_enabled']) || $user_info['x_post_enabled'] != 1) {
        // User has not enabled X posting
        return false;
    }
    
    // Check if this event type is enabled for posting
    $x_post_events = isset($user_info['x_post_events']) ? (int)$user_info['x_post_events'] : 13;
    $event_flags = [
        READING_NOW => 1,      // Start reading
        'progress' => 2,       // Progress updates
        READING_FINISH => 4,   // Finish reading
        'review' => 8          // Reviews
    ];
    
    if (isset($event_flags[$event_type])) {
        if (!($x_post_events & $event_flags[$event_type])) {
            // This event type is not enabled for posting
            return false;
        }
    }
    
    // Get user's display name
    $user_name = $user_info['nickname'] ?? $user_info['user_id'];
    
    // Get book information
    $book_info = getBookInformation($book_id);
    if (!$book_info) {
        error_log('[X API] Book not found: ' . $book_id);
        return false;
    }
    
    $book_title = $book_info['name'];
    $author = $book_info['author'] ?? '';
    
    // Prepare tweet text based on event type
    $tweet_text = '';
    
    // Check if posting from user's own account
    $isOwnAccount = !empty($user_info['x_oauth_token']) && !empty($user_info['x_oauth_token_secret']);
    
    switch ($event_type) {
        case READING_NOW:
            // Started reading
            if ($isOwnAccount) {
                // User's own account - don't include their name
                $tweet_text = sprintf('「%s」を読み始めました！ #読書記録 #ReadNest', $book_title);
            } else {
                // Default account - include user name
                $tweet_text = sprintf(X_TEMPLATE_START_READING, $user_name, $book_title);
            }
            if ($author) {
                $tweet_text = str_replace('」を', '」（' . $author . '）を', $tweet_text);
            }
            break;
            
        case READING_FINISH:
            // Finished reading
            if ($isOwnAccount) {
                // User's own account - don't include their name
                $tweet_text = sprintf('「%s」を読み終わりました！ #読書記録 #ReadNest', $book_title);
            } else {
                // Default account - include user name
                $tweet_text = sprintf(X_TEMPLATE_FINISH_READING, $user_name, $book_title);
            }
            if ($author) {
                $tweet_text = str_replace('」を', '」（' . $author . '）を', $tweet_text);
            }
            break;
            
        case 'review':
            // Added review
            $star_text = str_repeat('★', $rating) . str_repeat('☆', 5 - $rating);
            
            // レビューテキストの抜粋を作成
            $review_excerpt = '';
            if (!empty($review_text)) {
                $review_text_clean = strip_tags($review_text);
                $review_text_clean = preg_replace('/\s+/', ' ', trim($review_text_clean));
                
                // 基本のツイートテキストの長さを計算（レビューテキスト抜粋を除く）
                $base_tweet_length = mb_strlen(sprintf("「%s」のレビューを投稿しました。評価: %s\n\n\n\n#読書記録 #ReadNest", 
                    $book_title, $star_text));
                
                // 著者名を含める場合の追加文字数
                if ($author) {
                    $base_tweet_length += mb_strlen('（' . $author . '）');
                }
                
                // URL分の文字数（短縮URL）
                $url_length = 24;
                
                // 利用可能な文字数を計算（日本語は2文字として計算される場合を考慮）
                $available_length = 140 - $base_tweet_length - $url_length - 10; // 10文字の余裕を持たせる
                
                if (mb_strlen($review_text_clean) > $available_length) {
                    $review_excerpt = mb_substr($review_text_clean, 0, max(20, $available_length - 3)) . '...';
                } else {
                    $review_excerpt = $review_text_clean;
                }
            }
            
            if ($isOwnAccount) {
                // User's own account - don't include their name
                if (!empty($review_excerpt)) {
                    $tweet_text = sprintf("「%s」のレビューを投稿しました。評価: %s\n\n%s\n\n#読書記録 #ReadNest", 
                        $book_title, $star_text, $review_excerpt);
                } else {
                    $tweet_text = sprintf('「%s」のレビューを投稿しました。評価: %s #読書記録 #ReadNest', 
                        $book_title, $star_text);
                }
            } else {
                // Default account - include user name
                if (!empty($review_excerpt)) {
                    $base_text = sprintf(X_TEMPLATE_ADD_REVIEW, $user_name, $book_title, $star_text);
                    // X_TEMPLATE_ADD_REVIEWの末尾からハッシュタグ部分を分離
                    $parts = preg_split('/(\s+#)/', $base_text, 2, PREG_SPLIT_DELIM_CAPTURE);
                    if (count($parts) >= 2) {
                        $tweet_text = $parts[0] . "\n\n" . $review_excerpt . $parts[1] . (isset($parts[2]) ? $parts[2] : '');
                    } else {
                        $tweet_text = $base_text . "\n\n" . $review_excerpt;
                    }
                } else {
                    $tweet_text = sprintf(X_TEMPLATE_ADD_REVIEW, $user_name, $book_title, $star_text);
                }
            }
            if ($author) {
                $tweet_text = str_replace('」の', '」（' . $author . '）の', $tweet_text);
            }
            break;
            
        default:
            return false;
    }
    
    // Add book URL if space available
    $book_url = 'https://readnest.jp/book/' . $book_id;
    
    // 文字数を計算（日本語の文字数カウント）
    $tweet_length = calculateTweetLength($tweet_text);
    $url_addition_length = 1 + 23; // スペース + 短縮URL
    
    // Add book URL if space permits
    if ($tweet_length + $url_addition_length <= 280) {
        $tweet_text .= ' ' . $book_url;
    } else {
        // URLが入らない場合は、テキストを短縮
        $max_text_length = 280 - $url_addition_length;
        $tweet_text = truncateTweetText($tweet_text, $max_text_length);
        $tweet_text .= ' ' . $book_url;
    }
    
    // Post to both accounts if user has X connection
    $success_user = false;
    $success_dokusho = false;
    
    if (!empty($user_info['x_oauth_token']) && !empty($user_info['x_oauth_token_secret'])) {
        // First post to user's own X account
        $oauth = new XOAuthV2(
            X_API_KEY,
            X_API_SECRET,
            $user_info['x_oauth_token'],
            $user_info['x_oauth_token_secret']
        );
        $result = $oauth->postTweet($tweet_text);
        
        if ($result) {
            error_log('[X API] Successfully posted to user\'s X account @' . $user_info['x_screen_name'] . ' for user ' . $user_id . ', book ' . $book_id);
            $success_user = true;
        } else {
            error_log('[X API] Failed to post to user\'s X account. User: ' . $user_id);
        }
        
        // Add a small delay to ensure proper ordering
        usleep(500000); // 0.5 seconds
        
        // Then post to @dokusho account with user name format
        $dokusho_text = '';
        switch ($event_type) {
            case READING_NOW:
                $dokusho_text = sprintf(X_TEMPLATE_START_READING, $user_name, $book_title);
                break;
            case READING_FINISH:
                $dokusho_text = sprintf(X_TEMPLATE_FINISH_READING, $user_name, $book_title);
                break;
            case 'review':
                $star_text = str_repeat('★', $rating) . str_repeat('☆', 5 - $rating);
                $dokusho_text = sprintf(X_TEMPLATE_ADD_REVIEW, $user_name, $book_title, $star_text);
                break;
        }
        
        if ($author && $dokusho_text) {
            $dokusho_text = str_replace(['」を', '」の'], ['」（' . $author . '）を', '」（' . $author . '）の'], $dokusho_text);
        }
        
        // Add URL if space permits
        if ($dokusho_text && mb_strlen($dokusho_text) + mb_strlen(' ' . $book_url) <= X_POST_MAX_LENGTH) {
            $dokusho_text .= ' ' . $book_url;
        }
        
        if ($dokusho_text) {
            $client = new XApiClient();
            $dokusho_result = $client->postTweet($dokusho_text);
            
            if ($dokusho_result) {
                error_log('[X API] Successfully posted to @dokusho for user ' . $user_id . ', book ' . $book_id);
                $success_dokusho = true;
            } else {
                error_log('[X API] Failed to post to @dokusho. User: ' . $user_id);
            }
        }
        
        // Return true if at least one post succeeded
        return $success_user || $success_dokusho;
        
    } else {
        // User has no X connection - post only to @dokusho account
        $client = new XApiClient();
        $result = $client->postTweet($tweet_text);
        
        if ($result) {
            error_log('[X API] Successfully posted to @dokusho for user ' . $user_id . ', book ' . $book_id);
            return true;
        } else {
            error_log('[X API] Failed to post to @dokusho. User: ' . $user_id);
            return false;
        }
    }
}

/**
 * Post when user starts reading a book
 */
function postStartReadingToX($user_id, $book_id) {
    return postReadingEventToX($user_id, $book_id, READING_NOW);
}

/**
 * Post when user finishes reading a book
 */
function postFinishReadingToX($user_id, $book_id) {
    return postReadingEventToX($user_id, $book_id, READING_FINISH);
}

/**
 * Post when user adds a review
 */
function postReviewToX($user_id, $book_id, $rating, $review_text = '') {
    return postReadingEventToX($user_id, $book_id, 'review', $rating, $review_text);
}

/**
 * Post reading progress to X
 * 
 * @param int $user_id User ID
 * @param int $book_id Book ID  
 * @param int $current_page Current page
 * @param int $total_page Total pages
 * @param string $memo Optional memo to include
 * @return bool Success status
 */
function postReadingProgressToX($user_id, $book_id, $current_page, $total_page = 0, $memo = '') {
    global $g_db;
    
    // Get user information
    $user_info = getUserInformation($user_id);
    if (!$user_info) {
        return false;
    }
    
    // Check if user has public diary
    if ($user_info['diary_policy'] != 1) {
        return false;
    }
    
    // Get book information
    $book_info = getBookInformation($book_id);
    if (!$book_info) {
        return false;
    }
    
    $book_title = $book_info['name'];
    $author = $book_info['author'] ?? '';
    $user_name = $user_info['nickname'] ?? $user_info['user_id'];
    
    // Format progress text
    $progress_text = '';
    if ($total_page > 0) {
        $percentage = round(($current_page / $total_page) * 100);
        $progress_text = sprintf('%d/%dページ (%d%%)', $current_page, $total_page, $percentage);
    } else {
        $progress_text = sprintf('%dページ', $current_page);
    }
    
    // Create tweet text
    if (!empty($user_info['x_oauth_token']) && !empty($user_info['x_oauth_token_secret'])) {
        // User's own account - don't include their name
        $tweet_text = sprintf('「%s」を読んでいます。進捗: %s', $book_title, $progress_text);
        
        // Add memo if provided and space permits
        if (!empty($memo)) {
            $memo_preview = mb_strlen($memo) > 100 ? mb_substr($memo, 0, 100) . '...' : $memo;
            $tweet_with_memo = $tweet_text . "\n\n" . $memo_preview . ' #読書記録 #ReadNest';
            
            // Check if within character limit (considering URL will be added later)
            if (mb_strlen($tweet_with_memo) + 24 <= X_POST_MAX_LENGTH) { // 24 chars for URL
                $tweet_text = $tweet_with_memo;
            } else {
                // Try shorter memo
                $memo_preview = mb_substr($memo, 0, 50) . '...';
                $tweet_with_memo = $tweet_text . "\n\n" . $memo_preview . ' #読書記録 #ReadNest';
                if (mb_strlen($tweet_with_memo) + 24 <= X_POST_MAX_LENGTH) {
                    $tweet_text = $tweet_with_memo;
                } else {
                    // Just add hashtags without memo
                    $tweet_text .= ' #読書記録 #ReadNest';
                }
            }
        } else {
            $tweet_text .= ' #読書記録 #ReadNest';
        }
    } else {
        // Default account - include user name
        $tweet_text = sprintf(X_TEMPLATE_READING_PROGRESS, $user_name, $book_title, $progress_text);
    }
    
    if ($author) {
        $tweet_text = str_replace('」を', '」（' . $author . '）を', $tweet_text);
    }
    
    // Add book URL if space permits
    $book_url = 'https://readnest.jp/book/' . $book_id;
    if (mb_strlen($tweet_text) + mb_strlen(' ' . $book_url) <= X_POST_MAX_LENGTH) {
        $tweet_text .= ' ' . $book_url;
    }
    
    // Post to both accounts if user has X connection
    $success_user = false;
    $success_dokusho = false;
    
    if (!empty($user_info['x_oauth_token']) && !empty($user_info['x_oauth_token_secret'])) {
        // First check if progress posting is enabled for user's account
        if ($user_info['x_post_enabled'] && ($user_info['x_post_events'] & X_EVENT_READING_PROGRESS)) {
            // Post to user's account first
            $oauth = new XOAuthV2(
                X_API_KEY,
                X_API_SECRET,
                $user_info['x_oauth_token'],
                $user_info['x_oauth_token_secret']
            );
            $result = $oauth->postTweet($tweet_text);
            
            if ($result) {
                error_log('[X API] Successfully posted progress to user\'s X account @' . $user_info['x_screen_name']);
                $success_user = true;
            } else {
                error_log('[X API] Failed to post progress to user\'s X account. User: ' . $user_id);
            }
        }
        
        // Add a small delay to ensure proper ordering
        usleep(500000); // 0.5 seconds
        
        // Then post to @dokusho account with user name format
        $dokusho_text = sprintf(X_TEMPLATE_READING_PROGRESS, $user_name, $book_title, $progress_text);
        
        if ($author) {
            $dokusho_text = str_replace('」を', '」（' . $author . '）を', $dokusho_text);
        }
        
        // Add memo for dokusho account too
        if (!empty($memo)) {
            $memo_preview = mb_strlen($memo) > 80 ? mb_substr($memo, 0, 80) . '...' : $memo;
            $dokusho_with_memo = $dokusho_text . "\n\n" . $memo_preview;
            
            // Check if within character limit (considering URL will be added later)
            if (mb_strlen($dokusho_with_memo) + 24 <= X_POST_MAX_LENGTH) {
                $dokusho_text = $dokusho_with_memo;
            }
        }
        
        // Add URL if space permits
        if (mb_strlen($dokusho_text) + mb_strlen(' ' . $book_url) <= X_POST_MAX_LENGTH) {
            $dokusho_text .= ' ' . $book_url;
        }
        
        $client = new XApiClient();
        $dokusho_result = $client->postTweet($dokusho_text);
        
        if ($dokusho_result) {
            error_log('[X API] Successfully posted progress to @dokusho for user ' . $user_id);
            $success_dokusho = true;
        } else {
            error_log('[X API] Failed to post progress to @dokusho. User: ' . $user_id);
        }
        
        // Return true if at least one post succeeded
        return $success_user || $success_dokusho;
        
    } else {
        // User has no X connection - post only to @dokusho account
        $client = new XApiClient();
        $result = $client->postTweet($tweet_text);
        
        if ($result) {
            error_log('[X API] Successfully posted progress to @dokusho for user ' . $user_id);
            return true;
        } else {
            error_log('[X API] Failed to post progress to @dokusho. User: ' . $user_id);
            return false;
        }
    }
}


