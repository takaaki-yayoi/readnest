<?php
/**
 * 本のエンティティページテンプレート
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

<!-- デバッグ情報 -->
<?php if (isset($_GET['debug'])): ?>
<div class="bg-yellow-100 p-4 mb-4 text-sm">
    <h3 class="font-bold mb-2">Debug Info:</h3>
    <div class="space-y-2">
        <div>
            <strong>Book Info ASIN:</strong> <?php echo htmlspecialchars($book_info['asin'] ?? 'NULL'); ?><br>
            <strong>Book Info ISBN:</strong> <?php echo htmlspecialchars($book_info['isbn'] ?? 'NULL'); ?><br>
            <strong>Book Info image_url:</strong> <?php echo htmlspecialchars($book_info['image_url'] ?? 'NULL'); ?><br>
            <strong>Query ASIN ($book_asin):</strong> <?php echo htmlspecialchars($book_asin ?? 'NULL'); ?><br>
            <strong>Readers Found:</strong> <?php echo count($readers); ?><br>
            <strong>Tags Found:</strong> <?php echo count($popular_tags); ?><br>
            <strong>My Book:</strong> <?php echo $my_book ? 'Found (book_id: ' . $my_book['book_id'] . ')' : 'Not found'; ?>
        </div>
        <details>
            <summary class="cursor-pointer">Full Book Info</summary>
            <pre class="text-xs mt-2"><?php echo htmlspecialchars(print_r($book_info, true)); ?></pre>
        </details>
        <details>
            <summary class="cursor-pointer">Stats</summary>
            <pre class="text-xs mt-2"><?php echo htmlspecialchars(print_r($stats, true)); ?></pre>
        </details>
        <?php if (!empty($readers)): ?>
        <details>
            <summary class="cursor-pointer">First Reader Info</summary>
            <pre class="text-xs mt-2"><?php echo htmlspecialchars(print_r($readers[0], true)); ?></pre>
        </details>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- ヘッダーセクション -->
<section class="bg-gradient-to-r from-readnest-primary to-readnest-accent dark:from-gray-800 dark:to-gray-700 text-white py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row gap-6">
            <!-- 本の画像 -->
            <div class="flex-shrink-0">
                <?php 
                // 画像URLの優先順位: image_url > 読者リストの最初の画像
                $display_image = $book_info['image_url'] ?? '';
                if (empty($display_image) && !empty($readers)) {
                    foreach ($readers as $reader) {
                        if (!empty($reader['image_url'])) {
                            $display_image = $reader['image_url'];
                            break;
                        }
                    }
                }
                ?>
                <?php if (!empty($display_image)): ?>
                <img src="<?php echo htmlspecialchars($display_image); ?>" 
                     alt="<?php echo htmlspecialchars($book_info['title']); ?>"
                     class="w-32 h-44 object-cover rounded-lg shadow-lg"
                     onerror="this.onerror=null; this.src='/img/no-image-book.png';">
                <?php else: ?>
                <div class="w-32 h-44 bg-gray-300 rounded-lg flex items-center justify-center">
                    <i class="fas fa-book text-gray-500 text-3xl"></i>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- 本の情報 -->
            <div class="flex-1">
                <h1 class="text-3xl font-bold mb-2"><?php echo htmlspecialchars($book_info['title']); ?></h1>
                <?php if (!empty($book_info['author'])): ?>
                <p class="text-xl mb-4">
                    <i class="fas fa-user-edit mr-2 opacity-90"></i>
                    <a href="/author.php?name=<?php echo urlencode($book_info['author']); ?>" 
                       class="hover:underline hover:text-white/90 transition-colors"
                       title="<?php echo htmlspecialchars($book_info['author']); ?>の他の作品を見る">
                        <?php echo htmlspecialchars($book_info['author']); ?>
                    </a>
                </p>
                <?php endif; ?>
                
                <!-- 統計情報 -->
                <div class="flex flex-wrap gap-4 mb-4">
                    <?php if ($stats['avg_rating'] > 0): ?>
                    <div class="flex items-center">
                        <div class="text-yellow-300">
                            <?php for ($i = 0; $i < floor($stats['avg_rating']); $i++): ?>
                                <i class="fas fa-star"></i>
                            <?php endfor; ?>
                            <?php if ($stats['avg_rating'] - floor($stats['avg_rating']) >= 0.5): ?>
                                <i class="fas fa-star-half-alt"></i>
                            <?php endif; ?>
                        </div>
                        <span class="ml-2 font-semibold text-lg"><?php echo $stats['avg_rating']; ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="flex items-center">
                        <i class="fas fa-users mr-2"></i>
                        <span><?php echo $stats['total_readers']; ?>人が登録</span>
                    </div>
                    
                    <?php if ($stats['reviews_count'] > 0): ?>
                    <div class="flex items-center">
                        <i class="fas fa-comments mr-2"></i>
                        <span><?php echo $stats['reviews_count']; ?>件のレビュー</span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- アクションボタン -->
                <div class="mt-4 flex flex-wrap gap-2">
                    <?php if ($login_flag): ?>
                        <?php if ($my_book): ?>
                            <a href="/book/<?php echo $my_book['book_id']; ?>" 
                               class="inline-flex items-center px-4 py-2 bg-white text-readnest-primary rounded-lg hover:bg-gray-100 transition-colors">
                                <i class="fas fa-book-open mr-2"></i>
                                自分の読書記録を見る
                            </a>
                        <?php else: ?>
                            <a href="/add_book.php?asin=<?php echo urlencode($book_info['asin']); ?>" 
                               class="inline-flex items-center px-4 py-2 bg-white text-readnest-primary rounded-lg hover:bg-gray-100 transition-colors">
                                <i class="fas fa-plus mr-2"></i>
                                本棚に追加
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <!-- Amazonボタン（ログイン不要） -->
                    <?php 
                    // Amazon URLを生成
                    $amazon_url = '';
                    if (!empty($book_info['asin'])) {
                        $amazon_url = "https://www.amazon.co.jp/dp/" . urlencode($book_info['asin']);
                    } elseif (!empty($book_info['isbn'])) {
                        $amazon_url = "https://www.amazon.co.jp/dp/" . urlencode($book_info['isbn']);
                    } else {
                        // タイトルと著者で検索
                        $search_query = $book_info['title'];
                        if (!empty($book_info['author'])) {
                            $search_query .= ' ' . $book_info['author'];
                        }
                        $amazon_url = "https://www.amazon.co.jp/s?k=" . urlencode($search_query);
                    }
                    ?>
                    <a href="<?php echo htmlspecialchars($amazon_url); ?>" 
                       target="_blank"
                       rel="noopener noreferrer"
                       class="inline-flex items-center px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 transition-colors">
                        <i class="fab fa-amazon mr-2"></i>
                        Amazonで見る
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- メインコンテンツ -->
<section class="py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- 本の説明文（最優先表示） -->
        <?php if (!empty($book_info['description'])): ?>
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-2xl font-bold mb-4 text-gray-900">
                <i class="fas fa-book-open text-blue-500 mr-2"></i>この本について
            </h2>
            <div class="prose max-w-none text-gray-700 leading-relaxed">
                <?php echo nl2br(htmlspecialchars($book_info['description'])); ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- 評価とレビューセクション -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- 左カラム：レビューとタグ -->
            <div class="lg:col-span-2 space-y-6">
                
                <!-- 評価サマリー -->
                <?php if ($rating_count > 0): ?>
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-xl font-bold text-gray-900">
                            <i class="fas fa-star text-yellow-500 mr-2"></i>みんなの評価
                        </h2>
                        <div class="flex items-center">
                            <span class="text-3xl font-bold text-gray-900 mr-2"><?php echo $stats['avg_rating']; ?></span>
                            <div class="text-yellow-400">
                                <?php for ($i = 0; $i < floor($stats['avg_rating']); $i++): ?>
                                    <i class="fas fa-star"></i>
                                <?php endfor; ?>
                                <?php if ($stats['avg_rating'] - floor($stats['avg_rating']) >= 0.5): ?>
                                    <i class="fas fa-star-half-alt"></i>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 評価分布 -->
                    <div class="space-y-2">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                        <?php 
                        $count = $stats['rating_distribution'][$i];
                        $percentage = $rating_count > 0 ? ($count / $rating_count) * 100 : 0;
                        ?>
                        <div class="flex items-center gap-3">
                            <div class="flex items-center justify-end w-20">
                                <span class="text-sm text-gray-600 mr-1"><?php echo $i; ?></span>
                                <i class="fas fa-star text-yellow-400 text-sm"></i>
                            </div>
                            <div class="flex-1 bg-gray-200 rounded-full h-3 overflow-hidden">
                                <div class="bg-gradient-to-r from-yellow-400 to-yellow-500 h-full transition-all duration-500" 
                                     style="width: <?php echo $percentage; ?>%"></div>
                            </div>
                            <span class="text-sm text-gray-600 w-12 text-right"><?php echo $count; ?>件</span>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- レビュー一覧 -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-xl font-bold mb-4 text-gray-900">
                        <i class="fas fa-comments text-green-500 mr-2"></i>レビュー
                    </h2>
                    
                    <?php 
                    $reviews = array_filter($readers, function($reader) {
                        return !empty($reader['memo']) && ($reader['diary_policy'] == 1 || $reader['user_id'] == $GLOBALS['mine_user_id']);
                    });
                    ?>
                    
                    <?php if (!empty($reviews)): ?>
                    <div class="space-y-4">
                        <?php foreach (array_slice($reviews, 0, 10) as $reader): ?>
                        <div class="border-b dark:border-gray-700 pb-4 last:border-b-0">
                            <div class="flex items-start gap-3">
                                <!-- ユーザーアイコン -->
                                <a href="/profile.php?user_id=<?php echo $reader['user_id']; ?>" 
                                   class="flex-shrink-0">
                                    <img src="<?php echo !empty($reader['photo_url']) ? htmlspecialchars($reader['photo_url']) : '/img/no-image-user.png'; ?>" 
                                         alt="<?php echo htmlspecialchars($reader['nickname']); ?>"
                                         class="w-10 h-10 rounded-full object-cover">
                                </a>
                                
                                <!-- レビュー内容 -->
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-1">
                                        <a href="/profile.php?user_id=<?php echo $reader['user_id']; ?>" 
                                           class="font-semibold text-gray-900 hover:text-readnest-primary">
                                            <?php echo htmlspecialchars($reader['nickname']); ?>
                                        </a>
                                        
                                        <?php if ($reader['rating'] > 0): ?>
                                        <div class="text-yellow-500 text-sm">
                                            <?php for ($i = 0; $i < $reader['rating']; $i++): ?>
                                                <i class="fas fa-star"></i>
                                            <?php endfor; ?>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($reader['finished_date'])): ?>
                                        <span class="text-xs text-gray-500">
                                            <?php echo date('Y年n月', strtotime($reader['finished_date'])); ?>読了
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="text-gray-700 text-sm">
                                        <?php echo nl2br(htmlspecialchars($reader['memo'])); ?>
                                    </div>
                                    
                                    <!-- 個別の読書記録へのリンク -->
                                    <div class="mt-2">
                                        <a href="/book/<?php echo $reader['book_id']; ?>" 
                                           class="text-xs text-blue-600 hover:text-blue-700">
                                            この読書記録を詳しく見る →
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <p class="text-gray-500 text-center py-8">
                        まだレビューが投稿されていません
                    </p>
                    <?php endif; ?>
                </div>
                
                <!-- 読者リスト（コンパクト表示） -->
                <?php if (!empty($readers)): ?>
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-xl font-bold mb-4 text-gray-900">
                        <i class="fas fa-users text-blue-500 mr-2"></i>この本を読んでいる人（<?php echo count($readers); ?>人）
                    </h2>
                    
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                        <?php foreach (array_slice($readers, 0, 12) as $reader): ?>
                        <a href="/profile.php?user_id=<?php echo $reader['user_id']; ?>" 
                           class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-50 transition-colors">
                            <img src="<?php echo !empty($reader['photo_url']) ? htmlspecialchars($reader['photo_url']) : '/img/no-image-user.png'; ?>" 
                                 alt="<?php echo htmlspecialchars($reader['nickname']); ?>"
                                 class="w-8 h-8 rounded-full object-cover">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate">
                                    <?php echo htmlspecialchars($reader['nickname']); ?>
                                </p>
                                <?php if ($reader['status'] == READING_NOW): ?>
                                    <p class="text-xs text-blue-600">読書中</p>
                                <?php elseif ($reader['status'] == READING_FINISH || $reader['status'] == READ_BEFORE): ?>
                                    <p class="text-xs text-green-600">読了</p>
                                <?php endif; ?>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if (count($readers) > 12): ?>
                    <p class="text-sm text-gray-500 text-center mt-4">
                        他<?php echo count($readers) - 12; ?>人
                    </p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- 右カラム：サイドバー情報 -->
            <div class="space-y-6">
                <!-- 読書ステータス -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">
                        <i class="fas fa-chart-pie text-purple-500 mr-2"></i>読書ステータス
                    </h3>
                    <div class="space-y-3">
                        <?php if ($stats['reading_now'] > 0): ?>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">読書中</span>
                            <span class="font-semibold text-blue-600"><?php echo $stats['reading_now']; ?>人</span>
                        </div>
                        <?php endif; ?>
                        <?php if ($stats['finished'] > 0): ?>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">読了</span>
                            <span class="font-semibold text-green-600"><?php echo $stats['finished']; ?>人</span>
                        </div>
                        <?php endif; ?>
                        <?php if ($stats['want_to_read'] > 0): ?>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">読みたい</span>
                            <span class="font-semibold text-yellow-600"><?php echo $stats['want_to_read']; ?>人</span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- 人気のタグ -->
                <?php if (!empty($popular_tags)): ?>
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">
                        <i class="fas fa-tags text-indigo-500 mr-2"></i>人気のタグ
                    </h3>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach ($popular_tags as $tag): ?>
                        <a href="/tag/<?php echo urlencode($tag['tag_name']); ?>" 
                           class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-gray-100 text-gray-700 hover:bg-gray-200 transition-colors">
                            <i class="fas fa-tag mr-1 text-xs"></i>
                            <?php echo htmlspecialchars($tag['tag_name']); ?>
                            <span class="ml-1 text-xs text-gray-500">(<?php echo $tag['user_count']; ?>)</span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- 書籍情報 -->
                <?php if (!empty($book_info['publisher']) || !empty($book_info['published_date']) || !empty($book_info['page_count'])): ?>
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">
                        <i class="fas fa-info-circle text-gray-500 mr-2"></i>書籍情報
                    </h3>
                    <dl class="space-y-2">
                        <?php if (!empty($book_info['publisher'])): ?>
                        <div>
                            <dt class="text-xs text-gray-500">出版社</dt>
                            <dd class="text-sm text-gray-900"><?php echo htmlspecialchars($book_info['publisher']); ?></dd>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($book_info['published_date'])): ?>
                        <div>
                            <dt class="text-xs text-gray-500">発売日</dt>
                            <dd class="text-sm text-gray-900"><?php echo htmlspecialchars($book_info['published_date']); ?></dd>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($book_info['page_count'])): ?>
                        <div>
                            <dt class="text-xs text-gray-500">ページ数</dt>
                            <dd class="text-sm text-gray-900"><?php echo htmlspecialchars($book_info['page_count']); ?>ページ</dd>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($book_info['isbn'])): ?>
                        <div>
                            <dt class="text-xs text-gray-500">ISBN</dt>
                            <dd class="text-sm text-gray-900 font-mono"><?php echo htmlspecialchars($book_info['isbn']); ?></dd>
                        </div>
                        <?php endif; ?>
                    </dl>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- まだ誰も登録していない場合 -->
        <?php if (empty($readers)): ?>
        <div class="bg-white rounded-lg shadow p-12 text-center">
            <i class="fas fa-book-open text-gray-300 text-5xl mb-4"></i>
            <p class="text-gray-500 text-lg mb-4">まだ誰もこの本を登録していません</p>
            <?php if ($login_flag): ?>
            <a href="/add_book.php?asin=<?php echo urlencode($book_info['asin']); ?>" 
               class="inline-flex items-center px-6 py-3 bg-readnest-primary text-white rounded-lg hover:bg-readnest-accent transition-colors">
                <i class="fas fa-plus mr-2"></i>
                最初の読者になる
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- 関連書籍 -->
<?php if (!empty($similar_books)): ?>
<section class="max-w-7xl mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-bold mb-4 text-gray-900">
            <i class="fas fa-book text-indigo-500 mr-2"></i>
            <?php echo htmlspecialchars($book_info['author']); ?>の他の作品
        </h2>
        
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
            <?php foreach ($similar_books as $similar): ?>
            <div class="group">
                <a href="/book_entity.php?asin=<?php echo urlencode($similar['asin']); ?>" 
                   class="block hover:opacity-90 transition-opacity">
                    <div class="bg-gray-50 rounded-lg p-3 hover:shadow-md transition-shadow">
                        <?php if (!empty($similar['image_url'])): ?>
                        <img src="<?php echo htmlspecialchars($similar['image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($similar['title']); ?>"
                             class="w-full h-32 object-contain mb-2">
                        <?php else: ?>
                        <div class="w-full h-32 bg-gray-200 flex items-center justify-center mb-2">
                            <i class="fas fa-book text-gray-400 text-2xl"></i>
                        </div>
                        <?php endif; ?>
                        
                        <h3 class="text-xs font-medium text-gray-900 line-clamp-2 mb-1">
                            <?php echo htmlspecialchars($similar['title']); ?>
                        </h3>
                        
                        <?php if ($similar['reader_count'] > 0): ?>
                        <div class="flex items-center justify-between text-xs text-gray-500">
                            <span><?php echo $similar['reader_count']; ?>人</span>
                            <?php if ($similar['avg_rating'] > 0): ?>
                            <span class="flex items-center">
                                <i class="fas fa-star text-yellow-400 mr-1"></i>
                                <?php echo number_format($similar['avg_rating'], 1); ?>
                            </span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
        
        <?php if (!empty($book_info['author'])): ?>
        <div class="mt-4 text-center">
            <a href="/author.php?name=<?php echo urlencode($book_info['author']); ?>" 
               class="inline-flex items-center text-sm text-readnest-primary hover:underline">
                <i class="fas fa-arrow-right mr-1"></i>
                <?php echo htmlspecialchars($book_info['author']); ?>の作品をもっと見る
            </a>
        </div>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>

<?php
$d_content = ob_get_clean();

// ベーステンプレートを使用
include(__DIR__ . '/t_base.php');
?>