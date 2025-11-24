<?php
/**
 * OAuth 2.0 Authorization Endpoint
 *
 * RFC 6749準拠の認可エンドポイント
 * PKCE (RFC 7636)対応
 */

require_once(dirname(__DIR__) . '/modern_config.php');

// ログインチェック
if (!checkLogin()) {
    // ログインしていない場合は、ログイン後にこのページに戻る
    $_SESSION['oauth_redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header('Location: /login.php');
    exit;
}

$user_id = $_SESSION['AUTH_USER'];

// パラメータ取得
$client_id = $_GET['client_id'] ?? '';
$redirect_uri = $_GET['redirect_uri'] ?? '';
$response_type = $_GET['response_type'] ?? '';
$scope = $_GET['scope'] ?? '';
$state = $_GET['state'] ?? '';
$code_challenge = $_GET['code_challenge'] ?? '';
$code_challenge_method = $_GET['code_challenge_method'] ?? '';
$resource = $_GET['resource'] ?? ''; // RFC 8707

// resourceパラメータのログ記録（MCP仕様）
if ($resource) {
    error_log("Authorization request with resource parameter: $resource");
}

// バリデーション
if (empty($client_id) || empty($redirect_uri) || $response_type !== 'code') {
    http_response_code(400);
    die('Invalid request');
}

// クライアント情報を確認
$client_sql = "SELECT client_name, redirect_uris FROM b_oauth_clients WHERE client_id = ?";
$client = $g_db->getRow($client_sql, [$client_id], DB_FETCHMODE_ASSOC);

if (DB::isError($client) || !$client) {
    http_response_code(400);
    die('Invalid client_id');
}

// リダイレクトURIを検証
$allowed_uris = explode("\n", trim($client['redirect_uris']));
if (!in_array($redirect_uri, $allowed_uris)) {
    http_response_code(400);
    die('Invalid redirect_uri');
}

// ユーザー同意画面を表示
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // 同意画面のHTML
    ?>
    <!DOCTYPE html>
    <html lang="ja">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>アクセス許可 - ReadNest</title>
        <style>
            body { font-family: system-ui, -apple-system, sans-serif; max-width: 500px; margin: 50px auto; padding: 20px; }
            .card { border: 1px solid #ddd; border-radius: 8px; padding: 30px; }
            h1 { font-size: 24px; margin-bottom: 20px; }
            .client { font-weight: bold; color: #0066cc; }
            .permissions { background: #f5f5f5; padding: 15px; border-radius: 4px; margin: 20px 0; }
            .permissions li { margin: 10px 0; }
            .buttons { display: flex; gap: 10px; margin-top: 20px; }
            button { flex: 1; padding: 12px; border: none; border-radius: 4px; font-size: 16px; cursor: pointer; }
            .allow { background: #0066cc; color: white; }
            .deny { background: #fff; border: 1px solid #ddd; }
        </style>
    </head>
    <body>
        <div class="card">
            <h1>アクセス許可のリクエスト</h1>
            <p><span class="client"><?php echo htmlspecialchars($client['client_name']); ?></span> があなたのReadNestデータへのアクセスを要求しています。</p>

            <div class="permissions">
                <strong>許可される操作:</strong>
                <ul>
                    <li>📚 本棚データの読み取り</li>
                    <li>📊 読書統計の読み取り</li>
                </ul>
            </div>

            <form method="POST">
                <input type="hidden" name="client_id" value="<?php echo htmlspecialchars($client_id); ?>">
                <input type="hidden" name="redirect_uri" value="<?php echo htmlspecialchars($redirect_uri); ?>">
                <input type="hidden" name="response_type" value="<?php echo htmlspecialchars($response_type); ?>">
                <input type="hidden" name="scope" value="<?php echo htmlspecialchars($scope); ?>">
                <input type="hidden" name="state" value="<?php echo htmlspecialchars($state); ?>">
                <input type="hidden" name="code_challenge" value="<?php echo htmlspecialchars($code_challenge); ?>">
                <input type="hidden" name="code_challenge_method" value="<?php echo htmlspecialchars($code_challenge_method); ?>">

                <div class="buttons">
                    <button type="submit" name="action" value="deny" class="deny">拒否</button>
                    <button type="submit" name="action" value="allow" class="allow">許可</button>
                </div>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// POSTリクエスト（ユーザーの同意/拒否）
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action !== 'allow') {
        // 拒否された場合
        $error_params = http_build_query([
            'error' => 'access_denied',
            'error_description' => 'User denied access',
            'state' => $state
        ]);
        header("Location: $redirect_uri?$error_params");
        exit;
    }

    // 認可コードを生成
    $code = bin2hex(random_bytes(32));
    $expires_at = date('Y-m-d H:i:s', time() + 600); // 10分後

    // 認可コードを保存
    $insert_sql = "INSERT INTO b_oauth_authorization_codes
                   (code, client_id, user_id, redirect_uri, scope, code_challenge, code_challenge_method, expires_at)
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    $result = $g_db->query($insert_sql, [
        $code,
        $client_id,
        $user_id,
        $redirect_uri,
        $scope,
        $code_challenge,
        $code_challenge_method,
        $expires_at
    ]);

    if (DB::isError($result)) {
        http_response_code(500);
        die('Server error');
    }

    // リダイレクト
    $params = http_build_query([
        'code' => $code,
        'state' => $state
    ]);

    header("Location: $redirect_uri?$params");
    exit;
}
?>
