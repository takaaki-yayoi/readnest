<?php
/**
 * ユーザー本登録（アクティベーション）ページ
 * PHP 8.2.28対応版
 */

declare(strict_types=1);

// モダン設定を読み込み
require_once('modern_config.php');
require_once(dirname(__FILE__) . '/library/registration_logger.php');

// ページタイトル設定
$d_site_title = "本登録 - ReadNest";

// メタ情報
$g_meta_description = "ReadNestの本登録を完了して、読書記録を始めましょう。";
$g_meta_keyword = "本登録,アクティベーション,ReadNest";

$valid_flag = true;
$error_message = '';
$activation_status = 'pending'; // pending, success, error

// すでにログインしている場合はトップページへリダイレクト
if (checkLogin()) {
    header('Location: /');
    exit;
}

// interim_idパラメータのチェック
if (empty($_GET['interim_id'])) {
    $activation_status = 'error';
    $error_message = '無効なアクセスです。新規登録からやり直してください。';
} else {
    $interim_id = $_GET['interim_id'];
    
    // アクティベーション試行をログに記録
    RegistrationLogger::logActivationAttempt($interim_id);
    
    try {
        // interim_idからユーザーIDを取得
        $user_id = getUserByInterimId($interim_id);
        
        if ($user_id !== null) {
            // ユーザー情報を取得
            $user_info_array = getUserInformation($user_id);
            
            if ($user_info_array) {
                // すでにアクティベート済みかチェック（regist_dateがnullでないことで判定）
                // MySQL の DATETIME デフォルト値 '0000-00-00 00:00:00' も未登録として扱う
                if ($user_info_array['regist_date'] !== null 
                    && $user_info_array['regist_date'] !== '' 
                    && $user_info_array['regist_date'] !== '0000-00-00 00:00:00') {
                    $activation_status = 'already_activated';
                    $error_message = 'このアカウントは既に本登録が完了しています。';
                    RegistrationLogger::logActivationFailed($interim_id, 'Already activated');
                } else {
                    // アクティベーション実行
                    userActivate($user_id);
                    
                    // セッションにユーザーIDを保存（自動ログイン）
                    $_SESSION['AUTH_USER'] = $user_id;
                    
                    $mail_address = $user_info_array['email'];
                    $nickname = $user_info_array['nickname'];
                    
                    // 本登録完了メールを送信
                    // メール送信前の文字エンコーディング設定
                    mb_language("Japanese");
                    mb_internal_encoding("UTF-8");
                    
                    $mail_title = "ReadNest - 本登録完了 -";
                    $mail_body = "{$nickname}さん、ReadNestへようこそ！\n\n" . 
                               "本登録が完了しました。\n" . 
                               "以下のリンクからご利用いただけます。\n\n" . 
                               "https://readnest.jp\n\n" . 
                               "ReadNestでの読書ライフをお楽しみください。\n\n" . 
                               "【はじめにやること】\n" . 
                               "1. 本棚に読みたい本を追加する\n" . 
                               "2. 読書進捗を記録する\n" . 
                               "3. レビューを投稿する\n\n" . 
                               "--------------------------\n" . 
                               "ReadNest - あなたの読書の巣\n" . 
                               "https://readnest.jp\n";
                    
                    // ヘッダーのエンコード
                    $mail_from = mb_encode_mimeheader("ReadNest") . " <noreply@readnest.jp>";
                    $mail_headers = "From: " . $mail_from . "\r\n";
                    $mail_headers .= "Reply-To: admin@readnest.jp\r\n";
                    $mail_headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
                    $mail_headers .= "Content-Transfer-Encoding: 8bit\r\n";
                    $mail_headers .= "MIME-Version: 1.0\r\n";
                    
                    $result = mb_send_mail($mail_address, $mail_title, $mail_body, $mail_headers);
                    
                    if (!$result) {
                        error_log('Sending activation complete mail failed: ' . $mail_address);
                        RegistrationLogger::logMailSent($mail_address, false);
                    } else {
                        RegistrationLogger::logMailSent($mail_address, true);
                    }
                    
                    // アクティベーション成功をログに記録
                    RegistrationLogger::logActivationSuccess($user_id, $mail_address);
                    
                    $activation_status = 'success';
                }
            } else {
                $activation_status = 'error';
                $error_message = 'ユーザー情報が見つかりません。';
                RegistrationLogger::logActivationFailed($interim_id, 'User not found');
            }
        } else {
            $activation_status = 'error';
            $error_message = '無効なリンクです。有効期限が切れている可能性があります。';
            RegistrationLogger::logExpiredActivation($interim_id);
        }
    } catch (Exception $e) {
        error_log('Activation error: ' . $e->getMessage());
        $activation_status = 'error';
        $error_message = 'アクティベーション処理中にエラーが発生しました。';
        RegistrationLogger::logActivationFailed($interim_id, 'Exception: ' . $e->getMessage());
    }
}

// Analytics設定
$g_analytics = '<!-- Google Analytics code would go here -->';

// モダンテンプレートを使用してページを表示
include(getTemplatePath('t_user_activate.php'));
?>