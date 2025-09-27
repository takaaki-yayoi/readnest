<?php
/**
 * 本棚ページのタブ化された統合セクション
 * 読書統計、AI読書アドバイザー、作家クラウド、タグクラウドを1つのタブUIに統合
 */
?>

<!-- 統合タブセクション -->
<?php if ($is_own_bookshelf): ?>
<section class="py-4 sm:py-8" x-data="{ 
    sectionOpen: localStorage.getItem('bookshelfTabsOpen') !== 'false',
    activeTab: localStorage.getItem('bookshelfActiveTab') || 'stats',
    toggleSection() {
        this.sectionOpen = !this.sectionOpen;
        localStorage.setItem('bookshelfTabsOpen', this.sectionOpen);
    },
    setActiveTab(tab) {
        this.activeTab = tab;
        localStorage.setItem('bookshelfActiveTab', tab);
    },
    tagMode: localStorage.getItem('bookshelfTagMode') || 'popular',
    setTagMode(mode) {
        this.tagMode = mode;
        localStorage.setItem('bookshelfTagMode', mode);
    }
}">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <!-- セクションヘッダー（折りたたみボタン付き） -->
            <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 flex items-center justify-between cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors"
                 @click="toggleSection()">
                <div class="flex items-center space-x-3">
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-200">
                        <i class="fas fa-layer-group text-gray-500 mr-2"></i>本棚ダッシュボード
                    </h2>
                    <span class="text-sm text-gray-500 dark:text-gray-400" x-show="!sectionOpen" x-cloak>
                        （<span x-text="activeTab === 'stats' ? '読書統計' : activeTab === 'ai' ? 'AIアドバイザー' : activeTab === 'authors' ? '作家クラウド' : 'タグクラウド'"></span>）
                    </span>
                </div>
                <button class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 transition-colors">
                    <i class="fas" :class="sectionOpen ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                </button>
            </div>
            
            <!-- タブコンテンツエリア -->
            <div x-show="sectionOpen" x-collapse x-cloak>
            <!-- タブヘッダー -->
            <div class="border-b border-gray-200 dark:border-gray-700">
                <nav class="flex -mb-px">
                    <!-- 読書統計タブ -->
                    <button @click="setActiveTab('stats')"
                            :class="activeTab === 'stats' ? 'border-readnest-primary text-readnest-primary' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600'"
                            class="w-1/4 py-3 px-1 text-center border-b-2 font-medium text-sm transition-colors">
                        <i class="fas fa-chart-bar mr-2"></i>
                        <span class="hidden sm:inline">読書統計</span>
                        <span class="sm:hidden">統計</span>
                    </button>
                    
                    <!-- AI読書アドバイザータブ -->
                    <button @click="setActiveTab('ai')"
                            :class="activeTab === 'ai' ? 'border-readnest-primary text-readnest-primary' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600'"
                            class="w-1/4 py-3 px-1 text-center border-b-2 font-medium text-sm transition-colors">
                        <i class="fas fa-robot mr-2"></i>
                        <span class="hidden sm:inline">AIアドバイザー</span>
                        <span class="sm:hidden">AI</span>
                    </button>
                    
                    <!-- 作家クラウドタブ -->
                    <button @click="setActiveTab('authors')"
                            :class="activeTab === 'authors' ? 'border-readnest-primary text-readnest-primary' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600'"
                            class="w-1/4 py-3 px-1 text-center border-b-2 font-medium text-sm transition-colors">
                        <i class="fas fa-feather-alt mr-2"></i>
                        <span class="hidden sm:inline">作家クラウド</span>
                        <span class="sm:hidden">作家</span>
                    </button>
                    
                    <!-- タグクラウドタブ -->
                    <button @click="setActiveTab('tags')"
                            :class="activeTab === 'tags' ? 'border-readnest-primary text-readnest-primary' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600'"
                            class="w-1/4 py-3 px-1 text-center border-b-2 font-medium text-sm transition-colors">
                        <i class="fas fa-tags mr-2"></i>
                        <span class="hidden sm:inline">タグクラウド</span>
                        <span class="sm:hidden">タグ</span>
                    </button>
                </nav>
            </div>
            
            <!-- タブコンテンツ -->
            <div class="p-4 sm:p-6" style="min-height: 180px;">
                <!-- 読書統計コンテンツ -->
                <div x-show="activeTab === 'stats'" x-cloak style="min-height: 150px;">
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 sm:gap-6 text-center">
                        <div>
                            <div class="text-xl sm:text-2xl md:text-3xl font-bold text-readnest-primary">
                                <?php echo number_format(isset($bookshelf_stats[BUY_SOMEDAY]) ? $bookshelf_stats[BUY_SOMEDAY] : 0); ?>
                            </div>
                            <div class="text-xs sm:text-sm text-gray-600 dark:text-gray-400 mt-1">いつか買う</div>
                        </div>
                        <div>
                            <div class="text-xl sm:text-2xl md:text-3xl font-bold text-blue-600">
                                <?php echo number_format(isset($bookshelf_stats[NOT_STARTED]) ? $bookshelf_stats[NOT_STARTED] : 0); ?>
                            </div>
                            <div class="text-xs sm:text-sm text-gray-600 dark:text-gray-400 mt-1">未読</div>
                        </div>
                        <div>
                            <div class="text-xl sm:text-2xl md:text-3xl font-bold text-yellow-600">
                                <?php echo number_format(isset($bookshelf_stats[READING_NOW]) ? $bookshelf_stats[READING_NOW] : 0); ?>
                            </div>
                            <div class="text-xs sm:text-sm text-gray-600 dark:text-gray-400 mt-1">読書中</div>
                        </div>
                        <div>
                            <div class="text-xl sm:text-2xl md:text-3xl font-bold text-green-600">
                                <?php 
                                $finished_count = (isset($bookshelf_stats[READING_FINISH]) ? $bookshelf_stats[READING_FINISH] : 0) + 
                                                 (isset($bookshelf_stats[READ_BEFORE]) ? $bookshelf_stats[READ_BEFORE] : 0);
                                echo number_format($finished_count); 
                                ?>
                            </div>
                            <div class="text-xs sm:text-sm text-gray-600 dark:text-gray-400 mt-1">読了</div>
                        </div>
                        <div class="col-span-2 md:col-span-1">
                            <div class="text-xl sm:text-2xl md:text-3xl font-bold text-readnest-accent">
                                <?php echo number_format(isset($read_stats[1]) ? $read_stats[1] : 0); ?>
                            </div>
                            <div class="text-xs sm:text-sm text-gray-600 dark:text-gray-400 mt-1">総ページ数</div>
                        </div>
                    </div>
                    
                    <!-- 読書カレンダーと統計へのリンク -->
                    <div class="mt-6 flex justify-center gap-3">
                        <a href="/reading_calendar.php" 
                           class="inline-flex items-center px-4 py-2 bg-emerald-500 text-white rounded-lg hover:bg-emerald-600 transition-colors">
                            <i class="fas fa-calendar-check mr-2"></i>
                            読書カレンダー
                        </a>
                        <a href="/reading_stats.php" 
                           class="inline-flex items-center px-4 py-2 bg-indigo-500 text-white rounded-lg hover:bg-indigo-600 transition-colors">
                            <i class="fas fa-chart-line mr-2"></i>
                            詳細統計
                        </a>
                    </div>
                </div>
                
                <!-- AI読書アドバイザーコンテンツ -->
                <div x-show="activeTab === 'ai'" x-cloak style="min-height: 150px;">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- 読書推薦 -->
                        <div class="bg-purple-50 dark:bg-purple-900 rounded-lg p-4">
                            <h3 class="font-semibold text-purple-900 dark:text-purple-100 mb-2">
                                <i class="fas fa-book mr-2"></i>おすすめの本
                            </h3>
                            <p class="text-sm text-purple-700 dark:text-purple-300 mb-3">読書履歴に基づく推薦</p>
                            <a href="/recommendations.php" 
                               class="block w-full bg-purple-600 text-white px-4 py-2 rounded-md hover:bg-purple-700 text-sm font-medium text-center">
                                AIに推薦してもらう
                            </a>
                        </div>
                        
                        <!-- 読書傾向分析 -->
                        <div class="bg-indigo-50 dark:bg-indigo-900 rounded-lg p-4">
                            <h3 class="font-semibold text-indigo-900 dark:text-indigo-100 mb-2">
                                <i class="fas fa-chart-line mr-2"></i>読書傾向
                            </h3>
                            <p class="text-sm text-indigo-700 dark:text-indigo-300 mb-3">あなたの読書パターン</p>
                            <button onclick="analyzeReadingTrends()" 
                                    class="w-full bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 text-sm font-medium">
                                分析を見る
                            </button>
                        </div>
                        
                        <!-- 読書チャレンジ -->
                        <div class="bg-pink-50 dark:bg-pink-900 rounded-lg p-4">
                            <h3 class="font-semibold text-pink-900 dark:text-pink-100 mb-2">
                                <i class="fas fa-trophy mr-2"></i>今月の目標
                            </h3>
                            <p class="text-sm text-pink-700 dark:text-pink-300 mb-3">パーソナライズされた目標</p>
                            <button onclick="getReadingChallenge()" 
                                    class="w-full bg-pink-600 text-white px-4 py-2 rounded-md hover:bg-pink-700 text-sm font-medium">
                                チャレンジを見る
                            </button>
                        </div>
                    </div>
                    
                    <!-- 結果表示エリア -->
                    <div id="ai-recommendation-result" class="mt-6 hidden">
                        <div class="border-t pt-6">
                            <div id="ai-loading" class="hidden text-center py-8">
                                <svg class="animate-spin h-8 w-8 mx-auto text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <p class="mt-2 text-gray-600 dark:text-gray-400">AIが分析中...</p>
                            </div>
                            
                            <div id="ai-error" class="hidden bg-red-50 border-l-4 border-red-400 p-4">
                                <p class="text-red-700"></p>
                            </div>
                            
                            <div id="ai-content" class="prose max-w-none"></div>
                        </div>
                    </div>
                </div>
                
                <!-- 作家クラウドコンテンツ -->
                <div x-show="activeTab === 'authors'" x-cloak style="min-height: 150px;">
                    <!-- 説明文 -->
                    <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                        <p>あなたが読んだ本の作家を可視化しています。文字サイズは読んだ冊数、色は読書状況を表します。</p>
                    </div>
                    
                    <?php if (!empty($author_cloud_data)): ?>
                    <div class="text-center">
                        <?php foreach ($author_cloud_data as $author): ?>
                            <?php
                            $colorClass = $author['color_class'] ?? 'from-gray-500 to-gray-600';
                            $fontSize = $author['font_size'] ?? 14;
                            $isFavorite = $author['is_favorite'] ?? false;
                            ?>
                            <a href="/bookshelf.php?search_word=<?php echo urlencode($author['author']); ?>&search_type=author" 
                               class="inline-block px-2 py-1 m-1 rounded-lg transition-all duration-300 hover:scale-110 bg-gradient-to-r <?php echo $colorClass; ?> text-white <?php echo $isFavorite ? 'ring-2 ring-yellow-400' : ''; ?>"
                               style="font-size: <?php echo $fontSize; ?>px;"
                               title="<?php echo htmlspecialchars($author['author']); ?> (<?php echo $author['book_count']; ?>冊)">
                                <?php echo htmlspecialchars($author['author']); ?>
                                <?php echo $isFavorite ? '⭐' : ''; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- リンク -->
                    <div class="mt-4 flex justify-center gap-4">
                        <a href="/my_authors.php" class="inline-flex items-center px-4 py-2 text-sm text-blue-600 hover:text-blue-700 font-medium">
                            <i class="fas fa-th mr-2"></i>すべての作家を見る
                            <i class="fas fa-arrow-right ml-2"></i>
                        </a>
                    </div>
                    
                    <!-- 凡例 -->
                    <div class="mt-6 flex flex-wrap justify-center gap-4 text-xs">
                        <span class="flex items-center gap-1">
                            <span class="inline-block w-3 h-3 bg-gradient-to-r from-green-500 to-green-600 rounded"></span>
                            読了
                        </span>
                        <span class="flex items-center gap-1">
                            <span class="inline-block w-3 h-3 bg-gradient-to-r from-yellow-500 to-yellow-600 rounded"></span>
                            読書中
                        </span>
                        <span class="flex items-center gap-1">
                            <span class="inline-block w-3 h-3 bg-gradient-to-r from-blue-500 to-blue-600 rounded"></span>
                            未読
                        </span>
                        <span class="flex items-center gap-1">
                            ⭐ 高評価
                        </span>
                    </div>
                    <?php else: ?>
                    <p class="text-gray-500 dark:text-gray-400 text-center">まだ作家データがありません</p>
                    <?php endif; ?>
                </div>
                
                <!-- タグクラウドコンテンツ -->
                <div x-show="activeTab === 'tags'" x-cloak style="min-height: 150px;">
                    <?php if ($show_tag_cloud && !empty($tag_cloud_data_popular)): ?>
                    <!-- タグ切り替えボタン -->
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex gap-2">
                            <button @click="setTagMode('popular')"
                                    :class="tagMode === 'popular' ? 'bg-green-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300'"
                                    class="px-3 py-1 rounded-md text-sm font-medium transition-colors">
                                <i class="fas fa-fire mr-1"></i>人気
                            </button>
                            <button @click="setTagMode('recent')"
                                    :class="tagMode === 'recent' ? 'bg-blue-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300'"
                                    class="px-3 py-1 rounded-md text-sm font-medium transition-colors">
                                <i class="fas fa-clock mr-1"></i>最近
                            </button>
                        </div>
                    </div>
                    
                    <!-- 人気タグ表示 -->
                    <div x-show="tagMode === 'popular'" class="text-center">
                        <?php if (!empty($tag_cloud_data_popular)): ?>
                            <?php foreach ($tag_cloud_data_popular as $tag): ?>
                                <?php
                                $fontSize = isset($tag['font_size']) ? $tag['font_size'] : 14;
                                $colorClass = isset($tag['color_class']) ? $tag['color_class'] : 'text-gray-600';
                                ?>
                                <a href="/bookshelf.php?search_word=<?php echo urlencode($tag['tag']); ?>&search_type=tag" 
                                   class="inline-block px-3 py-1 m-1 rounded-full hover:opacity-80 transition-opacity <?php echo $colorClass; ?>"
                                   style="font-size: <?php echo $fontSize; ?>px;">
                                    <?php echo htmlspecialchars($tag['tag']); ?>
                                    <span class="text-xs ml-1">(<?php echo $tag['count']; ?>)</span>
                                </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-gray-500 dark:text-gray-400">人気タグデータがありません</p>
                        <?php endif; ?>
                    </div>
                    
                    <!-- 最近のタグ表示 -->
                    <div x-show="tagMode === 'recent'" x-cloak class="text-center">
                        <?php if (!empty($tag_cloud_data_recent)): ?>
                            <?php foreach ($tag_cloud_data_recent as $tag): ?>
                                <?php
                                $fontSize = isset($tag['font_size']) ? $tag['font_size'] : 14;
                                $colorClass = isset($tag['color_class']) ? $tag['color_class'] : 'text-gray-600';
                                ?>
                                <a href="/bookshelf.php?search_word=<?php echo urlencode($tag['tag']); ?>&search_type=tag" 
                                   class="inline-block px-3 py-1 m-1 rounded-full hover:opacity-80 transition-opacity <?php echo $colorClass; ?>"
                                   style="font-size: <?php echo $fontSize; ?>px;">
                                    <?php echo htmlspecialchars($tag['tag']); ?>
                                    <span class="text-xs ml-1">(<?php echo $tag['count']; ?>)</span>
                                </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-gray-500 dark:text-gray-400">最近のタグデータがありません</p>
                        <?php endif; ?>
                    </div>
                    
                    <!-- 凡例と統計情報 -->
                    <?php if (!empty($tag_cloud_stats)): ?>
                    <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <!-- 凡例（人気タグのみ） -->
                        <div x-show="tagMode === 'popular'" class="flex flex-wrap justify-center gap-3 mb-3 text-xs">
                            <span class="flex items-center gap-1">
                                <span class="inline-block w-3 h-3 bg-blue-500 rounded"></span>
                                10冊以上
                            </span>
                            <span class="flex items-center gap-1">
                                <span class="inline-block w-3 h-3 bg-green-500 rounded"></span>
                                5〜9冊
                            </span>
                            <span class="flex items-center gap-1">
                                <span class="inline-block w-3 h-3 bg-gray-300 rounded"></span>
                                4冊以下
                            </span>
                        </div>
                        <!-- 統計情報 -->
                        <div class="flex justify-center gap-4 text-sm text-gray-600 dark:text-gray-400">
                            <span><strong><?php echo $tag_cloud_stats['total_tags'] ?? 0; ?></strong> 個のタグ</span>
                            <span><strong><?php echo $tag_cloud_stats['total_books'] ?? 0; ?></strong> 冊の本</span>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php else: ?>
                    <p class="text-gray-500 dark:text-gray-400 text-center">タグクラウドのデータがありません</p>
                    <?php endif; ?>
                </div>
            </div><!-- タブコンテンツ -->
            </div><!-- x-show="sectionOpen" -->
        </div>
    </div>
</section>
<?php endif; ?>