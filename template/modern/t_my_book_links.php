<?php
if(!defined('CONFIG')) {
    error_log('direct access detected...');
    die('reference for this file is not allowed.');
}

// メインコンテンツを生成
ob_start();
?>

<!-- ヘッダーセクション -->
<section class="bg-gradient-to-r from-blue-500 to-cyan-600 dark:from-gray-800 dark:to-gray-700 text-white py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold mb-2">
            <i class="fas fa-link mr-3"></i>
            ブックリンク
        </h1>
        <p class="text-lg text-white opacity-90">
            本の中で紹介されていた本の一覧
        </p>
    </div>
</section>

<!-- 統計セクション -->
<section class="py-6 bg-gray-50 dark:bg-gray-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-3 gap-3 sm:gap-4">
            <div class="bg-white dark:bg-gray-700 rounded-lg shadow p-4 text-center">
                <p class="text-xs sm:text-sm text-gray-600 dark:text-gray-300 mb-1">リンク</p>
                <p class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-gray-100"><?php echo number_format($stats['total_links']); ?><span class="text-sm font-normal ml-1">件</span></p>
            </div>
            <div class="bg-white dark:bg-gray-700 rounded-lg shadow p-4 text-center">
                <p class="text-xs sm:text-sm text-gray-600 dark:text-gray-300 mb-1">紹介元の本</p>
                <p class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-gray-100"><?php echo number_format($stats['source_books']); ?><span class="text-sm font-normal ml-1">冊</span></p>
            </div>
            <div class="bg-white dark:bg-gray-700 rounded-lg shadow p-4 text-center">
                <p class="text-xs sm:text-sm text-gray-600 dark:text-gray-300 mb-1">本棚にない本</p>
                <p class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-gray-100"><?php echo number_format($stats['not_in_shelf']); ?><span class="text-sm font-normal ml-1">冊</span></p>
            </div>
        </div>
    </div>
</section>

<section class="py-6">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <?php if ($stats['total_links'] === 0): ?>
        <!-- まだリンクがない -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-8 text-center">
            <i class="fas fa-link text-4xl text-gray-300 dark:text-gray-600 mb-4"></i>
            <p class="text-gray-700 dark:text-gray-300 mb-2">まだブックリンクがありません。</p>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                読書中・読了の本の詳細ページにある「紹介された本を追加」から、本の中で紹介されていた本を残せます。
            </p>
            <a href="/help.php#book-links" class="text-sm text-readnest-primary hover:underline">使い方を見る</a>
        </div>
        <?php else: ?>

        <!-- 表示方法・絞り込み -->
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <div class="inline-flex rounded-md shadow-sm" role="group">
                <a href="<?php echo html(myBookLinksUrl('source', $not_in_shelf)); ?>"
                   class="px-3 py-2 text-sm rounded-l-md border <?php echo $view === 'source' ? 'bg-blue-600 border-blue-600 text-white' : 'bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'; ?>">
                    紹介元の本ごと
                </a>
                <a href="<?php echo html(myBookLinksUrl('target', $not_in_shelf)); ?>"
                   class="px-3 py-2 text-sm rounded-r-md border-t border-b border-r <?php echo $view === 'target' ? 'bg-blue-600 border-blue-600 text-white' : 'bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'; ?>">
                    紹介された本ごと
                </a>
            </div>
            <a href="<?php echo html(myBookLinksUrl($view, !$not_in_shelf)); ?>"
               class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 hover:text-blue-600">
                <i class="far <?php echo $not_in_shelf ? 'fa-check-square text-blue-600' : 'fa-square'; ?>"></i>
                本棚にない本だけ
            </a>
        </div>

        <?php if (empty($groups)): ?>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 text-center text-sm text-gray-500 dark:text-gray-400">
            本棚にない本はありません。
        </div>
        <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($groups as $group): ?>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <!-- グループの本 -->
                <div class="flex gap-3 pb-3 border-b border-gray-100 dark:border-gray-700">
                    <a href="<?php echo html($group['url']); ?>" class="flex-shrink-0">
                        <img src="<?php echo html($group['image_url']); ?>" alt="" loading="lazy"
                             class="w-12 h-16 object-cover rounded shadow-sm bg-gray-200"
                             onerror="this.src='/img/no-image-book.png'">
                    </a>
                    <div class="min-w-0 flex-1">
                        <a href="<?php echo html($group['url']); ?>"
                           class="font-semibold text-gray-900 dark:text-gray-100 hover:text-blue-600 line-clamp-2">
                            <?php echo html($group['title']); ?>
                        </a>
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate"><?php echo html($group['author']); ?></p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            <?php if ($view === 'source'): ?>
                            <?php echo count($group['items']); ?>冊を紹介
                            <?php else: ?>
                            <?php echo count($group['items']); ?>冊の本で紹介
                            <?php if (empty($group['in_shelf'])): ?>
                            <span class="ml-1 inline-block bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-700 rounded px-1.5">本棚にない本</span>
                            <?php endif; ?>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>

                <!-- 紹介された本（または紹介元の本） -->
                <ul class="mt-3 space-y-3">
                    <?php foreach ($group['items'] as $item): ?>
                    <li class="flex gap-3">
                        <a href="<?php echo html($item['url']); ?>" class="flex-shrink-0">
                            <img src="<?php echo html($item['image_url']); ?>" alt="" loading="lazy"
                                 class="w-10 h-14 object-cover rounded shadow-sm bg-gray-200"
                                 onerror="this.src='/img/no-image-book.png'">
                        </a>
                        <div class="min-w-0 flex-1">
                            <a href="<?php echo html($item['url']); ?>"
                               class="text-sm font-medium text-gray-900 dark:text-gray-100 hover:text-blue-600 line-clamp-2">
                                <?php echo html($item['title']); ?>
                            </a>
                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                <?php echo html($item['author']); ?>
                                <?php if ($view === 'source' && empty($item['target_book_id'])): ?>
                                <span class="ml-1 inline-block bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-700 rounded px-1.5">本棚にない本</span>
                                <?php endif; ?>
                            </p>
                            <?php if ($item['page'] !== null || $item['memo'] !== ''): ?>
                            <div class="mt-1 text-xs text-gray-600 dark:text-gray-300">
                                <?php if ($item['page'] !== null): ?>
                                <span class="inline-block bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded px-1.5 mr-1">p.<?php echo (int)$item['page']; ?></span>
                                <?php endif; ?>
                                <span class="whitespace-pre-line break-words"><?php echo html($item['memo']); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <p class="mt-6 text-xs text-gray-500 dark:text-gray-400">
            <i class="fas fa-info-circle mr-1"></i>
            リンクの追加・メモの編集・削除は、紹介元の本の詳細ページで行えます。
        </p>
        <?php endif; ?>
    </div>
</section>

<?php
$d_content = ob_get_clean();

// ベーステンプレートを使用
include(__DIR__ . '/t_base.php');
?>
