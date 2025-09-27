<?php
/**
 * 管理画面ユーティリティメニュー
 * 一度だけ実行するツールやデバッグツール用
 */

// 現在のページ
$current_page = basename($_SERVER['SCRIPT_NAME'], '.php');

// ユーティリティツールの定義
$utility_tools = [
    'migration' => [
        'title' => 'マイグレーションツール',
        'icon' => 'fas fa-database',
        'items' => [
            'datetime_migration' => '日時フィールドマイグレーション',
            'migrate_user_status' => 'ユーザーステータス追加',
            'add_google_auth_table' => 'Google認証テーブル追加',
            'add_reading_analysis_table' => '読書分析テーブル追加',
            'apply_x_oauth_migration' => 'X OAuth設定追加',
            'add_performance_indexes' => 'パフォーマンスインデックス追加',
            'add_first_login_flag' => '初回ログインフラグ追加',
        ]
    ],
    'debug' => [
        'title' => 'デバッグツール',
        'icon' => 'fas fa-bug',
        'items' => [
            'debug_nickname_issue' => 'ニックネーム問題デバッグ',
            'check_nickname_cache' => 'ニックネームキャッシュ確認',
            'check_popular_books' => '人気の本確認',
            'check_null_nicknames' => 'NULLニックネーム確認',
            'check_mail_config' => 'メール設定確認',
            'x_api_debug' => 'X APIデバッグ',
            'test_x_post' => 'Xテスト投稿',
        ]
    ],
    'fix' => [
        'title' => '修正ツール',
        'icon' => 'fas fa-wrench',
        'items' => [
            'fix_deleted_users' => '削除済みユーザー修正',
            'fix_empty_nicknames' => '空ニックネーム修正',
            'fix_activities_cache' => '活動キャッシュ修正',
            'force_clear_activities_cache' => '活動キャッシュ強制クリア',
            'update_popular_books' => '人気の本更新',
            'fix_book_repository' => 'b_book_repository修復',
        ]
    ],
];

// 現在のページがユーティリティツールに含まれるか確認
$is_utility_page = false;
foreach ($utility_tools as $category) {
    if (array_key_exists($current_page, $category['items'])) {
        $is_utility_page = true;
        break;
    }
}

// ユーティリティメニューを表示（該当ページの場合のみ）
if ($is_utility_page):
?>
<div class="bg-yellow-50 border border-yellow-200 rounded-lg shadow mb-6 p-4">
    <div class="flex items-center mb-3">
        <i class="fas fa-tools text-yellow-600 mr-2"></i>
        <h3 class="text-lg font-semibold text-gray-700">管理ツール</h3>
    </div>
    
    <?php foreach ($utility_tools as $key => $category): ?>
        <div class="mb-4">
            <h4 class="text-sm font-medium text-gray-600 mb-2 flex items-center">
                <i class="<?php echo $category['icon']; ?> mr-1 text-xs"></i>
                <?php echo htmlspecialchars($category['title']); ?>
            </h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                <?php foreach ($category['items'] as $page => $label): ?>
                    <a href="/admin/<?php echo $page; ?>.php" 
                       class="<?php echo $current_page === $page ? 'bg-yellow-600 text-white' : 'bg-white text-gray-700 hover:bg-yellow-100'; ?> px-3 py-2 rounded text-sm transition-colors flex items-center">
                        <?php if ($current_page === $page): ?>
                            <i class="fas fa-chevron-right mr-2 text-xs"></i>
                        <?php endif; ?>
                        <?php echo htmlspecialchars($label); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
    
    <div class="mt-4 pt-3 border-t border-yellow-200">
        <p class="text-xs text-yellow-700">
            <i class="fas fa-exclamation-triangle mr-1"></i>
            これらのツールは管理者専用です。実行前に必ずバックアップを取得してください。
        </p>
    </div>
</div>
<?php endif; ?>