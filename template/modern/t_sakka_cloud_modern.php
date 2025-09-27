<?php
/**
 * 作家クラウドテンプレート（モダン版）
 */

if(!defined('CONFIG')) {
    error_log('direct access detected...');
    die('reference for this file is not allowed.');
}

// ヘルパー関数を読み込み
require_once(dirname(dirname(__DIR__)) . '/library/form_helpers.php');

// メインコンテンツを生成
ob_start();
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <!-- ヘッダー -->
    <div class="text-center mb-12">
        <h1 class="text-4xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent mb-4">
            みんなの作家クラウド
        </h1>
        <p class="text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">
            ReadNestで人気の作家を可視化。フォントサイズは読者数の多さを表しています
        </p>
    </div>
    
    <!-- 統計カード -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl p-6 text-white shadow-xl">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-sm mb-1">作家数</p>
                    <p class="text-3xl font-bold"><?php echo number_format($total_authors); ?></p>
                </div>
                <svg class="w-12 h-12 text-blue-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
            </div>
        </div>
        
        <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-2xl p-6 text-white shadow-xl">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-emerald-100 text-sm mb-1">作品数</p>
                    <p class="text-3xl font-bold"><?php echo number_format($total_books); ?></p>
                </div>
                <svg class="w-12 h-12 text-emerald-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                </svg>
            </div>
        </div>
        
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl p-6 text-white shadow-xl">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-100 text-sm mb-1">のべ読者数</p>
                    <p class="text-3xl font-bold"><?php echo number_format($total_readers); ?></p>
                </div>
                <svg class="w-12 h-12 text-purple-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
            </div>
        </div>
    </div>
    
    <!-- 作家クラウド -->
    <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-lg p-8 md:p-12">
        <div class="author-cloud text-center leading-relaxed">
            <?php if (!empty($authors)): ?>
                <?php
                // 最大値と最小値を取得
                $maxCount = max(array_column($authors, 'reader_count'));
                $minCount = min(array_column($authors, 'reader_count'));
                
                // 表示順をシャッフル
                shuffle($authors);
                
                // カラーパレット（グラデーション対応）
                $colors = [
                    'from-blue-500 to-blue-600',
                    'from-purple-500 to-purple-600',
                    'from-pink-500 to-pink-600',
                    'from-indigo-500 to-indigo-600',
                    'from-teal-500 to-teal-600',
                    'from-emerald-500 to-emerald-600',
                    'from-orange-500 to-orange-600',
                    'from-red-500 to-red-600'
                ];
                
                foreach ($authors as $index => $author):
                    $count = $author['reader_count'];
                    
                    // フォントサイズを計算（14px〜48px）
                    if ($maxCount > $minCount) {
                        $ratio = ($count - $minCount) / ($maxCount - $minCount);
                        $size = 14 + (34 * sqrt($ratio));
                    } else {
                        $size = 14;
                    }
                    
                    // カラーをランダムに選択
                    $colorClass = $colors[array_rand($colors)];
                    
                    $authorName = htmlspecialchars($author['author']);
                    $bookCount = $author['book_count'];
                    $readerCount = $author['reader_count'];
                    $avgRating = $author['average_rating'] ? number_format($author['average_rating'], 1) : '-';
                    
                    // 読者数に応じた特別なスタイル
                    $isPopular = $count > ($maxCount * 0.7);
                    $extraClass = $isPopular ? 'font-bold' : '';
                ?>
                    <a href="/author.php?name=<?php echo urlencode($author['author']); ?>" 
                       class="inline-block px-3 py-2 m-2 rounded-lg transition-all duration-300 hover:scale-110 hover:shadow-lg bg-gradient-to-r <?php echo $colorClass; ?> text-white <?php echo $extraClass; ?>"
                       style="font-size: <?php echo $size; ?>px;"
                       data-author-stats='<?php echo json_encode([
                           'name' => $authorName,
                           'readers' => $readerCount,
                           'books' => $bookCount,
                           'rating' => $avgRating
                       ]); ?>'>
                        <?php echo $authorName; ?>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center py-12">
                    <svg class="w-24 h-24 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                    <p class="text-gray-500 dark:text-gray-400 text-lg mb-2">作家データがまだ生成されていません</p>
                    <p class="text-gray-400 dark:text-gray-500 text-sm">しばらくお待ちください。自動的に生成されます。</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- 更新情報 -->
    <div class="mt-8 text-center">
        <?php
        // 最終更新日時を取得
        $last_update = $g_db->getOne("SELECT MAX(updated_at) FROM b_author_stats_cache");
        if ($last_update) {
            echo '<p class="text-sm text-gray-500 dark:text-gray-400">最終更新: ' . date('Y年m月d日 H:i', strtotime($last_update)) . '</p>';
        }
        ?>
    </div>
</div>

<!-- モーダル for 作家詳細 -->
<div id="authorModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-gray-800 rounded-2xl max-w-md w-full p-6 transform transition-all">
        <div class="flex justify-between items-start mb-4">
            <h3 id="modalAuthorName" class="text-xl font-bold text-gray-800 dark:text-gray-200"></h3>
            <button onclick="closeModal()" class="text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-400">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div class="space-y-3">
            <div class="flex items-center justify-between p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                <span class="text-gray-600 dark:text-gray-400">読者数</span>
                <span id="modalReaders" class="font-bold text-blue-600 dark:text-blue-400"></span>
            </div>
            <div class="flex items-center justify-between p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                <span class="text-gray-600 dark:text-gray-400">作品数</span>
                <span id="modalBooks" class="font-bold text-green-600 dark:text-green-400"></span>
            </div>
            <div class="flex items-center justify-between p-3 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                <span class="text-gray-600 dark:text-gray-400">平均評価</span>
                <span id="modalRating" class="font-bold text-purple-600 dark:text-purple-400"></span>
            </div>
        </div>
        <div class="mt-6 flex gap-3">
            <a id="modalViewLink" href="#" class="flex-1 bg-gradient-to-r from-blue-500 to-blue-600 text-white text-center py-3 rounded-lg hover:shadow-lg transition-shadow">
                作品を見る
            </a>
            <button onclick="closeModal()" class="flex-1 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 py-3 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                閉じる
            </button>
        </div>
    </div>
</div>

<script>
// モーダル制御
function showAuthorModal(element) {
    const stats = JSON.parse(element.getAttribute('data-author-stats'));
    document.getElementById('modalAuthorName').textContent = stats.name;
    document.getElementById('modalReaders').textContent = stats.readers.toLocaleString() + '人';
    document.getElementById('modalBooks').textContent = stats.books.toLocaleString() + '冊';
    document.getElementById('modalRating').textContent = stats.rating === '-' ? '評価なし' : '★' + stats.rating;
    document.getElementById('modalViewLink').href = '/author.php?name=' + encodeURIComponent(stats.name);
    document.getElementById('authorModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('authorModal').classList.add('hidden');
}

// 作家リンクにクリックイベントを追加
document.addEventListener('DOMContentLoaded', function() {
    const authorLinks = document.querySelectorAll('[data-author-stats]');
    authorLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            if (window.innerWidth < 768) { // モバイルではモーダル表示
                e.preventDefault();
                showAuthorModal(this);
            }
        });
    });
    
    // モーダル背景クリックで閉じる
    document.getElementById('authorModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });
});
</script>

<style>
.author-cloud a {
    font-family: 'Noto Sans JP', -apple-system, BlinkMacSystemFont, sans-serif;
    text-decoration: none;
    display: inline-block;
    white-space: nowrap;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

/* アニメーション */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.author-cloud a {
    animation: fadeIn 0.5s ease-out;
    animation-fill-mode: both;
}

.author-cloud a:nth-child(n) { animation-delay: calc(0.02s * var(--index)); }

/* レスポンシブ対応 */
@media (max-width: 640px) {
    .author-cloud a {
        font-size: calc(var(--size) * 0.8) !important;
        margin: 0.25rem !important;
        padding: 0.375rem 0.75rem !important;
    }
}
</style>

<?php
$d_content = ob_get_clean();

// ベーステンプレートを使用
include(__DIR__ . '/t_base.php');
?>