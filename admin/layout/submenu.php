<?php
/**
 * 管理画面サブメニュー
 * PHP 8.2.28対応版
 */

// 現在のページ
$current_page = basename($_SERVER['SCRIPT_NAME'], '.php');

// サブメニューの定義
$submenus = [
    'cache' => [
        'title' => 'キャッシュ管理',
        'items' => [
            'cache_clear' => ['label' => 'キャッシュクリア', 'icon' => 'fas fa-trash'],
            'cache_diagnostics' => ['label' => 'キャッシュ診断', 'icon' => 'fas fa-stethoscope'],
            'cache_inspector' => ['label' => 'キャッシュインスペクター', 'icon' => 'fas fa-search'],
            'clear_popular_books_cache' => ['label' => '人気の本キャッシュ', 'icon' => 'fas fa-book'],
            'clear_review_cache' => ['label' => 'レビューキャッシュ', 'icon' => 'fas fa-comment'],
        ]
    ],
    'database' => [
        'title' => 'データベース管理',
        'items' => [
            'optimize_database' => ['label' => 'DB最適化', 'icon' => 'fas fa-tachometer-alt'],
            'check_tables' => ['label' => 'テーブル確認', 'icon' => 'fas fa-table'],
            'check_datetime_issues' => ['label' => '日付型問題チェック', 'icon' => 'fas fa-calendar-exclamation'],
            'fix_regist_date_null' => ['label' => 'regist_date修正', 'icon' => 'fas fa-wrench'],
            'datetime_migration' => ['label' => '日時マイグレーション', 'icon' => 'fas fa-calendar'],
        ]
    ],
    'x_api' => [
        'title' => 'X (Twitter) 連携',
        'items' => [
            'x_integration' => ['label' => 'X連携設定', 'icon' => 'fab fa-x-twitter'],
            'x_api_debug' => ['label' => 'APIデバッグ', 'icon' => 'fas fa-bug'],
            'test_x_post' => ['label' => 'テスト投稿', 'icon' => 'fas fa-paper-plane'],
        ]
    ],
    'users' => [
        'title' => 'ユーザー管理',
        'items' => [
            'users' => ['label' => 'ユーザー一覧', 'icon' => 'fas fa-users'],
            'registration_logs' => ['label' => '登録ログ', 'icon' => 'fas fa-clipboard-list'],
            'clean_interim_users' => ['label' => '仮登録ユーザー管理', 'icon' => 'fas fa-user-clock'],
            'fix_deleted_users' => ['label' => '削除済みユーザー修正', 'icon' => 'fas fa-user-slash'],
            'fix_empty_nicknames' => ['label' => '空ニックネーム修正', 'icon' => 'fas fa-user-edit'],
            'check_null_nicknames' => ['label' => 'NULLニックネーム確認', 'icon' => 'fas fa-user-question'],
        ]
    ],
    'cron' => [
        'title' => 'Cron管理',
        'items' => [
            'cron_management' => ['label' => 'Cronジョブ一覧', 'icon' => 'fas fa-clock'],
            'cron_status' => ['label' => 'Cron実行ログ', 'icon' => 'fas fa-history'],
        ]
    ],
    'ai_content' => [
        'title' => 'AI・コンテンツ管理',
        'items' => [
            'book_processing' => ['label' => '統合書籍処理', 'icon' => 'fas fa-cogs'],
            'update_descriptions' => ['label' => '説明文更新（個別）', 'icon' => 'fas fa-book-medical'],
            'embeddings' => ['label' => 'エンベディング（個別）', 'icon' => 'fas fa-vector-square'],
            'embedding_progress' => ['label' => 'エンベディング進捗', 'icon' => 'fas fa-tasks'],
            'embedding_debug_enhanced' => ['label' => 'エンベディングデバッグ', 'icon' => 'fas fa-bug'],
        ]
    ],
];

// 現在のページが属するサブメニューを特定
$current_submenu = null;
foreach ($submenus as $key => $submenu) {
    if (isset($submenu['items'][$current_page])) {
        $current_submenu = $key;
        break;
    }
}

// サブメニューを表示
if ($current_submenu && isset($submenus[$current_submenu])):
?>
<div class="bg-white rounded-lg shadow mb-6 p-4">
    <h3 class="text-lg font-semibold text-gray-700 mb-3">
        <?php echo htmlspecialchars($submenus[$current_submenu]['title']); ?>
    </h3>
    <div class="flex flex-wrap gap-2">
        <?php foreach ($submenus[$current_submenu]['items'] as $page => $item): ?>
            <a href="/admin/<?php echo $page; ?>.php" 
               class="<?php echo $current_page === $page ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?> px-3 py-2 rounded-md text-sm font-medium transition-colors flex items-center gap-2">
                <i class="<?php echo $item['icon']; ?> text-xs"></i>
                <?php echo htmlspecialchars($item['label']); ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>