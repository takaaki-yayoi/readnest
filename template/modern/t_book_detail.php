<?php
if(!defined('CONFIG')) {
  error_log('direct access detected. file:' . __FILE__ . ' line:' . __LINE__ . ' ip:' . $_SERVER['REMOTE_ADDR'] . ' agent:' . $_SERVER['HTTP_USER_AGENT']);
  die('reference for this file is not allowed.');
}

// フォームヘルパーを読み込み
require_once(dirname(dirname(__DIR__)) . '/library/form_helpers.php');

// 追加のヘッド要素
ob_start();
?>
<style>
    [x-cloak] { display: none !important; }
</style>
<?php
// 画像URLの処理
$og_image_url = '/img/no-image-book.png';
if (!empty($book['image_url'])) {
    $image_url = $book['image_url'];
    // 無効な画像URLパターンをチェック
    $invalid_patterns = ['noimage', 'no-image', 'no_image', 'not-available', 'NULL'];
    $is_invalid = false;
    foreach ($invalid_patterns as $pattern) {
        if (stripos($image_url, $pattern) !== false) {
            $is_invalid = true;
            break;
        }
    }
    
    if (!$is_invalid) {
        // HTTPSまたはHTTPで始まる場合はそのまま使用
        if (strpos($image_url, 'https://') === 0 || strpos($image_url, 'http://') === 0) {
            $og_image_url = $image_url;
        } 
        // //で始まる場合はhttps:を追加
        elseif (strpos($image_url, '//') === 0) {
            $og_image_url = 'https:' . $image_url;
        }
        // /で始まる場合は相対パス
        elseif (strpos($image_url, '/') === 0) {
            $og_image_url = 'https://readnest.jp' . $image_url;
        }
        // それ以外（相対パス）
        else {
            $og_image_url = 'https://readnest.jp/' . $image_url;
        }
    }
}

// デフォルト画像の場合も完全なURLにする
if ($og_image_url === '/img/no-image-book.png') {
    $og_image_url = 'https://readnest.jp/img/no-image-book.png';
}

// タイトルと著者の取得
$og_title = isset($book['title']) ? $book['title'] : '本の詳細';
$og_author = isset($book['author']) ? $book['author'] : '作者不明';

// 説明文をOpen Graph用に取得
$og_book_description = '';
if (!empty($book['amazon_id']) || !empty($book['isbn'])) {
    $desc_sql = "SELECT description FROM b_book_repository WHERE asin = ?";
    $desc_result = $g_db->getOne($desc_sql, [$book['amazon_id'] ?? $book['isbn'] ?? '']);
    if (!DB::isError($desc_result) && !empty($desc_result)) {
        // 説明文を150文字に制限してOG用に使用
        $og_book_description = mb_substr(strip_tags($desc_result), 0, 150);
    }
}

// OG用の説明文を構成
if (!empty($og_book_description)) {
    $og_description = $og_book_description . '... | ' . $og_author . '著 | ReadNest';
} else {
    $og_description = $og_author . 'の作品『' . $og_title . '』。ReadNestで読書記録を管理しよう。';
}
?>
<!-- Open Graph -->
<meta property="og:type" content="book">
<meta property="og:title" content="<?php echo html($og_title . ' - ' . $og_author); ?>">
<meta property="og:description" content="<?php echo html($og_description); ?>">
<meta property="og:image" content="<?php echo html($og_image_url); ?>">
<meta property="og:image:secure_url" content="<?php echo html($og_image_url); ?>">
<meta property="og:url" content="https://readnest.jp/book/<?php echo html(isset($book['book_id']) ? $book['book_id'] : ''); ?>">

<!-- Twitter Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:site" content="@dokusho">
<meta name="twitter:title" content="<?php echo html($og_title . ' - ' . $og_author); ?>">
<meta name="twitter:description" content="<?php echo html($og_description); ?>">
<meta name="twitter:image" content="<?php echo html($og_image_url); ?>">

<!-- CSRF Token -->
<?php
if (isset($_SESSION['AUTH_USER'])) {
    if (!function_exists('generateCSRFToken')) {
        require_once(dirname(dirname(dirname(__FILE__))) . '/library/csrf.php');
    }
    $csrf_token = generateCSRFToken();
    echo '<meta name="csrf-token" content="' . htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') . '">';
}
?>

<!-- AI機能用のメタデータ -->
<meta name="book-title" content="<?php echo html(isset($book['title']) ? $book['title'] : ''); ?>">
<meta name="book-author" content="<?php echo html(isset($book['author']) ? $book['author'] : ''); ?>">
<meta name="user-review" content="<?php echo html(isset($user_book_info['memo']) ? $user_book_info['memo'] : ''); ?>">

<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-5ZF3NGQ4QT"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', 'G-5ZF3NGQ4QT');
</script>
<?php
$d_additional_head = ob_get_clean();

// コンテンツ部分を生成
ob_start();
?>

<div class="bg-gray-50 dark:bg-gray-900 min-h-screen py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- パンくずリスト -->
        <nav class="mb-8" aria-label="Breadcrumb">
            <ol class="flex items-center space-x-2 text-sm">
                <li>
                    <a href="/" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                        <i class="fas fa-home"></i>
                    </a>
                </li>
                <li class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400 dark:text-gray-500 mx-2"></i>
                    <a href="/bookshelf.php" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">本棚</a>
                </li>
                <li class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400 dark:text-gray-500 mx-2"></i>
                    <span class="text-gray-700 dark:text-gray-300"><?php echo html(isset($book['title']) ? $book['title'] : '本の詳細'); ?></span>
                </li>
            </ol>
        </nav>
        
        <?php if (isset($show_progress_success) && $show_progress_success): ?>
        <!-- 進捗更新成功メッセージ -->
        <div class="mb-6 bg-emerald-50 border border-emerald-200 rounded-lg p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-check-circle text-2xl text-emerald-600"></i>
                </div>
                <div class="ml-3 flex-1">
                    <h3 class="text-sm font-medium text-emerald-800">
                        読書進捗を更新しました！
                        <?php if ($progress_page > 0): ?>
                        （<?php echo $progress_page; ?>ページまで）
                        <?php endif; ?>
                    </h3>
                    <p class="mt-1 text-sm text-emerald-700">
                        今日も読書お疲れさまでした。この調子で読書習慣を続けましょう！
                        <a href="/reading_calendar.php" class="ml-2 font-medium underline hover:text-emerald-900">
                            <i class="fas fa-calendar-check mr-1"></i>読書カレンダーで記録を確認
                        </a>
                    </p>
                </div>
                <div class="ml-4 flex-shrink-0">
                    <button onclick="this.parentElement.parentElement.parentElement.style.display='none'" 
                            class="text-emerald-400 hover:text-emerald-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
            <!-- 本の基本情報 -->
            <div class="p-6 lg:p-8">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- 左側：画像と基本情報のみ -->
                    <div class="lg:col-span-1">
                        <div class="flex flex-col items-center space-y-6">
                            <!-- 表紙画像 -->
                            <div class="relative">
                                <img src="<?php echo html(isset($book['image_url']) ? $book['image_url'] : '/img/no-image-book.png'); ?>" 
                                     alt="<?php echo html(isset($book['title']) ? $book['title'] : ''); ?>" 
                                     class="w-64 h-80 object-cover rounded-lg shadow-lg"
                                     loading="eager"
                                     fetchpriority="high"
                                     onerror="this.onerror=null; this.src='/img/no-image-book.png';">
                                <?php if ($login_flag && $is_book_owner): ?>
                                <!-- 画像編集ボタン -->
                                <a href="/change_book_image.php?book_id=<?php echo $book['book_id']; ?>" 
                                   class="absolute top-2 right-2 bg-white dark:bg-gray-700 bg-opacity-90 dark:bg-opacity-90 text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 p-3 rounded-full shadow-md transition-colors group"
                                   title="表紙画像を変更">
                                    <i class="fas fa-camera text-lg"></i>
                                    <!-- ホバー時の説明 -->
                                    <span class="absolute top-full right-0 mt-2 px-3 py-1 bg-gray-800 text-white text-sm rounded-lg opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none">
                                        表紙画像を変更
                                    </span>
                                </a>
                                
                                <!-- お気に入りボタン -->
                                <button onclick="toggleFavorite(<?php echo $book['book_id']; ?>, this)"
                                        class="absolute top-2 left-2 bg-white dark:bg-gray-700 bg-opacity-90 dark:bg-opacity-90 text-yellow-500 hover:text-yellow-600 p-3 rounded-full shadow-md transition-all group"
                                        title="<?php echo $is_favorite ? 'お気に入りから削除' : 'お気に入りに追加'; ?>">
                                    <i class="<?php echo $is_favorite ? 'fas' : 'far'; ?> fa-star text-lg group-hover:scale-110 transition-transform"></i>
                                    <!-- ホバー時の説明 -->
                                    <span class="absolute top-full left-0 mt-2 px-3 py-1 bg-gray-800 text-white text-sm rounded-lg opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none">
                                        <?php echo $is_favorite ? 'お気に入りから削除' : 'お気に入りに追加'; ?>
                                    </span>
                                </button>
                                <?php endif; ?>
                            </div>
                            
                            <!-- AI推薦への案内（上部に目立つように配置） -->
                            <?php if (!empty($ai_recommendations)): ?>
                            <div class="mb-4 p-3 bg-gradient-to-r from-purple-50 to-pink-50 rounded-lg border border-purple-200">
                                <a href="#ai-recommendations" class="flex items-center justify-center space-x-2 text-purple-700 hover:text-purple-900 transition-colors">
                                    <i class="fas fa-robot text-purple-600"></i>
                                    <span class="text-sm font-medium">この本に似た<?php echo count($ai_recommendations); ?>冊のAI推薦あり</span>
                                    <i class="fas fa-chevron-down text-xs animate-bounce"></i>
                                </a>
                            </div>
                            <?php endif; ?>
                            
                            <!-- 基本情報（コンパクト） -->
                            <div class="text-center">
                                <h1 class="text-xl font-bold text-gray-900 dark:text-white mb-2"><?php echo html(isset($book['title']) ? $book['title'] : ''); ?></h1>

                                <!-- 著者名表示 -->
                                <div class="text-lg text-gray-700 dark:text-gray-300 mb-3">
                                    <?php if (!empty($book['author'])): ?>
                                        <div class="flex items-center justify-center space-x-2">
                                            <a href="/author.php?name=<?php echo urlencode($book['author']); ?>"
                                               class="text-readnest-primary hover:text-readnest-primary-dark underline transition-colors"
                                               title="<?php echo html($book['author']); ?>の作家情報を見る">
                                                <?php echo html($book['author']); ?>
                                            </a>
                                            <?php if (isset($is_book_owner) && $is_book_owner): ?>
                                            <button onclick="showAuthorEditForm()" class="text-blue-600 hover:text-blue-800 text-sm" title="著者を編集">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    <?php elseif (isset($is_book_owner) && $is_book_owner): ?>
                                        <div class="flex items-center justify-center space-x-2">
                                            <span class="text-gray-500 dark:text-gray-400">著者未設定</span>
                                            <button onclick="showAuthorEditForm()" class="text-blue-600 hover:text-blue-800 text-sm" title="著者を編集">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (isset($is_book_owner) && $is_book_owner): ?>
                                    <!-- 編集フォーム -->
                                    <div id="authorEditForm" style="display: none;">
                                        <form action="" method="post" class="flex items-center justify-center space-x-2 mt-2">
                                            <?php echo csrfField(); ?>
                                            <input type="hidden" name="action" value="update_author">
                                            <input type="hidden" name="book_id" value="<?php echo html($book['book_id']); ?>">
                                            <input type="text" name="author" value="<?php echo html($book['author'] ?? ''); ?>"
                                                   class="w-48 px-3 py-1 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded text-base focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400"
                                                   placeholder="著者名" maxlength="100" required>
                                            <button type="submit" class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm" title="保存">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button type="button" onclick="hideAuthorEditForm()" class="px-3 py-1 bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-gray-200 rounded hover:bg-gray-400 dark:hover:bg-gray-500 text-sm" title="キャンセル">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </form>
                                    </div>
                                    <script>
                                    function showAuthorEditForm() {
                                        document.getElementById('authorEditForm').style.display = 'block';
                                        document.getElementById('authorEditForm').querySelector('input[name="author"]').focus();
                                    }
                                    function hideAuthorEditForm() {
                                        document.getElementById('authorEditForm').style.display = 'none';
                                    }
                                    </script>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- みんなの評価ページへのリンク -->
                                <?php
                                // ASINやISBNがある場合はそれを使用、なければbook_idを使用
                                $entity_id = null;
                                if (!empty($book['amazon_id'])) {
                                    $entity_id = $book['amazon_id'];
                                } elseif (!empty($book['isbn'])) {
                                    $entity_id = $book['isbn'];
                                } elseif (!empty($book['isbn10'])) {
                                    $entity_id = $book['isbn10'];
                                } elseif (!empty($book['isbn13'])) {
                                    $entity_id = $book['isbn13'];
                                } elseif (!empty($book['book_id'])) {
                                    $entity_id = 'book_' . $book['book_id'];
                                }
                                ?>
                                <?php if (!empty($entity_id)): ?>
                                    <div class="mt-3">
                                        <a href="/book_entity/<?php echo urlencode($entity_id); ?>"
                                           class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-purple-500 to-pink-500 text-white rounded-lg hover:from-purple-600 hover:to-pink-600 transition-all text-sm font-medium">
                                            <i class="fas fa-users mr-2"></i>
                                            みんなの評価・レビューを見る
                                        </a>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- 本の所有者情報（他のユーザーの本の場合のみ表示） -->
                                <?php if ($book_owner_info && (!$login_flag || $mine_user_id !== $book['user_id'])): ?>
                                <div class="mt-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">この本の所有者</p>
                                    <div class="flex items-center justify-center space-x-2">
                                        <img src="<?php echo html(isset($book_owner_info['user_photo']) ? $book_owner_info['user_photo'] : '/img/no-image-user.png'); ?>" 
                                             alt="<?php echo html($book_owner_info['nickname']); ?>" 
                                             class="w-8 h-8 rounded-full object-cover">
                                        <a href="/profile.php?user_id=<?php echo html($book_owner_info['user_id']); ?>" 
                                           class="text-sm font-medium text-readnest-primary hover:text-readnest-accent">
                                            <?php echo html($book_owner_info['nickname']); ?>さん
                                        </a>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <!-- 評価とステータス -->
                                <div class="flex flex-col items-center space-y-2">
                                    <?php if (!empty($average_rating)): ?>
                                    <div class="flex items-center">
                                        <div class="rating mr-2">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <?php if ($i <= round($average_rating)): ?>
                                                    <i class="fas fa-star text-yellow-400 text-sm"></i>
                                                <?php else: ?>
                                                    <i class="far fa-star text-gray-300 text-sm"></i>
                                                <?php endif; ?>
                                            <?php endfor; ?>
                                        </div>
                                        <span class="text-gray-600 dark:text-gray-400 text-sm">(<?php echo number_format($average_rating, 1); ?>)</span>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($total_users)): ?>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">
                                        <i class="fas fa-users mr-1"></i>
                                        <?php echo html($total_users); ?>人が登録
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($book['reference_count'])): ?>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">
                                        <i class="fas fa-eye mr-1"></i>
                                        <?php echo number_format($book['reference_count']); ?>回参照
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($book['update_date'])): ?>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">
                                        <i class="fas fa-clock mr-1"></i>
                                        <?php echo formatRelativeTime($book['update_date']); ?>に更新
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- 外部リンク -->
                                <div class="mt-4 space-y-2">
                                    <?php if (!empty($book['amazon_url'])): ?>
                                    <a href="<?php echo html($book['amazon_url']); ?>" 
                                       target="_blank" 
                                       rel="noopener noreferrer"
                                       class="btn-outline w-full text-center text-sm">
                                        <i class="fas fa-external-link-alt mr-2"></i>詳細ページを見る
                                    </a>
                                    <?php endif; ?>
                                    
                                    <!-- Amazonで検索ボタン -->
                                    <a href="https://www.amazon.co.jp/s?k=<?php echo urlencode($book['title'] . ' ' . ($book['author'] ?? '')); ?>" 
                                       target="_blank"
                                       rel="noopener noreferrer"
                                       class="w-full inline-flex items-center justify-center px-4 py-2 bg-orange-500 text-white rounded-md hover:bg-orange-600 transition-colors text-sm">
                                        <i class="fab fa-amazon mr-2"></i>Amazonで検索
                                    </a>
                                </div>
                            </div>
                            
                            <!-- 書籍情報とタグセクション（左下に配置） -->
                            <div class="mt-4 space-y-4">
                                <!-- 本の詳細情報 -->
                                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                    <h3 class="font-semibold mb-3">書籍情報</h3>
                                    <dl class="grid grid-cols-1 gap-2 text-sm">
                                        <?php if (!empty($book['primary_genre'])): ?>
                                        <div class="flex">
                                            <dt class="font-medium text-gray-600 dark:text-gray-400 w-20">ジャンル:</dt>
                                            <dd class="text-gray-900">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                                    <?php echo html($book['primary_genre']); ?>
                                                </span>
                                                <?php if (!empty($book['genres']) && count($book['genres']) > 1): ?>
                                                    <span class="text-xs text-gray-500 dark:text-gray-400 ml-1">他<?php echo count($book['genres']) - 1; ?>件</span>
                                                <?php endif; ?>
                                            </dd>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($book['publisher'])): ?>
                                        <div class="flex">
                                            <dt class="font-medium text-gray-600 dark:text-gray-400 w-20">出版社:</dt>
                                            <dd class="text-gray-900"><?php echo html($book['publisher']); ?></dd>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($book['published_date'])): ?>
                                        <div class="flex">
                                            <dt class="font-medium text-gray-600 dark:text-gray-400 w-20">出版日:</dt>
                                            <dd class="text-gray-900"><?php echo html($book['published_date']); ?></dd>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($book['isbn'])): ?>
                                        <div class="flex">
                                            <dt class="font-medium text-gray-600 dark:text-gray-400 w-20">ISBN:</dt>
                                            <dd class="text-gray-900"><?php echo html($book['isbn']); ?></dd>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($book['pages']) || (isset($_SESSION['AUTH_USER']) && isset($is_book_owner) && $is_book_owner)): ?>
                                        <div class="flex" x-data="{ editingPages: false, newPages: '<?php echo html(isset($book['pages']) ? $book['pages'] : ''); ?>' }">
                                            <dt class="font-medium text-gray-600 dark:text-gray-400 w-20">ページ数:</dt>
                                            <dd class="text-gray-900">
                                                <div x-show="!editingPages" class="flex items-center space-x-2">
                                                    <span><?php echo html(isset($book['pages']) ? $book['pages'] : '未設定'); ?><?php if (!empty($book['pages'])): ?>ページ<?php endif; ?></span>
                                                    <?php if (isset($_SESSION['AUTH_USER']) && isset($is_book_owner) && $is_book_owner): ?>
                                                    <button @click="editingPages = true; $nextTick(() => $refs.pagesInput.focus())" 
                                                            class="text-blue-600 hover:text-blue-800 text-sm" 
                                                            title="ページ数を編集">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <?php if (isset($_SESSION['AUTH_USER']) && isset($is_book_owner) && $is_book_owner): ?>
                                                <form x-show="editingPages"
                                                      action=""
                                                      method="post"
                                                      class="flex items-center space-x-2"
                                                      @submit.prevent="if(newPages && newPages > 0) { $el.submit(); }">
                                                    <?php csrfFieldTag(); ?>
                                                    <input type="hidden" name="action" value="update_pages">
                                                    <input type="hidden" name="book_id" value="<?php echo html($book['book_id']); ?>">
                                                    <input type="number" 
                                                           name="total_pages" 
                                                           x-ref="pagesInput"
                                                           x-model="newPages"
                                                           class="w-20 px-2 py-1 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded text-sm focus:outline-none focus:ring-1 focus:ring-blue-500 dark:focus:ring-blue-400"
                                                           min="1"
                                                           max="9999"
                                                           required>
                                                    <button type="submit" 
                                                            class="text-green-600 hover:text-green-800 text-sm"
                                                            title="保存">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                    <button type="button" 
                                                            @click="editingPages = false; newPages = '<?php echo html(isset($book['pages']) ? $book['pages'] : ''); ?>'" 
                                                            class="text-red-600 hover:text-red-800 text-sm"
                                                            title="キャンセル">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </form>
                                                <?php endif; ?>
                                            </dd>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($book['reference_count'])): ?>
                                        <div class="flex">
                                            <dt class="font-medium text-gray-600 dark:text-gray-400 w-20">参照数:</dt>
                                            <dd class="text-gray-900"><?php echo number_format($book['reference_count']); ?>回</dd>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($book['create_date'])): ?>
                                        <div class="flex">
                                            <dt class="font-medium text-gray-600 dark:text-gray-400 w-20">登録日:</dt>
                                            <dd class="text-gray-900">
                                                <?php 
                                                if (is_numeric($book['create_date']) && $book['create_date'] > 0) {
                                                    echo date('Y/m/d', $book['create_date']);
                                                } else {
                                                    echo formatDate($book['create_date'], 'Y/m/d');
                                                }
                                                ?>
                                            </dd>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($book['update_date'])): ?>
                                        <div class="flex">
                                            <dt class="font-medium text-gray-600 dark:text-gray-400 w-20">更新日:</dt>
                                            <dd class="text-gray-900"><?php echo formatDate($book['update_date'], 'Y/m/d'); ?></dd>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($login_flag && $is_book_owner && !empty($user_book_info) && ($user_book_info['status'] == READING_FINISH || $user_book_info['status'] == READ_BEFORE)): ?>
                                        <div class="flex items-center" x-data="{ editing: false }">
                                            <dt class="font-medium text-gray-600 w-20">
                                                読了日:
                                                <a href="/help.php#reading-management" target="_blank" class="ml-1 text-xs text-blue-500 hover:text-blue-600" title="読了日設定の詳細">
                                                    <i class="fas fa-question-circle"></i>
                                                </a>
                                            </dt>
                                            <dd class="text-gray-900 flex items-center space-x-2">
                                                <span x-show="!editing">
                                                    <?php if (!empty($user_book_info['finished_date'])): ?>
                                                        <?php echo formatDate($user_book_info['finished_date'], 'Y/m/d'); ?>
                                                    <?php else: ?>
                                                        <span class="text-gray-400">未設定</span>
                                                    <?php endif; ?>
                                                </span>
                                                <form x-show="editing" @submit.prevent="updateFinishedDate($event, <?php echo $book['book_id']; ?>)" class="flex items-center space-x-2">
                                                    <input type="date" 
                                                           name="finished_date" 
                                                           value="<?php echo html($user_book_info['finished_date'] ?? ''); ?>"
                                                           class="px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:outline-none focus:ring-1 focus:ring-blue-500 dark:focus:ring-blue-400"
                                                           @keydown.escape="editing = false">
                                                    <button type="submit" class="text-green-600 hover:text-green-700 text-sm">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                    <button type="button" @click="editing = false" class="text-red-600 hover:text-red-700 text-sm">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </form>
                                                <button @click="editing = true" x-show="!editing" class="text-blue-600 hover:text-blue-800 text-sm">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            </dd>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($book_owner_info && (!$login_flag || $mine_user_id !== $book['user_id'])): ?>
                                        <div class="flex">
                                            <dt class="font-medium text-gray-600 dark:text-gray-400 w-20">所有者:</dt>
                                            <dd>
                                                <a href="/profile.php?user_id=<?php echo html($book_owner_info['user_id']); ?>" 
                                                   class="text-readnest-primary hover:text-readnest-accent inline-flex items-center space-x-1">
                                                    <img src="<?php echo html(isset($book_owner_info['user_photo']) ? $book_owner_info['user_photo'] : '/img/no-image-user.png'); ?>" 
                                                         alt="<?php echo html($book_owner_info['nickname']); ?>" 
                                                         class="w-5 h-5 rounded-full object-cover inline-block">
                                                    <span><?php echo html($book_owner_info['nickname']); ?>さん</span>
                                                </a>
                                            </dd>
                                        </div>
                                        <?php endif; ?>
                                    </dl>
                                </div>
                                
                                <!-- タグセクション -->
                                <?php if (!empty($book_tags) || ($login_flag && $is_book_owner)): ?>
                                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3 flex items-center">
                                        <i class="fas fa-tags mr-2"></i>タグ
                                    </h3>
                                    
                                    <!-- タグセクション -->
                                    <?php if ($login_flag && $is_book_owner): ?>
                                    <!-- 編集可能なタグ -->
                                    <div x-data="{ 
                                        editingTags: false, 
                                        tags: '<?php echo html(implode(', ', $book_tags ?? [])); ?>',
                                        originalTags: '<?php echo html(implode(', ', $book_tags ?? [])); ?>',
                                        saveTag() {
                                            const form = document.createElement('form');
                                            form.method = 'POST';
                                            form.action = '';
                                            
                                            const bookIdInput = document.createElement('input');
                                            bookIdInput.type = 'hidden';
                                            bookIdInput.name = 'book_id';
                                            bookIdInput.value = <?php echo $book['book_id']; ?>;
                                            form.appendChild(bookIdInput);
                                            
                                            const actionInput = document.createElement('input');
                                            actionInput.type = 'hidden';
                                            actionInput.name = 'action';
                                            actionInput.value = 'update_tags';
                                            form.appendChild(actionInput);
                                            
                                            const tagsInput = document.createElement('input');
                                            tagsInput.type = 'hidden';
                                            tagsInput.name = 'tags';
                                            tagsInput.value = this.tags;
                                            form.appendChild(tagsInput);
                                            
                                            document.body.appendChild(form);
                                            form.submit();
                                        },
                                        removeTag(tagToRemove) {
                                            const tagArray = this.tags.split(',').map(t => t.trim()).filter(t => t);
                                            const newTags = tagArray.filter(t => t !== tagToRemove);
                                            this.tags = newTags.join(', ');
                                        }
                                    }">
                                        <!-- 通常モード：タグ表示 -->
                                        <div x-show="!editingTags">
                                            <?php if (!empty($book_tags)): ?>
                                            <div class="flex flex-wrap gap-2 mb-3">
                                                <?php foreach ($book_tags as $tag): ?>
                                                <a href="/search_book_by_tag.php?tag=<?php echo urlencode($tag); ?>" 
                                                   class="inline-block bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-sm hover:bg-blue-200 transition-colors">
                                                    <?php echo html($tag); ?>
                                                </a>
                                                <?php endforeach; ?>
                                            </div>
                                            <?php endif; ?>
                                            <button @click="editingTags = true; tags = originalTags;" 
                                                    class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                                <i class="fas fa-edit mr-1"></i>
                                                <?php echo empty($book_tags) ? 'タグを追加' : 'タグを編集'; ?>
                                            </button>
                                        </div>
                                        
                                        <!-- 編集モード -->
                                        <div x-show="editingTags" x-cloak class="space-y-3">
                                            <!-- 編集中のタグ表示（削除可能） -->
                                            <div x-show="tags.trim()" class="bg-white dark:bg-gray-700 p-3 rounded-lg border border-gray-200 dark:border-gray-600">
                                                <p class="text-xs text-gray-600 dark:text-gray-400 mb-2">現在のタグ（クリックで削除）：</p>
                                                <div class="flex flex-wrap gap-2">
                                                    <template x-for="tag in tags.split(',').map(t => t.trim()).filter(t => t)" :key="tag">
                                                        <button @click="removeTag(tag)" 
                                                                class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-xs hover:bg-red-100 hover:text-red-700 transition-colors"
                                                                type="button">
                                                            <span x-text="tag"></span>
                                                            <i class="fas fa-times ml-1"></i>
                                                        </button>
                                                    </template>
                                                </div>
                                            </div>
                                            
                                            <!-- タグ入力エリア -->
                                            <div class="bg-gray-50 dark:bg-gray-600 p-3 rounded-lg">
                                                <p class="text-xs text-gray-600 dark:text-gray-400 mb-2">
                                                    <i class="fas fa-info-circle mr-1"></i>
                                                    タグをカンマ区切りで入力してください（例：ミステリー, 推理小説, 感動）
                                                </p>
                                                <div class="flex gap-2">
                                                    <input type="text" 
                                                           x-model="tags"
                                                           placeholder="タグをカンマ区切りで入力" 
                                                           class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-blue-500 dark:focus:ring-blue-400"
                                                           maxlength="200">
                                                    <button @click="saveTag()" 
                                                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm font-medium">
                                                        <i class="fas fa-save mr-1"></i>保存
                                                    </button>
                                                    <button @click="editingTags = false; tags = originalTags;" 
                                                            class="px-4 py-2 bg-gray-500 hover:bg-gray-600 dark:bg-gray-600 dark:hover:bg-gray-500 text-white rounded-md text-sm font-medium">
                                                        キャンセル
                                                    </button>
                                                </div>
                                            </div>
                                            
                                            <!-- AIタグ生成 -->
                                            <div>
                                                <button type="button" 
                                                        onclick="generateAITagsForEdit()"
                                                        class="w-full bg-gradient-to-r from-green-500 to-teal-500 text-white px-3 py-2 rounded-md hover:from-green-600 hover:to-teal-600 transition-colors text-sm">
                                                    <i class="fas fa-robot mr-2"></i>AIでタグを自動生成
                                                </button>
                                                
                                                <!-- AI生成結果 -->
                                                <div id="ai-tags-panel" class="hidden mt-3">
                                                    <!-- ローディング -->
                                                    <div id="ai-tags-loading" class="text-center py-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                                        <svg class="animate-spin h-5 w-5 mx-auto text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                        </svg>
                                                        <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">タグを生成中...</p>
                                                    </div>
                                                    
                                                    <!-- 生成されたタグ -->
                                                    <div id="ai-tags-result" class="hidden bg-green-50 p-3 rounded-lg">
                                                        <div class="flex items-center justify-between mb-2">
                                                            <p class="text-xs text-gray-700 dark:text-gray-300">
                                                                <i class="fas fa-magic mr-1"></i>AI生成タグ（クリックで追加）：
                                                            </p>
                                                            <button type="button" 
                                                                    onclick="addAllAITagsToInput()"
                                                                    class="text-xs bg-green-600 text-white px-3 py-1 rounded hover:bg-green-700 transition-colors">
                                                                <i class="fas fa-plus-circle mr-1"></i>すべて追加
                                                            </button>
                                                        </div>
                                                        <div id="ai-tags-list" class="flex flex-wrap gap-2"></div>
                                                    </div>
                                                    
                                                    <!-- エラー -->
                                                    <div id="ai-tags-error" class="hidden bg-red-50 border-l-2 border-red-400 p-2 rounded">
                                                        <p class="text-xs text-red-700"></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php else: ?>
                                    <!-- 読み取り専用のタグ表示 -->
                                    <?php if (!empty($book_tags)): ?>
                                    <div class="flex flex-wrap gap-2">
                                        <?php foreach ($book_tags as $tag): ?>
                                        <a href="/search_book_by_tag.php?tag=<?php echo urlencode($tag); ?>" 
                                           class="inline-block bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-sm hover:bg-blue-200 transition-colors">
                                            <?php echo html($tag); ?>
                                        </a>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php else: ?>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">タグが設定されていません</p>
                                    <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                                
                                <!-- ソーシャルメディア共有ボタン -->
                                <div class="mt-6 bg-gray-50 rounded-lg p-4">
                                    <h3 class="text-sm font-medium text-gray-700 mb-3 flex items-center">
                                        <i class="fas fa-share-alt mr-2"></i>この本を共有する
                                    </h3>
                                    <div class="flex items-center space-x-3">
                                        <!-- Twitter (X) -->
                                        <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode('https://readnest.jp/book/' . $book['book_id']); ?>&text=<?php echo urlencode('「' . $book['title'] . '」' . ($book['author'] ? ' - ' . $book['author'] : '') . ' #ReadNest'); ?>" 
                                           target="_blank" 
                                           rel="noopener noreferrer"
                                           class="flex items-center justify-center w-10 h-10 rounded-full border-2 border-gray-300 bg-white text-gray-900 hover:bg-gray-100 transition-colors font-bold text-sm"
                                           title="X (Twitter)で共有">
                                            X
                                        </a>
                                        
                                        <!-- Facebook -->
                                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode('https://readnest.jp/book/' . $book['book_id']); ?>" 
                                           target="_blank" 
                                           rel="noopener noreferrer"
                                           class="flex items-center justify-center w-10 h-10 rounded-full bg-blue-600 text-white hover:bg-blue-700 transition-colors"
                                           title="Facebookで共有">
                                            <i class="fab fa-facebook-f"></i>
                                        </a>
                                        
                                        <!-- LINE -->
                                        <a href="https://social-plugins.line.me/lineit/share?url=<?php echo urlencode('https://readnest.jp/book/' . $book['book_id']); ?>" 
                                           target="_blank" 
                                           rel="noopener noreferrer"
                                           class="flex items-center justify-center w-10 h-10 rounded-full bg-green-500 text-white hover:bg-green-600 transition-colors"
                                           title="LINEで共有">
                                            <i class="fab fa-line"></i>
                                        </a>
                                        
                                        <!-- リンクをコピー -->
                                        <button onclick="copyToClipboard('https://readnest.jp/book/<?php echo $book['book_id']; ?>')" 
                                                class="flex items-center justify-center w-10 h-10 rounded-full bg-gray-500 text-white hover:bg-gray-600 transition-all duration-300 relative"
                                                title="リンクをコピー">
                                            <i class="fas fa-link" id="copy-icon"></i>
                                            <i class="fas fa-check hidden" id="check-icon"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 右側：レビューと読書進捗 -->
                    <div class="lg:col-span-1 flex flex-col space-y-6">
                        <!-- 本の説明文（Google Books APIから取得） -->
                        <?php 
                        // b_book_repositoryテーブルから説明文を取得
                        $description_sql = "SELECT description FROM b_book_repository WHERE asin = ?";
                        $book_description = $g_db->getOne($description_sql, [$book['amazon_id'] ?? $book['isbn'] ?? '']);
                        ?>
                        <?php if (!empty($book_description) && !DB::isError($book_description)): ?>
                        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 mt-4">
                            <h3 class="font-semibold mb-3 flex items-center">
                                <i class="fas fa-book-open mr-2 text-gray-600 dark:text-gray-400"></i>
                                内容紹介
                            </h3>
                            <div class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed">
                                <?php 
                                // 説明文を適切な長さで表示（展開可能）
                                $full_description = html($book_description);
                                $max_length = 300;
                                $is_long = mb_strlen($book_description) > $max_length;
                                ?>
                                <div x-data="{ expanded: false }">
                                    <div x-show="!expanded">
                                        <?php if ($is_long): ?>
                                            <?php echo nl2br(html(mb_substr($book_description, 0, $max_length))); ?>...
                                            <button @click="expanded = true" class="text-blue-600 hover:text-blue-800 ml-1 text-sm font-medium">
                                                続きを読む
                                            </button>
                                        <?php else: ?>
                                            <?php echo nl2br($full_description); ?>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($is_long): ?>
                                    <div x-show="expanded" x-cloak>
                                        <?php echo nl2br($full_description); ?>
                                        <button @click="expanded = false" class="text-blue-600 hover:text-blue-800 ml-1 text-sm font-medium">
                                            閉じる
                                        </button>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Google Books API帰属表示 -->
                            <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                                <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                                    <div class="flex items-center space-x-2">
                                        <img src="https://www.google.com/intl/ja/googlebooks/images/gbs_preview_button1.png" 
                                             alt="Google プレビュー" 
                                             class="h-5">
                                        <span>書籍情報提供: Google Books</span>
                                    </div>
                                    <?php if (!empty($book['isbn'])): ?>
                                    <a href="https://books.google.co.jp/books?q=isbn:<?php echo urlencode($book['isbn']); ?>" 
                                       target="_blank" 
                                       rel="noopener noreferrer"
                                       class="text-blue-600 hover:text-blue-800 flex items-center space-x-1">
                                        <span>Google Booksで見る</span>
                                        <i class="fas fa-external-link-alt text-xs"></i>
                                    </a>
                                    <?php elseif (!empty($book['title'])): ?>
                                    <a href="https://books.google.co.jp/books?q=<?php echo urlencode($book['title'] . ' ' . ($book['author'] ?? '')); ?>" 
                                       target="_blank" 
                                       rel="noopener noreferrer"
                                       class="text-blue-600 hover:text-blue-800 flex items-center space-x-1">
                                        <span>Google Booksで見る</span>
                                        <i class="fas fa-external-link-alt text-xs"></i>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        
                        <!-- 公開設定ユーザーのレビュー表示 -->
                        <?php if (!empty($public_user_review)): ?>
                            <div class="bg-amber-50 rounded-lg border-2 border-amber-200 mb-6">
                                <div class="p-4">
                                    <h2 class="text-lg font-bold text-amber-800 mb-3 flex items-center">
                                        <i class="fas fa-star mr-2"></i>📝 レビュー
                                        <span class="text-sm font-normal text-amber-600 ml-2">
                                            （<?php echo html($public_user_review['nickname']); ?>さんのレビュー）
                                        </span>
                                    </h2>
                                    
                                    <div class="bg-white dark:bg-gray-800 rounded-lg p-3 border border-amber-300 dark:border-amber-600">
                                        <?php if (!empty($public_user_review['rating'])): ?>
                                        <div class="flex items-center mb-2">
                                            <span class="text-sm font-medium text-amber-800 mr-2">評価:</span>
                                            <div class="flex mr-2">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <?php if ($i <= $public_user_review['rating']): ?>
                                                        <i class="fas fa-star text-yellow-400 text-sm"></i>
                                                    <?php else: ?>
                                                        <i class="far fa-star text-gray-300 text-sm"></i>
                                                    <?php endif; ?>
                                                <?php endfor; ?>
                                            </div>
                                            <span class="text-amber-600 text-sm"><?php echo $public_user_review['rating']; ?>/5</span>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($public_user_review['memo'])): ?>
                                        <div>
                                            <span class="text-sm font-medium text-amber-800">レビュー:</span>
                                            <div class="mt-1 text-gray-700 text-sm leading-relaxed">
                                                <?php echo XSS::nl2brAutoLink($public_user_review['memo']); ?>
                                            </div>
                                        </div>
                                        <?php endif; ?>

                                        <!-- いいねボタン（ログインユーザーかつ本の所有者でない場合） -->
                                        <?php if ($login_flag && $mine_user_id != $public_user_review['user_id']): ?>
                                        <div class="mt-3">
                                            <?php
                                            require_once(dirname(dirname(dirname(__FILE__))) . '/library/like_helpers.php');
                                            $review_target_id = generateReviewTargetId($book['book_id'], $public_user_review['user_id']);
                                            $like_count = getLikeCount('review', $review_target_id);
                                            $is_liked = isUserLiked($mine_user_id, 'review', $review_target_id);
                                            echo generateLikeButton(
                                                'review',
                                                $book['book_id'],
                                                $like_count,
                                                $is_liked,
                                                ['review_user_id' => $public_user_review['user_id']]
                                            );
                                            ?>
                                        </div>
                                        <?php elseif ($like_count > 0): ?>
                                        <!-- いいね数のみ表示 -->
                                        <div class="mt-3">
                                            <span class="inline-flex items-center gap-1 px-3 py-1 text-sm text-gray-600 dark:text-gray-400">
                                                <i class="fas fa-heart text-red-500"></i>
                                                <span><?php echo number_format($like_count); ?></span>
                                            </span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- 公開ユーザーの読書履歴セクション -->
                        <?php if (!empty($reading_progress) && !$is_book_owner): ?>
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden border border-gray-200 dark:border-gray-700 mb-6">
                            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-gray-700 dark:to-gray-600 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                                <h2 class="text-xl font-bold text-gray-800 flex items-center">
                                    <i class="fas fa-history text-blue-600 mr-3"></i>
                                    読書履歴
                                </h2>
                            </div>
                            <div class="p-6">
                                <div class="space-y-3">
                                    <?php foreach (array_slice($reading_progress, 0, 10) as $progress): ?>
                                    <div class="flex items-start space-x-3 text-sm border-b border-gray-100 dark:border-gray-600 pb-2">
                                        <span class="text-gray-500 whitespace-nowrap">
                                            <?php echo formatDate($progress['date'], 'Y/m/d'); ?>
                                        </span>
                                        <span class="text-blue-600 font-medium whitespace-nowrap">
                                            <?php echo html($progress['page']); ?>ページ
                                        </span>
                                        <?php if (!empty($progress['memo'])): ?>
                                        <span class="text-gray-700 flex-1">
                                            <?php echo html($progress['memo']); ?>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php if (count($reading_progress) > 10): ?>
                                <p class="text-gray-500 dark:text-gray-400 text-sm mt-3">他<?php echo count($reading_progress) - 10; ?>件の履歴があります</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- 本棚登録・レビュー統合セクション -->
                        <?php if (isset($_SESSION['AUTH_USER'])): ?>
                            <?php if (isset($is_in_bookshelf) && $is_in_bookshelf): ?>
                                
                                <!-- 読書進捗とレビューのレイアウト -->
                                <div class="space-y-6">
                                    <!-- 📊 読書進捗セクション（自分の本の場合） -->
                                    <?php if ($is_book_owner): ?>
                                    <div class="bg-blue-50 dark:bg-gray-800 rounded-lg border-2 border-blue-200 dark:border-blue-600">
                                    <div class="p-4">
                                        <h2 class="text-lg font-bold text-blue-800 dark:text-blue-200 mb-3 flex items-center justify-between">
                                            <span>
                                                <i class="fas fa-chart-line mr-2"></i>📊 読書進捗
                                            </span>
                                            <?php if (isset($is_book_owner) && $is_book_owner): ?>
                                            <a href="/reading_history_edit.php?book_id=<?php echo $book['book_id']; ?>" 
                                               class="text-sm font-normal text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 transition-colors">
                                                <i class="fas fa-history mr-1"></i>履歴を編集
                                            </a>
                                            <?php endif; ?>
                                        </h2>
                                                
                                                <!-- 現在の進捗表示 -->
                                                <?php if (!empty($user_book_info) && (!empty($user_book_info['current_page']) || !empty($user_book_info['status']))): ?>
                                                <div class="bg-white dark:bg-gray-800 rounded-lg p-3 mb-3 border border-blue-300 dark:border-blue-600">
                                                    <?php if (!empty($user_book_info['current_page']) && !empty($book['pages'])): ?>
                                                    <?php 
                                                        $progress_percentage = round(($user_book_info['current_page'] / $book['pages']) * 100, 1);
                                                        $remaining_pages = $book['pages'] - $user_book_info['current_page'];
                                                    ?>
                                                    <!-- 進捗グラフ -->
                                                    <div class="mb-3">
                                                        <div class="flex justify-between items-baseline mb-1">
                                                            <span class="text-sm font-medium text-blue-800 dark:text-blue-200">読書進捗</span>
                                                            <span class="text-lg font-bold text-blue-600 dark:text-blue-400"><?php echo $progress_percentage; ?>%</span>
                                                        </div>
                                                        <div class="bg-gray-200 dark:bg-gray-700 rounded-full h-4 relative overflow-hidden">
                                                            <div class="bg-gradient-to-r from-blue-400 to-blue-600 h-4 rounded-full transition-all duration-300 flex items-center justify-end pr-2" 
                                                                 style="width: <?php echo $progress_percentage; ?>%">
                                                                <?php if ($progress_percentage >= 20): ?>
                                                                <span class="text-xs text-white font-semibold"><?php echo $progress_percentage; ?>%</span>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- 詳細情報 -->
                                                    <div class="grid grid-cols-2 gap-3 text-sm">
                                                        <div>
                                                            <span class="text-gray-600 dark:text-gray-400">現在:</span>
                                                            <span class="font-bold text-blue-600 dark:text-blue-400 ml-1"><?php echo html($user_book_info['current_page']); ?>ページ</span>
                                                        </div>
                                                        <div>
                                                            <span class="text-gray-600 dark:text-gray-400">残り:</span>
                                                            <span class="font-bold text-gray-700 dark:text-gray-300 ml-1"><?php echo $remaining_pages; ?>ページ</span>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- 読書ペース表示 -->
                                                    <?php if (!empty($reading_progress) && count($reading_progress) >= 2): ?>
                                                    <?php
                                                        // 最近の進捗からペースを計算
                                                        $recent_progress = array_slice($reading_progress, 0, 7); // 最近７日分
                                                        $total_days = 0;
                                                        $total_pages_read = 0;
                                                        
                                                        for ($i = 0; $i < count($recent_progress) - 1; $i++) {
                                                            $current = $recent_progress[$i];
                                                            $prev = $recent_progress[$i + 1];
                                                            
                                                            $days_diff = (strtotime($current['date']) - strtotime($prev['date'])) / 86400;
                                                            $pages_diff = $current['page'] - $prev['page'];
                                                            
                                                            if ($days_diff > 0 && $pages_diff > 0) {
                                                                $total_days += $days_diff;
                                                                $total_pages_read += $pages_diff;
                                                            }
                                                        }
                                                        
                                                        if ($total_days > 0 && $total_pages_read > 0) {
                                                            $daily_pace = round($total_pages_read / $total_days, 1);
                                                            $days_to_complete = ($daily_pace > 0) ? ceil($remaining_pages / $daily_pace) : 0;
                                                    ?>
                                                    <div class="mt-3 pt-3 border-t border-blue-200">
                                                        <div class="flex items-center justify-between text-sm">
                                                            <span class="text-gray-600 dark:text-gray-400">
                                                                <i class="fas fa-tachometer-alt mr-1"></i>読書ペース:
                                                            </span>
                                                            <span class="font-semibold text-gray-700 dark:text-gray-300"><?php echo $daily_pace; ?>ページ/日</span>
                                                        </div>
                                                        <?php if ($remaining_pages > 0): ?>
                                                        <div class="flex items-center justify-between text-sm mt-1">
                                                            <span class="text-gray-600 dark:text-gray-400">
                                                                <i class="fas fa-flag-checkered mr-1"></i>完読予想:
                                                            </span>
                                                            <span class="font-semibold text-green-600 dark:text-green-400">約<?php echo $days_to_complete; ?>日後</span>
                                                        </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <?php } ?>
                                                    <?php endif; ?>
                                                    
                                                    <?php elseif (!empty($user_book_info['current_page'])): ?>
                                                    <!-- 総ページ数が未設定の場合 -->
                                                    <div class="flex items-center mb-2">
                                                        <span class="text-sm font-medium text-blue-800 dark:text-blue-200 mr-2">現在のページ:</span>
                                                        <span class="text-blue-600 text-sm font-bold"><?php echo html($user_book_info['current_page']); ?>ページ</span>
                                                    </div>
                                                    <?php endif; ?>
                                                    
                                                    <?php if (!empty($user_book_info['status'])): ?>
                                                    <div class="flex items-center">
                                                        <span class="text-sm font-medium text-blue-800 mr-2">ステータス:</span>
                                                        <span class="text-blue-600 text-sm">
                                                            <?php 
                                                            $statusTexts = [
                                                                BUY_SOMEDAY => 'いつか買う',
                                                                NOT_STARTED => 'まだ読んでない', 
                                                                READING_NOW => '読書中',
                                                                READING_FINISH => '読了',
                                                                READ_BEFORE => '昔読んだ'
                                                            ];
                                                            echo html(isset($statusTexts[$user_book_info['status']]) ? $statusTexts[$user_book_info['status']] : '');
                                                            ?>
                                                        </span>
                                                    </div>
                                                    <?php endif; ?>
                                                </div>
                                                <?php endif; ?>
                                                
                                                <!-- 読書履歴一覧 -->
                                                <?php if (!empty($reading_progress)): ?>
                                                <div class="mt-3 bg-white dark:bg-gray-800 rounded-lg p-3 border border-blue-300 dark:border-blue-600">
                                                    <h4 class="text-sm font-medium text-blue-800 dark:text-blue-200 mb-2">読書履歴</h4>
                                                    
                                                    <!-- 進捗グラフ -->
                                                    <?php if (count($reading_progress) >= 2 && !empty($book['pages'])): ?>
                                                    <div class="mb-3 p-2 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                                        <canvas id="progressChart" class="w-full" style="height: 150px;"></canvas>
                                                    </div>
                                                    <script>
                                                    document.addEventListener('DOMContentLoaded', function() {
                                                        const ctx = document.getElementById('progressChart');
                                                        if (ctx) {
                                                            const progressData = <?php
                                                                $chart_data = [];
                                                                $daily_progress = [];

                                                                // 日毎に集計（その日の最大ページ数を取る）
                                                                foreach ($reading_progress as $p) {
                                                                    $date_key = date('Y-m-d', strtotime($p['date']));
                                                                    if (!isset($daily_progress[$date_key]) || $daily_progress[$date_key] < $p['page']) {
                                                                        $daily_progress[$date_key] = $p['page'];
                                                                    }
                                                                }

                                                                // 最新30日分を取得してチャートデータに変換
                                                                $sorted_dates = array_keys($daily_progress);
                                                                sort($sorted_dates);
                                                                $recent_dates = array_slice($sorted_dates, -30);

                                                                foreach ($recent_dates as $date) {
                                                                    $chart_data[] = [
                                                                        'x' => $date . 'T00:00:00',  // ISO 8601形式で送信
                                                                        'y' => $daily_progress[$date]
                                                                    ];
                                                                }

                                                                echo json_encode($chart_data);
                                                            ?>;

                                                            console.log('Progress data:', progressData);
                                                            console.log('Data sample:', progressData.slice(0, 3));
                                                            console.log('Data count:', progressData.length);

                                                            new Chart(ctx, {
                                                                type: 'line',
                                                                data: {
                                                                    datasets: [{
                                                                        label: 'ページ数',
                                                                        data: progressData,
                                                                        borderColor: 'rgb(59, 130, 246)',
                                                                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                                                        tension: 0.3,
                                                                        fill: true
                                                                    }]
                                                                },
                                                                options: {
                                                                    responsive: true,
                                                                    maintainAspectRatio: false,
                                                                    plugins: {
                                                                        legend: {
                                                                            display: false
                                                                        },
                                                                        tooltip: {
                                                                            callbacks: {
                                                                                label: function(context) {
                                                                                    const date = new Date(context.parsed.x);
                                                                                    const formattedDate = date.toLocaleDateString('ja-JP', { year: 'numeric', month: '2-digit', day: '2-digit' });
                                                                                    const percentage = Math.round((context.parsed.y / <?php echo $book['pages']; ?>) * 100);
                                                                                    return formattedDate + ': ' + context.parsed.y + 'ページ (' + percentage + '%)';
                                                                                }
                                                                            }
                                                                        }
                                                                    },
                                                                    scales: {
                                                                        x: {
                                                                            type: 'time',
                                                                            time: {
                                                                                unit: 'day',
                                                                                displayFormats: {
                                                                                    day: 'MM/DD'
                                                                                },
                                                                                tooltipFormat: 'YYYY/MM/DD'
                                                                            },
                                                                            distribution: 'series',
                                                                            title: {
                                                                                display: false
                                                                            },
                                                                            ticks: {
                                                                                source: 'data',
                                                                                autoSkip: true,
                                                                                maxTicksLimit: 8,
                                                                                maxRotation: 45,
                                                                                minRotation: 45
                                                                            }
                                                                        },
                                                                        y: {
                                                                            beginAtZero: true,
                                                                            max: <?php echo $book['pages']; ?>,
                                                                            ticks: {
                                                                                callback: function(value) {
                                                                                    return value + 'p';
                                                                                }
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                            });
                                                        }
                                                    });
                                                    </script>
                                                    <?php endif; ?>
                                                    
                                                    <div class="space-y-2 max-h-40 overflow-y-auto" x-data="{ editingId: null }">
                                                        <?php foreach ($reading_progress as $progress): ?>
                                                        <div class="group hover:bg-gray-50 dark:hover:bg-gray-700 p-1 rounded" x-data="{ editing: false }" @finish-edit="editing = false; editingId = null">
                                                            <div x-show="!editing" class="flex items-start space-x-2 text-xs">
                                                                <span class="text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                                                    <?php echo formatDate($progress['date'], 'y/m/d H:i'); ?>
                                                                </span>
                                                                <span class="text-blue-600 font-medium whitespace-nowrap">
                                                                    <?php echo html($progress['page']); ?>ページ
                                                                </span>
                                                                <span class="text-gray-700 dark:text-gray-300 flex-1">
                                                                    <?php if (!empty($progress['memo'])): ?>
                                                                        <?php echo html($progress['memo']); ?>
                                                                    <?php else: ?>
                                                                        <span class="text-gray-400">メモなし</span>
                                                                    <?php endif; ?>
                                                                </span>
                                                                <?php if ($is_book_owner): ?>
                                                                <button @click="editing = true; editingId = <?php echo $progress['event_id']; ?>; $nextTick(() => $refs.memo_<?php echo $progress['event_id']; ?>.focus())"
                                                                        x-show="editingId !== <?php echo $progress['event_id']; ?>"
                                                                        class="opacity-0 group-hover:opacity-100 text-blue-600 hover:text-blue-800 transition-opacity">
                                                                    <i class="fas fa-edit"></i>
                                                                </button>
                                                                <?php endif; ?>
                                                            </div>
                                                            
                                                            <!-- 編集フォーム -->
                                                            <?php if ($is_book_owner): ?>
                                                            <form x-show="editing" @submit.prevent="updateProgressMemo($event, <?php echo $progress['event_id']; ?>, $el)" 
                                                                  class="flex items-start space-x-2">
                                                                <span class="text-gray-500 whitespace-nowrap text-xs">
                                                                    <?php echo formatDate($progress['date'], 'y/m/d H:i'); ?>
                                                                </span>
                                                                <span class="text-blue-600 font-medium whitespace-nowrap text-xs">
                                                                    <?php echo html($progress['page']); ?>ページ
                                                                </span>
                                                                <input type="text" 
                                                                       x-ref="memo_<?php echo $progress['event_id']; ?>"
                                                                       name="memo"
                                                                       value="<?php echo html($progress['memo'] ?? ''); ?>"
                                                                       @keydown.escape="editing = false; editingId = null"
                                                                       class="flex-1 px-2 py-0.5 text-xs border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:outline-none focus:ring-1 focus:ring-blue-500 dark:focus:ring-blue-400"
                                                                       placeholder="メモを入力">
                                                                <button type="submit" 
                                                                        class="text-green-600 hover:text-green-700 text-xs">
                                                                    <i class="fas fa-check"></i>
                                                                </button>
                                                                <button type="button" 
                                                                        @click="editing = false; editingId = null"
                                                                        class="text-red-600 hover:text-red-700 text-xs">
                                                                    <i class="fas fa-times"></i>
                                                                </button>
                                                            </form>
                                                            <?php endif; ?>
                                                        </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                                <?php endif; ?>
                                                
                                                <!-- 進捗更新フォーム -->
                                                <?php 
                                                // 未読了の本は自動的にフォームを展開
                                                $is_unfinished = !empty($user_book_info) && isset($user_book_info['status']) && 
                                                                $user_book_info['status'] != READING_FINISH && 
                                                                $user_book_info['status'] != READ_BEFORE;
                                                ?>
                                                <div class="mt-4">
                                                    <?php if ($is_unfinished): ?>
                                                    <div class="flex items-center justify-between mb-3">
                                                        <h3 class="text-sm font-semibold text-blue-800">
                                                            <i class="fas fa-bookmark mr-1"></i>進捗を更新
                                                        </h3>
                                                        <form action="" method="post" class="flex-shrink-0">
                                                            <?php csrfFieldTag(); ?>
                                                            <input type="hidden" name="action" value="mark_as_finished">
                                                            <input type="hidden" name="book_id" value="<?php echo html($book['book_id']); ?>">
                                                            <button type="submit" 
                                                                    class="bg-green-500 text-white py-2 px-3 rounded-lg hover:bg-green-600 transition-colors text-sm font-medium"
                                                                    onclick="return confirm('この本を読了しますか？');">
                                                                <i class="fas fa-check-circle mr-2"></i>読み終わった
                                                            </button>
                                                        </form>
                                                    </div>
                                                    
                                                    <div class="mt-3">
                                                        <form action="" method="post" class="space-y-3 bg-white dark:bg-gray-800 rounded-lg p-3 border border-blue-300 dark:border-blue-600">
                                                            <?php csrfFieldTag(); ?>
                                                            <input type="hidden" name="action" value="update_progress">
                                                            <input type="hidden" name="book_id" value="<?php echo html($book['book_id']); ?>">
                                                            
                                                            <!-- 現在のページ -->
                                                            <div>
                                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">現在のページ</label>
                                                                <input type="number" name="current_page" 
                                                                       id="progress-page-input"
                                                                       x-ref="currentPageInput"
                                                                       @focus="$event.target.select()"
                                                                       value="<?php echo html(isset($user_book_info['current_page']) ? $user_book_info['current_page'] : ''); ?>"
                                                                       class="w-full px-2 py-1 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                                       placeholder="例: 123"
                                                                       min="1" 
                                                                       <?php if (!empty($book['pages']) && $book['pages'] > 0): ?>
                                                                       max="<?php echo html($book['pages']); ?>"
                                                                       <?php endif; ?>
                                                                       id="current-page-input">
                                                                <?php if (!empty($book['pages'])): ?>
                                                                <div class="text-xs text-gray-500 mt-1">全<?php echo html($book['pages']); ?>ページ</div>
                                                                <?php endif; ?>
                                                            </div>
                                                            
                                                            <!-- 読書メモ -->
                                                            <div>
                                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">読書メモ</label>
                                                                <textarea name="memo" rows="3" 
                                                                          class="w-full px-2 py-1 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                                          placeholder="今日読んだ部分の感想やメモ..."></textarea>
                                                            </div>
                                                            
                                                            <div>
                                                                <button type="submit" class="w-full bg-blue-500 text-white py-1 px-3 rounded-md hover:bg-blue-600 transition-colors text-sm">
                                                                    <i class="fas fa-save mr-1"></i>保存
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                    <?php endif; ?>
                                                </div>
                                                
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                    <!-- 📝 レビューセクション -->
                                    <div class="bg-amber-50 rounded-lg border-2 border-amber-200">
                                    <div class="p-4">
                                        <h2 class="text-lg font-bold text-amber-800 mb-3 flex items-center">
                                            <i class="fas fa-star mr-2"></i>📝 あなたのレビュー
                                        </h2>
                                        
                                        <!-- 現在のレビュー表示 -->
                                        <?php if (!empty($user_book_info) && (!empty($user_book_info['rating']) || !empty($user_book_info['memo']))): ?>
                                        <div class="bg-white dark:bg-gray-800 rounded-lg p-3 mb-3 border border-amber-300 dark:border-amber-600">
                                            <?php if (!empty($user_book_info['rating'])): ?>
                                            <div class="flex items-center mb-2">
                                                <span class="text-sm font-medium text-amber-800 mr-2">評価:</span>
                                                <div class="flex mr-2">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <?php if ($i <= $user_book_info['rating']): ?>
                                                            <i class="fas fa-star text-yellow-400 text-sm"></i>
                                                        <?php else: ?>
                                                            <i class="far fa-star text-gray-300 text-sm"></i>
                                                        <?php endif; ?>
                                                    <?php endfor; ?>
                                                </div>
                                                <span class="text-amber-600 text-sm"><?php echo $user_book_info['rating']; ?>/5</span>
                                            </div>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($user_book_info['memo'])): ?>
                                            <div>
                                                <span class="text-sm font-medium text-amber-800">レビュー:</span>
                                                <div class="mt-1 text-gray-700 dark:text-gray-300 text-sm leading-relaxed">
                                                    <?php echo XSS::nl2brAutoLink($user_book_info['memo']); ?>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <!-- レビュー編集フォーム -->
                                        <?php if (isset($is_book_owner) && $is_book_owner): ?>
                                        <div x-data="{ showReviewForm: false }">
                                            <button @click="showReviewForm = !showReviewForm"
                                                    class="w-full bg-amber-500 text-white py-2 px-3 rounded-lg hover:bg-amber-600 transition-colors text-sm font-medium">
                                                <i class="fas fa-edit mr-2"></i>
                                                <span x-text="showReviewForm ? 'レビューを閉じる' : 'レビューを編集'"></span>
                                            </button>
                                            
                                            <div x-show="showReviewForm" x-transition class="mt-3">
                                                <form action="" method="post" class="space-y-3 bg-white dark:bg-gray-800 rounded-lg p-3 border border-amber-300 dark:border-amber-600">
                                                    <?php csrfFieldTag(); ?>
                                                    <input type="hidden" name="action" value="update_review">
                                                    <input type="hidden" name="book_id" value="<?php echo html($book['book_id']); ?>">
                                                    
                                                    <?php
                                                    $current_rating = isset($user_book_info['rating']) ? $user_book_info['rating'] : 0;
                                                    $current_comment = isset($user_book_info['memo']) ? $user_book_info['memo'] : '';
                                                    ?>
                                                    
                                                    <!-- 評価 -->
                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">評価</label>
                                                        <select name="rating" class="w-full px-2 py-1 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 dark:focus:ring-amber-400">
                                                            <option value="0" <?php echo $current_rating == 0 ? 'selected' : ''; ?>>未評価</option>
                                                            <option value="1" <?php echo $current_rating == 1 ? 'selected' : ''; ?>>⭐☆☆☆☆ 1つ星</option>
                                                            <option value="2" <?php echo $current_rating == 2 ? 'selected' : ''; ?>>⭐⭐☆☆☆ 2つ星</option>
                                                            <option value="3" <?php echo $current_rating == 3 ? 'selected' : ''; ?>>⭐⭐⭐☆☆ 3つ星</option>
                                                            <option value="4" <?php echo $current_rating == 4 ? 'selected' : ''; ?>>⭐⭐⭐⭐☆ 4つ星</option>
                                                            <option value="5" <?php echo $current_rating == 5 ? 'selected' : ''; ?>>⭐⭐⭐⭐⭐ 5つ星</option>
                                                        </select>
                                                    </div>
                                                    
                                                    <!-- レビュー -->
                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">レビュー・感想</label>
                                                        <textarea name="comment" id="memo" rows="4" 
                                                                  class="w-full px-2 py-1 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 dark:focus:ring-amber-400"
                                                                  placeholder="この本の感想やレビューを書いてください..."><?php echo html($current_comment); ?></textarea>
                                                    </div>
                                                    
                                                    <!-- AI書評アシスタントボタン -->
                                                    <div class="mt-2">
                                                        <button type="button" 
                                                                onclick="try { window.toggleAIAssistant(); } catch(e) { alert('エラー: ' + e.message); }"
                                                                class="inline-flex items-center px-3 py-1 bg-gradient-to-r from-purple-500 to-indigo-500 text-white text-sm font-medium rounded-md hover:from-purple-600 hover:to-indigo-600">
                                                            <i class="fas fa-robot mr-2"></i>
                                                            AI書評アシスタントを使う
                                                        </button>
                                                    </div>
                                                    
                                                    <div class="flex space-x-2">
                                                        <button type="submit" class="flex-1 bg-amber-500 text-white py-1 px-3 rounded-md hover:bg-amber-600 transition-colors text-sm">
                                                            <i class="fas fa-save mr-1"></i>保存
                                                        </button>
                                                        <button type="button" @click="showReviewForm = false"
                                                                class="px-3 py-1 border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300 dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors text-sm">
                                                            キャンセル
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                </div>
                                
                            <?php else: ?>
                                <!-- 本棚に未登録の場合のボタン -->
                                <div class="bg-blue-50 rounded-lg border-2 border-blue-200 p-4">
                                    <h2 class="text-lg font-bold text-blue-800 mb-3 flex items-center">
                                        <i class="fas fa-plus-circle mr-2"></i>この本を追加
                                    </h2>
                                    <form action="/add_book.php" method="post">
                                        <?php csrfFieldTag(); ?>
                                        <input type="hidden" name="asin" value="<?php echo html(isset($book['amazon_id']) ? $book['amazon_id'] : ''); ?>">
                                        <input type="hidden" name="isbn" value="<?php echo html(isset($book['isbn']) ? $book['isbn'] : ''); ?>">
                                        <input type="hidden" name="product_name" value="<?php echo html($book['title']); ?>">
                                        <input type="hidden" name="author" value="<?php echo html($book['author']); ?>">
                                        <input type="hidden" name="detail_url" value="<?php echo html($book['amazon_url']); ?>">
                                        <input type="hidden" name="image_url" value="<?php echo html($book['image_url']); ?>">
                                        <input type="hidden" name="number_of_pages" value="<?php echo html($book['pages']); ?>">
                                        <input type="hidden" name="status_list" value="1">
                                        <button type="submit" class="w-full bg-blue-500 text-white py-2 px-3 rounded-lg hover:bg-blue-600 transition-colors text-sm font-medium">
                                            <i class="fas fa-plus-circle mr-2"></i>本棚に追加
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        
                        <?php else: ?>
                            <!-- 未ログインユーザー向け -->
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg border-2 border-gray-200 dark:border-gray-600 p-4 text-center">
                                <h2 class="text-lg font-bold text-gray-800 mb-3">
                                    <i class="fas fa-sign-in-alt mr-2"></i>ログインが必要です
                                </h2>
                                <p class="text-gray-600 mb-3 text-sm">この本をレビューしたり、読書進捗を記録するにはログインが必要です。</p>
                                <a href="/index.php" class="bg-readnest-primary text-white py-2 px-4 rounded-lg hover:bg-opacity-90 transition-colors text-sm font-medium">
                                    <i class="fas fa-sign-in-alt mr-2"></i>ログイン
                                </a>
                            </div>
                        <?php endif; ?>
                        
                    </div>
                </div>
            </div>
        </div>

        <!-- 削除ボタン -->
        <?php if (isset($_SESSION['AUTH_USER']) && isset($is_book_owner) && $is_book_owner): ?>
        <div class="mt-8" x-data="{ showDeleteConfirm: false }">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 text-center">
                <button @click="showDeleteConfirm = true"
                        class="text-red-600 hover:text-red-800 transition-colors font-medium">
                    <i class="fas fa-trash-alt mr-2"></i>本棚から削除
                </button>

                <!-- 削除確認ダイアログ -->
                <div x-show="showDeleteConfirm" x-transition class="mt-4 max-w-md mx-auto">
                    <div class="bg-red-50 rounded-lg p-4 border border-red-200">
                        <p class="text-red-800 mb-4">本当にこの本を本棚から削除しますか？</p>
                        <form action="" method="post" class="flex space-x-3">
                            <?php csrfFieldTag(); ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="book_id" value="<?php echo html($book['book_id']); ?>">
                            <button type="submit" class="flex-1 bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md transition-colors">
                                削除する
                            </button>
                            <button type="button" @click="showDeleteConfirm = false"
                                    class="flex-1 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 px-4 py-2 rounded-md transition-colors">
                                キャンセル
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- AI推薦セクション -->
        <?php if (!empty($ai_recommendations)): ?>
        <div class="mt-8" id="ai-recommendations">
            <div class="bg-gradient-to-r from-purple-50 to-pink-50 rounded-xl shadow-lg p-6">
                <h2 class="text-2xl font-bold mb-6 flex items-center">
                    <i class="fas fa-robot text-purple-600 mr-3"></i>
                    AIが見つけた似た本
                    <?php if ($embedding_generated): ?>
                    <span class="ml-3 text-sm font-normal text-orange-600">
                        <i class="fas fa-magic"></i> リアルタイムで分析しました
                    </span>
                    <?php endif; ?>
                </h2>
                
                <div class="bg-white dark:bg-gray-800 rounded-lg p-4 mb-4">
                    <p class="text-sm text-gray-600 mb-2">
                        <i class="fas fa-info-circle mr-1"></i>
                        「<?php echo html($book['title']); ?>」の<strong>文章スタイル、テーマ、内容</strong>を分析し、
                        類似度の高い本を<?php echo count($ai_recommendations); ?>冊見つけました
                    </p>
                </div>
                
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                    <?php foreach ($ai_recommendations as $rec): ?>
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-lg transition-all hover:-translate-y-1">
                        <div class="relative">
                            <!-- 類似度バッジ -->
                            <div class="absolute top-2 left-2 z-10">
                                <span class="inline-block px-2 py-1 text-xs rounded-full 
                                             <?php echo $rec['similarity'] >= 70 ? 'bg-green-500' : ($rec['similarity'] >= 60 ? 'bg-yellow-500' : 'bg-orange-500'); ?> 
                                             text-white font-semibold">
                                    <?php echo $rec['similarity']; ?>%
                                </span>
                            </div>
                            
                            <!-- 本の画像（クリックでエンティティページ） -->
                            <a href="/book_entity/<?php echo urlencode($rec['asin']); ?>" 
                               class="block">
                                <img src="<?php echo html($rec['image_url']); ?>" 
                                     alt="<?php echo html($rec['title']); ?>"
                                     class="w-full h-48 object-cover rounded-t-lg hover:opacity-90 transition-opacity"
                                     onerror="this.src='/img/no-image-book.png'">
                            </a>
                        </div>
                        
                        <div class="p-3">
                            <h3 class="font-semibold text-sm mb-1 line-clamp-2" title="<?php echo html($rec['title']); ?>">
                                <a href="/book_entity/<?php echo urlencode($rec['asin']); ?>" 
                                   class="hover:text-purple-600 transition-colors">
                                    <?php echo html($rec['title']); ?>
                                </a>
                            </h3>
                            <p class="text-xs text-gray-600 mb-2 truncate">
                                <?php echo html($rec['author']); ?>
                            </p>
                            
                            <!-- 説明文を最初から表示 -->
                            <?php if (!empty($rec['description'])): ?>
                            <div class="mb-2 p-2 bg-gray-50 rounded text-xs text-gray-600">
                                <p class="line-clamp-3">
                                    <?php echo html(mb_substr($rec['description'], 0, 100)); ?><?php echo mb_strlen($rec['description']) > 100 ? '...' : ''; ?>
                                </p>
                            </div>
                            <?php endif; ?>
                            
                            <!-- メタ情報 -->
                            <div class="flex flex-wrap gap-1 mb-2 text-xs">
                                <?php if ($rec['reader_count'] > 0): ?>
                                <span class="inline-flex items-center px-1.5 py-0.5 bg-blue-50 text-blue-700 rounded">
                                    <i class="fas fa-users mr-1"></i><?php echo $rec['reader_count']; ?>人
                                </span>
                                <?php endif; ?>
                                
                                <?php if ($rec['avg_rating'] > 0): ?>
                                <span class="inline-flex items-center px-1.5 py-0.5 bg-yellow-50 text-yellow-700 rounded">
                                    <i class="fas fa-star mr-1"></i><?php echo $rec['avg_rating']; ?>
                                </span>
                                <?php endif; ?>
                                
                            </div>
                            
                            <!-- レビュー情報（ある場合） -->
                            <?php if (!empty($rec['has_review'])): ?>
                            <div class="mb-2 p-2 bg-green-50 border border-green-200 rounded">
                                <a href="/book/<?php echo $rec['review_book_id']; ?>" 
                                   class="block text-xs hover:text-green-700 transition-colors">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="font-semibold text-green-700">
                                            <i class="fas fa-comment-dots mr-1"></i>レビューあり
                                        </span>
                                        <?php if ($rec['review_rating'] > 0): ?>
                                        <span class="text-yellow-600">
                                            <?php for ($i = 0; $i < $rec['review_rating']; $i++): ?>★<?php endfor; ?>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-gray-600">
                                        by <?php echo html($rec['review_nickname']); ?>
                                        <?php if ($rec['review_has_memo']): ?>
                                        <span class="ml-1 text-green-600">
                                            <i class="fas fa-pen text-xs"></i>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                </a>
                            </div>
                            <?php endif; ?>
                            
                            <!-- アクションボタン -->
                            <div class="flex gap-2">
                                <!-- 追加ボタン（大きく表示） -->
                                <a href="/add_book.php?asin=<?php echo urlencode($rec['asin']); ?>" 
                                   class="flex-1 px-3 py-1.5 bg-gray-600 text-white text-xs rounded-md hover:bg-gray-700 transition-colors text-center font-medium">
                                    <i class="fas fa-plus mr-1"></i>追加
                                </a>
                                
                                <!-- Amazon検索ボタン -->
                                <a href="https://www.amazon.co.jp/s?k=<?php echo urlencode($rec['title'] . ' ' . $rec['author']); ?>" 
                                   target="_blank"
                                   class="px-2 py-1.5 bg-orange-500 text-white text-xs rounded-md hover:bg-orange-600 transition-colors text-center"
                                   title="Amazonで検索">
                                    <i class="fab fa-amazon"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- もっと探すリンク -->
                <div class="mt-6 text-center">
                    <a href="/recommendations.php?type=similar&book_id=<?php echo $book['book_id']; ?>" 
                       class="inline-flex items-center px-6 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                        <i class="fas fa-search mr-2"></i>
                        もっと類似本を探す
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- コメント・読者タブセクション -->
        <div class="mt-8">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg">
                <div class="border-t" x-data="{ activeTab: 'readers' }">
                    <!-- タブヘッダー -->
                    <div class="border-b">
                    <nav class="flex">
                        <button @click="activeTab = 'readers'" 
                                :class="activeTab === 'readers' ? 'border-b-2 border-readnest-primary text-readnest-primary' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                                class="px-6 py-3 font-medium text-sm focus:outline-none transition-colors">
                            <i class="fas fa-users mr-2"></i>
                            この本の読者 (<?php echo html(count(isset($readers) ? $readers : [])); ?>)
                        </button>
                    </nav>
                </div>
                
                <!-- タブコンテンツ -->
                <div class="p-6">
                    <div class="max-w-full">
                        <?php if (false): // コメント機能は無効化 ?>
                            <div class="mb-6" x-data="{ showCommentForm: false }">
                                <button x-show="!showCommentForm" 
                                        @click="showCommentForm = true"
                                        class="w-full bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 py-3 px-4 rounded-lg transition-colors text-sm font-medium">
                                    <i class="fas fa-pen mr-2"></i>コメントを書く
                                </button>
                                
                                <div x-show="showCommentForm" x-transition>
                                    <form action="" method="post" class="space-y-4">
                                        <?php csrfFieldTag(); ?>
                                        <input type="hidden" name="action" value="add_comment">
                                        <input type="hidden" name="book_id" value="<?php echo html($book['book_id']); ?>">
                                        
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">コメント</label>
                                            <textarea name="comment" 
                                                      rows="4" 
                                                      class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-readnest-primary focus:border-transparent"
                                                      placeholder="この本についてのコメントを書いてください..."
                                                      required></textarea>
                                        </div>
                                        
                                        <div class="flex space-x-2">
                                            <button type="submit" 
                                                    class="flex-1 bg-readnest-primary text-white py-2 px-4 rounded-md hover:bg-opacity-90 transition-colors text-sm font-medium">
                                                <i class="fas fa-paper-plane mr-2"></i>投稿する
                                            </button>
                                            <button type="button" 
                                                    @click="showCommentForm = false"
                                                    class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300 dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors text-sm">
                                                キャンセル
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($reviews)): ?>
                        <div class="space-y-4 max-h-96 overflow-y-auto">
                            <?php foreach ($reviews as $review): ?>
                            <div class="border-b border-gray-100 dark:border-gray-600 pb-3 last:border-b-0">
                                <div class="flex items-start space-x-3">
                                    <img src="<?php echo html(isset($review['user_photo']) ? $review['user_photo'] : '/img/no-image-user.png'); ?>" 
                                         alt="<?php echo html($review['nickname']); ?>" 
                                         class="w-8 h-8 rounded-full">
                                    <div class="flex-1">
                                        <div class="flex items-center justify-between mb-1">
                                            <div class="flex items-center space-x-1">
                                                <span class="font-medium text-sm"><?php echo html($review['nickname']); ?></span>
                                                <?php if (isset($review['user_level'])): ?>
                                                    <?php echo getLevelBadgeHtml($review['user_level'], 'xs'); ?>
                                                <?php endif; ?>
                                            </div>
                                            <div class="flex items-center space-x-2">
                                                <?php if (!empty($review['rating'])): ?>
                                                <div class="flex">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <i class="<?php echo $i <= $review['rating'] ? 'fas' : 'far'; ?> fa-star text-yellow-400 text-xs"></i>
                                                    <?php endfor; ?>
                                                </div>
                                                <?php endif; ?>
                                                
                                                <?php 
                                                // 本の所有者の場合、削除ボタンを表示
                                                if (isset($_SESSION['AUTH_USER']) && $_SESSION['AUTH_USER'] == $book['user_id'] && isset($review['comment_id'])): 
                                                ?>
                                                <form action="" method="post" class="inline" onsubmit="return confirm('このコメントを削除しますか？');">
                                                    <?php csrfFieldTag(); ?>
                                                    <input type="hidden" name="action" value="delete_comment">
                                                    <input type="hidden" name="comment_id" value="<?php echo html($review['comment_id']); ?>">
                                                    <button type="submit" class="text-red-500 hover:text-red-700 text-xs" title="コメントを削除">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </form>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <p class="text-gray-700 dark:text-gray-300 text-sm"><?php echo XSS::nl2brAutoLink($review['comment']); ?></p>

                                        <!-- いいねボタン（自分のレビュー以外） -->
                                        <?php if ($login_flag && $mine_user_id != $review['user_id']): ?>
                                        <div class="mt-2">
                                            <?php
                                            require_once(dirname(dirname(dirname(__FILE__))) . '/library/like_helpers.php');
                                            echo generateLikeButton(
                                                'review',
                                                $review['book_id'],
                                                $review['like_count'] ?? 0,
                                                $review['is_liked'] ?? false,
                                                ['review_user_id' => $review['user_id']]
                                            );
                                            ?>
                                        </div>
                                        <?php elseif (isset($review['like_count']) && $review['like_count'] > 0): ?>
                                        <!-- ログインしていないか自分のレビューの場合はいいね数のみ表示 -->
                                        <div class="mt-2">
                                            <span class="inline-flex items-center gap-1 px-3 py-1 text-sm text-gray-600 dark:text-gray-400">
                                                <i class="fas fa-heart text-red-500"></i>
                                                <span><?php echo number_format($review['like_count']); ?></span>
                                            </span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                        
                        <!-- 読者タブ -->
                        <div x-show="activeTab === 'readers'" x-transition>
                    <?php if (!empty($readers)): ?>
                        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 max-h-96 overflow-y-auto">
                            <?php foreach ($readers as $reader): ?>
                            <a href="/book/<?php echo html($reader['book_id']); ?>" 
                               class="flex items-center space-x-2 p-2 rounded hover:bg-gray-50 transition-colors">
                                <img src="<?php echo html(isset($reader['user_photo']) ? $reader['user_photo'] : '/img/no-image-user.png'); ?>" 
                                     alt="<?php echo html($reader['nickname']); ?>" 
                                     class="w-6 h-6 rounded-full">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center space-x-1">
                                        <span class="text-sm font-medium truncate"><?php echo html($reader['nickname']); ?></span>
                                        <?php if (isset($reader['user_level'])): ?>
                                            <?php echo getLevelBadgeHtml($reader['user_level'], 'xs'); ?>
                                        <?php endif; ?>
                                        <?php if (!empty($reader['has_review']) && $reader['has_review']): ?>
                                        <span class="text-green-500 ml-1" title="レビューあり">
                                            <i class="fas fa-comment-dots text-xs"></i>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                    <?php 
                                    $statusTexts = [
                                        BUY_SOMEDAY => 'いつか買う',
                                        NOT_STARTED => 'まだ読んでない', 
                                        READING_NOW => '読書中',
                                        READING_FINISH => '読了',
                                        READ_BEFORE => '昔読んだ'
                                    ];
                                    ?>
                                    <div class="text-xs text-gray-500">
                                        <?php echo html(isset($statusTexts[$reader['status']]) ? $statusTexts[$reader['status']] : ''); ?>
                                    </div>
                                </div>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8">
                            <i class="fas fa-users-slash text-3xl text-gray-300 mb-2"></i>
                            <p class="text-gray-500">まだ読者がいません</p>
                        </div>
                    <?php endif; ?>
                        </div>
                    </div>
                </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$d_content = ob_get_clean();

// 追加のスクリプト
ob_start();
?>
<script>
function copyToClipboard(text) {
    // eventからボタン要素を取得
    const button = event.currentTarget || event.target;
    const copyIcon = button.querySelector('#copy-icon');
    const checkIcon = button.querySelector('#check-icon');
    
    // navigator.clipboardが利用できない場合のフォールバック
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(function() {
            showCopySuccess(button, copyIcon, checkIcon);
        }).catch(function(err) {
            fallbackCopyToClipboard(text, button, copyIcon, checkIcon);
        });
    } else {
        // Clipboard APIが使えない場合のフォールバック
        fallbackCopyToClipboard(text, button, copyIcon, checkIcon);
    }
}

function showCopySuccess(button, copyIcon, checkIcon) {
    // ボタンの状態を変更
    const originalTitle = button.title;
    button.title = 'コピーしました！';
    button.classList.add('bg-green-600', 'scale-110');
    button.classList.remove('bg-gray-500');
    
    // アイコンを変更
    if (copyIcon && checkIcon) {
        copyIcon.classList.add('hidden');
        checkIcon.classList.remove('hidden');
    }
    
    // トースト通知を表示
    showToast('リンクをコピーしました！');
    
    // 2秒後に元に戻す
    setTimeout(() => {
        button.title = originalTitle;
        button.classList.remove('bg-green-600', 'scale-110');
        button.classList.add('bg-gray-500');
        
        if (copyIcon && checkIcon) {
            copyIcon.classList.remove('hidden');
            checkIcon.classList.add('hidden');
        }
    }, 2000);
}

function showToast(message) {
    // 既存のトーストがあれば削除
    const existingToast = document.getElementById('copy-toast');
    if (existingToast) {
        existingToast.remove();
    }
    
    // トースト要素を作成
    const toast = document.createElement('div');
    toast.id = 'copy-toast';
    toast.className = 'fixed bottom-4 right-4 bg-green-600 text-white px-6 py-3 rounded-lg shadow-lg transform transition-all duration-300 translate-y-full';
    toast.innerHTML = `
        <div class="flex items-center">
            <i class="fas fa-check-circle mr-2"></i>
            <span>${message}</span>
        </div>
    `;
    
    document.body.appendChild(toast);
    
    // アニメーションで表示
    setTimeout(() => {
        toast.classList.remove('translate-y-full');
        toast.classList.add('translate-y-0');
    }, 10);
    
    // 3秒後に削除
    setTimeout(() => {
        toast.classList.remove('translate-y-0');
        toast.classList.add('translate-y-full');
        setTimeout(() => {
            toast.remove();
        }, 300);
    }, 3000);
}

function fallbackCopyToClipboard(text, button, copyIcon, checkIcon) {
    // テキストエリアを作成してコピー
    const textArea = document.createElement("textarea");
    textArea.value = text;
    textArea.style.position = "fixed";
    textArea.style.left = "-999999px";
    textArea.style.top = "-999999px";
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    
    try {
        const successful = document.execCommand('copy');
        
        if (successful) {
            showCopySuccess(button, copyIcon, checkIcon);
        } else {
            showToast('コピーに失敗しました', 'error');
        }
    } catch (err) {
        showToast('お使いのブラウザではリンクのコピーがサポートされていません。', 'error');
    }
    
    document.body.removeChild(textArea);
}

// AI機能関数は外部ファイル（/js/book_detail_ai.js）から読み込まれます

// 読了日を更新する関数
async function updateFinishedDate(event, bookId) {
    event.preventDefault();
    const form = event.target;
    const dateInput = form.querySelector('input[name="finished_date"]');
    const finishedDate = dateInput.value;
    const submitButton = form.querySelector('button[type="submit"]');
    const originalHTML = submitButton.innerHTML;
    
    // ローディング表示
    submitButton.disabled = true;
    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    
    try {
        // FormDataを作成してPOST
        const formData = new FormData();
        formData.append('action', 'update_review');
        formData.append('book_id', bookId);
        formData.append('finished_date', finishedDate);
        formData.append('rating', '<?php echo html($user_book_info['rating'] ?? 0); ?>');
        formData.append('comment', '<?php echo html($user_book_info['memo'] ?? ''); ?>');
        formData.append('csrf_token', '<?php echo $csrf_token; ?>');
        
        const response = await fetch('/book_detail.php', {
            method: 'POST',
            body: formData
        });
        
        if (response.ok) {
            // ページをリロードして更新を反映
            window.location.reload();
        } else {
            showToast('エラーが発生しました', 'error');
            submitButton.disabled = false;
            submitButton.innerHTML = originalHTML;
        }
    } catch (error) {
        console.error('Error updating finished date:', error);
        showToast('エラーが発生しました', 'error');
        submitButton.disabled = false;
        submitButton.innerHTML = originalHTML;
    }
}

// 読書履歴のメモを更新する関数
async function updateProgressMemo(event, eventId, formElement) {
    event.preventDefault();
    const form = event.target;
    const memoInput = form.querySelector('input[name="memo"]');
    const newMemo = memoInput.value.trim();
    
    try {
        const response = await fetch('/api/update_progress_memo.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                event_id: eventId,
                memo: newMemo,
                csrf_token: '<?php echo $csrf_token; ?>'
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // 成功時は編集モードを終了して表示を更新
            const container = form.closest('[x-data]');
            const displayDiv = container.querySelector('div[x-show="!editing"]');
            const displaySpan = displayDiv.querySelector('.text-gray-700.flex-1') || displayDiv.querySelector('.dark\\:text-gray-300.flex-1');
            
            // 表示を更新
            if (newMemo) {
                displaySpan.innerHTML = escapeHtml(newMemo);
            } else {
                displaySpan.innerHTML = '<span class="text-gray-400">メモなし</span>';
            }
            
            // Alpine.jsのイベントを発火して編集モードを終了
            formElement.dispatchEvent(new CustomEvent('finish-edit', { bubbles: true }));
            
            showToast('メモを更新しました', 'success');
        } else {
            showToast(data.message || 'メモの更新に失敗しました', 'error');
        }
    } catch (error) {
        console.error('Error updating memo:', error);
        showToast('エラーが発生しました', 'error');
    }
}

// escapeHtml関数はcommon-utils.jsで定義済み

// classList compatibility helper functions
function addClass(el, className) {
    if (el.classList) {
        el.classList.add(className);
    } else {
        if (!hasClass(el, className)) {
            el.className += ' ' + className;
        }
    }
}

function removeClass(el, className) {
    if (el.classList) {
        el.classList.remove(className);
    } else {
        el.className = el.className.replace(new RegExp('(^|\\s)' + className + '(?:\\s|$)', 'g'), '$1').replace(/\s+/g, ' ').replace(/^\s*|\s*$/g, '');
    }
}

function hasClass(el, className) {
    if (el.classList) {
        return el.classList.contains(className);
    } else {
        return new RegExp('(^|\\s)' + className + '(?:\\s|$)').test(el.className);
    }
}

// 簡単なテスト関数
window.testAI = function() {
    alert('AI関数が正しく定義されています');
};

// AI書評アシスタント機能をインラインで定義
// 即座に実行される関数で定義
(function() {
    
    window.toggleAIAssistant = function() {
        try {
            var modal = document.getElementById('ai-assistant-modal');
            if (!modal) {
                createAIAssistantModal();
            } else {
                if (modal.style.display === 'none' || modal.style.display === '') {
                    modal.style.display = 'flex';
                    modal.style.alignItems = 'center';
                    modal.style.justifyContent = 'center';
                } else {
                    modal.style.display = 'none';
                }
            }
        } catch (error) {
            alert('エラーが発生しました: ' + error.message);
        }
    };

    window.createAIAssistantModal = function() {
        try {
            
            // モーダルコンテナを作成
            var modal = document.createElement('div');
            modal.id = 'ai-assistant-modal';
            modal.className = 'fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50';
            modal.style.display = 'flex';
            modal.style.alignItems = 'center';
            modal.style.justifyContent = 'center';
            modal.onclick = function(event) {
                if (event.target === modal) {
                    closeAIAssistantModal();
                }
            };
        
        // 簡略化されたHTMLを挿入
        modal.innerHTML = '<div class="relative mx-auto p-5 border border-gray-300 dark:border-gray-600 w-full max-w-2xl shadow-lg rounded-md bg-white dark:bg-gray-800" style="margin-top: 2rem;">' +
            '<div class="absolute top-3 right-3">' +
                '<button onclick="closeAIAssistantModal()" class="text-gray-400 hover:text-gray-600">' +
                    '<i class="fas fa-times text-xl"></i>' +
                '</button>' +
            '</div>' +
            '<div class="bg-gradient-to-r from-purple-50 to-indigo-50 rounded-lg p-6">' +
                '<h3 class="text-lg font-semibold text-gray-900 mb-4"><i class="fas fa-robot mr-2"></i>AI書評アシスタント</h3>' +
                '<p class="text-sm text-gray-600 mb-4">AIがあなたの書評作成をお手伝いします</p>' +
                '<div class="mb-4">' +
                    '<label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">感想やキーワードを入力してください（任意）</label>' +
                    '<textarea id="ai-user-input-modal" rows="3" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md" placeholder="例: すごく感動した、友情、成長物語など（空欄でも生成可能）"></textarea>' +
                    '<p class="text-xs text-gray-500 mt-1">何も入力しなくても、本の情報から書評を生成できます</p>' +
                '</div>' +
                '<button type="button" onclick="generateAIReviewModal()" class="w-full bg-purple-500 text-white py-2 px-4 rounded-md hover:bg-purple-600 mb-3">' +
                    '<i class="fas fa-magic mr-2"></i>AI書評を生成' +
                '</button>' +
                '<div id="ai-loading-modal" class="hidden bg-white dark:bg-gray-700 rounded-lg p-4 mb-3">' +
                    '<div class="text-center"><i class="fas fa-spinner fa-spin mr-2"></i>AI書評を生成中...</div>' +
                '</div>' +
                '<div id="ai-error-modal" class="hidden bg-red-50 border-l-4 border-red-400 p-3 mb-3">' +
                    '<p class="text-sm text-red-700"></p>' +
                '</div>' +
                '<div id="ai-review-result-modal" class="hidden bg-white dark:bg-gray-700 rounded-lg p-3">' +
                    '<h5 class="font-medium text-gray-900 mb-2 text-sm"><i class="fas fa-check-circle text-green-500 mr-1"></i>AIが生成した書評</h5>' +
                    '<textarea id="ai-generated-review-modal" rows="6" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md text-sm mb-3"></textarea>' +
                    '<button type="button" onclick="useGeneratedReviewModal()" class="bg-green-500 text-white py-2 px-4 rounded-md hover:bg-green-600 mr-2">' +
                        '<i class="fas fa-check mr-2"></i>この書評を使用' +
                    '</button>' +
                    '<button type="button" onclick="closeAIAssistantModal()" class="bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-gray-300 py-2 px-4 rounded-md hover:bg-gray-400 dark:hover:bg-gray-500">' +
                        '<i class="fas fa-times mr-2"></i>キャンセル' +
                    '</button>' +
                '</div>' +
            '</div>' +
        '</div>';
        
        // DOMに追加
        document.body.appendChild(modal);
        } catch (error) {
            alert('モーダル作成エラー: ' + error.message);
        }
    };

        window.closeAIAssistantModal = function() {
        var modal = document.getElementById('ai-assistant-modal');
        if (modal) {
            modal.style.display = 'none';
        }
    };

    window.generateAIReviewModal = function() {
    var bookTitleMeta = document.querySelector('meta[name="book-title"]');
    var bookTitle = bookTitleMeta ? bookTitleMeta.content : '';
    var bookAuthorMeta = document.querySelector('meta[name="book-author"]');
    var bookAuthor = bookAuthorMeta ? bookAuthorMeta.content : '';
    var ratingSelect = document.querySelector('select[name="rating"]');
    var currentRating = ratingSelect ? ratingSelect.value : '5';
    var userInputElement = document.getElementById('ai-user-input-modal');
    var userInput = userInputElement ? userInputElement.value : '';
    
    // 空の入力でも許可する（本の情報から推測してレビューを生成）
    if (!userInput || !userInput.trim()) {
        userInput = '本のタイトルと著者の情報から、この本の魅力を伝える書評を生成してください。';
    }
    
    // ローディング表示
    var loadingDiv = document.getElementById('ai-loading-modal');
    var errorDiv = document.getElementById('ai-error-modal');
    var resultDiv = document.getElementById('ai-review-result-modal');
    
    if (loadingDiv) removeClass(loadingDiv, 'hidden');
    if (errorDiv) addClass(errorDiv, 'hidden');
    if (resultDiv) addClass(resultDiv, 'hidden');
    
    // XMLHttpRequestを使用（古いブラウザ対応）
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/ai_review_simple.php', true);
    xhr.setRequestHeader('Content-Type', 'application/json');
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            if (loadingDiv) addClass(loadingDiv, 'hidden');
            
            if (xhr.status === 200) {
                try {
                    var data = JSON.parse(xhr.responseText);
                    
                    if (data.success) {
                        var generatedReviewTextarea = document.getElementById('ai-generated-review-modal');
                        if (generatedReviewTextarea) {
                            generatedReviewTextarea.value = data.review;
                        }
                        if (resultDiv) {
                            removeClass(resultDiv, 'hidden');
                        }
                    } else {
                        if (errorDiv) {
                            errorDiv.querySelector('p').textContent = data.error || 'エラーが発生しました';
                            removeClass(errorDiv, 'hidden');
                        }
                    }
                } catch (e) {
                    if (errorDiv) {
                        errorDiv.querySelector('p').textContent = '応答の解析に失敗しました';
                        removeClass(errorDiv, 'hidden');
                    }
                }
            } else {
                if (errorDiv) {
                    errorDiv.querySelector('p').textContent = '通信エラーが発生しました';
                    removeClass(errorDiv, 'hidden');
                }
            }
        }
    };
    
    xhr.send(JSON.stringify({
        action: 'generate_review',
        title: bookTitle,
        author: bookAuthor,
        user_input: userInput,
        rating: parseInt(currentRating)
    }));
    };

    window.useGeneratedReviewModal = function() {
    var generatedReviewElement = document.getElementById('ai-generated-review-modal');
    var generatedReview = generatedReviewElement ? generatedReviewElement.value : '';
    var memoTextarea = document.getElementById('memo');
    
    if (generatedReview && memoTextarea) {
        memoTextarea.value = generatedReview;
    }
    
    closeAIAssistantModal();
    };

    // 関数定義完了をログ出力
    
    // すべてのAI生成タグを追加する関数
    window.addAllGeneratedTags = function() {
        var tagButtons = document.querySelectorAll('#ai-tags-inline-list button[data-tag]');
        var input = document.querySelector('input[name="tag"]');
        
        if (!input || tagButtons.length === 0) return;
        
        // すべてのタグを配列に集める
        var allTags = [];
        tagButtons.forEach(function(button) {
            var tag = button.getAttribute('data-tag');
            if (tag) {
                allTags.push(tag);
            }
        });
        
        // 既存の値があれば追加、なければ新規設定
        if (input.value.trim()) {
            input.value = input.value + ', ' + allTags.join(', ');
        } else {
            input.value = allTags.join(', ');
        }
        
        // Alpine.jsのモデルを更新
        input.dispatchEvent(new Event('input', { bubbles: true }));
        
        // ボタンを無効化
        tagButtons.forEach(function(button) {
            button.disabled = true;
            button.className = 'px-3 py-1 bg-gray-200 text-gray-600 rounded-full text-xs cursor-not-allowed';
        });
    };
    
    // AIタグ生成関数（インライン版）
    window.generateAITagsInline = function() {
        
        var bookTitleMeta = document.querySelector('meta[name="book-title"]');
        var bookTitle = bookTitleMeta ? bookTitleMeta.content : '';
        var bookAuthorMeta = document.querySelector('meta[name="book-author"]');
        var bookAuthor = bookAuthorMeta ? bookAuthorMeta.content : '';
        var userReviewMeta = document.querySelector('meta[name="user-review"]');
        var userReview = userReviewMeta ? userReviewMeta.content : '';
        
        // パネルを表示
        document.getElementById('ai-tags-inline-panel').classList.remove('hidden');
        document.getElementById('ai-tags-inline-loading').classList.remove('hidden');
        document.getElementById('ai-tags-inline-result').classList.add('hidden');
        
        fetch('/book_detail_ajax.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                book_title: bookTitle,
                book_author: bookAuthor,
                user_review: userReview || '本のタイトルと著者から推測してタグを生成してください'
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.text();
        })
        .then(text => {
            
            // 空のレスポンスチェック
            if (!text || text.trim() === '') {
                throw new Error('Empty response from server');
            }
            
            try {
                return JSON.parse(text);
            } catch (e) {
                
                // 部分的なJSONレスポンスの処理を試行
                var partialMatch = text.match(/\{.*\}/);
                if (partialMatch) {
                    try {
                        return JSON.parse(partialMatch[0]);
                    } catch (e2) {
                    }
                }
                
                throw new Error('Invalid JSON response: ' + text.substring(0, 100));
            }
        })
        .then(data => {
            document.getElementById('ai-tags-inline-loading').classList.add('hidden');
            document.getElementById('ai-tags-inline-result').classList.remove('hidden');
            
            if (data.success && data.tags && data.tags.length > 0) {
                var tagsList = document.getElementById('ai-tags-inline-list');
                tagsList.innerHTML = ''; // クリア
                
                data.tags.forEach(function(tag) {
                    var button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'px-3 py-1 bg-green-100 text-green-800 rounded-full text-xs hover:bg-green-200 transition-colors';
                    button.textContent = tag;
                    button.setAttribute('data-tag', tag);
                    
                    // クロージャでtagを保持するonclickハンドラー
                    button.onclick = (function(tagValue) {
                        return function() {
                            // まず編集ボタンをクリックして編集モードを開始
                            var editButton = document.querySelector('[x-data] button');
                            if (editButton) {
                                editButton.click();
                            }
                            
                            // 少し待ってからタグを追加
                            setTimeout(function() {
                                var input = document.querySelector('input[name="tags"]');
                                if (input) {
                                    var currentValue = input.value.trim();
                                    
                                    // タグを追加
                                    if (currentValue) {
                                        // 重複チェック
                                        var tags = currentValue.split(',').map(function(t) { return t.trim(); });
                                        if (tags.indexOf(tagValue) === -1) {
                                            input.value = currentValue + ', ' + tagValue;
                                        }
                                    } else {
                                        input.value = tagValue;
                                    }
                                    
                                    // inputイベントを発火してx-modelを更新
                                    input.dispatchEvent(new Event('input', { bubbles: true }));
                                    
                                    // フォーカスを設定
                                    input.focus();
                                }
                            }, 200);
                            
                            // ボタンの状態を更新
                            this.textContent = '✓ ' + tagValue;
                            this.disabled = true;
                            this.className = 'px-3 py-1 bg-gray-200 text-gray-600 rounded-full text-xs cursor-not-allowed';
                        };
                    })(tag);
                    
                    tagsList.appendChild(button);
                    tagsList.appendChild(document.createTextNode(' '));
                });
            } else {
                document.getElementById('ai-tags-inline-list').innerHTML = 
                    '<p class="text-xs text-red-600">タグの生成に失敗しました</p>';
            }
        })
        .catch(error => {
            document.getElementById('ai-tags-inline-loading').classList.add('hidden');
            document.getElementById('ai-tags-inline-result').classList.remove('hidden');
            document.getElementById('ai-tags-inline-list').innerHTML = 
                '<p class="text-xs text-red-600">エラーが発生しました</p>';
        });
    };
    
    // すべての生成タグを一括追加
    window.addAllGeneratedTags = function() {
        // まず編集ボタンをクリックして編集モードを開始
        // より具体的なセレクタを使用して、タグ編集ボタンのみを選択
        var tagSection = document.querySelector('[x-data*="editingTags"]');
        var editButton = tagSection ? tagSection.querySelector('button[x-show="!editingTags"]') : null;
        
        if (editButton && !editButton.classList.contains('hidden')) {
            editButton.click();
        }
        
        // 少し待ってからタグを追加
        setTimeout(function() {
            var input = document.querySelector('input[name="tags"]');
            if (input) {
                var currentValue = input.value.trim();
                var currentTags = currentValue ? currentValue.split(',').map(function(t) { return t.trim(); }) : [];
                
                // すべての生成されたタグを取得
                var tagButtons = document.querySelectorAll('#ai-tags-inline-list button[data-tag]');
                var newTags = [];
                
                tagButtons.forEach(function(button) {
                    var tag = button.getAttribute('data-tag');
                    // 重複チェック
                    if (tag && currentTags.indexOf(tag) === -1 && newTags.indexOf(tag) === -1) {
                        newTags.push(tag);
                    }
                });
                
                // タグを追加
                if (newTags.length > 0) {
                    if (currentValue) {
                        input.value = currentValue + ', ' + newTags.join(', ');
                    } else {
                        input.value = newTags.join(', ');
                    }
                    
                    // inputイベントを発火してx-modelを更新
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                    
                    // すべてのボタンを無効化
                    tagButtons.forEach(function(button) {
                        button.disabled = true;
                        button.classList.add('bg-gray-200', 'text-gray-500', 'cursor-not-allowed');
                        button.classList.remove('bg-green-100', 'text-green-800', 'hover:bg-green-200');
                    });
                    
                    // フォーカスを設定
                    input.focus();
                }
            }
        }, 200);
    };
    
    // タグを削除する関数
    window.removeTag = function(tag, bookId) {
        // 現在のタグリストを取得
        var currentTags = [];
        var tagButtons = document.querySelectorAll('button[onclick*="removeTag"]');
        tagButtons.forEach(function(button) {
            var buttonTag = button.textContent.trim().replace(/×$/, '').trim();
            if (buttonTag !== tag) {
                currentTags.push(buttonTag);
            }
        });
        
        // フォームを作成して送信
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = '';
        
        var bookIdInput = document.createElement('input');
        bookIdInput.type = 'hidden';
        bookIdInput.name = 'book_id';
        bookIdInput.value = bookId;
        form.appendChild(bookIdInput);
        
        var actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'update_tags';
        form.appendChild(actionInput);
        
        var tagsInput = document.createElement('input');
        tagsInput.type = 'hidden';
        tagsInput.name = 'tags';
        tagsInput.value = currentTags.join(', ');
        form.appendChild(tagsInput);
        
        document.body.appendChild(form);
        form.submit();
    };
    
    // タグを追加する関数（フォーム送信用）
    window.addTag = function(formElement, bookId) {
        var input = formElement.querySelector('input[name="tag"]');
        if (!input || !input.value.trim()) return;
        
        // 現在のタグリストを取得
        var currentTags = [];
        var tagButtons = document.querySelectorAll('button[onclick*="removeTag"]');
        tagButtons.forEach(function(button) {
            var buttonTag = button.textContent.trim().replace(/×$/, '').trim();
            currentTags.push(buttonTag);
        });
        
        // 新しいタグを追加
        var newTag = input.value.trim();
        if (currentTags.indexOf(newTag) === -1) {
            currentTags.push(newTag);
        }
        
        // フォームを作成して送信
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = '';
        
        var bookIdInput = document.createElement('input');
        bookIdInput.type = 'hidden';
        bookIdInput.name = 'book_id';
        bookIdInput.value = bookId;
        form.appendChild(bookIdInput);
        
        var actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'update_tags';
        form.appendChild(actionInput);
        
        var tagsInput = document.createElement('input');
        tagsInput.type = 'hidden';
        tagsInput.name = 'tags';
        tagsInput.value = currentTags.join(', ');
        form.appendChild(tagsInput);
        
        document.body.appendChild(form);
        form.submit();
    };
    // AIタグ生成関数（編集モード用）
    window.generateAITagsForEdit = function() {
        const panel = document.getElementById('ai-tags-panel');
        const loadingDiv = document.getElementById('ai-tags-loading');
        const resultDiv = document.getElementById('ai-tags-result');
        const errorDiv = document.getElementById('ai-tags-error');
        const listDiv = document.getElementById('ai-tags-list');
        
        // パネルを表示
        panel.classList.remove('hidden');
        loadingDiv.classList.remove('hidden');
        resultDiv.classList.add('hidden');
        errorDiv.classList.add('hidden');
        
        // 書籍情報を取得
        const bookTitle = document.querySelector('meta[name="book-title"]')?.content || '';
        const bookAuthor = document.querySelector('meta[name="book-author"]')?.content || '';
        
        // AIタグ生成APIを呼び出し
        fetch('/book_detail_ajax.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                book_title: bookTitle,
                book_author: bookAuthor,
                user_review: ''
            })
        })
        .then(response => response.json())
        .then(data => {
            loadingDiv.classList.add('hidden');
            
            if (data.success && data.tags && data.tags.length > 0) {
                listDiv.innerHTML = '';
                
                data.tags.forEach(tag => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'px-3 py-1 bg-green-100 text-green-800 rounded-full text-xs hover:bg-green-200 transition-colors';
                    button.textContent = tag;
                    button.setAttribute('data-tag', tag);
                    button.onclick = function() {
                        addAITagToInput(tag);
                        this.disabled = true;
                        this.className = 'px-3 py-1 bg-gray-200 text-gray-500 rounded-full text-xs cursor-not-allowed';
                        this.innerHTML = '✓ ' + tag;
                    };
                    listDiv.appendChild(button);
                });
                
                resultDiv.classList.remove('hidden');
            } else {
                errorDiv.querySelector('p').textContent = data.error || 'タグの生成に失敗しました';
                errorDiv.classList.remove('hidden');
            }
        })
        .catch(error => {
            loadingDiv.classList.add('hidden');
            errorDiv.querySelector('p').textContent = 'エラーが発生しました';
            errorDiv.classList.remove('hidden');
        });
    };
    
    // 単一のAIタグを編集フィールドに追加
    window.addAITagToInput = function(tag) {
        const tagsInput = document.querySelector('[x-model="tags"]');
        
        if (tagsInput) {
            // Alpine.jsのデータを取得 - v3対応
            const alpineComponent = tagsInput.closest('[x-data]');
            
            if (alpineComponent) {
                // Alpine.js v3ではAlpine.$data()を使用
                let alpineData = null;
                if (window.Alpine && window.Alpine.$data) {
                    alpineData = window.Alpine.$data(alpineComponent);
                } else if (alpineComponent._x_dataStack) {
                    // 別の方法: _x_dataStackを使用
                    alpineData = alpineComponent._x_dataStack[0];
                }
                
                if (alpineData) {
                    const currentTags = alpineData.tags.split(',').map(t => t.trim()).filter(t => t);
                    
                    // 重複チェック
                    if (!currentTags.includes(tag)) {
                        const newValue = alpineData.tags ? alpineData.tags + ', ' + tag : tag;
                        alpineData.tags = newValue;
                        
                        // 手動でinputイベントをトリガー（Alpine.jsの更新を確実にする）
                        tagsInput.value = newValue;
                        tagsInput.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                }
            }
        }
    };
    
    // すべてのAIタグを編集フィールドに追加
    window.addAllAITagsToInput = function() {
        const buttons = document.querySelectorAll('#ai-tags-list button:not(:disabled)');
        const tagsInput = document.querySelector('[x-model="tags"]');
        
        if (tagsInput && buttons.length > 0) {
            // Alpine.jsのデータを取得 - v3対応
            const alpineComponent = tagsInput.closest('[x-data]');
            
            if (alpineComponent) {
                // Alpine.js v3ではAlpine.$data()を使用
                let alpineData = null;
                if (window.Alpine && window.Alpine.$data) {
                    alpineData = window.Alpine.$data(alpineComponent);
                } else if (alpineComponent._x_dataStack) {
                    // 別の方法: _x_dataStackを使用
                    alpineData = alpineComponent._x_dataStack[0];
                }
                
                if (alpineData) {
                    const currentTags = alpineData.tags.split(',').map(t => t.trim()).filter(t => t);
                    const newTags = [];
                    
                    buttons.forEach(button => {
                        const tag = button.getAttribute('data-tag') || button.textContent.replace('✓ ', '').trim();
                        
                        // 重複チェック
                        if (tag && !currentTags.includes(tag) && !newTags.includes(tag)) {
                            newTags.push(tag);
                        }
                        button.disabled = true;
                        button.className = 'px-3 py-1 bg-gray-200 text-gray-500 rounded-full text-xs cursor-not-allowed';
                        button.innerHTML = '✓ ' + tag;
                    });
                    
                    if (newTags.length > 0) {
                        const newValue = alpineData.tags 
                            ? alpineData.tags + ', ' + newTags.join(', ')
                            : newTags.join(', ');
                        alpineData.tags = newValue;
                        
                        // 手動でinputイベントをトリガー（Alpine.jsの更新を確実にする）
                        tagsInput.value = newValue;
                        tagsInput.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                }
            }
        }
    };
})(); // 即座実行関数終了

// ページ入力フィールドのカスタムバリデーションと自動フォーカス
document.addEventListener('DOMContentLoaded', function() {
    const currentPageInput = document.getElementById('current-page-input');
    
    if (currentPageInput) {
        <?php 
        // 未読了かどうかを判定
        $is_unfinished = isset($user_book_info['status']) && 
                        ($user_book_info['status'] == NOT_STARTED || $user_book_info['status'] == READING_NOW);
        if ($is_unfinished): 
        ?>
        // 未読了の本の場合、自動的にページ入力欄にフォーカス
        currentPageInput.focus();
        currentPageInput.select();
        <?php endif; ?>
        
        // カスタムバリデーションメッセージを設定
        currentPageInput.addEventListener('invalid', function(e) {
            e.preventDefault();
            
            const value = parseInt(this.value);
            const min = parseInt(this.min);
            const max = this.hasAttribute('max') ? parseInt(this.max) : null;
            
            let message = '';
            
            if (this.validity.valueMissing) {
                message = '現在のページを入力してください';
            } else if (this.validity.rangeUnderflow || value < min) {
                message = 'ページ番号は1以上で入力してください';
            } else if (max && (this.validity.rangeOverflow || value > max)) {
                message = `ページ番号は${max}以下で入力してください（本の総ページ数: ${max}ページ）`;
            } else if (this.validity.stepMismatch || !Number.isInteger(value)) {
                message = 'ページ番号は整数で入力してください';
            } else if (!max && value > 99999) {
                message = 'ページ番号が大きすぎます。本の総ページ数が設定されていない場合は、99999ページまで入力可能です';
            }
            
            this.setCustomValidity(message);
            
            // エラーメッセージを表示
            showToast(message, 'error');
        });
        
        // 入力が変更されたらカスタムバリデーションをリセット
        currentPageInput.addEventListener('input', function() {
            this.setCustomValidity('');
        });
        
        // フォーム送信時の追加チェック
        const form = currentPageInput.closest('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                const value = parseInt(currentPageInput.value);
                const max = currentPageInput.hasAttribute('max') ? parseInt(currentPageInput.max) : null;
                
                // 本の総ページ数が設定されていない場合の警告
                if (!max && value > 1000) {
                    if (!confirm(`現在のページが${value}ページと入力されていますが、本の総ページ数が設定されていません。\nこのまま保存しますか？`)) {
                        e.preventDefault();
                        currentPageInput.focus();
                        return false;
                    }
                }
            });
        }
    }
});
</script>

<script>
// お気に入りの切り替え
function toggleFavorite(bookId, button) {
    const icon = button.querySelector('i');
    const tooltip = button.querySelector('span');
    const isFavorite = icon.classList.contains('fas');
    
    // 即座にUIを更新（楽観的更新）
    if (isFavorite) {
        icon.classList.remove('fas');
        icon.classList.add('far');
        button.title = 'お気に入りに追加';
        if (tooltip) {
            tooltip.textContent = 'お気に入りに追加';
        }
    } else {
        icon.classList.remove('far');
        icon.classList.add('fas');
        button.title = 'お気に入りから削除';
        if (tooltip) {
            tooltip.textContent = 'お気に入りから削除';
        }
    }
    
    // サーバーにリクエスト
    fetch('/ajax/toggle_favorite.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'book_id=' + bookId + '&action=toggle'
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            // エラーの場合は元に戻す
            if (isFavorite) {
                icon.classList.add('fas');
                icon.classList.remove('far');
                button.title = 'お気に入りから削除';
                if (tooltip) {
                    tooltip.textContent = 'お気に入りから削除';
                }
            } else {
                icon.classList.add('far');
                icon.classList.remove('fas');
                button.title = 'お気に入りに追加';
                if (tooltip) {
                    tooltip.textContent = 'お気に入りに追加';
                }
            }
            alert(data.message || 'エラーが発生しました');
        }
    })
    .catch(error => {
        // エラーの場合は元に戻す
        if (isFavorite) {
            icon.classList.add('fas');
            icon.classList.remove('far');
            button.title = 'お気に入りから削除';
            if (tooltip) {
                tooltip.textContent = 'お気に入りから削除';
            }
        } else {
            icon.classList.add('far');
            icon.classList.remove('fas');
            button.title = 'お気に入りに追加';
            if (tooltip) {
                tooltip.textContent = 'お気に入りに追加';
            }
        }
        console.error('Error:', error);
        alert('通信エラーが発生しました');
    });
}

// 読書中の本の場合、モバイルで進捗入力欄に自動フォーカス
document.addEventListener('DOMContentLoaded', function() {
    <?php if (isset($user_book_info['status']) && $user_book_info['status'] == READING_NOW && $is_book_owner): ?>
    // 読書中の本の場合、モバイルデバイスで進捗入力欄に自動フォーカス
    const progressInput = document.getElementById('progress-page-input');
    if (progressInput && window.innerWidth <= 768) {
        // モバイルデバイスの場合
        setTimeout(function() {
            // 入力欄が画面内に表示されるようにスクロール
            progressInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
            // フォーカスを当てて値を選択
            progressInput.focus();
            progressInput.select();
        }, 300);
    }
    <?php endif; ?>
    
    // メソッドがPOSTのすべてのフォームを取得
    const forms = document.querySelectorAll('form[method="post"]');
    
    forms.forEach(form => {
        // すでにonsubmitハンドラがある場合はスキップ
        if (form.onsubmit || form.hasAttribute('@submit.prevent')) {
            return;
        }
        
        form.addEventListener('submit', function(e) {
            const submitButton = form.querySelector('button[type="submit"]');
            if (!submitButton) return;
            
            // ボタンのHTMLを保存
            const originalHTML = submitButton.innerHTML;
            const originalDisabled = submitButton.disabled;
            
            // ローディング表示に変更
            submitButton.disabled = true;
            
            // ボタンのテキストによって異なるローディング表示
            if (submitButton.textContent.includes('保存')) {
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>保存中...';
            } else if (submitButton.textContent.includes('追加')) {
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>追加中...';
            } else if (submitButton.textContent.includes('削除')) {
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>削除中...';
            } else if (submitButton.textContent.includes('投稿')) {
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>投稿中...';
            } else if (submitButton.textContent.includes('読み終わった')) {
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>処理中...';
            } else {
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            }
            
            // タイムアウト対策（10秒後に元に戻す）
            setTimeout(() => {
                submitButton.innerHTML = originalHTML;
                submitButton.disabled = originalDisabled;
            }, 10000);
        });
    });
});
</script>

<?php
$d_additional_scripts = ob_get_clean();

// ベーステンプレートを使用
include(__DIR__ . '/t_base.php');
?>