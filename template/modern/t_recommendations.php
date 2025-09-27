<?php
if(!defined('CONFIG')) {
    error_log('direct access detected...');
    die('reference for this file is not allowed.');
}

// データを取得
$highly_rated_books = $recommendation_data['highly_rated_books'] ?? [];
$reading_books = $recommendation_data['reading_books'] ?? [];
$favorite_authors = $recommendation_data['favorite_authors'] ?? [];
$embedding_recommendations = $recommendation_data['embedding_recommendations'] ?? [];
$author_recommendations = $recommendation_data['author_recommendations'] ?? [];
$popular_books = $recommendation_data['popular_books'] ?? [];
$starter_books = $recommendation_data['starter_books'] ?? [];
$base_book_info = $recommendation_data['base_book_info'] ?? null;
$rec_type = $recommendation_data['rec_type'] ?? 'popular';
$stats = $recommendation_data['stats'] ?? [];

// メインコンテンツを生成
ob_start();
?>

<!-- AIレコメンデーションページ -->
<div class="bg-gradient-to-br from-purple-50 via-pink-50 to-indigo-50 dark:from-gray-900 dark:via-gray-900 dark:to-gray-900 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- ヘッダー -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-gray-900 dark:text-gray-100 mb-3">
                <i class="fas fa-magic text-purple-600 dark:text-purple-400 mr-3"></i>
                AIレコメンデーション
                <a href="/help.php#ai-features" class="ml-3 text-base text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 transition-colors" title="AI機能のヘルプ">
                    <i class="fas fa-question-circle"></i>
                </a>
            </h1>
            <p class="text-lg text-gray-600 dark:text-gray-300">あなたの読書履歴を分析して、次に読むべき本をAIが提案します</p>
            
            
            <?php if ($rec_type === 'recommended' && !empty($highly_rated_books)): ?>
            <div class="mt-4 p-3 bg-purple-50 dark:bg-purple-900/30 rounded-lg inline-block">
                <p class="text-sm text-purple-700 dark:text-purple-300">
                    <i class="fas fa-lightbulb mr-2"></i>
                    <strong>提案ロジック：</strong>あなたが<span class="font-bold">★4以上</span>を付けた<span class="font-bold"><?php echo count($highly_rated_books); ?>冊</span>の本を分析し、
                    それらと<span class="font-bold">内容が似ている</span>本を探しています
                </p>
                <p class="text-xs text-purple-600 dark:text-purple-400 mt-2">
                    <i class="fas fa-info-circle mr-1"></i>
                    推薦は24時間キャッシュされます。新しい組み合わせを見たい場合は「新しい推薦」ボタンをクリックしてください。
                </p>
            </div>
            <?php elseif ($rec_type === 'popular'): ?>
            <div class="mt-4 p-3 bg-orange-50 dark:bg-orange-900/30 rounded-lg inline-block">
                <p class="text-sm text-orange-700 dark:text-orange-300">
                    <i class="fas fa-fire mr-2"></i>
                    <strong>提案ロジック：</strong>ReadNestユーザー全体で<span class="font-bold">人気が高く</span>、
                    <span class="font-bold">高評価</span>を得ている本を表示しています
                </p>
            </div>
            <?php elseif ($rec_type === 'author'): ?>
            <div class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/30 rounded-lg inline-block">
                <p class="text-sm text-blue-700 dark:text-blue-300">
                    <i class="fas fa-user-edit mr-2"></i>
                    <strong>提案ロジック：</strong>あなたが<span class="font-bold">2冊以上読んだ作家</span>の
                    <span class="font-bold">まだ読んでいない作品</span>を提案しています
                </p>
            </div>
            <?php endif; ?>
        </div>

        <!-- レコメンデーションタイプ選択 -->
        <div class="mb-6">
            <div class="flex justify-center gap-4 flex-wrap">
                <a href="/recommendations.php?type=recommended"
                   class="px-4 py-2 rounded-lg font-medium transition-all <?php echo $rec_type === 'recommended' ? 'bg-purple-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'; ?>">
                    <i class="fas fa-magic mr-2"></i>おすすめ
                </a>
                <!-- AI探索タブは一時的に非表示
                <a href="?type=ai_suggest" 
                   class="px-4 py-2 rounded-lg font-medium transition-all <?php echo $rec_type === 'ai_suggest' ? 'bg-purple-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100'; ?>">
                    <i class="fas fa-search-plus mr-2"></i>AI探索
                </a>
                -->
                <a href="/recommendations.php?type=popular"
                   class="px-4 py-2 rounded-lg font-medium transition-all <?php echo $rec_type === 'popular' ? 'bg-purple-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'; ?>">
                    <i class="fas fa-fire mr-2"></i>人気の本
                </a>
                <a href="/recommendations.php?type=author"
                   class="px-4 py-2 rounded-lg font-medium transition-all <?php echo $rec_type === 'author' ? 'bg-purple-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'; ?>">
                    <i class="fas fa-user-edit mr-2"></i>好きな作家
                </a>
            </div>
            
        </div>

        <!-- ベースとなる本の情報 -->
        <?php if ($base_book_info): ?>
        <div class="mb-6 bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-xl shadow-sm p-5 border border-purple-200 dark:border-purple-600">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0">
                    <i class="fas fa-book-reader text-purple-600 text-2xl"></i>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-2">基準本から類似作品を探しています</h3>
                    <div class="flex items-center gap-4 bg-white dark:bg-gray-800 rounded-lg p-3">
                        <?php if (!empty($base_book_info['image_url'])): ?>
                        <img src="<?php echo htmlspecialchars($base_book_info['image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($base_book_info['title']); ?>"
                             class="w-16 h-20 object-cover rounded shadow-sm">
                        <?php endif; ?>
                        <div>
                            <div class="font-bold text-lg text-gray-900 dark:text-gray-100"><?php echo htmlspecialchars($base_book_info['title']); ?></div>
                            <div class="text-sm text-gray-600 dark:text-gray-400"><?php echo htmlspecialchars($base_book_info['author']); ?></div>
                            <?php if (!empty($base_book_info['rating'])): ?>
                            <div class="text-sm mt-1">
                                <span class="text-yellow-500">
                                    <?php for ($i = 0; $i < $base_book_info['rating']; $i++): ?>
                                    <i class="fas fa-star"></i>
                                    <?php endfor; ?>
                                </span>
                                <span class="text-gray-500 dark:text-gray-400 ml-2">あなたの評価</span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <p class="text-sm text-purple-700 dark:text-purple-300 mt-3">
                        <i class="fas fa-info-circle mr-1"></i>
                        この本の<strong>文章スタイル、テーマ、ジャンル</strong>をAIが分析し、似た特徴を持つ本を探しました
                    </p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- 非同期ローディング表示 -->
        <?php if (isset($recommendation_data['use_async']) && $recommendation_data['use_async'] && $rec_type === 'recommended'): ?>
        <div id="async-loading" class="mb-8">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8 text-center">
                <div class="animate-spin rounded-full h-12 w-12 border-4 border-purple-500 border-t-transparent mx-auto mb-4"></div>
                <h3 class="text-xl font-semibold text-gray-700 dark:text-gray-300 mb-2">AIが推薦を準備しています...</h3>
                <p class="text-gray-500 dark:text-gray-400">あなたの読書履歴を分析中です</p>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Embeddingベースのレコメンデーション -->
        <div id="recommendation-content" class="mb-8" style="<?php echo (isset($recommendation_data['use_async']) && $recommendation_data['use_async'] && empty($embedding_recommendations) && $rec_type === 'recommended') ? 'display: none;' : ''; ?>">
            <?php if ($rec_type === 'recommended' || $base_book_info): ?>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                <?php if (!empty($embedding_recommendations)): ?>
                <div class="flex justify-between items-start mb-4">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 flex items-center">
                        <i class="fas fa-robot text-purple-600 dark:text-purple-400 mr-3"></i>
                        <?php if ($base_book_info): ?>
                            「<?php echo htmlspecialchars($base_book_info['title']); ?>」に似た作品
                        <?php else: ?>
                            AIが見つけたおすすめ本
                        <?php endif; ?>
                    </h2>
                    <?php if ($rec_type === 'recommended'): ?>
                    <button onclick="refreshRecommendations()" class="px-3 py-1.5 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 rounded-lg hover:bg-purple-200 dark:hover:bg-purple-900/40 transition-colors text-sm flex items-center gap-2">
                        <i class="fas fa-sync-alt"></i>
                        <span>新しい推薦</span>
                    </button>
                    <?php endif; ?>
                </div>
                <?php elseif (!(isset($recommendation_data['use_async']) && $recommendation_data['use_async'])): ?>
                <div class="text-center py-8">
                    <i class="fas fa-robot text-gray-300 text-5xl mb-4"></i>
                    <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        <?php if ($base_book_info): ?>
                            「<?php echo htmlspecialchars($base_book_info['title']); ?>」と十分に類似した本が見つかりませんでした
                        <?php else: ?>
                            十分に類似した本が見つかりませんでした
                        <?php endif; ?>
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        類似度70%以上の本のみを表示しています。<br>
                        より多くの本を評価すると、精度の高い推薦が可能になります。
                    </p>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($embedding_recommendations)): ?>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <?php foreach (array_slice($embedding_recommendations, 0, 12) as $book): ?>
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg p-4 hover:shadow-lg transition-all">
                        <div class="flex gap-4">
                            <div class="flex-shrink-0">
                                <?php 
                                $book_image = (!empty($book['image_url']) && $book['image_url'] !== 'NULL') 
                                            ? $book['image_url'] 
                                            : '/img/no-image-book.png';
                                ?>
                                <?php if (!empty($book['amazon_id'])): ?>
                                <a href="/book_entity.php?asin=<?php echo urlencode($book['amazon_id']); ?>">
                                    <img src="<?php echo htmlspecialchars($book_image); ?>" 
                                         alt="<?php echo htmlspecialchars($book['title']); ?>"
                                         class="w-20 h-28 object-cover rounded shadow-sm hover:opacity-90 transition-opacity"
                                         onerror="this.src='/img/no-image-book.png'">
                                </a>
                                <?php elseif (!empty($book['isbn'])): ?>
                                <a href="/book_entity.php?isbn=<?php echo urlencode($book['isbn']); ?>">
                                    <img src="<?php echo htmlspecialchars($book_image); ?>" 
                                         alt="<?php echo htmlspecialchars($book['title']); ?>"
                                         class="w-20 h-28 object-cover rounded shadow-sm hover:opacity-90 transition-opacity"
                                         onerror="this.src='/img/no-image-book.png'">
                                </a>
                                <?php else: ?>
                                <img src="<?php echo htmlspecialchars($book_image); ?>" 
                                     alt="<?php echo htmlspecialchars($book['title']); ?>"
                                     class="w-20 h-28 object-cover rounded shadow-sm"
                                     onerror="this.src='/img/no-image-book.png'">
                                <?php endif; ?>
                            </div>
                            
                            <div class="flex-1 min-w-0 text-left">
                                <h3 class="font-bold text-base mb-1 text-left text-gray-900 dark:text-gray-100">
                                    <?php if (!empty($book['amazon_id'])): ?>
                                    <a href="/book_entity.php?asin=<?php echo urlencode($book['amazon_id']); ?>" 
                                       class="hover:text-purple-600 dark:hover:text-purple-400 transition-colors">
                                        <?php echo htmlspecialchars($book['title']); ?>
                                    </a>
                                    <?php elseif (!empty($book['isbn'])): ?>
                                    <a href="/book_entity.php?isbn=<?php echo urlencode($book['isbn']); ?>" 
                                       class="hover:text-purple-600 dark:hover:text-purple-400 transition-colors">
                                        <?php echo htmlspecialchars($book['title']); ?>
                                    </a>
                                    <?php else: ?>
                                    <?php echo htmlspecialchars($book['title']); ?>
                                    <?php endif; ?>
                                </h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2 text-left">
                                    <?php echo htmlspecialchars($book['author']); ?>
                                </p>
                                
                                <!-- 説明文 -->
                                <?php if (!empty($book['description']) && $book['description'] !== 'NULL'): ?>
                                <div class="mb-2 p-2 bg-gray-50 dark:bg-gray-700 rounded">
                                    <p class="text-xs text-gray-700 dark:text-gray-300 line-clamp-2">
                                        <?php echo htmlspecialchars(mb_substr($book['description'], 0, 150)); ?><?php echo mb_strlen($book['description']) > 150 ? '...' : ''; ?>
                                    </p>
                                </div>
                                <?php endif; ?>
                                
                                <!-- メタ情報 -->
                                <div class="flex flex-wrap gap-2 mb-2 text-xs">
                                    <!-- 類似度と基準本 -->
                                    <?php if (!empty($book['base_book_title'])): ?>
                                    <div class="w-full mb-1">
                                        <span class="text-purple-700 dark:text-purple-300 font-semibold">
                                            「<?php echo htmlspecialchars(mb_strimwidth($book['base_book_title'], 0, 40, '...')); ?>」に類似
                                        </span>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <!-- 類似度 -->
                                    <?php if (!empty($book['similarity'])): ?>
                                    <span class="inline-flex items-center px-2 py-0.5 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 rounded">
                                        <i class="fas fa-percentage mr-1"></i>
                                        類似度 <?php echo $book['similarity']; ?>%
                                    </span>
                                    <?php endif; ?>
                                    
                                    <!-- 平均評価 -->
                                    <?php if (!empty($book['avg_rating']) && $book['avg_rating'] > 0): ?>
                                    <span class="inline-flex items-center px-2 py-0.5 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300 rounded">
                                        <i class="fas fa-star mr-1"></i>
                                        <?php echo number_format($book['avg_rating'], 1); ?>
                                    </span>
                                    <?php endif; ?>
                                    
                                    <!-- カテゴリ -->
                                    <?php if (!empty($book['google_categories'])): ?>
                                    <?php 
                                    $categories = explode('/', $book['google_categories']);
                                    $main_category = end($categories);
                                    ?>
                                    <span class="inline-flex items-center px-2 py-0.5 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded">
                                        <i class="fas fa-tag mr-1"></i>
                                        <?php echo htmlspecialchars($main_category); ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- アクションボタン -->
                                <div class="flex gap-2">
                                    <!-- 本棚に追加 -->
                                    <a href="/add_book.php?asin=<?php echo urlencode($book['amazon_id']); ?>" 
                                       class="inline-flex items-center text-xs px-3 py-1.5 bg-purple-600 text-white rounded hover:bg-purple-700 transition-colors">
                                        <i class="fas fa-plus mr-1"></i>本棚に追加
                                    </a>
                                    
                                    <!-- 類似本を探す -->
                                    <?php if (!empty($book['book_id'])): ?>
                                    <a href="/recommendations.php?type=similar&book_id=<?php echo $book['book_id']; ?>" 
                                       class="inline-flex items-center text-xs px-3 py-1.5 bg-indigo-600 text-white rounded hover:bg-indigo-700 transition-colors">
                                        <i class="fas fa-search mr-1"></i>類似本
                                    </a>
                                    <?php endif; ?>
                                    
                                    <!-- Amazon検索リンク -->
                                    <a href="https://www.amazon.co.jp/s?k=<?php echo urlencode($book['title'] . ' ' . $book['author']); ?>" 
                                       target="_blank"
                                       class="inline-flex items-center text-xs px-3 py-1.5 bg-orange-500 text-white rounded hover:bg-orange-600 transition-colors">
                                        <i class="fab fa-amazon mr-1"></i>Amazon
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- 作家別レコメンデーション -->
        <?php if (!empty($author_recommendations) && is_array($author_recommendations) && isset($author_recommendations[0])): ?>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4 text-gray-900 dark:text-gray-100 flex items-center">
                <i class="fas fa-user-edit text-indigo-600 dark:text-indigo-400 mr-3"></i>
                お気に入り作家の新作
                <span class="ml-3 text-sm text-gray-500 dark:text-gray-400">まだ読んでいない作品</span>
            </h2>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <?php 
                $author_list = array_filter($author_recommendations, function($item) {
                    return isset($item['amazon_id']); // 配列形式の本のみ
                });
                foreach (array_slice($author_list, 0, 12) as $book): 
                ?>
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg p-4 hover:shadow-lg transition-all">
                    <div class="flex gap-4">
                        <div class="flex-shrink-0">
                            <?php 
                            $book_image = (!empty($book['image_url']) && $book['image_url'] !== 'NULL') 
                                        ? $book['image_url'] 
                                        : '/img/no-image-book.png';
                            ?>
                            <img src="<?php echo htmlspecialchars($book_image); ?>" 
                                 alt="<?php echo htmlspecialchars($book['title']); ?>"
                                 class="w-20 h-28 object-cover rounded shadow-sm"
                                 onerror="this.src='/img/no-image-book.png'">
                        </div>
                        
                        <div class="flex-1 min-w-0 text-left">
                            <h3 class="font-bold text-base mb-1 text-left text-gray-900 dark:text-gray-100">
                                <?php if (!empty($book['amazon_id'])): ?>
                                <a href="/book_entity.php?asin=<?php echo urlencode($book['amazon_id']); ?>" 
                                   class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                                    <?php echo htmlspecialchars($book['title']); ?>
                                </a>
                                <?php elseif (!empty($book['isbn'])): ?>
                                <a href="/book_entity.php?isbn=<?php echo urlencode($book['isbn']); ?>" 
                                   class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                                    <?php echo htmlspecialchars($book['title']); ?>
                                </a>
                                <?php else: ?>
                                <?php echo htmlspecialchars($book['title']); ?>
                                <?php endif; ?>
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-2 text-left">
                                <?php echo htmlspecialchars($book['author']); ?>
                            </p>
                            
                            <!-- 説明文 -->
                            <?php if (!empty($book['description'])): ?>
                            <div class="mb-2 p-2 bg-gray-50 dark:bg-gray-700 rounded">
                                <p class="text-xs text-gray-700 dark:text-gray-300 line-clamp-2">
                                    <?php echo htmlspecialchars(mb_substr($book['description'], 0, 150)); ?><?php echo mb_strlen($book['description']) > 150 ? '...' : ''; ?>
                                </p>
                            </div>
                            <?php endif; ?>
                            
                            <!-- メタ情報 -->
                            <div class="flex flex-wrap gap-2 mb-2 text-xs">
                                <!-- 作家推薦 -->
                                <span class="inline-flex items-center px-2 py-0.5 bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 rounded">
                                    <i class="fas fa-user-edit mr-1"></i>
                                    お気に入り作家
                                </span>
                                
                                <!-- 評価 -->
                                <?php if (!empty($book['avg_rating']) && $book['avg_rating'] > 0): ?>
                                <span class="inline-flex items-center px-2 py-0.5 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300 rounded">
                                    <i class="fas fa-star mr-1"></i>
                                    <?php echo number_format($book['avg_rating'], 1); ?>
                                </span>
                                <?php endif; ?>
                                
                                <!-- 読者数 -->
                                <?php if (!empty($book['reader_count']) && $book['reader_count'] > 0): ?>
                                <span class="inline-flex items-center px-2 py-0.5 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded">
                                    <i class="fas fa-users mr-1"></i>
                                    <?php echo $book['reader_count']; ?>人
                                </span>
                                <?php endif; ?>
                            </div>
                            
                            <!-- アクションボタン -->
                            <div class="flex gap-2">
                                <!-- 本棚に追加 -->
                                <a href="/add_book.php?asin=<?php echo urlencode($book['amazon_id']); ?>" 
                                   class="inline-flex items-center text-xs px-3 py-1.5 bg-purple-600 text-white rounded hover:bg-purple-700 transition-colors">
                                    <i class="fas fa-plus mr-1"></i>本棚に追加
                                </a>
                                
                                <!-- Amazon検索リンク -->
                                <a href="https://www.amazon.co.jp/s?k=<?php echo urlencode($book['title'] . ' ' . $book['author']); ?>" 
                                   target="_blank"
                                   class="inline-flex items-center text-xs px-3 py-1.5 bg-orange-500 text-white rounded hover:bg-orange-600 transition-colors">
                                    <i class="fab fa-amazon mr-1"></i>Amazon
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- 全体の人気本 -->
        <?php if (!empty($popular_books) && is_array($popular_books) && isset($popular_books[0])): ?>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4 text-gray-900 dark:text-gray-100 flex items-center">
                <i class="fas fa-fire text-red-500 dark:text-red-400 mr-3"></i>
                みんなが読んでいる本
                <span class="ml-3 text-sm text-gray-500 dark:text-gray-400">読者数・評価順</span>
            </h2>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <?php 
                $popular_list = array_filter($popular_books, function($item) {
                    return isset($item['amazon_id']); // 配列形式の本のみ
                });
                foreach (array_slice($popular_list, 0, 12) as $book): 
                ?>
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg p-4 hover:shadow-lg transition-all">
                    <div class="flex gap-4">
                            <div class="flex-shrink-0">
                            <?php 
                            $book_image = (!empty($book['image_url']) && $book['image_url'] !== 'NULL') 
                                        ? $book['image_url'] 
                                        : '/img/no-image-book.png';
                            ?>
                            <img src="<?php echo htmlspecialchars($book_image); ?>" 
                                 alt="<?php echo htmlspecialchars($book['title']); ?>"
                                 class="w-20 h-28 object-cover rounded shadow-sm"
                                 onerror="this.src='/img/no-image-book.png'">
                        </div>
                        
                        <div class="flex-1 min-w-0 text-left">
                            <h3 class="font-bold text-base mb-1 text-left text-gray-900 dark:text-gray-100">
                                <?php if (!empty($book['amazon_id'])): ?>
                                <a href="/book_entity.php?asin=<?php echo urlencode($book['amazon_id']); ?>" 
                                   class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                                    <?php echo htmlspecialchars($book['title']); ?>
                                </a>
                                <?php elseif (!empty($book['isbn'])): ?>
                                <a href="/book_entity.php?isbn=<?php echo urlencode($book['isbn']); ?>" 
                                   class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                                    <?php echo htmlspecialchars($book['title']); ?>
                                </a>
                                <?php else: ?>
                                <?php echo htmlspecialchars($book['title']); ?>
                                <?php endif; ?>
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-2 text-left">
                                <?php echo htmlspecialchars($book['author']); ?>
                            </p>
                            
                            <!-- 説明文 -->
                            <?php if (!empty($book['description'])): ?>
                            <div class="mb-2 p-2 bg-gray-50 dark:bg-gray-700 rounded">
                                <p class="text-xs text-gray-700 dark:text-gray-300 line-clamp-2">
                                    <?php echo htmlspecialchars(mb_substr($book['description'], 0, 150)); ?><?php echo mb_strlen($book['description']) > 150 ? '...' : ''; ?>
                                </p>
                            </div>
                            <?php endif; ?>
                            
                            <!-- メタ情報 -->
                            <div class="flex flex-wrap gap-2 mb-2 text-xs">
                                <!-- 読者数 -->
                                <span class="inline-flex items-center px-2 py-0.5 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded">
                                    <i class="fas fa-fire mr-1"></i>
                                    人気 <?php echo $book['reader_count']; ?>人
                                </span>
                                
                                <!-- 評価 -->
                                <?php if (!empty($book['avg_rating']) && $book['avg_rating'] > 0): ?>
                                <span class="inline-flex items-center px-2 py-0.5 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300 rounded">
                                    <i class="fas fa-star mr-1"></i>
                                    <?php echo number_format($book['avg_rating'], 1); ?>
                                </span>
                                <?php endif; ?>
                                
                                <!-- 読了数 -->
                                <?php if (!empty($book['finished_count']) && $book['finished_count'] > 0): ?>
                                <span class="inline-flex items-center px-2 py-0.5 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 rounded">
                                    <i class="fas fa-check mr-1"></i>
                                    読了 <?php echo $book['finished_count']; ?>人
                                </span>
                                <?php endif; ?>
                            </div>
                            
                            <!-- アクションボタン -->
                            <div class="flex gap-2">
                                <!-- 本棚に追加 -->
                                <a href="/add_book.php?asin=<?php echo urlencode($book['amazon_id']); ?>" 
                                   class="inline-flex items-center text-xs px-3 py-1.5 bg-purple-600 text-white rounded hover:bg-purple-700 transition-colors">
                                    <i class="fas fa-plus mr-1"></i>本棚に追加
                                </a>
                                
                                <!-- Amazon検索リンク -->
                                <a href="https://www.amazon.co.jp/s?k=<?php echo urlencode($book['title'] . ' ' . $book['author']); ?>" 
                                   target="_blank"
                                   class="inline-flex items-center text-xs px-3 py-1.5 bg-orange-500 text-white rounded hover:bg-orange-600 transition-colors">
                                    <i class="fab fa-amazon mr-1"></i>Amazon
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- 高評価本リスト（おすすめタブの時のみ表示） -->
        <?php if ($rec_type === 'recommended' && !empty($highly_rated_books)): ?>
        <div class="mb-8">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-semibold mb-4 text-gray-900 dark:text-gray-100 flex items-center">
                    <i class="fas fa-star text-yellow-500 dark:text-yellow-400 mr-3"></i>
                    レコメンデーションの基準本
                    <span class="ml-3 text-sm text-gray-500 dark:text-gray-400">（クリックで個別に類似本を探せます）</span>
                </h2>
                
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                    <?php foreach ($highly_rated_books as $book): ?>
                    <a href="/recommendations.php?type=similar&book_id=<?php echo $book['book_id']; ?>" 
                       class="hover:opacity-90 transition-all hover:scale-105 group relative"
                       title="クリックで類似本を探す">
                        <div class="text-center relative">
                            <?php 
                            $image_url = (!empty($book['image_url']) && $book['image_url'] !== 'NULL' && $book['image_url'] !== '') 
                                        ? $book['image_url'] 
                                        : '/img/no-image-book.png';
                            
                            // embeddingの状態を確認
                            $has_embedding = !empty($book['combined_embedding']);
                            $is_dynamic = isset($book['dynamically_generated']) && $book['dynamically_generated'];
                            ?>
                            
                            <!-- Embeddingステータスバッジ -->
                            <?php if (!$has_embedding && empty($book['combined_embedding'])): ?>
                            <div class="absolute top-0 right-0 z-10">
                                <span class="inline-block px-1 py-0.5 text-xs rounded-bl bg-orange-500 text-white" title="選択時にAIが分析します">
                                    <i class="fas fa-magic"></i>
                                </span>
                            </div>
                            <?php elseif ($is_dynamic): ?>
                            <div class="absolute top-0 right-0 z-10">
                                <span class="inline-block px-1 py-0.5 text-xs rounded-bl bg-green-500 text-white" title="AI分析済み">
                                    <i class="fas fa-check"></i>
                                </span>
                            </div>
                            <?php endif; ?>
                            
                            <img src="<?php echo htmlspecialchars($image_url); ?>" 
                                 alt="<?php echo htmlspecialchars($book['title']); ?>"
                                 class="w-full h-32 object-cover rounded shadow-sm mb-2 group-hover:shadow-lg"
                                 onerror="this.src='/img/no-image-book.png'">
                            <div class="text-xs font-semibold text-gray-800 dark:text-gray-200 mb-1 truncate px-1" title="<?php echo htmlspecialchars($book['title']); ?>">
                                <?php echo htmlspecialchars($book['title']); ?>
                            </div>
                            <div class="text-xs text-gray-600 dark:text-gray-400 mb-1 truncate px-1">
                                <?php echo htmlspecialchars($book['author']); ?>
                            </div>
                            <div class="text-xs text-yellow-500">
                                <?php for ($i = 0; $i < $book['rating']; $i++): ?>
                                <i class="fas fa-star"></i>
                                <?php endfor; ?>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
                
                <div class="mt-4 text-sm text-gray-600 dark:text-gray-400 border-t dark:border-gray-700 pt-3">
                    <p class="mb-1">
                        <i class="fas fa-info-circle mr-1"></i>
                        <span class="font-semibold"><?php echo count($highly_rated_books); ?>冊</span>の高評価本から選択できます
                    </p>
                    <?php 
                    $no_embedding_count = 0;
                    foreach ($highly_rated_books as $book) {
                        if (empty($book['combined_embedding'])) $no_embedding_count++;
                    }
                    if ($no_embedding_count > 0): 
                    ?>
                    <p class="text-orange-600 dark:text-orange-400">
                        <i class="fas fa-magic mr-1"></i>
                        <?php echo $no_embedding_count; ?>冊は選択時に自動的にAI分析されます
                    </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- データ不足の場合のメッセージ（非同期の場合は非表示） -->
        <?php if (empty($embedding_recommendations) && empty($author_recommendations) && empty($popular_books) && !(isset($recommendation_data['use_async']) && $recommendation_data['use_async'])): ?>
        <div id="no-data-message" class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8 text-center">
            <i class="fas fa-robot text-6xl text-gray-300 dark:text-gray-600 mb-4"></i>
            <h3 class="text-xl font-semibold text-gray-700 dark:text-gray-300 mb-2">レコメンデーションを生成できません</h3>
            <p class="text-gray-500 dark:text-gray-400 mb-4">
                <?php if (empty($highly_rated_books) && empty($reading_books)): ?>
                    まだ十分な読書データがありません。<br>
                    本を読んで評価をつけると、AIがあなたの好みを学習します。
                <?php else: ?>
                    類似本のデータを収集中です。<br>
                    しばらくお待ちください。
                <?php endif; ?>
            </p>
            <a href="/bookshelf.php" class="inline-block px-6 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                <i class="fas fa-book mr-2"></i>
                本棚に戻る
            </a>
        </div>
        <?php endif; ?>

    </div>
</div>

<!-- 非同期読み込み用JavaScript -->
<?php if (isset($recommendation_data['use_async']) && $recommendation_data['use_async']): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 現在のタブタイプを取得
    const urlParams = new URLSearchParams(window.location.search);
    const recType = urlParams.get('type') || 'recommended';
    const bookId = urlParams.get('book_id') || '';
    const refresh = urlParams.get('refresh') || '';
    
    // recommendedタブの場合のみ非同期読み込みを実行
    if (recType === 'recommended') {
        const loadingDiv = document.getElementById('async-loading');
        const contentDiv = document.getElementById('recommendation-content');
        
        if (loadingDiv) {
            // APIを呼び出してデータを取得
            let apiUrl = `/api/recommendations_data.php?type=${recType}`;
            if (bookId) {
                apiUrl += `&book_id=${bookId}`;
            }
            if (refresh === 'true') {
                apiUrl += '&refresh=true';
            }
            
            fetch(apiUrl)
                .then(response => response.json())
                .then(data => {
                    
                    // データが取得できたらページをリロード
                    if (data.success) {
                        // 単純にページをリロードする（非同期モードを無効にして通常版が読み込まれる）
                        window.location.href = `/recommendations.php?type=${recType}` + (bookId ? `&book_id=${bookId}` : '');
                    } else {
                        // エラーメッセージを表示
                        if (loadingDiv) {
                            loadingDiv.innerHTML = `
                                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8 text-center">
                                    <i class="fas fa-exclamation-triangle text-6xl text-orange-500 dark:text-orange-400 mb-4"></i>
                                    <h3 class="text-xl font-semibold text-gray-700 dark:text-gray-300 mb-2">エラーが発生しました</h3>
                                    <p class="text-gray-500 dark:text-gray-400 mb-4">${data.message || 'データの取得に失敗しました'}</p>
                                    <button onclick="location.reload()" class="px-6 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                                        <i class="fas fa-redo mr-2"></i>再試行
                                    </button>
                                </div>
                            `;
                        }
                    }
                })
                .catch(error => {
                    if (loadingDiv) {
                        loadingDiv.innerHTML = `
                            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8 text-center">
                                <i class="fas fa-exclamation-triangle text-6xl text-red-500 dark:text-red-400 mb-4"></i>
                                <h3 class="text-xl font-semibold text-gray-700 dark:text-gray-300 mb-2">通信エラー</h3>
                                <p class="text-gray-500 dark:text-gray-400 mb-4">サーバーとの通信に失敗しました</p>
                                <button onclick="location.reload()" class="px-6 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                                    <i class="fas fa-redo mr-2"></i>再試行
                                </button>
                            </div>
                        `;
                    }
                });
        }
    }
});
</script>
<?php endif; ?>

<!-- 説明文詳細表示用JavaScript -->
<script>
function showDescription(description) {
    alert(description);
}

// 推薦再生成関数
function refreshRecommendations() {
    if (confirm('新しい推薦を生成しますか？（既存のキャッシュはクリアされます）')) {
        window.location.href = '/recommendations.php?type=recommended&refresh=true';
    }
}
</script>

<?php
$d_content = ob_get_clean();

// ベーステンプレートを使用
include(__DIR__ . '/t_base.php');
?>