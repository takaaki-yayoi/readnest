<?php
if(!defined('CONFIG')) {
  error_log('direct access detected. file:' . __FILE__ . ' line:' . __LINE__ . ' ip:' . $_SERVER['REMOTE_ADDR'] . ' agent:' . $_SERVER['HTTP_USER_AGENT']);
  die('reference for this file is not allowed.');
}

// コンテンツ部分を生成
ob_start();
?>

<?php if (!isset($_SESSION['AUTH_USER'])): ?>
<!-- ヘッダー部分 -->
<header class="absolute top-0 left-0 right-0 z-20 p-4">
    <div class="max-w-6xl mx-auto flex justify-between items-center">
        <div class="text-2xl font-serif text-gray-800">
            ReadNest
        </div>
    </div>
</header>

<!-- Hero Section - 感情に訴えかけるデザイン -->
<section class="relative min-h-screen flex items-center justify-center bg-gradient-to-br from-rose-50 via-amber-50 to-sky-50 overflow-hidden">
    <!-- 装飾的な背景要素 -->
    <div class="absolute inset-0">
        <div class="absolute top-20 left-10 w-72 h-72 bg-rose-200 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-float"></div>
        <div class="absolute top-40 right-10 w-72 h-72 bg-amber-200 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-float-delayed"></div>
        <div class="absolute bottom-20 left-1/2 w-72 h-72 bg-sky-200 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-float-slow"></div>
    </div>
    
    <div class="relative z-10 max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="text-center">
            <!-- エモーショナルなメインコピー -->
            <h1 class="text-4xl sm:text-6xl font-serif text-gray-800 mb-6 leading-tight">
                あなただけの<br>
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-rose-400 to-amber-500">
                    読書の物語
                </span>を<br>
                紡ぎませんか
            </h1>
            
            <!-- 共感を呼ぶサブコピー -->
            <p class="text-lg sm:text-xl text-gray-600 mb-12 max-w-2xl mx-auto leading-relaxed">
                本を開くたびに訪れる、特別な時間。<br>
                ページをめくる喜び、心に残る一節、<br>
                そんな大切な瞬間を、ReadNestで記録しましょう。
            </p>
            
            <!-- CTAボタン -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center mb-12">
                <a href="/register.php" 
                   class="group relative inline-flex items-center justify-center px-8 py-4 text-lg font-medium text-white bg-gradient-to-r from-rose-400 to-amber-400 rounded-full shadow-xl hover:shadow-2xl transform hover:-translate-y-1 transition-all duration-300">
                    <span class="absolute inset-0 w-full h-full bg-gradient-to-r from-rose-400 to-amber-400 rounded-full blur opacity-75 group-hover:opacity-100 transition duration-300"></span>
                    <span class="relative">無料で始める</span>
                </a>
                <a href="#features" 
                   class="inline-flex items-center justify-center px-8 py-4 text-lg font-medium text-gray-700 bg-white bg-opacity-80 backdrop-blur-sm rounded-full shadow-lg hover:shadow-xl transform hover:-translate-y-1 transition-all duration-300">
                    詳しく見る
                </a>
            </div>
            
            <!-- ログインセクション -->
            <div class="mt-8">
                <!-- ログインフォーム -->
                <div id="loginForm" class="bg-white bg-opacity-90 backdrop-blur-sm rounded-2xl p-6 max-w-sm mx-auto shadow-xl">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">ログイン</h3>
                    
                    <?php if (file_exists(dirname(dirname(__DIR__)) . '/config/google_oauth.php')): ?>
                    <!-- Googleログイン -->
                    <div class="mb-4">
                        <a href="/auth/google_login.php" 
                           class="w-full flex items-center justify-center bg-white text-gray-700 border border-gray-300 py-3 px-4 rounded-lg hover:bg-gray-50 transition-colors focus:outline-none focus:ring-2 focus:ring-rose-200">
                            <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" 
                                 alt="Google" 
                                 class="w-5 h-5 mr-3">
                            <span class="font-medium">Googleでログイン</span>
                        </a>
                    </div>
                    <div class="relative mb-4">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-gray-200"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="px-2 bg-white text-gray-500">または</span>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <form action="/index.php" method="post">
                        <div class="mb-4">
                            <input type="text" 
                                   name="username" 
                                   placeholder="メールアドレス" 
                                   required
                                   class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-200 transition">
                        </div>
                        <div class="mb-4">
                            <input type="password" 
                                   name="password" 
                                   placeholder="パスワード" 
                                   required
                                   class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-200 transition">
                        </div>
                        <div class="mb-4">
                            <label class="flex items-center">
                                <input type="checkbox" name="autologin" class="mr-2">
                                <span class="text-sm text-gray-600">ログイン状態を保持する</span>
                            </label>
                        </div>
                        <button type="submit" 
                                class="w-full py-3 text-white bg-gradient-to-r from-rose-400 to-amber-400 rounded-lg font-medium hover:shadow-lg transform hover:-translate-y-0.5 transition-all duration-200">
                            ログイン
                        </button>
                        <div class="mt-4 text-center">
                            <a href="/reissue.php" class="text-sm text-gray-600 hover:text-rose-500 transition-colors">
                                パスワードをお忘れですか？
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            
        </div>
    </div>
</section>

<!-- 感情に訴える特徴セクション -->
<section id="features" class="py-20 bg-white">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-3xl sm:text-4xl font-serif text-gray-800 mb-4">
                ReadNestがあなたの読書を<br>
                もっと豊かにする理由
            </h2>
        </div>
        
        <div class="grid md:grid-cols-3 gap-8">
            <!-- 特徴1: 記録 -->
            <div class="text-center group">
                <div class="w-20 h-20 mx-auto mb-6 rounded-full bg-gradient-to-br from-rose-100 to-rose-200 flex items-center justify-center group-hover:scale-110 transition-transform">
                    <svg class="w-8 h-8 text-rose-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <h3 class="text-xl font-serif text-gray-800 mb-3">心に残る一冊を記録</h3>
                <p class="text-gray-600 leading-relaxed mb-3">
                    感動した場面、心に響いた言葉。<br>
                    あなたの読書体験を<br>
                    美しく残していけます。
                </p>
                <div class="text-sm text-gray-500 bg-rose-50 rounded-lg p-3">
                    <ul class="text-left space-y-1">
                        <li>• 5段階評価とレビュー投稿</li>
                        <li>• 読書進捗の記録（ページ数）</li>
                        <li>• 読了日・開始日の管理</li>
                    </ul>
                </div>
            </div>
            
            <!-- 特徴2: つながり -->
            <div class="text-center group">
                <div class="w-20 h-20 mx-auto mb-6 rounded-full bg-gradient-to-br from-amber-100 to-amber-200 flex items-center justify-center group-hover:scale-110 transition-transform">
                    <svg class="w-8 h-8 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-serif text-gray-800 mb-3">本好きと繋がる</h3>
                <p class="text-gray-600 leading-relaxed mb-3">
                    同じ本を愛する人たちと<br>
                    感想を共有し、<br>
                    新しい視点に出会えます。
                </p>
                <div class="text-sm text-gray-500 bg-amber-50 rounded-lg p-3">
                    <ul class="text-left space-y-1">
                        <li>• みんなのレビューを閲覧</li>
                        <li>• 月間読書ランキング</li>
                        <li>• 読書仲間の活動をフォロー</li>
                    </ul>
                </div>
            </div>
            
            <!-- 特徴3: 発見 -->
            <div class="text-center group">
                <div class="w-20 h-20 mx-auto mb-6 rounded-full bg-gradient-to-br from-sky-100 to-sky-200 flex items-center justify-center group-hover:scale-110 transition-transform">
                    <svg class="w-8 h-8 text-sky-500" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-serif text-gray-800 mb-3">運命の一冊と出会う</h3>
                <p class="text-gray-600 leading-relaxed mb-3">
                    AIがあなたの好みを理解し、<br>
                    次に読むべき<br>
                    特別な一冊を提案します。
                </p>
                <div class="text-sm text-gray-500 bg-sky-50 rounded-lg p-3">
                    <ul class="text-left space-y-1">
                        <li>• AI書評アシスタント</li>
                        <li>• パーソナライズド推薦</li>
                        <li>• タグやジャンルで検索</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ユーザーストーリー -->
<section class="py-20 bg-gradient-to-br from-rose-50 to-amber-50">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl sm:text-4xl font-serif text-gray-800 mb-4">
                読書がもっと楽しくなる体験
            </h2>
            <p class="text-lg text-gray-600 mt-6 leading-relaxed">
                本を読むだけでなく、記録し、振り返り、共有する。<br>
                ReadNestは、あなたの読書体験を豊かにする<br>
                すべての機能を提供します。
            </p>
        </div>
        
        <div class="space-y-8">
        </div>
    </div>
</section>

<!-- 最新の活動 -->
<?php if (isLatestActivitiesEnabled()): ?>
<section class="bg-gray-50 py-16 sm:py-24">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 text-center mb-12">最新の活動</h2>
        
        <?php 
        $activeItems = 1;
        if (isNewReviewsEnabled()) $activeItems++;
        if (isPopularBooksEnabled()) $activeItems++;
        if (isPopularTagsEnabled()) $activeItems++;
        
        $gridClass = 'grid grid-cols-1 gap-8';
        if ($activeItems === 2) {
            $gridClass = 'grid grid-cols-1 lg:grid-cols-2 gap-8';
        } elseif ($activeItems >= 3) {
            $gridClass = 'grid grid-cols-1 lg:grid-cols-3 gap-8';
        }
        ?>
        <div class="<?php echo $gridClass; ?>">
            <!-- 新着レビュー -->
            <?php if (isNewReviewsEnabled()): ?>
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-xl font-semibold mb-4 flex items-center">
                    <i class="fas fa-comment text-rose-500 mr-2"></i>
                    新着レビュー
                </h3>
                <div id="new_review" class="space-y-4">
                    <?php if (!empty($new_reviews)): ?>
                        <?php foreach (array_slice($new_reviews, 0, 3) as $review): ?>
                        <div class="border-b last:border-0 pb-4 last:pb-0">
                            <div class="flex items-start">
                                <img src="<?php echo html(isset($review['user_photo']) && !empty($review['user_photo']) ? $review['user_photo'] : '/img/no-image-user.png'); ?>" 
                                     alt="<?php echo html($review['nickname']); ?>" 
                                     class="w-10 h-10 rounded-full object-cover mr-3 flex-shrink-0"
                                     onerror="this.src='/img/no-image-user.png'">
                                <div class="flex-1">
                                    <div class="text-sm text-gray-600 mb-1">
                                        <span class="font-medium text-gray-900"><?php echo html($review['nickname']); ?></span>
                                        さんが
                                        「<?php echo html(mb_substr($review['book_title'], 0, 20)); ?>」
                                        にレビュー
                                    </div>
                                    <p class="text-sm text-gray-700 line-clamp-2">
                                        <?php echo html(mb_substr($review['comment'], 0, 80)); ?>...
                                    </p>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-gray-500 text-center">まだレビューがありません</p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- みんなの読書活動 -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-xl font-semibold mb-4 flex items-center">
                    <i class="fas fa-clock text-amber-500 mr-2"></i>
                    みんなの読書活動
                </h3>
                <div id="recent_activities" class="space-y-3">
                    <?php if (!empty($formatted_activities)): ?>
                        <?php foreach (array_slice($formatted_activities, 0, 5) as $activity): ?>
                        <div class="flex items-start space-x-3 p-2 hover:bg-gray-50 rounded">
                            <img src="<?php echo html(isset($activity['user_photo']) && !empty($activity['user_photo']) ? $activity['user_photo'] : '/img/no-image-user.png'); ?>" 
                                 alt="<?php echo html(isset($activity['user_name']) ? $activity['user_name'] : 'ユーザー'); ?>" 
                                 class="w-10 h-10 rounded-full object-cover flex-shrink-0"
                                 onerror="this.src='/img/no-image-user.png'">
                            <div class="flex-1 min-w-0">
                                <div class="text-sm">
                                    <span class="font-medium text-gray-900">
                                        <?php echo html(isset($activity['user_name']) ? $activity['user_name'] : '名無しさん'); ?>
                                    </span>
                                    <span class="text-gray-600">さんが</span>
                                    <?php
                                    $badge_colors = [
                                        'blue' => 'bg-blue-100 text-blue-800',
                                        'yellow' => 'bg-yellow-100 text-yellow-800',
                                        'green' => 'bg-green-100 text-green-800',
                                        'gray' => 'bg-gray-100 text-gray-800'
                                    ];
                                    $badge_color = isset($activity['type_color']) && isset($badge_colors[$activity['type_color']]) 
                                        ? $badge_colors[$activity['type_color']] 
                                        : 'bg-gray-100 text-gray-800';
                                    ?>
                                    <span class="inline-block px-2 py-0.5 text-xs rounded-full ml-1 <?php echo $badge_color; ?>">
                                        <?php echo html(isset($activity['type']) ? $activity['type'] : '更新'); ?>
                                    </span>
                                </div>
                                <div class="text-sm text-gray-600 mt-1">
                                    「<?php echo html(mb_substr($activity['book_title'], 0, 30)); ?>」
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-gray-500 text-center">まだ活動がありません</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- 読書中の本 -->
            <?php if (isPopularBooksEnabled()): ?>
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-xl font-semibold mb-4 flex items-center">
                    <i class="fas fa-book-reader text-sky-500 mr-2"></i>
                    みんなが読んでいる本
                </h3>
                <div id="read_book" class="grid grid-cols-3 gap-3">
                    <?php if (!empty($reading_books)): ?>
                        <?php foreach (array_slice($reading_books, 0, 9) as $book): ?>
                        <div class="relative group">
                            <img src="<?php echo html(!empty($book['image_url']) && strpos($book['image_url'], 'noimage') === false ? $book['image_url'] : '/img/no-image-book.png'); ?>" 
                                 alt="<?php echo html($book['title']); ?>" 
                                 class="w-full h-32 object-cover rounded shadow-sm group-hover:opacity-80 transition-opacity"
                                 title="<?php echo html($book['title']); ?>"
                                 onerror="this.src='/img/no-image-book.png'">
                            <div class="absolute bottom-0 right-0 bg-black bg-opacity-70 text-white px-2 py-1 rounded-tl text-xs font-medium">
                                <i class="fas fa-bookmark mr-1"></i><?php echo intval($book['bookmark_count']); ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-gray-500 text-center col-span-3">まだデータがありません</p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- 最終CTA -->
<section class="py-20 bg-white">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl sm:text-4xl font-serif text-gray-800 mb-6">
            今日から、あなたの読書を<br>
            もっと特別なものに
        </h2>
        <p class="text-lg text-gray-600 mb-8">
            登録は無料。いつでも退会できます。<br>
            まずは気軽に始めてみませんか？
        </p>
        <a href="/register.php" 
           class="group relative inline-flex items-center justify-center px-10 py-5 text-xl font-medium text-white bg-gradient-to-r from-rose-400 to-amber-400 rounded-full shadow-xl hover:shadow-2xl transform hover:-translate-y-1 transition-all duration-300">
            <span class="absolute inset-0 w-full h-full bg-gradient-to-r from-rose-400 to-amber-400 rounded-full blur opacity-75 group-hover:opacity-100 transition duration-300"></span>
            <span class="relative">無料で登録する</span>
        </a>
        
        <!-- 具体的な機能の説明 -->
        <div class="mt-8 grid grid-cols-3 gap-4 text-center">
            <div>
                <svg class="w-8 h-8 mx-auto mb-2 text-book-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
                <p class="text-sm text-gray-700 font-medium">読書記録</p>
                <p class="text-xs text-gray-600">感想やメモを残せる</p>
            </div>
            <div>
                <svg class="w-8 h-8 mx-auto mb-2 text-book-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                <p class="text-sm text-gray-700 font-medium">進捗管理</p>
                <p class="text-xs text-gray-600">月間目標を設定</p>
            </div>
            <div>
                <svg class="w-8 h-8 mx-auto mb-2 text-book-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                </svg>
                <p class="text-sm text-gray-700 font-medium">AI アシスタント</p>
                <p class="text-xs text-gray-600">読書の相談相手</p>
            </div>
        </div>
        
        <!-- ヘルプへのリンク -->
        <div class="mt-8 pt-8 border-t border-gray-200">
            <p class="text-gray-600">
                使い方がわからない？機能について詳しく知りたい？
            </p>
            <a href="/help.php" class="inline-flex items-center mt-3 text-sky-600 hover:text-sky-700 font-medium">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                </svg>
                ヘルプセンターを見る
            </a>
        </div>
    </div>
</section>

<?php else: ?>
<!-- ログイン済みユーザー向けコンテンツは既存のt_index.phpの内容を取り込む -->
<?php 
// t_index.phpのログイン済み部分を直接インクルード
$logged_in_template = dirname(__FILE__) . '/t_index.php';
if (file_exists($logged_in_template)) {
    include($logged_in_template);
}
?>
<?php endif; ?>

<style>
@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-20px); }
}

@keyframes float-delayed {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-30px); }
}

@keyframes float-slow {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-15px); }
}

.animate-float {
    animation: float 6s ease-in-out infinite;
}

.animate-float-delayed {
    animation: float-delayed 8s ease-in-out infinite;
    animation-delay: 2s;
}

.animate-float-slow {
    animation: float-slow 10s ease-in-out infinite;
    animation-delay: 4s;
}
</style>

<?php
$d_content = ob_get_clean();

// ベーステンプレートを使用してページを表示
include(__DIR__ . '/t_base.php');
?>