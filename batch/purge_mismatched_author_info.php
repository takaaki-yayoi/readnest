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
 *   php batch/purge_mismatched_author_info.php --apply      # 実際に削除
 *   php batch/purge_mismatched_author_info.php --apply --refetch --limit=200
 *
 *   --refetch を付けると削除後にその場で取り直す。付けない場合は author.php の
 *   アクセス時に遅延取得される。クローラーが大量に来ている最中は、負荷が
 *   一点に集中しないよう --refetch を少しずつ回す方が安全。
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

$opts    = getopt('', ['apply', 'refetch', 'limit::']);
$apply   = isset($opts['apply']);
$refetch = isset($opts['refetch']);
$limit   = isset($opts['limit']) ? max(1, (int)$opts['limit']) : 0;

/** 作家名・記事タイトルの比較用正規化（AuthorInfoFetcher と同じ規則） */
function purgeNormalize(string $name): string {
    $name = preg_replace('/[（(][^）)]*[）)]\s*$/u', '', $name);
    $name = preg_replace('/[\s\x{3000}・･‐‑‒–—―\-]/u', '', $name);
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

$rows = $g_db->getAll(
    "SELECT author_name, wikipedia_url, source FROM b_author_info WHERE source = 'wikipedia'",
    null,
    DB_FETCHMODE_ASSOC
);

if (DB::isError($rows)) {
    exit('クエリ失敗: ' . $rows->getMessage() . "\n");
}

$rows = $rows ?: [];
$cache = getCache();

$mismatched = [];
foreach ($rows as $row) {
    $title = purgeTitleFromUrl((string)($row['wikipedia_url'] ?? ''));
    // URLが無いものも、どの記事を根拠にしたか追えないので取り直す
    if ($title === '' || purgeNormalize($title) !== purgeNormalize((string)$row['author_name'])) {
        $mismatched[] = ['author' => (string)$row['author_name'], 'title' => $title];
    }
}

$total = count($rows);
$bad   = count($mismatched);
printf("source=wikipedia のレコード: %d 件\n", $total);
printf("記事タイトルが作家名と一致しない: %d 件 (%.1f%%)\n", $bad, $total ? $bad * 100 / $total : 0.0);

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
        // Wikipedia / OpenAI への連投を避ける
        usleep(300000);
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
