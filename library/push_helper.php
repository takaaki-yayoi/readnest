<?php
if (!defined('CONFIG')) {
    error_log('direct access detected...');
    die('reference for this file is not allowed.');
}

/**
 * Web Push 送信ヘルパー
 *
 * minishlink/web-push を使ったラッパー。VAPID鍵が未設定なら no-op。
 * 410 Gone / 404 を返した購読は自動的に削除する（再購読されるまで送らない）。
 */

/**
 * 単一ユーザーに通知送信
 *
 * @param int   $user_id
 * @param array $payload  ['title' => string, 'body' => string, 'url' => string]
 * @return int  実際に送信成功した端末数
 */
function sendPushToUser(int $user_id, array $payload): int
{
    global $g_db;

    if (!defined('VAPID_PUBLIC_KEY') || VAPID_PUBLIC_KEY === '' || VAPID_PRIVATE_KEY === '') {
        error_log('[Push] VAPID keys not configured, skip send');
        return 0;
    }

    $autoload = dirname(__DIR__) . '/vendor/autoload.php';
    if (!file_exists($autoload)) {
        error_log('[Push] vendor/autoload.php not found, run composer install');
        return 0;
    }
    require_once $autoload;

    $sql = "SELECT id, endpoint, p256dh, auth FROM b_push_subscriptions WHERE user_id = ?";
    $rows = $g_db->getAll($sql, [$user_id], DB_FETCHMODE_ASSOC);
    if (DB::isError($rows) || empty($rows)) {
        return 0;
    }

    $auth = [
        'VAPID' => [
            'subject' => VAPID_SUBJECT,
            'publicKey' => VAPID_PUBLIC_KEY,
            'privateKey' => VAPID_PRIVATE_KEY,
        ],
    ];

    try {
        $webPush = new \Minishlink\WebPush\WebPush($auth);
    } catch (\Throwable $e) {
        error_log('[Push] WebPush init failed: ' . $e->getMessage());
        return 0;
    }

    $body = json_encode($payload, JSON_UNESCAPED_UNICODE);
    $sub_index = [];
    foreach ($rows as $row) {
        try {
            $subscription = \Minishlink\WebPush\Subscription::create([
                'endpoint' => $row['endpoint'],
                'publicKey' => $row['p256dh'],
                'authToken' => $row['auth'],
            ]);
            $webPush->queueNotification($subscription, $body);
            $sub_index[$row['endpoint']] = (int)$row['id'];
        } catch (\Throwable $e) {
            error_log('[Push] queue failed for sub#' . $row['id'] . ': ' . $e->getMessage());
        }
    }

    $success = 0;
    foreach ($webPush->flush() as $report) {
        $endpoint = $report->getRequest()->getUri()->__toString();
        if ($report->isSuccess()) {
            $success++;
            if (isset($sub_index[$endpoint])) {
                $g_db->query(
                    "UPDATE b_push_subscriptions SET last_used_at = NOW() WHERE id = ?",
                    [$sub_index[$endpoint]]
                );
            }
        } else {
            $code = $report->getResponse() ? $report->getResponse()->getStatusCode() : 0;
            if ($code === 404 || $code === 410) {
                if (isset($sub_index[$endpoint])) {
                    $g_db->query(
                        "DELETE FROM b_push_subscriptions WHERE id = ?",
                        [$sub_index[$endpoint]]
                    );
                    error_log('[Push] removed expired sub#' . $sub_index[$endpoint] . ' (HTTP ' . $code . ')');
                }
            } else {
                error_log('[Push] send failed for ' . substr($endpoint, 0, 80) . ' HTTP ' . $code);
            }
        }
    }

    return $success;
}

/**
 * ユーザーが少なくとも1つの購読を持っているか
 */
function userHasPushSubscription(int $user_id): bool
{
    global $g_db;
    $count = $g_db->getOne(
        "SELECT COUNT(*) FROM b_push_subscriptions WHERE user_id = ?",
        [$user_id]
    );
    return !DB::isError($count) && (int)$count > 0;
}

/**
 * オプトイン済みユーザーへのみ通知送信
 *
 * b_user.streak_reminder_enabled = 1 のユーザーにのみ送る。
 * このフラグはストリークリマインダーだけでなく、push通知全般のON/OFFスイッチとして使う。
 *
 * @return int 送信成功端末数
 */
function sendPushIfOptedIn(int $user_id, array $payload): int
{
    global $g_db;

    $enabled = $g_db->getOne(
        "SELECT streak_reminder_enabled FROM b_user WHERE user_id = ?",
        [$user_id]
    );
    if (DB::isError($enabled) || empty($enabled)) {
        return 0;
    }

    return sendPushToUser($user_id, $payload);
}
