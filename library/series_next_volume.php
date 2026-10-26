<?php
/**
 * 続巻の囁き（ホームの囁きレコメンド用）
 *
 * 最近読了した本のタイトルから巻数を読み取り、同じ著者の本（b_book_repository）から
 * 次の巻を探す。次の巻が本棚に無ければ「続き、まだ本棚にありません」と囁く。
 * 外部APIは使わない（ホーム表示のたびに呼ぶため）。
 */

if (!defined('CONFIG')) {
    die('reference for this file is not allowed.');
}

require_once(__DIR__ . '/cache.php');

/**
 * 全角英数・記号を半角にそろえる
 */
function seriesNormalize(string $text): string {
    if (class_exists('Normalizer')) {
        $normalized = Normalizer::normalize($text, Normalizer::FORM_KC);
        if ($normalized !== false) {
            return $normalized;
        }
    }
    return mb_convert_kana($text, 'as', 'UTF-8');
}

/**
 * タイトルからシリーズ名と巻数を読み取る
 *
 * 誤判定を避けるため、巻数は区切り（空白・括弧・「第」「巻」等）があるものだけを対象にする。
 * 「海の上」「1984」のような区切りの無いものは巻数として扱わない。
 *
 * @return array|null ['base' => 比較用のシリーズ名, 'order' => 巻数, 'kind' => 'number'|'jouge'|'zengo']
 */
function parseSeriesVolume(string $title): ?array {
    $t = trim(seriesNormalize($title));

    // 末尾のレーベル表記を外す: （新潮文庫）【毎日文庫】(講談社コミックス) 等
    // 括弧で囲まれたものだけを外す（「ちくま文庫の歴史」のような書名は残す）
    $label = '/\s*[\(（【\[〈][^\(（【\[〈\)）】\]〉]*(?:文庫|新書|選書|叢書|コミックス|ブックス|ノベルス|BOOKS|Books)[^\(（【\[〈\)）】\]〉]*[\)）】\]〉]\s*$/u';
    for ($i = 0; $i < 3; $i++) {
        $stripped = preg_replace($label, '', $t);
        if ($stripped === null || $stripped === $t || mb_strlen($stripped) < 2) {
            break;
        }
        $t = trim($stripped);
    }
    // 「―」で囲まれた副題区切りの末尾（「騎士団長殺し―第1部 顕れるイデア編(上)―」の最後の「―」）
    $t = trim(preg_replace('/[―—\-]+$/u', '', $t));

    $kanji = ['一' => 1, '二' => 2, '三' => 3, '四' => 4, '五' => 5, '六' => 6, '七' => 7, '八' => 8, '九' => 9, '十' => 10];
    $sep = '(?:\s|[\.・:：、,―—\-])';

    $patterns = [
        // 上・中・下（括弧付き / 上巻 / 区切り付き）
        ['/[\(（〈]\s*(上|中|下)\s*[\)）〉]$/u', 'jouge'],
        ['/(上|中|下)巻$/u', 'jouge'], // 「巻」が付けば区切りが無くても誤判定しにくい
        ['/' . $sep . '+(上|中|下)$/u', 'jouge'],
        // 前編・後編
        ['/[\(（〈]\s*(前編|後編)\s*[\)）〉]$/u', 'zengo'],
        ['/' . $sep . '*(前編|後編)$/u', 'zengo'],
        // 第N巻・第N部・N巻（漢数字も）
        ['/第\s*([0-9]{1,3}|[一二三四五六七八九十])\s*[巻部]$/u', 'number'],
        ['/' . $sep . '+([0-9]{1,3})\s*巻$/u', 'number'],
        // (7)・Vol.2・BOOK1・#3
        ['/[\(（]\s*([0-9]{1,3})\s*[\)）]$/u', 'number'],
        ['/(?:vol|VOL|Vol|book|BOOK|Book|#)\.?\s*([0-9]{1,3})$/u', 'number'],
        // 区切りの後の数字（「テンカイチ 7」「海辺のカフカ. 2」）
        ['/' . $sep . '+([0-9]{1,3})$/u', 'number'],
    ];

    foreach ($patterns as [$regex, $kind]) {
        if (!preg_match($regex, $t, $m, PREG_OFFSET_CAPTURE)) {
            continue;
        }
        $token = $m[1][0];
        if ($kind === 'jouge') {
            $order = ['上' => 1, '中' => 2, '下' => 3][$token];
        } elseif ($kind === 'zengo') {
            $order = ($token === '前編') ? 1 : 2;
        } else {
            $order = isset($kanji[$token]) ? $kanji[$token] : (int)$token;
        }

        $base = substr($t, 0, $m[0][1]);
        $base = preg_replace('/[\s\.・:：、,―—\-\(（〈]+$/u', '', $base);
        // 副題の有無で別シリーズにならないよう、コロンより前をシリーズ名にする
        // （「テンカイチ : 日本最強武芸者決定戦. 7」と「テンカイチ 8」を同じシリーズにする）
        if (preg_match('/^(.+?)\s*[:：]/u', $base, $bm) && mb_strlen(trim($bm[1])) >= 2) {
            $base = $bm[1];
        }
        $key = mb_strtolower(preg_replace('/\s+/u', '', $base));
        if (mb_strlen($key) < 2 || $order < 1) {
            return null;
        }
        return ['base' => $key, 'order' => $order, 'kind' => ($kind === 'number') ? 'number' : 'sequence'];
    }
    return null;
}

/**
 * 候補の中から次の巻を選ぶ
 * 数字の巻は「ちょうど次」だけ（途中が抜けていると飛ばしてしまうため）。
 * 上中下・前後編は次に大きいもの（上の次が中か下かは作品による）。
 *
 * @param array $current parseSeriesVolume の結果
 * @param array $books   [['title' => ..., ...], ...]
 */
function findNextVolume(array $current, array $books): ?array {
    $best = null;
    $best_order = PHP_INT_MAX;
    foreach ($books as $book) {
        $parsed = parseSeriesVolume((string)$book['title']);
        if ($parsed === null || $parsed['base'] !== $current['base'] || $parsed['kind'] !== $current['kind']) {
            continue;
        }
        $ok = ($current['kind'] === 'number')
            ? $parsed['order'] === $current['order'] + 1
            : $parsed['order'] > $current['order'];
        if ($ok && $parsed['order'] < $best_order) {
            $best = $book;
            $best_order = $parsed['order'];
        }
    }
    return $best;
}

/**
 * 続巻の囁きを1件返す（無ければnull）
 *
 * @return array|null ['amazon_id', 'title', 'author', 'image_url', 'prev_title']
 */
function getSeriesNextWhisper(int $user_id): ?array {
    global $g_db;

    $cache = getCache();
    $cache_key = 'series_next_' . $user_id;
    $candidates = $cache->get($cache_key);

    if ($candidates === false || !is_array($candidates)) {
        $candidates = [];

        // 本棚にある本（巻数の比較用）
        $owned_rows = $g_db->getAll('SELECT name, amazon_id FROM b_book_list WHERE user_id = ?', [$user_id], DB_FETCHMODE_ASSOC);
        if (DB::isError($owned_rows)) {
            return null;
        }
        $owned_asins = [];
        $owned_volumes = [];
        foreach ($owned_rows as $row) {
            if (!empty($row['amazon_id'])) {
                $owned_asins[$row['amazon_id']] = true;
            }
            $parsed = parseSeriesVolume((string)$row['name']);
            if ($parsed) {
                $owned_volumes[$parsed['base'] . '#' . $parsed['kind'] . '#' . $parsed['order']] = true;
            }
        }

        // 最近読了した本
        $finished = $g_db->getAll(
            "SELECT bl.name, COALESCE(NULLIF(br.author, ''), bl.author) AS author
             FROM b_book_list bl
             LEFT JOIN b_book_repository br ON br.asin = bl.amazon_id
             WHERE bl.user_id = ? AND bl.status IN (" . READING_FINISH . ", " . READ_BEFORE . ")
             ORDER BY bl.finished_date IS NULL, bl.finished_date DESC, bl.update_date DESC
             LIMIT 30",
            [$user_id],
            DB_FETCHMODE_ASSOC
        );
        if (DB::isError($finished)) {
            return null;
        }

        $books_by_author = [];
        foreach ($finished as $row) {
            if (count($candidates) >= 5 || count($books_by_author) >= 10) {
                break;
            }
            $current = parseSeriesVolume((string)$row['name']);
            $author = trim((string)$row['author']);
            if ($current === null || $author === '') {
                continue;
            }

            if (!isset($books_by_author[$author])) {
                $rows = $g_db->getAll(
                    'SELECT asin AS amazon_id, title, author, image_url FROM b_book_repository WHERE author = ? LIMIT 300',
                    [$author],
                    DB_FETCHMODE_ASSOC
                );
                $books_by_author[$author] = DB::isError($rows) ? [] : $rows;
            }

            $next = findNextVolume($current, $books_by_author[$author]);
            if ($next === null || isset($owned_asins[$next['amazon_id']])) {
                continue;
            }
            $next_parsed = parseSeriesVolume((string)$next['title']);
            if (isset($owned_volumes[$next_parsed['base'] . '#' . $next_parsed['kind'] . '#' . $next_parsed['order']])) {
                continue; // 別の版（別ASIN）で既に持っている
            }
            $next['prev_title'] = $row['name'];
            $candidates[$next['amazon_id']] = $next;
        }

        $candidates = array_values($candidates);
        $cache->set($cache_key, $candidates, 6 * 3600);
    }

    if (empty($candidates)) {
        return null;
    }

    // キャッシュ後に本棚へ追加した本は出さない
    $pick = $candidates[array_rand($candidates)];
    $added = $g_db->getOne('SELECT 1 FROM b_book_list WHERE user_id = ? AND amazon_id = ? LIMIT 1', [$user_id, $pick['amazon_id']]);
    if (!DB::isError($added) && $added) {
        return null;
    }
    return $pick;
}
