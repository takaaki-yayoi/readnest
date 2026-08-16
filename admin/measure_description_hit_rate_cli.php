<?php
/**
 * 説明文の取得可能率を実測するCLIスクリプト
 *
 * b_book_repository の説明文が空の本を標本抽出し、実際に Google Books API を
 * 叩いて「今から埋め直したら何%取れるのか」を測る。
 * 21万件のバックフィルに着手する価値があるかの判断材料にする。
 *
 * DBは更新しない（GoogleBooksAPI クラス内部の google_books_cache には
 * ヒットした結果がキャッシュされる）。
 *
 * 使い方:
 *   php admin/measure_description_hit_rate_cli.php          … 50件で実測
 *   php admin/measure_description_hit_rate_cli.php 200      … 件数を指定
 */

if (php_sapi_name() !== 'cli') {
    die("This script must be run from command line\n");
}

require_once(dirname(__DIR__) . '/config.php');
require_once(dirname(__DIR__) . '/library/database.php');
require_once(dirname(__DIR__) . '/library/google_books_api.php');

global $g_db;

$sample_size = max(1, (int)($argv[1] ?? 50));
$sleep_ms = 300; // API連打を避ける

echo "説明文の取得可能率 実測\n";
echo str_repeat('=', 64) . "\n\n";
printf("標本数: %d 件 / API間隔: %d ms\n\n", $sample_size, $sleep_ms);

if (!defined('GOOGLE_BOOKS_API_KEY') || GOOGLE_BOOKS_API_KEY === '') {
    echo "※ GOOGLE_BOOKS_API_KEY が未設定です。APIキー無しでも動きますが、\n";
    echo "  レート制限が厳しくなります。\n\n";
}

// ユーザーが実際に持っている本のうち、説明文が無いものを新しい順に抽出する。
// b_book_repository を単体で引くと、誰も読んでいない本ばかり当たる可能性がある。
$recent_asins = $g_db->getAll("
    SELECT amazon_id
    FROM b_book_list
    WHERE amazon_id IS NOT NULL AND amazon_id != ''
    ORDER BY book_id DESC
    LIMIT 3000
", [], DB_FETCHMODE_ASSOC);

if (DB::isError($recent_asins) || empty($recent_asins)) {
    echo "b_book_list からASINを取得できませんでした\n";
    exit(1);
}

$asins = array_values(array_unique(array_column($recent_asins, 'amazon_id')));
$ph = implode(',', array_fill(0, count($asins), '?'));

$targets = $g_db->getAll("
    SELECT asin, title, author, isbn
    FROM b_book_repository
    WHERE asin IN ({$ph})
      AND (description IS NULL OR description = '')
    LIMIT " . $sample_size, $asins, DB_FETCHMODE_ASSOC);

if (DB::isError($targets)) {
    echo "対象の抽出に失敗: " . $targets->getMessage() . "\n";
    exit(1);
}
if (empty($targets)) {
    echo "説明文が空の本が見つかりませんでした（直近3000件の範囲では全て埋まっている）\n";
    exit(0);
}

printf("対象 %d 件で実測します\n\n", count($targets));

$api = new GoogleBooksAPI();

$hit = 0;
$miss = 0;
$error = 0;
$lengths = [];
$hit_examples = [];
$miss_examples = [];
$by_isbn = [
    'ISBNあり' => ['hit' => 0, 'total' => 0],
    'ISBNなし' => ['hit' => 0, 'total' => 0],
];

$start = microtime(true);

foreach ($targets as $i => $book) {
    $has_isbn = !empty($book['isbn']);
    $bucket = $has_isbn ? 'ISBNあり' : 'ISBNなし';
    $by_isbn[$bucket]['total']++;

    try {
        $info = $api->getBookInfo(
            $has_isbn ? $book['isbn'] : null,
            $book['title'],
            $book['author']
        );
    } catch (Exception $e) {
        $error++;
        printf("  [%3d] ERROR %s : %s\n", $i + 1, $book['asin'], $e->getMessage());
        usleep($sleep_ms * 1000);
        continue;
    }

    $desc = is_array($info) ? trim((string)($info['description'] ?? '')) : '';

    if ($desc !== '') {
        $hit++;
        $by_isbn[$bucket]['hit']++;
        $len = mb_strlen($desc);
        $lengths[] = $len;
        if (count($hit_examples) < 5) {
            $hit_examples[] = sprintf('%s（%d文字）', mb_strimwidth($book['title'], 0, 34, '…'), $len);
        }
    } else {
        $miss++;
        if (count($miss_examples) < 5) {
            $miss_examples[] = sprintf('%s / isbn=%s', mb_strimwidth($book['title'], 0, 34, '…'), $has_isbn ? $book['isbn'] : 'なし');
        }
    }

    if (($i + 1) % 10 === 0) {
        printf("  %d件処理（取得 %d / 空 %d / エラー %d）\n", $i + 1, $hit, $miss, $error);
    }

    usleep($sleep_ms * 1000);
}

$elapsed = microtime(true) - $start;
$done = $hit + $miss;

echo "\n" . str_repeat('-', 64) . "\n";
echo "結果\n\n";
printf("  実測 %d 件 / 所要 %.1f 秒（1件あたり %.2f 秒）\n\n", $done + $error, $elapsed, ($done + $error) > 0 ? $elapsed / ($done + $error) : 0);
printf("  説明文が取れた : %d 件 (%.1f%%)\n", $hit, $done > 0 ? $hit / $done * 100 : 0);
printf("  空だった       : %d 件 (%.1f%%)\n", $miss, $done > 0 ? $miss / $done * 100 : 0);
if ($error > 0) {
    printf("  APIエラー      : %d 件\n", $error);
}

echo "\n";
foreach ($by_isbn as $label => $stat) {
    if ($stat['total'] > 0) {
        printf("  %s : %d 件中 %d 件 (%.1f%%)\n",
            $label, $stat['total'], $stat['hit'], $stat['hit'] / $stat['total'] * 100);
    }
}

if (!empty($lengths)) {
    sort($lengths);
    $median = $lengths[intdiv(count($lengths), 2)];
    printf("\n  説明文の長さ : 中央値 %d文字 / 最短 %d / 最長 %d\n", $median, $lengths[0], end($lengths));
    $short = count(array_filter($lengths, function($l) { return $l < 100; }));
    printf("  うち100文字未満 : %d 件 (%.1f%%) ← 短すぎるとembeddingの改善効果は限定的\n",
        $short, $short / count($lengths) * 100);
}

if (!empty($hit_examples)) {
    echo "\n  取れた例:\n";
    foreach ($hit_examples as $e) { echo "    - {$e}\n"; }
}
if (!empty($miss_examples)) {
    echo "\n  取れなかった例:\n";
    foreach ($miss_examples as $e) { echo "    - {$e}\n"; }
}

// 全体への外挿
$remaining = $g_db->getOne("
    SELECT COUNT(*) FROM b_book_repository
    WHERE combined_embedding IS NOT NULL AND (description IS NULL OR description = '')
");
if (!DB::isError($remaining) && $done > 0) {
    $rate = $hit / $done;
    printf("\n  説明文なしでembedding保有: %d 件\n", (int)$remaining);
    printf("  この実測値を当てはめると、埋まる見込み: 約 %d 件\n", (int)round($remaining * $rate));
    printf("  所要時間の目安: 約 %.1f 時間（1件 %.2f 秒 × %d 件）\n",
        $remaining * ($elapsed / max(1, $done + $error)) / 3600,
        $elapsed / max(1, $done + $error), (int)$remaining);
}

echo "\n";
