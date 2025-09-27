<?php
/**
 * お気に入り本一覧テンプレート
 */

if(!defined('CONFIG')) {
    error_log('direct access detected...');
    die('reference for this file is not allowed.');
}

// メインコンテンツを生成
ob_start();
?>

<!-- お気に入りページヘッダー -->
<section class="bg-gradient-to-r from-yellow-50 to-orange-50 dark:from-yellow-900/20 dark:to-orange-900/20 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 flex items-center">
                    <i class="fas fa-star text-yellow-500 dark:text-yellow-400 mr-3"></i>
                    <?php if ($is_own_favorites): ?>
                    お気に入りの本
                    <?php else: ?>
                    <?php echo html($target_nickname); ?>さんのお気に入り
                    <?php endif; ?>
                </h1>
                <p class="mt-2 text-gray-600 dark:text-gray-400">
                    <?php echo count($books); ?>冊のお気に入り
                    <?php if (!$is_own_favorites): ?>
                    （公開分のみ）
                    <?php endif; ?>
                </p>
            </div>
            <div class="flex gap-2">
                <a href="/profile.php<?php echo $is_own_favorites ? '' : '?user_id=' . $target_user_id; ?>" 
                   class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors text-sm inline-flex items-center text-gray-900 dark:text-gray-100">
                    <i class="fas fa-user mr-2"></i>
                    プロフィールを見る
                </a>
                <?php if ($is_own_favorites && isset($is_public_user) && $is_public_user): ?>
                <button onclick="togglePrivacyModal()" 
                        class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors text-sm text-gray-900 dark:text-gray-100">
                    <i class="fas fa-cog mr-2"></i>
                    公開設定
                </button>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if ($is_own_favorites && isset($is_public_user) && $is_public_user): ?>
        <!-- 公開設定の説明 -->
        <div class="mt-6 bg-white dark:bg-gray-800 rounded-lg p-4 border border-blue-200 dark:border-blue-700">
            <div class="flex items-start space-x-3">
                <i class="fas fa-info-circle text-blue-500 dark:text-blue-400 mt-1"></i>
                <div class="text-sm text-gray-700 dark:text-gray-300">
                    <p class="font-semibold mb-2 text-gray-900 dark:text-gray-100">お気に入りの公開設定について</p>
                    <ul class="space-y-1">
                        <li class="flex items-center">
                            <i class="fas fa-eye text-green-500 dark:text-green-400 mr-2 w-4"></i>
                            <span class="text-gray-700 dark:text-gray-300">緑の目アイコン: プロフィールページで公開中</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-eye-slash text-gray-400 dark:text-gray-500 mr-2 w-4"></i>
                            <span class="text-gray-700 dark:text-gray-300">グレーの目アイコン: プロフィールページで非公開</span>
                        </li>
                    </ul>
                    <p class="mt-2 text-xs text-gray-600 dark:text-gray-400">
                        ※ 左上の目アイコンをクリックして、個別に公開/非公開を切り替えできます
                    </p>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- お気に入り本一覧 -->
<section class="py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <?php if (empty($books)): ?>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-12 text-center">
            <i class="fas fa-star text-gray-300 text-6xl mb-4"></i>
            <h2 class="text-xl font-semibold text-gray-700 dark:text-gray-300 mb-2">お気に入りがまだありません</h2>
            <p class="text-gray-500 dark:text-gray-400 mb-6">本棚から気に入った本を⭐マークで登録してください</p>
            <a href="/bookshelf.php" class="inline-flex items-center px-6 py-3 bg-readnest-primary text-white rounded-lg hover:bg-readnest-primary-dark transition-colors">
                <i class="fas fa-book mr-2"></i>
                本棚へ移動
            </a>
        </div>
        <?php else: ?>
        
        <!-- AI推薦への案内（上部に配置） -->
        <?php if ($is_own_favorites && !empty($ai_recommendations)): ?>
        <div class="mb-6 p-4 bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-lg border border-purple-200 dark:border-purple-600">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <i class="fas fa-robot text-purple-600 dark:text-purple-400 text-2xl"></i>
                    <div>
                        <h3 class="font-semibold text-purple-900 dark:text-purple-100">AI推薦が利用可能です</h3>
                        <p class="text-sm text-purple-700 dark:text-purple-300">お気に入りの本を分析して、<?php echo count($ai_recommendations); ?>冊の類似本を見つけました</p>
                    </div>
                </div>
                <a href="#ai-recommendations" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition-colors text-sm font-medium flex items-center">
                    <span>推薦を見る</span>
                    <i class="fas fa-chevron-down ml-2"></i>
                </a>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($is_own_favorites): ?>
        <!-- ドラッグモード切り替えボタン -->
        <div class="mb-4 flex justify-end">
            <button id="toggle-drag-mode" 
                    class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors text-sm">
                <i class="fas fa-arrows-alt mr-2"></i>
                並び替えモード
            </button>
        </div>
        <?php endif; ?>
        
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4" id="favorites-grid">
            <?php foreach ($books as $book): ?>
            <div class="favorite-book-item bg-white dark:bg-gray-800 rounded-lg shadow-sm border dark:border-gray-600 hover:shadow-md transition-shadow relative" 
                 data-book-id="<?php echo $book['book_id']; ?>"
                 draggable="false">
                <div class="relative">
                    <a href="/book_detail.php?book_id=<?php echo $book['book_id']; ?>" class="block">
                        <div class="relative w-full" style="padding-bottom: 133.33%;">
                            <img src="<?php echo html($book['image_url']); ?>" 
                                 alt="<?php echo html($book['title']); ?>" 
                                 class="absolute inset-0 w-full h-full object-cover rounded-t-lg"
                                 loading="lazy"
                                 onerror="this.src='/img/no-image-book.png'">
                        </div>
                    </a>
                    
                    <?php if ($is_own_favorites): ?>
                    <!-- お気に入り解除ボタン -->
                    <div class="absolute top-2 right-2 group/tooltip">
                        <button onclick="event.preventDefault(); event.stopPropagation(); removeFavorite(<?php echo $book['book_id']; ?>, this); return false;"
                                class="w-8 h-8 bg-white bg-opacity-90 rounded-full flex items-center justify-center hover:bg-opacity-100 transition-all shadow-md z-10">
                            <i class="fas fa-star text-yellow-500"></i>
                        </button>
                        <div class="absolute right-0 top-10 bg-gray-800 text-white text-xs px-2 py-1 rounded opacity-0 group-hover/tooltip:opacity-100 transition-opacity pointer-events-none whitespace-nowrap">
                            お気に入りから削除
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($is_own_favorites && isset($is_public_user) && $is_public_user): ?>
                    <!-- 公開設定トグル -->
                    <div class="absolute top-2 left-2 group/tooltip">
                        <button onclick="event.preventDefault(); event.stopPropagation(); toggleBookPrivacy(<?php echo $book['book_id']; ?>, this); return false;"
                                class="w-8 h-8 bg-white bg-opacity-90 rounded-full flex items-center justify-center hover:bg-opacity-100 transition-all shadow-md z-10"
                                data-book-id="<?php echo $book['book_id']; ?>"
                                data-is-public="<?php echo $book['is_public']; ?>">
                            <i class="fas fa-<?php echo $book['is_public'] ? 'eye' : 'eye-slash'; ?> text-<?php echo $book['is_public'] ? 'green-500' : 'gray-400'; ?>"></i>
                        </button>
                        <div class="absolute left-0 top-10 bg-gray-800 text-white text-xs px-2 py-1 rounded opacity-0 group-hover/tooltip:opacity-100 transition-opacity pointer-events-none whitespace-nowrap">
                            <?php echo $book['is_public'] ? 'プロフィールで公開中' : 'プロフィールで非公開'; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="p-3">
                    <h3 class="font-medium text-sm text-gray-900 dark:text-gray-100 line-clamp-2 mb-1">
                        <a href="/book_detail.php?book_id=<?php echo $book['book_id']; ?>" class="hover:text-readnest-primary transition-colors">
                            <?php echo html($book['title']); ?>
                        </a>
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 line-clamp-1 mb-2"><?php echo html($book['author']); ?></p>
                    
                    <!-- 評価 -->
                    <?php if ($book['rating'] > 0): ?>
                    <div class="flex items-center mb-2">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                        <i class="<?php echo $i <= $book['rating'] ? 'fas' : 'far'; ?> fa-star text-yellow-400 text-xs"></i>
                        <?php endfor; ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- 更新日 -->
                    <p class="text-xs text-gray-400 dark:text-gray-500">
                        <i class="far fa-clock mr-1"></i><?php echo $book['update_date']; ?>
                    </p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- AI推薦セクション -->
<?php if ($is_own_favorites && !empty($ai_recommendations)): ?>
<section class="py-8 bg-gradient-to-r from-purple-50 to-pink-50 dark:from-gray-800 dark:to-gray-900" id="ai-recommendations">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6 flex items-center">
                <i class="fas fa-robot text-purple-600 dark:text-purple-400 mr-3"></i>
                お気に入りの本に基づくAI推薦
            </h2>
            
            <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4 mb-6">
                <p class="text-sm text-gray-700 dark:text-gray-300">
                    <i class="fas fa-info-circle mr-1 text-purple-600"></i>
                    あなたのお気に入りの本それぞれと似た本を探しました。
                    各本には最も類似しているお気に入り本のタイトルを表示しています。
                </p>
            </div>
            
            <?php if (empty($ai_recommendations)): ?>
            <div class="text-center py-8">
                <i class="fas fa-search text-gray-300 text-4xl mb-3"></i>
                <p class="text-gray-600 dark:text-gray-400 mb-2">現在のお気に入りの本と十分に類似した本が見つかりませんでした</p>
                <p class="text-sm text-gray-500 dark:text-gray-500">より多くの本をお気に入りに追加すると、精度の高い推薦が可能になります</p>
            </div>
            <?php else: ?>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                <?php foreach ($ai_recommendations as $rec): ?>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-lg transition-all hover:-translate-y-1" 
                     x-data="{ showDescription: false }">
                    <div class="relative">
                        <!-- 類似度バッジ -->
                        <div class="absolute top-2 left-2 z-10">
                            <span class="inline-block px-2 py-1 text-xs rounded-full 
                                         <?php echo $rec['similarity'] >= 75 ? 'bg-green-500' : ($rec['similarity'] >= 65 ? 'bg-yellow-500' : 'bg-orange-500'); ?> 
                                         text-white font-semibold"
                                 title="「<?php echo htmlspecialchars($rec['base_book_title']); ?>」との類似度">
                                <?php echo $rec['similarity']; ?>%
                            </span>
                        </div>
                        
                        <!-- 本の画像（クリックで詳細） -->
                        <a href="/add_book.php?asin=<?php echo urlencode($rec['asin']); ?>" 
                           class="block">
                            <img src="<?php echo html($rec['image_url']); ?>" 
                                 alt="<?php echo html($rec['title']); ?>"
                                 class="w-full h-48 object-cover rounded-t-lg hover:opacity-90 transition-opacity"
                                 onerror="this.src='/img/no-image-book.png'">
                        </a>
                    </div>
                    
                    <div class="p-3">
                        <h3 class="font-semibold text-sm text-gray-900 dark:text-gray-100 mb-1 line-clamp-2" title="<?php echo html($rec['title']); ?>">
                            <a href="/add_book.php?asin=<?php echo urlencode($rec['asin']); ?>"
                               class="text-gray-900 dark:text-gray-100 hover:text-purple-600 dark:hover:text-purple-400 transition-colors">
                                <?php echo html($rec['title']); ?>
                            </a>
                        </h3>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mb-1 truncate">
                            <?php echo html($rec['author']); ?>
                        </p>
                        
                        <!-- 基準本の表示 -->
                        <?php if (!empty($rec['base_book_title'])): ?>
                        <div class="text-xs text-purple-600 dark:text-purple-400 mb-2 truncate font-medium">
                            「<?php echo htmlspecialchars(mb_strimwidth($rec['base_book_title'], 0, 30, '...')); ?>」に類似
                        </div>
                        <?php endif; ?>
                        
                        <!-- 統計情報 -->
                        <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 mb-3">
                            <?php if ($rec['reader_count'] > 0): ?>
                            <span>
                                <i class="fas fa-user-friends mr-1"></i>
                                <?php echo $rec['reader_count']; ?>人
                            </span>
                            <?php endif; ?>
                            
                            <?php if ($rec['avg_rating'] > 0): ?>
                            <span class="text-yellow-500 dark:text-yellow-400">
                                <i class="fas fa-star mr-1"></i>
                                <?php echo $rec['avg_rating']; ?>
                            </span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- アクションボタン -->
                        <div class="flex gap-2">
                            <!-- 本棚に追加ボタン -->
                            <form action="/add_book.php" method="post" class="flex-1">
                                <?php csrfFieldTag(); ?>
                                <input type="hidden" name="asin" value="<?php echo html($rec['asin']); ?>">
                                <input type="hidden" name="title" value="<?php echo html($rec['title']); ?>">
                                <input type="hidden" name="author" value="<?php echo html($rec['author']); ?>">
                                <input type="hidden" name="image_url" value="<?php echo html($rec['image_url']); ?>">
                                <button type="submit" 
                                        class="w-full px-2 py-1.5 bg-purple-600 text-white text-xs rounded-md hover:bg-purple-700 transition-colors flex items-center justify-center">
                                    <i class="fas fa-plus mr-1"></i>
                                    追加
                                </button>
                            </form>
                            
                            <!-- 説明文表示ボタン -->
                            <button @click="showDescription = !showDescription" 
                                    class="px-2 py-1.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs rounded-md hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                                <i class="fas fa-info-circle"></i>
                            </button>
                        </div>
                        
                        <!-- 説明文ポップアップ -->
                        <div x-show="showDescription" 
                             x-transition
                             @click.away="showDescription = false"
                             class="absolute bottom-full left-0 right-0 mb-2 p-3 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 z-20">
                            <button @click="showDescription = false" 
                                    class="absolute top-1 right-1 text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300">
                                <i class="fas fa-times text-xs"></i>
                            </button>
                            <h4 class="font-semibold text-xs text-gray-900 dark:text-gray-100 mb-1"><?php echo html($rec['title']); ?></h4>
                            <?php if (!empty($rec['description'])): ?>
                                <p class="text-xs text-gray-600 dark:text-gray-400 max-h-32 overflow-y-auto">
                                    <?php echo html(mb_substr($rec['description'], 0, 200)); ?>...
                                </p>
                            <?php else: ?>
                                <p class="text-xs text-gray-500 dark:text-gray-400 italic">説明文はまだ登録されていません</p>
                            <?php endif; ?>
                            <div class="mt-2 pt-2 border-t border-gray-100 dark:border-gray-700">
                                <p class="text-xs text-purple-600 dark:text-purple-400">
                                    <i class="fas fa-percentage mr-1"></i>
                                    類似度: <?php echo $rec['similarity']; ?>%
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <!-- もっと探すリンク -->
            <div class="mt-6 text-center">
                <a href="/recommendations.php?type=recommended" 
                   class="inline-flex items-center px-6 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                    <i class="fas fa-search mr-2"></i>
                    もっと推薦を見る
                </a>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<script>
<?php if ($is_own_favorites): ?>
// お気に入りから削除
function removeFavorite(bookId, button) {
    if (!confirm('お気に入りから削除しますか？')) {
        return;
    }
    
    // カードを取得（favorite-book-itemクラスを使用）
    const card = button.closest('.favorite-book-item');
    if (!card) {
        console.error('Card not found');
        return;
    }
    
    // カードを半透明にする
    card.style.opacity = '0.5';
    card.style.pointerEvents = 'none';
    
    fetch('/ajax/toggle_favorite.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'book_id=' + bookId + '&action=remove'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // スムーズなアニメーションで削除
            card.style.transition = 'all 0.3s ease-out';
            card.style.transform = 'scale(0.8)';
            card.style.opacity = '0';
            
            setTimeout(() => {
                card.remove();
                
                // グリッド内の残りのカード数をチェック
                const remainingCards = document.querySelectorAll('#favorites-grid .favorite-book-item').length;
                if (remainingCards === 0) {
                    // 0件になったらページをリロード
                    location.reload();
                }
            }, 300);
        } else {
            // エラーの場合は元に戻す
            card.style.opacity = '1';
            card.style.pointerEvents = 'auto';
            alert(data.message || 'エラーが発生しました');
        }
    })
    .catch(error => {
        // エラーの場合は元に戻す
        card.style.opacity = '1';
        card.style.pointerEvents = 'auto';
        console.error('Error:', error);
        alert('通信エラーが発生しました');
    });
}

// 個別の本の公開設定を切り替え
function toggleBookPrivacy(bookId, button) {
    const icon = button.querySelector('i');
    const tooltipDiv = button.parentElement.querySelector('div');
    const isPublic = icon.classList.contains('fa-eye');
    const newIsPublic = isPublic ? 0 : 1;
    
    // アイコンとツールチップを即座に更新（楽観的UI）
    if (newIsPublic) {
        icon.classList.remove('fa-eye-slash', 'text-gray-400');
        icon.classList.add('fa-eye', 'text-green-500');
        button.dataset.isPublic = '1';
        if (tooltipDiv) {
            tooltipDiv.textContent = 'プロフィールで公開中';
        }
    } else {
        icon.classList.remove('fa-eye', 'text-green-500');
        icon.classList.add('fa-eye-slash', 'text-gray-400');
        button.dataset.isPublic = '0';
        if (tooltipDiv) {
            tooltipDiv.textContent = 'プロフィールで非公開';
        }
    }
    
    // サーバーに送信
    fetch('/favorites.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: `action=update_privacy&book_id=${bookId}&is_public=${newIsPublic}`
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            // エラーの場合は元に戻す
            if (newIsPublic) {
                icon.classList.remove('fa-eye', 'text-green-500');
                icon.classList.add('fa-eye-slash', 'text-gray-400');
                button.dataset.isPublic = '0';
                if (tooltipDiv) {
                    tooltipDiv.textContent = 'プロフィールで非公開';
                }
            } else {
                icon.classList.remove('fa-eye-slash', 'text-gray-400');
                icon.classList.add('fa-eye', 'text-green-500');
                button.dataset.isPublic = '1';
                if (tooltipDiv) {
                    tooltipDiv.textContent = 'プロフィールで公開中';
                }
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        // エラーの場合は元に戻す
        if (newIsPublic) {
            icon.classList.remove('fa-eye', 'text-green-500');
            icon.classList.add('fa-eye-slash', 'text-gray-400');
            button.dataset.isPublic = '0';
            if (tooltipDiv) {
                tooltipDiv.textContent = 'プロフィールで非公開';
            }
        } else {
            icon.classList.remove('fa-eye-slash', 'text-gray-400');
            icon.classList.add('fa-eye', 'text-green-500');
            button.dataset.isPublic = '1';
            if (tooltipDiv) {
                tooltipDiv.textContent = 'プロフィールで公開中';
            }
        }
    });
}


// 公開設定モーダルを表示
function togglePrivacyModal() {
    // モーダルを作成
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
    modal.innerHTML = `
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-md w-full mx-4">
            <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-4">お気に入りの公開設定</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
                お気に入りの本をプロフィールページで公開する設定を選択してください。
            </p>

            <div class="space-y-3">
                <button onclick="updateAllPrivacy(1)" class="w-full bg-green-500 text-white py-3 px-4 rounded-lg hover:bg-green-600 transition-colors">
                    <i class="fas fa-eye mr-2"></i>
                    すべてのお気に入りを公開する
                </button>

                <button onclick="updateAllPrivacy(0)" class="w-full bg-gray-500 text-white py-3 px-4 rounded-lg hover:bg-gray-600 transition-colors">
                    <i class="fas fa-eye-slash mr-2"></i>
                    すべてのお気に入りを非公開にする
                </button>

                <button onclick="closeModal(this)" class="w-full border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 py-3 px-4 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    キャンセル
                </button>
            </div>

            <p class="text-xs text-gray-500 dark:text-gray-400 mt-4">
                ※ 個別の設定は、各本の左上にある目のアイコンから変更できます
            </p>
        </div>
    `;
    
    // モーダルを表示
    document.body.appendChild(modal);
    
    // 背景クリックで閉じる
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.remove();
        }
    });
}

// モーダルを閉じる
function closeModal(button) {
    const modal = button.closest('.fixed');
    if (modal) {
        modal.remove();
    }
}

// 全お気に入りの公開設定を更新
function updateAllPrivacy(isPublic) {
    const message = isPublic ? 
        'すべてのお気に入りを「プロフィールで公開」に設定しますか？' : 
        'すべてのお気に入りを「プロフィールで非公開」に設定しますか？';
    
    if (confirm(message)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/favorites.php';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'update_all_privacy';
        
        const publicInput = document.createElement('input');
        publicInput.type = 'hidden';
        publicInput.name = 'is_public';
        publicInput.value = isPublic.toString();
        
        form.appendChild(actionInput);
        form.appendChild(publicInput);
        document.body.appendChild(form);
        form.submit();
    }
}
// ドラッグ&ドロップ機能（シンプル版）
let dragMode = false;
let draggedElement = null;

// ドラッグモードの切り替え
const toggleBtn = document.getElementById('toggle-drag-mode');
if (toggleBtn) {
    toggleBtn.addEventListener('click', function() {
        dragMode = !dragMode;
        
        if (dragMode) {
            enableDragMode();
            this.classList.remove('bg-gray-100', 'dark:bg-gray-700', 'text-gray-700', 'dark:text-gray-300');
            this.classList.add('bg-blue-500', 'text-white');
            this.innerHTML = '<i class="fas fa-check mr-2"></i>完了';
        } else {
            disableDragMode();
            this.classList.remove('bg-blue-500', 'text-white');
            this.classList.add('bg-gray-100', 'dark:bg-gray-700', 'text-gray-700', 'dark:text-gray-300');
            this.innerHTML = '<i class="fas fa-arrows-alt mr-2"></i>並び替えモード';
        }
    });
}

// ドラッグモードを有効化
function enableDragMode() {
    const items = document.querySelectorAll('.favorite-book-item');
    
    items.forEach(item => {
        // ドラッグ可能にする
        item.draggable = true;
        item.classList.add('cursor-move');
        
        // ドラッグハンドルを表示
        if (!item.querySelector('.drag-handle')) {
            const dragHandle = document.createElement('div');
            dragHandle.className = 'drag-handle absolute top-2 left-2 w-8 h-8 bg-blue-500 bg-opacity-80 rounded-full flex items-center justify-center text-white z-20';
            dragHandle.innerHTML = '<i class="fas fa-grip-vertical"></i>';
            item.appendChild(dragHandle);
        }
        
        // ドラッグイベントの設定
        item.addEventListener('dragstart', handleDragStart);
        item.addEventListener('dragend', handleDragEnd);
        item.addEventListener('dragover', handleDragOver);
        item.addEventListener('drop', handleDrop);
        item.addEventListener('dragenter', handleDragEnter);
    });
}

// ドラッグモードを無効化
function disableDragMode() {
    const items = document.querySelectorAll('.favorite-book-item');
    
    items.forEach(item => {
        item.draggable = false;
        item.classList.remove('cursor-move');
        
        // ドラッグハンドルを削除
        const dragHandle = item.querySelector('.drag-handle');
        if (dragHandle) {
            dragHandle.remove();
        }
        
        // イベントリスナーを削除
        item.removeEventListener('dragstart', handleDragStart);
        item.removeEventListener('dragend', handleDragEnd);
        item.removeEventListener('dragover', handleDragOver);
        item.removeEventListener('drop', handleDrop);
        item.removeEventListener('dragenter', handleDragEnter);
    });
    
    // 並び順を保存
    saveOrder();
}

// ドラッグ開始
function handleDragStart(e) {
    draggedElement = this;
    this.classList.add('opacity-50');
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/html', this.innerHTML);
}

// ドラッグ終了
function handleDragEnd(e) {
    this.classList.remove('opacity-50');
    
    // すべてのハイライトを削除
    const items = document.querySelectorAll('.favorite-book-item');
    items.forEach(item => {
        item.classList.remove('drag-over');
    });
}

// ドラッグオーバー
function handleDragOver(e) {
    if (e.preventDefault) {
        e.preventDefault();
    }
    e.dataTransfer.dropEffect = 'move';
    
    const draggingOver = this;
    const grid = document.getElementById('favorites-grid');
    const allItems = [...grid.querySelectorAll('.favorite-book-item')];
    const draggingOverIndex = allItems.indexOf(draggingOver);
    const draggingIndex = allItems.indexOf(draggedElement);
    
    if (draggingOver !== draggedElement) {
        if (draggingIndex < draggingOverIndex) {
            draggingOver.parentNode.insertBefore(draggedElement, draggingOver.nextSibling);
        } else {
            draggingOver.parentNode.insertBefore(draggedElement, draggingOver);
        }
    }
    
    return false;
}

// ドラッグエンター
function handleDragEnter(e) {
    if (this !== draggedElement) {
        this.classList.add('drag-over');
    }
}

// ドロップ
function handleDrop(e) {
    if (e.stopPropagation) {
        e.stopPropagation();
    }
    return false;
}

// 並び順を保存
function saveOrder() {
    const grid = document.getElementById('favorites-grid');
    const items = grid.querySelectorAll('.favorite-book-item');
    const bookIds = Array.from(items).map(item => item.dataset.bookId);
    
    // Ajaxで保存
    fetch('/ajax/update_favorites_order.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ book_ids: bookIds })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // 成功時のフィードバック
            showNotification('並び順を保存しました', 'success');
        } else {
            showNotification('保存に失敗しました', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('エラーが発生しました', 'error');
    });
}

// 通知表示
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `fixed bottom-4 right-4 px-6 py-3 rounded-lg shadow-lg transition-all z-50 ${
        type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'
    }`;
    notification.innerHTML = `
        <div class="flex items-center">
            <i class="fas fa-${
                type === 'success' ? 'check-circle' : 'exclamation-circle'
            } mr-2"></i>
            ${message}
        </div>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.classList.add('opacity-0');
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}
<?php endif; ?>
</script>

<style>
/* ドラッグ中のスタイル */
.favorite-book-item.drag-over {
    background-color: #eff6ff;
    border: 2px solid #3b82f6;
}

/* ダークモード対応 */
@media (prefers-color-scheme: dark) {
    .favorite-book-item.drag-over {
        background-color: #1e3a8a;
        border: 2px solid #60a5fa;
    }
}

/* ドラッグモード中のカーソル */
.cursor-move {
    cursor: move !important;
}

/* ドラッグハンドルのホバー効果 */
.drag-handle:hover {
    transform: scale(1.1);
}
</style>

<?php
$d_content = ob_get_clean();

// bodyにクラスを追加してJavaScriptで識別できるようにする
$body_class = 'favorites-page';

// ベーステンプレートを使用
include(__DIR__ . '/t_base.php');
?>