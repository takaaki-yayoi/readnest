<?php
/**
 * 作家の本一覧テンプレート
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

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- ヘッダー -->
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-3xl font-bold text-gray-800">
                <?php echo htmlspecialchars($author); ?>
            </h1>
            <a href="/sakka_cloud.php" class="text-blue-600 hover:text-blue-700">
                <i class="fas fa-cloud mr-1"></i>作家クラウドへ
            </a>
        </div>
        
        <div class="flex items-center gap-6 text-gray-600">
            <div>
                <i class="fas fa-book mr-1"></i>
                <span class="font-semibold"><?php echo number_format($stats['total_books']); ?></span> 作品
            </div>
            <div>
                <i class="fas fa-users mr-1"></i>
                <span class="font-semibold"><?php echo number_format($stats['total_readers']); ?></span> 人が読書中
            </div>
        </div>
    </div>
    
    <!-- 本の一覧 -->
    <?php if (!empty($books)): ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($books as $book): ?>
        <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow">
            <div class="p-4">
                <div class="flex gap-4">
                    <!-- 本の画像 -->
                    <div class="flex-shrink-0">
                        <?php if (!empty($book['image_url'])): ?>
                        <img src="<?php echo htmlspecialchars($book['image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($book['title']); ?>"
                             class="w-20 h-28 object-cover rounded shadow-sm">
                        <?php else: ?>
                        <div class="w-20 h-28 bg-gray-200 rounded flex items-center justify-center">
                            <i class="fas fa-book text-gray-400 text-2xl"></i>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- 本の情報 -->
                    <div class="flex-1 min-w-0">
                        <h3 class="font-semibold text-gray-800 mb-2 line-clamp-2">
                            <?php echo htmlspecialchars($book['title']); ?>
                        </h3>
                        
                        <div class="space-y-1 text-sm text-gray-600">
                            <div>
                                <i class="fas fa-users mr-1 text-xs"></i>
                                <?php echo number_format($book['reader_count']); ?>人が登録
                            </div>
                            <?php if ($book['completed_count'] > 0): ?>
                            <div>
                                <i class="fas fa-check-circle mr-1 text-xs text-green-600"></i>
                                <?php echo number_format($book['completed_count']); ?>人が読了
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (isset($_SESSION['AUTH_USER'])): ?>
                        <div class="mt-3">
                            <a href="/book_entity/<?php echo urlencode($book['asin']); ?>" 
                               class="text-blue-600 hover:text-blue-700 text-sm font-medium">
                                詳細を見る <i class="fas fa-arrow-right ml-1 text-xs"></i>
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <?php if (count($books) >= 100): ?>
    <div class="mt-6 text-center text-gray-600">
        <p>上位100作品を表示しています</p>
    </div>
    <?php endif; ?>
    
    <?php else: ?>
    <div class="bg-white rounded-lg shadow-sm p-12 text-center">
        <i class="fas fa-book-open text-gray-300 text-5xl mb-4"></i>
        <p class="text-gray-500 text-lg">この作家の作品が見つかりませんでした</p>
    </div>
    <?php endif; ?>
    
    <!-- CTA（未ログインユーザー向け） -->
    <?php if (!isset($_SESSION['AUTH_USER']) && !empty($books)): ?>
    <div class="mt-12 bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg shadow-lg p-8 text-white text-center">
        <h2 class="text-2xl font-bold mb-4">読書記録を始めよう</h2>
        <p class="mb-6 opacity-90">
            無料でアカウントを作成して、あなたの読書記録を管理しましょう
        </p>
        <div class="flex gap-4 justify-center">
            <a href="/register.php" class="bg-white text-blue-600 px-6 py-3 rounded-lg font-semibold hover:bg-gray-100 transition-colors">
                <i class="fas fa-user-plus mr-2"></i>新規登録
            </a>
            <a href="/login.php" class="bg-blue-700 bg-opacity-50 text-white px-6 py-3 rounded-lg font-semibold hover:bg-opacity-70 transition-colors">
                <i class="fas fa-sign-in-alt mr-2"></i>ログイン
            </a>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php
$d_content = ob_get_clean();

// ベーステンプレートを使用
include(__DIR__ . '/t_base.php');
?>