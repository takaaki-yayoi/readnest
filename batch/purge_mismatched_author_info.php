<?php
/**
 * 誤った作家情報（別人のWikipedia記事）をパージする一回限りのバッチ
 *
 * 背景
 *   library/author_info_fetcher.php が Wikipedia の全文検索（list=search）の1位を
 *   タイトル検証なしで採用していたため、翻訳者や同姓の別人、さらには概念・作品の
 *   記事が作家紹介として保存されていた。
 *   例) 有川真由美 → 「メイプルタウン物語 (映画)」、志村史夫 → 「磁力」
 *
 *   取得ロジックは修正済みだが、誤った内容は b_author_info（30日TTL）と
 *   ファイルキャッシュ（30日）に残るため、明示的に消して取り直させる。
 *
 * 判定
 *   保存されている wikipedia_url の記事タイトルが作家名と一致しないものを誤りとみなす。
 *   Wikipedia API は叩かないので判定自体は高速。
 *   リダイレクト由来の正しい対応（Mark Twain → マーク・トウェイン）も不一致として
 *   消えるが、次回取得時に修正済みロジックで正しく引き直されるため実害はない。
 *
 * 使い方
 *   php batch/purge_mismatched_author_info.php              # 確認のみ（既定）
 *   php batch/purge_mismatched_author_info.php --stats      # source別の件数だけ見る
 *   php batch/purge_mismatched_author_info.php --apply      # 実際に削除
 *   php batch/purge_mismatched_author_info.php --apply --refetch --limit=200
 *   php batch/purge_mismatched_author_info.php --author='有川 真由美'  # 1名だけ試す
 *
 *   --refetch は実質必須。付けない場合、削除された作家は author.php の
 *   アクセス時にWikipedia APIを同期で叩く。本番実測でこの取得だけで
 *   1.5〜2.3秒かかり、サイトマップ送信済みでクローラーが22,000件超の
 *   作家ページを巡回している状況では全件がその遅さになる。
 *
 *   このループは「1名削除 → その場で取り直す」を順に回すため、まだ処理して
 *   いない作家は元のデータのまま残る。途中で止めても中途半端な状態にならない。
 */

declare(strict_types=1);

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit("CLI専用です\n");
}

chdir(dirname(__DIR__));
require_once('config.php');
require_once('library/database.php');
require_once('library/cache.php');
require_once('library/author_info_fetcher.php');

$g_db = DB_Connect();
if (!$g_db || DB::isError($g_db)) {
    exit("DB接続に失敗しました\n");
}

$opts    = getopt('', ['apply', 'refetch', 'stats', 'limit::', 'author::']);
$apply   = isset($opts['apply']);
$refetch = isset($opts['refetch']);
$limit   = isset($opts['limit']) ? max(1, (int)$opts['limit']) : 0;
$only    = isset($opts['author']) ? (string)$opts['author'] : '';

// --author=作家名 を付けると1名だけを対象にする。
// 大量パージの前に、取り直しが正しく動くかを1件で確かめるために使う。
// Wikipedia で一致する記事が無い作家は説明文が空になるのが正しい挙動。
// その場合 author.php は「ReadNestでの読まれ方」（自前の集計）を表示する。
if ($only !== '') {
    $fetcher = new AuthorInfoFetcher();
    $g_db->query("DELETE FROM b_author_info WHERE author_name = ?", [$only]);
    getCache()->delete('author_info_' . md5($only));
    echo "「{$only}」を削除して取り直します...\n\n";
    $info = $fetcher->getAuthorInfo($only);
    printf("source      : %s\n", $info['source'] ?? '(なし)');
    printf("wikipedia   : %s\n", $info['wikipedia_url'] ?: '(なし)');
    printf("説明文長    : %d 文字\n", mb_strlen((string)($info['description'] ?? '')));
    printf("説明文      : %s\n", mb_substr((string)($info['description'] ?? '(空)'), 0, 200));
    if (empty($info['description'])) {
        echo "\n説明文は空です。Wikipediaに一致する記事が無い作家では正常な結果で、\n";
        echo "author.php 側が自前の集計（ReadNestでの読まれ方）を表示します。\n";
    }
    exit(0);
}

/** 作家名・記事タイトルの比較用正規化（AuthorInfoFetcher と同じ規則） */
function purgeNormalize(string $name): string {
    if (function_exists('mb_convert_kana')) {
        $name = mb_convert_kana($name, 'as', 'UTF-8');
    }
    $name = preg_replace('/[（(][^）)]*[）)]\s*$/u', '', $name);
    $name = preg_replace('/[\s\x{3000}・･、,，\.．。\'’"＂‐‑‒–—―\-]/u', '', $name);
    return mb_strtolower(trim($name), 'UTF-8');
}

/** wikipedia_url から記事タイトルを取り出す */
function purgeTitleFromUrl(string $url): string {
    $path = parse_url($url, PHP_URL_PATH);
    if (!$path) {
        return '';
    }
    $slug = substr($path, (int)strrpos($path, '/') + 1);
    return str_replace('_', ' ', rawurldecode($slug));
}

// --stats: source別の件数とサンプルだけを出す
if (isset($opts['stats'])) {
    $counts = $g_db->getAll(
        "SELECT source, COUNT(*) AS n FROM b_author_info GROUP BY source ORDER BY n DESC",
        null,
        DB_FETCHMODE_ASSOC
    );
    echo "--- source別の件数 ---\n";
    foreach ($counts ?: [] as $c) {
        printf("  %-12s %d 件\n", $c['source'] ?: '(空)', (int)$c['n']);
    }
    $samples = $g_db->getAll(
        "SELECT author_name, description FROM b_author_info WHERE source = 'openai' LIMIT 5",
        null,
        DB_FETCHMODE_ASSOC
    );
    echo "\n--- openai由来のサンプル ---\n";
    foreach ($samples ?: [] as $s) {
        printf("  %s\n    %s\n", $s['author_name'], mb_substr((string)$s['description'], 0, 120));
    }
    exit(0);
}

$rows = $g_db->getAll(
    "SELECT author_name, wikipedia_url, source FROM b_author_info WHERE source IN ('wikipedia', 'openai')",
    null,
    DB_FETCHMODE_ASSOC
);

if (DB::isError($rows)) {
    exit('クエリ失敗: ' . $rows->getMessage() . "\n");
}

$rows = $rows ?: [];
$cache = getCache();

$mismatched = [];
$openai_count = 0;
foreach ($rows as $row) {
    // openai由来は全件対象。根拠を与えずに生成した経歴で、事実確認ができない。
    // 例) 有川真由美に『君の膵臓をたべたい』（実際は住野よるの作品）を代表作として記載
    if (($row['source'] ?? '') === 'openai') {
        $mismatched[] = ['author' => (string)$row['author_name'], 'title' => '(AI生成)'];
        $openai_count++;
        continue;
    }
    $title = purgeTitleFromUrl((string)($row['wikipedia_url'] ?? ''));
    // URLが無いものも、どの記事を根拠にしたか追えないので取り直す
    if ($title === '' || purgeNormalize($title) !== purgeNormalize((string)$row['author_name'])) {
        $mismatched[] = ['author' => (string)$row['author_name'], 'title' => $title];
    }
}

$total = count($rows);
$bad   = count($mismatched);
printf("wikipedia / openai 由来のレコード: %d 件\n", $total);
printf("パージ対象: %d 件 (%.1f%%)  うち AI生成 %d 件\n",
    $bad, $total ? $bad * 100 / $total : 0.0, $openai_count);

if ($bad === 0) {
    exit("パージ対象はありません\n");
}

echo "\n--- 対象の例（先頭20件） ---\n";
foreach (array_slice($mismatched, 0, 20) as $m) {
    printf("  %-30s -> %s\n", $m['author'], $m['title'] !== '' ? $m['title'] : '(URLなし)');
}

if (!$apply) {
    echo "\n確認のみで終了しました。実際に削除するには --apply を付けてください。\n";
    exit(0);
}

$targets = $limit > 0 ? array_slice($mismatched, 0, $limit) : $mismatched;
printf("\n%d 件を削除します...\n", count($targets));

$deleted = 0;
$refetched = 0;
$fetcher = $refetch ? new AuthorInfoFetcher() : null;

foreach ($targets as $m) {
    $author = $m['author'];

    $res = $g_db->query("DELETE FROM b_author_info WHERE author_name = ?", [$author]);
    if (DB::isError($res)) {
        fwrite(STDERR, "削除失敗: {$author}: " . $res->getMessage() . "\n");
        continue;
    }
    // AuthorInfoFetcher::getAuthorInfo と同じキー
    $cache->delete('author_info_' . md5($author));
    $deleted++;

    if ($fetcher) {
        $info = $fetcher->getAuthorInfo($author);
        if (!empty($info['description'])) {
            $refetched++;
        }
        // Wikipedia への連投を避ける。1作家あたり2リクエストを約1.5秒かけて
        // 出しているので、これ以上の間隔は不要（呼び出しを2回にまとめる前は
        // 最大8リクエストだった）。
        usleep(100000);
    }

    if ($deleted % 100 === 0) {
        printf("  %d 件処理\n", $deleted);
    }
}

printf("\n削除: %d 件\n", $deleted);
if ($fetcher) {
    printf("取り直して説明文が入った: %d 件\n", $refetched);
} else {
    echo "取り直しは author.php のアクセス時に遅延実行されます\n";
}
