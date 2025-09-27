<?php
if(!defined('CONFIG')) {
    error_log('direct access detected...');
    die('reference for this file is not allowed.');
}

// メインコンテンツを生成
ob_start();
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- ページヘッダー -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">
            <i class="fas fa-feather-alt text-gray-500 mr-3"></i>
            あなたの作家一覧
        </h1>
        <p class="mt-2 text-gray-600">
            あなたが読んだ本の作家を表示しています（<?php echo count($author_cloud_data); ?>人）
        </p>
    </div>

    <!-- 作家クラウド -->
    <div class="bg-white rounded-lg shadow p-6">
        <?php if (empty($author_cloud_data)): ?>
            <p class="text-gray-500 text-center py-8">まだ作家データがありません</p>
        <?php else: ?>
            <div class="text-center">
                <?php foreach ($author_cloud_data as $author): ?>
                    <?php
                    $colorClass = $author['color_class'] ?? 'from-gray-500 to-gray-600';
                    $fontSize = $author['font_size'] ?? 14;
                    $isFavorite = $author['is_favorite'] ?? false;
                    ?>
                    <a href="/bookshelf.php?search_word=<?php echo urlencode($author['author']); ?>&search_type=author" 
                       class="inline-block px-3 py-2 m-2 rounded-lg transition-all duration-300 hover:scale-110 bg-gradient-to-r <?php echo $colorClass; ?> text-white <?php echo $isFavorite ? 'ring-2 ring-yellow-400' : ''; ?>"
                       style="font-size: <?php echo $fontSize; ?>px;"
                       title="<?php echo htmlspecialchars($author['author']); ?> (<?php echo $author['book_count']; ?>冊)">
                        <?php echo htmlspecialchars($author['author']); ?>
                        <?php if ($isFavorite): ?>⭐<?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- 凡例 -->
            <div class="mt-8 pt-6 border-t border-gray-200">
                <p class="text-sm text-gray-600 mb-3">凡例：</p>
                <div class="flex flex-wrap gap-4 text-sm">
                    <div class="flex items-center gap-2">
                        <span class="inline-block w-4 h-4 bg-gradient-to-r from-green-500 to-emerald-600 rounded"></span>
                        <span class="text-gray-600">読了した本が多い</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="inline-block w-4 h-4 bg-gradient-to-r from-blue-500 to-indigo-600 rounded"></span>
                        <span class="text-gray-600">読書中の本がある</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="inline-block w-4 h-4 bg-gradient-to-r from-gray-500 to-gray-600 rounded"></span>
                        <span class="text-gray-600">その他</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-yellow-400">⭐</span>
                        <span class="text-gray-600">高評価（平均4.0以上）</span>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- 説明 -->
    <div class="mt-6 bg-blue-50 rounded-lg p-4">
        <p class="text-sm text-blue-800">
            <i class="fas fa-info-circle mr-2"></i>
            作家名をクリックすると、あなたの本棚でその作家の本を検索できます。
        </p>
    </div>
</div>

<?php
$d_content = ob_get_clean();

// ベーステンプレートを使用
include(__DIR__ . '/t_base.php');
?>