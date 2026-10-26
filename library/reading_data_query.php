<?php
/**
 * 読書データ検索（Text2SQLの安全な置き換え）
 *
 * 旧方式（text2sql_functions.php）はLLMが生成したSQLをそのまま実行していたため、
 * プロンプト操作で他ユーザーのデータや任意のテーブルを読み出せた。
 * この方式ではLLMには検索条件のJSONだけを返させ、PHP側で固定の形のクエリに当てはめる。
 *  - 対象は本人の b_book_list と b_book_tags のみ。user_id は常にプレースホルダで固定
 *  - 列名・並び順・集計方法は許可リストから選ぶだけで、LLMの出力をSQL文字列に埋め込まない
 *  - 値（著者名・期間など）はすべてプレースホルダで渡す
 */

require_once(dirname(__FILE__) . '/openai_models.php');

const READING_DATA_STATUS_LABELS = [
    0 => 'いつか買う',
    1 => '積読',
    2 => '読書中',
    3 => '読了',
    4 => '既読',
];

/**
 * 質問から検索条件を抽出（OpenAI API）
 *
 * @return array|null 生の条件。データの質問でない・抽出に失敗した場合はnull
 */
function extractReadingDataConditions(string $question): ?array {
    $today = date('Y-m-d');

    $system_prompt = "あなたは読書記録アプリの検索条件抽出器です。
ユーザーの質問が、本人の本棚データ（冊数・一覧・評価・ページ数・期間・著者・タグ）を集計・抽出すれば答えられるものかを判定し、検索条件をJSONで返してください。
SQLは書かないでください。今日は {$today} です。

返すJSONの形（使わない項目は null）:
{
  \"is_data_question\": true/false,       // 本棚データの集計・抽出で答えられる質問か。本の推薦や感想の相談は false
  \"metric\": \"count\" | \"list\" | \"avg_rating\" | \"total_pages\" | \"breakdown\",
  \"group_by\": \"month\" | \"year\" | \"author\" | \"status\" | \"rating\" | \"tag\" | null,  // metric=breakdown のとき必須
  \"statuses\": [0-4の整数],               // 0:いつか買う 1:積読 2:読書中 3:読了 4:既読。指定なしは []
  \"date_field\": \"finished\" | \"registered\" | null,  // 期間をどの日付で絞るか。finished=読了日、registered=本棚に登録した日
  \"date_from\": \"YYYY-MM-DD\" | null,
  \"date_to\": \"YYYY-MM-DD\" | null,
  \"rating_min\": 1-5 | null,
  \"rating_max\": 1-5 | null,
  \"author\": \"著者名（部分一致）\" | null,
  \"title\": \"タイトルの一部\" | null,
  \"tag\": \"タグ・ジャンル名\" | null,
  \"order_by\": \"finished_date\" | \"rating\" | \"registered\" | \"pages\" | null,
  \"order\": \"asc\" | \"desc\",
  \"limit\": 1-50 | null
}

判断のルール:
- 「読んだ」「読了」は statuses [3, 4]、date_field は finished
- 「積読」は [1]、「読みたい」「いつか買う」は [0]、「読書中」「読んでいる」は [2]
- 「登録した」「追加した」は date_field registered
- 「今年」「先月」「去年」などは具体的な日付範囲にする
- 「何冊」「数」は count、「一覧」「どの本」「リスト」は list、「平均評価」は avg_rating、「何ページ」は total_pages
- 「月ごと」「推移」は breakdown + month、「著者別」「よく読む作家」は breakdown + author、「ジャンル別」は breakdown + tag
- 「評価が高い本」は list + order_by rating + order desc";

    $messages = [
        ['role' => 'system', 'content' => $system_prompt],
        ['role' => 'user', 'content' => $question]
    ];

    try {
        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . OPENAI_API_KEY
        ]);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(openaiChatParams([
            'model' => openaiChatModel(),
            'messages' => $messages,
            'max_tokens' => 300,
            'temperature' => 0.1,
            'response_format' => ['type' => 'json_object']
        ])));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code !== 200 || !$response) {
            error_log("[ReadingDataQuery] OpenAI API error: HTTP {$http_code}");
            return null;
        }

        $result = json_decode($response, true);
        $conditions = json_decode($result['choices'][0]['message']['content'] ?? '', true);
        if (!is_array($conditions) || empty($conditions['is_data_question'])) {
            return null;
        }
        return $conditions;
    } catch (Exception $e) {
        error_log('[ReadingDataQuery] ' . $e->getMessage());
        return null;
    }
}

/**
 * LLMが返した条件を許可リストで検証・正規化
 *
 * @return array|null 不正な条件の場合はnull（通常の会話にフォールバックさせる）
 */
function normalizeReadingDataConditions(array $raw): ?array {
    $metric = $raw['metric'] ?? '';
    if (!in_array($metric, ['count', 'list', 'avg_rating', 'total_pages', 'breakdown'], true)) {
        return null;
    }

    $group_by = $raw['group_by'] ?? null;
    if ($metric === 'breakdown') {
        if (!in_array($group_by, ['month', 'year', 'author', 'status', 'rating', 'tag'], true)) {
            return null;
        }
    } else {
        $group_by = null;
    }

    $statuses = [];
    foreach ((array)($raw['statuses'] ?? []) as $s) {
        if (is_numeric($s) && isset(READING_DATA_STATUS_LABELS[(int)$s])) {
            $statuses[] = (int)$s;
        }
    }
    $statuses = array_values(array_unique($statuses));

    $date_from = readingDataValidDate($raw['date_from'] ?? null);
    $date_to = readingDataValidDate($raw['date_to'] ?? null);
    $date_field = in_array($raw['date_field'] ?? null, ['finished', 'registered'], true) ? $raw['date_field'] : null;
    if (($date_from || $date_to) && !$date_field) {
        $date_field = 'finished';
    }
    // 「今年」が12/31までになると、回答側が残りの月も含めて読書ペースを計算してしまうため今日で打ち切る
    $today = date('Y-m-d');
    if ($date_to !== null && $date_to > $today) {
        $date_to = $today;
    }

    $rating_min = readingDataValidRating($raw['rating_min'] ?? null);
    $rating_max = readingDataValidRating($raw['rating_max'] ?? null);

    $order_by = in_array($raw['order_by'] ?? null, ['finished_date', 'rating', 'registered', 'pages'], true) ? $raw['order_by'] : null;
    $order = (($raw['order'] ?? '') === 'asc') ? 'asc' : 'desc';

    $limit = is_numeric($raw['limit'] ?? null) ? (int)$raw['limit'] : 20;
    $limit = max(1, min(50, $limit));

    return [
        'metric' => $metric,
        'group_by' => $group_by,
        'statuses' => $statuses,
        'date_field' => $date_field,
        'date_from' => $date_from,
        'date_to' => $date_to,
        'rating_min' => $rating_min,
        'rating_max' => $rating_max,
        'author' => readingDataValidText($raw['author'] ?? null),
        'title' => readingDataValidText($raw['title'] ?? null),
        'tag' => readingDataValidText($raw['tag'] ?? null),
        'order_by' => $order_by,
        'order' => $order,
        'limit' => $limit,
    ];
}

function readingDataValidDate($value): ?string {
    if (!is_string($value)) return null;
    $d = DateTime::createFromFormat('!Y-m-d', $value);
    return ($d && $d->format('Y-m-d') === $value) ? $value : null;
}

function readingDataValidRating($value): ?int {
    return (is_numeric($value) && (int)$value >= 1 && (int)$value <= 5) ? (int)$value : null;
}

function readingDataValidText($value): ?string {
    if (!is_string($value)) return null;
    $value = trim($value);
    return ($value === '') ? null : mb_substr($value, 0, 100);
}

/**
 * LIKE用に % と _ をエスケープ
 */
function readingDataLike(string $value): string {
    return '%' . addcslashes($value, '%_\\') . '%';
}

/**
 * 正規化済みの条件からクエリを組み立てる
 *
 * @return array [SQL, パラメータ]
 */
function buildReadingDataQuery(array $c, int $user_id): array {
    $where = ['bl.user_id = ?'];
    $params = [$user_id];
    $joins = '';

    if (!empty($c['statuses'])) {
        $where[] = 'bl.status IN (' . implode(',', array_fill(0, count($c['statuses']), '?')) . ')';
        $params = array_merge($params, $c['statuses']);
    }

    // 読了日はDATE型、登録日（create_date）はUNIX時刻
    $date_expr = ($c['date_field'] === 'registered') ? 'FROM_UNIXTIME(bl.create_date)' : 'bl.finished_date';
    if ($c['date_from']) {
        $where[] = ($c['date_field'] === 'registered') ? 'bl.create_date >= UNIX_TIMESTAMP(?)' : 'bl.finished_date >= ?';
        $params[] = $c['date_from'];
    }
    if ($c['date_to']) {
        $where[] = ($c['date_field'] === 'registered') ? 'bl.create_date < UNIX_TIMESTAMP(DATE_ADD(?, INTERVAL 1 DAY))' : 'bl.finished_date <= ?';
        $params[] = $c['date_to'];
    }

    if ($c['rating_min'] !== null) {
        $where[] = 'bl.rating >= ?';
        $params[] = $c['rating_min'];
    }
    if ($c['rating_max'] !== null) {
        $where[] = 'bl.rating > 0 AND bl.rating <= ?';
        $params[] = $c['rating_max'];
    }

    if ($c['author'] !== null) {
        $where[] = 'bl.author LIKE ?';
        $params[] = readingDataLike($c['author']);
    }
    if ($c['title'] !== null) {
        $where[] = 'bl.name LIKE ?';
        $params[] = readingDataLike($c['title']);
    }

    if ($c['group_by'] === 'tag') {
        // タグ別の内訳はタグ単位で数えるためJOINする
        $joins = ' INNER JOIN b_book_tags bt ON bt.book_id = bl.book_id AND bt.user_id = bl.user_id';
        if ($c['tag'] !== null) {
            $where[] = 'bt.tag_name LIKE ?';
            $params[] = readingDataLike($c['tag']);
        }
    } elseif ($c['tag'] !== null) {
        $where[] = 'EXISTS (SELECT 1 FROM b_book_tags bt WHERE bt.book_id = bl.book_id AND bt.user_id = bl.user_id AND bt.tag_name LIKE ?)';
        $params[] = readingDataLike($c['tag']);
    }

    switch ($c['metric']) {
        case 'count':
            $select = 'COUNT(*) AS count';
            $tail = '';
            break;
        case 'avg_rating':
            $select = 'ROUND(AVG(bl.rating), 2) AS avg_rating, COUNT(*) AS count';
            $where[] = 'bl.rating > 0';
            $tail = '';
            break;
        case 'total_pages':
            $select = 'SUM(bl.total_page) AS total_pages, COUNT(*) AS count';
            $tail = '';
            break;
        case 'breakdown':
            $group_exprs = [
                'month' => "DATE_FORMAT({$date_expr}, '%Y-%m')",
                'year' => "DATE_FORMAT({$date_expr}, '%Y')",
                'author' => 'bl.author',
                'status' => 'bl.status',
                'rating' => 'bl.rating',
                'tag' => 'bt.tag_name',
            ];
            $group_expr = $group_exprs[$c['group_by']];
            if (in_array($c['group_by'], ['month', 'year'], true)) {
                $where[] = ($c['date_field'] === 'registered') ? 'bl.create_date > 0' : 'bl.finished_date IS NOT NULL';
                // 古い順にLIMITすると直近の月が抜けるため、新しい順に取って実行後に時系列へ並べ直す
                $order_sql = 'label DESC';
            } elseif ($c['group_by'] === 'rating') {
                $where[] = 'bl.rating > 0'; // 0は未評価
                $order_sql = 'label DESC';
            } else {
                $where[] = "{$group_expr} IS NOT NULL AND {$group_expr} != ''";
                $order_sql = 'count DESC';
            }
            $select = "{$group_expr} AS label, COUNT(DISTINCT bl.book_id) AS count";
            $tail = " GROUP BY label ORDER BY {$order_sql} LIMIT " . readingDataBreakdownLimit($c['group_by']);
            break;
        default: // list
            $order_columns = [
                'finished_date' => 'bl.finished_date IS NULL, bl.finished_date',
                'rating' => 'bl.rating',
                'registered' => 'bl.create_date',
                'pages' => 'bl.total_page',
            ];
            // 評価などは同値が多く、並びが決まらないと古い本ばかり選ばれるため、最近読んだ本を優先する
            $order_sql = isset($order_columns[$c['order_by']])
                ? $order_columns[$c['order_by']] . ' ' . strtoupper($c['order']) . ', bl.finished_date IS NULL, bl.finished_date DESC, bl.update_date DESC'
                : 'bl.update_date DESC';
            $select = 'bl.name, bl.author, bl.status, bl.rating, bl.finished_date, bl.total_page';
            $tail = " ORDER BY {$order_sql} LIMIT " . (int)$c['limit'];
    }

    $sql = "SELECT {$select} FROM b_book_list bl{$joins} WHERE " . implode(' AND ', $where) . $tail;
    return [$sql, $params];
}

/**
 * 内訳の最大件数（月別は直近2年分）
 */
function readingDataBreakdownLimit(string $group_by): int {
    return ($group_by === 'month') ? 24 : 50;
}

/**
 * 条件の日本語説明（回答冒頭の「データベース検索を実行しました」に使う）
 */
function describeReadingDataConditions(array $c, array $rows): string {
    // チャット画面は回答をHTMLとして表示するため、利用者が入力した文字列はエスケープする
    $h = function($v) { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); };
    $parts = [];
    if ($c['date_from'] || $c['date_to']) {
        $range = ($c['date_from'] ?? '') . '〜' . ($c['date_to'] ?? '');
        $parts[] = $range . (($c['date_field'] === 'registered') ? 'に登録した' : 'に読了した');
    }
    if ($c['author'] !== null) $parts[] = "著者「{$h($c['author'])}」の";
    if ($c['title'] !== null) $parts[] = "タイトルに「{$h($c['title'])}」を含む";
    if ($c['tag'] !== null && $c['group_by'] !== 'tag') $parts[] = "タグ「{$h($c['tag'])}」の";
    if ($c['rating_min'] !== null || $c['rating_max'] !== null) {
        $parts[] = '評価' . ($c['rating_min'] ?? 1) . '〜' . ($c['rating_max'] ?? 5) . 'の';
    }
    // 「読了した読了・既読の本」と重ならないよう、読了日で絞っていて読了・既読だけなら省く
    $only_finished = !array_diff($c['statuses'], [3, 4]);
    if (!empty($c['statuses']) && !($c['date_field'] === 'finished' && ($c['date_from'] || $c['date_to']) && $only_finished)) {
        $labels = array_map(function($s) { return READING_DATA_STATUS_LABELS[$s]; }, $c['statuses']);
        $parts[] = implode('・', $labels) . 'の';
    }
    $target = implode('', $parts) . '本';

    $group_labels = ['month' => '月別', 'year' => '年別', 'author' => '著者別', 'status' => 'ステータス別', 'rating' => '評価別', 'tag' => 'タグ別'];
    switch ($c['metric']) {
        case 'count':       $action = 'の数を集計'; break;
        case 'avg_rating':  $action = 'の平均評価を計算'; break;
        case 'total_pages': $action = 'の合計ページ数を計算'; break;
        case 'breakdown':
            $action = 'を' . $group_labels[$c['group_by']] . 'に集計';
            if ($c['group_by'] === 'month') {
                $action .= '（読了のあった月のうち新しい' . readingDataBreakdownLimit('month') . 'か月分。0冊の月は結果に含まれない）';
            }
            break;
        default:            $action = "のリストを最大{$c['limit']}件取得";
    }

    // 冊数・平均などの集計は結果が常に1行なので、件数ではなく値を出す
    if ($c['metric'] === 'count') {
        return $target . $action . '（結果: ' . (int)($rows[0]['count'] ?? 0) . '冊）';
    }
    if (in_array($c['metric'], ['avg_rating', 'total_pages'], true)) {
        return $target . $action . '（対象: ' . (int)($rows[0]['count'] ?? 0) . '冊）';
    }
    return $target . $action . '（結果: ' . count($rows) . '件）';
}

/**
 * 質問に対して読書データ検索を実行
 *
 * 旧 executeText2SQL() と同じ形（success / data / count / debug）で返す。
 * データの質問でない・条件が不正な場合は success=false（通常の会話で答える）
 */
function executeReadingDataQuery(string $question, int $user_id): array {
    global $g_db;

    $raw = extractReadingDataConditions($question);
    if ($raw === null) {
        return ['success' => false, 'error' => 'Not a data question'];
    }
    $conditions = normalizeReadingDataConditions($raw);
    if ($conditions === null) {
        return ['success' => false, 'error' => 'Invalid conditions'];
    }

    [$sql, $params] = buildReadingDataQuery($conditions, $user_id);
    $rows = $g_db->getAll($sql, $params, DB_FETCHMODE_ASSOC);
    if (DB::isError($rows)) {
        error_log('[ReadingDataQuery] Query failed: ' . $rows->getMessage());
        return ['success' => false, 'error' => 'Query failed'];
    }

    if (in_array($conditions['group_by'], ['month', 'year'], true)) {
        $rows = array_reverse($rows); // 新しい順に取得したので時系列（古い順）に戻す
    }

    // ステータスは数値のままだとLLMが解釈できないため表示名に変換
    foreach ($rows as &$row) {
        if (isset($row['status']) && isset(READING_DATA_STATUS_LABELS[(int)$row['status']])) {
            $row['status'] = READING_DATA_STATUS_LABELS[(int)$row['status']];
        }
        if ($conditions['group_by'] === 'status' && isset(READING_DATA_STATUS_LABELS[(int)$row['label']])) {
            $row['label'] = READING_DATA_STATUS_LABELS[(int)$row['label']];
        }
        if ($conditions['group_by'] === 'rating') {
            $row['label'] = '★' . $row['label'];
        }
    }
    unset($row);

    return [
        'success' => true,
        'data' => $rows,
        'count' => count($rows),
        'explanation' => describeReadingDataConditions($conditions, $rows),
        'debug' => [
            'mode' => 'reading_data_query',
            'query' => $sql,
            'params' => $params,
            'conditions' => $conditions,
        ],
    ];
}
