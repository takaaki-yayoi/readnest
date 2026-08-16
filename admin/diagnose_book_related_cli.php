<?php
/**
 * book_detail.php の関連本（AI推薦 / 同じ著者）の状態を診断するCLIスクリプト
 *
 * 使い方:
 *   php admin/diagnose_book_related_cli.php                … 全体の統計のみ
 *   php admin/diagnose_book_related_cli.php B00XXXXXXX     … 指定ASINを診断
 *   php admin/diagnose_book_related_cli.php --book-id=1234 … ReadNestのURL /book/1234 から診断
 */

if (php_sapi_name() !== 'cli') {
    die("This script must be run from command line\n");
}

require_once(dirname(__DIR__) . '/config.php');
require_once(dirname(__DIR__) . '/library/database.php');
require_once(dirname(__DIR__) . '/library/vector_similarity.php');
require_once(dirname(__DIR__) . '/library/recommendation_pool.php');

if (!defined('RECOMMENDATION_POOL_SIZE')) {
    define('RECOMMENDATION_POOL_SIZE', 600);
}

global $g_db;

$target_arg = $argv[1] ?? '';
$target_asin = '';
$target_book_id = 0;

if (strpos($target_arg, '--book-id=') === 0) {
    $target_book_id = (int)substr($target_arg, strlen('--book-id='));
} elseif ($target_arg !== '') {
    $target_asin = $target_arg;
}

echo "book_detail 関連本 診断\n";
echo str_repeat('=', 60) . "\n\n";

// ---- 1. 全体統計 ----
echo "1. b_book_repository のembedding保有状況\n";
// google_books_checked はマイグレーション（admin/migrations/add_google_books_checked_column.sql）
// が適用済みの環境にしか無いので、存在を確かめてから集計に入れる
$has_gb_checked = $g_db->getRow("SHOW COLUMNS FROM b_book_repository LIKE 'google_books_checked'", [], DB_FETCHMODE_ASSOC);
$gb_checked_expr = (!DB::isError($has_gb_checked) && $has_gb_checked)
    ? "SUM(CASE WHEN google_books_checked = 1 AND (description IS NULL OR description = '') THEN 1 ELSE 0 END)"
    : "NULL";

$repo_stats = $g_db->getRow("
    SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN combined_embedding IS NOT NULL THEN 1 ELSE 0 END) AS with_embedding,
        SUM(CASE WHEN google_categories IS NOT NULL AND google_categories != '' THEN 1 ELSE 0 END) AS with_categories,
        SUM(CASE WHEN description IS NOT NULL AND description != '' THEN 1 ELSE 0 END) AS with_description,
        SUM(CASE WHEN combined_embedding IS NOT NULL
                  AND (description IS NULL OR description = '') THEN 1 ELSE 0 END) AS embedding_without_desc,
        {$gb_checked_expr} AS checked_no_desc
    FROM b_book_repository
", [], DB_FETCHMODE_ASSOC);

if (DB::isError($repo_stats)) {
    echo "   ERROR: " . $repo_stats->getMessage() . "\n";
} else {
    $total = (int)$repo_stats['total'];
    $pct = function($n) use ($total) { return $total > 0 ? $n / $total * 100 : 0; };
    $with = (int)$repo_stats['with_embedding'];
    $with_desc = (int)$repo_stats['with_description'];
    $no_desc_embed = (int)$repo_stats['embedding_without_desc'];

    printf("   総件数        : %d\n", $total);
    printf("   embedding有り : %d (%.1f%%)\n", $with, $pct($with));
    printf("   説明文有り    : %d (%.1f%%)\n", $with_desc, $pct($with_desc));
    printf("   カテゴリ有り  : %d\n", (int)$repo_stats['with_categories']);
    printf("   ⚠ 説明文なしでembeddingを作った本 : %d (embedding保有の%.1f%%)\n",
        $no_desc_embed, $with > 0 ? $no_desc_embed / $with * 100 : 0);
    echo "     ← この本たちの類似度は実質タイトルと著者名の文字列類似度になる\n";
    if ($repo_stats['checked_no_desc'] !== null) {
        printf("   うちGoogle Books確認済みで説明文が取れなかった本 : %d\n", (int)$repo_stats['checked_no_desc']);
        echo "     ← ここは説明文を埋めようとしても取れない（別の取得元が必要）\n";
    } else {
        echo "   google_books_checked 列なし（add_google_books_checked_column.sql が未適用）\n";
    }
}
echo "\n";

echo "2. b_popular_books_cache（cron/update_popular_books.php が毎時更新）\n";
$popular_stats = $g_db->getRow("
    SELECT
        COUNT(*) AS total,
        MAX(created_at) AS updated_at
    FROM b_popular_books_cache
", [], DB_FETCHMODE_ASSOC);

if (DB::isError($popular_stats)) {
    echo "   ERROR: " . $popular_stats->getMessage() . "\n";
    echo "   → テーブルが無い場合は cron/update_popular_books.php を一度実行してください\n";
} else {
    printf("   総件数    : %d\n", (int)$popular_stats['total']);
    printf("   最終更新  : %s\n", $popular_stats['updated_at'] ?? '(不明)');

    if ((int)$popular_stats['total'] === 0) {
        echo "   ⚠ 空です。cron が動いていないか、preCalculatePopularBooks() の INSERT が失敗しています。\n";
        echo "     → php cron/update_popular_books.php を手動実行してエラーを確認してください。\n";
    }
    // 注意: ここで b_book_repository と JOIN して embedding 保有数を数えてはいけない。
    // pc.amazon_id に索引が無いため 17万行 × 23万行 のネストループになり、
    // MySQL が接続ごと落とす（2006 MySQL server has gone away）。
    // 保有数は下の「3. 候補プール」で上位N件に限定して数える。
}

// 直近のcron実行ログ
$cron_log = $g_db->getAll("
    SELECT status, message, created_at
    FROM b_cron_log
    WHERE cron_type = 'update_popular_books'
    ORDER BY created_at DESC
    LIMIT 3
", [], DB_FETCHMODE_ASSOC);
if (DB::isError($cron_log)) {
    echo "   直近のcronログ: 取得不可（" . $cron_log->getMessage() . "）\n";
} elseif (empty($cron_log)) {
    echo "   直近のcronログ: 0件（b_cron_log への記録が失敗している可能性）\n";
} else {
    echo "   直近のcronログ:\n";
    foreach ($cron_log as $log) {
        printf("     [%s] %s : %s\n",
            date('Y-m-d H:i', is_numeric($log['created_at']) ? (int)$log['created_at'] : strtotime((string)$log['created_at'])),
            $log['status'],
            $log['message']
        );
    }
}
echo "\n";

// ---- 3. 候補プールの実測 ----
echo "3. 候補プール（library/recommendation_pool.php）\n";
$pool_start = microtime(true);
$pool_asins = getRecommendationPoolAsins(RECOMMENDATION_POOL_SIZE);
$pool_elapsed = (microtime(true) - $pool_start) * 1000;
printf("   ASIN取得 : %d / %d （%.0f ms）\n", count($pool_asins), RECOMMENDATION_POOL_SIZE, $pool_elapsed);

if (empty($pool_asins)) {
    echo "   ⚠ プールが0件です。この状態では同著者の候補しか出ません。\n";
} else {
    // 主キー引きなので件数を絞れば一瞬で終わる
    $embed_start = microtime(true);
    $ph = implode(',', array_fill(0, count($pool_asins), '?'));
    $with_embedding = $g_db->getOne("
        SELECT COUNT(*)
        FROM b_book_repository
        WHERE asin IN ({$ph}) AND combined_embedding IS NOT NULL
    ", $pool_asins);
    $embed_elapsed = (microtime(true) - $embed_start) * 1000;
    if (!DB::isError($with_embedding)) {
        printf("   うちembedding有り : %d （%.0f ms） ← 実際に類似度計算にかかる候補数\n",
            (int)$with_embedding, $embed_elapsed);
    }
}
echo "\n";

// ---- 4. b_book_list の本が b_book_repository に載っているか（標本調査）----
// 直近に登録された本を見る。ASIN順に取ると先頭が特定の出版国に偏るため、
// 主キー降順（＝登録が新しい順）で取る。登録直後の本が対象外なら
// バッチが追いついていない、という判断ができる。
echo "4. b_book_repository の網羅率（直近登録された本500件で照合）\n";
$sample_rows = $g_db->getAll("
    SELECT amazon_id
    FROM b_book_list
    WHERE amazon_id IS NOT NULL AND amazon_id != ''
    ORDER BY book_id DESC
    LIMIT 500
", [], DB_FETCHMODE_ASSOC);

if (!DB::isError($sample_rows) && !empty($sample_rows)) {
    $sample_asins = array_values(array_unique(array_column($sample_rows, 'amazon_id')));
    $sph = implode(',', array_fill(0, count($sample_asins), '?'));
    $in_repo = $g_db->getOne("SELECT COUNT(DISTINCT asin) FROM b_book_repository WHERE asin IN ({$sph})", $sample_asins);
    $has_embed = $g_db->getOne("SELECT COUNT(DISTINCT asin) FROM b_book_repository WHERE asin IN ({$sph}) AND combined_embedding IS NOT NULL", $sample_asins);
    if (!DB::isError($in_repo) && !DB::isError($has_embed)) {
        $n = count($sample_asins);
        printf("   標本 %d 件中、repositoryにあり %d 件 (%.1f%%) / うちembedding有り %d 件 (%.1f%%)\n",
            $n, (int)$in_repo, $n ? (int)$in_repo / $n * 100 : 0,
            (int)$has_embed, $n ? (int)$has_embed / $n * 100 : 0);
        echo "   ← ここが低いと、本棚の本の多くがAI推薦の対象外になる\n";
    }
}
echo "\n";

if ($target_asin === '' && $target_book_id === 0) {
    echo "本を指定すると、候補プールと類似度上位を表示します。\n";
    echo "  php admin/diagnose_book_related_cli.php B00XXXXXXX\n";
    echo "  php admin/diagnose_book_related_cli.php --book-id=1234   （ReadNestのURL /book/1234 の数字）\n";
    exit(0);
}

// ---- 5. 指定した本の診断 ----
echo str_repeat('-', 60) . "\n";

// book_id 指定ならASINを引く
if ($target_book_id > 0) {
    $book_row = $g_db->getRow("
        SELECT book_id, name, author, amazon_id
        FROM b_book_list
        WHERE book_id = ?
    ", [$target_book_id], DB_FETCHMODE_ASSOC);

    if (DB::isError($book_row) || !$book_row) {
        echo "5. book_id {$target_book_id} は b_book_list に存在しません\n";
        exit(1);
    }

    printf("5. book_id %d の診断\n\n", $target_book_id);
    printf("   タイトル : %s\n", $book_row['name']);
    printf("   著者     : %s\n", $book_row['author']);
    printf("   ASIN     : %s\n\n", $book_row['amazon_id'] !== '' ? $book_row['amazon_id'] : '(なし)');

    if (empty($book_row['amazon_id'])) {
        echo "   ASINが無いためAI推薦は動作しません（同じ著者の関連本のみ）\n";
        exit(1);
    }
    $target_asin = $book_row['amazon_id'];
} else {
    echo "5. ASIN {$target_asin} の診断\n\n";
}

$repo_info = $g_db->getRow("
    SELECT asin, title, author, google_categories, combined_embedding,
           description, embedding_type, embedding_has_description
    FROM b_book_repository
    WHERE asin = ?
", [$target_asin], DB_FETCHMODE_ASSOC);

if (DB::isError($repo_info) || !$repo_info) {
    echo "   ⚠ b_book_repository に行がありません\n";
    echo "     → AI推薦は動作しません（同じ著者の関連本のみ表示される）\n";
    echo "     → b_book_repository への登録は admin/batch_generate_embeddings.php 系のバッチ待ち\n";

    // ReadNest内で何人が読んでいるかは分かる
    $readers = $g_db->getOne("SELECT COUNT(DISTINCT user_id) FROM b_book_list WHERE amazon_id = ?", [$target_asin]);
    if (!DB::isError($readers)) {
        printf("     （この本のReadNest内の読者数: %d 人）\n", (int)$readers);
    }
    exit(1);
}

printf("   タイトル : %s\n", $repo_info['title']);
printf("   著者     : %s\n", $repo_info['author']);
printf("   カテゴリ : %s\n", $repo_info['google_categories'] ?: '(なし)');

if (empty($repo_info['combined_embedding'])) {
    echo "   embedding: なし → 初回アクセス時に動的生成が走ります\n";
    exit(1);
}

$book_vector = json_decode($repo_info['combined_embedding'], true);
if (!is_array($book_vector) || empty($book_vector)) {
    echo "   embedding: デコード失敗（truncated の可能性）→ admin/fix_embedding_cli.php を確認\n";
    exit(1);
}
printf("   embedding: %d次元 (%d bytes)\n", count($book_vector), strlen($repo_info['combined_embedding']));

// embeddingの材料。descriptionが無いとタイトルと著者名だけで作られるため、
// 主題ではなくタイトルの語形（「実践」「入門」など）に引っ張られる。
$desc_len = mb_strlen((string)($repo_info['description'] ?? ''));
printf("   embeddingの材料 : description %s / type=%s\n\n",
    $desc_len > 0 ? "{$desc_len}文字" : "なし ⚠",
    $repo_info['embedding_type'] ?: '(不明)');

// 候補ASINの収集（book_detail.php と同じ優先順・同じ上限）
if (!defined('RECOMMENDATION_CANDIDATE_MAX')) {
    define('RECOMMENDATION_CANDIDATE_MAX', 600);
}

$candidate_asins = [];
$addAsins = function($rows, $column, $label) use (&$candidate_asins, $target_asin) {
    if (DB::isError($rows)) {
        printf("   %-16s : ERROR %s\n", $label, $rows->getMessage());
        return;
    }
    $added = 0;
    $capped = false;
    foreach ((array)$rows as $row) {
        if (count($candidate_asins) >= RECOMMENDATION_CANDIDATE_MAX) {
            $capped = true;
            break;
        }
        $asin = (string)($row[$column] ?? '');
        if ($asin === '' || $asin === $target_asin || isset($candidate_asins[$asin])) {
            continue;
        }
        $candidate_asins[$asin] = true;
        $added++;
    }
    printf("   %-16s : +%d 件%s\n", $label, $added, $capped ? '（上限に到達）' : '');
};

echo "   候補ASINの収集:\n";

$co_start = microtime(true);
$addAsins($g_db->getAll("
    SELECT bl2.amazon_id,
           COUNT(DISTINCT bl2.user_id) AS co_count,
           MAX(bl2.update_date) AS last_update
    FROM (
        SELECT DISTINCT bl1.user_id
        FROM b_book_list bl1
        INNER JOIN b_user u ON u.user_id = bl1.user_id
        WHERE bl1.amazon_id = ? AND u.diary_policy = 1 AND u.status = 1
        LIMIT 50
    ) r
    INNER JOIN b_book_list bl2 ON bl2.user_id = r.user_id
    WHERE bl2.amazon_id IS NOT NULL AND bl2.amazon_id != '' AND bl2.amazon_id != ?
    GROUP BY bl2.amazon_id
    ORDER BY co_count DESC, last_update DESC, bl2.amazon_id ASC
    LIMIT 300
", [$target_asin, $target_asin], DB_FETCHMODE_ASSOC), 'amazon_id', '協調フィルタ');
printf("   %-16s   （%.0f ms）\n", '', (microtime(true) - $co_start) * 1000);

if (!empty($repo_info['author'])) {
    $addAsins($g_db->getAll("
        SELECT br.asin FROM b_book_repository br
        WHERE br.author = ? AND br.combined_embedding IS NOT NULL LIMIT 20
    ", [$repo_info['author']], DB_FETCHMODE_ASSOC), 'asin', '同著者');
}

// 同カテゴリの候補取得は book_detail.php 側で削除したのでここにも無い

$addAsins(array_map(function($a) { return ['amazon_id' => $a]; }, $pool_asins), 'amazon_id', '人気順');

$candidates = [];
if (!empty($candidate_asins)) {
    $asin_list = array_keys($candidate_asins);
    $ph2 = implode(',', array_fill(0, count($asin_list), '?'));
    $fetch_start = microtime(true);
    $rows = $g_db->getAll("
        SELECT br.asin, br.title, br.author, br.combined_embedding, br.google_categories
        FROM b_book_repository br
        WHERE br.asin IN ({$ph2}) AND br.combined_embedding IS NOT NULL
    ", $asin_list, DB_FETCHMODE_ASSOC);
    $fetch_elapsed = (microtime(true) - $fetch_start) * 1000;
    if (!DB::isError($rows)) {
        $candidates = $rows;
    }
    printf("   embedding取得    : %d 件（%.0f ms）\n", count($candidates), $fetch_elapsed);
}

printf("   合計             : %d 件\n\n", count($candidates));

if (empty($candidates)) {
    echo "   候補が0件です。embedding生成のバッチ状況を確認してください。\n";
    exit(1);
}

$start = microtime(true);
$scored = [];
foreach ($candidates as $candidate) {
    $similarity = VectorSimilarity::cosineSimilarityWithVector($book_vector, $candidate['combined_embedding']);
    if ($similarity <= 0) {
        continue;
    }
    $scored[] = [
        'asin' => $candidate['asin'],
        'title' => $candidate['title'],
        'author' => $candidate['author'],
        'similarity' => $similarity
    ];
}
$elapsed = (microtime(true) - $start) * 1000;

usort($scored, function($a, $b) {
    return $b['similarity'] <=> $a['similarity'];
});

printf("6. 類似度計算 (%d件 / %.0f ms)\n\n", count($scored), $elapsed);

$over_threshold = array_filter($scored, function($s) { return $s['similarity'] > 0.5; });
printf("   閾値0.5超え : %d 件 ← これが0だと関連本セクションは表示されない\n\n", count($over_threshold));

// 上位の本がdescription付きembeddingかどうかを併記する。
// desc無しの本ばかりが上位に来ているなら、閾値ではなく
// embeddingの材料が精度を決めている。
$top = array_slice($scored, 0, 15);
$top_desc = [];
if (!empty($top)) {
    $top_asins = array_column($top, 'asin');
    $tph = implode(',', array_fill(0, count($top_asins), '?'));
    $desc_rows = $g_db->getAll("
        SELECT asin, CHAR_LENGTH(COALESCE(description, '')) AS desc_len
        FROM b_book_repository
        WHERE asin IN ({$tph})
    ", $top_asins, DB_FETCHMODE_ASSOC);
    if (!DB::isError($desc_rows)) {
        foreach ($desc_rows as $d) {
            $top_desc[$d['asin']] = (int)$d['desc_len'];
        }
    }
}

echo "   類似度 上位15件（desc = embeddingに使われた説明文の文字数）:\n";
foreach ($top as $i => $s) {
    $len = $top_desc[$s['asin']] ?? 0;
    printf("   %2d. %5.1f%%  desc%-6s %s / %s\n",
        $i + 1,
        $s['similarity'] * 100,
        $len > 0 ? $len : 'なし',
        mb_strimwidth($s['title'], 0, 40, '…'),
        mb_strimwidth((string)$s['author'], 0, 18, '…')
    );
}
echo "\n";
