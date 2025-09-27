<?php
if(!defined('CONFIG')) {
  error_log('direct access detected. file:' . __FILE__ . ' line:' . __LINE__ . ' ip:' . $_SERVER['REMOTE_ADDR'] . ' agent:' . $_SERVER['HTTP_USER_AGENT']);
  die('reference for this file is not allowed.');
}

// コンテンツ部分を生成
ob_start();
?>

<?php if (!isset($_SESSION['AUTH_USER'])): ?>
<!-- Hero Section - 未ログイン時のみ表示 -->
<section class="bg-gradient-to-r from-readnest-primary to-readnest-accent dark:from-gray-800 dark:to-gray-700 text-white relative overflow-hidden">
    <div class="absolute inset-0 bg-black opacity-10"></div>
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-24">
        <div class="text-center">
            <!-- AI機能バッジ -->
            <div class="inline-flex items-center bg-gradient-to-r from-purple-600 to-pink-600 dark:from-gray-600 dark:to-gray-700 text-white px-4 py-2 rounded-full text-sm font-semibold mb-6 animate-pulse shadow-lg">
                <i class="fas fa-sparkles mr-2"></i>
                AI機能搭載 - GPT-4使用
            </div>
            
            <h1 class="text-4xl sm:text-5xl font-bold mb-6">
                ReadNestへようこそ
            </h1>
            <p class="text-xl sm:text-2xl mb-4 text-white opacity-90">
                あなたの読書の巣。進捉を記録し、レビューを共有し、本好き仲間とつながりましょう
            </p>
            
            <!-- AI機能の紹介 -->
            <div class="bg-white bg-opacity-20 backdrop-blur-sm rounded-lg p-4 max-w-2xl mx-auto mb-8 shadow-lg">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-center">
                    <div>
                        <i class="fas fa-magic text-2xl mb-2"></i>
                        <p class="text-sm font-semibold">AI書評アシスタント</p>
                        <p class="text-xs opacity-90">レビュー作成を支援</p>
                    </div>
                    <div>
                        <i class="fas fa-robot text-2xl mb-2"></i>
                        <p class="text-sm font-semibold">AI本推薦</p>
                        <p class="text-xs opacity-90">読書履歴から類似本を提案</p>
                    </div>
                    <div>
                        <i class="fas fa-brain text-2xl mb-2"></i>
                        <p class="text-sm font-semibold">読書傾向分析</p>
                        <p class="text-xs opacity-90">AIが読書パターンを解析</p>
                    </div>
                </div>
            </div>
            
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="/register.php" class="btn bg-white text-readnest-primary hover:bg-readnest-beige px-8 py-3 text-lg font-semibold shadow-lg transform hover:scale-105 transition-all">
                    <i class="fas fa-user-plus mr-2"></i>今すぐ始める
                </a>
                <a href="#ai-features" 
                   class="btn bg-transparent border-2 border-white text-white hover:bg-white hover:text-readnest-primary px-8 py-3 text-lg font-semibold transition-all inline-block">
                    AI機能を見る
                </a>
            </div>
        </div>
    </div>
</section>
<?php else: ?>
<!-- 控えめなウェルカムセクション - ログイン時 -->
<?php
// 挨拶メッセージのバリエーション
$hour = (int)date('H');
$day_of_week = date('w');
$month = (int)date('n');
$date = date('j');

// 時間帯による挨拶
if ($hour >= 5 && $hour < 10) {
    $greeting = 'おはようございます！';
    $messages = [
        '朝の読書で一日をスタートしましょう',
        '爽やかな朝の読書時間を楽しんでください',
        '今日はどんな本と出会えるでしょうか',
        '朝の静かな時間に読書はいかがですか',
        '素敵な一日の始まりに読書を'
    ];
} elseif ($hour >= 10 && $hour < 12) {
    $greeting = 'こんにちは！';
    $messages = [
        '充実した読書時間をお過ごしください',
        '今日も素晴らしい本との出会いがありますように',
        '読書で心を豊かにしましょう',
        '午前中の読書タイムはいかがですか',
        '集中できる時間に読書を楽しみましょう'
    ];
} elseif ($hour >= 12 && $hour < 15) {
    $greeting = 'こんにちは！';
    $messages = [
        'お昼休みに読書でリフレッシュ',
        'ランチタイムの読書はいかがですか',
        '午後も素敵な読書時間を',
        '昼下がりの読書でほっと一息',
        'お昼の読書タイムを楽しんでください'
    ];
} elseif ($hour >= 15 && $hour < 18) {
    $greeting = 'こんにちは！';
    $messages = [
        '午後のひとときを読書で彩りましょう',
        '夕方までの時間を読書で充実させましょう',
        '読書で午後の疲れをリフレッシュ',
        '素敵な午後の読書時間を',
        '今日も読書を楽しんでいきましょう'
    ];
} elseif ($hour >= 18 && $hour < 21) {
    $greeting = 'こんばんは！';
    $messages = [
        '夜の読書時間をお楽しみください',
        '一日の終わりに読書でリラックス',
        '静かな夜に読書はいかがですか',
        '今夜はどんな本を読みますか？',
        '夜の読書で心を落ち着けましょう'
    ];
} else {
    $greeting = 'こんばんは！';
    $messages = [
        '夜更かし読書を楽しんでいますか？',
        '静寂な深夜の読書時間',
        '夜の静けさの中で読書を',
        '深夜の読書タイムですね',
        'ゆったりとした夜の読書を'
    ];
}

// 曜日による特別メッセージ
if ($day_of_week == 0) { // 日曜日
    $special_messages = [
        '日曜日はゆっくり読書を楽しみましょう',
        '休日の読書で心をリフレッシュ',
        'のんびり日曜日の読書タイム'
    ];
} elseif ($day_of_week == 6) { // 土曜日
    $special_messages = [
        '週末は読書三昧はいかがですか',
        '土曜日の読書で充実した休日を',
        'ゆったり週末読書を楽しみましょう'
    ];
} elseif ($day_of_week == 1) { // 月曜日
    $special_messages = [
        '新しい一週間を読書でスタート',
        '月曜日も読書で元気に',
        '今週も素敵な本と出会えますように'
    ];
} elseif ($day_of_week == 5) { // 金曜日
    $special_messages = [
        '金曜日！週末に向けて読書の準備を',
        '一週間お疲れさまでした。読書でリラックス',
        'TGIF！週末は読書を楽しみましょう'
    ];
} else {
    $special_messages = [];
}

// 季節による特別メッセージ
if ($month >= 3 && $month <= 5) { // 春
    $seasonal_messages = [
        '春の陽気と共に読書を楽しみましょう',
        '新しい季節に新しい本との出会いを',
        '春風を感じながら読書はいかがですか'
    ];
} elseif ($month >= 6 && $month <= 8) { // 夏
    $seasonal_messages = [
        '夏の涼しい場所で読書を',
        '暑い夏は室内で読書がおすすめ',
        '夏休みの読書計画はいかがですか'
    ];
} elseif ($month >= 9 && $month <= 11) { // 秋
    $seasonal_messages = [
        '読書の秋を満喫しましょう',
        '秋の夜長に読書はぴったり',
        '芸術の秋、読書で感性を磨きましょう'
    ];
} else { // 冬
    $seasonal_messages = [
        '暖かい部屋で読書を楽しみましょう',
        '冬の長い夜は読書に最適',
        'こたつで読書、最高の贅沢です'
    ];
}

// 特別な日のメッセージ
$special_day_messages = [];
if ($month == 1 && $date <= 7) {
    $special_day_messages[] = '新年も読書で充実した一年に';
    $special_day_messages[] = '今年の読書目標は決まりましたか？';
} elseif ($month == 4 && $date == 23) {
    $special_day_messages[] = '今日は世界図書・著作権デー！';
} elseif ($month == 10 && $date == 27) {
    $special_day_messages[] = '読書週間が始まります！';
} elseif ($month == 11 && $date == 1) {
    $special_day_messages[] = '11月1日は古典の日です';
}

// すべてのメッセージを統合
$all_messages = array_merge($messages, $special_messages, $seasonal_messages, $special_day_messages);

// 日付とユーザーIDを使ってランダムに選択（同じ日は同じメッセージ）
$seed = crc32(date('Y-m-d') . ($_SESSION['AUTH_USER'] ?? ''));
srand($seed);
$selected_message = $all_messages[array_rand($all_messages)];
srand(); // ランダムシードをリセット

// おかえりなさいのバリエーション
$welcome_variations = ['おかえりなさい！', 'お帰りなさい！', 'ようこそ！', 'Welcome back!'];
$welcome_greeting = ($hour >= 21 || $hour < 5) ? $greeting : $welcome_variations[array_rand($welcome_variations)];
?>
<section class="bg-gradient-to-r from-gray-50 to-blue-50 dark:from-gray-800 dark:to-gray-900 border-b border-gray-200 dark:border-gray-700">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white"><?php echo $welcome_greeting; ?></h1>
                <p class="text-gray-600 dark:text-gray-400 text-sm"><?php echo $selected_message; ?></p>
            </div>
            <div class="flex gap-2 sm:gap-3">
                <a href="/add_book.php" class="btn-primary px-3 sm:px-4 py-2 text-sm flex-1 sm:flex-none text-center">
                    <i class="fas fa-plus mr-1 sm:mr-2"></i>本を追加
                </a>
                <a href="/bookshelf.php" class="btn-secondary px-3 sm:px-4 py-2 text-sm flex-1 sm:flex-none text-center">
                    <i class="fas fa-book mr-1 sm:mr-2"></i>本棚
                </a>
                <a href="/reading_calendar.php" class="btn-secondary px-3 sm:px-4 py-2 text-sm flex-1 sm:flex-none text-center">
                    <i class="fas fa-calendar-check mr-1 sm:mr-2"></i>カレンダー
                </a>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if (isset($_SESSION['AUTH_USER']) && isset($reading_level) && isset($streak_milestone)): ?>
<!-- モチベーションセクション（スリム版） -->
<section class="bg-gradient-to-r from-purple-600 to-indigo-600 dark:from-gray-700 dark:to-gray-800 text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <!-- レベル・称号 -->
            <div class="flex items-center gap-3">
                <i class="fas fa-<?php echo $reading_level['title']['icon']; ?> text-xl"></i>
                <div>
                    <div class="text-sm font-bold">
                        <a href="/leveling_guide.php" class="hover:underline">
                            Lv.<?php echo $reading_level['level']; ?> <?php echo $reading_level['title']['name']; ?>
                        </a>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="bg-white/20 rounded-full h-1.5 w-24 overflow-hidden">
                            <div class="bg-white h-full transition-all duration-500" style="width: <?php echo $reading_level['progress']; ?>%"></div>
                        </div>
                        <span class="text-xs opacity-75"><?php echo $reading_level['progress']; ?>%</span>
                    </div>
                </div>
            </div>
            
            <!-- 連続記録 -->
            <div class="flex items-center gap-3">
                <?php if ($current_streak >= 7): ?>
                    <i class="fas fa-fire text-xl animate-pulse"></i>
                <?php else: ?>
                    <i class="fas fa-calendar-check text-xl"></i>
                <?php endif; ?>
                <div>
                    <div class="text-sm font-bold"><?php echo $current_streak; ?>日連続</div>
                    <?php if ($streak_milestone['current']): ?>
                        <div class="text-xs opacity-75"><?php echo $streak_milestone['current']['title']; ?></div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- 今月のペース -->
            <div class="flex items-center gap-3">
                <i class="fas fa-<?php echo $monthly_pace['icon']; ?> text-xl"></i>
                <div>
                    <div class="text-sm font-bold"><?php echo $monthly_pace['status']; ?></div>
                    <div class="text-xs opacity-75">今月<?php echo $monthly_pace['books_read']; ?>冊</div>
                </div>
            </div>
            
            <!-- 今月のランキング -->
            <?php if (isset($my_ranking_info) && $my_ranking_info): ?>
            <a href="/ranking.php" class="flex items-center gap-3 hover:opacity-80 active:opacity-60 transition-opacity group cursor-pointer">
                <?php if ($my_ranking_info['rank'] !== '圏外' && $my_ranking_info['rank'] !== '-' && intval($my_ranking_info['rank']) <= 3): ?>
                    <i class="fas fa-trophy text-xl <?php echo intval($my_ranking_info['rank']) == 1 ? 'text-yellow-500' : (intval($my_ranking_info['rank']) == 2 ? 'text-gray-400' : 'text-amber-600'); ?>"></i>
                <?php else: ?>
                    <i class="fas fa-ranking-star text-xl"></i>
                <?php endif; ?>
                <div>
                    <div class="text-sm font-bold flex items-center gap-1">
                        <?php if ($my_ranking_info['rank'] === '圏外' || $my_ranking_info['rank'] === '-'): ?>
                            <?php if ($my_ranking_info['book_count'] == 0): ?>
                                未参加
                            <?php else: ?>
                                ランキング圏外
                            <?php endif; ?>
                        <?php else: ?>
                            <?php echo $my_ranking_info['rank']; ?>位
                        <?php endif; ?>
                        <i class="fas fa-chevron-right text-xs opacity-75 group-hover:opacity-100 transition-opacity"></i>
                    </div>
                    <div class="text-xs opacity-75">今月のランキング</div>
                </div>
            </a>
            <?php endif; ?>
            
            <!-- 励ましメッセージ -->
            <div class="text-sm font-medium opacity-90 flex-1 text-center sm:text-right">
                <?php echo $motivational_message; ?>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if (isset($latest_announcement) && $latest_announcement): ?>
<!-- お知らせバナー -->
<?php
// タイプに応じてバナーの色を変更
$banner_colors = [
    'general' => ['bg' => 'bg-blue-50 dark:bg-blue-900/20', 'border' => 'border-blue-200 dark:border-blue-700', 'icon' => 'text-blue-600 dark:text-blue-400', 'text' => 'text-blue-900 dark:text-blue-100', 'date' => 'text-blue-600 dark:text-blue-400', 'link' => 'text-blue-800 dark:text-blue-300 hover:text-blue-900 dark:hover:text-blue-200'],
    'new_feature' => ['bg' => 'bg-green-50 dark:bg-green-900/20', 'border' => 'border-green-200 dark:border-green-700', 'icon' => 'text-green-600 dark:text-green-400', 'text' => 'text-green-900 dark:text-green-100', 'date' => 'text-green-600 dark:text-green-400', 'link' => 'text-green-800 dark:text-green-300 hover:text-green-900 dark:hover:text-green-200'],
    'bug_fix' => ['bg' => 'bg-red-50 dark:bg-red-900/20', 'border' => 'border-red-200 dark:border-red-700', 'icon' => 'text-red-600 dark:text-red-400', 'text' => 'text-red-900 dark:text-red-100', 'date' => 'text-red-600 dark:text-red-400', 'link' => 'text-red-800 dark:text-red-300 hover:text-red-900 dark:hover:text-red-200'],
    'maintenance' => ['bg' => 'bg-yellow-50 dark:bg-yellow-900/20', 'border' => 'border-yellow-200 dark:border-yellow-700', 'icon' => 'text-yellow-600 dark:text-yellow-400', 'text' => 'text-yellow-900 dark:text-yellow-100', 'date' => 'text-yellow-600 dark:text-yellow-400', 'link' => 'text-yellow-800 dark:text-yellow-300 hover:text-yellow-900 dark:hover:text-yellow-200']
];
$type = $latest_announcement['type'] ?? 'general';
$colors = $banner_colors[$type] ?? $banner_colors['general'];

$type_icons = [
    'general' => 'bullhorn',
    'new_feature' => 'sparkles',
    'bug_fix' => 'bug',
    'maintenance' => 'wrench'
];
$icon = $type_icons[$type] ?? 'bullhorn';
?>
<section class="<?php echo $colors['bg']; ?> border-b <?php echo $colors['border']; ?>">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3">
        <div class="flex items-center space-x-3">
            <div class="flex-shrink-0">
                <i class="fas fa-<?php echo $icon; ?> <?php echo $colors['icon']; ?>"></i>
            </div>
            <div class="flex-1 min-w-0">
                <a href="/announcement_detail.php?id=<?php echo $latest_announcement['announcement_id']; ?>" 
                   class="flex items-center justify-between hover:opacity-80 transition-opacity">
                    <div class="flex-1">
                        <span class="text-sm font-medium <?php echo $colors['text']; ?> hover:underline">
                            <?php echo html($latest_announcement['title']); ?>
                        </span>
                        <span class="text-xs <?php echo $colors['date']; ?> ml-3">
                            <?php echo date('Y年n月j日', strtotime($latest_announcement['created'])); ?>
                        </span>
                    </div>
                    <i class="fas fa-chevron-right <?php echo $colors['icon']; ?> ml-2"></i>
                </a>
            </div>
            <div class="flex-shrink-0 ml-3">
                <a href="/announcements.php" class="text-xs <?php echo $colors['link']; ?> whitespace-nowrap">
                    一覧へ
                </a>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if (!isset($_SESSION['AUTH_USER'])): ?>
<!-- ログインフォーム -->
<section class="bg-gray-50 py-8">
    <div class="max-w-md mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <h2 class="text-2xl font-bold text-gray-900 mb-6 text-center">ログイン</h2>
            <?php if (!empty($g_error)): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo html($g_error); ?>
            </div>
            <?php endif; ?>
            <?php if (file_exists(BASEDIR . '/config/google_oauth.php')): ?>
            <!-- Googleログイン -->
            <div class="mb-6">
                <a href="/auth/google_login.php" 
                   class="w-full flex items-center justify-center bg-white text-gray-700 border border-gray-300 py-2 px-4 rounded-md hover:bg-gray-50 transition-colors focus:outline-none focus:ring-2 focus:ring-gray-300 focus:ring-offset-2">
                    <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" 
                         alt="Google" 
                         class="w-5 h-5 mr-3">
                    <span class="font-medium">Googleでログイン</span>
                </a>
            </div>
            <div class="relative mb-6">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-gray-300"></div>
                </div>
                <div class="relative flex justify-center text-sm">
                    <span class="px-2 bg-white text-gray-500">または</span>
                </div>
            </div>
            <?php endif; ?>
            
            <form action="/index.php" method="post">
                <div class="mb-4">
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-2">メールアドレス</label>
                    <input type="email" 
                           name="username" 
                           id="username" 
                           required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-readnest-primary focus:border-transparent"
                           placeholder="email@example.com">
                </div>
                <div class="mb-6">
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">パスワード</label>
                    <input type="password" 
                           name="password" 
                           id="password" 
                           required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-readnest-primary focus:border-transparent"
                           placeholder="••••••••">
                </div>
                <div class="flex items-center justify-between mb-6">
                    <label class="flex items-center">
                        <input type="checkbox" 
                               name="autologin" 
                               id="autologin" 
                               value="on"
                               class="h-4 w-4 text-readnest-primary focus:ring-readnest-primary border-gray-300 rounded">
                        <span class="ml-2 text-sm text-gray-600">次回からログインを省略する</span>
                    </label>
                    <a href="/reissue.php" class="text-sm text-readnest-primary hover:text-readnest-accent">
                        パスワードを忘れた方
                    </a>
                </div>
                <button type="submit" 
                        class="w-full bg-readnest-primary text-white py-2 px-4 rounded-md hover:bg-readnest-accent transition-colors focus:outline-none focus:ring-2 focus:ring-readnest-primary focus:ring-offset-2">
                    ログイン
                </button>
            </form>
            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600">
                    アカウントをお持ちでない方は
                    <a href="/register.php" class="text-readnest-primary hover:text-readnest-accent font-medium">
                        新規登録
                    </a>
                </p>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if (isset($_SESSION['AUTH_USER'])): ?>
<!-- ログイン済みユーザーのダッシュボード -->
<section class="bg-white dark:bg-gray-900 py-12 border-b dark:border-gray-700">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- 統合タブボックス：最近更新した本 / 作家クラウド -->
        <?php if (!empty($my_recent_books) || !empty($user_author_cloud_html)): ?>
        <div class="mb-12" x-data="{ activeTab: localStorage.getItem('indexActiveTab') || 'recent' }">
            <!-- タブヘッダー -->
            <div class="border-b border-gray-200 dark:border-gray-700 mb-6">
                <nav class="flex -mb-px space-x-8">
                    <?php if (!empty($my_recent_books)): ?>
                    <button @click="activeTab = 'recent'; localStorage.setItem('indexActiveTab', 'recent')"
                            :class="activeTab === 'recent' ? 'border-readnest-primary text-readnest-primary' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600'"
                            class="py-2 px-1 border-b-2 font-medium text-sm transition-colors">
                        <i class="fas fa-clock mr-2"></i>
                        最近更新した本
                    </button>
                    <?php endif; ?>
                    
                    <?php if (!empty($user_author_cloud_html)): ?>
                    <button @click="activeTab = 'authors'; localStorage.setItem('indexActiveTab', 'authors')"
                            :class="activeTab === 'authors' ? 'border-readnest-primary text-readnest-primary' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600'"
                            class="py-2 px-1 border-b-2 font-medium text-sm transition-colors">
                        <i class="fas fa-feather-alt mr-2"></i>
                        作家クラウド
                    </button>
                    <?php endif; ?>
                </nav>
            </div>
            
            <!-- タブコンテンツ -->
            <?php if (!empty($my_recent_books)): ?>
            <div x-show="activeTab === 'recent'" x-cloak>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                <?php foreach ($my_recent_books as $recent_book): ?>
                <a href="/book/<?php echo html($recent_book['book_id']); ?>" 
                   class="group bg-gray-50 dark:bg-gray-800 rounded-lg p-4 hover:bg-gray-100 dark:hover:bg-gray-700 transition-all hover:shadow-md">
                    <div class="flex items-start space-x-3">
                        <img src="<?php echo html(isset($recent_book['image_url']) && !empty($recent_book['image_url']) ? $recent_book['image_url'] : '/img/no-image-book.png'); ?>" 
                             alt="<?php echo html($recent_book['title']); ?>"
                             class="w-12 h-16 object-cover rounded shadow-sm group-hover:shadow-md transition-shadow"
                             onerror="this.src='/img/no-image-book.png'">
                        <div class="flex-1 min-w-0">
                            <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate group-hover:text-readnest-primary transition-colors">
                                <?php echo html($recent_book['title']); ?>
                            </h4>
                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                <?php echo html(isset($recent_book['author']) ? $recent_book['author'] : ''); ?>
                            </p>
                            <?php 
                            $status_labels = [
                                0 => ['いつか買う', 'text-gray-500'],
                                1 => ['積読', 'text-yellow-600'],
                                2 => ['読書中', 'text-blue-600'],
                                3 => ['読了', 'text-green-600'],
                                4 => ['昔読んだ', 'text-purple-600']
                            ];
                            $status_info = isset($status_labels[$recent_book['status']]) ? $status_labels[$recent_book['status']] : ['不明', 'text-gray-500'];
                            ?>
                            <div class="mt-1 flex items-center space-x-2">
                                <span class="text-xs <?php echo $status_info[1]; ?> font-medium">
                                    <?php echo $status_info[0]; ?>
                                </span>
                                <?php if ($recent_book['status'] == 2 && $recent_book['current_page'] > 0 && $recent_book['total_page'] > 0): ?>
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    <?php echo round(($recent_book['current_page'] / $recent_book['total_page']) * 100); ?>%
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
                </div>
                <div class="mt-4 text-center">
                    <a href="/bookshelf.php" class="text-sm text-readnest-primary hover:text-readnest-accent font-medium">
                        すべての本を見る <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($user_author_cloud_html)): ?>
            <div x-show="activeTab === 'authors'" x-cloak>
                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-6">
                    <?php echo $user_author_cloud_html; ?>
                    <div class="mt-4 text-center">
                        <a href="/my_authors.php" class="text-sm text-readnest-primary hover:text-readnest-accent font-medium">
                            すべての作家を見る <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- 個人統計情報 -->
        <div class="text-center mb-6 sm:mb-8">
            <h2 class="text-2xl sm:text-3xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">あなたの読書記録</h2>
        </div>
        
        <!-- 統計グリッド - レスポンシブ対応 -->
        <div class="grid grid-cols-2 xs:grid-cols-2 sm:grid-cols-3 tablet:grid-cols-6 gap-2 sm:gap-3 md:gap-4 text-center mb-6 sm:mb-8">
            <!-- モバイルでは最初の行に2つ -->
            <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-gray-800 dark:to-gray-700 rounded-lg p-2.5 sm:p-3 md:p-4">
                <div class="text-lg sm:text-xl md:text-2xl font-bold text-blue-600"><?php echo number_format(isset($user_stats['total_books']) ? $user_stats['total_books'] : 0); ?></div>
                <div class="text-xs sm:text-sm text-gray-700 dark:text-gray-300 mt-0.5 sm:mt-1">総読書数</div>
            </div>
            <a href="/bookshelf.php?status=<?php echo READING_NOW; ?>" 
               class="bg-gradient-to-br from-orange-50 to-orange-100 dark:from-gray-800 dark:to-gray-700 rounded-lg p-2.5 sm:p-3 md:p-4 hover:shadow-lg transition-all block group cursor-pointer relative overflow-hidden">
                <div class="absolute top-1 right-1 text-orange-400 opacity-50 group-hover:opacity-100 transition-opacity">
                    <i class="fas fa-arrow-right text-xs"></i>
                </div>
                <div class="text-lg sm:text-xl md:text-2xl font-bold text-orange-600 group-hover:scale-105 transition-transform"><?php echo number_format(isset($user_stats['reading_now']) ? $user_stats['reading_now'] : 0); ?></div>
                <div class="text-xs sm:text-sm text-gray-700 dark:text-gray-300 mt-0.5 sm:mt-1 group-hover:text-orange-700 dark:group-hover:text-orange-400 transition-colors">読書中</div>
            </a>
            
            <!-- タブレット以上では1行に5つ、モバイルでは2列目に3つ -->
            <a href="/bookshelf.php?status=<?php echo READING_FINISH; ?>&filter_year=<?php echo date('Y'); ?>" 
               class="bg-gradient-to-br from-green-50 to-green-100 dark:from-gray-800 dark:to-gray-700 rounded-lg p-2.5 sm:p-3 md:p-4 hover:shadow-lg transition-all block group cursor-pointer relative overflow-hidden">
                <div class="absolute top-1 right-1 text-green-400 opacity-50 group-hover:opacity-100 transition-opacity">
                    <i class="fas fa-arrow-right text-xs"></i>
                </div>
                <div class="text-lg sm:text-xl md:text-2xl font-bold text-green-600 group-hover:scale-105 transition-transform"><?php echo number_format(isset($user_stats['this_year_books']) ? $user_stats['this_year_books'] : 0); ?></div>
                <div class="text-xs sm:text-sm text-gray-700 dark:text-gray-300 mt-0.5 sm:mt-1 group-hover:text-green-700 dark:group-hover:text-green-400 transition-colors">
                    <span class="hidden xs:inline">今年読んだ本</span>
                    <span class="xs:hidden">今年</span>
                </div>
            </a>
            <a href="/bookshelf.php?status=<?php echo READING_FINISH; ?>&filter_month=<?php echo date('Y-m'); ?>" 
               class="bg-gradient-to-br from-purple-50 to-purple-100 dark:from-gray-800 dark:to-gray-700 rounded-lg p-2.5 sm:p-3 md:p-4 hover:shadow-lg transition-all block group cursor-pointer relative overflow-hidden">
                <div class="absolute top-1 right-1 text-purple-400 opacity-50 group-hover:opacity-100 transition-opacity">
                    <i class="fas fa-arrow-right text-xs"></i>
                </div>
                <div class="text-lg sm:text-xl md:text-2xl font-bold text-purple-600 group-hover:scale-105 transition-transform"><?php echo number_format(isset($user_stats['this_month_books']) ? $user_stats['this_month_books'] : 0); ?></div>
                <div class="text-xs sm:text-sm text-gray-700 dark:text-gray-300 mt-0.5 sm:mt-1 group-hover:text-purple-700 dark:group-hover:text-purple-400 transition-colors">
                    <span class="hidden xs:inline">今月読んだ本</span>
                    <span class="xs:hidden">今月</span>
                </div>
            </a>
            <div class="bg-gradient-to-br from-readnest-primary/10 to-readnest-accent/10 dark:from-gray-800 dark:to-gray-700 rounded-lg p-2.5 sm:p-3 md:p-4">
                <div class="text-lg sm:text-xl md:text-2xl font-bold text-readnest-primary"><?php echo number_format(isset($user_stats['total_pages']) ? $user_stats['total_pages'] : 0); ?></div>
                <div class="text-xs sm:text-sm text-gray-700 dark:text-gray-300 mt-0.5 sm:mt-1">
                    <span class="hidden xs:inline">総読書ページ</span>
                    <span class="xs:hidden">総ページ</span>
                </div>
            </div>
            <!-- レビュー数を追加 -->
            <a href="/my_reviews.php" 
               class="bg-gradient-to-br from-indigo-50 to-indigo-100 dark:from-gray-800 dark:to-gray-700 rounded-lg p-2.5 sm:p-3 md:p-4 hover:shadow-lg transition-all block group cursor-pointer relative overflow-hidden">
                <div class="absolute top-1 right-1 text-indigo-400 opacity-50 group-hover:opacity-100 transition-opacity">
                    <i class="fas fa-arrow-right text-xs"></i>
                </div>
                <div class="text-lg sm:text-xl md:text-2xl font-bold text-indigo-600 group-hover:scale-105 transition-transform"><?php echo number_format(isset($user_stats['total_reviews']) ? $user_stats['total_reviews'] : 0); ?></div>
                <div class="text-xs sm:text-sm text-gray-700 dark:text-gray-300 mt-0.5 sm:mt-1 group-hover:text-indigo-700 dark:group-hover:text-indigo-400 transition-colors">
                    <span class="hidden xs:inline">レビュー数</span>
                    <span class="xs:hidden">レビュー</span>
                </div>
            </a>
        </div>


        <!-- X連携の案内（未連携の場合のみ） - レスポンシブ対応 -->
        <?php 
        $user_info = getUserInformation($mine_user_id);
        if (!isset($user_info['x_oauth_token']) || empty($user_info['x_oauth_token'])): 
        ?>
        <div class="bg-gradient-to-r from-gray-900 to-gray-800 rounded-lg shadow-lg p-4 sm:p-6 mb-6 sm:mb-8 text-white">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4">
                <div class="flex items-start sm:items-center">
                    <div class="bg-white rounded-full p-2 sm:p-3 mr-3 sm:mr-4 flex-shrink-0">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6 md:w-8 md:h-8 text-black" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-base sm:text-lg md:text-xl font-bold mb-1">X（Twitter）連携で読書体験をシェア</h3>
                        <p class="text-xs sm:text-sm opacity-90">読書記録を自動でXに投稿して、フォロワーと読書体験を共有できます</p>
                    </div>
                </div>
                <a href="/account.php#x_settings" 
                   class="inline-flex items-center justify-center px-4 sm:px-5 py-2 sm:py-2.5 bg-white text-black font-medium rounded-full hover:bg-gray-100 transition-colors text-sm sm:text-base whitespace-nowrap self-start sm:self-auto">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5 mr-1.5 sm:mr-2" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                    </svg>
                    連携する
                </a>
            </div>
        </div>
        <?php endif; ?>

        <!-- 年間目標進捗 - レスポンシブ対応 -->
        <?php if (isset($yearly_goal) && isset($current_year) && isset($goal_progress_rate)): ?>
        <div class="bg-gradient-to-r from-readnest-primary to-readnest-accent rounded-lg shadow-lg p-4 sm:p-6 mb-8 sm:mb-12 text-white">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 sm:gap-6">
                <div>
                    <div class="flex items-start justify-between mb-2 gap-2 sm:gap-4">
                        <h3 class="text-lg sm:text-xl md:text-2xl font-bold"><?php echo $current_year; ?>年の読書目標</h3>
                        <a href="/account.php" 
                           class="inline-flex items-center px-2 sm:px-3 py-1 bg-white bg-opacity-20 hover:bg-opacity-30 rounded-md text-xs sm:text-sm font-medium transition-colors"
                           title="目標を変更">
                            <i class="fas fa-edit mr-1 text-xs"></i>
                            <span class="hidden xs:inline">変更</span>
                        </a>
                    </div>
                    <p class="text-sm sm:text-base md:text-lg opacity-90">
                        年間目標: <?php echo $yearly_goal; ?>冊
                        <?php if (!isset($user_info['books_per_year']) || $user_info['books_per_year'] <= 0): ?>
                        <span class="text-xs sm:text-sm ml-2">（デフォルト値）</span>
                        <?php endif; ?>
                    </p>
                    <?php if (!isset($user_info['books_per_year']) || $user_info['books_per_year'] <= 0): ?>
                    <p class="text-xs sm:text-sm opacity-75 mt-1">
                        <a href="/account.php" class="underline hover:no-underline">
                            アカウント設定から目標を設定できます
                        </a>
                    </p>
                    <?php endif; ?>
                </div>
                
                <div class="flex-1 max-w-2xl">
                    <!-- 進捗バー -->
                    <div class="mb-3">
                        <div class="flex justify-between text-xs sm:text-sm mb-1">
                            <span><?php echo $user_stats['this_year_books']; ?>冊 / <?php echo $yearly_goal; ?>冊</span>
                            <span><?php echo round($goal_progress_rate); ?>%</span>
                        </div>
                        <div class="w-full bg-white bg-opacity-30 rounded-full h-3 sm:h-4 overflow-hidden">
                            <div class="bg-white h-full rounded-full transition-all duration-500 relative" 
                                 style="width: <?php echo min(100, $goal_progress_rate); ?>%">
                                <?php if ($goal_progress_rate >= 100): ?>
                                <div class="absolute inset-0 bg-gradient-to-r from-yellow-400 to-yellow-300 rounded-full animate-pulse"></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if ($goal_progress_rate >= 100): ?>
                        <p class="text-xs sm:text-sm mt-2 text-yellow-200 font-semibold">
                            🎉 おめでとうございます！年間目標を達成しました！
                        </p>
                        <?php endif; ?>
                    </div>
                    
                    <!-- 進捗詳細 - モバイル対応 -->
                    <div class="grid grid-cols-1 xs:grid-cols-3 gap-2 sm:gap-4 text-xs sm:text-sm">
                        <div class="bg-white bg-opacity-20 rounded-lg p-2 sm:p-3">
                            <div class="font-semibold text-xs sm:text-sm">現在のペース</div>
                            <div class="text-xs sm:text-sm">
                                <?php if ($books_behind_or_ahead >= 0): ?>
                                    <span class="text-green-300">
                                        <span class="hidden sm:inline">予定より</span><?php echo abs(round($books_behind_or_ahead, 1)); ?>冊<span class="hidden xs:inline">先行</span>
                                    </span>
                                <?php else: ?>
                                    <span class="text-yellow-300">
                                        <span class="hidden sm:inline">予定より</span><?php echo abs(round($books_behind_or_ahead, 1)); ?>冊<span class="hidden xs:inline">遅れ</span>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="bg-white bg-opacity-20 rounded-lg p-2 sm:p-3">
                            <div class="font-semibold text-xs sm:text-sm">年間予測</div>
                            <div class="text-xs sm:text-sm">
                                <span class="hidden sm:inline">このペースなら</span>年間<?php echo $current_pace_yearly; ?>冊
                            </div>
                        </div>
                        
                        <div class="bg-white bg-opacity-20 rounded-lg p-2 sm:p-3">
                            <div class="font-semibold text-xs sm:text-sm">必要ペース</div>
                            <div class="text-xs sm:text-sm">月<?php echo $required_monthly_pace; ?>冊<span class="hidden xs:inline">で達成</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- 読書ペース分析 -->
        <?php if (isset($most_active_hour) && isset($completion_rate) && isset($avg_reading_speed) && isset($current_streak)): ?>
        <div class="bg-gradient-to-br from-indigo-50 to-purple-50 dark:from-gray-800 dark:to-gray-800 rounded-lg shadow-lg border border-indigo-200 dark:border-gray-700 p-4 mb-8">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 flex items-center">
                    <i class="fas fa-tachometer-alt mr-2 text-orange-600 text-sm"></i>
                    読書ペース分析
                </h3>
                <a href="/reading_insights.php?mode=pace" class="text-xs text-readnest-primary hover:text-readnest-accent">
                    詳細 <i class="fas fa-arrow-right ml-1 text-xs"></i>
                </a>
            </div>
            
            <div class="grid grid-cols-3 gap-3 mb-3">
                <!-- 最も活発な時間帯 -->
                <div class="text-center">
                    <div class="text-2xl font-bold text-orange-600">
                        <?php echo $most_active_hour; ?>時
                    </div>
                    <div class="text-xs text-gray-600 dark:text-gray-400">最も活発</div>
                </div>
                
                <!-- 完読率 -->
                <div class="text-center border-x border-gray-200">
                    <div class="text-2xl font-bold text-green-600">
                        <?php 
                        $overall = $completion_rate['overall'];
                        $total_started = $overall['completed'] + $overall['reading'] + $overall['not_started'];
                        echo $total_started > 0 ? round(($overall['completed'] / $total_started) * 100) : 0;
                        ?>%
                    </div>
                    <div class="text-xs text-gray-600 dark:text-gray-400">完読率</div>
                </div>
                
                <!-- 平均読書速度 -->
                <div class="text-center">
                    <div class="text-2xl font-bold text-blue-600">
                        <?php echo $avg_reading_speed; ?>
                    </div>
                    <div class="text-xs text-gray-600 dark:text-gray-400">ページ/日</div>
                </div>
            </div>
            
            <!-- 月間目標との関係を表示 -->
            <?php if (isset($monthly_goal_info) && $monthly_goal_info['goal'] > 0): ?>
            <?php
            // 現在の月間ペースを計算
            $days_passed = date('j');
            $current_monthly_pace = $days_passed > 0 ? $monthly_achievement / $days_passed : 0;
            
            // 必要ペースを計算
            $days_remaining = date('t') - $days_passed;
            $books_remaining = max(0, $monthly_goal_info['goal'] - $monthly_achievement);
            $required_pace = $days_remaining > 0 ? $books_remaining / $days_remaining : 0;
            
            // ペース判定
            $pace_status = 'on_track';
            if ($current_monthly_pace >= $required_pace * 1.1) {
                $pace_status = 'ahead';
            } elseif ($current_monthly_pace < $required_pace * 0.9) {
                $pace_status = 'behind';
            }
            ?>
            <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-600">
                <div class="flex justify-between items-center text-sm">
                    <div>
                        <span class="text-gray-600 dark:text-gray-400">月間目標達成に必要:</span>
                        <span class="font-semibold ml-1">
                            <?php if ($required_pace >= 1): ?>
                                日<?php echo number_format($required_pace, 1); ?>冊
                            <?php elseif ($required_pace > 0): ?>
                                <?php echo ceil(1 / $required_pace); ?>日に1冊
                            <?php else: ?>
                                達成済み
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="flex items-center">
                        <?php if ($pace_status === 'ahead'): ?>
                            <i class="fas fa-check-circle text-green-500 mr-1"></i>
                            <span class="text-green-600 text-xs">順調</span>
                        <?php elseif ($pace_status === 'behind'): ?>
                            <i class="fas fa-exclamation-circle text-yellow-500 mr-1"></i>
                            <span class="text-yellow-600 text-xs">要加速</span>
                        <?php else: ?>
                            <i class="fas fa-minus-circle text-blue-500 mr-1"></i>
                            <span class="text-blue-600 dark:text-blue-400 text-xs">標準</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- 読書カレンダー（シンプル版） -->
        <?php if (isset($reading_map) && isset($current_streak)): ?>
        <div class="bg-gradient-to-br from-emerald-50 to-teal-50 dark:from-gray-800 dark:to-gray-800 rounded-lg shadow-xl border border-emerald-200 dark:border-gray-700 p-6 mb-12">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                    <i class="fas fa-calendar-check text-emerald-600 mr-2"></i>
                    今月の読書カレンダー
                </h3>
                <a href="/reading_calendar.php" 
                   class="text-sm text-readnest-primary hover:text-readnest-accent font-medium">
                    詳細を見る <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
            
            <!-- カレンダー本体（シンプル版） -->
            <div class="mb-6">
                    <table class="w-full">
                        <thead>
                            <tr class="text-xs text-gray-500 dark:text-gray-400">
                                <th class="py-2 text-center w-[14.28%]">日</th>
                                <th class="py-2 text-center w-[14.28%]">月</th>
                                <th class="py-2 text-center w-[14.28%]">火</th>
                                <th class="py-2 text-center w-[14.28%]">水</th>
                                <th class="py-2 text-center w-[14.28%]">木</th>
                                <th class="py-2 text-center w-[14.28%]">金</th>
                                <th class="py-2 text-center w-[14.28%]">土</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $year = isset($current_year) ? $current_year : date('Y');
                            $month = isset($current_month) ? $current_month : date('n');
                            $first_day = mktime(0, 0, 0, $month, 1, $year);
                            $days_in_month = date('t', $first_day);
                            $day_of_week = date('w', $first_day);
                            $current_date = 1;
                            $today = date('Y-m-d');
                            
                            // 月の読書日数を計算
                            $reading_days_count = 0;
                            $total_days_passed = 0;
                            
                            for ($day = 1; $day <= $days_in_month; $day++) {
                                $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
                                if (isset($reading_map[$date])) {
                                    $reading_days_count++;
                                }
                                if (strtotime($date) <= time()) {
                                    $total_days_passed++;
                                }
                            }
                            
                            // 週ごとにループ
                            for ($week = 0; $week < 6; $week++) {
                                if ($current_date > $days_in_month) break;
                                
                                echo '<tr>';
                                for ($day = 0; $day < 7; $day++) {
                                    echo '<td class="p-1 text-center align-middle" style="width: 14.28%;">';
                                    
                                    if (($week == 0 && $day < $day_of_week) || $current_date > $days_in_month) {
                                        // 空のセル
                                        echo '<div class="w-8 h-8 lg:w-10 lg:h-10 mx-auto"></div>';
                                    } else {
                                        $date = sprintf('%04d-%02d-%02d', $year, $month, $current_date);
                                        $is_today = ($date === $today);
                                        $has_reading = isset($reading_map[$date]);
                                        $is_future = strtotime($date) > time();
                                        $book_count = isset($reading_map[$date]) ? $reading_map[$date]['book_count'] : 0;
                                        
                                        $cell_class = 'w-8 h-8 lg:w-10 lg:h-10 rounded-full flex items-center justify-center text-xs lg:text-sm relative group cursor-pointer mx-auto ';
                                        
                                        // 読書量に応じて色の濃淡を設定
                                        if ($is_today) {
                                            $cell_class .= 'ring-2 ring-yellow-400 font-bold ';
                                            if ($has_reading) {
                                                if ($book_count >= 5) {
                                                    $cell_class .= 'bg-emerald-600 text-white ';
                                                } elseif ($book_count >= 3) {
                                                    $cell_class .= 'bg-emerald-500 text-white ';
                                                } elseif ($book_count >= 2) {
                                                    $cell_class .= 'bg-emerald-400 text-white ';
                                                } else {
                                                    $cell_class .= 'bg-emerald-300 dark:bg-emerald-700 text-gray-800 dark:text-gray-100 ';
                                                }
                                            } else {
                                                $cell_class .= 'bg-yellow-50 dark:bg-yellow-900/20 text-gray-700 dark:text-gray-300 ';
                                            }
                                        } elseif ($has_reading) {
                                            // 読書量による色分け
                                            if ($book_count >= 5) {
                                                $cell_class .= 'bg-emerald-600 text-white ';
                                            } elseif ($book_count >= 3) {
                                                $cell_class .= 'bg-emerald-500 text-white ';
                                            } elseif ($book_count >= 2) {
                                                $cell_class .= 'bg-emerald-400 text-white ';
                                            } else {
                                                $cell_class .= 'bg-emerald-300 dark:bg-emerald-700 text-gray-800 dark:text-gray-100 ';
                                            }
                                        } elseif (!$is_future) {
                                            $cell_class .= 'bg-gray-50 dark:bg-gray-700 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-600 ';
                                        } else {
                                            $cell_class .= 'bg-gray-50 dark:bg-gray-800 text-gray-400 dark:text-gray-500 ';
                                        }
                                        
                                        echo '<div class="' . $cell_class . '">';
                                        echo $current_date;
                                        
                                        // ツールチップ
                                        if ($has_reading) {
                                            echo '<div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-1 px-2 py-1 bg-gray-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none whitespace-nowrap z-10">';
                                            echo date('n月j日', strtotime($date)) . ' - ' . $book_count . '冊';
                                            echo '</div>';
                                        }
                                        
                                        echo '</div>';
                                        
                                        $current_date++;
                                    }
                                    
                                    echo '</td>';
                                }
                                echo '</tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                    
                    <!-- 色の凡例 -->
                    <div class="mt-3 px-2">
                        <div class="flex flex-wrap items-center justify-center gap-x-2 sm:gap-x-3 gap-y-1 text-xs">
                            <span class="text-gray-600 dark:text-gray-400">読書量：</span>
                            <div class="flex items-center gap-1">
                                <div class="w-3 h-3 bg-emerald-300 rounded-full flex-shrink-0"></div>
                                <span class="whitespace-nowrap">1冊</span>
                            </div>
                            <div class="flex items-center gap-1">
                                <div class="w-3 h-3 bg-emerald-400 rounded-full flex-shrink-0"></div>
                                <span class="whitespace-nowrap">2冊</span>
                            </div>
                            <div class="flex items-center gap-1">
                                <div class="w-3 h-3 bg-emerald-500 rounded-full flex-shrink-0"></div>
                                <span class="whitespace-nowrap">3-4冊</span>
                            </div>
                            <div class="flex items-center gap-1">
                                <div class="w-3 h-3 bg-emerald-600 rounded-full flex-shrink-0"></div>
                                <span class="whitespace-nowrap">5冊以上</span>
                            </div>
                        </div>
                    </div>
            </div>
            
            <!-- 統計情報（横並び） -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- 今月の読書 -->
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm text-gray-600 dark:text-gray-400">今月の読書</span>
                            <span class="text-lg font-bold text-emerald-600">
                                <?php echo $reading_days_count; ?>日
                            </span>
                        </div>
                        <?php 
                        // 今月の日数
                        $progress_percentage = $days_in_month > 0 ? round(($reading_days_count / $days_in_month) * 100) : 0;
                        $days_remaining = max(0, $days_in_month - $reading_days_count);
                        ?>
                        <div class="mt-2">
                            <div class="flex justify-between text-xs text-gray-600 dark:text-gray-400 mb-1">
                                <span>今月の進捗</span>
                                <span><?php echo $progress_percentage; ?>%</span>
                            </div>
                            <div class="bg-gray-200 dark:bg-gray-600 rounded-full h-1.5 overflow-hidden">
                                <div class="bg-emerald-500 h-full transition-all duration-500" 
                                     style="width: <?php echo $progress_percentage; ?>%"></div>
                            </div>
                            <?php if ($days_remaining > 0): ?>
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                残り<?php echo $days_remaining; ?>日で全日読書を達成
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                        
                    <!-- 連続記録 -->
                    <div class="bg-orange-50 dark:bg-orange-900/20 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm text-gray-600 dark:text-gray-400">連続記録</span>
                            <div class="flex items-center">
                                <span class="text-lg font-bold text-orange-600 mr-1"><?php echo $current_streak; ?>日</span>
                                <?php if ($current_streak >= 7): ?>
                                    <i class="fas fa-fire text-orange-500 animate-pulse"></i>
                                <?php elseif ($current_streak >= 3): ?>
                                    <i class="fas fa-fire text-orange-500"></i>
                                <?php else: ?>
                                    <i class="fas fa-calendar-check text-orange-400"></i>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if ($current_streak > 0 && isset($streak_milestone)): ?>
                            <?php if ($streak_milestone['current']): ?>
                            <div class="text-xs text-orange-700 dark:text-orange-400 mb-2">
                                <i class="fas fa-<?php echo $streak_milestone['current']['icon']; ?> mr-1"></i>
                                <?php echo $streak_milestone['current']['title']; ?>達成！
                            </div>
                            <?php endif; ?>
                            <?php if ($streak_milestone['next']): ?>
                            <div class="mt-2">
                                <div class="flex justify-between text-xs text-gray-600 dark:text-gray-400 mb-1">
                                    <span>次: <?php echo $streak_milestone['next']['title']; ?></span>
                                    <span><?php echo $streak_milestone['progress']; ?>%</span>
                                </div>
                                <div class="bg-orange-200 dark:bg-orange-800 rounded-full h-1.5 overflow-hidden">
                                    <div class="bg-orange-500 h-full transition-all duration-500" style="width: <?php echo $streak_milestone['progress']; ?>%"></div>
                                </div>
                                <div class="text-xs text-orange-700 dark:text-orange-400 mt-1">
                                    あと<?php echo $streak_milestone['days_to_next']; ?>日で達成
                                </div>
                            </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                        
                    <!-- 今月のランキング -->
                    <?php if (isset($my_ranking_info) && $my_ranking_info): ?>
                    <div class="bg-purple-50 dark:bg-gray-800 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm text-gray-600 dark:text-gray-400">今月のランキング</span>
                            <a href="/ranking.php" 
                               class="text-purple-600 hover:text-purple-800"
                               title="ランキングを見る">
                                <i class="fas fa-external-link-alt text-sm"></i>
                            </a>
                        </div>
                        <div class="flex items-center mb-2">
                            <?php if ($my_ranking_info['rank'] !== '圈外' && $my_ranking_info['rank'] !== '-' && intval($my_ranking_info['rank']) <= 3): ?>
                                <i class="fas fa-trophy text-2xl mr-2 <?php echo intval($my_ranking_info['rank']) == 1 ? 'text-yellow-500' : (intval($my_ranking_info['rank']) == 2 ? 'text-gray-400' : 'text-amber-600'); ?>"></i>
                            <?php endif; ?>
                            <span class="text-lg font-bold text-purple-600">
                                <?php if ($my_ranking_info['rank'] === '圈外' || $my_ranking_info['rank'] === '-'): ?>
                                    <?php if ($my_ranking_info['book_count'] == 0): ?>
                                        未参加
                                    <?php else: ?>
                                        ランキング圈外
                                    <?php endif; ?>
                                <?php else: ?>
                                    <?php echo $my_ranking_info['rank']; ?>位
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="text-xs text-gray-600 dark:text-gray-400">
                            今月<?php echo $my_ranking_info['book_count']; ?>冊読了
                        </div>
                        <?php if ($my_ranking_info['rank'] !== '圈外' && $my_ranking_info['rank'] !== '-' && is_numeric($my_ranking_info['rank']) && intval($my_ranking_info['rank']) <= 10): ?>
                        <div class="text-xs text-purple-700 dark:text-purple-400 mt-1">
                            <i class="fas fa-chart-line mr-1"></i>TOP 10入り！
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                        
                    <!-- 月間目標 -->
                    <?php if (isset($monthly_goal_info) && isset($monthly_achievement)): ?>
                    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-2">
                                <span class="text-sm text-gray-600 dark:text-gray-400"><?php echo date('n'); ?>月の目標</span>
                                <?php if ($monthly_goal_info['type'] === 'custom'): ?>
                                <span class="text-xs bg-blue-200 dark:bg-blue-800 text-blue-700 dark:text-blue-300 px-2 py-0.5 rounded">カスタム</span>
                                <?php endif; ?>
                            </div>
                            <a href="/monthly_goals.php" 
                               class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300"
                               title="月間目標を設定">
                                <i class="fas fa-cog text-sm"></i>
                            </a>
                        </div>
                        <div class="mb-2">
                            <span class="text-lg font-bold text-blue-600">
                                <?php echo $monthly_achievement; ?>/<?php echo $monthly_goal_info['goal']; ?>冊
                            </span>
                        </div>
                        <div>
                            <div class="flex justify-between text-xs text-gray-600 dark:text-gray-400 mb-1">
                                <span>達成率</span>
                                <span><?php echo round($monthly_progress); ?>%</span>
                            </div>
                            <div class="bg-blue-200 dark:bg-blue-800 rounded-full h-1.5 overflow-hidden mb-2">
                                <div class="bg-blue-500 h-full transition-all duration-500" 
                                     style="width: <?php echo min(100, $monthly_progress); ?>%"></div>
                            </div>
                            <?php if ($monthly_progress >= 100): ?>
                            <p class="text-xs text-green-600">
                                <i class="fas fa-check-circle mr-1"></i>今月の目標達成！
                            </p>
                            <?php else: ?>
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                あと<?php echo max(0, $monthly_goal_info['goal'] - $monthly_achievement); ?>冊で達成
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- 読書グラフ -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-bold mb-6 text-center text-gray-900 dark:text-gray-100">読書統計</h2>
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- 年別読書数グラフ -->
                <div>
                    <h3 class="text-sm font-semibold mb-3 flex items-center text-gray-900 dark:text-gray-100">
                        <i class="fas fa-calendar-alt text-green-600 mr-2"></i>
                        年別読書数
                    </h3>
                    <div class="h-48">
                        <canvas id="yearlyChart"></canvas>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">過去5年間の読了冊数</p>
                </div>
                
                <!-- 月別読書数グラフ -->
                <div>
                    <h3 class="text-sm font-semibold mb-3 flex items-center text-gray-900 dark:text-gray-100">
                        <i class="fas fa-calendar text-blue-600 mr-2"></i>
                        月別読書数
                    </h3>
                    <div class="h-48">
                        <canvas id="monthlyChart"></canvas>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">過去12ヶ月の読了冊数</p>
                </div>
                
                <!-- 日別ページ数累積グラフ -->
                <div>
                    <h3 class="text-sm font-semibold mb-3 flex items-center">
                        <i class="fas fa-calendar-day text-purple-600 mr-2"></i>
                        読書ページ累積
                    </h3>
                    <div class="h-48">
                        <canvas id="dailyPagesChart"></canvas>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">
                        過去30日間のページ数累積
                        <span id="sampleDataNote" class="text-amber-600 ml-2" style="display: none;">（サンプルデータ）</span>
                    </p>
                </div>
            </div>
            
            <!-- 中央配置のリンク -->
            <div class="mt-6 text-center">
                <a href="/reading_insights.php?mode=overview" class="inline-flex items-center px-6 py-3 bg-blue-600 text-white text-base font-medium rounded-lg hover:bg-blue-700 transition-colors shadow-sm">
                    <i class="fas fa-chart-line mr-2"></i>詳細な統計を見る
                </a>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if (!isset($_SESSION['AUTH_USER'])): ?>
<!-- 未ログインユーザーの統計情報 -->
<section class="bg-white py-12 border-b">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 text-center">
            <div>
                <div class="text-3xl sm:text-4xl font-bold text-readnest-accent"><?php echo number_format(isset($total_books) ? $total_books : 45678); ?></div>
                <div class="text-gray-600 mt-2">登録書籍</div>
            </div>
            <div>
                <div class="text-3xl sm:text-4xl font-bold text-readnest-primary"><?php echo number_format(isset($total_reviews) ? $total_reviews : 8901); ?></div>
                <div class="text-gray-600 mt-2">レビュー</div>
            </div>
            <div>
                <div class="text-3xl sm:text-4xl font-bold text-readnest-accent"><?php echo number_format(isset($total_pages_read) ? $total_pages_read : 234567); ?></div>
                <div class="text-gray-600 mt-2">読了ページ</div>
            </div>
        </div>
    </div>
</section>

<!-- 機能紹介 -->
<section id="features" class="py-16 sm:py-24">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-4">主な機能</h2>
            <p class="text-xl text-gray-600">読書体験を豊かにする、さまざまな機能をご用意しています</p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- 本棚管理 -->
            <div class="text-center">
                <div class="w-20 h-20 bg-book-primary-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-book-open text-3xl text-book-primary-600"></i>
                </div>
                <h3 class="text-xl font-semibold mb-2">本棚管理</h3>
                <p class="text-gray-600">読みたい本、読んでいる本、読み終わった本を整理して管理できます</p>
            </div>
            
            <!-- 読書記録 -->
            <div class="text-center">
                <div class="w-20 h-20 bg-book-secondary-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-chart-line text-3xl text-book-secondary-600"></i>
                </div>
                <h3 class="text-xl font-semibold mb-2">読書記録</h3>
                <p class="text-gray-600">読書の進捗を記録し、グラフで可視化。モチベーション維持に役立ちます</p>
            </div>
            
            <!-- レビュー共有 -->
            <div class="text-center">
                <div class="w-20 h-20 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-comments text-3xl text-blue-600"></i>
                </div>
                <h3 class="text-xl font-semibold mb-2">レビュー共有</h3>
                <p class="text-gray-600">感想やレビューを投稿して、他の読者と交流できます</p>
            </div>
        </div>
        
        <!-- 新機能の追加 -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mt-8">
            <!-- 読書カレンダー -->
            <div class="text-center">
                <div class="w-20 h-20 bg-emerald-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-calendar-check text-3xl text-emerald-600"></i>
                </div>
                <h3 class="text-xl font-semibold mb-2">読書カレンダー</h3>
                <p class="text-gray-600">毎日の読書を記録して、習慣化をサポート。連続記録も一目でわかります</p>
            </div>
            
            <!-- AI機能 -->
            <div class="text-center">
                <div class="w-20 h-20 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-robot text-3xl text-purple-600"></i>
                </div>
                <h3 class="text-xl font-semibold mb-2">AI支援</h3>
                <p class="text-gray-600">AIが書評作成や本の推薦、自然な言葉での検索をサポート。あなたの読書をより豊かに</p>
            </div>
            
            <!-- 読書マップ -->
            <div class="text-center">
                <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-map-marked-alt text-3xl text-green-600"></i>
                </div>
                <h3 class="text-xl font-semibold mb-2">読書マップ</h3>
                <p class="text-gray-600">あなたの読書傾向を視覚的に表示。新しいジャンルの発見にも</p>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- AI機能紹介セクション -->
<section id="ai-features" class="bg-gradient-to-br from-purple-50 to-pink-50 dark:from-gray-900 dark:to-gray-900 py-4 sm:py-6" 
         x-data="{ 
             expanded: <?php echo (!isset($_SESSION['AUTH_USER']) || (isset($is_first_login) && $is_first_login)) ? 'true' : 'false'; ?>,
             toggleExpand() { 
                 this.expanded = !this.expanded;
                 if (typeof window !== 'undefined') {
                     localStorage.setItem('ai_features_expanded', this.expanded);
                 }
             },
             init() {
                 // 初回ログインでない場合はローカルストレージの値を使用
                 <?php if (!isset($is_first_login) || !$is_first_login): ?>
                 const saved = localStorage.getItem('ai_features_expanded');
                 if (saved !== null) {
                     this.expanded = saved === 'true';
                 }
                 <?php endif; ?>
             }
         }">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- ヘッダー部分（常に表示） -->
        <div class="text-center mb-4">
            <button @click="toggleExpand()" 
                    id="ai-features-button"
                    class="inline-flex items-center bg-gradient-to-r from-purple-600 to-pink-600 dark:from-gray-600 dark:to-gray-700 text-white px-6 py-3 rounded-full font-semibold hover:from-purple-700 hover:to-pink-700 dark:hover:from-gray-700 dark:hover:to-gray-800 transition-all shadow-lg group">
                <i class="fas fa-sparkles mr-2"></i>
                AI搭載機能
                <i class="fas fa-chevron-down ml-2 transition-transform duration-300" 
                   :class="expanded ? 'rotate-180' : ''"></i>
            </button>
            
            <!-- 折り畳み時の簡易説明 -->
            <div x-show="!expanded" x-transition class="mt-2">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    クリックしてAI機能の詳細を表示
                </p>
            </div>
        </div>
        
        <!-- 展開時のコンテンツ -->
        <div x-show="expanded" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform -translate-y-4"
             x-transition:enter-end="opacity-100 transform translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 transform translate-y-0"
             x-transition:leave-end="opacity-0 transform -translate-y-4"
             class="space-y-8">
            
            <div class="text-center">
                <div class="flex items-center justify-center mb-4">
                    <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 dark:text-gray-100">
                        AIがあなたの読書体験を豊かに
                    </h2>
                    <a href="/help.php#ai-features" class="ml-4 text-sm text-purple-600 dark:text-purple-400 hover:text-purple-700 dark:hover:text-purple-300 font-medium">
                        <i class="fas fa-question-circle mr-1"></i>詳しい使い方
                    </a>
                </div>
                <p class="text-xl text-gray-600 dark:text-gray-400 max-w-3xl mx-auto">
                    最新のAI技術で、書評作成から本の推薦まで、あなたの読書ライフを全面サポート
                </p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- AI検索機能 -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 transform hover:scale-105 transition-all">
                <div class="w-14 h-14 bg-gradient-to-r from-orange-500 to-amber-600 rounded-lg flex items-center justify-center mb-4">
                    <i class="fas fa-search text-white text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-3">AI検索</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-4">
                    自然な言葉で本を検索。「泣ける恋愛小説」「元気が出る本」など、気分やテーマから探せます。
                </p>
                <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-2">
                    <li><i class="fas fa-check text-green-500 mr-2"></i>自然な言葉で検索可能</li>
                    <li><i class="fas fa-check text-green-500 mr-2"></i>気分やテーマから探せる</li>
                    <li><i class="fas fa-check text-green-500 mr-2"></i>ジャンルで絞り込み</li>
                </ul>
            </div>
            
            <!-- AI推薦機能 NEW! -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 transform hover:scale-105 transition-all relative">
                <span class="absolute top-4 right-4 bg-red-500 text-white text-xs px-2 py-1 rounded-full font-bold animate-pulse">NEW!</span>
                <div class="w-14 h-14 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-lg flex items-center justify-center mb-4">
                    <i class="fas fa-robot text-white text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-3">AI推薦</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-4">
                    あなたの読書傾向を分析し、好みに合った本を提案。新しい本との出会いをサポートします。
                </p>
                <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-2">
                    <li><i class="fas fa-check text-green-500 mr-2"></i>読書履歴を分析</li>
                    <li><i class="fas fa-check text-green-500 mr-2"></i>類似度の高い本を提案</li>
                    <li><i class="fas fa-check text-green-500 mr-2"></i>リアルタイムで生成</li>
                </ul>
                <?php if (isset($_SESSION['AUTH_USER'])): ?>
                <a href="/recommendations.php" class="inline-block mt-4 bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition-colors text-sm font-medium">
                    <i class="fas fa-magic mr-2"></i>AI推薦を見る
                </a>
                <?php endif; ?>
            </div>
            
            <!-- AI書評アシスタント -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 transform hover:scale-105 transition-all">
                <div class="w-14 h-14 bg-gradient-to-r from-purple-500 to-purple-600 rounded-lg flex items-center justify-center mb-4">
                    <i class="fas fa-pen-fancy text-white text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-3">AI書評アシスタント</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-4">
                    簡単な感想を入力するだけで、AIが詳細で魅力的な書評を自動生成。書評作成の負担を軽減します。
                </p>
                <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-2">
                    <li><i class="fas fa-check text-green-500 mr-2"></i>感想から詳細な書評を生成</li>
                    <li><i class="fas fa-check text-green-500 mr-2"></i>書評の長さを自由に調整</li>
                    <li><i class="fas fa-check text-green-500 mr-2"></i>適切なタグを自動提案</li>
                </ul>
            </div>
            
            <!-- AIチャットアシスタント -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 transform hover:scale-105 transition-all">
                <div class="w-14 h-14 bg-gradient-to-r from-pink-500 to-pink-600 rounded-lg flex items-center justify-center mb-4">
                    <i class="fas fa-robot text-white text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-3">AIチャットアシスタント</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-4">
                    画面右下のチャットで、本に関する質問や相談が可能。読書の疑問を即座に解決します。
                </p>
                <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-2">
                    <li><i class="fas fa-check text-green-500 mr-2"></i>本に関する質問応答</li>
                    <li><i class="fas fa-check text-green-500 mr-2"></i>読書相談・アドバイス</li>
                    <li><i class="fas fa-check text-green-500 mr-2"></i>ページ内容に応じた提案</li>
                </ul>
            </div>
        </div>
        
            <!-- CTA -->
            <div class="text-center mt-12">
                <?php if (!isset($_SESSION['AUTH_USER'])): ?>
                <a href="/register.php" class="inline-flex items-center bg-gradient-to-r from-purple-600 to-pink-600 dark:from-gray-600 dark:to-gray-700 text-white px-8 py-3 rounded-full text-lg font-semibold hover:shadow-lg transform hover:scale-105 transition-all">
                    <i class="fas fa-rocket mr-2"></i>
                    今すぐAI機能を体験する
                </a>
                <?php else: ?>
                <a href="/bookshelf.php" class="inline-flex items-center bg-gradient-to-r from-purple-600 to-pink-600 dark:from-gray-600 dark:to-gray-700 text-white px-8 py-3 rounded-full text-lg font-semibold hover:shadow-lg transform hover:scale-105 transition-all">
                    <i class="fas fa-magic mr-2"></i>
                    AI機能を使ってみる
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- 最新の活動 -->
<?php if (isLatestActivitiesEnabled()): ?>
<section class="bg-gray-50 dark:bg-gray-900 py-6 sm:py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 dark:text-white text-center mb-12">最新の活動</h2>
        
        <?php 
        // みんなの読書活動は最新の活動セクション内で常に表示されるため、
        // isLatestActivitiesEnabled()がtrueの場合は必ず1つは表示される
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
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h3 class="text-xl font-semibold mb-4 flex items-center">
                    <i class="fas fa-comment text-book-primary-600 mr-2"></i>
                    新着レビュー
                </h3>
                <div id="new_review" class="space-y-3">
                    <?php if (!empty($new_reviews)): ?>
                        <?php foreach (array_slice($new_reviews, 0, 6) as $review): ?>
                        <div class="flex items-start space-x-3 p-2 hover:bg-gray-50 dark:hover:bg-gray-700 rounded">
                            <img src="<?php echo html(isset($review['user_photo']) && !empty($review['user_photo']) ? $review['user_photo'] : '/img/no-image-user.png'); ?>" 
                                 alt="<?php echo html($review['nickname']); ?>" 
                                 class="w-10 h-10 rounded-full object-cover flex-shrink-0"
                                 onerror="this.src='/img/no-image-user.png'">
                            <div class="flex-1 min-w-0">
                                <div class="text-sm">
                                    <a href="/profile.php?user_id=<?php echo html($review['user_id']); ?>" 
                                       class="font-medium text-gray-900 dark:text-gray-100 hover:text-readnest-primary dark:hover:text-readnest-accent">
                                        <?php echo html($review['nickname']); ?>
                                    </a>
                                    <span class="text-gray-600 dark:text-gray-400">さんが</span>
                                    <span class="inline-block px-2 py-1 bg-book-primary-100 dark:bg-book-primary-900/30 text-book-primary-800 dark:text-book-primary-300 rounded-full text-xs font-medium">
                                        <?php if (!empty($review['rating']) && $review['rating'] > 0): ?>
                                            <?php for ($i = 0; $i < $review['rating']; $i++): ?>★<?php endfor; ?>
                                        <?php else: ?>
                                            レビュー
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <div class="mt-1 flex items-center space-x-2">
                                    <img src="<?php echo html(isset($review['image_url']) && !empty($review['image_url']) ? $review['image_url'] : '/img/no-image-book.png'); ?>" 
                                         alt="<?php echo html($review['book_title']); ?>" 
                                         class="w-6 h-8 object-cover rounded shadow-sm"
                                         onerror="this.src='/img/no-image-book.png'">
                                    <a href="/book/<?php echo html($review['book_id']); ?>"
                                       class="text-sm text-gray-900 dark:text-gray-100 hover:text-readnest-primary dark:hover:text-readnest-accent line-clamp-1 flex-1">
                                        <?php echo html($review['book_title']); ?>
                                    </a>
                                </div>
                                <?php if (!empty($review['comment'])): ?>
                                <div class="text-xs text-gray-700 dark:text-gray-300 mt-1 line-clamp-2">
                                    <?php echo html($review['comment']); ?>
                                </div>
                                <?php endif; ?>
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    <?php 
                                    // タイムスタンプが有効かチェック
                                    if ($review['created_at'] && $review['created_at'] > 0) {
                                        echo formatDate($review['created_at'], 'Y年n月j日 H:i');
                                    } else {
                                        echo date('Y年n月j日 H:i');
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-gray-500 text-center">まだレビューがありません</p>
                    <?php endif; ?>
                </div>
                <div class="mt-4 text-center">
                    <a href="/reviews.php" class="text-book-primary-600 hover:text-book-primary-700 text-sm font-medium">
                        もっと見る <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- みんなの読書活動 -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h3 class="text-xl font-semibold mb-4 flex items-center">
                    <i class="fas fa-clock text-green-600 mr-2"></i>
                    みんなの読書活動
                </h3>
                <div id="recent_activities" class="space-y-3">
                    <?php if (!empty($formatted_activities)): ?>
                        <?php foreach (array_slice($formatted_activities, 0, 6) as $activity): ?>
                        <div class="flex items-start space-x-3 p-2 hover:bg-gray-50 dark:hover:bg-gray-700 rounded">
                            <img src="<?php echo html(isset($activity['user_photo']) && !empty($activity['user_photo']) ? $activity['user_photo'] : '/img/no-image-user.png'); ?>" 
                                 alt="<?php echo html(isset($activity['user_name']) ? $activity['user_name'] : 'ユーザー'); ?>" 
                                 class="w-10 h-10 rounded-full object-cover flex-shrink-0"
                                 onerror="this.src='/img/no-image-user.png'">
                            <div class="flex-1 min-w-0">
                                <div class="text-sm">
                                    <a href="/profile.php?user_id=<?php echo html(isset($activity['user_id']) ? $activity['user_id'] : ''); ?>" 
                                       class="font-medium text-gray-900 dark:text-gray-100 hover:text-readnest-primary dark:hover:text-readnest-accent">
                                        <?php echo html(isset($activity['user_name']) ? $activity['user_name'] : '名無しさん'); ?>
                                    </a>
                                    <?php if (isset($activity['user_level'])): ?>
                                        <?php echo getLevelBadgeHtml($activity['user_level'], 'xs'); ?>
                                    <?php endif; ?>
                                    <span class="text-gray-600 dark:text-gray-400">さんが</span>
                                    <?php
                                    $badge_colors = [
                                        'blue' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300',
                                        'yellow' => 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300',
                                        'green' => 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300',
                                        'gray' => 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300'
                                    ];
                                    $type_color = isset($activity['type_color']) ? $activity['type_color'] : 'gray';
                                    $badge_class = isset($badge_colors[$type_color]) ? $badge_colors[$type_color] : $badge_colors['gray'];
                                    ?>
                                    <span class="inline-block px-2 py-1 <?php echo $badge_class; ?> rounded-full text-xs font-medium">
                                        <?php echo html(isset($activity['type']) ? $activity['type'] : '更新'); ?>
                                    </span>
                                </div>
                                <div class="mt-1 flex items-center space-x-2">
                                    <img src="<?php echo html(isset($activity['book_image']) && !empty($activity['book_image']) ? $activity['book_image'] : '/img/no-image-book.png'); ?>" 
                                         alt="<?php echo html(isset($activity['book_title']) ? $activity['book_title'] : '本'); ?>" 
                                         class="w-6 h-8 object-cover rounded shadow-sm"
                                         onerror="this.src='/img/no-image-book.png'">
                                    <a href="/book/<?php echo html(isset($activity['book_id']) ? $activity['book_id'] : ''); ?>"
                                       class="text-sm text-gray-900 dark:text-gray-100 hover:text-readnest-primary dark:hover:text-readnest-accent line-clamp-1 flex-1">
                                        <?php echo html(isset($activity['book_title']) ? $activity['book_title'] : 'タイトル不明'); ?>
                                    </a>
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    <?php echo html(isset($activity['activity_date']) ? $activity['activity_date'] : ''); ?>
                                    <?php if (!empty($activity['page'])): ?>
                                        <span class="ml-2"><?php echo html($activity['page']); ?>ページ</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-gray-500 dark:text-gray-400 text-center">まだ活動がありません</p>
                    <?php endif; ?>
                </div>
                <?php if (!empty($formatted_activities) && count($formatted_activities) > 6): ?>
                <div class="mt-4 text-center">
                    <a href="/activities.php" class="text-green-600 hover:text-green-700 text-sm font-medium">
                        もっと見る <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- 読書中の本 -->
            <?php if (isPopularBooksEnabled()): ?>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h3 class="text-xl font-semibold mb-4 flex items-center">
                    <i class="fas fa-book-reader text-book-secondary-600 mr-2"></i>
                    みんなが読んでいる本
                </h3>
                <div id="read_book" class="grid grid-cols-3 gap-3">
                    <?php if (!empty($reading_books)): ?>
                        <?php foreach ($reading_books as $book): ?>
                        <div class="relative group">
                            <a href="<?php echo !empty($book['amazon_id']) ? '/book_entity/' . urlencode($book['amazon_id']) : '/book/' . html($book['book_id']); ?>" 
                               class="block">
                                <img src="<?php echo html(!empty($book['image_url']) && strpos($book['image_url'], 'noimage') === false ? $book['image_url'] : '/img/no-image-book.png'); ?>" 
                                     alt="<?php echo html($book['title']); ?>" 
                                     class="w-full h-32 object-cover rounded shadow-sm group-hover:opacity-80 transition-opacity"
                                     title="<?php echo html($book['title']); ?>"
                                     onerror="this.src='/img/no-image-book.png'">
                                <div class="absolute bottom-0 right-0 bg-black bg-opacity-70 text-white px-2 py-1 rounded-tl text-xs font-medium">
                                    <i class="fas fa-bookmark mr-1"></i><?php echo intval($book['bookmark_count']); ?>
                                </div>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-gray-500 dark:text-gray-400 text-center col-span-3">まだデータがありません</p>
                    <?php endif; ?>
                </div>
                <div class="mt-4 text-center">
                    <a href="/popular_book.php" class="text-readnest-primary hover:text-readnest-accent text-sm font-medium">
                        もっと見る <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- 人気のタグ -->
            <?php if (isPopularTagsEnabled()): ?>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h3 class="text-xl font-semibold mb-4 flex items-center">
                    <i class="fas fa-tags text-purple-600 mr-2"></i>
                    人気のタグ
                </h3>
                <div class="flex flex-wrap gap-2 max-h-48 overflow-y-auto">
                    <?php if (!empty($popular_tags)): ?>
                        <?php foreach ($popular_tags as $tag): ?>
                        <a href="/search_book_by_tag.php?tag=<?php echo urlencode($tag['tag_name']); ?>" 
                           class="inline-block bg-purple-100 dark:bg-gray-700 text-purple-700 dark:text-gray-300 px-3 py-1 rounded-full text-xs hover:bg-purple-200 dark:hover:bg-gray-600 transition-colors">
                            <?php echo html($tag['tag_name']); ?>
                        </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-gray-500 dark:text-gray-400 text-sm">まだタグが登録されていません</p>
                    <?php endif; ?>
                </div>
                <?php if (!empty($popular_tags)): ?>
                <div class="mt-4 text-center">
                    <a href="/tag_cloud.php" class="text-purple-600 dark:text-purple-400 hover:text-purple-700 dark:hover:text-purple-300 text-sm font-medium">
                        すべてのタグを見る <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <!-- 人気の作家 -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h3 class="text-xl font-semibold mb-4 flex items-center">
                    <i class="fas fa-users text-indigo-600 mr-2"></i>
                    人気の作家
                </h3>
                
                <?php
                // 作家クラウドのプレビューを表示
                require_once(dirname(dirname(__DIR__)) . '/library/sakka_cloud_generator.php');
                $author_generator = new SakkaCloudGenerator();
                $preview_authors = $author_generator->getPopularAuthors(20); // 上位20名を取得
                
                if (!empty($preview_authors)):
                    // 最新更新順にソート（last_read_dateで並び替え）
                    usort($preview_authors, function($a, $b) {
                        return strtotime($b['last_read_date']) - strtotime($a['last_read_date']);
                    });
                    
                    // 最大値と最小値を取得（フォントサイズ計算用）
                    $maxCount = max(array_column($preview_authors, 'reader_count'));
                    $minCount = min(array_column($preview_authors, 'reader_count'));
                    
                    // カラーパレット
                    $colors = [
                        'from-blue-500 to-blue-600',
                        'from-purple-500 to-purple-600',
                        'from-pink-500 to-pink-600',
                        'from-indigo-500 to-indigo-600',
                        'from-teal-500 to-teal-600',
                        'from-emerald-500 to-emerald-600'
                    ];
                ?>
                
                <div class="author-cloud-preview text-center mb-4" style="line-height: 2.2;">
                    <?php 
                    // 最初の15名のみ表示（チラ見せ）
                    foreach (array_slice($preview_authors, 0, 15) as $index => $author):
                        $count = $author['reader_count'];
                        
                        // フォントサイズを計算（10px〜20px）
                        if ($maxCount > $minCount) {
                            $ratio = ($count - $minCount) / ($maxCount - $minCount);
                            $size = 10 + (10 * sqrt($ratio));
                        } else {
                            $size = 12;
                        }
                        
                        // カラーをランダムに選択
                        $colorClass = $colors[array_rand($colors)];
                    ?>
                        <a href="/author.php?name=<?php echo urlencode($author['author']); ?>" 
                           class="inline-block px-2 py-1 m-1 rounded transition-all duration-300 hover:scale-110 bg-gradient-to-r <?php echo $colorClass; ?> text-white"
                           style="font-size: <?php echo $size; ?>px;"
                           title="<?php echo htmlspecialchars($author['author']); ?> (<?php echo number_format($author['reader_count']); ?>人が読書中)">
                            <?php echo htmlspecialchars($author['author']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                
                <?php endif; ?>
                
                <div class="text-center">
                    <a href="/sakka_cloud.php" class="inline-block px-3 py-1.5 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700 transition">
                        <i class="fas fa-cloud mr-1"></i>
                        すべての作家を見る
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
<?php endif; // パフォーマンステストのため一時的に無効化終了 ?>

<!-- CTA セクション - 未ログイン時のみ表示 -->
<?php if (!isset($_SESSION['AUTH_USER'])): ?>
<section class="bg-readnest-primary text-white py-16">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl sm:text-4xl font-bold mb-4">
            今すぐ始めましょう
        </h2>
        <p class="text-xl mb-8 opacity-90">
            無料で登録して、読書の楽しさを共有しましょう
        </p>
        <a href="/register.php" class="btn bg-white text-readnest-primary hover:bg-readnest-beige px-8 py-3 text-lg font-semibold shadow-lg">
            <i class="fas fa-user-plus mr-2"></i>無料アカウント作成
        </a>
    </div>
</section>
<?php endif; ?>

<!-- 追加のスクリプト -->
<?php
ob_start();
?>
<?php if (isset($_SESSION['AUTH_USER'])): ?>
<!-- Chart.js for reading statistics -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 月別読書数データ
    const monthlyData = <?php echo json_encode($monthly_stats); ?>;
    const yearlyData = <?php echo json_encode($yearly_progress); ?>;
    const dailyProgress = <?php echo json_encode(isset($daily_progress) ? $daily_progress : []); ?>;
    
    
    // 月別読書数グラフ
    if (document.getElementById('monthlyChart')) {
        try {
            // 過去12ヶ月のラベルを生成
            const monthLabels = [];
            const monthCounts = [];
            const currentDate = new Date();
            
            for (let i = 11; i >= 0; i--) {
                const date = new Date(currentDate.getFullYear(), currentDate.getMonth() - i, 1);
                const monthKey = date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0');
                const monthLabel = (date.getMonth() + 1) + '月';
                
                monthLabels.push(monthLabel);
                
                // データから該当月の読書数を取得
                const monthData = monthlyData.find(d => d.month === monthKey);
                monthCounts.push(monthData ? parseInt(monthData.count) : 0);
            }
            
            new Chart(document.getElementById('monthlyChart'), {
            type: 'bar',
            data: {
                labels: monthLabels,
                datasets: [{
                    label: '読書数',
                    data: monthCounts,
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.8)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
        } catch (error) {
            console.error('Error creating monthly chart:', error);
        }
    }
    
    // 年別読書数グラフ
    if (document.getElementById('yearlyChart')) {
        try {
            const yearLabels = yearlyData.map(d => d.year + '年');
            const yearCounts = yearlyData.map(d => parseInt(d.count));
            
            new Chart(document.getElementById('yearlyChart'), {
            type: 'bar',
            data: {
                labels: yearLabels,
                datasets: [{
                    label: '読書数',
                    data: yearCounts,
                    backgroundColor: [
                        'rgba(34, 197, 94, 0.8)',
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(168, 85, 247, 0.8)',
                        'rgba(249, 115, 22, 0.8)',
                        'rgba(239, 68, 68, 0.8)'
                    ],
                    borderColor: [
                        'rgb(34, 197, 94)',
                        'rgb(59, 130, 246)',
                        'rgb(168, 85, 247)',
                        'rgb(249, 115, 22)',
                        'rgb(239, 68, 68)'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
        } catch (error) {
            console.error('Error creating yearly chart:', error);
        }
    }
    
    // 日別ページ数累積グラフ
    if (document.getElementById('dailyPagesChart')) {
        try {
            // 過去30日のラベルとデータを生成
            const dailyLabels = [];
            const dailyPages = [];
            const currentDate = new Date();
            
            // dailyProgressデータを日付でソート
            let cumulativePages = 0;
            
            for (let i = 29; i >= 0; i--) {
                const date = new Date(currentDate.getTime() - (i * 24 * 60 * 60 * 1000));
                const dateKey = date.getFullYear() + '-' + 
                              String(date.getMonth() + 1).padStart(2, '0') + '-' + 
                              String(date.getDate()).padStart(2, '0');
                
                dailyLabels.push((date.getMonth() + 1) + '/' + date.getDate());
                
                // dailyProgressから該当日のページ数を取得
                const dayData = dailyProgress.find(d => d.date === dateKey);
                if (dayData) {
                    cumulativePages = parseInt(dayData.cumulative_pages) || cumulativePages;
                }
                dailyPages.push(cumulativePages);
            }
            
            // データがない場合の処理
            const hasData = dailyProgress.length > 0 && dailyPages.some(p => p > 0);
            if (!hasData) {
                // データがない場合はグラフを非表示にしてメッセージを表示
                document.getElementById('dailyPagesChart').style.display = 'none';
                const noDataMessage = document.createElement('div');
                noDataMessage.className = 'text-center text-gray-500 py-8';
                noDataMessage.innerHTML = '<i class="fas fa-book-open text-4xl mb-2"></i><p>まだ読書データがありません</p><p class="text-sm">本を追加して読書を始めましょう</p>';
                document.getElementById('dailyPagesChart').parentElement.appendChild(noDataMessage);
                return;
            }
            
            new Chart(document.getElementById('dailyPagesChart'), {
                type: 'line',
                data: {
                    labels: dailyLabels,
                    datasets: [{
                        label: '累積ページ数',
                        data: dailyPages,
                        borderColor: 'rgb(168, 85, 247)',
                        backgroundColor: 'rgba(168, 85, 247, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: false,
                            min: Math.max(0, Math.min(...dailyPages) - 50),
                            max: Math.max(...dailyPages) + 50,
                            ticks: {
                                callback: function(value) {
                                    return value.toLocaleString();
                                }
                            }
                        },
                        x: {
                            ticks: {
                                maxTicksLimit: 10
                            }
                        }
                    }
                }
            });
        } catch (error) {
            console.error('Error creating daily pages chart:', error);
        }
    }
});
</script>
<?php endif; ?>

<script>
// ページ読み込み後に最新情報を定期的に更新
document.addEventListener('DOMContentLoaded', function() {
    // 30秒ごとに最新情報を更新（必要に応じて）
    /*
    setInterval(async () => {
        try {
            // 新着レビューを更新
            const reviewResponse = await fetch('/new_created_review.php');
            const reviewHtml = await reviewResponse.text();
            document.getElementById('new_review').innerHTML = reviewHtml;
            
            // 読書中の本を更新
            const bookResponse = await fetch('/new_read_books.php');
            const bookHtml = await bookResponse.text();
            document.getElementById('read_book').innerHTML = bookHtml;
            
            // タグクラウドを更新
            const tagResponse = await fetch('/new_tag_cloud.php');
            const tagHtml = await tagResponse.text();
            document.getElementById('tag_cloud').innerHTML = tagHtml;
        } catch (error) {
            console.error('Update error:', error);
        }
    }, 30000);
    */
});

// スムーズスクロール for AI機能を見るボタン
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
});
</script>
<?php
$d_additional_scripts = ob_get_clean();

$d_content = ob_get_clean();

// ベーステンプレートを使用
include(__DIR__ . '/t_base.php');
?>