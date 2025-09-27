<?php
/**
 * モダン新規登録ページ
 * PHP 8.2.28対応版
 */

declare(strict_types=1);

// モダン設定を読み込み
require_once('modern_config.php');
require_once(dirname(__FILE__) . '/library/registration_logger.php');

// ページタイトル設定
$d_site_title = "新規登録 - ReadNest";

// メタ情報
$g_meta_description = "ReadNestに新規登録して、読書の記録を始めましょう。本の管理、読書進捗の記録、レビューの投稿など、様々な機能をご利用いただけます。";
$g_meta_keyword = "新規登録,会員登録,ReadNest,読書記録,本棚";

$valid_flag = true;
$errors = [];
$success_message = '';
$step = 'input';

// すでにログインしている場合はリダイレクト
if(checkLogin()) {
    header('Location: /');
    exit;
}

// フォームデータの初期化
$email1 = $_POST['email1'] ?? '';
$email2 = $_POST['email2'] ?? '';
$nickname = $_POST['nickname'] ?? '';
$password1 = $_POST['password1'] ?? '';
$password2 = $_POST['password2'] ?? '';
$confirm = $_POST['confirm'] ?? '';
$agree_terms = $_POST['agree_terms'] ?? '';

// POSTデータがある場合の処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // バリデーション
    if (empty($email1)) {
        $errors[] = 'メールアドレスを入力してください。';
        $valid_flag = false;
    } elseif (!filter_var($email1, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'メールアドレスの形式が正しくありません。';
        $valid_flag = false;
    }
    
    if (empty($email2)) {
        $errors[] = 'メールアドレス（確認）を入力してください。';
        $valid_flag = false;
    } elseif ($email1 !== $email2) {
        $errors[] = 'メールアドレスが一致しません。';
        $valid_flag = false;
    }
    
    // メールアドレスの重複チェック
    if ($valid_flag && !empty($email1)) {
        try {
            global $g_db;
            // 仮登録・本登録問わず、すべてのユーザーをチェック（削除済みを除く）
            $sql = "SELECT user_id, status, regist_date FROM b_user WHERE email = ? AND status != ? LIMIT 1";
            $result = $g_db->getRow($sql, array($email1, USER_STATUS_DELETED), DB_FETCHMODE_ASSOC);
            if (!DB::isError($result) && $result) {
                RegistrationLogger::logDuplicateAttempt($email1, (string)$result['status']);
                if ($result['status'] == USER_STATUS_INTERIM) {
                    $errors[] = 'このメールアドレスは仮登録済みです。送信された認証メールをご確認ください。';
                } else {
                    $errors[] = 'このメールアドレスは既に登録されています。';
                }
                $valid_flag = false;
            }
        } catch (Exception $e) {
            // エラーログに記録
            error_log('Email duplicate check error: ' . $e->getMessage());
        }
    }
    
    if (empty($nickname)) {
        $errors[] = 'ニックネームを入力してください。';
        $valid_flag = false;
    } elseif (mb_strlen($nickname) > 50) {
        $errors[] = 'ニックネームは50文字以内で入力してください。';
        $valid_flag = false;
    }
    
    if (empty($password1)) {
        $errors[] = 'パスワードを入力してください。';
        $valid_flag = false;
    } elseif (strlen($password1) < 6) {
        $errors[] = 'パスワードは6文字以上で入力してください。';
        $valid_flag = false;
    }
    
    if (empty($password2)) {
        $errors[] = 'パスワード（確認）を入力してください。';
        $valid_flag = false;
    } elseif ($password1 !== $password2) {
        $errors[] = 'パスワードが一致しません。';
        $valid_flag = false;
    }
    
    if (empty($agree_terms)) {
        $errors[] = '利用規約に同意してください。';
        $valid_flag = false;
    }
    
    // 確認画面または登録処理
    if ($valid_flag) {
        if ($confirm !== 'yes') {
            // 確認画面を表示
            $step = 'confirm';
        } else {
            // 本登録処理
            try {
                RegistrationLogger::logRegistrationStart($email1, $nickname);
                $interim_id = registUserInterim($email1, $nickname, $password1);
                
                if ($interim_id === false) {
                    throw new Exception('データベースエラーが発生しました');
                }
                
                RegistrationLogger::logInterimRegistrationSuccess($email1, $nickname, $interim_id);
                
                $activate_url = "https://readnest.jp/user_activate.php?interim_id=$interim_id";
                
                // メール送信前の文字エンコーディング設定
                mb_language("Japanese");
                mb_internal_encoding("UTF-8");
                
                $mail_title = "ReadNest - 仮登録メール -";
                $mail_body = "ReadNestへのご登録ありがとうございます。\n\n" .
                           "以下のリンクをクリックすると本登録画面に移動します。\n" . 
                           "※このリンクは1時間有効です。\n\n" . 
                           "$activate_url\n". 
                           "\n" . 
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
                
                $result = mb_send_mail($email1, $mail_title, $mail_body, $mail_headers);
                
                RegistrationLogger::logMailSent($email1, $result);
                
                if(!$result) {
                    error_log('Sending mail failed: ' . $email1);
                    $errors[] = 'メールの送信に失敗しました。しばらく経ってから再度お試しください。';
                } else {
                    $step = 'complete';
                }
            } catch (Exception $e) {
                error_log('Registration error: ' . $e->getMessage());
                RegistrationLogger::logInterimRegistrationFailed($email1, $e->getMessage());
                $errors[] = '登録処理中にエラーが発生しました。しばらく経ってから再度お試しください。';
            }
        }
    }
} else {
    $step = 'input';
}

// Analytics設定
$g_analytics = '<!-- Google Analytics code would go here -->';

// モダンテンプレートを使用してページを表示
include(getTemplatePath('t_register.php'));
?>