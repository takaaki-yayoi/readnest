<?php
/**
 * 403 Forbidden - 管理者専用エラーページ（リニューアル版）
 */
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 アクセス拒否 - ReadNest Admin</title>
    
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
    
    <style>
        .lock-animation {
            animation: shake 0.5s ease-in-out;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }
        
        .slide-in {
            animation: slideIn 0.5s ease-out;
        }
        
        @keyframes slideIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-red-50 via-orange-50 to-yellow-50 flex items-center justify-center p-4">
    <div class="w-full max-w-2xl slide-in">
        <div class="bg-white rounded-3xl shadow-2xl overflow-hidden">
            <!-- ヘッダー -->
            <div class="relative bg-gradient-to-r from-red-500 to-red-600 p-12 text-white text-center">
                <div class="absolute inset-0 bg-black opacity-10"></div>
                <div class="relative z-10">
                    <div class="inline-flex items-center justify-center w-28 h-28 bg-white bg-opacity-20 rounded-full mb-6 lock-animation backdrop-blur-sm">
                        <i class="fas fa-lock text-6xl"></i>
                    </div>
                    <h1 class="text-7xl font-bold mb-2">403</h1>
                    <p class="text-3xl font-semibold">アクセス拒否</p>
                    <p class="text-lg opacity-90 mt-2">Forbidden Access</p>
                </div>
            </div>
            
            <!-- コンテンツ -->
            <div class="p-10">
                <div class="bg-red-50 border-l-4 border-red-500 rounded-lg p-6 mb-8">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <i class="fas fa-shield-alt text-red-500 text-3xl mr-4"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-red-800 text-lg mb-2">管理者専用エリア</h3>
                            <p class="text-red-700 leading-relaxed">
                                このページは管理者のみアクセス可能です。<br>
                                管理者権限を持つアカウントでログインしてください。
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="text-center">
                    <div class="flex flex-col sm:flex-row gap-4 justify-center mb-8">
                        <a href="/login.php" 
                           class="group inline-flex items-center justify-center px-8 py-4 bg-gradient-to-r from-readnest-primary to-gray-700 text-white rounded-xl hover:from-gray-700 hover:to-readnest-primary focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-readnest-primary transition-all duration-200 shadow-lg hover:shadow-xl transform hover:scale-105">
                            <i class="fas fa-sign-in-alt mr-3"></i>
                            ログインページへ
                        </a>
                        
                        <a href="/" 
                           class="group inline-flex items-center justify-center px-8 py-4 border-2 border-gray-300 rounded-xl text-gray-700 bg-white hover:bg-gray-50 hover:border-gray-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-readnest-primary transition-all duration-200 shadow-md hover:shadow-lg">
                            <i class="fas fa-home mr-3 group-hover:-translate-x-1 transition-transform"></i>
                            ホームへ戻る
                        </a>
                    </div>
                    
                    <div class="pt-8 border-t border-gray-200">
                        <p class="text-sm text-gray-500">
                            <i class="fas fa-question-circle mr-1"></i>
                            ログインに関してお困りの場合は、
                            <a href="/help.php#contact" class="text-readnest-primary hover:text-readnest-accent font-medium hover:underline transition-colors">
                                お問い合わせ
                            </a>
                            ください
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- フッター -->
        <div class="mt-6 text-center text-sm text-gray-500">
            <p>
                <i class="fas fa-lock text-gray-400 mr-1"></i>
                ReadNest Admin - Secure Area
            </p>
        </div>
    </div>
</body>
</html>