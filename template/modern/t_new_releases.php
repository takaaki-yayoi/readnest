<?php
if(!defined('CONFIG')) {
    error_log('direct access detected. file:' . __FILE__ . ' line:' . __LINE__);
    die('reference for this file is not allowed.');
}

require_once(dirname(dirname(__DIR__)) . '/library/form_helpers.php');
require_once(dirname(dirname(__DIR__)) . '/library/affiliate_helper.php');

ob_start();
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- ヘッダー -->
    <div class="mb-8">
        <div class="flex items-center gap-3 mb-2">
            <i class="fas fa-bell text-yellow-500 text-xl"></i>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">新刊情報</h1>
            <?php if ($unread_count > 0): ?>
            <span class="bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full"><?php echo $unread_count; ?></span>
            <?php endif; ?>
        </div>
        <p class="text-sm text-gray-500 dark:text-gray-400">お気に入り作家の新しい作品をお届けします</p>
    </div>

    <?php if (empty($fav_authors)): ?>
    <!-- お気に入り作家がいない場合 -->
    <div class="text-center py-16">
        <i class="fas fa-book-reader text-gray-300 dark:text-gray-600 text-5xl mb-4"></i>
        <p class="text-gray-500 dark:text-gray-400 mb-2">お気に入り作家がまだいません</p>
        <p class="text-sm text-gray-400 dark:text-gray-500 mb-6">本を2冊以上読んだ作家の新刊情報が届くようになります</p>
        <a href="/add_book.php" class="inline-flex items-center px-4 py-2 bg-readnest-primary text-white rounded-lg hover:bg-readnest-primary/90 transition-colors text-sm">
            <i class="fas fa-plus mr-2"></i>本を追加する
        </a>
    </div>
    <?php else: ?>

    <!-- フィルター -->
    <div class="flex items-center gap-3 mb-6">
        <a href="/new_releases.php?filter=all"
           class="px-3 py-1.5 rounded-full text-sm transition-colors <?php echo $filter === 'all' ? 'bg-readnest-primary text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'; ?>">
            すべて
        </a>
        <a href="/new_releases.php?filter=unread"
           class="px-3 py-1.5 rounded-full text-sm transition-colors <?php echo $filter === 'unread' ? 'bg-readnest-primary text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'; ?>">
            未読
            <?php if ($unread_count > 0): ?>
            <span class="ml-1 bg-red-500 text-white text-xs font-bold px-1.5 py-0.5 rounded-full"><?php echo $unread_count; ?></span>
            <?php endif; ?>
        </a>
    </div>

    <?php if (!empty($notifications)): ?>
    <!-- 通知一覧 -->
    <div class="space-y-3 mb-8">
        <?php foreach ($notifications as $notif): ?>
        <?php
            $data = $notif['parsed_data'];
            $author = $data['author'] ?? '';
            $title = $data['title'] ?? '';
            $image_url = $data['image_url'] ?? '';
            $published_date = $data['published_date'] ?? '';
            $link_url = $notif['link_url'] ?: '/add_book.php?keyword=' . urlencode($title);
            $created_at = $notif['created_at'];
            $is_unread = !$notif['is_read'];
            $author_info = $fav_authors[$author] ?? null;
            $amazon_url = getAmazonProductUrl(['title' => $title, 'author' => $author]);
        ?>
        <div class="group flex items-start gap-4 p-4 rounded-lg border transition-all
                  <?php echo $is_unread
                      ? 'border-yellow-200 dark:border-yellow-800 bg-yellow-50/50 dark:bg-yellow-900/10 hover:bg-yellow-50 dark:hover:bg-yellow-900/20'
                      : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-750'; ?>">
            <!-- 表紙画像（本棚追加リンク） -->
            <a href="<?php echo html($link_url); ?>" class="flex-shrink-0">
                <?php if (!empty($image_url)): ?>
                <img src="<?php echo html($image_url); ?>"
                     alt="<?php echo html($title); ?>"
                     class="w-12 h-16 object-cover rounded shadow-sm"
                     onerror="this.style.display='none'">
                <?php else: ?>
                <div class="w-12 h-16 bg-gray-200 dark:bg-gray-700 rounded flex items-center justify-center">
                    <i class="fas fa-book text-gray-400 dark:text-gray-500"></i>
                </div>
                <?php endif; ?>
            </a>

            <!-- 情報 -->
            <div class="min-w-0 flex-1">
                <a href="<?php echo html($link_url); ?>" class="block">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white group-hover:text-readnest-primary dark:group-hover:text-readnest-accent transition-colors">
                                <?php if ($is_unread): ?>
                                <span class="inline-block w-2 h-2 bg-yellow-500 rounded-full mr-1.5 flex-shrink-0"></span>
                                <?php endif; ?>
                                『<?php echo html($title); ?>』
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                <?php echo html($author); ?>
                                <?php if (!empty($published_date)): ?>
                                 · <?php echo html($published_date); ?>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                    <?php if ($author_info): ?>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                        あなたは<?php echo html($author); ?>の作品を<?php echo intval($author_info['book_count']); ?>冊読了
                        <?php if ($author_info['avg_rating']): ?>
                         · 平均<?php echo round(floatval($author_info['avg_rating']), 1); ?>点
                        <?php endif; ?>
                    </p>
                    <?php endif; ?>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                        <i class="far fa-clock mr-1"></i><?php echo date('Y/m/d', is_numeric($created_at) ? $created_at : strtotime($created_at)); ?>
                    </p>
                </a>
                <div class="mt-2 flex items-center gap-2">
                    <a href="<?php echo html($link_url); ?>"
                       class="inline-flex items-center text-xs text-readnest-primary dark:text-readnest-accent hover:underline">
                        <i class="fas fa-plus mr-1"></i>本棚に追加
                    </a>
                    <a href="<?php echo html($amazon_url); ?>"
                       target="_blank"
                       rel="noopener noreferrer sponsored"
                       class="inline-flex items-center px-2 py-1 bg-orange-500 text-white text-xs rounded hover:bg-orange-600 transition-colors">
                        <i class="fab fa-amazon mr-1"></i>Amazonで購入
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- ページネーション -->
    <?php if ($total_pages > 1): ?>
    <div class="flex justify-center gap-2 mb-8">
        <?php if ($page > 1): ?>
        <a href="/new_releases.php?filter=<?php echo $filter; ?>&page=<?php echo $page - 1; ?>"
           class="px-3 py-1.5 rounded-lg text-sm bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600">
            <i class="fas fa-chevron-left mr-1"></i>前へ
        </a>
        <?php endif; ?>

        <span class="px-3 py-1.5 text-sm text-gray-500 dark:text-gray-400">
            <?php echo $page; ?> / <?php echo $total_pages; ?>
        </span>

        <?php if ($page < $total_pages): ?>
        <a href="/new_releases.php?filter=<?php echo $filter; ?>&page=<?php echo $page + 1; ?>"
           class="px-3 py-1.5 rounded-lg text-sm bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600">
            次へ<i class="fas fa-chevron-right ml-1"></i>
        </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php elseif ($filter === 'unread'): ?>
    <div class="text-center py-12">
        <i class="fas fa-check-circle text-green-400 text-4xl mb-3"></i>
        <p class="text-gray-500 dark:text-gray-400">未読の新刊通知はありません</p>
        <a href="/new_releases.php?filter=all" class="text-sm text-readnest-primary dark:text-readnest-accent hover:underline mt-2 inline-block">
            すべての通知を見る
        </a>
    </div>
    <?php else: ?>
    <div class="text-center py-12 mb-8">
        <i class="fas fa-inbox text-gray-300 dark:text-gray-600 text-4xl mb-3"></i>
        <p class="text-gray-500 dark:text-gray-400">新刊通知はまだありません</p>
        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">毎日自動でチェックしています。新刊が見つかるとここに表示されます。</p>
    </div>
    <?php endif; ?>

    <?php if (!empty($cached_books)): ?>
    <!-- お気に入り作家の最近の著作（通知とは別にキャッシュから） -->
    <div class="border-t border-gray-200 dark:border-gray-700 pt-8">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">お気に入り作家の近刊</h2>
        <p class="text-xs text-gray-400 dark:text-gray-500 mb-4">あなたの本棚にはまだない作品です</p>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <?php foreach ($cached_books as $book): ?>
            <?php
                $author_info = $fav_authors[$book['author_name']] ?? null;
                $amazon_url = getAmazonProductUrl([
                    'title' => $book['book_title'],
                    'author' => $book['author_name']
                ]);
                $add_link = '/add_book.php?keyword=' . urlencode($book['book_title']);
            ?>
            <div class="group flex items-center gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-750 transition-colors">
                <a href="<?php echo html($add_link); ?>" class="flex-shrink-0">
                    <?php if (!empty($book['image_url'])): ?>
                    <img src="<?php echo html($book['image_url']); ?>"
                         alt="<?php echo html($book['book_title']); ?>"
                         class="w-10 h-14 object-cover rounded shadow-sm"
                         onerror="this.style.display='none'">
                    <?php else: ?>
                    <div class="w-10 h-14 bg-gray-100 dark:bg-gray-700 rounded flex items-center justify-center">
                        <i class="fas fa-book text-gray-300 dark:text-gray-500 text-sm"></i>
                    </div>
                    <?php endif; ?>
                </a>
                <div class="min-w-0 flex-1">
                    <a href="<?php echo html($add_link); ?>" class="block">
                        <p class="text-sm font-medium text-gray-800 dark:text-gray-200 group-hover:text-readnest-primary dark:group-hover:text-readnest-accent truncate transition-colors">
                            <?php echo html($book['book_title']); ?>
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            <?php echo html($book['author_name']); ?>
                            <?php if (!empty($book['published_date'])): ?>
                             · <?php echo html($book['published_date']); ?>
                            <?php endif; ?>
                        </p>
                    </a>
                </div>
                <a href="<?php echo html($amazon_url); ?>"
                   target="_blank"
                   rel="noopener noreferrer sponsored"
                   class="flex-shrink-0 inline-flex items-center px-2 py-1 bg-orange-500 text-white text-xs rounded hover:bg-orange-600 transition-colors">
                    <i class="fab fa-amazon mr-1"></i>Amazon
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- お気に入り作家一覧 -->
    <?php if (!empty($fav_authors)): ?>
    <div class="border-t border-gray-200 dark:border-gray-700 pt-8 mt-8">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">チェック対象の作家</h2>
        <p class="text-xs text-gray-400 dark:text-gray-500 mb-4">2冊以上読了または平均4.0点以上の作家</p>

        <div class="flex flex-wrap gap-2">
            <?php foreach ($fav_authors as $name => $info): ?>
            <a href="/author.php?name=<?php echo urlencode($name); ?>"
               class="inline-flex items-center px-3 py-1.5 rounded-full text-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-readnest-primary/10 dark:hover:bg-readnest-accent/10 hover:text-readnest-primary dark:hover:text-readnest-accent transition-colors">
                <?php echo html($name); ?>
                <span class="ml-1.5 text-xs text-gray-400 dark:text-gray-500"><?php echo intval($info['book_count']); ?>冊</span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php endif; ?>
</div>

<?php
$d_content = ob_get_clean();

include(__DIR__ . '/t_base.php');
?>
