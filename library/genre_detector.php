<?php
/**
 * ジャンル判定ライブラリ
 * Google Books APIやタグ情報から本のジャンルを自動判定
 */

declare(strict_types=1);

// Google Books APIのカテゴリを日本語ジャンルにマッピング
const GENRE_MAPPING = [
    // Fiction
    'Fiction' => '小説',
    'Fiction / General' => '小説',
    'Fiction / Literary' => '純文学',
    'Fiction / Mystery & Detective' => 'ミステリー',
    'Fiction / Science Fiction' => 'SF',
    'Fiction / Fantasy' => 'ファンタジー',
    'Fiction / Romance' => '恋愛小説',
    'Fiction / Thrillers' => 'スリラー',
    'Fiction / Historical' => '歴史小説',
    
    // Non-Fiction
    'Business & Economics' => 'ビジネス・経済',
    'Business' => 'ビジネス・経済',
    'Economics' => 'ビジネス・経済',
    'Computers' => 'コンピュータ・IT',
    'Technology & Engineering' => 'コンピュータ・IT',
    'Self-Help' => '自己啓発',
    'Psychology' => '心理学',
    'Biography & Autobiography' => '伝記・自伝',
    'History' => '歴史',
    'Science' => '科学',
    'Mathematics' => '数学',
    'Art' => 'アート・芸術',
    'Music' => '音楽',
    'Cooking' => '料理・レシピ',
    'Travel' => '旅行',
    'Health & Fitness' => '健康・フィットネス',
    'Medical' => '医学',
    'Education' => '教育',
    'Philosophy' => '哲学',
    'Religion' => '宗教',
    'Social Science' => '社会科学',
    'Political Science' => '政治',
    'Nature' => '自然・環境',
    'Sports & Recreation' => 'スポーツ',
    'Comics & Graphic Novels' => '漫画・グラフィックノベル',
    'Poetry' => '詩歌',
    'Drama' => '戯曲',
    'Language Arts & Disciplines' => '語学',
    'Foreign Language Study' => '語学',
    'Young Adult Fiction' => 'ヤングアダルト',
    'Juvenile Fiction' => '児童書',
    'Juvenile Nonfiction' => '児童書',
];

// タグからジャンルを推測するためのマッピング
const TAG_TO_GENRE_MAPPING = [
    'ビジネス・経済' => ['経営', '起業', 'マーケティング', 'リーダーシップ', '経済', 'MBA', '会計', '投資', '金融', 'ファイナンス'],
    '小説' => ['ミステリー', 'SF', 'ファンタジー', '恋愛', '純文学', '推理', '冒険', 'ホラー', 'サスペンス'],
    'ミステリー' => ['推理', '探偵', '犯罪', '謎解き', 'トリック', '本格ミステリー'],
    'SF' => ['サイエンスフィクション', '宇宙', 'ロボット', 'AI', '未来', 'タイムトラベル'],
    'ファンタジー' => ['魔法', '異世界', 'ドラゴン', '冒険', 'ファンタジー', '剣と魔法'],
    'コンピュータ・IT' => ['プログラミング', 'Python', 'JavaScript', 'AI', '機械学習', 'データサイエンス', 'Web開発', 'アプリ開発', 'セキュリティ'],
    '自己啓発' => ['成長', 'モチベーション', '習慣', 'マインドセット', '成功', '目標達成', 'ライフハック'],
    '心理学' => ['心理', 'メンタル', '行動', '認知', 'カウンセリング', '精神'],
    '歴史' => ['日本史', '世界史', '戦国', '江戸', '明治', '昭和', '戦争', '文明'],
    '科学' => ['物理', '化学', '生物', '宇宙', '実験', '研究', 'サイエンス'],
    '料理・レシピ' => ['料理', 'レシピ', 'クッキング', 'お菓子', 'パン', '和食', '洋食', '中華'],
    '健康・フィットネス' => ['ダイエット', 'トレーニング', 'ヨガ', '筋トレ', '健康', 'ウォーキング'],
    'アート・芸術' => ['美術', '絵画', 'デザイン', 'イラスト', '写真', '建築'],
    '漫画・グラフィックノベル' => ['マンガ', 'コミック', 'グラフィックノベル', '劇画'],
    '語学' => ['英語', '英会話', 'TOEIC', '中国語', '韓国語', '外国語', '翻訳'],
];

/**
 * Google Books APIのカテゴリ情報から日本語ジャンルを取得
 */
function getGenreFromAPICategory(?array $categories): ?array {
    if (empty($categories)) {
        return null;
    }
    
    $result = [];
    foreach ($categories as $category) {
        // 完全一致を優先
        if (isset(GENRE_MAPPING[$category])) {
            $result[] = [
                'genre' => GENRE_MAPPING[$category],
                'genre_en' => $category,
                'confidence' => 1.0
            ];
            continue;
        }
        
        // 部分一致を試みる
        foreach (GENRE_MAPPING as $en => $ja) {
            if (stripos($category, $en) !== false || stripos($en, $category) !== false) {
                $result[] = [
                    'genre' => $ja,
                    'genre_en' => $category,
                    'confidence' => 0.8
                ];
                break;
            }
        }
    }
    
    return !empty($result) ? $result : null;
}

/**
 * タグからジャンルを推測
 */
function inferGenreFromTags(array $tags): ?array {
    if (empty($tags)) {
        return null;
    }
    
    $genre_scores = [];
    
    foreach ($tags as $tag) {
        $tag_lower = mb_strtolower($tag);
        foreach (TAG_TO_GENRE_MAPPING as $genre => $keywords) {
            foreach ($keywords as $keyword) {
                if (mb_stripos($tag_lower, mb_strtolower($keyword)) !== false) {
                    $genre_scores[$genre] = ($genre_scores[$genre] ?? 0) + 1;
                }
            }
        }
    }
    
    if (empty($genre_scores)) {
        return null;
    }
    
    // スコアでソート
    arsort($genre_scores);
    
    $result = [];
    $max_score = max($genre_scores);
    
    foreach ($genre_scores as $genre => $score) {
        // 最大スコアの50%以上のジャンルを返す
        if ($score >= $max_score * 0.5) {
            $confidence = min(0.9, $score / (count($tags) * 0.5));
            $result[] = [
                'genre' => $genre,
                'genre_en' => null,
                'confidence' => $confidence
            ];
        }
    }
    
    return !empty($result) ? $result : null;
}

/**
 * タイトルと著者からジャンルを推測（簡易版）
 */
function inferGenreFromTitle(string $title, string $author = ''): ?array {
    $combined = $title . ' ' . $author;
    $combined_lower = mb_strtolower($combined);
    
    // タイトルに含まれるキーワードからジャンルを推測
    $title_patterns = [
        '小説' => ['物語', '小説', 'ストーリー'],
        'ミステリー' => ['殺人', '事件', '探偵', '刑事', '推理'],
        'ビジネス・経済' => ['経営', 'ビジネス', 'マーケティング', '戦略', '仕事術'],
        'コンピュータ・IT' => ['プログラミング', '入門', '実践', 'Web', 'アプリ'],
        '自己啓発' => ['成功', '習慣', '人生', '幸せ', '思考'],
        '料理・レシピ' => ['レシピ', '料理', 'クッキング', 'おかず', 'お弁当'],
    ];
    
    foreach ($title_patterns as $genre => $patterns) {
        foreach ($patterns as $pattern) {
            if (mb_stripos($combined_lower, mb_strtolower($pattern)) !== false) {
                return [[
                    'genre' => $genre,
                    'genre_en' => null,
                    'confidence' => 0.6
                ]];
            }
        }
    }
    
    return null;
}

/**
 * 本のジャンルを判定
 */
function determineBookGenre(int $book_id, ?array $api_categories = null, ?string $title = null, ?string $author = null): array {
    global $g_dbh, $g_db;
    
    $genres = [];
    
    // 1. APIカテゴリから判定
    if ($api_categories) {
        $api_genres = getGenreFromAPICategory($api_categories);
        if ($api_genres) {
            foreach ($api_genres as $genre_info) {
                $genre_info['source'] = 'api';
                $genres[] = $genre_info;
            }
        }
    }
    
    // 2. タグから推測（APIで判定できなかった場合）
    if (empty($genres)) {
        // DB_PDOクラスのインスタンスを使用
        $db = $g_db;
        
        if ($db) {
            try {
                $result = $db->getRow(
                    "SELECT GROUP_CONCAT(t.tag_name) as tags 
                     FROM b_tag_map tm 
                     JOIN b_tag t ON tm.tag_id = t.tag_id 
                     WHERE tm.book_id = ?",
                    [$book_id]
                );
                
                if ($result && !DB::isError($result) && $result['tags']) {
                    $tags = explode(',', $result['tags'] ?? '');
                    $tag_genres = inferGenreFromTags($tags);
                    if ($tag_genres) {
                        foreach ($tag_genres as $genre_info) {
                            $genre_info['source'] = 'tag';
                            $genres[] = $genre_info;
                        }
                    }
                }
            } catch (Exception $e) {
                error_log("Error getting tags for genre detection: " . $e->getMessage());
            }
        }
    }
    
    // 3. タイトルから推測（まだジャンルが判定できていない場合）
    if (empty($genres) && $title) {
        $title_genres = inferGenreFromTitle($title, $author ?? '');
        if ($title_genres) {
            foreach ($title_genres as $genre_info) {
                $genre_info['source'] = 'title';
                $genres[] = $genre_info;
            }
        }
    }
    
    // 4. どれでも判定できなかった場合は「未分類」
    if (empty($genres)) {
        $genres[] = [
            'genre' => '未分類',
            'genre_en' => 'Uncategorized',
            'confidence' => 0.1,
            'source' => 'user'
        ];
    }
    
    return $genres;
}

/**
 * 本のジャンルを保存
 */
function saveBookGenres(int $book_id, array $genres): bool {
    global $g_dbh, $g_db;
    
    // DB_PDOクラスのインスタンスを使用
    $db = $g_db;
    
    if (!$db) {
        error_log("Database connection not available in saveBookGenres");
        return false;
    }
    
    try {
        // まずテーブルが存在するか確認
        $table_exists = $db->getOne("SHOW TABLES LIKE 'b_book_genres'");
        
        if (!$table_exists) {
            // テーブルが存在しない場合は何もしない
            return false;
        }
        
        // 既存のジャンルを削除
        $result = $db->query("DELETE FROM b_book_genres WHERE book_id = ?", [$book_id]);
        if (DB::isError($result)) {
            throw new Exception($result->getMessage());
        }
        
        // 新しいジャンルを挿入
        foreach ($genres as $genre_info) {
            $result = $db->query(
                "INSERT INTO b_book_genres (book_id, genre, genre_en, confidence, source) 
                 VALUES (?, ?, ?, ?, ?)",
                [
                    $book_id,
                    $genre_info['genre'],
                    $genre_info['genre_en'] ?? null,
                    $genre_info['confidence'],
                    $genre_info['source']
                ]
            );
            
            if (DB::isError($result)) {
                throw new Exception($result->getMessage());
            }
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Error saving book genres: " . $e->getMessage());
        return false;
    }
}

/**
 * 本のジャンルを取得
 */
function getBookGenres(int $book_id): array {
    global $g_dbh, $g_db;
    
    // DB_PDOクラスのインスタンスを使用
    $db = $g_db;
    
    if (!$db) {
        error_log("Database connection not available in getBookGenres");
        return [];
    }
    
    try {
        // まずテーブルが存在するか確認
        $table_exists = $db->getOne("SHOW TABLES LIKE 'b_book_genres'");
        
        if (!$table_exists) {
            // テーブルが存在しない場合は空配列を返す
            return [];
        }
        
        $genres = $db->getAll(
            "SELECT genre, genre_en, confidence, source 
             FROM b_book_genres 
             WHERE book_id = ? 
             ORDER BY confidence DESC, genre",
            [$book_id]
        );
        
        if (DB::isError($genres)) {
            throw new Exception($genres->getMessage());
        }
        
        return $genres ?: [];
    } catch (Exception $e) {
        error_log("Error getting book genres: " . $e->getMessage());
        return [];
    }
}

/**
 * 最も信頼度の高いジャンルを取得
 */
function getBookPrimaryGenre(int $book_id): ?string {
    $genres = getBookGenres($book_id);
    return !empty($genres) ? $genres[0]['genre'] : null;
}

/**
 * ユーザーの読書ジャンル統計を取得
 */
function getUserGenreStats(int $user_id, ?string $start_date = null, ?string $end_date = null): array {
    global $g_dbh, $g_db;
    
    // DB_PDOクラスのインスタンスを使用
    $db = $g_db;
    
    if (!$db) {
        error_log("Database connection not available in getUserGenreStats");
        return [];
    }
    
    try {
        // まずテーブルが存在するか確認
        $table_exists = $db->getOne("SHOW TABLES LIKE 'b_book_genres'");
        
        if (!$table_exists) {
            // テーブルが存在しない場合は空配列を返す
            return [];
        }
        
        $sql = "SELECT bg.genre, COUNT(DISTINCT bl.book_id) as book_count
                FROM b_book_list bl
                JOIN b_book_genres bg ON bl.book_id = bg.book_id
                WHERE bl.user_id = ? AND bl.status = 1";
        
        $params = [$user_id];
        
        if ($start_date && $end_date) {
            $sql .= " AND bl.finished_date BETWEEN ? AND ?";
            $params[] = $start_date;
            $params[] = $end_date;
        }
        
        $sql .= " GROUP BY bg.genre ORDER BY book_count DESC";
        
        $result = $db->getAll($sql, $params);
        
        if (DB::isError($result)) {
            throw new Exception($result->getMessage());
        }
        
        return $result ?: [];
    } catch (Exception $e) {
        error_log("Error getting user genre stats: " . $e->getMessage());
        return [];
    }
}