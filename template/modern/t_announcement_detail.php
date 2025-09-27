<?php
if(!defined('CONFIG')) {
    error_log('direct access detected...');
    die('reference for this file is not allowed.');
}

// メインコンテンツを生成
ob_start();
?>

<!-- お知らせ詳細 -->
<div class="bg-gray-50 dark:bg-gray-900 min-h-screen py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- パンくずリスト -->
        <nav class="mb-8" aria-label="Breadcrumb">
            <ol class="flex items-center space-x-2 text-sm">
                <li>
                    <a href="/" class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300">
                        <i class="fas fa-home"></i>
                    </a>
                </li>
                <li class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400 dark:text-gray-500 mx-2"></i>
                    <a href="/announcements.php" class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300">お知らせ</a>
                </li>
                <li class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400 dark:text-gray-500 mx-2"></i>
                    <span class="text-gray-900 dark:text-gray-100"><?php echo mb_strimwidth(html($announcement['title']), 0, 30, '...'); ?></span>
                </li>
            </ol>
        </nav>

        <!-- お知らせ詳細 -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
            <div class="p-6 md:p-8">
                <!-- タイトル -->
                <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-gray-100 mb-4">
                    <?php echo html($announcement['title']); ?>
                </h1>

                <!-- メタ情報 -->
                <div class="flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400 mb-6 pb-6 border-b dark:border-gray-700">
                    <div class="flex items-center">
                        <i class="far fa-calendar mr-2"></i>
                        <?php echo date('Y年n月j日', strtotime($announcement['published_at'])); ?>
                    </div>
                    <?php if ($announcement['priority'] === 'high'): ?>
                    <span class="px-2 py-1 bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200 text-xs font-medium rounded">重要</span>
                    <?php endif; ?>
                    <?php
                    $category_labels = [
                        'news' => ['お知らせ', 'bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200'],
                        'update' => ['アップデート', 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200'],
                        'maintenance' => ['メンテナンス', 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200'],
                        'feature' => ['新機能', 'bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200']
                    ];
                    $category = $announcement['category'] ?? 'news';
                    $label = $category_labels[$category] ?? $category_labels['news'];
                    ?>
                    <span class="px-2 py-1 <?php echo $label[1]; ?> text-xs font-medium rounded">
                        <?php echo $label[0]; ?>
                    </span>
                </div>

                <!-- 本文 -->
                <div class="prose prose-lg max-w-none">
                    <?php 
                    // HTMLタグを許可する場合はそのまま出力、そうでない場合は改行をbrタグに変換
                    $content = $announcement['content'];
                    // 基本的なHTMLタグのみ許可
                    $allowed_tags = '<p><br><strong><em><u><a><ul><ol><li><h2><h3><h4><blockquote>';
                    $content = strip_tags($content, $allowed_tags);
                    // 改行を<br>に変換（既にHTMLタグが含まれていない部分のみ）
                    if (!preg_match('/<[^>]+>/', $content)) {
                        $content = nl2br($content);
                    }
                    echo $content;
                    ?>
                </div>
            </div>
        </div>

        <!-- ナビゲーション -->
        <div class="mt-8 flex justify-between items-center">
            <a href="/announcements.php" class="inline-flex items-center text-readnest-primary hover:text-readnest-primary-dark transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>
                お知らせ一覧へ戻る
            </a>

            <?php
            // 前後のお知らせを取得
            $prev_sql = sprintf(
                "SELECT announcement_id, title FROM b_announcements 
                 WHERE announcement_id < %d AND status = 'published' 
                 ORDER BY announcement_id DESC LIMIT 1",
                $announcement_id
            );
            $prev = $g_db->getRow($prev_sql, null, DB_FETCHMODE_ASSOC);

            $next_sql = sprintf(
                "SELECT announcement_id, title FROM b_announcements 
                 WHERE announcement_id > %d AND status = 'published' 
                 ORDER BY announcement_id ASC LIMIT 1",
                $announcement_id
            );
            $next = $g_db->getRow($next_sql, null, DB_FETCHMODE_ASSOC);
            ?>

            <div class="flex gap-4">
                <?php if (!DB::isError($prev) && $prev): ?>
                <a href="/announcement_detail.php?id=<?php echo $prev['announcement_id']; ?>"
                   class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 transition-colors"
                   title="<?php echo html($prev['title']); ?>">
                    <i class="fas fa-chevron-left"></i> 前へ
                </a>
                <?php endif; ?>

                <?php if (!DB::isError($next) && $next): ?>
                <a href="/announcement_detail.php?id=<?php echo $next['announcement_id']; ?>"
                   class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 transition-colors"
                   title="<?php echo html($next['title']); ?>">
                    次へ <i class="fas fa-chevron-right"></i>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
$d_content = ob_get_clean();

// ベーステンプレートを使用
include(__DIR__ . '/t_base.php');
?>