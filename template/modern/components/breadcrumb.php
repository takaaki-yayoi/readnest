<?php
/**
 * パンくずリストコンポーネント
 * 
 * 使用方法:
 * $breadcrumbs = [
 *     ['label' => 'ホーム', 'url' => '/'],
 *     ['label' => '本棚', 'url' => '/bookshelf.php'],
 *     ['label' => '本の詳細', 'url' => null] // 現在のページはURLなし
 * ];
 * include(getTemplatePath('components/breadcrumb.php'));
 */

if (!defined('CONFIG')) {
    die('Direct access not allowed');
}

// パンくずリストが定義されていない場合は何も表示しない
if (!isset($breadcrumbs) || empty($breadcrumbs)) {
    return;
}
?>

<!-- Breadcrumb Navigation -->
<nav aria-label="パンくずリスト" class="bg-gray-50 border-b border-gray-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <ol class="flex items-center space-x-2 py-3 text-sm">
            <?php foreach ($breadcrumbs as $index => $item): ?>
                <?php if ($index > 0): ?>
                    <li class="flex items-center">
                        <svg class="flex-shrink-0 h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </li>
                <?php endif; ?>
                
                <li class="flex items-center">
                    <?php if ($item['url'] && $index < count($breadcrumbs) - 1): ?>
                        <!-- リンクありの項目 -->
                        <a href="<?php echo htmlspecialchars($item['url']); ?>" 
                           class="text-gray-600 hover:text-readnest-primary transition-colors">
                            <?php if ($index === 0): ?>
                                <i class="fas fa-home mr-1"></i>
                            <?php endif; ?>
                            <?php echo htmlspecialchars($item['label']); ?>
                        </a>
                    <?php else: ?>
                        <!-- 現在のページ（リンクなし） -->
                        <span class="text-gray-900 font-medium" aria-current="page">
                            <?php echo htmlspecialchars($item['label']); ?>
                        </span>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ol>
    </div>
</nav>