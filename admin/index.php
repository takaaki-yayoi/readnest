<?php
/**
 * 管理画面ダッシュボード
 * PHP 8.2.28対応版
 */

declare(strict_types=1);

// 設定を読み込み
require_once(dirname(__DIR__) . '/config.php');
require_once(dirname(__DIR__) . '/library/database.php');

// データベース接続を先に初期化
$g_db = DB_Connect();

require_once(__DIR__ . '/admin_auth.php');
require_once(__DIR__ . '/admin_helpers.php');

// 管理者認証チェック
if (!isAdmin()) {
    http_response_code(403);
    include('403.php');
    exit;
}

$page_title = 'ダッシュボード';

// 統計データを取得
$total_users = safeDbResult($g_db->getOne("SELECT COUNT(*) FROM b_user WHERE regist_date IS NOT NULL"), 0);
$new_users = safeDbResult($g_db->getOne("SELECT COUNT(*) FROM b_user WHERE regist_date >= DATE_FORMAT(NOW(), '%Y-%m-01')"), 0);
$total_books = safeDbResult($g_db->getOne("SELECT COUNT(DISTINCT book_id) FROM b_book_list"), 0);
// event_dateがDATETIME型の場合の処理
$monthly_finished = safeDbResult($g_db->getOne("SELECT COUNT(*) FROM b_book_event WHERE event_date >= DATE_FORMAT(NOW(), '%Y-%m-01') AND event = ?", array(READING_FINISH)), 0);
$pending_contacts = safeDbResult($g_db->getOne("SELECT COUNT(*) FROM b_contact WHERE status = 'new'"), 0);
$recent_contacts = safeDbResult($g_db->getAll("SELECT * FROM b_contact ORDER BY created_at DESC LIMIT 5", null, DB_FETCHMODE_ASSOC), array());
$recent_users = safeDbResult($g_db->getAll("SELECT user_id, nickname, email, regist_date FROM b_user WHERE regist_date IS NOT NULL ORDER BY regist_date DESC LIMIT 5", null, DB_FETCHMODE_ASSOC), array());

include('layout/header.php');
?>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- 統計カード -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 bg-blue-100 rounded-full">
                <i class="fas fa-users text-blue-600 text-2xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-600">総ユーザー数</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars((string)safeNumber($total_users)); ?></p>
                <p class="text-xs text-green-600">+<?php echo htmlspecialchars((string)safeNumber($new_users)); ?> 今月</p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 bg-green-100 rounded-full">
                <i class="fas fa-book text-green-600 text-2xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-600">登録書籍数</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars((string)safeNumber($total_books)); ?></p>
                <p class="text-xs text-gray-500">全ユーザー合計</p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 bg-purple-100 rounded-full">
                <i class="fas fa-book-reader text-purple-600 text-2xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-600">今月の読了数</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars((string)safeNumber($monthly_finished)); ?></p>
                <p class="text-xs text-gray-500">全ユーザー合計</p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 bg-red-100 rounded-full">
                <i class="fas fa-envelope text-red-600 text-2xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-600">未対応問い合わせ</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars((string)safeNumber($pending_contacts)); ?></p>
                <p class="text-xs text-gray-500">要対応</p>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    <!-- 最近の問い合わせ -->
    <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b">
            <h3 class="text-lg font-semibold text-gray-900">最近の問い合わせ</h3>
        </div>
        <div class="p-6">
            <?php if (empty($recent_contacts)): ?>
                <p class="text-gray-500 text-center py-4">問い合わせがありません</p>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($recent_contacts as $contact): ?>
                    <div class="flex items-start space-x-3 pb-4 border-b last:border-0">
                        <div class="flex-shrink-0">
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-gray-200">
                                <i class="fas fa-envelope text-gray-600 text-sm"></i>
                            </span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">
                                <?php echo safeHtml($contact['subject']); ?>
                            </p>
                            <p class="text-sm text-gray-500">
                                <?php echo safeHtml($contact['name']); ?> - 
                                <?php echo safeDate($contact['created_at'], 'm/d H:i'); ?>
                            </p>
                        </div>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium <?php echo $contact['status'] === 'new' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800'; ?>">
                            <?php echo $contact['status'] === 'new' ? '新規' : htmlspecialchars($contact['status']); ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="mt-4 text-center">
                    <a href="/admin/contacts.php" class="text-sm text-admin-primary hover:text-admin-accent">
                        すべて見る <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- 最近の新規ユーザー -->
    <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b">
            <h3 class="text-lg font-semibold text-gray-900">最近の新規ユーザー</h3>
        </div>
        <div class="p-6">
            <?php if (empty($recent_users)): ?>
                <p class="text-gray-500 text-center py-4">新規ユーザーがいません</p>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($recent_users as $user): ?>
                    <div class="flex items-center space-x-3 pb-4 border-b last:border-0">
                        <div class="flex-shrink-0">
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-blue-100">
                                <i class="fas fa-user text-blue-600 text-sm"></i>
                            </span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900">
                                <?php echo safeHtml($user['nickname']); ?>
                            </p>
                            <p class="text-sm text-gray-500">
                                <?php echo safeDate($user['regist_date']); ?>
                            </p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="mt-4 text-center">
                    <a href="/admin/users.php" class="text-sm text-admin-primary hover:text-admin-accent">
                        すべて見る <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- 管理ツールセクション -->
<div class="mt-8">
    <h2 class="text-xl font-semibold text-gray-900 mb-4">管理ツール</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <a href="/admin/check_nickname_cache.php" class="bg-white rounded-lg shadow p-4 hover:shadow-lg transition-shadow">
            <div class="flex items-center">
                <div class="p-2 bg-indigo-100 rounded">
                    <i class="fas fa-id-badge text-indigo-600"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-900">ニックネームキャッシュ管理</p>
                    <p class="text-xs text-gray-500">キャッシュ状態の確認と再構築</p>
                </div>
            </div>
        </a>
        
        <a href="/admin/clear_popular_books_cache.php" class="bg-white rounded-lg shadow p-4 hover:shadow-lg transition-shadow">
            <div class="flex items-center">
                <div class="p-2 bg-green-100 rounded">
                    <i class="fas fa-fire text-green-600"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-900">人気の本キャッシュ管理</p>
                    <p class="text-xs text-gray-500">キャッシュクリアと診断</p>
                </div>
            </div>
        </a>
        
        <a href="/admin/site_maintenance.php" class="bg-white rounded-lg shadow p-4 hover:shadow-lg transition-shadow">
            <div class="flex items-center">
                <div class="p-2 bg-orange-100 rounded">
                    <i class="fas fa-tools text-orange-600"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-900">サイトメンテナンス</p>
                    <p class="text-xs text-gray-500">キャッシュクリアとDBチェック</p>
                </div>
            </div>
        </a>
        
        <a href="/admin/clean_interim_users.php" class="bg-white rounded-lg shadow p-4 hover:shadow-lg transition-shadow">
            <div class="flex items-center">
                <div class="p-2 bg-red-100 rounded">
                    <i class="fas fa-user-clock text-red-600"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-900">仮登録ユーザー管理</p>
                    <p class="text-xs text-gray-500">期限切れユーザーのクリーンアップ</p>
                </div>
            </div>
        </a>
        
        <a href="/admin/level_distribution.php" class="bg-white rounded-lg shadow p-4 hover:shadow-lg transition-shadow">
            <div class="flex items-center">
                <div class="p-2 bg-purple-100 rounded">
                    <i class="fas fa-trophy text-purple-600"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-900">レベル分布</p>
                    <p class="text-xs text-gray-500">ユーザーレベルの統計</p>
                </div>
            </div>
        </a>
        
        <a href="/admin/fix_update_dates.php" class="bg-white rounded-lg shadow p-4 hover:shadow-lg transition-shadow">
            <div class="flex items-center">
                <div class="p-2 bg-red-100 rounded">
                    <i class="fas fa-calendar-check text-red-600"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-900">update_date整合性チェック</p>
                    <p class="text-xs text-gray-500">読書進捗と更新日の不整合を修正</p>
                </div>
            </div>
        </a>
        
        <a href="/admin/fix_invalid_dates.php" class="bg-white rounded-lg shadow p-4 hover:shadow-lg transition-shadow">
            <div class="flex items-center">
                <div class="p-2 bg-orange-100 rounded">
                    <i class="fas fa-calendar-times text-orange-600"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-900">無効な日付の修正</p>
                    <p class="text-xs text-gray-500">-0001-11-30等の無効日付を復旧</p>
                </div>
            </div>
        </a>
        
        <a href="/admin/check_datetime_issues.php" class="bg-white rounded-lg shadow p-4 hover:shadow-lg transition-shadow">
            <div class="flex items-center">
                <div class="p-2 bg-yellow-100 rounded">
                    <i class="fas fa-calendar-exclamation text-yellow-600"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-900">日付型問題チェック</p>
                    <p class="text-xs text-gray-500">DATETIME/DATE型の問題診断</p>
                </div>
            </div>
        </a>
        
        <a href="/admin/apply_performance_indexes.php" class="bg-white rounded-lg shadow p-4 hover:shadow-lg transition-shadow">
            <div class="flex items-center">
                <div class="p-2 bg-yellow-100 rounded">
                    <i class="fas fa-database text-yellow-600"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-900">インデックス管理</p>
                    <p class="text-xs text-gray-500">パフォーマンス改善用インデックス</p>
                </div>
            </div>
        </a>
        
        <a href="/admin/check_index_performance_simple.php" class="bg-white rounded-lg shadow p-4 hover:shadow-lg transition-shadow">
            <div class="flex items-center">
                <div class="p-2 bg-blue-100 rounded">
                    <i class="fas fa-tachometer-alt text-blue-600"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-900">パフォーマンス確認</p>
                    <p class="text-xs text-gray-500">インデックスの効果測定</p>
                </div>
            </div>
        </a>
        
        <a href="/admin/registration_logs.php" class="bg-white rounded-lg shadow p-4 hover:shadow-lg transition-shadow">
            <div class="flex items-center">
                <div class="p-2 bg-blue-100 rounded">
                    <i class="fas fa-clipboard-list text-blue-600"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-900">ユーザー登録ログ</p>
                    <p class="text-xs text-gray-500">登録プロセスの監視</p>
                </div>
            </div>
        </a>
        
        <a href="/admin/bulk_fix_book_images.php" class="bg-white rounded-lg shadow p-4 hover:shadow-lg transition-shadow">
            <div class="flex items-center">
                <div class="p-2 bg-emerald-100 rounded">
                    <i class="fas fa-layer-group text-emerald-600"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-900">書籍画像管理</p>
                    <p class="text-xs text-gray-500">画像の検証・一括更新・ログ管理</p>
                </div>
            </div>
        </a>
        
        <a href="/admin/fix_book_images_simple.php" class="bg-white rounded-lg shadow p-4 hover:shadow-lg transition-shadow">
            <div class="flex items-center">
                <div class="p-2 bg-blue-100 rounded">
                    <i class="fas fa-images text-blue-600"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-900">書籍画像修正ツール</p>
                    <p class="text-xs text-gray-500">画像なし・表示されない本を修正</p>
                </div>
            </div>
        </a>
        
        <a href="/admin/analyze_image_urls.php" class="bg-white rounded-lg shadow p-4 hover:shadow-lg transition-shadow">
            <div class="flex items-center">
                <div class="p-2 bg-purple-100 rounded">
                    <i class="fas fa-chart-bar text-purple-600"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-900">画像URL詳細分析</p>
                    <p class="text-xs text-gray-500">画像URLの状態を詳しく分析</p>
                </div>
            </div>
        </a>
        
        <a href="/admin/manage_uploaded_images.php" class="bg-white rounded-lg shadow p-4 hover:shadow-lg transition-shadow">
            <div class="flex items-center">
                <div class="p-2 bg-indigo-100 rounded">
                    <i class="fas fa-cloud-upload-alt text-indigo-600"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-900">画像管理・統計</p>
                    <p class="text-xs text-gray-500">画像の管理と利用状況統計</p>
                </div>
            </div>
        </a>
        
        <a href="/admin/check_upload_dirs.php" class="bg-white rounded-lg shadow p-4 hover:shadow-lg transition-shadow">
            <div class="flex items-center">
                <div class="p-2 bg-teal-100 rounded">
                    <i class="fas fa-folder-open text-teal-600"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-900">アップロードディレクトリ確認</p>
                    <p class="text-xs text-gray-500">ディレクトリの作成・権限確認</p>
                </div>
            </div>
        </a>
        
        <a href="/admin/image_management.php" class="bg-white rounded-lg shadow p-4 hover:shadow-lg transition-shadow">
            <div class="flex items-center">
                <div class="p-2 bg-indigo-100 rounded">
                    <i class="fas fa-shield-alt text-indigo-600"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-900">画像総合管理</p>
                    <p class="text-xs text-gray-500">有害コンテンツ検知・削除機能付き</p>
                </div>
            </div>
        </a>
    </div>
</div>

<?php include('layout/footer.php'); ?>