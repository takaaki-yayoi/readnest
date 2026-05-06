<?php
/**
 * VAPID鍵ペア生成スクリプト
 *
 * 使い方（本番サーバーで1回だけ実行）:
 *   composer install
 *   php scripts/generate_vapid_keys.php
 *
 * 出力された定数を config.php にそのまま貼り付けてください。
 * 鍵を再生成すると既存の購読は全て無効になるため、運用開始後は再生成しないこと。
 */

if (php_sapi_name() !== 'cli') {
    die('This script can only be run from the command line.');
}

$autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (!file_exists($autoload)) {
    fwrite(STDERR, "Error: vendor/autoload.php not found. Run 'composer install' first.\n");
    exit(1);
}
require_once $autoload;

use Minishlink\WebPush\VAPID;

$keys = VAPID::createVapidKeys();

echo "==== VAPID Keys Generated ====\n\n";
echo "以下を config.php に追記してください（既存の OPENAI_API_KEY 定義の近く）:\n\n";
echo "// Web Push (VAPID) — push通知署名用\n";
echo "define('VAPID_SUBJECT', 'mailto:admin@readnest.jp');\n";
echo "define('VAPID_PUBLIC_KEY', '" . $keys['publicKey'] . "');\n";
echo "define('VAPID_PRIVATE_KEY', '" . $keys['privateKey'] . "');\n\n";
echo "VAPID_PUBLIC_KEY はフロントにも露出するため公開しても問題ない。\n";
echo "VAPID_PRIVATE_KEY は絶対に外部に出さないこと（gitignore済み config.php で管理）。\n";
