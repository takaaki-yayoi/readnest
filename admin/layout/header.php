<?php
/**
 * 管理画面ヘッダー
 * PHP 8.2.28対応版
 */

// 管理者認証を要求
require_once(dirname(__DIR__) . '/admin_auth.php');

// users.phpなど個別のファイルで既に認証チェックしている場合はスキップ
if (!defined('SKIP_HEADER_AUTH_CHECK')) {
    requireAdmin();
}

$admin_info = getAdminInfo();
$current_page = basename($_SERVER['SCRIPT_NAME'], '.php');
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title ?? 'ReadNest Admin'); ?></title>
    
    <!-- Google Tag Manager -->
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','GTM-N6MRQPH9');</script>
    <!-- End Google Tag Manager -->
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .bg-admin-primary { background-color: #1a1a2e; }
        .text-admin-primary { color: #1a1a2e; }
        .bg-admin-secondary { background-color: #16213e; }
        .text-admin-accent { color: #e94560; }
        .bg-admin-accent { background-color: #e94560; }
        .hover\:bg-admin-accent:hover { background-color: #e94560; }
        .sidebar-collapsed { width: 4rem !important; }
        .sidebar-collapsed .sidebar-text { display: none; }
        .sidebar-collapsed .sidebar-icon { margin: 0 auto; }
        .sidebar-collapsed nav a { justify-content: center; }
        .sidebar-collapsed .sidebar-header { display: none; }
        .sidebar-collapsed .sidebar-footer { display: none; }
        .transition-width { transition: width 0.3s ease; }
        
        /* ツールチップ */
        .sidebar-collapsed nav a {
            position: relative;
        }
        .sidebar-collapsed nav a:hover::after {
            content: attr(data-tooltip);
            position: absolute;
            left: 100%;
            top: 50%;
            transform: translateY(-50%);
            margin-left: 0.5rem;
            background-color: #1a1a2e;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            white-space: nowrap;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            z-index: 20;
            font-size: 0.875rem;
        }
        .sidebar-collapsed nav a:hover::before {
            content: '';
            position: absolute;
            left: 100%;
            top: 50%;
            transform: translateY(-50%);
            border: 6px solid transparent;
            border-right-color: #1a1a2e;
            margin-left: -5px;
            z-index: 20;
        }
    </style>
    <script>
        // サイドバーの表示状態を管理
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const toggleBtn = document.getElementById('sidebar-toggle');
            const mainContent = document.getElementById('main-content');
            
            // localStorageから状態を復元
            const isCollapsed = localStorage.getItem('admin-sidebar-collapsed') === 'true';
            if (isCollapsed) {
                sidebar.classList.add('sidebar-collapsed');
                mainContent.classList.add('ml-16');
                mainContent.classList.remove('ml-64');
            }
            
            toggleBtn.addEventListener('click', function() {
                sidebar.classList.toggle('sidebar-collapsed');
                const collapsed = sidebar.classList.contains('sidebar-collapsed');
                
                if (collapsed) {
                    mainContent.classList.add('ml-16');
                    mainContent.classList.remove('ml-64');
                } else {
                    mainContent.classList.remove('ml-16');
                    mainContent.classList.add('ml-64');
                }
                
                // 状態を保存
                localStorage.setItem('admin-sidebar-collapsed', collapsed);
            });
        });
    </script>
</head>
<body class="bg-gray-100">
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-N6MRQPH9"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->
    <div class="flex h-screen relative">
        <!-- サイドバー -->
        <aside id="sidebar" class="fixed h-full w-64 bg-admin-primary text-white transition-width z-10">
            <div class="p-6 sidebar-header">
                <div class="flex items-center space-x-3">
                    <i class="fas fa-shield-alt text-3xl sidebar-icon"></i>
                    <div class="sidebar-text">
                        <h1 class="text-xl font-bold">ReadNest Admin</h1>
                        <p class="text-xs text-gray-400">管理画面</p>
                    </div>
                </div>
            </div>
            
            <nav class="px-4">
                <a href="/admin/" 
                   class="flex items-center space-x-3 px-4 py-3 rounded-lg mb-2 <?php echo $current_page === 'index' ? 'bg-admin-accent' : 'hover:bg-admin-secondary'; ?> transition-colors"
                   data-tooltip="ダッシュボード">
                    <i class="fas fa-tachometer-alt w-5 sidebar-icon"></i>
                    <span class="sidebar-text">ダッシュボード</span>
                </a>
                
                <a href="/admin/announcements.php" 
                   class="flex items-center space-x-3 px-4 py-3 rounded-lg mb-2 <?php echo $current_page === 'announcements' ? 'bg-admin-accent' : 'hover:bg-admin-secondary'; ?> transition-colors"
                   data-tooltip="お知らせ管理">
                    <i class="fas fa-bullhorn w-5 sidebar-icon"></i>
                    <span class="sidebar-text">お知らせ管理</span>
                </a>
                
                <a href="/admin/contacts.php" 
                   class="flex items-center space-x-3 px-4 py-3 rounded-lg mb-2 <?php echo $current_page === 'contacts' ? 'bg-admin-accent' : 'hover:bg-admin-secondary'; ?> transition-colors"
                   data-tooltip="問い合わせ管理">
                    <i class="fas fa-envelope w-5 sidebar-icon"></i>
                    <span class="sidebar-text">問い合わせ管理</span>
                </a>
                
                <a href="/admin/statistics.php" 
                   class="flex items-center space-x-3 px-4 py-3 rounded-lg mb-2 <?php echo $current_page === 'statistics' ? 'bg-admin-accent' : 'hover:bg-admin-secondary'; ?> transition-colors"
                   data-tooltip="統計情報">
                    <i class="fas fa-chart-bar w-5 sidebar-icon"></i>
                    <span class="sidebar-text">統計情報</span>
                </a>
                
                <a href="/admin/level_distribution.php" 
                   class="flex items-center space-x-3 px-4 py-3 rounded-lg mb-2 <?php echo $current_page === 'level_distribution' ? 'bg-admin-accent' : 'hover:bg-admin-secondary'; ?> transition-colors"
                   data-tooltip="レベル分布">
                    <i class="fas fa-trophy w-5 sidebar-icon"></i>
                    <span class="sidebar-text">レベル分布</span>
                </a>
                
                <a href="/admin/users.php" 
                   class="flex items-center space-x-3 px-4 py-3 rounded-lg mb-2 <?php echo $current_page === 'users' ? 'bg-admin-accent' : 'hover:bg-admin-secondary'; ?> transition-colors"
                   data-tooltip="ユーザー管理">
                    <i class="fas fa-users w-5 sidebar-icon"></i>
                    <span class="sidebar-text">ユーザー管理</span>
                </a>
                
                <a href="/admin/cache_clear.php" 
                   class="flex items-center space-x-3 px-4 py-3 rounded-lg mb-2 <?php echo in_array($current_page, ['cache_clear', 'cache_diagnostics', 'clear_popular_books_cache', 'clear_review_cache']) ? 'bg-admin-accent' : 'hover:bg-admin-secondary'; ?> transition-colors"
                   data-tooltip="キャッシュ管理">
                    <i class="fas fa-sync-alt w-5 sidebar-icon"></i>
                    <span class="sidebar-text">キャッシュ管理</span>
                </a>
                
                <a href="/admin/cron_management.php" 
                   class="flex items-center space-x-3 px-4 py-3 rounded-lg mb-2 <?php echo in_array($current_page, ['cron_management', 'cron_status']) ? 'bg-admin-accent' : 'hover:bg-admin-secondary'; ?> transition-colors"
                   data-tooltip="Cron管理">
                    <i class="fas fa-clock w-5 sidebar-icon"></i>
                    <span class="sidebar-text">Cron管理</span>
                </a>
                
                <a href="/admin/optimize_database.php" 
                   class="flex items-center space-x-3 px-4 py-3 rounded-lg mb-2 <?php echo in_array($current_page, ['optimize_database', 'check_tables', 'datetime_migration', 'migrate_user_status']) ? 'bg-admin-accent' : 'hover:bg-admin-secondary'; ?> transition-colors"
                   data-tooltip="DB最適化">
                    <i class="fas fa-database w-5 sidebar-icon"></i>
                    <span class="sidebar-text">DB最適化</span>
                </a>
                
                
                <a href="/admin/site_settings.php" 
                   class="flex items-center space-x-3 px-4 py-3 rounded-lg mb-2 <?php echo $current_page === 'site_settings' ? 'bg-admin-accent' : 'hover:bg-admin-secondary'; ?> transition-colors"
                   data-tooltip="サイト設定">
                    <i class="fas fa-cog w-5 sidebar-icon"></i>
                    <span class="sidebar-text">サイト設定</span>
                </a>
                
                <a href="/admin/x_integration.php" 
                   class="flex items-center space-x-3 px-4 py-3 rounded-lg mb-2 <?php echo in_array($current_page, ['x_integration', 'x_api_debug', 'test_x_post']) ? 'bg-admin-accent' : 'hover:bg-admin-secondary'; ?> transition-colors"
                   data-tooltip="X連携管理">
                    <i class="fab fa-x-twitter w-5 sidebar-icon"></i>
                    <span class="sidebar-text">X連携管理</span>
                </a>
                
                <a href="/admin/book_processing.php" 
                   class="flex items-center space-x-3 px-4 py-3 rounded-lg mb-2 <?php echo in_array($current_page, ['book_processing', 'update_descriptions', 'embeddings', 'embedding_progress', 'embedding_debug_enhanced']) ? 'bg-admin-accent' : 'hover:bg-admin-secondary'; ?> transition-colors"
                   data-tooltip="AI・コンテンツ">
                    <i class="fas fa-robot w-5 sidebar-icon"></i>
                    <span class="sidebar-text">AI・コンテンツ</span>
                </a>
                
                <a href="/admin/logs.php" 
                   class="flex items-center space-x-3 px-4 py-3 rounded-lg mb-2 <?php echo $current_page === 'logs' ? 'bg-admin-accent' : 'hover:bg-admin-secondary'; ?> transition-colors"
                   data-tooltip="ログ管理">
                    <i class="fas fa-file-alt w-5 sidebar-icon"></i>
                    <span class="sidebar-text">ログ管理</span>
                </a>
                
                <a href="/admin/manage_uploaded_images.php" 
                   class="flex items-center space-x-3 px-4 py-3 rounded-lg mb-2 <?php echo $current_page === 'manage_uploaded_images' ? 'bg-admin-accent' : 'hover:bg-admin-secondary'; ?> transition-colors"
                   data-tooltip="画像管理・統計">
                    <i class="fas fa-images w-5 sidebar-icon"></i>
                    <span class="sidebar-text">画像管理・統計</span>
                </a>
                
                <div class="border-t border-gray-700 my-4"></div>
                
                <a href="/" 
                   class="flex items-center space-x-3 px-4 py-3 rounded-lg mb-2 hover:bg-admin-secondary transition-colors"
                   data-tooltip="サイトを表示">
                    <i class="fas fa-home w-5 sidebar-icon"></i>
                    <span class="sidebar-text">サイトを表示</span>
                </a>
                
                <a href="/clearsessions.php" 
                   class="flex items-center space-x-3 px-4 py-3 rounded-lg mb-2 hover:bg-admin-secondary transition-colors"
                   data-tooltip="ログアウト">
                    <i class="fas fa-sign-out-alt w-5 sidebar-icon"></i>
                    <span class="sidebar-text">ログアウト</span>
                </a>
            </nav>
            
            <div class="absolute bottom-0 w-full p-6 sidebar-footer">
                <div class="text-sm sidebar-text">
                    <p class="text-gray-400">ログイン中:</p>
                    <p class="font-medium"><?php echo htmlspecialchars($admin_info['nickname'] ?? 'Admin'); ?></p>
                    <p class="text-xs text-gray-500"><?php echo htmlspecialchars($admin_info['email'] ?? ''); ?></p>
                </div>
            </div>
        </aside>
        
        <!-- メインコンテンツエリア -->
        <main id="main-content" class="flex-1 ml-64 overflow-y-auto transition-all duration-300">
            <!-- ヘッダーバー -->
            <header class="bg-white shadow-sm">
                <div class="px-8 py-4 flex items-center justify-between">
                    <div class="flex items-center">
                        <button id="sidebar-toggle" class="text-gray-600 hover:text-gray-900 focus:outline-none mr-4">
                            <i class="fas fa-bars text-xl"></i>
                        </button>
                        <h2 class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($page_title ?? 'ダッシュボード'); ?></h2>
                    </div>
                </div>
            </header>
            
            <!-- コンテンツ -->
            <div class="p-8"><?php 
// ここから各ページのコンテンツが入る
?>