<?php
/**
 * API Key管理ページ
 */

declare(strict_types=1);

require_once('modern_config.php');

// ログインチェック
if (!checkLogin()) {
    header('Location: https://readnest.jp/');
    exit;
}

$mine_user_id = $_SESSION['AUTH_USER'];
$d_nickname = getNickname($mine_user_id);

// POSTリクエスト処理
$message = '';
$error = '';
$new_api_key = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'generate') {
        // API Key生成
        $name = trim($_POST['name'] ?? '');

        if (empty($name)) {
            $error = 'API Keyの名前を入力してください';
        } else {
            // API Keyを生成（64文字のランダムな文字列）
            $api_key = bin2hex(random_bytes(32));

            $sql = "INSERT INTO b_api_keys (user_id, api_key, name, is_active)
                    VALUES (?, ?, ?, 1)";

            $result = $g_db->query($sql, [$mine_user_id, $api_key, $name]);

            if (DB::isError($result)) {
                error_log("API Key generation error: " . $result->getMessage());
                $error = 'API Keyの生成に失敗しました: ' . $result->getMessage();
            } else {
                $message = 'API Keyを生成しました';
                $new_api_key = $api_key;
            }
        }
    } elseif ($action === 'delete') {
        // API Key削除
        $api_key_id = (int)($_POST['api_key_id'] ?? 0);

        $sql = "DELETE FROM b_api_keys
                WHERE api_key_id = ? AND user_id = ?";

        $result = $g_db->query($sql, [$api_key_id, $mine_user_id]);

        if (DB::isError($result)) {
            error_log("API Key deletion error: " . $result->getMessage());
            $error = 'API Keyの削除に失敗しました';
        } else {
            $message = 'API Keyを削除しました';
        }
    } elseif ($action === 'toggle') {
        // API Key有効/無効切り替え
        $api_key_id = (int)($_POST['api_key_id'] ?? 0);

        $sql = "UPDATE b_api_keys
                SET is_active = 1 - is_active
                WHERE api_key_id = ? AND user_id = ?";

        $result = $g_db->query($sql, [$api_key_id, $mine_user_id]);

        if (DB::isError($result)) {
            error_log("API Key toggle error: " . $result->getMessage());
            $error = 'API Keyの状態変更に失敗しました';
        } else {
            $message = 'API Keyの状態を変更しました';
        }
    }
}

// API Key一覧を取得
$sql = "SELECT api_key_id, api_key, name, is_active, expires_at, created_at, last_used_at
        FROM b_api_keys
        WHERE user_id = ?
        ORDER BY created_at DESC";

$api_keys = $g_db->getAll($sql, [$mine_user_id], DB_FETCHMODE_ASSOC);

if (DB::isError($api_keys)) {
    error_log("API Keys fetch error: " . $api_keys->getMessage());
    $api_keys = [];
}

// ページメタ情報
$d_site_title = 'API Key管理 - ReadNest';
$g_meta_description = 'API Keyの管理';
$g_meta_keyword = 'ReadNest,API,設定';

// テンプレートを読み込み
include(getTemplatePath('t_api_keys.php'));
?>
