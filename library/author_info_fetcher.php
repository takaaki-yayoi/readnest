<?php
/**
 * 作家情報取得クラス
 * Wikipedia APIとOpenAI APIから作家の情報を取得してキャッシュ
 */

class AuthorInfoFetcher {
    private $cache;
    private $db;
    private $openai_api_key;
    
    public function __construct() {
        global $g_db;
        $this->db = $g_db;
        
        // キャッシュシステムを初期化
        require_once(dirname(__FILE__) . '/cache.php');
        $this->cache = getCache();
        
        // OpenAI APIキー（config.phpから取得）
        $this->openai_api_key = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : '';
    }
    
    /**
     * 作家情報を取得（キャッシュ優先）
     */
    public function getAuthorInfo($author_name) {
        // キャッシュキー
        $cache_key = 'author_info_' . md5($author_name);
        
        // キャッシュから取得を試みる（30日間有効）
        $cached_info = $this->cache->get($cache_key);
        if ($cached_info !== false) {
            return $cached_info;
        }
        
        // データベースからも確認
        $db_info = $this->getFromDatabase($author_name);
        if ($db_info) {
            // キャッシュに保存
            $this->cache->set($cache_key, $db_info, 86400 * 30); // 30日間
            return $db_info;
        }
        
        // 新規取得
        $info = $this->fetchNewAuthorInfo($author_name);
        
        // 作家名が設定されていれば保存（説明文がなくても）
        if ($info && !empty($info['name'])) {
            // データベースに保存（取得試行の記録として）
            $this->saveToDatabase($author_name, $info);
            
            // 説明文がある場合のみキャッシュ（有効なデータのみ）
            if (!empty($info['description'])) {
                $this->cache->set($cache_key, $info, 86400 * 30); // 30日間
            } else {
                // 説明文がない場合は短時間キャッシュ（再試行を可能にする）
                $this->cache->set($cache_key, $info, 3600); // 1時間
                error_log("Author info fetched but no description for: " . $author_name);
            }
        }
        
        return $info;
    }
    
    /**
     * 新しい作家情報を取得
     */
    private function fetchNewAuthorInfo($author_name) {
        $info = [
            'name' => $author_name,
            'description' => '',
            'wikipedia_url' => '',
            'birth_date' => null,
            'death_date' => null,
            'nationality' => '',
            'genres' => [],
            'notable_works' => [],
            'awards' => [],
            'image_url' => '',
            'source' => 'none',
            'fetched_at' => date('Y-m-d H:i:s')
        ];
        
        // 1. まずWikipediaから取得を試みる
        $wikipedia_info = $this->fetchFromWikipedia($author_name);
        if ($wikipedia_info && !empty($wikipedia_info['description'])) {
            $info = array_merge($info, $wikipedia_info);
            $info['source'] = 'wikipedia';
            return $info;
        }
        
        // 2. WikipediaにないまたはOpenAI APIキーがある場合はOpenAIから取得
        if (!empty($this->openai_api_key)) {
            $openai_info = $this->fetchFromOpenAI($author_name);
            if ($openai_info && !empty($openai_info['description'])) {
                $info = array_merge($info, $openai_info);
                $info['source'] = 'openai';
                return $info;
            }
        }
        
        return $info;
    }
    
    /**
     * Wikipediaから作家情報を取得
     */
    private function fetchFromWikipedia($author_name) {
        // 日本語版Wikipediaを優先
        $languages = ['ja', 'en'];
        
        foreach ($languages as $lang) {
            $api_url = "https://{$lang}.wikipedia.org/w/api.php";
            
            // 1. ページ検索
            $search_params = [
                'action' => 'query',
                'format' => 'json',
                'list' => 'search',
                'srsearch' => $author_name,
                'srlimit' => 1,
                'utf8' => 1
            ];
            
            $search_url = $api_url . '?' . http_build_query($search_params);
            $search_result = @file_get_contents($search_url);
            
            if (!$search_result) {
                continue;
            }
            
            $search_data = json_decode($search_result, true);
            if (empty($search_data['query']['search'])) {
                continue;
            }
            
            $page_title = $search_data['query']['search'][0]['title'];
            $page_id = $search_data['query']['search'][0]['pageid'];
            
            // 2. ページ内容取得
            $content_params = [
                'action' => 'query',
                'format' => 'json',
                'prop' => 'extracts|pageimages|info',
                'pageids' => $page_id,
                'exintro' => 1,
                'explaintext' => 1,
                'exsentences' => 5,
                'piprop' => 'original',
                'inprop' => 'url',
                'utf8' => 1
            ];
            
            $content_url = $api_url . '?' . http_build_query($content_params);
            $content_result = @file_get_contents($content_url);
            
            if (!$content_result) {
                continue;
            }
            
            $content_data = json_decode($content_result, true);
            $page_data = $content_data['query']['pages'][$page_id] ?? null;
            
            if (!$page_data) {
                continue;
            }
            
            // 3. Infobox情報を取得（構造化データ）
            $infobox_params = [
                'action' => 'query',
                'format' => 'json',
                'prop' => 'revisions',
                'pageids' => $page_id,
                'rvprop' => 'content',
                'rvslots' => 'main',
                'utf8' => 1
            ];
            
            $infobox_url = $api_url . '?' . http_build_query($infobox_params);
            $infobox_result = @file_get_contents($infobox_url);
            
            $birth_date = null;
            $death_date = null;
            $nationality = '';
            $genres = [];
            $notable_works = [];
            
            if ($infobox_result) {
                $infobox_data = json_decode($infobox_result, true);
                $content = $infobox_data['query']['pages'][$page_id]['revisions'][0]['slots']['main']['*'] ?? '';
                
                // 簡易的なInfobox解析
                if (preg_match('/\|\s*生年月日\s*=\s*([^\|]+)/u', $content, $matches)) {
                    $birth_date = $this->parseWikiDate($matches[1]);
                }
                if (preg_match('/\|\s*没年月日\s*=\s*([^\|]+)/u', $content, $matches)) {
                    $death_date = $this->parseWikiDate($matches[1]);
                }
                if (preg_match('/\|\s*国籍\s*=\s*([^\|]+)/u', $content, $matches)) {
                    $nationality = trim(strip_tags($matches[1]));
                }
                if (preg_match('/\|\s*ジャンル\s*=\s*([^\|]+)/u', $content, $matches)) {
                    $genres_text = trim(strip_tags($matches[1]));
                    $genres = array_map('trim', explode('、', $genres_text));
                }
                if (preg_match('/\|\s*代表作\s*=\s*([^\|]+)/u', $content, $matches)) {
                    $works_text = trim(strip_tags($matches[1]));
                    // 『』で囲まれた作品名を抽出
                    preg_match_all('/『([^』]+)』/u', $works_text, $work_matches);
                    $notable_works = $work_matches[1] ?? [];
                }
            }
            
            return [
                'description' => $page_data['extract'] ?? '',
                'wikipedia_url' => $page_data['fullurl'] ?? '',
                'image_url' => $page_data['original']['source'] ?? '',
                'birth_date' => $birth_date,
                'death_date' => $death_date,
                'nationality' => $nationality,
                'genres' => $genres,
                'notable_works' => $notable_works,
                'language' => $lang
            ];
        }
        
        return null;
    }
    
    /**
     * OpenAI APIから作家情報を取得
     */
    private function fetchFromOpenAI($author_name) {
        if (empty($this->openai_api_key)) {
            return null;
        }
        
        $prompt = "以下の作家について、簡潔な説明文（200文字程度）を日本語で提供してください。存在する場合は生年月日、国籍、ジャンル、代表作も含めてください。作家名: {$author_name}";
        
        $api_url = 'https://api.openai.com/v1/chat/completions';
        
        $data = [
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'あなたは文学に詳しい図書館司書です。作家について簡潔で正確な情報を提供します。'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.3,
            'max_tokens' => 500
        ];
        
        $options = [
            'http' => [
                'header' => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $this->openai_api_key
                ],
                'method' => 'POST',
                'content' => json_encode($data),
                'timeout' => 10
            ]
        ];
        
        $context = stream_context_create($options);
        $response = @file_get_contents($api_url, false, $context);
        
        if (!$response) {
            error_log('OpenAI API request failed for author: ' . $author_name);
            return null;
        }
        
        $result = json_decode($response, true);
        $content = $result['choices'][0]['message']['content'] ?? '';
        
        if (empty($content)) {
            return null;
        }
        
        // OpenAIの応答をパース
        $info = [
            'description' => $content,
            'genres' => [],
            'notable_works' => []
        ];
        
        // ジャンルを抽出
        if (preg_match('/ジャンル[：:]\s*([^。\n]+)/u', $content, $matches)) {
            $genres_text = trim($matches[1]);
            $info['genres'] = array_map('trim', preg_split('/[、,]/u', $genres_text));
        }
        
        // 代表作を抽出
        if (preg_match('/代表作[：:]\s*([^。\n]+)/u', $content, $matches)) {
            $works_text = trim($matches[1]);
            preg_match_all('/『([^』]+)』/u', $works_text, $work_matches);
            $info['notable_works'] = $work_matches[1] ?? [];
        }
        
        return $info;
    }
    
    /**
     * Wiki日付形式をパース
     */
    private function parseWikiDate($date_string) {
        // {{生年月日|1970|1|1}} のような形式をパース
        if (preg_match('/\{\{[^|]+\|(\d{4})\|(\d{1,2})\|(\d{1,2})\}\}/u', $date_string, $matches)) {
            return sprintf('%04d-%02d-%02d', $matches[1], $matches[2], $matches[3]);
        }
        
        // 通常の日付形式を試みる
        $date_string = trim(strip_tags($date_string));
        if (preg_match('/(\d{4})年(\d{1,2})月(\d{1,2})日/u', $date_string, $matches)) {
            return sprintf('%04d-%02d-%02d', $matches[1], $matches[2], $matches[3]);
        }
        
        return null;
    }
    
    /**
     * データベースから作家情報を取得
     */
    private function getFromDatabase($author_name) {
        $sql = "SELECT * FROM b_author_info WHERE author_name = ? AND updated_at > DATE_SUB(NOW(), INTERVAL 30 DAY)";
        $result = $this->db->getRow($sql, [$author_name], DB_FETCHMODE_ASSOC);
        
        if (!DB::isError($result) && $result) {
            // JSON形式のフィールドをデコード
            $result['genres'] = json_decode($result['genres'] ?? '[]', true);
            $result['notable_works'] = json_decode($result['notable_works'] ?? '[]', true);
            $result['awards'] = json_decode($result['awards'] ?? '[]', true);
            return $result;
        }
        
        return null;
    }
    
    /**
     * データベースに作家情報を保存
     */
    private function saveToDatabase($author_name, $info) {
        $sql = "
            INSERT INTO b_author_info 
            (author_name, description, wikipedia_url, birth_date, death_date, 
             nationality, genres, notable_works, awards, image_url, source, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
                description = VALUES(description),
                wikipedia_url = VALUES(wikipedia_url),
                birth_date = VALUES(birth_date),
                death_date = VALUES(death_date),
                nationality = VALUES(nationality),
                genres = VALUES(genres),
                notable_works = VALUES(notable_works),
                awards = VALUES(awards),
                image_url = VALUES(image_url),
                source = VALUES(source),
                updated_at = NOW()
        ";
        
        $params = [
            $author_name,
            $info['description'] ?? '',
            $info['wikipedia_url'] ?? '',
            isset($info['birth_date']) ? $info['birth_date'] : null,
            isset($info['death_date']) ? $info['death_date'] : null,
            $info['nationality'] ?? '',
            json_encode($info['genres'] ?? [], JSON_UNESCAPED_UNICODE),
            json_encode($info['notable_works'] ?? [], JSON_UNESCAPED_UNICODE),
            json_encode($info['awards'] ?? [], JSON_UNESCAPED_UNICODE),
            $info['image_url'] ?? '',
            $info['source'] ?? 'manual'
        ];
        
        $result = $this->db->query($sql, $params);
        
        if (DB::isError($result)) {
            error_log('Failed to save author info for "' . $author_name . '": ' . $result->getMessage());
            error_log('SQL: ' . $sql);
            error_log('Params: ' . json_encode($params, JSON_UNESCAPED_UNICODE));
            return false;
        } else {
            error_log('Successfully saved author info for: ' . $author_name . ' (source: ' . ($info['source'] ?? 'manual') . ')');
        }
        
        return true;
    }
    
    /**
     * 作家情報のHTMLを生成
     */
    public function generateAuthorInfoHtml($author_name) {
        $info = $this->getAuthorInfo($author_name);
        
        if (!$info || empty($info['description'])) {
            return '';
        }
        
        $html = '<div class="author-info-portal bg-gradient-to-r from-purple-50 to-pink-50 rounded-lg p-6 mb-6 shadow-sm">';
        $html .= '<div class="flex flex-col md:flex-row gap-6">';
        
        // 画像がある場合
        if (!empty($info['image_url'])) {
            $html .= '<div class="flex-shrink-0">';
            $html .= '<img src="' . htmlspecialchars($info['image_url']) . '" alt="' . htmlspecialchars($author_name) . '" ';
            $html .= 'class="w-32 h-32 md:w-40 md:h-40 rounded-lg object-cover shadow-md">';
            $html .= '</div>';
        }
        
        // 情報部分
        $html .= '<div class="flex-1">';
        $html .= '<h2 class="text-2xl font-bold text-gray-900 mb-2">' . htmlspecialchars($author_name) . '</h2>';
        
        // 基本情報
        $html .= '<div class="flex flex-wrap gap-4 text-sm text-gray-600 mb-3">';
        
        if (!empty($info['birth_date'])) {
            $birth_year = date('Y', strtotime($info['birth_date']));
            $death_year = !empty($info['death_date']) ? date('Y', strtotime($info['death_date'])) : null;
            $html .= '<span class="flex items-center gap-1">';
            $html .= '<i class="fas fa-calendar-alt"></i>';
            $html .= $birth_year;
            if ($death_year) {
                $html .= ' - ' . $death_year;
            }
            $html .= '</span>';
        }
        
        if (!empty($info['nationality'])) {
            $html .= '<span class="flex items-center gap-1">';
            $html .= '<i class="fas fa-globe"></i>';
            $html .= htmlspecialchars($info['nationality']);
            $html .= '</span>';
        }
        
        if (!empty($info['genres'])) {
            $html .= '<span class="flex items-center gap-1">';
            $html .= '<i class="fas fa-tags"></i>';
            $html .= htmlspecialchars(implode('、', array_slice($info['genres'], 0, 3)));
            $html .= '</span>';
        }
        
        $html .= '</div>';
        
        // 説明文
        $html .= '<div class="text-gray-700 mb-4 line-clamp-3">';
        $html .= nl2br(htmlspecialchars($info['description']));
        $html .= '</div>';
        
        // 代表作
        if (!empty($info['notable_works'])) {
            $html .= '<div class="mb-3">';
            $html .= '<h3 class="text-sm font-semibold text-gray-600 mb-1">代表作</h3>';
            $html .= '<div class="flex flex-wrap gap-2">';
            foreach (array_slice($info['notable_works'], 0, 5) as $work) {
                $html .= '<span class="px-3 py-1 bg-white bg-opacity-70 rounded-full text-sm text-gray-700">';
                $html .= '『' . htmlspecialchars($work) . '』';
                $html .= '</span>';
            }
            $html .= '</div>';
            $html .= '</div>';
        }
        
        // リンク
        $html .= '<div class="flex gap-3">';
        if (!empty($info['wikipedia_url'])) {
            $html .= '<a href="' . htmlspecialchars($info['wikipedia_url']) . '" ';
            $html .= 'target="_blank" rel="noopener noreferrer" ';
            $html .= 'class="text-sm text-blue-600 hover:text-blue-800 flex items-center gap-1">';
            $html .= '<i class="fab fa-wikipedia-w"></i> Wikipedia';
            $html .= '</a>';
        }
        
        // この作家の本を検索
        $html .= '<a href="/add_book.php?search_word=' . urlencode($author_name) . '&search_type=author" ';
        $html .= 'class="text-sm text-purple-600 hover:text-purple-800 flex items-center gap-1">';
        $html .= '<i class="fas fa-search"></i> この作家の本を検索';
        $html .= '</a>';
        
        $html .= '</div>';
        
        // データソース表示
        $source_label = [
            'wikipedia' => 'Wikipedia',
            'openai' => 'AI生成',
            'manual' => '手動登録'
        ];
        $html .= '<div class="mt-3 text-xs text-gray-500">';
        $html .= 'データソース: ' . ($source_label[$info['source']] ?? '不明');
        $html .= '</div>';
        
        $html .= '</div>'; // flex-1
        $html .= '</div>'; // flex
        $html .= '</div>'; // author-info-portal
        
        return $html;
    }
}
?>