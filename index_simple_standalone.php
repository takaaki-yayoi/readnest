<?php
/**
 * 完全スタンドアロンの静的トップページ（ログアウト時用）
 * 外部依存なし
 */

declare(strict_types=1);

// セッション開始（シンプル版）
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// シンプルなログインチェック
$login_flag = isset($_SESSION['AUTH_USER']) && !empty($_SESSION['AUTH_USER']);

// ログイン済みの場合は通常のindex.phpにリダイレクト
if ($login_flag) {
    header('Location: /index.php');
    exit;
}

// 静的な統計情報を読み込み
$static_stats_file = __DIR__ . '/data/static_stats.php';
if (file_exists($static_stats_file)) {
    include($static_stats_file);
    $total_users = $static_stats['total_users'] ?? 12000;
    $total_books = $static_stats['total_books'] ?? 45000;
    $total_reviews = $static_stats['total_reviews'] ?? 8900;
    $total_pages_read = $static_stats['total_pages_read'] ?? 2300000;
} else {
    // フォールバック値
    $total_users = 12000;
    $total_books = 45000;
    $total_reviews = 8900;
    $total_pages_read = 2300000;
}

// HTMLエスケープ関数（シンプル版）
function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="ReadNest - 読書の進捉を記録し、レビューを書き、本を整理するための居心地のよい空間です。読書仲間とのつながりを楽しみましょう。">
    <meta name="keywords" content="読書,本,書評,レビュー,本棚,読書記録,ReadNest">
    <title>ReadNest - あなたの読書の巣</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'readnest-primary': '#8B4513',
                        'readnest-accent': '#D2691E',
                        'readnest-beige': '#F5DEB3',
                        'readnest-light': '#FAF0E6'
                    }
                }
            }
        }
    </script>
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        .btn {
            @apply inline-flex items-center justify-center px-4 py-2 rounded-md font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2;
        }
        .btn-primary {
            @apply bg-readnest-primary text-white hover:bg-readnest-accent focus:ring-readnest-primary;
        }
        .btn-outline {
            @apply border-2 border-readnest-primary text-readnest-primary hover:bg-readnest-primary hover:text-white;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- ヘッダー -->
    <header class="bg-white shadow-sm">
        <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <a href="/" class="flex items-center">
                        <i class="fas fa-book-open text-readnest-primary text-2xl mr-2"></i>
                        <span class="text-xl font-bold text-readnest-primary">ReadNest</span>
                    </a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="/register.php" class="text-gray-700 hover:text-readnest-primary">新規登録</a>
                    <a href="#login" class="btn btn-primary">ログイン</a>
                </div>
            </div>
        </nav>
    </header>

    <!-- メインコンテンツ -->
    <main>
        <!-- ヒーローセクション -->
        <section class="bg-gradient-to-b from-readnest-beige to-white py-20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center">
                    <h1 class="text-4xl sm:text-5xl font-bold text-readnest-primary mb-6">
                        読書の楽しさを、もっと身近に
                    </h1>
                    <p class="text-xl text-gray-700 mb-8 max-w-3xl mx-auto">
                        ReadNestは、あなたの読書体験を記録し、共有するためのプラットフォームです。<br>
                        読書の進捗を管理し、感想を残し、新しい本との出会いを楽しみましょう。
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <a href="/register.php" class="btn-primary text-lg px-8 py-4">
                            <i class="fas fa-user-plus mr-2"></i>無料で始める
                        </a>
                        <a href="#features" class="btn-outline text-lg px-8 py-4">
                            <i class="fas fa-info-circle mr-2"></i>詳しく見る
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- 統計セクション -->
        <section class="py-16 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
                    <div>
                        <div class="text-3xl font-bold text-readnest-primary"><?php echo number_format($total_users); ?>+</div>
                        <div class="text-gray-600 mt-2">登録ユーザー</div>
                    </div>
                    <div>
                        <div class="text-3xl font-bold text-readnest-accent"><?php echo number_format($total_books); ?>+</div>
                        <div class="text-gray-600 mt-2">登録された本</div>
                    </div>
                    <div>
                        <div class="text-3xl font-bold text-blue-600"><?php echo number_format($total_reviews); ?>+</div>
                        <div class="text-gray-600 mt-2">書評・レビュー</div>
                    </div>
                    <div>
                        <div class="text-3xl font-bold text-purple-600"><?php echo number_format($total_pages_read); ?>+</div>
                        <div class="text-gray-600 mt-2">読まれたページ数</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 機能紹介 -->
        <section id="features" class="py-20 bg-gray-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-3xl font-bold text-center text-gray-900 mb-12">ReadNestでできること</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div class="bg-white rounded-lg shadow-lg p-8">
                        <div class="w-16 h-16 bg-readnest-primary rounded-full flex items-center justify-center mb-4">
                            <i class="fas fa-book-open text-white text-2xl"></i>
                        </div>
                        <h3 class="text-xl font-semibold mb-3">読書記録の管理</h3>
                        <p class="text-gray-600">
                            読んだ本、読んでいる本、これから読む本を整理。
                            読書の進捗をページ単位で記録できます。
                        </p>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow-lg p-8">
                        <div class="w-16 h-16 bg-readnest-accent rounded-full flex items-center justify-center mb-4">
                            <i class="fas fa-pen text-white text-2xl"></i>
                        </div>
                        <h3 class="text-xl font-semibold mb-3">感想・レビューの共有</h3>
                        <p class="text-gray-600">
                            読んだ本の感想を記録し、他の読者と共有。
                            評価や詳細なレビューを残せます。
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA -->
        <section class="py-20 bg-readnest-primary text-white">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h2 class="text-3xl font-bold mb-4">今すぐ読書記録を始めよう</h2>
                <p class="text-xl mb-8 opacity-90">
                    無料で登録して、あなたの読書ライフをもっと充実させましょう
                </p>
                <a href="/register.php" class="btn bg-white text-readnest-primary hover:bg-gray-100 text-lg px-8 py-4 font-semibold">
                    <i class="fas fa-rocket mr-2"></i>無料登録する
                </a>
            </div>
        </section>

        <!-- ログインフォーム -->
        <section id="login" class="py-16 bg-gray-50">
            <div class="max-w-md mx-auto px-4 sm:px-6 lg:px-8">
                <div class="bg-white rounded-lg shadow-lg p-8">
                    <h2 class="text-2xl font-bold text-center text-gray-900 mb-6">ログイン</h2>
                    
                    <form action="/clearsessions.php" method="post">
                        <input type="hidden" name="todo" value="login">
                        
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="username">
                                メールアドレス
                            </label>
                            <input type="email" 
                                   name="username" 
                                   id="username"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-readnest-primary"
                                   required>
                        </div>
                        
                        <div class="mb-6">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="password">
                                パスワード
                            </label>
                            <input type="password" 
                                   name="password" 
                                   id="password"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-readnest-primary"
                                   required>
                        </div>
                        
                        <div class="mb-6">
                            <label class="flex items-center">
                                <input type="checkbox" name="autologin" value="on" class="mr-2">
                                <span class="text-sm text-gray-700">ログイン状態を保持する</span>
                            </label>
                        </div>
                        
                        <button type="submit" 
                                class="w-full bg-readnest-primary text-white py-2 px-4 rounded-md hover:bg-readnest-accent transition-colors font-semibold">
                            ログイン
                        </button>
                    </form>
                    
                    <div class="mt-6 text-center">
                        <a href="/register.php" class="text-readnest-primary hover:underline">
                            新規登録はこちら
                        </a>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- フッター -->
    <footer class="bg-gray-800 text-white py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <p class="text-sm">
                    &copy; 2025 ReadNest. All rights reserved. | 
                    <a href="/help.php" class="hover:underline">ヘルプ</a> | 
                    <a href="/help.php#privacy_policy" class="hover:underline">プライバシーポリシー</a>
                </p>
            </div>
        </div>
    </footer>
</body>
</html>