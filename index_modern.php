<?php
/**
 * モダンテンプレートを使用するトップページ
 * PHP 8.2.28対応版
 * 既存のindex.phpをベースに、新しいテンプレートシステムを適用
 */

declare(strict_types=1);

// モダン設定を読み込み
require_once('modern_config.php');

// セッション管理は自動的に開始される（session.phpで処理済み）

// データベース接続は既にconfig.phpで$g_dbとして設定済み

// ページタイトル設定
$d_site_title = "ReadNest - あなたの読書の巣";

// メタ情報
$g_meta_description = "ReadNest - 読書の進捉を記録し、レビューを書き、本を整理するための居心地のよい空間です。読書仲間とのつながりを楽しみましょう。";
$g_meta_keyword = "読書,本,書評,レビュー,本棚,読書記録,ReadNest";

// 統計情報を取得
try {
    global $g_db;
    
    // 総ユーザー数
    $total_users = $g_db->getOne("SELECT COUNT(*) FROM b_user WHERE status = 1");
    if(DB::isError($total_users)) {
        $total_users = 1234;
    } else {
        $total_users = intval($total_users ?? 0);
    }
    
    // 総書籍数
    $total_books = $g_db->getOne("SELECT COUNT(DISTINCT book_id) FROM b_book_list");
    if(DB::isError($total_books)) {
        $total_books = 45678;
    } else {
        $total_books = intval($total_books ?? 0);
    }
    
    // 総レビュー数
    $total_reviews = $g_db->getOne("SELECT COUNT(*) FROM b_book_list WHERE comment != '' AND comment IS NOT NULL");
    if(DB::isError($total_reviews)) {
        $total_reviews = 8901;
    } else {
        $total_reviews = intval($total_reviews ?? 0);
    }
    
    // 総読了ページ数（概算）
    $total_pages_read = $g_db->getOne("SELECT SUM(CASE WHEN status = ? THEN total_page ELSE current_page END) FROM b_book_list WHERE current_page > 0", array(READING_FINISH));
    if(DB::isError($total_pages_read)) {
        $total_pages_read = 234567;
    } else {
        $total_pages_read = intval($total_pages_read ?? 0);
    }
    
} catch (Exception $e) {
    // エラー時はデフォルト値を使用
    $total_users = 1234;
    $total_books = 45678;
    $total_reviews = 8901;
    $total_pages_read = 234567;
}

// 新着レビューを取得
try {
    $new_reviews_sql = "
        SELECT bl.comment, bl.rating, bl.book_id, b.title as book_title, u.user_id, u.nickname, u.user_photo, bl.updated_at as created_at
        FROM b_book_list bl
        JOIN b_user u ON bl.user_id = u.user_id
        JOIN b_book b ON bl.book_id = b.book_id
        WHERE bl.comment != '' AND bl.comment IS NOT NULL AND u.status = 1
        ORDER BY bl.updated_at DESC
        LIMIT 5
    ";
    $new_reviews = $g_db->getAll($new_reviews_sql);
    if(DB::isError($new_reviews)) {
        $new_reviews = array();
    }
} catch (Exception $e) {
    $new_reviews = array();
}

// 読書中の本を取得
try {
    $reading_books_sql = "
        SELECT DISTINCT b.book_id, b.title, b.image_url
        FROM b_book_list bl
        JOIN b_book b ON bl.book_id = b.book_id
        JOIN b_user u ON bl.user_id = u.user_id
        WHERE bl.status = ? AND u.status = 1
        ORDER BY bl.updated_at DESC
        LIMIT 12
    ";
    $reading_books = $g_db->getAll($reading_books_sql, array(READING_NOW));
    if(DB::isError($reading_books)) {
        $reading_books = array();
    }
} catch (Exception $e) {
    $reading_books = array();
}

// 人気のタグを取得（ダミーデータ）
$popular_tags = array(
    array('name' => '小説', 'count' => 156),
    array('name' => 'ビジネス', 'count' => 89),
    array('name' => '技術書', 'count' => 67),
    array('name' => '漫画', 'count' => 234),
    array('name' => '自己啓発', 'count' => 45),
    array('name' => '歴史', 'count' => 78),
    array('name' => 'ミステリー', 'count' => 123),
    array('name' => 'SF', 'count' => 34)
);

// ログイン処理
$g_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $g_error = 'メールアドレスとパスワードを入力してください';
    } else {
        try {
            // ユーザー認証
            $user_sql = "SELECT user_id, password, nickname, status FROM b_user WHERE email = ?";
            $user_result = DB_Query($user_sql, [$email]);
            
            if (!empty($user_result)) {
                $user = $user_result[0];
                
                // パスワード確認（SHA1ハッシュ）
                if (sha1($password) === $user['password']) {
                    if ($user['status'] == 1) {
                        // ログイン成功
                        $_SESSION['user_id'] = $user['user_id'];
                        $_SESSION['nickname'] = $user['nickname'];
                        
                        // モダンテンプレートを有効化
                        $_SESSION['use_modern_template'] = true;
                        setcookie('use_modern_template', '1', time() + (86400 * 30), '/'); // 30日間有効
                        
                        // リダイレクト
                        header('Location: /bookshelf.php');
                        exit;
                    } else {
                        $g_error = 'アカウントが有効化されていません';
                    }
                } else {
                    $g_error = 'メールアドレスまたはパスワードが正しくありません';
                }
            } else {
                $g_error = 'メールアドレスまたはパスワードが正しくありません';
            }
        } catch (Exception $e) {
            $g_error = 'ログイン処理中にエラーが発生しました';
            error_log('Login error: ' . $e->getMessage());
        }
    }
}

// Analytics設定
$g_analytics = '<!-- Google Analytics code would go here -->';

// モダンテンプレートを使用してページを表示
if (isset($_SESSION['user_id'])) {
    // ログイン済みの場合はダッシュボード表示
    include(getTemplatePath('t_index.php'));
} else {
    // 未ログインの場合はログインフォーム付きトップページ
    include(getTemplatePath('t_index.php'));
}