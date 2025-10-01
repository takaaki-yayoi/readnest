<?php
/**
 * いいね機能API
 * 読書活動とレビューへのいいね/取り消しを処理
 */

declare(strict_types=1);

// エラー表示を抑制（JSON出力のため）
error_reporting(0);
ini_set('display_errors', '0');

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/library/database.php';
require_once dirname(__DIR__) . '/library/csrf.php';

header('Content-Type: application/json; charset=UTF-8');

// レスポンスを返す関数
function sendResponse($success, $message = '', $data = null) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ログインチェック
if (!checkLogin()) {
    sendResponse(false, 'ログインが必要です');
}

$user_id = (int)$_SESSION['AUTH_USER'];

// リクエストメソッドの確認
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, 'POSTリクエストのみ対応しています');
}

// POSTデータの取得
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// デバッグログ
error_log('Like API: Received data: ' . print_r($data, true));
error_log('Like API: Session ID: ' . session_id());
error_log('Like API: Session csrf_token: ' . ($_SESSION['csrf_token'] ?? 'not set'));

if (!$data) {
    error_log('Like API: Failed to decode JSON');
    sendResponse(false, 'リクエストデータが不正です');
}

// CSRFトークンの検証
if (!isset($data['csrf_token'])) {
    error_log('Like API: CSRF token not provided in request');
    sendResponse(false, 'CSRFトークンが送信されていません');
}

if (!verifyCSRFToken($data['csrf_token'])) {
    error_log('Like API: CSRF token verification failed');
    error_log('Like API: Provided token: ' . $data['csrf_token']);
    sendResponse(false, '不正なリクエストです（CSRFトークンが無効です）');
}

// 必須パラメータのチェック
if (!isset($data['target_type']) || !isset($data['target_id'])) {
    sendResponse(false, '必須パラメータが不足しています');
}

$target_type = $data['target_type'];
$target_id = (int)$data['target_id'];
$action = $data['action'] ?? 'toggle'; // toggle, add, remove

// target_typeのバリデーション
if (!in_array($target_type, ['activity', 'review'])) {
    sendResponse(false, '不正な対象タイプです');
}

try {
    global $g_db;

    // トランザクション開始
    $g_db->query('START TRANSACTION');

    // 対象の存在確認と投稿者ユーザーIDの取得
    $target_user_id = null;

    if ($target_type === 'activity') {
        // 読書活動の場合: b_book_eventから取得
        $sql = "SELECT user_id FROM b_book_event WHERE event_id = ?";
        $result = $g_db->getRow($sql, [$target_id]);

        if (!$result) {
            $g_db->query('ROLLBACK');
            sendResponse(false, '対象の活動が見つかりません');
        }

        $target_user_id = (int)$result['user_id'];

    } else if ($target_type === 'review') {
        // レビューの場合: b_book_listから取得
        // target_idはbook_idとして扱い、user_idはリクエストから取得
        if (!isset($data['review_user_id'])) {
            $g_db->query('ROLLBACK');
            sendResponse(false, 'レビューのユーザーIDが必要です');
        }

        $review_user_id = (int)$data['review_user_id'];

        $sql = "SELECT user_id FROM b_book_list
                WHERE book_id = ? AND user_id = ?
                AND (rating > 0 OR (memo IS NOT NULL AND memo != ''))";
        $result = $g_db->getRow($sql, [$target_id, $review_user_id]);

        if (!$result) {
            $g_db->query('ROLLBACK');
            sendResponse(false, '対象のレビューが見つかりません');
        }

        $target_user_id = (int)$result['user_id'];

        // レビューの場合、target_idを「book_id * 1000000 + user_id」の形式で保存
        // これにより、同じ本の異なるユーザーのレビューを区別できる
        $target_id = $target_id * 1000000 + $review_user_id;
    }

    // 自分の投稿にはいいねできない
    if ($target_user_id === $user_id) {
        $g_db->query('ROLLBACK');
        sendResponse(false, '自分の投稿にはいいねできません');
    }

    // 既存のいいね状態を確認
    $sql = "SELECT like_id FROM b_like
            WHERE user_id = ? AND target_type = ? AND target_id = ?";
    $existing_like = $g_db->getRow($sql, [$user_id, $target_type, $target_id]);

    $is_liked = !empty($existing_like);
    $new_state = null;

    // アクションに応じて処理
    if ($action === 'toggle') {
        if ($is_liked) {
            // いいねを取り消し
            $sql = "DELETE FROM b_like WHERE like_id = ?";
            $result = $g_db->query($sql, [$existing_like['like_id']]);

            if (DB::isError($result)) {
                $g_db->query('ROLLBACK');
                error_log('Failed to remove like: ' . $result->getMessage());
                sendResponse(false, 'いいねの取り消しに失敗しました');
            }

            // カウントを減らす
            $sql = "INSERT INTO b_like_count (target_type, target_id, like_count, updated_at)
                    VALUES (?, ?, 0, NOW())
                    ON DUPLICATE KEY UPDATE
                        like_count = GREATEST(0, like_count - 1),
                        updated_at = NOW()";
            $g_db->query($sql, [$target_type, $target_id]);

            $new_state = false;

        } else {
            // いいねを追加
            $sql = "INSERT INTO b_like (user_id, target_type, target_id, target_user_id, created_at)
                    VALUES (?, ?, ?, ?, NOW())";
            $result = $g_db->query($sql, [$user_id, $target_type, $target_id, $target_user_id]);

            if (DB::isError($result)) {
                $g_db->query('ROLLBACK');
                error_log('Failed to add like: ' . $result->getMessage());
                sendResponse(false, 'いいねの追加に失敗しました');
            }

            // カウントを増やす
            $sql = "INSERT INTO b_like_count (target_type, target_id, like_count, updated_at)
                    VALUES (?, ?, 1, NOW())
                    ON DUPLICATE KEY UPDATE
                        like_count = like_count + 1,
                        updated_at = NOW()";
            $g_db->query($sql, [$target_type, $target_id]);

            $new_state = true;
        }

    } else if ($action === 'add') {
        if (!$is_liked) {
            $sql = "INSERT INTO b_like (user_id, target_type, target_id, target_user_id, created_at)
                    VALUES (?, ?, ?, ?, NOW())";
            $result = $g_db->query($sql, [$user_id, $target_type, $target_id, $target_user_id]);

            if (DB::isError($result)) {
                $g_db->query('ROLLBACK');
                error_log('Failed to add like: ' . $result->getMessage());
                sendResponse(false, 'いいねの追加に失敗しました');
            }

            // カウントを増やす
            $sql = "INSERT INTO b_like_count (target_type, target_id, like_count, updated_at)
                    VALUES (?, ?, 1, NOW())
                    ON DUPLICATE KEY UPDATE
                        like_count = like_count + 1,
                        updated_at = NOW()";
            $g_db->query($sql, [$target_type, $target_id]);
        }
        $new_state = true;

    } else if ($action === 'remove') {
        if ($is_liked) {
            $sql = "DELETE FROM b_like WHERE like_id = ?";
            $result = $g_db->query($sql, [$existing_like['like_id']]);

            if (DB::isError($result)) {
                $g_db->query('ROLLBACK');
                error_log('Failed to remove like: ' . $result->getMessage());
                sendResponse(false, 'いいねの取り消しに失敗しました');
            }

            // カウントを減らす
            $sql = "INSERT INTO b_like_count (target_type, target_id, like_count, updated_at)
                    VALUES (?, ?, 0, NOW())
                    ON DUPLICATE KEY UPDATE
                        like_count = GREATEST(0, like_count - 1),
                        updated_at = NOW()";
            $g_db->query($sql, [$target_type, $target_id]);
        }
        $new_state = false;
    }

    // 最新のいいね数を取得
    $sql = "SELECT like_count FROM b_like_count
            WHERE target_type = ? AND target_id = ?";
    $count_result = $g_db->getRow($sql, [$target_type, $target_id]);
    $like_count = $count_result ? (int)$count_result['like_count'] : 0;

    // コミット
    $g_db->query('COMMIT');

    sendResponse(true, $new_state ? 'いいねしました' : 'いいねを取り消しました', [
        'is_liked' => $new_state,
        'like_count' => $like_count
    ]);

} catch (Exception $e) {
    if (isset($g_db)) {
        $g_db->query('ROLLBACK');
    }
    error_log('Like API error: ' . $e->getMessage());
    sendResponse(false, 'エラーが発生しました');
}