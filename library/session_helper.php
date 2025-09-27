<?php
/**
 * セッション管理ヘルパー
 * セッションタイムアウトやAJAXリクエストの処理を改善
 */

/**
 * セッションの状態をチェックしてJSONレスポンスを返す（AJAX用）
 */
function checkSessionForAjax() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (empty($_SESSION['AUTH_USER'])) {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode([
            'error' => 'Session expired',
            'message' => 'セッションがタイムアウトしました。ページを再読み込みしてください。',
            'redirect' => '/login.php'
        ]);
        exit;
    }
    
    return true;
}

/**
 * セッションの状態をチェックして適切にリダイレクト（通常ページ用）
 */
function checkSessionForPage() {
    if (empty($_SESSION['AUTH_USER'])) {
        // 現在のURLを保存してログイン後に戻れるようにする
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        
        // ログインページへリダイレクト
        header('Location: /login.php?session_expired=1');
        exit;
    }
    
    return true;
}

/**
 * セッションの残り時間を取得
 */
function getSessionRemainingTime() {
    if (empty($_SESSION['access'])) {
        return 0;
    }
    
    $elapsed = time() - $_SESSION['access'];
    $remaining = SESS_EXPIRES - $elapsed;
    
    return max(0, $remaining);
}

/**
 * セッションをリフレッシュ
 */
function refreshSession() {
    $_SESSION['access'] = time();
    
    // 1時間以上経過していたらセッションIDを再生成
    if (!empty($_SESSION['last_regenerate'])) {
        if (time() - $_SESSION['last_regenerate'] > 3600) {
            session_regenerate_id(true);
            $_SESSION['last_regenerate'] = time();
        }
    } else {
        $_SESSION['last_regenerate'] = time();
    }
}

/**
 * AJAXリクエストかどうかを判定
 */
function isAjaxRequest() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * セッション警告のJavaScriptコードを生成
 */
function generateSessionWarningScript() {
    $remaining = getSessionRemainingTime();
    $warning_time = 300; // 5分前に警告
    
    if ($remaining > 0) {
        return <<<SCRIPT
<script>
(function() {
    let sessionTimeout = {$remaining} * 1000;
    let warningTime = {$warning_time} * 1000;
    let warningShown = false;
    
    function showSessionWarning() {
        if (!warningShown && sessionTimeout <= warningTime) {
            warningShown = true;
            if (confirm('セッションがまもなくタイムアウトします。延長しますか？')) {
                // セッション延長のためにAJAXリクエスト
                fetch('/api/refresh_session.php', {
                    method: 'POST',
                    credentials: 'same-origin'
                }).then(response => {
                    if (response.ok) {
                        sessionTimeout = 7200 * 1000; // リセット
                        warningShown = false;
                    }
                });
            }
        }
    }
    
    // 1分ごとにチェック
    setInterval(function() {
        sessionTimeout -= 60000;
        showSessionWarning();
        
        if (sessionTimeout <= 0) {
            alert('セッションがタイムアウトしました。ログインページに移動します。');
            window.location.href = '/login.php?session_expired=1';
        }
    }, 60000);
})();
</script>
SCRIPT;
    }
    
    return '';
}
?>