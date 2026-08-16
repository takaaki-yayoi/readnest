<?php
/**
 * AI推薦の候補プール
 *
 * b_book_repository には24万件近い本があり、うち約17万件がembeddingを持つ。
 * 全件とコサイン類似度を取るのは現実的でないため、
 * 「ReadNestで実際に読まれている本」を上位N件に絞って候補プールとする。
 *
 * 取得元の優先順:
 *   1. b_popular_books_cache （cron/update_popular_books.php が毎時更新）
 *   2. b_book_list の集計（重いのでファイルキャッシュに6時間保持）
 *
 * 1が空＝cronが動いていない場合でも2で動作するが、
 * 2はユーザーのリクエスト中に走るため、cronの復旧が本来の対処。
 *
 * 返すのは「読者数の多い順のASIN」だけで、embeddingの有無は絞らない。
 * 絞るには b_book_repository とのJOINが必要になるが、索引が無く実用にならない。
 * 呼び出し側が asin IN (...) で候補を引くときに主キー引きで絞ること。
 * embedding保有率は約71%なので、必要数より多めに要求しておく。
 */

require_once(__DIR__ . '/cache.php');

if (!defined('RECOMMENDATION_POOL_TTL')) {
    define('RECOMMENDATION_POOL_TTL', 21600); // 6時間
}

/**
 * 候補プールのASINリストを取得する
 *
 * @param int $limit 取得件数
 * @return array ASINの配列（読者数の多い順）
 */
function getRecommendationPoolAsins($limit = 500) {
    global $g_db;

    $limit = max(1, (int)$limit);
    $cache = getCache();
    $cache_key = 'recommendation_pool_asins_' . $limit;

    // 空配列はキャッシュしない（集計元が復旧したら次のリクエストで拾い直す）
    $cached = $cache->get($cache_key);
    if (is_array($cached) && !empty($cached)) {
        return $cached;
    }

    // 1) 集計済みテーブルから取得（cronが動いていれば最速）
    //
    // ここで b_book_repository と JOIN して「embeddingを持つ本だけ」に
    // 絞りたくなるが、それをやってはいけない。
    // b_popular_books_cache.amazon_id には索引が無く、23.5万行の
    // b_book_repository とのネストループになって実質終わらない。
    // embeddingの有無は、呼び出し側が asin IN (...) で候補を引くときに
    // 主キー引きのついでに絞れば済む。
    $popular_sql = "
        SELECT pc.amazon_id
        FROM b_popular_books_cache pc
        WHERE pc.amazon_id IS NOT NULL
          AND pc.amazon_id != ''
        ORDER BY pc.user_count DESC
        LIMIT " . $limit;

    $rows = $g_db->getAll($popular_sql, [], DB_FETCHMODE_ASSOC);

    if (!DB::isError($rows) && !empty($rows)) {
        $asins = array_values(array_unique(array_column($rows, 'amazon_id')));
        $cache->set($cache_key, $asins, RECOMMENDATION_POOL_TTL);
        return $asins;
    }

    // 2) フォールバック：b_book_list を直接集計する
    //    b_popular_books_cache が空（cron未実行 or 失敗）のときだけ通る経路。
    error_log('[recommendation_pool] b_popular_books_cache が空のため b_book_list を直接集計します。'
              . ' cron/update_popular_books.php の実行状況を確認してください。');

    // 同時に複数リクエストが重い集計に殺到しないようロックを取る。
    // 取れなかったリクエストは空プールで諦める（同著者候補などは別途効く）。
    $lock_path = sys_get_temp_dir() . '/readnest_recommendation_pool.lock';
    $lock = @fopen($lock_path, 'c');
    if ($lock === false) {
        return [];
    }
    if (!flock($lock, LOCK_EX | LOCK_NB)) {
        fclose($lock);
        return [];
    }

    try {
        // ロック待ちの間に別プロセスが埋めている可能性がある
        $cached = $cache->get($cache_key);
        if (is_array($cached) && !empty($cached)) {
            return $cached;
        }

        // こちらも b_book_repository とはJOINしない（上と同じ理由）
        $fallback_sql = "
            SELECT bl.amazon_id
            FROM b_book_list bl
            INNER JOIN b_user u ON bl.user_id = u.user_id
            WHERE bl.amazon_id IS NOT NULL
              AND bl.amazon_id != ''
              AND u.diary_policy = 1
              AND u.status = 1
            GROUP BY bl.amazon_id
            ORDER BY COUNT(DISTINCT bl.user_id) DESC
            LIMIT " . $limit;

        $rows = $g_db->getAll($fallback_sql, [], DB_FETCHMODE_ASSOC);

        if (DB::isError($rows)) {
            error_log('[recommendation_pool] フォールバック集計に失敗: ' . $rows->getMessage());
            return [];
        }

        $asins = array_values(array_unique(array_column($rows, 'amazon_id')));
        if (!empty($asins)) {
            $cache->set($cache_key, $asins, RECOMMENDATION_POOL_TTL);
        }
        return $asins;
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}

/**
 * 候補プールのキャッシュを破棄する（cron実行後などに使う）
 *
 * @param int $limit getRecommendationPoolAsins() に渡したのと同じ件数
 * @return bool
 */
function clearRecommendationPoolCache($limit = 500) {
    return getCache()->delete('recommendation_pool_asins_' . max(1, (int)$limit));
}
