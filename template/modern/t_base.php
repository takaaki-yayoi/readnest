<?php
if(!defined('CONFIG')) {
  error_log('direct access detected. file:' . __FILE__ . ' line:' . __LINE__ . ' ip:' . $_SERVER['REMOTE_ADDR'] . ' agent:' . $_SERVER['HTTP_USER_AGENT']);
  die('reference for this file is not allowed.');
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
    
    <!-- キャッシュ制御（スマートフォン対策） -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    
    <!-- Google Tag Manager -->
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','GTM-N6MRQPH9');</script>
    <!-- End Google Tag Manager -->
    
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-5ZF3NGQ4QT"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());

      gtag('config', 'G-5ZF3NGQ4QT');
    </script>
    
    <title><?php echo html(isset($d_site_title) ? $d_site_title : 'ReadNest - あなたの読書の巣'); ?></title>
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="<?php echo html(isset($g_meta_description) ? $g_meta_description : 'ReadNest - 読書の進捉を記録し、レビューを書き、本を整理するための居心地のよい空間です。'); ?>">
    <meta name="keywords" content="<?php echo html(isset($g_meta_keyword) ? $g_meta_keyword : '読書,本,書評,レビュー,本棚,読書記録'); ?>">

    <!-- CSRF Token -->
    <?php
    if (isset($_SESSION['AUTH_USER'])) {
        // csrf.phpを読み込み
        if (!function_exists('generateCSRFToken')) {
            require_once(dirname(dirname(dirname(__FILE__))) . '/library/csrf.php');
        }
        $csrf_token = generateCSRFToken();
        echo '<meta name="csrf-token" content="' . htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') . '">';
    }
    ?>
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://readnest.jp<?php echo html($_SERVER['REQUEST_URI']); ?>">
    <meta property="og:title" content="<?php echo html(isset($d_site_title) ? $d_site_title : 'ReadNest'); ?>">
    <meta property="og:description" content="<?php echo html(isset($g_meta_description) ? $g_meta_description : 'ReadNest - あなたの読書の巣。読書進捉の記録、レビュー、本棚整理ができます。'); ?>">
    <meta property="og:image" content="<?php echo html(isset($g_og_image) ? $g_og_image : 'https://readnest.jp/img/og-image.jpg?v=20250119'); ?>">
    <meta property="og:site_name" content="ReadNest">
    
    <!-- Twitter Card / X -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:site" content="@dokusho">
    <meta name="twitter:title" content="<?php echo html(isset($d_site_title) ? $d_site_title : 'ReadNest'); ?>">
    <meta name="twitter:description" content="<?php echo html(isset($g_meta_description) ? $g_meta_description : 'ReadNest - あなたの読書の巣。読書進捉の記録、レビュー、本棚整理ができます。'); ?>">
    <meta name="twitter:image" content="<?php echo html(isset($g_og_image) ? $g_og_image : 'https://readnest.jp/img/og-image.jpg?v=20250119'); ?>">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png?v=<?php echo date('Ymd'); ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png?v=<?php echo date('Ymd'); ?>">
    <link rel="shortcut icon" href="/favicon.ico?v=<?php echo date('Ymd'); ?>">
    
    <!-- iOS Safari用の設定 - apple-touch-iconを削除してサイトプレビューでの大きな表示を防ぐ -->
    <!-- apple-touch-iconは意図的に設定しない（Safariのプレビューで大きく表示される問題を回避） -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="ReadNest">
    
    <!-- テーマカラー -->
    <meta name="theme-color" content="#1a4d3e">
    
    <!-- ダークモード初期化スクリプト（フラッシュ防止） -->
    <script>
        // ローカルストレージまたはシステム設定からテーマを取得
        (function() {
            <?php if (isset($_SESSION['AUTH_USER'])): ?>
            const theme = localStorage.getItem('theme') ||
                         (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            if (theme === 'dark') {
                document.documentElement.classList.add('dark');
            }
            <?php else: ?>
            // ログアウト時はダークモードを無効化
            document.documentElement.classList.remove('dark');
            localStorage.removeItem('theme');
            <?php endif; ?>
        })();
    </script>
    
    <!-- Tailwind CSS (CDN for development, replace with compiled CSS in production) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/template/modern/css/modern-styles.css">
    <script>
        tailwind.config = {
            darkMode: 'class', // クラスベースのダークモード
            theme: {
                extend: {
                    screens: {
                        'xs': '475px',
                        // Tailwindのデフォルト: sm: 640px, md: 768px, lg: 1024px, xl: 1280px, 2xl: 1536px
                        'tablet': '768px',  // タブレット縦向き
                        'tablet-lg': '1024px', // タブレット横向き
                        'landscape': { 'raw': '(orientation: landscape) and (max-height: 500px)' }, // スマホ横向き
                        'tall': { 'raw': '(min-height: 800px)' }, // 縦長画面
                    },
                    colors: {
                        'readnest-primary': '#1a4d3e',
                        'readnest-beige': '#f5f1e8',
                        'readnest-accent': '#38a182',
                        'book-primary': {
                            50: '#f0fdf4',
                            100: '#dcfce7',
                            200: '#bbf7d0',
                            300: '#86efac',
                            400: '#4ade80',
                            500: '#22c55e',
                            600: '#16a34a',
                            700: '#15803d',
                            800: '#166534',
                            900: '#14532d',
                        },
                        'book-secondary': {
                            50: '#fef3c7',
                            100: '#fee8a0',
                            200: '#fdd458',
                            300: '#fcbf24',
                            400: '#f59e0b',
                            500: '#d97706',
                            600: '#b45309',
                            700: '#92400e',
                            800: '#713f12',
                            900: '#5a3517',
                        },
                        'book-accent': '#901808',
                    }
                }
            }
        }
    </script>
    
    <!-- Custom CSS -->
    <link href="/css/modern.css" rel="stylesheet">
    
    <!-- AI Assistant CSS -->
    <?php if (isset($_SESSION['AUTH_USER'])): ?>
    <link href="/css/ai_assistant.css?v=<?php echo time(); ?>" rel="stylesheet">
    <?php endif; ?>
    
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Alpine.js x-cloak support and Dark Mode Styles -->
    <style>
        [x-cloak] { display: none !important; }
        
        /* Dark mode custom styles */
        .dark {
            color-scheme: dark;
        }
        
        /* ダークモードでの読みやすさ向上 */
        .dark .prose {
            color: #e5e7eb;
        }
        
        .dark .prose h1,
        .dark .prose h2,
        .dark .prose h3,
        .dark .prose h4 {
            color: #f3f4f6;
        }
        
        /* フォーム要素のダークモード対応 */
        .dark input[type="text"],
        .dark input[type="email"],
        .dark input[type="password"],
        .dark input[type="search"],
        .dark input[type="number"],
        .dark textarea,
        .dark select {
            background-color: #374151;
            border-color: #4b5563;
            color: #f3f4f6;
        }
        
        .dark input[type="text"]:focus,
        .dark input[type="email"]:focus,
        .dark input[type="password"]:focus,
        .dark input[type="search"]:focus,
        .dark input[type="number"]:focus,
        .dark textarea:focus,
        .dark select:focus {
            border-color: #60a5fa;
            background-color: #1f2937;
        }
        
        /* ボタンのダークモード対応 */
        .dark .btn-primary {
            background-color: #1e40af;
        }
        
        .dark .btn-primary:hover {
            background-color: #1e3a8a;
        }
        
        /* スクロールバーのダークモード対応 */
        .dark ::-webkit-scrollbar {
            background-color: #1f2937;
        }
        
        .dark ::-webkit-scrollbar-thumb {
            background-color: #4b5563;
        }
        
        .dark ::-webkit-scrollbar-thumb:hover {
            background-color: #6b7280;
        }
    </style>
    
    <!-- Alpine.js for lightweight interactivity -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- Chart.js for modern graphs -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <!-- Moment.js and Chart.js date adapter for time scale -->
    <script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-moment@1.0.1/dist/chartjs-adapter-moment.min.js"></script>
    
    
    <?php if (isset($d_additional_head)) echo $d_additional_head; ?>
</head>
<body class="bg-readnest-beige dark:bg-gray-900 text-gray-900 dark:text-gray-100 min-h-screen flex flex-col transition-colors" x-data="{ mobileMenuOpen: false, userMenuOpen: false }"<?php 
    // 登録完了ページでのみチュートリアル自動起動フラグを設定
    if (isset($GLOBALS['activation_success']) && $GLOBALS['activation_success'] === true) {
        echo ' data-activation-success="true"';
    }
?>>
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-N6MRQPH9"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->
    
    <!-- Page Loader -->
    <div id="page-loader" class="page-loader hidden">
        <div class="spinner"></div>
    </div>
    
    <!-- Header -->
    <header class="bg-white dark:bg-gray-800 shadow-sm sticky top-0 z-40 border-b border-gray-100 dark:border-gray-700 transition-colors">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-14 relative">
                <!-- Logo and Mobile Menu Button -->
                <div class="flex items-center">
                    <!-- Mobile/Tablet menu button -->
                    <button type="button" 
                            @click="mobileMenuOpen = !mobileMenuOpen"
                            class="lg:hidden inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-book-primary-500">
                        <span class="sr-only">メニューを開く</span>
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    
                    <!-- Logo -->
                    <a href="/" class="flex items-center ml-4 sm:ml-0">
                        <img src="/template/modern/img/readnest_logo.png" alt="ReadNest" class="h-8 w-auto">
                        <span class="ml-2 text-xl font-semibold text-readnest-primary dark:text-white hidden sm:block">ReadNest</span>
                    </a>
                </div>
                
                <!-- Desktop Navigation -->
                <?php 
                // ナビゲーションヘルパーを読み込み
                require_once(dirname(dirname(dirname(__FILE__))) . '/library/navigation_helper.php');
                
                // 読書中の本の数を取得
                $reading_count = 0;
                if (isset($_SESSION['AUTH_USER'])) {
                    $reading_count = getReadingCount($_SESSION['AUTH_USER']);
                }
                ?>
                <!-- iPad用コンパクトナビゲーション（768px-1024px） -->
                <nav class="hidden md:flex lg:hidden items-center space-x-1">
                    <a href="/" class="<?php echo getNavClass('/'); ?> px-2 py-2 text-sm" title="ホーム">
                        <i class="fas fa-home text-lg"></i>
                    </a>
                    <?php if (isset($_SESSION['AUTH_USER'])): ?>
                    <a href="/bookshelf.php" class="<?php echo getNavClass('/bookshelf.php'); ?> px-2 py-2 text-sm relative" title="本棚">
                        <i class="fas fa-book-open text-lg"></i>
                        <?php if ($reading_count > 0): ?>
                        <span class="absolute -top-1 -right-1 bg-blue-500 text-white text-xs w-4 h-4 rounded-full flex items-center justify-center">
                            <?php echo $reading_count; ?>
                        </span>
                        <?php endif; ?>
                    </a>
                    <a href="/recommendations.php" class="<?php echo getNavClass('/recommendations.php'); ?> px-2 py-2 text-sm relative" title="AI推薦">
                        <i class="fas fa-magic text-lg"></i>
                        <span class="absolute -top-1 -right-1 bg-gradient-to-r from-purple-600 to-pink-600 text-white text-[8px] px-1 py-0.5 rounded-full font-bold">New</span>
                    </a>
                    <a href="/favorites.php" class="<?php echo getNavClass('/favorites.php'); ?> px-2 py-2 text-sm" title="お気に入り">
                        <i class="fas fa-star text-lg"></i>
                    </a>
                    <a href="/add_book.php" class="<?php echo getNavClass('/add_book.php'); ?> px-2 py-2 text-sm" title="本を追加">
                        <i class="fas fa-plus-circle text-lg"></i>
                    </a>
                    <a href="/reading_assistant.php" class="<?php echo getNavClass('/reading_assistant.php'); ?> px-2 py-2 text-sm relative" title="アシスタント">
                        <i class="fas fa-robot text-lg"></i>
                        <span class="absolute -top-1 -right-1 bg-gradient-to-r from-green-600 to-blue-600 text-white text-[8px] px-1 py-0.5 rounded-full font-bold">AI</span>
                    </a>
                    <?php endif; ?>
                    <!-- 発見メニュー -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" 
                                @click.away="open = false"
                                class="<?php echo (strpos($_SERVER['REQUEST_URI'], 'popular') !== false || strpos($_SERVER['REQUEST_URI'], 'ranking') !== false || strpos($_SERVER['REQUEST_URI'], 'review') !== false) ? 'text-readnest-primary dark:text-white' : 'text-gray-700 dark:text-gray-300 hover:text-readnest-primary dark:hover:text-white'; ?> px-2 py-2 text-sm" title="発見">
                            <i class="fas fa-compass text-lg"></i>
                        </button>
                        <div x-show="open"
                             x-cloak
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="transform opacity-0 scale-95"
                             x-transition:enter-end="transform opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="transform opacity-100 scale-100"
                             x-transition:leave-end="transform opacity-0 scale-95"
                             class="absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-50">
                            <div class="py-1">
                                <a href="/popular_books.php" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                    <i class="fas fa-fire mr-2"></i>人気の本
                                </a>
                                <a href="/ranking.php" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                    <i class="fas fa-trophy mr-2"></i>ランキング
                                </a>
                                <a href="/recent_reviews.php" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                    <i class="fas fa-comments mr-2"></i>最新レビュー
                                </a>
                                <a href="/reviews.php" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                    <i class="fas fa-list mr-2"></i>レビュー一覧
                                </a>
                            </div>
                        </div>
                    </div>
                </nav>
                
                <!-- デスクトップ用フルナビゲーション（1024px以上） -->
                <nav class="hidden lg:flex lg:space-x-1">
                    <a href="/" class="<?php echo getNavClass('/'); ?> px-3 py-2 text-sm font-medium transition-all duration-200 whitespace-nowrap border-b-2 border-transparent">
                        <i class="fas fa-home mr-1"></i>ホーム
                    </a>
                    <?php if (isset($_SESSION['AUTH_USER'])): ?>
                    <a href="/bookshelf.php" class="<?php echo getNavClass('/bookshelf.php'); ?> px-3 py-2 text-sm font-medium transition-all duration-200 whitespace-nowrap border-b-2 border-transparent relative">
                        <i class="fas fa-book-open mr-1"></i>本棚
                        <?php if ($reading_count > 0): ?>
                        <span class="absolute -top-1 -right-1 bg-blue-500 text-white text-xs w-5 h-5 rounded-full flex items-center justify-center">
                            <?php echo $reading_count; ?>
                        </span>
                        <?php endif; ?>
                    </a>
                    <a href="/add_book.php" class="<?php echo getNavClass('/add_book.php'); ?> px-3 py-2 text-sm font-medium transition-all duration-200 whitespace-nowrap border-b-2 border-transparent">
                        <i class="fas fa-plus-circle mr-1"></i>本を追加
                    </a>
                    
                    <!-- AI機能統合メニュー -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" 
                                @click.away="open = false"
                                class="<?php echo (strpos($_SERVER['REQUEST_URI'], 'recommendations') !== false || strpos($_SERVER['REQUEST_URI'], 'reading_assistant') !== false || strpos($_SERVER['REQUEST_URI'], 'reading_insights') !== false) ? 'text-readnest-primary dark:text-white border-b-2 border-readnest-primary dark:border-white' : 'text-gray-700 dark:text-gray-300 hover:text-readnest-primary dark:hover:text-white border-b-2 border-transparent'; ?> px-3 py-2 text-sm font-medium transition-all duration-200 whitespace-nowrap inline-flex items-center relative">
                            <i class="fas fa-robot mr-1"></i>AI機能
                            <i class="fas fa-chevron-down ml-1 text-xs"></i>
                            <span class="absolute -top-1 -right-8 bg-gradient-to-r from-purple-600 to-blue-600 text-white text-xs px-1.5 py-0.5 rounded-full font-bold">AI</span>
                        </button>
                        
                        <div x-show="open"
                             x-cloak
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="transform opacity-0 scale-95"
                             x-transition:enter-end="transform opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="transform opacity-100 scale-100"
                             x-transition:leave-end="transform opacity-0 scale-95"
                             class="origin-top-left absolute left-0 mt-2 w-64 rounded-lg shadow-xl bg-white dark:bg-gray-800 ring-1 ring-black ring-opacity-5 z-50">
                            
                            <div class="py-1">
                                <a href="/recommendations.php" class="group flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                    <i class="fas fa-magic mr-3 text-purple-500 group-hover:scale-110 transition-transform"></i>
                                    <div>
                                        <div class="font-medium">AI推薦</div>
                                        <div class="text-xs text-gray-500">あなた好みの本を提案</div>
                                    </div>
                                </a>
                                <a href="/reading_assistant.php" class="group flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                    <i class="fas fa-comments mr-3 text-green-500 group-hover:scale-110 transition-transform"></i>
                                    <div>
                                        <div class="font-medium">読書アシスタント</div>
                                        <div class="text-xs text-gray-500">AIと読書の相談</div>
                                    </div>
                                </a>
                                <a href="/reading_insights.php" class="group flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                    <i class="fas fa-brain mr-3 text-indigo-500 group-hover:scale-110 transition-transform"></i>
                                    <div>
                                        <div class="font-medium">読書インサイト</div>
                                        <div class="text-xs text-gray-500">AI分析＆読書マップ</div>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- 発見メニュー -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" 
                                @click.away="open = false"
                                class="<?php echo (strpos($_SERVER['REQUEST_URI'], 'popular') !== false || strpos($_SERVER['REQUEST_URI'], 'ranking') !== false || strpos($_SERVER['REQUEST_URI'], 'review') !== false) ? 'text-readnest-primary dark:text-white border-b-2 border-readnest-primary dark:border-white' : 'text-gray-700 dark:text-gray-300 hover:text-readnest-primary dark:hover:text-white border-b-2 border-transparent'; ?> px-3 py-2 text-sm font-medium transition-all duration-200 whitespace-nowrap inline-flex items-center">
                            <i class="fas fa-compass mr-1"></i>発見
                            <i class="fas fa-chevron-down ml-1 text-xs"></i>
                        </button>
                        
                        <div x-show="open"
                             x-cloak
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="transform opacity-0 scale-95"
                             x-transition:enter-end="transform opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="transform opacity-100 scale-100"
                             x-transition:leave-end="transform opacity-0 scale-95"
                             class="origin-top-left absolute left-0 mt-2 w-64 rounded-lg shadow-xl bg-white dark:bg-gray-800 ring-1 ring-black ring-opacity-5 z-50">
                            
                            <div class="py-1">
                                <a href="/popular_book.php" class="group flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                    <i class="fas fa-fire mr-3 text-red-500 group-hover:scale-110 transition-transform"></i>
                                    <div>
                                        <div class="font-medium">人気の本</div>
                                        <div class="text-xs text-gray-500">今みんなが読んでいる本</div>
                                    </div>
                                </a>
                                <a href="/ranking.php" class="group flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                    <i class="fas fa-trophy mr-3 text-yellow-500 group-hover:scale-110 transition-transform"></i>
                                    <div>
                                        <div class="font-medium">ランキング</div>
                                        <div class="text-xs text-gray-500">月間・年間ベスト</div>
                                    </div>
                                </a>
                                <a href="/popular_review.php" class="group flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                    <i class="fas fa-comment-dots mr-3 text-orange-500 group-hover:scale-110 transition-transform"></i>
                                    <div>
                                        <div class="font-medium">人気のレビュー</div>
                                        <div class="text-xs text-gray-500">話題の感想・書評</div>
                                    </div>
                                </a>
                                <a href="/reviews.php" class="group flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                    <i class="fas fa-comments mr-3 text-blue-500 group-hover:scale-110 transition-transform"></i>
                                    <div>
                                        <div class="font-medium">レビュー一覧</div>
                                        <div class="text-xs text-gray-500">みんなのレビュー</div>
                                    </div>
                                </a>
                            </div>

                            <div class="border-t border-gray-200"></div>
                            
                            <div class="py-1">
                                <a href="/activities.php" class="group flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                    <i class="fas fa-stream mr-3 text-indigo-500 group-hover:scale-110 transition-transform"></i>
                                    <div>
                                        <div class="font-medium">読書活動</div>
                                        <div class="text-xs text-gray-500">みんなの読書状況</div>
                                    </div>
                                </a>
                                <a href="/global_search.php" class="group flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                    <i class="fas fa-search mr-3 text-purple-500 group-hover:scale-110 transition-transform"></i>
                                    <div>
                                        <div class="font-medium">グローバル検索</div>
                                        <div class="text-xs text-gray-500">全ユーザーから本を探す</div>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                </nav>
                
                <!-- Global Search and User Menu -->
                <div class="flex items-center space-x-3">
                    <?php if (isset($_SESSION['AUTH_USER'])): ?>
                    <!-- Dark Mode Toggle -->
                    <button @click="toggleDarkMode()"
                            class="p-2 rounded-lg text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                            title="ダークモード切り替え">
                        <svg x-show="!$store.darkMode.isDark" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                        </svg>
                        <svg x-show="$store.darkMode.isDark" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </button>
                    <?php endif; ?>

                    <!-- Global Search -->
                    <div class="relative hidden md:block" x-data="globalSearch()">
                        <div class="relative">
                            <input type="text" 
                                   x-model="searchQuery"
                                   @focus="showResults = true"
                                   @keydown.escape="showResults = false"
                                   @keydown.enter="submitSearch"
                                   placeholder="グローバル検索..."
                                   class="w-48 lg:w-64 px-3 py-1.5 pl-9 pr-4 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-readnest-primary focus:border-transparent bg-gray-50 hover:bg-white transition-colors">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-globe text-gray-400 text-sm"></i>
                            </div>
                                <div x-show="loading" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                    <svg class="animate-spin h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Mobile Search Button -->
                    <button @click="$dispatch('open-mobile-search')" 
                            class="md:hidden flex items-center gap-1 px-2 py-1.5 text-gray-600 hover:text-readnest-primary transition-colors bg-gray-50 hover:bg-gray-100 rounded-lg">
                        <i class="fas fa-search text-base"></i>
                        <span class="text-xs font-medium">検索</span>
                    </button>
                    
                    <?php if (isset($_SESSION['AUTH_USER'])): ?>
                    <?php 
                    // プロフィール写真とニックネームを取得
                    $current_user_photo = '/img/no-image-user.png';
                    $current_user_nickname = 'ユーザー';
                    
                    if (function_exists('getProfilePhotoURL')) {
                        $current_user_photo = getProfilePhotoURL($_SESSION['AUTH_USER']);
                    }
                    
                    if (function_exists('getNickname')) {
                        $nickname = getNickname($_SESSION['AUTH_USER']);
                        if ($nickname) {
                            $current_user_nickname = $nickname;
                        }
                    }
                    ?>
                    <!-- User Dropdown -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" 
                                @click.away="open = false"
                                class="flex items-center text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-book-primary-500">
                            <img class="h-8 w-8 rounded-full object-cover" 
                                 src="<?php echo html($current_user_photo); ?>" 
                                 alt="<?php echo html($current_user_nickname); ?>">
                            <span class="ml-2 text-gray-700 dark:text-gray-300 hidden sm:block"><?php echo html($current_user_nickname); ?></span>
                        </button>
                        
                        <div x-show="open"
                             x-cloak
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="transform opacity-0 scale-95"
                             x-transition:enter-end="transform opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="transform opacity-100 scale-100"
                             x-transition:leave-end="transform opacity-0 scale-95"
                             class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white dark:bg-gray-800 ring-1 ring-black ring-opacity-5 z-50">
                            <div class="py-1">
                                <a href="/profile.php?user_id=<?php echo $_SESSION['AUTH_USER']; ?>" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                    <i class="fas fa-user mr-2"></i> マイページ
                                </a>
                                <a href="/account.php" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                    <i class="fas fa-cog mr-2"></i> 設定
                                </a>
                                <hr class="my-1">

                                <!-- 個人機能 -->
                                <div class="px-4 py-1 text-xs text-gray-500 font-semibold">読書記録</div>
                                <a href="/favorites.php" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                    <i class="fas fa-star mr-2 text-yellow-500"></i> お気に入り
                                </a>
                                <a href="/my_reviews.php" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                    <i class="fas fa-pen-to-square mr-2 text-indigo-500"></i> マイレビュー
                                </a>
                                <a href="/my_likes.php" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                    <i class="fas fa-heart mr-2 text-red-500"></i> いいね
                                </a>
                                <a href="/reading_calendar.php" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                    <i class="fas fa-calendar-check mr-2 text-emerald-500"></i> 読書カレンダー
                                </a>
                                <a href="/report/<?php echo date('Y'); ?>/<?php echo date('n'); ?>" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                    <i class="fas fa-chart-bar mr-2 text-teal-500"></i> 月間レポート
                                </a>
                                <?php if (function_exists('isAdmin') && isAdmin()): ?>
                                <hr class="my-1">
                                <a href="/admin/" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                    <i class="fas fa-shield-alt mr-2"></i> 管理画面
                                </a>
                                <?php endif; ?>
                                <hr class="my-1">
                                <a href="/help.php" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                    <i class="fas fa-question-circle mr-2"></i> ヘルプ
                                </a>
                                <a href="/announcements.php" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                    <i class="fas fa-bullhorn mr-2"></i> お知らせ
                                </a>
                                <hr class="my-1">
                                <a href="/clearsessions.php" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                    <i class="fas fa-sign-out-alt mr-2"></i> ログアウト
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <!-- Login/Register Buttons -->
                    <a href="/help.php" class="text-gray-700 hover:text-readnest-primary px-3 py-2 text-sm font-medium transition-colors whitespace-nowrap">
                        <i class="fas fa-question-circle"></i>
                    </a>
                    <a href="/index.php" class="btn-outline text-sm">ログイン</a>
                    <a href="/register.php" class="btn-primary text-sm">新規登録</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Mobile/Tablet Drawer Menu -->
    <div class="lg:hidden">
        <!-- Overlay -->
        <div x-show="mobileMenuOpen"
             x-cloak
             @click="mobileMenuOpen = false"
             x-transition:enter="transition-opacity ease-linear duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-black bg-opacity-50 z-50"></div>
        
        <!-- Drawer -->
        <div x-show="mobileMenuOpen"
             x-cloak
             x-transition:enter="transition ease-in-out duration-300 transform"
             x-transition:enter-start="-translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in-out duration-300 transform"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="-translate-x-full"
             class="fixed inset-y-0 left-0 z-50 w-64 bg-white dark:bg-gray-800 shadow-lg">
            <div class="flex items-center justify-between h-16 px-4 border-b border-gray-200">
                <span class="text-xl font-semibold">メニュー</span>
                <button @click="mobileMenuOpen = false" class="text-gray-400 hover:text-gray-500">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <nav class="px-4 py-4 overflow-y-auto" style="max-height: calc(100vh - 4rem);">
                <!-- メイン機能 -->
                <div class="mb-6">
                    <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">メイン</h4>
                    <a href="/" class="block py-2.5 px-3 rounded-lg <?php echo isActivePage('/') ? 'bg-readnest-primary text-white' : 'text-gray-700 hover:bg-gray-100'; ?> transition-colors">
                        <i class="fas fa-home mr-3 w-4"></i> ホーム
                    </a>
                    <?php if (isset($_SESSION['AUTH_USER'])): ?>
                    <a href="/bookshelf.php" class="block py-2.5 px-3 rounded-lg <?php echo isActivePage('/bookshelf.php') ? 'bg-readnest-primary text-white' : 'text-gray-700 hover:bg-gray-100'; ?> transition-colors relative">
                        <i class="fas fa-book-open mr-3 w-4"></i> 本棚
                        <?php if ($reading_count > 0): ?>
                        <span class="absolute top-2 right-3 bg-blue-500 text-white text-xs w-5 h-5 rounded-full flex items-center justify-center">
                            <?php echo $reading_count; ?>
                        </span>
                        <?php endif; ?>
                    </a>
                    <a href="/recommendations.php" class="block py-2.5 px-3 rounded-lg <?php echo isActivePage('/recommendations.php') ? 'bg-readnest-primary text-white' : 'text-gray-700 hover:bg-gray-100'; ?> transition-colors relative">
                        <i class="fas fa-magic mr-3 w-4"></i> AI推薦
                        <span class="absolute top-2 right-3 bg-gradient-to-r from-purple-600 to-pink-600 text-white text-xs px-1.5 py-0.5 rounded-full font-bold animate-pulse">New</span>
                    </a>
                    <a href="/favorites.php" class="block py-2.5 px-3 rounded-lg <?php echo isActivePage('/favorites.php') ? 'bg-readnest-primary text-white' : 'text-gray-700 hover:bg-gray-100'; ?> transition-colors">
                        <i class="fas fa-star mr-3 w-4"></i> お気に入り
                    </a>
                    <a href="/add_book.php" class="block py-2.5 px-3 rounded-lg <?php echo isActivePage('/add_book.php') ? 'bg-readnest-primary text-white' : 'text-gray-700 hover:bg-gray-100'; ?> transition-colors">
                        <i class="fas fa-plus-circle mr-3 w-4"></i> 本を追加
                    </a>
                    <a href="/reading_assistant.php" class="block py-2.5 px-3 rounded-lg <?php echo isActivePage('/reading_assistant.php') ? 'bg-readnest-primary text-white' : 'text-gray-700 hover:bg-gray-100'; ?> transition-colors relative">
                        <i class="fas fa-robot mr-3 w-4"></i> アシスタント
                        <span class="absolute top-2 right-3 bg-gradient-to-r from-green-600 to-blue-600 text-white text-xs px-1.5 py-0.5 rounded-full font-bold animate-pulse">AI</span>
                    </a>
                    <?php endif; ?>
                </div>
                
                <!-- 発見機能 -->
                <div class="mb-6">
                    <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">発見</h4>
                    <a href="/global_search.php" class="block py-2 text-gray-700 hover:text-book-primary-600">
                        <i class="fas fa-globe mr-3 w-4 text-purple-500"></i> グローバル検索
                    </a>
                    <a href="/popular_book.php" class="block py-2 text-gray-700 hover:text-book-primary-600">
                        <i class="fas fa-fire mr-3 w-4 text-red-500"></i> 人気の本
                    </a>
                    <a href="/ranking.php" class="block py-2 text-gray-700 hover:text-book-primary-600">
                        <i class="fas fa-trophy mr-3 w-4 text-yellow-500"></i> ランキング
                    </a>
                    <a href="/popular_review.php" class="block py-2 text-gray-700 hover:text-book-primary-600">
                        <i class="fas fa-comment-dots mr-3 w-4 text-orange-500"></i> 人気のレビュー
                    </a>
                    <a href="/reviews.php" class="block py-2 text-gray-700 hover:text-book-primary-600">
                        <i class="fas fa-comments mr-3 w-4 text-blue-500"></i> レビュー一覧
                    </a>
                </div>

                <!-- コミュニティ -->
                <div class="mb-6">
                    <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">コミュニティ</h4>
                    <a href="/activities.php" class="block py-2 text-gray-700 hover:text-book-primary-600">
                        <i class="fas fa-stream mr-3 w-4 text-indigo-500"></i> 読書活動
                    </a>
                    <!-- ユーザー一覧（コミュニティ機能実装後に有効化）
                    <a href="/user_list.php" class="block py-2 text-gray-700 hover:text-book-primary-600">
                        <i class="fas fa-user-group mr-3 w-4 text-green-500"></i> ユーザー一覧
                    </a>
                    -->
                    <a href="/announcements.php" class="block py-2 text-gray-700 hover:text-book-primary-600">
                        <i class="fas fa-bullhorn mr-3 w-4 text-blue-500"></i> お知らせ
                    </a>
                </div>
                
                <?php if (isset($_SESSION['AUTH_USER'])): ?>
                <!-- 分析・記録 -->
                <div class="mb-6">
                    <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">分析・記録</h4>
                    <a href="/reading_calendar.php" class="block py-2 text-gray-700 hover:text-book-primary-600">
                        <i class="fas fa-calendar-check mr-3 w-4 text-emerald-500"></i> 読書カレンダー
                    </a>
                    <a href="/reading_insights.php" class="block py-2 text-gray-700 hover:text-book-primary-600">
                        <i class="fas fa-brain mr-3 w-4 text-purple-500"></i> 読書インサイト
                    </a>
                    <a href="/report/<?php echo date('Y'); ?>/<?php echo date('n'); ?>" class="block py-2 text-gray-700 hover:text-book-primary-600">
                        <i class="fas fa-calendar-alt mr-3 w-4 text-teal-500"></i> 月間レポート
                    </a>
                    <a href="/my_reviews.php" class="block py-2 text-gray-700 hover:text-book-primary-600">
                        <i class="fas fa-pen-to-square mr-3 w-4 text-indigo-500"></i> マイレビュー
                    </a>
                    <a href="/my_likes.php" class="block py-2 text-gray-700 hover:text-book-primary-600">
                        <i class="fas fa-heart mr-3 w-4 text-red-500"></i> いいね
                    </a>
                </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['AUTH_USER'])): ?>
                <!-- ユーザー情報 -->
                <div class="border-t border-gray-200 pt-4">
                    <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">アカウント</h4>
                    <div class="flex items-center py-2 px-2 mb-3 bg-gray-50 rounded">
                        <img class="h-6 w-6 rounded-full object-cover mr-2" 
                             src="<?php echo html(isset($current_user_photo) ? $current_user_photo : '/img/no-image-user.png'); ?>" 
                             alt="<?php echo html(isset($current_user_nickname) ? $current_user_nickname : 'ユーザー'); ?>">
                        <span class="text-sm text-gray-700"><?php echo html(isset($current_user_nickname) ? $current_user_nickname : 'ユーザー'); ?></span>
                    </div>
                    <a href="/profile.php" class="block py-2 text-gray-700 hover:text-book-primary-600">
                        <i class="fas fa-user mr-3 w-4"></i> プロフィール
                    </a>
                    <a href="/account.php" class="block py-2 text-gray-700 hover:text-book-primary-600">
                        <i class="fas fa-cog mr-3 w-4"></i> 設定
                    </a>
                    <?php if (function_exists('isAdmin') && isAdmin()): ?>
                    <a href="/admin/" class="block py-2 text-gray-700 hover:text-book-primary-600">
                        <i class="fas fa-shield-alt mr-3 w-4"></i> 管理画面
                    </a>
                    <?php endif; ?>
                    <a href="/help.php" class="block py-2 text-gray-700 hover:text-book-primary-600">
                        <i class="fas fa-question-circle mr-3 w-4"></i> ヘルプ
                    </a>
                    <a href="/tutorial.php" class="block py-2 text-gray-700 hover:text-book-primary-600">
                        <i class="fas fa-graduation-cap mr-3 w-4"></i> チュートリアル
                    </a>
                    <a href="/clearsessions.php" class="block py-2 text-gray-700 hover:text-book-primary-600">
                        <i class="fas fa-sign-out-alt mr-3 w-4"></i> ログアウト
                    </a>
                </div>
                <?php else: ?>
                <!-- ログインしていない場合 -->
                <div class="border-t border-gray-200 pt-4">
                    <a href="/help.php" class="block py-2 text-gray-700 hover:text-book-primary-600 mb-3">
                        <i class="fas fa-question-circle mr-3 w-4"></i> ヘルプ
                    </a>
                    <a href="/index.php" class="block py-2 px-4 bg-gray-100 text-gray-700 rounded mb-2 text-center">
                        ログイン
                    </a>
                    <a href="/register.php" class="block py-2 px-4 bg-readnest-primary text-white rounded text-center">
                        新規登録
                    </a>
                </div>
                <?php endif; ?>
            </nav>
        </div>
    </div>
    
    <!-- Flash Messages -->
    <?php if (isset($_SESSION['logout_success'])): ?>
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4 mx-4 mt-4" role="alert">
        <strong class="font-bold">ログアウトしました。</strong>
        <span class="block sm:inline">ご利用ありがとうございました。</span>
    </div>
    <?php unset($_SESSION['logout_success']); ?>
    <?php endif; ?>

    <!-- Main Content -->
    <main class="flex-grow">
        <?php echo isset($d_content) ? $d_content : ''; ?>
    </main>
    
    <!-- Footer -->
    <footer class="bg-gradient-to-b from-gray-700 to-gray-900 dark:from-gray-800 dark:to-gray-950 text-white mt-auto transition-colors border-t-4 border-gray-600">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- About -->
                <div>
                    <h3 class="text-lg font-semibold mb-4 text-gray-100">ReadNestについて</h3>
                    <p class="text-gray-300 text-sm">
                        あなたの読書の巣（ネスト）です。読書進捗の記録、レビューの投稿、本棚の整理ができる居心地の良い空間で、読書仲間とのつながりも楽しめます。
                    </p>
                </div>
                
                <!-- Links -->
                <div>
                    <h3 class="text-lg font-semibold mb-4 text-gray-100">リンク</h3>
                    <ul class="space-y-2 text-sm">
                        <li><a href="/help.php" class="text-gray-300 hover:text-white transition-colors">ヘルプ</a></li>
                        <li><a href="/announcements.php" class="text-gray-300 hover:text-white transition-colors">お知らせ</a></li>
                        <li><a href="/terms.php" class="text-gray-300 hover:text-white transition-colors">利用規約</a></li>
                        <li><a href="/terms.php#privacy" class="text-gray-300 hover:text-white transition-colors">プライバシーポリシー</a></li>
                        <li><a href="https://yayoi-taka.hatenablog.com/" target="_blank" rel="noopener noreferrer" class="text-gray-300 hover:text-white transition-colors">開発者ブログ</a></li>
                    </ul>
                </div>
                
                <!-- Social -->
                <div>
                    <h3 class="text-lg font-semibold mb-4 text-gray-100">ソーシャル</h3>
                    <div class="space-y-3">
                        <!-- フォローリンク -->
                        <div>
                            <a href="https://x.com/dokusho" 
                               target="_blank" 
                               rel="noopener noreferrer"
                               class="inline-flex items-center space-x-2 text-gray-300 hover:text-white transition-colors">
                                <span class="flex items-center justify-center w-8 h-8 rounded-full border border-gray-300 hover:border-white font-bold text-sm">X</span>
                                <span class="text-sm">@dokusho をフォロー</span>
                            </a>
                        </div>
                        
                        <!-- 共有リンク -->
                        <div class="flex space-x-4">
                            <!-- X (Twitter) -->
                            <a href="https://twitter.com/intent/tweet?text=ReadNest%20-%20あなたの読書の巣&url=https://readnest.jp" 
                               target="_blank" 
                               rel="noopener noreferrer"
                               class="text-gray-400 hover:text-white transition-colors flex items-center justify-center w-8 h-8 rounded-full border border-gray-400 hover:border-white font-bold text-sm"
                               title="Xで共有">
                                X
                            </a>
                        <!-- はてなブックマーク -->
                        <a href="https://b.hatena.ne.jp/entry/https://readnest.jp" 
                           target="_blank" 
                           rel="noopener noreferrer"
                           class="text-gray-400 hover:text-white transition-colors"
                           title="はてなブックマークに追加">
                            <i class="fa-solid fa-b text-xl"></i>
                        </a>
                        <!-- Facebook -->
                        <a href="https://www.facebook.com/sharer/sharer.php?u=https://readnest.jp" 
                           target="_blank" 
                           rel="noopener noreferrer"
                           class="text-gray-400 hover:text-white transition-colors"
                           title="Facebookで共有">
                            <i class="fab fa-facebook-f text-xl"></i>
                        </a>
                        <!-- LINE -->
                        <a href="https://social-plugins.line.me/lineit/share?url=https://readnest.jp" 
                           target="_blank" 
                           rel="noopener noreferrer"
                           class="text-gray-400 hover:text-white transition-colors"
                           title="LINEで送る">
                            <i class="fab fa-line text-xl"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- コピーライトセクション（フルwidth） -->
        <div class="mt-8 pt-8 border-t border-gray-700">
            <div class="text-center text-sm text-gray-400 py-4">
                <p>&copy; <?php echo date('Y'); ?> ReadNest. All rights reserved.</p>
                <p class="mt-2">このサイトはバイブコーディングでメンテナンスしています</p>
            </div>
        </div>
    </footer>
    
    <!-- Custom JavaScript -->
    <script src="/js/common-utils.js"></script>
    <script src="/js/modern.js"></script>
    <script src="/js/onboarding.js?v=<?php echo date('YmdHis'); ?>"></script>
    <script src="/js/like.js?v=<?php echo date('YmdHis'); ?>"></script>
    
    <!-- AI Assistant -->
    <?php if (isset($_SESSION['AUTH_USER'])): ?>
    <script>
        // ログイン状態をJavaScriptに渡す
        window.isLoggedIn = true;
        // 初回ログインフラグをJavaScriptに渡す
        window.isFirstLogin = <?php echo isset($GLOBALS['is_first_login']) && $GLOBALS['is_first_login'] ? 'true' : 'false'; ?>;
    </script>
    <script src="/js/ai_assistant.js?v=<?php echo date('YmdHis'); ?>"></script>
    <?php endif; ?>
    
    <!-- AI Search Enhancement -->
    <?php if (basename($_SERVER['SCRIPT_NAME']) === 'add_book.php'): ?>
    <script src="/js/ai_search.js?v=<?php echo date('YmdHis'); ?>"></script>
    <?php endif; ?>
    
    
    <?php if (isset($d_additional_scripts)) echo $d_additional_scripts; ?>
    
    <!-- Dark Mode and Global Search JavaScript -->
    <script>
    // Dark Mode Store (Alpine.js)
    document.addEventListener('alpine:init', () => {
        Alpine.store('darkMode', {
            <?php if (isset($_SESSION['AUTH_USER'])): ?>
            isDark: localStorage.getItem('theme') === 'dark' ||
                   (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches),
            <?php else: ?>
            isDark: false, // ログアウト時は常にライトモード
            <?php endif; ?>

            toggle() {
                <?php if (isset($_SESSION['AUTH_USER'])): ?>
                this.isDark = !this.isDark;
                const theme = this.isDark ? 'dark' : 'light';
                localStorage.setItem('theme', theme);

                if (this.isDark) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
                <?php else: ?>
                // ログアウト時はダークモード切り替えを無効化
                <?php endif; ?>
            }
        });
    });
    
    // Dark Mode Toggle Function
    function toggleDarkMode() {
        Alpine.store('darkMode').toggle();
    }
    
    // Global Search Function
    function globalSearch() {
        return {
            searchQuery: '',
            showResults: false,
            loading: false,
            
            submitSearch() {
                if (this.searchQuery.trim()) {
                    window.location.href = `/global_search.php?q=${encodeURIComponent(this.searchQuery)}`;
                }
            }
        }
    }
    
    // Mobile Search Modal
    document.addEventListener('alpine:init', () => {
        Alpine.data('mobileSearch', () => ({
            open: false,
            searchQuery: '',
            loading: false,
            results: {
                books: [],
                authors: [],
                reviews: [],
                total: 0
            },
            
            async performSearch() {
                if (this.searchQuery.length < 2) {
                    this.results = { books: [], authors: [], reviews: [], total: 0 };
                    return;
                }
                
                this.loading = true;
                
                try {
                    // グローバル検索ページにリダイレクト
                    window.location.href = `/global_search.php?q=${encodeURIComponent(this.searchQuery)}`;
                } catch (error) {
                    console.error('Search error:', error);
                } finally {
                    this.loading = false;
                }
            }
        }));
    });
    
    // Listen for mobile search button click
    window.addEventListener('open-mobile-search', () => {
        const mobileSearchModal = document.getElementById('mobile-search-modal');
        if (mobileSearchModal) {
            mobileSearchModal.__x.$data.open = true;
            setTimeout(() => {
                const input = mobileSearchModal.querySelector('input[type="search"]');
                if (input) input.focus();
            }, 100);
        }
    });
    </script>
    
    <!-- Mobile Search Modal -->
    <div x-data="mobileSearch()" 
         x-show="open" 
         x-cloak
         id="mobile-search-modal"
         class="fixed inset-0 z-50 md:hidden"
         @open-mobile-search.window="open = true">
        <!-- Backdrop -->
        <div x-show="open"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="open = false"
             class="fixed inset-0 bg-black bg-opacity-50"></div>
        
        <!-- Modal -->
        <div x-show="open"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="relative mx-4 mt-20 bg-white rounded-lg shadow-xl">
            
            <!-- Header -->
            <div class="px-4 py-4">
                <h3 class="text-lg font-semibold mb-3 flex items-center justify-between">
                    <span><i class="fas fa-search mr-2"></i>グローバル検索</span>
                    <button @click="open = false" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </h3>
                <form action="/global_search.php" method="get" class="space-y-3">
                    <input type="search" 
                           name="q"
                           x-model="searchQuery"
                           @keydown.enter="$el.form.submit()"
                           placeholder="タイトル、著者名、レビューで検索..."
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-readnest-primary"
                           autofocus>
                    <button type="submit" 
                            class="w-full px-4 py-3 bg-readnest-primary text-white rounded-lg hover:bg-readnest-accent transition-colors font-semibold">
                        <i class="fas fa-search mr-2"></i>検索
                    </button>
                </form>
                <p class="mt-3 text-xs text-gray-500 text-center">
                    ReadNest全体から本やレビューを検索します
                </p>
            </div>
        </div>
    </div>
    
    <!-- Analytics -->
    <?php echo isset($g_analytics) ? $g_analytics : ''; ?>
    
    <!-- SQL Debug Log -->
    <?php 
    // デバッグモードの場合のみSQLログを表示
    if (defined('DEBUG') && DEBUG && function_exists('displaySQLLog')) {
        echo displaySQLLog();
    }
    ?>
</body>
</html>