<?php
/**
 * お問い合わせフォーム処理
 * PHP 8.2.28対応版
 */

declare(strict_types=1);

// 設定を読み込み
require_once('config.php');
require_once('library/session.php');
require_once('library/database.php');

// データベース接続
$g_db = DB_Connect();

// POSTリクエストのみ受け付ける
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /help.php');
    exit;
}

// CSRF対策
// session.phpで既にセッションは開始されている
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    $_SESSION['contact_error'] = 'セッションの有効期限が切れました。もう一度お試しください。';
    header('Location: /help.php#contact-form');
    exit;
}

// 入力値の取得と検証
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$category = trim($_POST['category'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');
$user_id = $_POST['user_id'] ?? null;

// バリデーション
$errors = [];

if (empty($name)) {
    $errors[] = 'お名前を入力してください。';
} elseif (mb_strlen($name) > 50) {
    $errors[] = 'お名前は50文字以内で入力してください。';
}

if (empty($email)) {
    $errors[] = 'メールアドレスを入力してください。';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = '正しいメールアドレスを入力してください。';
}

if (empty($category)) {
    $errors[] = 'お問い合わせ種別を選択してください。';
} elseif (!in_array($category, ['question', 'request', 'bug', 'other'])) {
    $errors[] = '正しいお問い合わせ種別を選択してください。';
}

if (empty($subject)) {
    $errors[] = '件名を入力してください。';
} elseif (mb_strlen($subject) > 100) {
    $errors[] = '件名は100文字以内で入力してください。';
}

if (empty($message)) {
    $errors[] = 'お問い合わせ内容を入力してください。';
} elseif (mb_strlen($message) > 2000) {
    $errors[] = 'お問い合わせ内容は2000文字以内で入力してください。';
}

// エラーがある場合は戻る
if (!empty($errors)) {
    $_SESSION['contact_error'] = implode(' ', $errors);
    header('Location: /help.php#contact-form');
    exit;
}

try {
    // データベースに保存
    $insert_sql = 'INSERT INTO b_contact (user_id, name, email, category, subject, message, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)';
    
    $params = [
        $user_id,
        $name,
        $email,
        $category,
        $subject,
        $message,
        $_SERVER['REMOTE_ADDR'] ?? '',
        $_SERVER['HTTP_USER_AGENT'] ?? '',
        date('Y-m-d H:i:s')
    ];
    
    $result = $g_db->query($insert_sql, $params);
    
    if (DB::isError($result)) {
        throw new Exception('データベースエラー: ' . $result->getMessage());
    }
    
    // メール送信
    mb_language("Japanese");
    mb_internal_encoding("UTF-8");
    
    // カテゴリの日本語表記
    $category_labels = [
        'question' => '使い方に関する質問',
        'request' => '機能改善のご要望',
        'bug' => '不具合の報告',
        'other' => 'その他'
    ];
    
    $category_label = $category_labels[$category] ?? $category;
    
    // 管理者へのメール
    $admin_subject = "[ReadNest] お問い合わせ: {$subject}";
    $admin_body = "ReadNestにお問い合わせがありました。\n\n";
    $admin_body .= "【お問い合わせ情報】\n";
    $admin_body .= "日時: " . date('Y年m月d日 H:i') . "\n";
    $admin_body .= "お名前: {$name}\n";
    $admin_body .= "メールアドレス: {$email}\n";
    $admin_body .= "種別: {$category_label}\n";
    $admin_body .= "件名: {$subject}\n";
    $admin_body .= "\n【お問い合わせ内容】\n";
    $admin_body .= $message . "\n";
    $admin_body .= "\n【システム情報】\n";
    $admin_body .= "ユーザーID: " . ($user_id ?? '未ログイン') . "\n";
    $admin_body .= "IPアドレス: " . ($_SERVER['REMOTE_ADDR'] ?? '不明') . "\n";
    
    $admin_headers = "From: " . mb_encode_mimeheader("ReadNest お問い合わせフォーム", "UTF-8", "B") . " <noreply@readnest.jp>\r\n";
    $admin_headers .= "Reply-To: {$email}\r\n";
    $admin_headers .= "MIME-Version: 1.0\r\n";
    $admin_headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $admin_headers .= "Content-Transfer-Encoding: 8bit\r\n";
    
    $mail_sent = mb_send_mail('admin@readnest.jp', $admin_subject, $admin_body, $admin_headers);
    
    // ユーザーへの確認メール
    $user_subject = "[ReadNest] お問い合わせを受け付けました";
    $user_body = "{$name} 様\n\n";
    $user_body .= "ReadNestへのお問い合わせありがとうございます。\n";
    $user_body .= "以下の内容でお問い合わせを受け付けました。\n\n";
    $user_body .= "【お問い合わせ内容】\n";
    $user_body .= "種別: {$category_label}\n";
    $user_body .= "件名: {$subject}\n";
    $user_body .= "内容:\n{$message}\n\n";
    $user_body .= "内容を確認の上、ご返信させていただきます。\n";
    $user_body .= "お急ぎの場合は恐れ入りますが、しばらくお待ちください。\n\n";
    $user_body .= "─────────────────\n";
    $user_body .= "ReadNest - あなたの読書の巣\n";
    $user_body .= "https://readnest.jp/\n";
    $user_body .= "─────────────────\n";
    
    $user_headers = "From: " . mb_encode_mimeheader("ReadNest", "UTF-8", "B") . " <noreply@readnest.jp>\r\n";
    $user_headers .= "MIME-Version: 1.0\r\n";
    $user_headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $user_headers .= "Content-Transfer-Encoding: 8bit\r\n";
    
    mb_send_mail($email, $user_subject, $user_body, $user_headers);
    
    // 成功メッセージを設定してリダイレクト
    $_SESSION['contact_success'] = true;
    header('Location: /help.php#contact-form');
    exit;
    
} catch (Exception $e) {
    error_log('Contact form error: ' . $e->getMessage());
    $_SESSION['contact_error'] = 'お問い合わせの送信中にエラーが発生しました。しばらく時間をおいて再度お試しください。';
    header('Location: /help.php#contact-form');
    exit;
}
?>