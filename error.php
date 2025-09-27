<?php
/**
 * エラーページ（リニューアル版）
 * PHP 8.2.28対応版
 */

declare(strict_types=1);

// エラーコードとメッセージの取得
$error_code = $_GET['code'] ?? '500';
$error_message = $_GET['message'] ?? '';

// エラーコードに応じたメッセージ
$error_messages = [
    '400' => [
        'title' => '不正なリクエスト',
        'subtitle' => 'Bad Request',
        'message' => 'リクエストに問題があります。もう一度お試しください。',
        'icon' => 'fa-exclamation-triangle',
        'color' => 'yellow'
    ],
    '401' => [
        'title' => '認証が必要です',
        'subtitle' => 'Unauthorized',
        'message' => 'このページにアクセスするにはログインが必要です。',
        'icon' => 'fa-lock',
        'color' => 'orange'
    ],
    '403' => [
        'title' => 'アクセス拒否',
        'subtitle' => 'Forbidden',
        'message' => 'このページへのアクセス権限がありません。',
        'icon' => 'fa-ban',
        'color' => 'red'
    ],
    '404' => [
        'title' => 'ページが見つかりません',
        'subtitle' => 'Page Not Found',
        'message' => 'お探しのページは存在しないか、移動した可能性があります。',
        'icon' => 'fa-question-circle',
        'color' => 'blue'
    ],
    '500' => [
        'title' => 'サーバーエラー',
        'subtitle' => 'Internal Server Error',
        'message' => 'サーバーに問題が発生しました。しばらくしてからもう一度お試しください。',
        'icon' => 'fa-server',
        'color' => 'red'
    ],
    '503' => [
        'title' => 'メンテナンス中',
        'subtitle' => 'Service Unavailable',
        'message' => '現在メンテナンス中です。しばらくしてからアクセスしてください。',
        'icon' => 'fa-tools',
        'color' => 'gray'
    ]
];

$error_info = $error_messages[$error_code] ?? $error_messages['500'];

// カスタムメッセージがある場合は上書き
if (!empty($error_message)) {
    $error_info['message'] = htmlspecialchars($error_message);
}

// HTTPステータスコードを設定
http_response_code((int)$error_code);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $error_code; ?> <?php echo htmlspecialchars($error_info['title']); ?> - ReadNest</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'readnest-primary': '#4a5568',
                        'readnest-accent': '#ed8936',
                    }
                }
            }
        }
    </script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Custom styles -->
    <style>
        .gradient-text {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .error-icon-bg {
            background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0.2) 100%);
            backdrop-filter: blur(10px);
        }
        
        .pulse-animation {
            animation: pulse 3s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0% {
                transform: scale(1);
                opacity: 1;
            }
            50% {
                transform: scale(1.05);
                opacity: 0.8;
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }
        
        .slide-up {
            animation: slideUp 0.5s ease-out;
        }
        
        @keyframes slideUp {
            from {
                transform: translateY(20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 flex items-center justify-center p-4">
    <div class="w-full max-w-4xl slide-up">
        <!-- メインカード -->
        <div class="bg-white rounded-3xl shadow-2xl overflow-hidden">
            <!-- ヘッダー部分 -->
            <div class="relative bg-gradient-to-r from-<?php echo $error_info['color']; ?>-500 to-<?php echo $error_info['color']; ?>-600 p-12 text-white">
                <div class="absolute inset-0 bg-black opacity-10"></div>
                <div class="relative z-10 text-center">
                    <div class="inline-flex items-center justify-center w-32 h-32 error-icon-bg rounded-full mb-6 pulse-animation">
                        <i class="fas <?php echo $error_info['icon']; ?> text-6xl"></i>
                    </div>
                    <h1 class="text-8xl font-bold mb-2"><?php echo $error_code; ?></h1>
                    <p class="text-3xl font-semibold mb-2"><?php echo htmlspecialchars($error_info['title']); ?></p>
                    <p class="text-lg opacity-90"><?php echo htmlspecialchars($error_info['subtitle']); ?></p>
                </div>
            </div>
            
            <!-- コンテンツ部分 -->
            <div class="p-10">
                <!-- エラーメッセージ -->
                <div class="max-w-2xl mx-auto text-center mb-10">
                    <div class="bg-gray-50 rounded-2xl p-8 mb-8">
                        <i class="fas fa-info-circle text-gray-400 text-3xl mb-4"></i>
                        <p class="text-gray-700 text-lg leading-relaxed">
                            <?php echo nl2br(htmlspecialchars($error_info['message'])); ?>
                        </p>
                    </div>
                    
                    <!-- アクションボタン -->
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <button onclick="history.back()" 
                                class="group inline-flex items-center justify-center px-8 py-4 border-2 border-gray-300 rounded-xl text-gray-700 bg-white hover:bg-gray-50 hover:border-gray-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-readnest-primary transition-all duration-200 shadow-md hover:shadow-lg">
                            <i class="fas fa-arrow-left mr-3 group-hover:-translate-x-1 transition-transform"></i>
                            前のページに戻る
                        </button>
                        
                        <a href="/" 
                           class="group inline-flex items-center justify-center px-8 py-4 bg-gradient-to-r from-readnest-primary to-gray-700 text-white rounded-xl hover:from-gray-700 hover:to-readnest-primary focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-readnest-primary transition-all duration-200 shadow-md hover:shadow-lg transform hover:scale-105">
                            <i class="fas fa-home mr-3"></i>
                            ホームへ戻る
                        </a>
                    </div>
                </div>
                
                <!-- クイックリンク -->
                <div class="border-t border-gray-200 pt-10">
                    <h3 class="text-xl font-bold text-gray-800 mb-6 text-center">
                        <i class="fas fa-compass text-readnest-accent mr-2"></i>
                        クイックアクセス
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-3xl mx-auto">
                        <a href="/search_book.php" 
                           class="group flex items-center p-6 bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl hover:from-blue-100 hover:to-indigo-100 transition-all duration-200 shadow-sm hover:shadow-md">
                            <div class="flex-shrink-0 w-12 h-12 bg-blue-500 bg-opacity-10 rounded-lg flex items-center justify-center group-hover:bg-opacity-20 transition-colors">
                                <i class="fas fa-search text-blue-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="font-semibold text-gray-800">本を検索</p>
                                <p class="text-sm text-gray-600 mt-1">読みたい本を探す</p>
                            </div>
                        </a>
                        
                        <a href="/ranking.php" 
                           class="group flex items-center p-6 bg-gradient-to-br from-yellow-50 to-orange-50 rounded-xl hover:from-yellow-100 hover:to-orange-100 transition-all duration-200 shadow-sm hover:shadow-md">
                            <div class="flex-shrink-0 w-12 h-12 bg-yellow-500 bg-opacity-10 rounded-lg flex items-center justify-center group-hover:bg-opacity-20 transition-colors">
                                <i class="fas fa-trophy text-yellow-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="font-semibold text-gray-800">ランキング</p>
                                <p class="text-sm text-gray-600 mt-1">人気の本を見る</p>
                            </div>
                        </a>
                        
                        <a href="/help.php" 
                           class="group flex items-center p-6 bg-gradient-to-br from-purple-50 to-pink-50 rounded-xl hover:from-purple-100 hover:to-pink-100 transition-all duration-200 shadow-sm hover:shadow-md">
                            <div class="flex-shrink-0 w-12 h-12 bg-purple-500 bg-opacity-10 rounded-lg flex items-center justify-center group-hover:bg-opacity-20 transition-colors">
                                <i class="fas fa-question-circle text-purple-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="font-semibold text-gray-800">ヘルプ</p>
                                <p class="text-sm text-gray-600 mt-1">使い方を確認</p>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- フッター情報 -->
        <div class="mt-8 text-center">
            <p class="text-gray-600">
                <i class="fas fa-envelope text-gray-400 mr-2"></i>
                問題が続く場合は、<a href="/help.php#contact" class="text-readnest-primary hover:text-readnest-accent font-medium hover:underline transition-colors">お問い合わせ</a>ください
            </p>
            <?php if (isset($_SERVER['REQUEST_URI'])): ?>
            <p class="mt-3 text-xs text-gray-400">
                <i class="fas fa-link text-gray-300 mr-1"></i>
                <?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>
            </p>
            <?php endif; ?>
            <p class="mt-2 text-xs text-gray-400">
                <i class="fas fa-clock text-gray-300 mr-1"></i>
                <?php echo date('Y-m-d H:i:s'); ?>
            </p>
        </div>
    </div>
</body>
</html>