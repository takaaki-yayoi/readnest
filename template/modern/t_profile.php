<?php
if(!defined('CONFIG')) {
  error_log('direct access detected. file:' . __FILE__ . ' line:' . __LINE__ . ' ip:' . $_SERVER['REMOTE_ADDR'] . ' agent:' . $_SERVER['HTTP_USER_AGENT']);
  die('reference for this file is not allowed.');
}

// 日付ヘルパー関数を読み込み
require_once('library/date_helpers.php');
require_once('library/author_corrections.php');

// コンテンツ部分を生成
ob_start();
?>

<div class="bg-gray-50 dark:bg-gray-900 min-h-screen py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <?php if (!$profile_accessible): ?>
        <!-- 非公開プロフィール -->
        <div class="max-w-2xl mx-auto">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border dark:border-gray-700 p-8 text-center">
                <div class="mb-6">
                    <i class="fas fa-lock text-6xl text-gray-300 dark:text-gray-600"></i>
                </div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-4">プロフィールは非公開です</h1>
                <p class="text-gray-600 dark:text-gray-400 mb-6">このユーザーのプロフィールは非公開に設定されています。</p>
                <a href="/" class="btn bg-readnest-primary text-white px-6 py-2">
                    <i class="fas fa-home mr-2"></i>ホームに戻る
                </a>
            </div>
        </div>
        
        <?php else: ?>
        <!-- プロフィールヘッダー - レスポンシブ対応 -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border dark:border-gray-600 mb-6 sm:mb-8">
            <div class="p-4 sm:p-6 lg:p-8">
                <div class="flex flex-col sm:flex-row items-center sm:items-start gap-4 sm:gap-6 md:gap-8">
                    <!-- プロフィール写真 -->
                    <div class="flex-shrink-0">
                        <img src="<?php echo html($profile_photo_url); ?>" 
                             alt="<?php echo html($target_nickname); ?>" 
                             class="w-24 h-24 sm:w-28 sm:h-28 md:w-32 md:h-32 rounded-full border-4 border-readnest-primary shadow-lg">
                    </div>
                    
                    <!-- プロフィール情報 -->
                    <div class="flex-1 text-center sm:text-left">
                        <h1 class="text-2xl sm:text-2xl md:text-3xl font-bold text-gray-900 dark:text-gray-100 mb-2">
                            <?php echo html($target_nickname); ?>さん
                        </h1>
                        
                        <?php if ($user_level_info): ?>
                        <div class="mb-3">
                            <?php echo getLevelBadgeDetailHtml($user_level_info, 'md'); ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($user_info['introduction'])): ?>
                        <div class="text-sm sm:text-base text-gray-600 dark:text-gray-400 mb-3 sm:mb-4 leading-relaxed">
                            <?php echo nl2br(html($user_info['introduction'])); ?>
                        </div>
                        <?php endif; ?>
                        
                        <!-- アクションボタン -->
                        <div class="flex flex-wrap gap-2 sm:gap-3 justify-center sm:justify-start">
                            <a href="/bookshelf.php?user_id=<?php echo html($target_user_id); ?>" 
                               class="btn bg-readnest-primary text-white px-3 sm:px-4 py-1.5 sm:py-2 text-sm sm:text-base">
                                <i class="fas fa-book-open mr-1.5 sm:mr-2 text-sm"></i>本棚を見る
                            </a>
                            
                            <?php if ($is_own_profile): ?>
                            <a href="/my_reviews.php" 
                               class="btn bg-indigo-600 text-white px-3 sm:px-4 py-1.5 sm:py-2 text-sm sm:text-base">
                                <i class="fas fa-pen-to-square mr-1.5 sm:mr-2 text-sm"></i>マイレビュー
                            </a>
                            
                            <a href="/account.php" 
                               class="btn bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-3 sm:px-4 py-1.5 sm:py-2 text-sm sm:text-base">
                                <i class="fas fa-edit mr-1.5 sm:mr-2 text-sm"></i>プロフィール編集
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 読書統計 - レスポンシブ対応 -->
        <?php if ($reading_stats): ?>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-2 sm:gap-3 md:gap-4 mb-6 sm:mb-8">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border dark:border-gray-600 p-3 sm:p-4 md:p-6 text-center">
                <div class="text-xl sm:text-2xl md:text-3xl font-bold text-readnest-primary"><?php echo number_format($reading_stats['total_books']); ?></div>
                <div class="text-xs sm:text-sm md:text-base text-gray-600 dark:text-gray-400 mt-0.5 sm:mt-1">読了した本</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border dark:border-gray-600 p-3 sm:p-4 md:p-6 text-center">
                <div class="text-xl sm:text-2xl md:text-3xl font-bold text-readnest-accent"><?php echo number_format($reading_stats['this_year_books']); ?></div>
                <div class="text-xs sm:text-sm md:text-base text-gray-600 dark:text-gray-400 mt-0.5 sm:mt-1">今年読んだ本</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border dark:border-gray-600 p-3 sm:p-4 md:p-6 text-center">
                <div class="text-xl sm:text-2xl md:text-3xl font-bold text-blue-600"><?php echo number_format($reading_stats['reading_now']); ?></div>
                <div class="text-xs sm:text-sm md:text-base text-gray-600 dark:text-gray-400 mt-0.5 sm:mt-1">読書中の本</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border dark:border-gray-600 p-3 sm:p-4 md:p-6 text-center">
                <div class="text-xl sm:text-2xl md:text-3xl font-bold text-green-600"><?php echo number_format($reading_stats['total_pages']); ?></div>
                <div class="text-xs sm:text-sm md:text-base text-gray-600 dark:text-gray-400 mt-0.5 sm:mt-1">総読書ページ</div>
            </div>
            <?php if ($is_own_profile): ?>
            <a href="/my_reviews.php" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border dark:border-gray-600 p-3 sm:p-4 md:p-6 text-center hover:shadow-md transition-shadow group cursor-pointer block">
                <div class="text-xl sm:text-2xl md:text-3xl font-bold text-indigo-600 group-hover:scale-105 transition-transform"><?php echo number_format($reading_stats['total_reviews'] ?? 0); ?></div>
                <div class="text-xs sm:text-sm md:text-base text-gray-600 dark:text-gray-400 mt-0.5 sm:mt-1 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">レビュー数</div>
            </a>
            <?php else: ?>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border dark:border-gray-600 p-3 sm:p-4 md:p-6 text-center">
                <div class="text-xl sm:text-2xl md:text-3xl font-bold text-indigo-600"><?php echo number_format($reading_stats['total_reviews'] ?? 0); ?></div>
                <div class="text-xs sm:text-sm md:text-base text-gray-600 dark:text-gray-400 mt-0.5 sm:mt-1">レビュー数</div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- 最近の読書活動と統計情報のレイアウト - レスポンシブ対応 -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6 lg:gap-8">
            <!-- 左側: 最近の読書活動 -->
            <div class="lg:col-span-1">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border dark:border-gray-600 p-4 sm:p-6">
                    <h2 class="text-lg sm:text-xl font-semibold mb-3 sm:mb-4 text-gray-900 dark:text-gray-100">最近の読書活動</h2>
                    
                    <?php if (!empty($recent_books)): ?>
                        <div class="space-y-4">
                            <?php foreach (array_slice($recent_books, 0, 6) as $book): ?>
                            <div class="flex items-center space-x-2 sm:space-x-3 p-2 sm:p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <img src="<?php echo html(isset($book['image_url']) ? $book['image_url'] : '/img/noimage.jpg'); ?>" 
                                     alt="<?php echo html($book['name']); ?>" 
                                     class="w-10 h-14 sm:w-12 sm:h-16 object-cover rounded shadow-sm flex-shrink-0">
                                <div class="flex-1 min-w-0">
                                    <h3 class="font-medium text-sm sm:text-base text-gray-900 dark:text-gray-100 line-clamp-1">
                                        <a href="/book/<?php echo html($book['book_id']); ?>" 
                                           class="hover:text-readnest-primary dark:hover:text-readnest-accent">
                                            <?php echo html($book['name']); ?>
                                        </a>
                                    </h3>
                                    <p class="text-xs sm:text-sm text-gray-600 dark:text-gray-400 line-clamp-1"><?php echo html($book['author']); ?></p>
                                    <div class="flex items-center space-x-2 mt-1">
                                        <?php
                                        $status_colors = [
                                            READING_NOW => 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300',
                                            READING_FINISH => 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300',
                                            READ_BEFORE => 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300'
                                        ];
                                        $status_texts = [
                                            READING_NOW => '読書中',
                                            READING_FINISH => '読了',
                                            READ_BEFORE => '既読'
                                        ];
                                        $status_class = isset($status_colors[$book['status']]) ? $status_colors[$book['status']] : 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300';
                                        $status_text = isset($status_texts[$book['status']]) ? $status_texts[$book['status']] : '不明';
                                        ?>
                                        <span class="inline-block px-2 py-1 text-xs font-medium rounded-full <?php echo $status_class; ?>">
                                            <?php echo $status_text; ?>
                                        </span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                            <?php echo formatDate($book['update_date'], 'Y/m/d'); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="mt-4 sm:mt-6 text-center">
                            <a href="/bookshelf.php?user_id=<?php echo html($target_user_id); ?>" 
                               class="btn bg-readnest-primary text-white px-4 sm:px-6 py-1.5 sm:py-2 text-sm sm:text-base">
                                <i class="fas fa-book-open mr-1.5 sm:mr-2 text-sm"></i>すべての本を見る
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8">
                            <i class="fas fa-book-open text-4xl text-gray-300 dark:text-gray-600 mb-4"></i>
                            <p class="text-gray-600 dark:text-gray-400">まだ読書記録がありません</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- お気に入りの本 -->
                <?php if (!empty($favorite_books)): ?>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border dark:border-gray-600 p-4 sm:p-6 mt-4 sm:mt-6">
                    <h2 class="text-lg sm:text-xl font-semibold mb-3 sm:mb-4 flex items-center text-gray-900 dark:text-gray-100">
                        <i class="fas fa-star text-yellow-500 mr-2"></i>
                        お気に入りの本
                    </h2>
                    
                    <div class="grid grid-cols-3 sm:grid-cols-4 gap-2 sm:gap-3">
                        <?php foreach (array_slice($favorite_books, 0, 8) as $book): ?>
                        <div class="group relative">
                            <a href="/book_detail.php?book_id=<?php echo html($book['book_id']); ?>" 
                               class="block">
                                <div class="relative aspect-[3/4] overflow-hidden rounded-lg shadow-sm">
                                    <img src="<?php echo html(!empty($book['image_url']) && $book['image_url'] !== 'NULL' ? $book['image_url'] : '/img/no-image-book.png'); ?>" 
                                         alt="<?php echo html($book['name']); ?>" 
                                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200">
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent opacity-0 group-hover:opacity-100 transition-opacity">
                                        <div class="absolute bottom-0 left-0 right-0 p-2 text-white">
                                            <p class="text-xs font-medium line-clamp-2"><?php echo html($book['name']); ?></p>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if (count($favorite_books) > 8): ?>
                    <div class="mt-4 text-center">
                        <a href="/favorites.php<?php echo $is_own_profile ? '' : '?user_id=' . html($target_user_id); ?>" 
                           class="text-sm text-readnest-primary hover:underline">
                            すべてのお気に入りを見る (<?php echo count($favorite_books); ?>冊)
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- 右側: 読書進捗グラフと読書傾向分析 - レスポンシブ対応 -->
            <div class="lg:col-span-2 space-y-4 sm:space-y-6 lg:space-y-8">
                <!-- 読書進捗グラフ -->
                <?php if (!empty($reading_progress)): ?>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border dark:border-gray-600 p-4 sm:p-6">
                    <h2 class="text-lg sm:text-xl font-semibold mb-3 sm:mb-4">月別読書進捗（過去12ヶ月）</h2>
                    <div class="h-48 sm:h-56 md:h-64">
                        <canvas id="readingProgressChart"></canvas>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- 読書傾向分析 -->
                <?php if (!empty($reading_analysis)): ?>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border dark:border-gray-600 p-4 sm:p-6" id="current-analysis-section">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-3 sm:mb-4 gap-2 sm:gap-0">
                        <h2 class="text-lg sm:text-xl font-semibold flex items-center">
                            <i class="fas fa-chart-line text-indigo-600 mr-1.5 sm:mr-2 text-base sm:text-lg"></i>
                            読書傾向分析
                        </h2>
                        <?php if ($is_own_profile): ?>
                        <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                            <button onclick="showAnalysisHistory()" 
                                    class="text-xs sm:text-sm text-indigo-600 hover:text-indigo-700"
                                    title="過去の分析">
                                <i class="fas fa-history mr-0.5 sm:mr-1 text-xs"></i>履歴
                            </button>
                            <label class="inline-flex items-center cursor-pointer" title="他のユーザーに公開する">
                                <input type="checkbox"
                                       id="analysis-public-toggle"
                                       <?php echo ($reading_analysis['is_public'] == 1) ? 'checked' : ''; ?>
                                       onchange="toggleAnalysisVisibility(<?php echo $reading_analysis['analysis_id']; ?>)"
                                       class="sr-only peer">
                                <div class="relative w-9 h-5 bg-gray-300 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-indigo-600"></div>
                                <span class="ms-2 text-xs sm:text-sm text-gray-600 dark:text-gray-400">
                                    <i class="fas fa-<?php echo ($reading_analysis['is_public'] == 1) ? 'globe' : 'lock'; ?> mr-1" id="analysis-public-icon"></i>
                                    <span id="analysis-public-text"><?php echo ($reading_analysis['is_public'] == 1) ? '公開中' : '非公開'; ?></span>
                                </span>
                            </label>
                            <button onclick="shareAnalysisToX(<?php echo $reading_analysis['analysis_id']; ?>)"
                                    class="text-xs sm:text-sm text-indigo-600 hover:text-indigo-700"
                                    title="Xでシェア">
                                <i class="fab fa-x-twitter mr-0.5 sm:mr-1 text-xs"></i><span class="hidden sm:inline">シェア</span>
                            </button>
                            <a href="/og-image/analysis/<?php echo $reading_analysis['analysis_id']; ?>.png"
                               download="readnest_reading_analysis_<?php echo date('Ymd', strtotime($reading_analysis['created_at'])); ?>.png"
                               class="text-xs sm:text-sm text-indigo-600 hover:text-indigo-700"
                               title="画像を保存">
                                <i class="fas fa-download mr-0.5 sm:mr-1 text-xs"></i><span class="hidden sm:inline">保存</span>
                            </a>
                            <a href="/reading_insights.php?mode=trend" class="text-xs sm:text-sm text-indigo-600 hover:text-indigo-700" title="再分析">
                                <i class="fas fa-sync-alt mr-0.5 sm:mr-1 text-xs"></i><span class="hidden sm:inline">再分析</span>
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="bg-indigo-50 dark:bg-indigo-900/20 rounded-lg p-3 sm:p-4 md:p-6" id="analysis-content">
                        <div class="prose prose-sm sm:prose-base max-w-none text-gray-800 dark:text-gray-200">
                            <?php 
                        // 分析内容の著者名を修正
                        $corrected_content = AuthorCorrections::correctInText($reading_analysis['analysis_content']);
                        
                        // Markdownを簡易的にHTMLに変換
                        $analysis_html = $corrected_content;
                        // 【】で囲まれたセクションタイトルを太字に
                        $analysis_html = preg_replace('/【(.+?)】/', '<strong class="text-xl font-bold text-indigo-900">【$1】</strong>', $analysis_html);
                        $analysis_html = preg_replace('/^### (.+)$/m', '<h3 class="text-xl font-semibold text-indigo-900 mt-5 mb-3">$1</h3>', $analysis_html);
                        $analysis_html = preg_replace('/^## (.+)$/m', '<h2 class="text-2xl font-bold text-indigo-900 mt-6 mb-4">$1</h2>', $analysis_html);
                        $analysis_html = preg_replace('/^- (.+)$/m', '<li class="ml-5 text-base">$1</li>', $analysis_html);
                        $analysis_html = preg_replace('/^\* (.+)$/m', '<li class="ml-5 text-base">$1</li>', $analysis_html);
                        $analysis_html = preg_replace('/\*\*(.+?)\*\*/', '<strong class="font-bold">$1</strong>', $analysis_html);
                        $analysis_html = nl2br($analysis_html);
                            echo $analysis_html;
                            ?>
                        </div>
                        <div class="text-right mt-4 text-xs text-gray-600" id="analysis-date">
                            分析日: <?php echo date('Y年n月j日', strtotime($reading_analysis['created_at'])); ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- 公開アセット管理セクション（自分のプロフィールのみ） -->
                <?php if ($is_own_profile && !empty($user_assets)): ?>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border dark:border-gray-600 p-4 sm:p-6">
                    <h2 class="text-lg sm:text-xl font-semibold mb-3 sm:mb-4 flex items-center text-gray-900 dark:text-gray-100">
                        <i class="fas fa-folder-open text-amber-600 mr-2"></i>
                        AI分析アセット管理
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        AIが生成した分析・振り返りの公開設定を管理できます。
                    </p>

                    <div class="space-y-3">
                        <?php foreach ($user_assets as $asset): ?>
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-650 transition-colors" data-asset-id="<?php echo $asset['analysis_id']; ?>">
                            <div class="flex items-center min-w-0 flex-1">
                                <div class="flex-shrink-0 w-10 h-10 flex items-center justify-center rounded-full bg-<?php echo $asset['color']; ?>-100 dark:bg-<?php echo $asset['color']; ?>-900/30 mr-3">
                                    <i class="fas <?php echo $asset['icon']; ?> text-<?php echo $asset['color']; ?>-600 dark:text-<?php echo $asset['color']; ?>-400"></i>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <a href="<?php echo html($asset['link']); ?>" class="font-medium text-sm sm:text-base text-gray-900 dark:text-gray-100 hover:text-readnest-primary dark:hover:text-readnest-accent line-clamp-1">
                                        <?php echo html($asset['title']); ?>
                                    </a>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        <?php echo date('Y/m/d H:i', strtotime($asset['created_at'])); ?>
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 sm:gap-3 ml-2 flex-shrink-0">
                                <!-- 公開状態表示 -->
                                <span class="asset-status-badge text-xs px-2 py-1 rounded-full <?php echo $asset['is_public'] ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-600 dark:bg-gray-600 dark:text-gray-300'; ?>">
                                    <i class="fas <?php echo $asset['is_public'] ? 'fa-globe' : 'fa-lock'; ?> mr-1"></i>
                                    <span class="hidden sm:inline"><?php echo $asset['is_public'] ? '公開' : '非公開'; ?></span>
                                </span>

                                <!-- 公開トグル -->
                                <label class="relative inline-flex items-center cursor-pointer" title="公開設定を切り替え">
                                    <input type="checkbox"
                                           class="sr-only peer asset-public-toggle"
                                           data-asset-id="<?php echo $asset['analysis_id']; ?>"
                                           <?php echo $asset['is_public'] ? 'checked' : ''; ?>
                                           onchange="toggleAssetVisibility(<?php echo $asset['analysis_id']; ?>, this)">
                                    <div class="w-9 h-5 bg-gray-300 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-green-300 dark:peer-focus:ring-green-800 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-green-600"></div>
                                </label>

                                <!-- リンクボタン -->
                                <a href="<?php echo html($asset['link']); ?>"
                                   class="text-gray-400 hover:text-readnest-primary dark:hover:text-readnest-accent"
                                   title="詳細を見る">
                                    <i class="fas fa-external-link-alt"></i>
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="mt-4 text-xs text-gray-500 dark:text-gray-400">
                        <i class="fas fa-info-circle mr-1"></i>
                        公開にすると、他のユーザーがあなたのプロフィールで閲覧できるようになります。
                    </div>
                </div>
                <?php endif; ?>

                <!-- 分析履歴モーダル -->
                <div id="analysis-history-modal" class="fixed inset-0 z-50 hidden overflow-y-auto" style="background-color: rgba(0, 0, 0, 0.5);">
                    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle max-w-full sm:max-w-2xl w-full mx-auto" style="max-width: 95vw;">
                            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-lg font-medium text-gray-900">
                                        <i class="fas fa-history text-indigo-600 mr-2"></i>
                                        読書傾向分析の履歴
                                    </h3>
                                    <button onclick="closeAnalysisHistory()" class="text-gray-400 hover:text-gray-500">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <div id="analysis-history-content" class="max-h-96 overflow-y-auto">
                                    <div class="text-center py-8">
                                        <i class="fas fa-spinner fa-spin text-3xl text-indigo-600"></i>
                                        <p class="mt-2 text-gray-600">読み込み中...</p>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                <button onclick="closeAnalysisHistory()" class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">
                                    閉じる
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php endif; ?>
    </div>
</div>

<!-- 読書進捗グラフのスクリプト -->
<?php if (!empty($reading_progress)): ?>
<?php
ob_start();
?>
<script>
// 読書傾向分析の公開設定を切り替え
async function toggleAnalysisVisibility(analysisId) {
    const checkbox = document.getElementById('analysis-public-toggle');
    const icon = document.getElementById('analysis-public-icon');
    const text = document.getElementById('analysis-public-text');
    const isPublic = checkbox.checked ? 1 : 0;

    // UIを即時更新
    updatePublicUI(isPublic);

    try {
        const response = await fetch('/ajax/update_analysis_visibility.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                analysis_id: analysisId,
                is_public: isPublic
            })
        });

        const data = await response.json();

        if (!data.success) {
            // エラーの場合は元に戻す
            checkbox.checked = !checkbox.checked;
            updatePublicUI(checkbox.checked ? 1 : 0);
            alert('設定の更新に失敗しました');
        }
    } catch (error) {
        console.error('Error:', error);
        checkbox.checked = !checkbox.checked;
        updatePublicUI(checkbox.checked ? 1 : 0);
        alert('通信エラーが発生しました');
    }
}

// 公開状態のUI更新
function updatePublicUI(isPublic) {
    const icon = document.getElementById('analysis-public-icon');
    const text = document.getElementById('analysis-public-text');
    if (icon && text) {
        if (isPublic) {
            icon.className = 'fas fa-globe mr-1';
            text.textContent = '公開中';
        } else {
            icon.className = 'fas fa-lock mr-1';
            text.textContent = '非公開';
        }
    }
}

// アセットの公開設定を切り替え（アセット管理セクション用）
async function toggleAssetVisibility(analysisId, checkbox) {
    const isPublic = checkbox.checked ? 1 : 0;
    const assetRow = checkbox.closest('[data-asset-id]');
    const statusBadge = assetRow ? assetRow.querySelector('.asset-status-badge') : null;

    try {
        const response = await fetch('/ajax/update_analysis_visibility.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                analysis_id: analysisId,
                is_public: isPublic
            })
        });

        const data = await response.json();

        if (data.success) {
            // UIを更新
            if (statusBadge) {
                if (isPublic) {
                    statusBadge.className = 'asset-status-badge text-xs px-2 py-1 rounded-full bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400';
                    statusBadge.innerHTML = '<i class="fas fa-globe mr-1"></i><span class="hidden sm:inline">公開</span>';
                } else {
                    statusBadge.className = 'asset-status-badge text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-600 dark:bg-gray-600 dark:text-gray-300';
                    statusBadge.innerHTML = '<i class="fas fa-lock mr-1"></i><span class="hidden sm:inline">非公開</span>';
                }
            }

            // 読書傾向分析セクションの公開トグルも同期（同じ分析の場合）
            const mainToggle = document.getElementById('analysis-public-toggle');
            if (mainToggle && mainToggle.closest('[data-analysis-id]')) {
                const mainAnalysisId = mainToggle.closest('[data-analysis-id]').dataset.analysisId;
                if (mainAnalysisId == analysisId) {
                    mainToggle.checked = isPublic === 1;
                    updatePublicUI(isPublic);
                }
            }
        } else {
            // エラーの場合は元に戻す
            checkbox.checked = !checkbox.checked;
            alert('設定の更新に失敗しました');
        }
    } catch (error) {
        console.error('Error:', error);
        checkbox.checked = !checkbox.checked;
        alert('通信エラーが発生しました');
    }
}

// 読書傾向分析をXに投稿
async function shareAnalysisToX(analysisId) {
    const button = event.target.closest('button');
    const originalHtml = button.innerHTML;

    // 公開設定を確認
    const publicToggle = document.getElementById('analysis-public-toggle');
    if (publicToggle && !publicToggle.checked) {
        if (!confirm('Xでシェアするには分析を公開する必要があります。\n公開設定をONにしてシェアしますか？')) {
            return;
        }
        // 公開設定をONにする
        publicToggle.checked = true;
        await toggleAnalysisVisibility(analysisId);
        // 少し待ってからシェア処理を続行
        await new Promise(resolve => setTimeout(resolve, 500));
    }

    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>準備中...';

    try {
        // OGP画像のURL
        const ogImageUrl = '/og-image/analysis/' + analysisId + '.png';

        // 画像を事前に生成（キャッシュ作成のため）
        button.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>画像生成中...';
        const img = new Image();
        await new Promise((resolve, reject) => {
            img.onload = resolve;
            img.onerror = reject;
            img.src = ogImageUrl + '?t=' + Date.now();
        });

        // ツイートテキストを作成
        const tweetText = '私の読書傾向分析を公開しました！\n\n#ReadNest #読書記録';
        const currentUserId = '<?php echo html($target_user_id); ?>';
        const profileUrl = window.location.origin + '/profile.php?user_id=' + currentUserId + '&share_analysis=' + analysisId;

        // X投稿画面を開く
        const xUrl = `https://twitter.com/intent/tweet?text=${encodeURIComponent(tweetText)}&url=${encodeURIComponent(profileUrl)}`;
        window.open(xUrl, '_blank');

    } catch (error) {
        console.error('Error:', error);
        alert('画像の生成に失敗しました。しばらく待ってから再度お試しください。');
    } finally {
        button.disabled = false;
        button.innerHTML = originalHtml;
    }
}

// 分析履歴を表示
async function showAnalysisHistory() {
    const modal = document.getElementById('analysis-history-modal');
    const content = document.getElementById('analysis-history-content');
    
    modal.classList.remove('hidden');
    
    try {
        const response = await fetch('/ajax/get_analysis_history.php?user_id=<?php echo html($target_user_id); ?>');
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.error || '履歴の取得に失敗しました');
        }
        
        if (data.history.length === 0) {
            content.innerHTML = `
                <div class="text-center py-8">
                    <i class="fas fa-inbox text-gray-400 text-4xl mb-3"></i>
                    <p class="text-gray-600">まだ分析履歴がありません</p>
                </div>
            `;
            return;
        }
        
        // 履歴リストを生成
        let html = '<div class="space-y-3">';
        
        data.history.forEach((item, index) => {
            const date = new Date(item.created_at);
            const dateStr = date.getFullYear() + '年' + (date.getMonth() + 1) + '月' + date.getDate() + '日';
            const isCurrent = <?php echo isset($reading_analysis['analysis_id']) ? $reading_analysis['analysis_id'] : '0'; ?> == item.analysis_id;
            
            html += `
                <div class="border rounded-lg p-4 hover:bg-gray-50 cursor-pointer transition-colors ${isCurrent ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200'}"
                     onclick="loadAnalysis(${item.analysis_id})">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center">
                            <span class="text-sm font-medium text-gray-900">${dateStr}</span>
                            ${isCurrent ? '<span class="ml-2 px-2 py-1 text-xs bg-indigo-100 text-indigo-700 rounded">現在表示中</span>' : ''}
                            ${item.is_public == 1 ? '<span class="ml-2 px-2 py-1 text-xs bg-green-100 text-green-700 rounded">公開</span>' : '<span class="ml-2 px-2 py-1 text-xs bg-gray-100 text-gray-700 rounded">非公開</span>'}
                        </div>
                        ${!isCurrent ? '<i class="fas fa-chevron-right text-gray-400"></i>' : ''}
                    </div>
                    <p class="text-sm text-gray-600 line-clamp-2">${item.preview}</p>
                </div>
            `;
        });
        
        html += '</div>';
        content.innerHTML = html;
        
    } catch (error) {
        console.error('Error:', error);
        content.innerHTML = `
            <div class="text-center py-8">
                <i class="fas fa-exclamation-triangle text-red-500 text-3xl mb-3"></i>
                <p class="text-red-600">${error.message}</p>
            </div>
        `;
    }
}

// 分析履歴を閉じる
function closeAnalysisHistory() {
    const modal = document.getElementById('analysis-history-modal');
    modal.classList.add('hidden');
}

// 特定の分析を読み込む
async function loadAnalysis(analysisId) {
    try {
        const response = await fetch(`/ajax/get_analysis_history.php?user_id=<?php echo html($target_user_id); ?>&analysis_id=${analysisId}`);
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.error || '分析の取得に失敗しました');
        }
        
        const analysis = data.analysis;
        
        // 分析セクションの存在確認
        let analysisSection = document.getElementById('current-analysis-section');
        
        if (!analysisSection) {
            // 分析セクションが存在しない場合は作成
            const container = document.querySelector('.lg\\:col-span-2');
            if (!container) {
                console.error('Container not found');
                closeAnalysisHistory();
                return;
            }
            
            // 新しい分析セクションを作成
            const newSection = document.createElement('div');
            newSection.className = 'bg-white rounded-lg shadow-sm border p-6';
            newSection.id = 'current-analysis-section';
            newSection.innerHTML = `
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-semibold flex items-center">
                        <i class="fas fa-chart-line text-indigo-600 mr-2"></i>
                        読書傾向分析
                    </h2>
                    <?php if ($is_own_profile): ?>
                    <div class="flex items-center gap-3">
                        <button onclick="showAnalysisHistory()" 
                                class="text-sm text-indigo-600 hover:text-indigo-700"
                                title="過去の分析">
                            <i class="fas fa-history mr-1"></i>履歴
                        </button>
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" 
                                   id="analysis-public-toggle" 
                                   class="mr-2">
                            <span class="text-sm text-gray-600">公開</span>
                        </label>
                        <button onclick="shareAnalysisToX(0)" 
                                class="text-sm text-indigo-600 hover:text-indigo-700"
                                title="Xに投稿">
                            <i class="fab fa-x-twitter mr-1"></i>Xに投稿
                        </button>
                        <a href="/reading_insights.php?mode=trend" class="text-sm text-indigo-600 hover:text-indigo-700">
                            <i class="fas fa-sync-alt mr-1"></i>再分析
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="bg-indigo-50 rounded-lg p-6" id="analysis-content">
                    <div class="prose prose-base max-w-none text-gray-800"></div>
                    <div class="text-right mt-4 text-xs text-gray-600" id="analysis-date"></div>
                </div>
            `;
            container.appendChild(newSection);
            analysisSection = newSection;
        }
        
        // 分析内容を更新
        const contentDiv = document.getElementById('analysis-content');
        const dateDiv = document.getElementById('analysis-date');
        
        if (!contentDiv) {
            console.error('Analysis content div not found');
            closeAnalysisHistory();
            return;
        }
        
        // Markdown変換（PHPと同じ処理）
        let html = analysis.analysis_content;
        html = html.replace(/【(.+?)】/g, '<strong class="text-xl font-bold text-indigo-900">【$1】</strong>');
        html = html.replace(/^### (.+)$/gm, '<h3 class="text-xl font-semibold text-indigo-900 mt-5 mb-3">$1</h3>');
        html = html.replace(/^## (.+)$/gm, '<h2 class="text-2xl font-bold text-indigo-900 mt-6 mb-4">$1</h2>');
        html = html.replace(/^- (.+)$/gm, '<li class="ml-5 text-base">$1</li>');
        html = html.replace(/^\* (.+)$/gm, '<li class="ml-5 text-base">$1</li>');
        html = html.replace(/\*\*(.+?)\*\*/g, '<strong class="font-bold">$1</strong>');
        html = html.replace(/\n/g, '<br />');
        
        // コンテンツを更新
        const proseDiv = contentDiv.querySelector('.prose');
        if (proseDiv) {
            proseDiv.innerHTML = html;
        } else {
            contentDiv.innerHTML = `<div class="prose prose-base max-w-none text-gray-800">${html}</div>`;
        }
        
        // 日付を更新
        if (dateDiv) {
            const date = new Date(analysis.created_at);
            dateDiv.textContent = `分析日: ${date.getFullYear()}年${date.getMonth() + 1}月${date.getDate()}日`;
        }
        
        // 公開設定を更新
        const publicToggle = document.getElementById('analysis-public-toggle');
        if (publicToggle) {
            publicToggle.checked = analysis.is_public == 1;
            publicToggle.onchange = function() {
                toggleAnalysisVisibility(analysis.analysis_id);
            };
        }
        
        // Xに投稿ボタンを更新
        const xButton = document.querySelector('button[onclick^="shareAnalysisToX"]');
        if (xButton) {
            xButton.onclick = function() {
                shareAnalysisToX(analysis.analysis_id);
            };
        }
        
        // モーダルを閉じる
        closeAnalysisHistory();
        
        // スクロール
        if (analysisSection) {
            analysisSection.scrollIntoView({ behavior: 'smooth' });
        }
        
    } catch (error) {
        console.error('Error:', error);
        alert('エラーが発生しました: ' + error.message);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('readingProgressChart');
    if (ctx) {
        // データの準備
        const progressData = <?php echo json_encode($reading_progress); ?>;
        
        // 過去12ヶ月の月ラベルを生成
        const months = [];
        const counts = [];
        
        for (let i = 11; i >= 0; i--) {
            const date = new Date();
            date.setMonth(date.getMonth() - i);
            const monthKey = date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0');
            const monthLabel = date.getFullYear() + '年' + (date.getMonth() + 1) + '月';
            
            months.push(monthLabel);
            
            // データから該当月の読書数を取得
            const monthData = progressData.find(item => item.month === monthKey);
            counts.push(monthData ? parseInt(monthData.count) : 0);
        }
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: months,
                datasets: [{
                    label: '読了数',
                    data: counts,
                    borderColor: '#1a4d3e',
                    backgroundColor: 'rgba(26, 77, 62, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#1a4d3e',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }
});
</script>
<?php
$d_additional_scripts = ob_get_clean();
?>
<?php endif; ?>

<?php
// アセット管理用のスクリプト（reading_progressがない場合でも必要）
if ($is_own_profile && !empty($user_assets) && empty($reading_progress)):
ob_start();
?>
<script>
// アセットの公開設定を切り替え（アセット管理セクション用）
async function toggleAssetVisibility(analysisId, checkbox) {
    const isPublic = checkbox.checked ? 1 : 0;
    const assetRow = checkbox.closest('[data-asset-id]');
    const statusBadge = assetRow ? assetRow.querySelector('.asset-status-badge') : null;

    try {
        const response = await fetch('/ajax/update_analysis_visibility.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                analysis_id: analysisId,
                is_public: isPublic
            })
        });

        const data = await response.json();

        if (data.success) {
            // UIを更新
            if (statusBadge) {
                if (isPublic) {
                    statusBadge.className = 'asset-status-badge text-xs px-2 py-1 rounded-full bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400';
                    statusBadge.innerHTML = '<i class="fas fa-globe mr-1"></i><span class="hidden sm:inline">公開</span>';
                } else {
                    statusBadge.className = 'asset-status-badge text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-600 dark:bg-gray-600 dark:text-gray-300';
                    statusBadge.innerHTML = '<i class="fas fa-lock mr-1"></i><span class="hidden sm:inline">非公開</span>';
                }
            }
        } else {
            // エラーの場合は元に戻す
            checkbox.checked = !checkbox.checked;
            alert('設定の更新に失敗しました');
        }
    } catch (error) {
        console.error('Error:', error);
        checkbox.checked = !checkbox.checked;
        alert('通信エラーが発生しました');
    }
}
</script>
<?php
$d_additional_scripts = ob_get_clean();
endif;
?>

<?php
$d_content = ob_get_clean();

// ベーステンプレートを使用
include(__DIR__ . '/t_base.php');
?>