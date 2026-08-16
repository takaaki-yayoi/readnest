<?php
/**
 * 作家情報取得クラス
 * Wikipedia APIとOpenAI APIから作家の情報を取得してキャッシュ
 */

class AuthorInfoFetcher {
    private $cache;
    private $db;
    private $openai_api_key;

    // Wikipedia APIはUser-Agentポリシーにより、UA無し/汎用UAのリクエストを403で拒否する
    // https://meta.wikimedia.org/wiki/User-Agent_policy
    const HTTP_USER_AGENT = 'ReadNest/1.0 (https://readnest.jp; takaakiyayoi@gmail.com)';

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
     * Wikipedia API等へGETリクエストを送る（User-Agent必須）
     * UAを付けないとWikipediaは403を返すため、必ずこのメソッド経由で取得する
     */
    private function httpGet($url) {
        $context = stream_context_create([
            'http' => [
                'method'  => 'GET',
                'header'  => 'User-Agent: ' . self::HTTP_USER_AGENT . "\r\n",
                'timeout' => 10
            ]
        ]);
        return @file_get_contents($url, false, $context);
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
    /**
     * 作家名・記事タイトルの比較用に正規化する
     * 半角/全角スペース、中黒、各種ハイフンを落とし、曖昧さ回避の括弧も除去する
     */
    private function normalizeName($name) {
        $name = (string)$name;
        // 曖昧さ回避の括弧を落とす（例: 中村航 (小説家) → 中村航）
        $name = preg_replace('/[（(][^）)]*[）)]\s*$/u', '', $name);
        $name = preg_replace('/[\s\x{3000}・･‐‑‒–—―\-]/u', '', $name);
        return mb_strtolower(trim($name), 'UTF-8');
    }

    /**
     * Wikipedia記法の残りかすを落とす
     * explaintext=1 を指定しても記事冒頭のテンプレートやリンクが残ることがあり、
     * [[テレビドラマ]] や {{JPN}} がそのままページに出ていた
     */
    private function stripWikiMarkup($text) {
        $text = (string)$text;
        // {{テンプレート}}（入れ子を数回に分けて除去）
        for ($i = 0; $i < 3; $i++) {
            $text = preg_replace('/\{\{[^{}]*\}\}/u', '', $text);
        }
        // [[記事名|表示名]] → 表示名、[[記事名]] → 記事名
        $text = preg_replace('/\[\[(?:[^\[\]|]*\|)?([^\[\]|]*)\]\]/u', '$1', $text);
        // 取り残した角括弧
        $text = str_replace(['[[', ']]'], '', $text);
        // ''' 強調 '' 斜体
        $text = str_replace(["'''", "''"], '', $text);
        $text = preg_replace('/[ \t\x{3000}]+/u', ' ', $text);
        return trim($text);
    }

    /**
     * 抜粋が「その作家本人の人物紹介」として妥当かを判定する
     *
     * 記事タイトル照合をすり抜けた場合の保険。作品や概念の記事（『こころ』『磁力』など）を
     * 人物紹介として出さないよう、人物記事に特有の言い回しを要求する。
     * 落ちた場合は OpenAI 側のフォールバックに回るので、弾きすぎても実害は小さい。
     */
    private function looksLikePersonBio($extract, $author_name, $page_title) {
        if ($extract === '') {
            return false;
        }

        // 本人の名前か、リダイレクト解決後の記事タイトルが本文に出てくること
        $norm_extract = $this->normalizeName($extract);
        $mentions = mb_strpos($norm_extract, $this->normalizeName($author_name)) !== false
            || ($page_title !== '' && mb_strpos($norm_extract, $this->normalizeName($page_title)) !== false);
        if (!$mentions) {
            return false;
        }

        // 人物記事に特有の言い回し（生年の括弧書き、職業名）
        $person_patterns = '/[（(][^）)]*\d{3,4}年[^）)]*[-–—][^）)]*[）)]'
            . '|は、[^。]{0,30}(作家|小説家|著述家|著者|漫画家|評論家|翻訳家|随筆家|エッセイスト|詩人|歌人|俳人|脚本家|劇作家|ジャーナリスト|編集者|研究者|学者|教授|講師|実業家|経営者|医師|弁護士|建築家|写真家|音楽家|画家|イラストレーター|俳優|声優|人物)'
            . '|\(born\s|\bis\s+an?\s+[^.]{0,40}(author|writer|novelist|journalist|professor|researcher|poet|essayist|illustrator|scholar|historian|economist)/u';

        return (bool)preg_match($person_patterns, $extract);
    }

    /**
     * 作家名から Wikipedia の pageid を解決する
     *
     * 以前は list=search（全文検索）の1位を無検証で採用していた。全文検索は本文に
     * 名前が1回出るだけの記事も返すため、翻訳者の記事や同姓の別人、果ては「磁力」
     * 「投資信託」といった概念記事が作家紹介として表示されていた。
     * ここではタイトル一致を必須にする。
     */
    private function resolveWikipediaPageId($api_url, $author_name) {
        // 表記ゆれの候補（search_book_by_author.php と同じ考え方）
        $variants = array_values(array_unique(array_filter([
            $author_name,
            str_replace(' ', '', $author_name),
            str_replace(' ', '・', $author_name),
            str_replace('・', ' ', $author_name),
            str_replace('・', '', $author_name),
        ])));

        // 1. タイトル直引き。redirects=1 でリダイレクトも解決する
        //    （例: Mark Twain → マーク・トウェイン）。
        //    完全なタイトル一致なので、解決後のタイトルが違っても信頼できる。
        foreach ($variants as $variant) {
            $params = [
                'action' => 'query',
                'format' => 'json',
                'titles' => $variant,
                'redirects' => 1,
                'utf8' => 1,
            ];
            $result = $this->httpGet($api_url . '?' . http_build_query($params));
            if (!$result) {
                continue;
            }
            $data = json_decode($result, true);
            foreach ($data['query']['pages'] ?? [] as $pid => $page) {
                // 存在しないページは pageid -1 で返る
                if ((int)$pid > 0 && empty($page['missing'])) {
                    return (int)$pid;
                }
            }
        }

        // 2. タイトル内検索。ヒットしたタイトルが作家名と一致するものだけ採用する
        $params = [
            'action' => 'query',
            'format' => 'json',
            'list' => 'search',
            'srsearch' => 'intitle:"' . $author_name . '"',
            'srlimit' => 5,
            'utf8' => 1,
        ];
        $result = $this->httpGet($api_url . '?' . http_build_query($params));
        if ($result) {
            $data = json_decode($result, true);
            $normalized_variants = array_map([$this, 'normalizeName'], $variants);
            foreach ($data['query']['search'] ?? [] as $hit) {
                if (in_array($this->normalizeName($hit['title'] ?? ''), $normalized_variants, true)) {
                    return (int)$hit['pageid'];
                }
            }
        }

        return null;
    }

    private function fetchFromWikipedia($author_name) {
        // 日本語版Wikipediaを優先
        $languages = ['ja', 'en'];

        foreach ($languages as $lang) {
            $api_url = "https://{$lang}.wikipedia.org/w/api.php";

            // 1. タイトル一致で pageid を解決する（全文検索の1位を拾わない）
            $page_id = $this->resolveWikipediaPageId($api_url, $author_name);
            if (!$page_id) {
                continue;
            }

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
            $content_result = $this->httpGet($content_url);
            
            if (!$content_result) {
                continue;
            }
            
            $content_data = json_decode($content_result, true);
            $page_data = $content_data['query']['pages'][$page_id] ?? null;

            if (!$page_data) {
                continue;
            }

            // 残った Wikipedia 記法を落としてから、人物紹介として妥当か検証する。
            // 妥当でなければこの言語版は採用せず、最終的に OpenAI 側にフォールバックする。
            $extract = $this->stripWikiMarkup($page_data['extract'] ?? '');
            if (!$this->looksLikePersonBio($extract, $author_name, $page_data['title'] ?? '')) {
                error_log('AuthorInfoFetcher: rejected wikipedia article for "' . $author_name
                    . '" (title: ' . ($page_data['title'] ?? '?') . ', lang: ' . $lang . ')');
                continue;
            }
            $page_data['extract'] = $extract;
            
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
            $infobox_result = $this->httpGet($infobox_url);
            
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
                    $nationality = $this->stripWikiMarkup(strip_tags($matches[1]));
                }
                if (preg_match('/\|\s*ジャンル\s*=\s*([^\|]+)/u', $content, $matches)) {
                    $genres_text = $this->stripWikiMarkup(strip_tags($matches[1]));
                    $genres = array_map('trim', explode('、', $genres_text));
                }
                if (preg_match('/\|\s*代表作\s*=\s*([^\|]+)/u', $content, $matches)) {
                    $works_text = $this->stripWikiMarkup(strip_tags($matches[1]));
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