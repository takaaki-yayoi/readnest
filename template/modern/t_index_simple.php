<?php
if(!defined('CONFIG')) {
    die('Direct access not allowed');
}

// コンテンツ部分を生成
ob_start();
?>

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

<!-- 統計セクション（静的） -->
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
<section class="py-16 bg-gray-50">
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

<?php
$d_content = ob_get_clean();

// ベーステンプレートを使用
include(__DIR__ . '/t_base.php');
?>