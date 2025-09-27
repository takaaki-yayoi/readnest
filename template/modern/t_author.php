<?php
if(!defined('CONFIG')) {
    error_log('direct access detected...');
    die('reference for this file is not allowed.');
}

// メインコンテンツを生成
ob_start();
?>

<!-- ヘッダーセクション -->
<section class="bg-gradient-to-r from-purple-600 to-pink-600 dark:from-gray-800 dark:to-gray-700 text-white py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h1 class="text-3xl sm:text-4xl md:text-5xl font-bold mb-4">
                <?php echo htmlspecialchars($author_name); ?>
            </h1>
            <p class="text-lg sm:text-xl text-white opacity-90">
                作家紹介
            </p>
        </div>
    </div>
</section>

<!-- 作家情報セクション -->
<section class="py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <?php if ($author_info && !empty($author_info['description'])): ?>
        <!-- 作家詳細情報 -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
            <div class="flex flex-col md:flex-row gap-6">
                <?php if (!empty($author_info['image_url'])): ?>
                <div class="flex-shrink-0">
                    <img src="<?php echo htmlspecialchars($author_info['image_url']); ?>" 
                         alt="<?php echo htmlspecialchars($author_name); ?>" 
                         class="w-40 h-40 md:w-48 md:h-48 rounded-lg object-cover shadow-md">
                </div>
                <?php endif; ?>
                
                <div class="flex-1">
                    <!-- 基本情報 -->
                    <div class="flex flex-wrap gap-4 text-sm text-gray-600 mb-4">
                        <?php if (!empty($author_info['birth_date'])): ?>
                        <span class="flex items-center gap-1">
                            <i class="fas fa-calendar-alt"></i>
                            <?php 
                            $birth_year = date('Y', strtotime($author_info['birth_date']));
                            $death_year = !empty($author_info['death_date']) ? date('Y', strtotime($author_info['death_date'])) : null;
                            echo $birth_year;
                            if ($death_year) echo ' - ' . $death_year;
                            ?>
                        </span>
                        <?php endif; ?>
                        
                        <?php if (!empty($author_info['nationality'])): ?>
                        <span class="flex items-center gap-1">
                            <i class="fas fa-globe"></i>
                            <?php echo htmlspecialchars($author_info['nationality']); ?>
                        </span>
                        <?php endif; ?>
                        
                        <?php if (!empty($author_info['genres'])): ?>
                        <span class="flex items-center gap-1">
                            <i class="fas fa-tags"></i>
                            <?php echo htmlspecialchars(implode('、', array_slice($author_info['genres'], 0, 3))); ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- 説明文 -->
                    <div class="text-gray-700 mb-4">
                        <?php echo nl2br(htmlspecialchars($author_info['description'])); ?>
                    </div>
                    
                    <!-- 代表作 -->
                    <?php if (!empty($author_info['notable_works'])): ?>
                    <div class="mb-4">
                        <h3 class="text-sm font-semibold text-gray-600 mb-2">代表作</h3>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach (array_slice($author_info['notable_works'], 0, 5) as $work): ?>
                            <span class="px-3 py-1 bg-purple-50 rounded-full text-sm text-purple-700">
                                『<?php echo htmlspecialchars($work); ?>』
                            </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- リンク -->
                    <div class="flex flex-wrap gap-3">
                        <?php if (!empty($author_info['wikipedia_url'])): ?>
                        <a href="<?php echo htmlspecialchars($author_info['wikipedia_url']); ?>" 
                           target="_blank" rel="noopener noreferrer" 
                           class="text-sm text-blue-600 hover:text-blue-800 flex items-center gap-1">
                            <i class="fab fa-wikipedia-w"></i> Wikipedia
                        </a>
                        <?php endif; ?>
                        
                        <?php if ($login_flag): ?>
                        <a href="/add_book.php?search_word=<?php echo urlencode($author_name); ?>&search_type=author" 
                           class="text-sm text-purple-600 hover:text-purple-800 flex items-center gap-1">
                            <i class="fas fa-search"></i> この作家の本を検索
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- 統計情報 -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-blue-600 text-sm mb-1">登録作品数</p>
                        <p class="text-3xl font-bold text-blue-900">
                            <?php echo number_format($stats['total_books'] ?? 0); ?>
                        </p>
                    </div>
                    <i class="fas fa-book text-blue-300 text-4xl"></i>
                </div>
            </div>
            
            <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-green-600 text-sm mb-1">読者数</p>
                        <p class="text-3xl font-bold text-green-900">
                            <?php echo number_format($stats['total_readers'] ?? 0); ?>
                        </p>
                    </div>
                    <i class="fas fa-users text-green-300 text-4xl"></i>
                </div>
            </div>
        </div>
        
        <!-- 人気の本 -->
        <?php if (!empty($popular_books)): ?>
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">
                <i class="fas fa-fire text-orange-500 mr-2"></i>人気の本
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                <?php foreach ($popular_books as $book): ?>
                <div class="text-center group">
                    <?php if ($login_flag): ?>
                    <a href="/add_book.php?keyword=<?php echo urlencode($book['title']); ?>" 
                       class="block transition-transform hover:scale-105"
                       title="「<?php echo htmlspecialchars($book['title']); ?>」を検索">
                    <?php endif; ?>
                        <div class="bg-gray-50 rounded-lg p-2 mb-2 <?php echo $login_flag ? 'group-hover:bg-gray-100' : ''; ?> transition-colors">
                            <?php if (!empty($book['image_url'])): ?>
                            <img src="<?php echo htmlspecialchars($book['image_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($book['title']); ?>"
                                 class="w-full h-32 object-contain">
                            <?php else: ?>
                            <div class="w-full h-32 bg-gray-200 rounded flex items-center justify-center">
                                <i class="fas fa-book text-gray-400 text-3xl"></i>
                            </div>
                            <?php endif; ?>
                        </div>
                        <p class="text-sm font-medium text-gray-900 line-clamp-2 <?php echo $login_flag ? 'group-hover:text-readnest-primary' : ''; ?> transition-colors">
                            <?php echo htmlspecialchars($book['title']); ?>
                        </p>
                        <p class="text-xs text-gray-500 mt-1">
                            <?php echo number_format($book['reader_count']); ?>人が読書中
                        </p>
                        <?php if ($login_flag): ?>
                        <div class="mt-2 opacity-0 group-hover:opacity-100 transition-opacity">
                            <span class="inline-flex items-center text-xs text-readnest-primary">
                                <i class="fas fa-search mr-1"></i>検索
                            </span>
                        </div>
                        <?php endif; ?>
                    <?php if ($login_flag): ?>
                    </a>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php if ($login_flag): ?>
            <p class="text-xs text-gray-500 mt-4 text-center">
                <i class="fas fa-info-circle mr-1"></i>本をクリックすると検索できます
            </p>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- CTAセクション -->
        <?php if (!$login_flag): ?>
        <div class="mt-12 bg-gradient-to-r from-purple-600 to-pink-600 rounded-lg p-8 text-center text-white">
            <h2 class="text-2xl font-bold mb-4">ReadNestで読書を記録しよう</h2>
            <p class="mb-6">お気に入りの作家の本を探して、読書記録を始めませんか？</p>
            <div class="flex justify-center gap-4">
                <a href="/register.php" 
                   class="bg-white text-purple-600 px-6 py-3 rounded-lg font-semibold hover:bg-gray-100 transition">
                    無料で登録
                </a>
                <a href="/login.php" 
                   class="bg-purple-700 text-white px-6 py-3 rounded-lg font-semibold hover:bg-purple-800 transition">
                    ログイン
                </a>
            </div>
        </div>
        <?php else: ?>
        <div class="mt-8 text-center">
            <a href="/add_book.php?search_word=<?php echo urlencode($author_name); ?>&search_type=author" 
               class="inline-block bg-gradient-to-r from-purple-600 to-pink-600 text-white px-8 py-3 rounded-lg font-semibold hover:from-purple-700 hover:to-pink-700 transition">
                <i class="fas fa-plus mr-2"></i>この作家の本を本棚に追加
            </a>
        </div>
        <?php endif; ?>
        
    </div>
</section>

<?php
$d_content = ob_get_clean();

// ベーステンプレートを使用
include(__DIR__ . '/t_base.php');
?>