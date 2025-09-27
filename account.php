<?php
/**
 * モダンアカウント設定ページ
 * PHP 8.2.28対応版
 */

declare(strict_types=1);

// モダン設定を読み込み
require_once('modern_config.php');

// フォームヘルパーを読み込み
require_once(dirname(__FILE__) . '/library/form_helpers.php');

// checkPasswordById関数が定義されていない場合の緊急対処
if (!function_exists('checkPasswordById')) {
    function checkPasswordById($user_id, $password) {
        global $g_db;
        
        // パスワードをSHA1でハッシュ化
        $password_hash = sha1($password);
        
        // ユーザーIDとパスワードが一致するか確認（regist_dateがnullでないことで有効なユーザーを判定）
        $check_sql = 'SELECT COUNT(*) FROM b_user WHERE user_id = ? AND password = ? AND regist_date IS NOT NULL';
        $result = $g_db->getOne($check_sql, array($user_id, $password_hash));
        
        if (DB::isError($result)) {
            trigger_error($result->getMessage());
            return false;
        }
        
        return ($result > 0);
    }
}

// ログインチェック
if (!checkLogin()) {
    header('Location: https://readnest.jp/');
    exit;
}

$user_id = $_SESSION['AUTH_USER'];
$d_nickname = getNickname($user_id);
$user_info = getUserInformation($user_id);

// X連携状態を確認
$x_connected = !empty($user_info['x_oauth_token']) && !empty($user_info['x_oauth_token_secret']);
$x_screen_name = $user_info['x_screen_name'] ?? '';
$x_post_enabled = $user_info['x_post_enabled'] ?? 0;
$x_post_events = $user_info['x_post_events'] ?? 13;

// ページタイトル設定
$d_site_title = "アカウント設定 - ReadNest";

// メタ情報
$g_meta_description = "ReadNestのアカウント設定。プロフィール情報、メールアドレス、パスワード、プライバシー設定を管理できます。";
$g_meta_keyword = "アカウント設定,プロフィール,ReadNest,設定";

// 初期値設定
$form_data = [
    'email' => $user_info['email'] ?? '',
    'nickname' => $d_nickname,
    'diary_policy' => $user_info['diary_policy'] ?? 1,
    'books_per_year' => $user_info['books_per_year'] ?? '',
    'introduction' => $user_info['introduction'] ?? ''
];

$errors = [];
$success_message = '';
$step = 'input'; // input, confirm, complete

// URLパラメータからのメッセージ処理（X連携関連）
if (isset($_GET['x_connected']) && $_GET['x_connected'] === 'success') {
    $success_message = 'Xアカウントの連携が完了しました。';
}
if (isset($_GET['x_disconnected']) && $_GET['x_disconnected'] === 'success') {
    $success_message = 'X連携を解除しました。';
}

// X連携エラーメッセージ処理
if (isset($_GET['x_error'])) {
    switch ($_GET['x_error']) {
        case 'missing_params':
            $errors[] = 'X連携の処理中にパラメータが不足しています。';
            break;
        case 'token_mismatch':
            $errors[] = '認証トークンが一致しません。もう一度お試しください。';
            break;
        case 'access_token_failed':
            $errors[] = 'Xアクセストークンの取得に失敗しました。';
            break;
        case 'invalid_access_token':
            $errors[] = '無効なアクセストークンです。';
            break;
        case 'storage_failed':
            $errors[] = 'X連携情報の保存に失敗しました。';
            break;
        case 'request_token_failed':
            $errors[] = 'X認証の開始に失敗しました。';
            break;
        case 'invalid_token':
            $errors[] = '無効な認証トークンです。';
            break;
        default:
            $errors[] = 'X連携処理中にエラーが発生しました。';
            break;
    }
}

// プロフィール写真情報
$profile_photo_url = '';
$has_profile_photo = false;

if (isset($user_info['photo']) && $user_info['photo'] && $user_info['photo_state'] == PHOTO_REGISTER_STATE) {
    $profile_photo_url = "https://readnest.jp/display_profile_photo.php?user_id={$user_id}&mode=thumbnail";
    $has_profile_photo = true;
}

// POST処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'upload_photo') {
        // プロフィール画像アップロード処理
        if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] !== UPLOAD_ERR_NO_FILE) {
            $file = $_FILES['profile_photo'];
            
            if ($file['error'] === UPLOAD_ERR_OK) {
                // ファイルサイズチェック（1MB以下）
                if ($file['size'] > MAX_PHOTO_FILE_SIZE) {
                    $errors[] = 'ファイルサイズは1MB以下にしてください。';
                } else {
                    // 画像形式チェック
                    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime_type = finfo_file($finfo, $file['tmp_name']);
                    finfo_close($finfo);
                    
                    if (!in_array($mime_type, $allowed_types)) {
                        $errors[] = '画像はJPEG、PNG、GIFのいずれかでアップロードしてください。';
                    } else {
                        // 画像を保存
                        try {
                            // 古い画像を削除
                            if ($has_profile_photo) {
                                deleteProfilePhoto($user_id);
                            }
                            
                            // 新しい画像を保存
                            $photo_data = file_get_contents($file['tmp_name']);
                            saveProfilePhoto($user_id, $photo_data, $mime_type);
                            
                            $success_message = 'プロフィール画像をアップロードしました。';
                            $has_profile_photo = true;
                            $profile_photo_url = "https://readnest.jp/display_profile_photo.php?user_id={$user_id}&mode=thumbnail";
                        } catch (Exception $e) {
                            error_log("Error uploading profile photo: " . $e->getMessage());
                            $errors[] = '画像のアップロード中にエラーが発生しました。';
                        }
                    }
                }
            } else {
                $errors[] = 'ファイルのアップロードに失敗しました。';
            }
        }
    } elseif ($action === 'delete_photo') {
        // プロフィール画像削除処理
        try {
            deleteProfilePhoto($user_id);
            $success_message = 'プロフィール画像を削除しました。';
            $has_profile_photo = false;
            $profile_photo_url = '';
        } catch (Exception $e) {
            error_log("Error deleting profile photo: " . $e->getMessage());
            $errors[] = '画像の削除中にエラーが発生しました。';
        }
    } elseif ($action === 'update_profile') {
        // プロフィール更新処理
        // メールアドレスは変更不可なので、現在の値を使用
        $form_data['email'] = $user_info['email'];
        $form_data['nickname'] = trim($_POST['nickname'] ?? '');
        $form_data['diary_policy'] = (int)($_POST['diary_policy'] ?? 1);
        $form_data['books_per_year'] = trim($_POST['books_per_year'] ?? '');
        $form_data['introduction'] = trim($_POST['introduction'] ?? '');
        $confirm = $_POST['confirm'] ?? '';
        
        // バリデーション
        // メールアドレスのバリデーションは不要（変更不可のため）
        
        if (empty($form_data['nickname'])) {
            $errors[] = 'ニックネームを入力してください。';
        } elseif (mb_strlen($form_data['nickname']) > 50) {
            $errors[] = 'ニックネームは50文字以内で入力してください。';
        }
        
        if (!empty($form_data['books_per_year'])) {
            if (!is_numeric($form_data['books_per_year']) || (int)$form_data['books_per_year'] < 0 || (int)$form_data['books_per_year'] > 1000) {
                $errors[] = '年間目標読書数は0〜1000の数値で入力してください。';
            }
        }
        
        if (mb_strlen($form_data['introduction']) > 500) {
            $errors[] = '自己紹介は500文字以内で入力してください。';
        }
        
        if (empty($errors)) {
            if ($confirm === 'yes') {
                // 最終確認後の更新処理
                try {
                    $password = ''; // パスワードは別途処理
                    $amazon_id = ''; // Amazon IDは使用しない
                    $pager_type = '';
                    
                    updateUserInformation(
                        $user_id,
                        $form_data['email'],
                        $form_data['nickname'],
                        $password,
                        $amazon_id,
                        $form_data['diary_policy'],
                        $form_data['books_per_year'],
                        $pager_type,
                        $form_data['introduction']
                    );
                    
                    $success_message = 'プロフィール情報を更新しました。';
                    $step = 'complete';
                    
                    // セッションのニックネームも更新
                    $_SESSION['nickname'] = $form_data['nickname'];
                    
                } catch (Exception $e) {
                    error_log("Error updating user profile: " . $e->getMessage());
                    $errors[] = '更新中にエラーが発生しました。もう一度お試しください。';
                }
            } else {
                // 確認画面表示
                $step = 'confirm';
            }
        }
        
    } elseif ($action === 'change_password') {
        // パスワード変更処理
        
        // Googleログインのみのユーザーはパスワード変更不可
        $is_google_only_user = false;
        if (!empty($user_info['google_id']) && 
            $user_info['create_date'] === $user_info['regist_date'] &&
            $user_info['regist_date'] !== '0000-00-00 00:00:00' &&
            $user_info['regist_date'] !== null) {
            $is_google_only_user = true;
        }
        if ($is_google_only_user) {
            $errors[] = 'Googleアカウントでログインしているため、パスワードの変更はできません。';
        } else {
            $current_password = $_POST['current_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            
            if (empty($current_password)) {
                $errors[] = '現在のパスワードを入力してください。';
            }
        
            if (empty($new_password)) {
                $errors[] = '新しいパスワードを入力してください。';
            } elseif (strlen($new_password) < 6) {
                $errors[] = 'パスワードは6文字以上で入力してください。';
            } elseif ($new_password !== $confirm_password) {
                $errors[] = 'パスワードが一致しません。';
            }
            
            if (empty($errors)) {
                // 現在のパスワード確認
                global $g_db;
                $current_hash = $g_db->getOne("SELECT password FROM b_user WHERE user_id = ?", array($user_id));
                
                if (!DB::isError($current_hash) && $current_hash === sha1($current_password)) {
                    try {
                        // パスワード更新
                        $new_hash = sha1($new_password);
                        $result = $g_db->query("UPDATE b_user SET password = ? WHERE user_id = ?", array($new_hash, $user_id));
                        
                        if (!DB::isError($result)) {
                            $success_message = 'パスワードを変更しました。';
                            // ユーザー情報を更新
                            $user_info['password'] = $new_hash;
                        } else {
                            error_log("Error updating password: " . $result->getMessage());
                            $errors[] = 'パスワード変更中にエラーが発生しました。';
                        }
                    } catch (Exception $e) {
                        error_log("Error updating password: " . $e->getMessage());
                        $errors[] = 'パスワード変更中にエラーが発生しました。';
                    }
                } else {
                    $errors[] = '現在のパスワードが正しくありません。';
                }
            }
        }
    } elseif ($action === 'delete_account') {
        // 退会処理
        $delete_confirm = $_POST['delete_confirm'] ?? '';
        $delete_password = $_POST['delete_password'] ?? '';
        
        if ($delete_confirm === 'yes') {
            // Googleログインユーザーか確認
            $is_google_only_user = (!empty($user_info['google_id']) && 
                                   $user_info['create_date'] === $user_info['regist_date'] &&
                                   $user_info['regist_date'] !== '0000-00-00 00:00:00' &&
                                   $user_info['regist_date'] !== null);
            
            // パスワード確認（Googleのみのユーザーはスキップ）
            if (!$is_google_only_user && empty($delete_password)) {
                $errors[] = 'パスワードを入力してください。';
            } else {
                // Googleのみのユーザーまたはパスワードが正しい場合
                if ($is_google_only_user || checkPasswordById($user_id, $delete_password)) {
                    try {
                        // ユーザーデータを削除
                        $delete_result = deleteUserAccount($user_id);
                        
                        if ($delete_result === true) {
                            // セッション破棄
                            $_SESSION = array();
                            if (isset($_COOKIE[session_name()])) {
                                setcookie(session_name(), '', time() - 42000, '/');
                            }
                            session_destroy();
                            
                            // 退会完了ページへリダイレクト
                            header('Location: /account_deleted.php');
                            exit;
                        } else {
                            error_log("Error deleting account: deleteUserAccount returned false for user_id: " . $user_id);
                            $errors[] = 'アカウント削除中にエラーが発生しました。';
                        }
                    } catch (Exception $e) {
                        error_log("Error deleting account for user_id {$user_id}: " . $e->getMessage());
                        error_log("Stack trace: " . $e->getTraceAsString());
                        $errors[] = 'アカウント削除中にエラーが発生しました。詳細: ' . $e->getMessage();
                    }
                } else {
                    $errors[] = 'パスワードが正しくありません。';
                }
            }
        }
    } elseif ($action === 'update_x_settings' && $x_connected) {
        // X連携設定の更新
        $x_post_enabled = isset($_POST['x_post_enabled']) ? 1 : 0;
        $x_post_events_array = $_POST['x_post_events'] ?? [];
        
        // ビットマスクに変換
        $x_post_events = 0;
        foreach ($x_post_events_array as $event_flag) {
            $x_post_events |= (int)$event_flag;
        }
        
        // データベース更新
        global $g_db;
        $update_sql = "UPDATE b_user SET x_post_enabled = ?, x_post_events = ? WHERE user_id = ?";
        $result = $g_db->query($update_sql, [$x_post_enabled, $x_post_events, $user_id]);
        
        if (!DB::isError($result)) {
            $success_message = 'X連携設定を更新しました。';
            // ユーザー情報を再読み込み
            $user_info = getUserInformation($user_id);
            $x_post_enabled = $user_info['x_post_enabled'] ?? 0;
            $x_post_events = $user_info['x_post_events'] ?? 13;
        } else {
            $errors[] = 'X連携設定の更新に失敗しました。';
        }
    } elseif ($action === 'unlink_google') {
        // Google連携解除
        global $g_db;
        
        // b_google_authから削除（user_idを文字列として扱う）
        $delete_sql = "DELETE FROM b_google_auth WHERE user_id = ?";
        $result = $g_db->query($delete_sql, [(string)$user_id]);
        
        if (!DB::isError($result)) {
            // b_userのgoogle_idもNULLに設定
            $update_sql = "UPDATE b_user SET google_id = NULL WHERE user_id = ?";
            $g_db->query($update_sql, [$user_id]);
            
            $success_message = 'Googleアカウントの連携を解除しました。';
            $user_info['google_id'] = null;
            $google_auth_info = null;
            $is_google_linked = false;
        } else {
            $errors[] = 'Google連携の解除に失敗しました。';
        }
    }
}

// プロフィール写真情報は既にPOST処理前に初期化済み

// Google認証情報を取得
$google_auth_info = null;
$is_google_linked = false;

// b_google_authテーブルから情報を取得
global $g_db;
$google_sql = "SELECT * FROM b_google_auth WHERE user_id = CAST(? AS CHAR)";
$google_auth_info = $g_db->getRow($google_sql, [$user_id], DB_FETCHMODE_ASSOC);

if (!DB::isError($google_auth_info) && $google_auth_info) {
    $is_google_linked = true;
} elseif (!empty($user_info['google_id'])) {
    // b_google_authにレコードがないがb_user.google_idが存在する場合
    $is_google_linked = true;
    $google_auth_info = array(
        'google_id' => $user_info['google_id'],
        'google_email' => $user_info['email'],
        'google_name' => $user_info['nickname'] ?? $user_info['email']
    );
}

// Analytics設定
$g_analytics = '<!-- Google Analytics code would go here -->';

// テンプレート用変数を設定
$d_is_google_linked = $is_google_linked;

// モダンテンプレートを使用してページを表示
include(getTemplatePath('t_account.php'));
?>