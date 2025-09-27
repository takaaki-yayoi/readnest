<?php
require_once('../modern_config.php');

// APIレスポンスヘッダー
header('Content-Type: application/json; charset=utf-8');

// ログインチェック
$login_flag = checkLogin();
if (!$login_flag) {
    echo json_encode(['success' => false, 'message' => 'ログインが必要です']);
    exit;
}

$mine_user_id = $_SESSION['AUTH_USER'];

// POSTデータを取得
$input = json_decode(file_get_contents('php://input'), true);
$message = isset($input['message']) ? trim($input['message']) : '';
$user_context = isset($input['user_context']) ? $input['user_context'] : [];

if (empty($message)) {
    echo json_encode(['success' => false, 'message' => 'メッセージが入力されていません']);
    exit;
}

// OpenAI APIキーチェック
if (!defined('OPENAI_API_KEY') || empty(OPENAI_API_KEY)) {
    echo json_encode([
        'success' => true,
        'response' => '申し訳ございません。現在AIアシスタント機能はメンテナンス中です。'
    ]);
    exit;
}

// embeddingを使った類似検索
function searchByEmbedding($message, $user_id, $limit = 5) {
    global $g_db;
    
    // デバッグ情報
    $debug_info = [
        'start_time' => microtime(true),
        'message' => $message,
        'status' => 'initializing'
    ];
    
    // OpenAI APIでメッセージのembeddingを生成
    if (!defined('OPENAI_API_KEY') || empty(OPENAI_API_KEY)) {
        $debug_info['status'] = 'no_api_key';
        return ['results' => [], 'debug' => $debug_info];
    }
    
    $ch = curl_init('https://api.openai.com/v1/embeddings');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . OPENAI_API_KEY
    ]);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'model' => 'text-embedding-3-small',
        'input' => $message
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $debug_info['embedding_http_code'] = $http_code;
    $debug_info['embedding_time'] = microtime(true) - $debug_info['start_time'];
    
    if ($http_code !== 200) {
        $debug_info['status'] = 'embedding_api_error';
        $debug_info['error'] = $response;
        return ['results' => [], 'debug' => $debug_info];
    }
    
    $result = json_decode($response, true);
    if (!isset($result['data'][0]['embedding'])) {
        $debug_info['status'] = 'no_embedding_in_response';
        return ['results' => [], 'debug' => $debug_info];
    }
    
    $query_embedding = $result['data'][0]['embedding'];
    $debug_info['embedding_size'] = count($query_embedding);
    
    // b_book_repositoryからembeddingを持つ本を検索
    $sql = "SELECT asin, title, author, description, combined_embedding 
            FROM b_book_repository 
            WHERE combined_embedding IS NOT NULL 
            LIMIT 200";
    
    $books = $g_db->getAll($sql, [], DB_FETCHMODE_ASSOC);
    if (DB::isError($books)) {
        $debug_info['status'] = 'database_error';
        $debug_info['error'] = $books->getMessage();
        return ['results' => [], 'debug' => $debug_info];
    }
    
    $debug_info['total_books_checked'] = count($books);
    $debug_info['status'] = 'calculating_similarity';
    
    // コサイン類似度を計算
    $scored_books = [];
    $high_similarity_count = 0;
    foreach ($books as $book) {
        $book_embedding = json_decode($book['combined_embedding'], true);
        if (!$book_embedding) continue;
        
        // コサイン類似度計算
        $dot_product = 0;
        $norm_a = 0;
        $norm_b = 0;
        
        for ($i = 0; $i < count($query_embedding); $i++) {
            $dot_product += $query_embedding[$i] * $book_embedding[$i];
            $norm_a += $query_embedding[$i] * $query_embedding[$i];
            $norm_b += $book_embedding[$i] * $book_embedding[$i];
        }
        
        $similarity = $dot_product / (sqrt($norm_a) * sqrt($norm_b));
        
        if ($similarity > 0.7) { // 閾値以上の類似度のみ
            $high_similarity_count++;
            $scored_books[] = [
                'title' => $book['title'],
                'author' => $book['author'],
                'description' => mb_substr($book['description'] ?? '', 0, 100),
                'similarity' => round($similarity, 3),
                'source' => 'embedding_search'
            ];
        }
    }
    
    // 類似度順にソート
    usort($scored_books, function($a, $b) {
        return $b['similarity'] <=> $a['similarity'];
    });
    
    $debug_info['high_similarity_count'] = $high_similarity_count;
    $debug_info['returned_count'] = min($high_similarity_count, $limit);
    $debug_info['total_time'] = round(microtime(true) - $debug_info['start_time'], 3);
    $debug_info['status'] = 'success';
    
    return [
        'results' => array_slice($scored_books, 0, $limit),
        'debug' => $debug_info
    ];
}

// RAG用の拡張コンテキスト取得関数
function getEnhancedContext($user_id, $message, $user_context = []) {
    global $g_db;
    $context_parts = [];
    $data_sources = [];
    
    // 1. ユーザーの基本読書傾向
    if (!empty($user_context['recent_books'])) {
        $books_text = [];
        foreach ($user_context['recent_books'] as $book) {
            $rating = isset($book['rating']) && $book['rating'] ? "（評価: {$book['rating']}★）" : "";
            $books_text[] = "・{$book['title']} - {$book['author']}{$rating}";
        }
        $context_parts[] = "最近読んだ本:\n" . implode("\n", $books_text);
        $data_sources[] = 'user_recent_books';
    }
    
    if (!empty($user_context['favorite_genres'])) {
        $genre_names = array_column($user_context['favorite_genres'], 'tag_name');
        $context_parts[] = "よく読むジャンル: " . implode("、", $genre_names);
        $data_sources[] = 'user_favorite_genres';
    }
    
    // 2. Embedding検索（意味的に類似した本を検索）
    $embedding_search = searchByEmbedding($message, $user_id, 5);
    $embedding_results = $embedding_search['results'] ?? [];
    $embedding_debug = $embedding_search['debug'] ?? [];
    
    if (!empty($embedding_results)) {
        $embedding_text = "【意味的に関連する本（AI解析）】\n";
        foreach ($embedding_results as $book) {
            $embedding_text .= "・「{$book['title']}」({$book['author']}) - 類似度: {$book['similarity']}\n";
            if (!empty($book['description'])) {
                $embedding_text .= "  説明: {$book['description']}...\n";
            }
        }
        $context_parts[] = $embedding_text;
        $data_sources[] = 'embedding_search';
    }
    
    // 3. メッセージから関連する本を検索（テキスト検索）
    if (preg_match('/「([^」]+)」/u', $message, $matches)) {
        $book_title = $matches[1];
        
        // 本の詳細情報を取得
        $sql = "SELECT bl.*, 
                (SELECT COUNT(*) FROM b_book_list WHERE title = bl.title) as reader_count
                FROM b_book_list bl 
                WHERE bl.title LIKE ? 
                LIMIT 5";
        $related_books = $g_db->getAll($sql, ['%' . $book_title . '%'], DB_FETCHMODE_ASSOC);
        
        if (!DB::isError($related_books) && count($related_books) > 0) {
            $books_info = [];
            foreach ($related_books as $book) {
                $info = "・{$book['title']} ({$book['author']})";
                if ($book['rating']) $info .= " - 評価: {$book['rating']}★";
                if ($book['reader_count'] > 1) $info .= " - {$book['reader_count']}人が読書";
                $books_info[] = $info;
            }
            $context_parts[] = "関連する本の情報:\n" . implode("\n", $books_info);
            $data_sources[] = 'text_search_books';
        }
        
        // レビューを検索
        $sql = "SELECT review, rating FROM b_book_list 
                WHERE title LIKE ? AND review IS NOT NULL AND review != ''
                ORDER BY rating DESC 
                LIMIT 3";
        $reviews = $g_db->getAll($sql, ['%' . $book_title . '%'], DB_FETCHMODE_ASSOC);
        
        if (!DB::isError($reviews) && count($reviews) > 0) {
            $review_texts = [];
            foreach ($reviews as $review) {
                $review_text = mb_substr($review['review'], 0, 100);
                $review_texts[] = "・{$review['rating']}★: {$review_text}...";
            }
            $context_parts[] = "他のユーザーのレビュー:\n" . implode("\n", $review_texts);
        }
    }
    
    // 3. ジャンル検索
    if (preg_match('/(?:小説|ミステリー|SF|ファンタジー|ビジネス|自己啓発|歴史|哲学|科学|技術|アート|料理|旅行)/u', $message, $matches)) {
        $genre = $matches[0];
        
        // ジャンルの人気本を取得
        $sql = "SELECT bl.title, bl.author, AVG(bl.rating) as avg_rating, COUNT(*) as reader_count
                FROM b_book_list bl
                JOIN b_book_tags bt ON bl.id = bt.book_id
                WHERE bt.tag_name LIKE ? AND bl.rating > 0
                GROUP BY bl.title, bl.author
                ORDER BY avg_rating DESC, reader_count DESC
                LIMIT 5";
        $popular_books = $g_db->getAll($sql, ['%' . $genre . '%'], DB_FETCHMODE_ASSOC);
        
        if (!DB::isError($popular_books) && count($popular_books) > 0) {
            $books_list = [];
            foreach ($popular_books as $book) {
                $avg_rating = round($book['avg_rating'], 1);
                $books_list[] = "・{$book['title']} ({$book['author']}) - 平均評価: {$avg_rating}★ ({$book['reader_count']}人)";
            }
            $context_parts[] = "{$genre}ジャンルの人気本:\n" . implode("\n", $books_list);
        }
    }
    
    // 4. 著者検索
    if (preg_match('/([ぁ-んァ-ヶー一-龠]+(?:\s+[ぁ-んァ-ヶー一-龠]+)?)\s*(?:の|さん|氏|先生)/u', $message, $matches)) {
        $author = $matches[1];
        
        $sql = "SELECT title, rating, COUNT(*) OVER() as total_books
                FROM b_book_list
                WHERE author LIKE ?
                ORDER BY rating DESC
                LIMIT 5";
        $author_books = $g_db->getAll($sql, ['%' . $author . '%'], DB_FETCHMODE_ASSOC);
        
        if (!DB::isError($author_books) && count($author_books) > 0) {
            $books_list = [];
            foreach ($author_books as $book) {
                $rating_text = $book['rating'] ? " - {$book['rating']}★" : "";
                $books_list[] = "・{$book['title']}{$rating_text}";
            }
            $context_parts[] = "{$author}の作品:\n" . implode("\n", $books_list);
        }
    }
    
    // 5. ユーザーの読書統計
    if (!empty($user_context['reading_stats'])) {
        $stats = $user_context['reading_stats'];
        $stats_text = [];
        if (isset($stats['finished_count'])) $stats_text[] = "読了: {$stats['finished_count']}冊";
        if (isset($stats['reading_count'])) $stats_text[] = "読書中: {$stats['reading_count']}冊";
        if (isset($stats['avg_rating']) && $stats['avg_rating']) {
            $avg = round($stats['avg_rating'], 1);
            $stats_text[] = "平均評価: {$avg}★";
        }
        if (!empty($stats_text)) {
            $context_parts[] = "読書統計: " . implode("、", $stats_text);
        }
    }
    
    return [
        'context' => $context_parts,
        'sources' => $data_sources,
        'debug' => [
            'embedding' => $embedding_debug
        ]
    ];
}

// Text2SQLの判定と実行
function shouldUseText2SQL($message) {
    // データ分析系のキーワードをチェック
    $sql_keywords = [
        '何冊', '件数', '数を', '個数', 'カウント', '集計',
        '平均', '合計', '最大', '最小', 'ランキング', 'トップ',
        '一覧', 'リスト', '表示して', '見せて', '教えて',
        '統計', 'データ', '分析', '傾向', '推移',
        '去年', '今年', '今月', '先月', '期間',
        '評価が高い', '評価が低い', '星が多い',
        '読了', '読書中', '積読', '読みたい'
    ];
    
    foreach ($sql_keywords as $keyword) {
        if (mb_strpos($message, $keyword) !== false) {
            return true;
        }
    }
    
    return false;
}

// Text2SQL実行（直接関数呼び出し）
function executeText2SQL($message, $user_id) {
    global $g_db;
    
    // 共通関数を読み込み
    require_once(dirname(__DIR__) . '/library/text2sql_functions.php');
    
    // SQL生成
    $sql_result = generateSQL($message, $user_id);
    
    if (!$sql_result['success']) {
        // SQL生成失敗
        return $sql_result;
    }
    
    // SQL実行
    $execution_result = executeSQL($sql_result['sql'], $user_id);
    
    // デバッグ情報を追加
    $execution_result['debug'] = [
        'mode' => 'text2sql',
        'query' => $sql_result['sql']
    ];
    
    // SQL実行完了
    
    return $execution_result;
}

// Text2SQLチェック
$sql_data = null;
if (shouldUseText2SQL($message)) {
    // Text2SQLトリガー
    $sql_result = executeText2SQL($message, $mine_user_id);
    // Text2SQL結果取得
    if ($sql_result && $sql_result['success']) {
        $sql_data = $sql_result;
    } else {
        // Text2SQL失敗
    }
} else {
    // Text2SQL非トリガー
}

// 拡張コンテキストを取得
$context_result = getEnhancedContext($mine_user_id, $message, $user_context);
$context_parts = $context_result['context'];
$data_sources = $context_result['sources'];
$context_debug = $context_result['debug'] ?? [];

// システムプロンプトを構築
$system_prompt = "あなたはReadNestの読書アシスタントです。ユーザーの読書相談に親切に応じ、適切な本を推薦してください。\n";
$system_prompt .= "データベースの検索結果がある場合は、単に結果を列挙するだけでなく、以下の観点から分析と洞察を提供してください：\n";
$system_prompt .= "- 読書傾向の分析（ジャンル、著者、評価の傾向）\n";
$system_prompt .= "- パターンの発見（読書ペース、好みの変化）\n";
$system_prompt .= "- 具体的な推薦とその理由\n";
$system_prompt .= "- 読書習慣の改善提案\n\n";

// データソース情報を作成
$source_info = "";
if (!empty($data_sources)) {
    $source_labels = [
        'user_recent_books' => '📚 最近の読書履歴',
        'user_favorite_genres' => '🏷️ お気に入りジャンル',
        'embedding_search' => '🤖 AI意味解析',
        'text_search_books' => '🔍 テキスト検索',
        'sql_query' => '💾 データベース検索'
    ];
    
    $used_sources = [];
    foreach (array_unique($data_sources) as $source) {
        if (isset($source_labels[$source])) {
            $used_sources[] = $source_labels[$source];
        }
    }
    
    if (!empty($used_sources)) {
        $source_info = "\n📌 **参照データ**: " . implode(", ", $used_sources) . "\n";
    }
}

// Text2SQLの結果がある場合
$text2sql_info = "";
$sql_explanation = "";
if ($sql_data && isset($sql_data['debug'])) {
    $data_sources[] = 'sql_query';
    // SQLから日本語の説明を生成
    $sql = $sql_data['debug']['query'];
    
    if (stripos($sql, 'COUNT(*)') !== false) {
        if (stripos($sql, 'status = 3') !== false) {
            $sql_explanation = "読了した本の数を検索";
        } else if (stripos($sql, 'status = 2') !== false) {
            $sql_explanation = "読書中の本の数を検索";
        } else if (stripos($sql, 'status = 1') !== false) {
            $sql_explanation = "積読の本の数を検索";
        } else {
            $sql_explanation = "本の数を集計";
        }
    } else if (stripos($sql, 'AVG(') !== false) {
        $sql_explanation = "平均値を計算";
    } else if (stripos($sql, 'SELECT name') !== false || stripos($sql, 'SELECT title') !== false) {
        if (stripos($sql, 'LIMIT') !== false) {
            preg_match('/LIMIT\s+(\d+)/i', $sql, $matches);
            $limit = $matches[1] ?? '複数';
            $sql_explanation = "本のリストを{$limit}件取得";
        } else {
            $sql_explanation = "本のリストを取得";
        }
    } else {
        $sql_explanation = "データベースを検索";
    }
    
    if (isset($sql_data['count'])) {
        $sql_explanation .= "（結果: {$sql_data['count']}件）";
    }
    
    // データベース検索情報は全ユーザーに表示
    $text2sql_info = "\n📊 **データベース検索を実行しました**: {$sql_explanation}\n";
    
    // user_id=12の場合のみSQLクエリを表示
    if ($mine_user_id == 12) {
        $text2sql_info .= "<details style='cursor: pointer; color: #666; font-size: 0.9em; margin-top: 5px;'>\n";
        $text2sql_info .= "<summary>クエリを表示（デバッグ用）</summary>\n";
        $text2sql_info .= "```sql\n{$sql}\n```\n";
        $text2sql_info .= "</details>\n";
    }
    $text2sql_info .= "\n";
}

if ($sql_data && !empty($sql_data['data'])) {
    $system_prompt .= "【データベース検索結果】\n";
    $system_prompt .= "以下のデータを基に、単なる列挙ではなく分析的な回答を提供してください。\n";
    $system_prompt .= "結果数: " . count($sql_data['data']) . "件\n\n";
    
    // データタイプを判定して分析のヒントを追加
    if (isset($sql_data['data'][0])) {
        $first_item = $sql_data['data'][0];
        if (isset($first_item['rating'])) {
            $system_prompt .= "【分析のポイント】評価の分布、高評価の理由、ジャンルの傾向を含めてください。\n";
        }
        if (isset($first_item['finished_date'])) {
            $system_prompt .= "【分析のポイント】読書ペースの変化、季節性、読了期間の傾向を含めてください。\n";
        }
        if (isset($first_item['status'])) {
            $system_prompt .= "【分析のポイント】読書状況の内訳、積読の理由、読書計画の提案を含めてください。\n";
        }
    }
    
    $system_prompt .= "\nデータベースクエリ結果:\n";
    
    // 結果を整形
    if (count($sql_data['data']) <= 10) {
        // 少ない結果は詳細表示
        foreach ($sql_data['data'] as $index => $row) {
            $system_prompt .= ($index + 1) . ". ";
            $formatted_row = [];
            foreach ($row as $key => $value) {
                if ($value !== null) {
                    // キー名を日本語に変換
                    $key_jp = str_replace(
                        ['name', 'title', 'author', 'rating', 'status', 'finished_date', 'count', 'avg_rating', 'update_date'],
                        ['タイトル', 'タイトル', '著者', '評価', 'ステータス', '読了日', '件数', '平均評価', '更新日'],
                        $key
                    );
                    $formatted_row[] = "{$key_jp}: {$value}";
                }
            }
            $system_prompt .= implode(", ", $formatted_row) . "\n";
        }
    } else {
        // 多い結果は要約
        $system_prompt .= "合計 " . count($sql_data['data']) . " 件の結果があります。\n";
        $system_prompt .= "全リスト:\n";
        foreach ($sql_data['data'] as $index => $row) {
            $title = $row['name'] ?? $row['title'] ?? $row['tag_name'] ?? '項目' . ($index + 1);
            $author = $row['author'] ?? '';
            $rating = isset($row['rating']) && $row['rating'] > 0 ? "★{$row['rating']}" : '';
            
            $item_text = ($index + 1) . ". 「{$title}」";
            if ($author) $item_text .= " - {$author}";
            if ($rating) $item_text .= " {$rating}";
            
            $system_prompt .= $item_text . "\n";
            
            // 最大30件まで表示
            if ($index >= 29) {
                $system_prompt .= "... (残り " . (count($sql_data['data']) - 30) . " 件)\n";
                break;
            }
        }
    }
    
    $system_prompt .= "\n【重要】このデータを元に、ユーザーの質問に具体的に答えてください。\n";
    $system_prompt .= "- リストデータの場合は、必ず全件または主要な項目を番号付きリストで表示してください\n";
    $system_prompt .= "- 本のタイトルは必ず「」で囲んでください\n";
    $system_prompt .= "- データに存在しない情報は推測せず、『データがありません』と明確に伝えてください\n\n";
} else if ($sql_data && empty($sql_data['data'])) {
    $system_prompt .= "データベース検索結果: 該当するデータが見つかりませんでした。\n";
    $system_prompt .= "この事実を正直に伝え、代替案や別の視点からの提案を行ってください。\n\n";
}

if (!empty($context_parts)) {
    $system_prompt .= "追加のコンテキスト情報:\n" . implode("\n\n", $context_parts) . "\n\n";
}

$system_prompt .= "\n【回答の構成】\n";
$system_prompt .= "1. まず質問の意図を理解し、要約を1行で述べる\n";
$system_prompt .= "2. データがある場合は、以下の順で回答:\n";
$system_prompt .= "   a) 全体的な傾向や洞察（1-2文）\n";
$system_prompt .= "   b) 具体的なデータ（リスト形式）\n";
$system_prompt .= "   c) 分析から得られる提案や気づき（1-2文）\n";
$system_prompt .= "3. 回答のガイドライン:\n";
$system_prompt .= "   - 本のタイトルは「」で囲む\n";
$system_prompt .= "   - 数値データがある場合は具体的に示す\n";
$system_prompt .= "   - 読書習慣の改善につながる提案を含める\n";
$system_prompt .= "   - 単純な列挙ではなく、意味のあるグルーピングや分類を行う\n";

// デバッグ用: システムプロンプトの一部をログ出力
if ($sql_data && !empty($sql_data['data'])) {
    // SQLデータ処理
}

// ユーザーの本棚にある本のリストを追加
$user_books_sql = "SELECT DISTINCT name as title FROM b_book_list WHERE user_id = ?";
$user_books = $g_db->getAll($user_books_sql, [$mine_user_id], DB_FETCHMODE_ASSOC);
if (!DB::isError($user_books) && !empty($user_books)) {
    $book_titles = array_column($user_books, 'title');
    $system_prompt .= "\n【ユーザーの本棚にある本】\n";
    $system_prompt .= "以下の本はユーザーの本棚に既に存在します：\n";
    $system_prompt .= implode(", ", array_slice($book_titles, 0, 50)) . "\n";
    $system_prompt .= "これらの本について言及する際は、『本棚にある』ことを明示してください。";
}

// 会話履歴をメッセージ配列に変換
$messages = [
    ['role' => 'system', 'content' => $system_prompt]
];

// 直近の会話履歴を追加（コンテキスト維持）
if (!empty($user_context['conversation_history'])) {
    foreach (array_slice($user_context['conversation_history'], -5) as $hist) {
        if (isset($hist['role']) && isset($hist['content'])) {
            $role = $hist['role'] === 'user' ? 'user' : 'assistant';
            $messages[] = ['role' => $role, 'content' => $hist['content']];
        }
    }
}

// 現在のメッセージを追加（SQL実行時は明確な指示を追加）
$user_message = $message;
if ($sql_data && !empty($sql_data['data'])) {
    $result_count = count($sql_data['data']);
    if ($result_count > 20) {
        $user_message .= "\n\n【注意】" . $result_count . "件の検索結果があります。代表的なものを示し、全体の傾向を分析してください。";
    } else if ($result_count > 5) {
        $user_message .= "\n\n【注意】" . $result_count . "件の検索結果があります。パターンや傾向を見つけて、意味のあるグループ分けをしてください。";
    } else {
        $user_message .= "\n\n【注意】" . $result_count . "件の検索結果があります。それぞれの特徴を含めて詳しく説明してください。";
    }
}
$messages[] = ['role' => 'user', 'content' => $user_message];

try {
    // OpenAI API呼び出し
    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . OPENAI_API_KEY
    ]);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'model' => 'gpt-4o-mini',
        'messages' => $messages,
        'max_tokens' => 1500,  // リスト表示のため増加
        'temperature' => 0.5,  // 分析的な回答のため適度な温度に
        'presence_penalty' => 0.2,  // より多様な表現を促す
        'frequency_penalty' => 0.1
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 200) {
        $result = json_decode($response, true);
        if (isset($result['choices'][0]['message']['content'])) {
            $ai_response = $result['choices'][0]['message']['content'];
            
            // データソース情報とText2SQL情報を回答に追加
            if (!empty($source_info)) {
                $ai_response = $source_info . "\n" . $ai_response;
            }
            if (!empty($text2sql_info)) {
                $ai_response = $text2sql_info . $ai_response;
            }
            
            // デバッグ情報を追加（user_id=12の場合のみ）
            if ($mine_user_id == 12 && !empty($context_debug)) {
                $debug_text = "\n\n<details style='background: #f5f5f5; padding: 10px; border-radius: 5px; margin-top: 10px;'>\n";
                $debug_text .= "<summary style='cursor: pointer; color: #666; font-weight: bold;'>🔧 デバッグ情報</summary>\n";
                $debug_text .= "<div style='margin-top: 10px; font-family: monospace; font-size: 0.9em;'>\n";
                
                // Embedding検索のデバッグ情報
                if (!empty($context_debug['embedding'])) {
                    $embed_debug = $context_debug['embedding'];
                    $debug_text .= "**Embedding検索:**\n";
                    $debug_text .= "- ステータス: " . ($embed_debug['status'] ?? 'unknown') . "\n";
                    $debug_text .= "- 処理時間: " . ($embed_debug['total_time'] ?? 0) . "秒\n";
                    $debug_text .= "- チェックした本: " . ($embed_debug['total_books_checked'] ?? 0) . "冊\n";
                    $debug_text .= "- 高類似度の本: " . ($embed_debug['high_similarity_count'] ?? 0) . "冊\n";
                    $debug_text .= "- 返却数: " . ($embed_debug['returned_count'] ?? 0) . "冊\n";
                    
                    if (isset($embed_debug['embedding_size'])) {
                        $debug_text .= "- Embedding次元数: " . $embed_debug['embedding_size'] . "\n";
                    }
                    
                    if (isset($embed_debug['error'])) {
                        $debug_text .= "- エラー: " . substr($embed_debug['error'], 0, 100) . "...\n";
                    }
                }
                
                // Text2SQLのデバッグ情報
                if ($sql_data && isset($sql_data['debug'])) {
                    $debug_text .= "\n**Text2SQL:**\n";
                    $debug_text .= "- クエリ実行: " . (isset($sql_data['data']) ? '成功' : '失敗') . "\n";
                    $debug_text .= "- 結果件数: " . count($sql_data['data'] ?? []) . "件\n";
                    if (isset($sql_data['debug']['query'])) {
                        $debug_text .= "- SQL: `" . substr($sql_data['debug']['query'], 0, 100) . "`...\n";
                    }
                }
                
                // データソースサマリー
                $debug_text .= "\n**使用データソース:**\n";
                foreach (array_unique($data_sources) as $source) {
                    $debug_text .= "- " . $source . "\n";
                }
                
                $debug_text .= "</div>\n</details>\n";
                $ai_response .= $debug_text;
            }
            
            echo json_encode([
                'success' => true,
                'response' => $ai_response
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'response' => '申し訳ございません。適切な回答を生成できませんでした。'
            ]);
        }
    } else {
        echo json_encode([
            'success' => true,
            'response' => '申し訳ございません。現在混雑しています。しばらくしてからお試しください。'
        ]);
    }
} catch (Exception $e) {
    // エラー処理
    echo json_encode([
        'success' => true,
        'response' => '申し訳ございません。エラーが発生しました。'
    ]);
}
?>