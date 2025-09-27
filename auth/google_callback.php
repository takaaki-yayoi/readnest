<?php
/**
 * Google OAuth コールバックページ
 * Googleからの認証コードを受け取り、ユーザーログイン処理を行う
 */

require_once(dirname(__DIR__) . '/config.php');
require_once(dirname(__DIR__) . '/library/google_oauth.php');
require_once(dirname(__DIR__) . '/library/database.php');

// エラーログのみ（本番環境）
error_reporting(E_ALL);
ini_set('display_errors', 0);

// データベース接続を確立
global $g_db;
if (!isset($g_db)) {
    $g_db = DB_Connect();
}

// セッション開始（既にconfig.phpで開始されている場合もあるため確認）
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    // パラメータチェック
    if (isset($_GET['error'])) {
        throw new Exception('Google認証がキャンセルされました');
    }
    
    if (!isset($_GET['code']) || !isset($_GET['state'])) {
        throw new Exception('認証パラメータが不正です');
    }
    
    // CSRF対策: stateの検証
    if (!isset($_SESSION['google_oauth_state']) || $_GET['state'] !== $_SESSION['google_oauth_state']) {
        throw new Exception('セキュリティエラー: 不正なリクエストです');
    }
    
    // stateを削除
    unset($_SESSION['google_oauth_state']);
    
    // Google OAuthインスタンスを作成
    $google = new GoogleOAuth();
    
    // 認証コードをアクセストークンに交換
    $tokenData = $google->getAccessToken($_GET['code']);
    
    // ユーザー情報を取得
    $userInfo = $google->getUserInfo($tokenData['access_token']);
    
    // Google IDでユーザーを検索
    $existingUser = $google->findUserByGoogleId($userInfo['id']);
    
    if ($existingUser) {
        // 既存ユーザーの場合: ログイン処理
        $_SESSION['AUTH_USER'] = $existingUser['user_id'];
        $_SESSION['USER_NAME'] = $existingUser['nickname'];
        
        // 自動ログイン設定（15日間）
        require_once(dirname(__DIR__) . '/library/session.php');
        setAutoLogin($existingUser['user_id']);
        
        // トークン情報を更新
        $google->saveGoogleAuth(
            $existingUser['user_id'],
            $userInfo['id'],
            $userInfo['email'],
            $userInfo['name'],
            $userInfo['picture'] ?? null,
            $tokenData['access_token'],
            $tokenData['refresh_token'] ?? null,
            $tokenData['expires_in']
        );
        
    } else {
        // 新規ユーザーまたは未連携ユーザーの場合
        
        // メールアドレスで既存ユーザーを検索
        $unlinkedUser = $google->findUnlinkedUserByEmail($userInfo['email']);
        
        if ($unlinkedUser) {
            // 既存ユーザーとGoogleアカウントを連携
            $_SESSION['google_link_user'] = $unlinkedUser;
            $_SESSION['google_user_info'] = $userInfo;
            $_SESSION['google_token_data'] = $tokenData;
            
            // 連携確認ページへリダイレクト
            header('Location: /auth/google_link.php');
            exit;
            
        } else {
            // 完全に新規のユーザー
            
            // メールアドレスの重複チェック（Google連携済みユーザーも含む）
            $checkEmailSql = "SELECT user_id, nickname 
                            FROM b_user 
                            WHERE email = ? 
                            AND status = " . USER_STATUS_ACTIVE;
            $existingUserWithEmail = $g_db->getRow($checkEmailSql, [$userInfo['email']], DB_FETCHMODE_ASSOC);
            
            if (!DB::isError($existingUserWithEmail) && $existingUserWithEmail) {
                // 同じメールアドレスのユーザーが既に存在する場合
                error_log("Error: Email address already exists: {$userInfo['email']} (user_id: {$existingUserWithEmail['user_id']})");
                
                // エラーメッセージをセッションに保存
                $_SESSION['auth_error'] = 'このメールアドレスは既に登録されています。既存のアカウントでログインしてください。';
                header('Location: /login.php');
                exit;
            }
            
            // まず、このGoogle IDを持つ削除済みユーザーが存在する場合、Google IDをクリアする
            // これにより、同じGoogleアカウントで新規登録が可能になる
            $clearGoogleIdSql = "UPDATE b_user SET google_id = NULL 
                                WHERE google_id = ? 
                                AND status = " . USER_STATUS_DELETED;
            $clearResult = $g_db->query($clearGoogleIdSql, [$userInfo['id']]);
            
            if (DB::isError($clearResult)) {
                error_log("Warning: Failed to clear google_id from deleted users: " . $clearResult->getMessage());
            }
            
            // 同様にb_google_authテーブルからも削除済みユーザーの情報を削除
            $deleteGoogleAuthSql = "DELETE ga FROM b_google_auth ga 
                                   INNER JOIN b_user u ON ga.user_id = u.user_id 
                                   WHERE ga.google_id = ? 
                                   AND u.status = " . USER_STATUS_DELETED;
            $deleteResult = $g_db->query($deleteGoogleAuthSql, [$userInfo['id']]);
            
            if (DB::isError($deleteResult)) {
                error_log("Warning: Failed to delete google_auth for deleted users: " . $deleteResult->getMessage());
            }
            
            // 新規ユーザー作成
            $nickname = $userInfo['name'] ?? $userInfo['email'];
            
            // デバッグ: ユーザー情報を表示
            error_log("Creating new user: email={$userInfo['email']}, nickname=$nickname");
            
            // user_idはauto_incrementなので指定しない
            $create_sql = 'INSERT INTO b_user(email, create_date, regist_date, nickname, password, diary_policy, status, google_id) 
                          VALUES(?, NOW(), NOW(), ?, ?, ?, ?, ?)';
            
            // ランダムパスワード生成（Googleログインのみ使用するため実際には使わない）
            $randomPassword = sha1(bin2hex(random_bytes(16)));
            
            $result = $g_db->query($create_sql, array(
                $userInfo['email'],
                $nickname,
                $randomPassword,
                1, // diary_policy: 公開
                USER_STATUS_ACTIVE, // 本登録状態
                $userInfo['id'] // Google ID
            ));
            
            if (DB::isError($result)) {
                error_log("DB Error: " . $result->getMessage());
                error_log("SQL: " . $create_sql);
                error_log("Params: " . print_r(array($userInfo['email'], $nickname, $randomPassword, 1, USER_STATUS_ACTIVE, $userInfo['id']), true));
                throw new Exception('ユーザー登録に失敗しました: ' . $result->getMessage());
            }
            
            // 挿入されたユーザーIDを取得
            $newUserId = $g_db->getOne('SELECT LAST_INSERT_ID()');
            if (DB::isError($newUserId) || !$newUserId) {
                throw new Exception('ユーザーIDの取得に失敗しました');
            }
            
            error_log("User created successfully with ID: $newUserId");
            
            // Google認証情報を保存
            $google->saveGoogleAuth(
                $newUserId,
                $userInfo['id'],
                $userInfo['email'],
                $userInfo['name'],
                $userInfo['picture'] ?? null,
                $tokenData['access_token'],
                $tokenData['refresh_token'] ?? null,
                $tokenData['expires_in']
            );
            
            // ログイン処理
            $_SESSION['AUTH_USER'] = $newUserId;
            $_SESSION['USER_NAME'] = $nickname;
            
            // 新規登録時も自動ログイン設定（15日間）
            require_once(dirname(__DIR__) . '/library/session.php');
            setAutoLogin($newUserId);
        }
    }
    
    // リダイレクト先を決定
    $redirectUrl = '/bookshelf.php';
    if (isset($_SESSION['google_oauth_redirect'])) {
        $redirectUrl = $_SESSION['google_oauth_redirect'];
        unset($_SESSION['google_oauth_redirect']);
    }
    
    // ログイン成功、リダイレクト
    header('Location: ' . $redirectUrl);
    exit;
    
} catch (Exception $e) {
    // エラーログに記録
    error_log('Google OAuth Error: ' . $e->getMessage());
    error_log('File: ' . $e->getFile() . ' Line: ' . $e->getLine());
    
    // ユーザーにはエラーメッセージを表示
    $_SESSION['error_message'] = 'Googleログインに失敗しました。しばらくしてから再度お試しください。';
    header('Location: /');
    exit;
}