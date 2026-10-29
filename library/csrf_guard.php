<?php
/**
 * 全ページ共通のCSRF対策（library/session.php の最後から読み込む）
 *
 * POST等の処理ごとに検証を書くと漏れるため、ここで一括して行う。
 *  1. ログイン中のPOST/PUT/PATCH/DELETEでトークンを確認する
 *  2. HTML出力のPOSTフォームにトークンの隠しフィールドを差し込む
 *  3. <head> に、fetch / XMLHttpRequest / JSで作ったフォームへトークンを付けるスクリプトを差し込む
 *
 * 導入直後は CSRF_ENFORCE=false（記録のみ）。失敗してもリクエストは止めず、
 * dokusho_error_log.txt に「[CSRF] would block ...」を記録する。付け漏れが無いことを
 * 確認してから下の CSRF_ENFORCE の既定値を true にして拒否を有効にする
 * （config.php はgit管理外で変更履歴が残らないため、切り替えはこのファイルで行う）。
 */

if (!defined('CONFIG')) {
    die('reference for this file is not allowed.');
}

require_once(__DIR__ . '/csrf.php');

if (!defined('CSRF_ENFORCE')) {
    define('CSRF_ENFORCE', false);
}

/**
 * 管理画面から見えるReadNestエラーログに1行で記録
 */
function csrfGuardLog(string $message): void {
    $line = '[' . date('Y-m-d H:i:s') . '] [CSRF] ' . $message . PHP_EOL;
    if (strpos(__DIR__, '/Users/') === 0) {
        error_log(rtrim($line));
    } else {
        @error_log($line, 3, '/home/icotfeels/readnest.jp/log/dokusho_error_log.txt');
    }
}

/**
 * 確認の対象外となるリクエストか
 */
function csrfGuardIsExempt(): bool {
    if (PHP_SAPI === 'cli') {
        return true;
    }

    $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    if (in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)) {
        return true;
    }

    // Bearerトークン認証のAPI（MCP、OAuthトークン発行など）はブラウザが自動で認証情報を送らないためCSRFは成立しない
    $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    if (stripos($auth, 'Bearer ') === 0) {
        return true;
    }
    $path = (string)parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    foreach (['/oauth/token.php', '/mcp/'] as $prefix) {
        if (strpos($path, $prefix) === 0) {
            return true;
        }
    }

    // CSRFの被害はログイン中の操作を勝手に行われること。未ログインのPOST（ログイン・登録・お問い合わせ等）は対象外。
    // 自動ログインCookieがあるとこのリクエスト中にログイン状態になるため、ログイン中として扱う
    if (empty($_SESSION['AUTH_USER']) && empty($_COOKIE['AUTOLOGIN'])) {
        return true;
    }

    return false;
}

/**
 * リクエストのトークンを確認する
 * verifyCSRFToken() は期限切れ時にセッションのトークンを消すため、記録のみモードでも状態を変えないよう独自に比較する
 */
function csrfGuardCheck(): void {
    if (csrfGuardIsExempt()) {
        return;
    }

    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    $session_token = $_SESSION['csrf_token'] ?? '';

    if ($token !== '' && $session_token !== '' && is_string($token) && hash_equals($session_token, $token)) {
        return;
    }

    $reason = ($token === '') ? 'token missing' : (($session_token === '') ? 'no session token' : 'token mismatch');
    $path = (string)parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    $referer = (string)parse_url($_SERVER['HTTP_REFERER'] ?? '', PHP_URL_PATH);
    csrfGuardLog(sprintf(
        '%s %s %s (%s) user=%s referer=%s',
        CSRF_ENFORCE ? 'blocked' : 'would block',
        $_SERVER['REQUEST_METHOD'] ?? '',
        $path,
        $reason,
        $_SESSION['AUTH_USER'] ?? 'autologin',
        $referer
    ));

    if (!CSRF_ENFORCE) {
        return;
    }

    http_response_code(403);
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    $is_ajax = (strpos($accept, 'application/json') !== false)
        || (strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest')
        || !empty($_SERVER['HTTP_X_CSRF_TOKEN']);
    if ($is_ajax) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['success' => false, 'error' => 'ページの有効期限が切れました。再読み込みしてからもう一度お試しください。']);
    } else {
        header('Content-Type: text/html; charset=UTF-8');
        echo '<!DOCTYPE html><meta charset="UTF-8"><p>ページの有効期限が切れました。前のページに戻り、再読み込みしてからもう一度お試しください。</p>';
    }
    exit;
}

/**
 * HTML出力にトークンを差し込む（ob_start のコールバック）
 */
function csrfGuardInjectToken(string $buffer): string {
    // HTML以外（JSON・画像など）はそのまま返す
    foreach (headers_list() as $header) {
        if (stripos($header, 'Content-Type:') === 0 && stripos($header, 'text/html') === false) {
            return $buffer;
        }
    }
    $head = ltrim(substr($buffer, 0, 64));
    if ($head === '' || $head[0] === '{' || $head[0] === '[') {
        return $buffer; // Content-Type未指定のJSON
    }
    if (stripos($buffer, '<form') === false && stripos($buffer, '<head') === false) {
        return $buffer;
    }

    $token = $_SESSION['csrf_token'] ?? '';
    if ($token === '') {
        return $buffer;
    }
    $token_attr = htmlspecialchars($token, ENT_QUOTES, 'UTF-8');

    // POSTフォームに隠しフィールドを差し込む（既にある場合も同じ値なので重複して問題ない）
    // 属性値内の「>」（Alpine.jsの @submit="x > 0" 等）でタグを途中で切らないよう、引用符の中は読み飛ばす
    $buffer = preg_replace_callback(
        '/<form\b(?:[^>"\']++|"[^"]*+"|\'[^\']*+\')*+>/i',
        function ($m) use ($token_attr) {
            if (!preg_match('/\bmethod\s*=\s*["\']?post\b/i', $m[0])) {
                return $m[0];
            }
            // 送信先が別サイトのフォームにはトークンを渡さない
            if (preg_match('/\baction\s*=\s*["\']?(?:https?:)?\/\/([^\/"\'\s>]+)/i', $m[0], $a)
                && strcasecmp($a[1], $_SERVER['HTTP_HOST'] ?? '') !== 0) {
                return $m[0];
            }
            return $m[0] . '<input type="hidden" name="csrf_token" value="' . $token_attr . '">';
        },
        $buffer
    );

    // <head> の直後に、JSからの送信へトークンを付けるスクリプトを差し込む
    // （CDNが静的ファイルを長期キャッシュするため外部ファイルにせずインラインにする）
    $script = '<script>window.READNEST_CSRF_TOKEN=window.READNEST_CSRF_TOKEN||'
        . json_encode($token, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_APOS) . ';'
        . file_get_contents(__DIR__ . '/csrf_guard_client.js')
        . '</script>';
    $buffer = preg_replace('/<head\b[^>]*>/i', '$0' . str_replace(['\\', '$'], ['\\\\', '\\$'], $script), $buffer, 1);

    return $buffer;
}

if (PHP_SAPI !== 'cli') {
    // トークンを発行（セッションに無ければ作る）
    generateCSRFToken();
    csrfGuardCheck();

    // SSE等のストリーミング応答はバッファリングすると届かなくなるため対象外
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    if (strpos($accept, 'text/event-stream') === false && strpos((string)($_SERVER['REQUEST_URI'] ?? ''), '/mcp/') !== 0) {
        ob_start('csrfGuardInjectToken');
    }
}
