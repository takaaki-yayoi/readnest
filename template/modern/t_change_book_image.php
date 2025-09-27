<?php
if(!defined('CONFIG')) {
    error_log('direct access detected. file:' . __FILE__ . ' line:' . __LINE__ . ' ip:' . $_SERVER['REMOTE_ADDR'] . ' agent:' . $_SERVER['HTTP_USER_AGENT']);
    die('reference for this file is not allowed.');
}

// ヘルパー関数を読み込み
require_once(dirname(dirname(__DIR__)) . '/library/form_helpers.php');

// メインコンテンツを生成
ob_start();
?>
<div class="container mx-auto px-4 py-8 max-w-4xl">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">表紙画像の変更</h1>
        
        <?php if (isset($message) && $message): ?>
        <div class="mb-6 p-4 rounded-lg <?php echo $message_type === 'success' ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300' : 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300'; ?>">
            <?php echo html($message); ?>
        </div>
        <?php endif; ?>
        
        <div class="mb-6 pb-6 border-b">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-2"><?php echo html($book['name']); ?></h2>
            <p class="text-gray-600 dark:text-gray-400"><?php echo html($book['author'] ?? ''); ?></p>
        </div>
        
        <div class="mb-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3">現在の表紙</h3>
            <img src="<?php echo html($book['image_url'] ?? '/img/no-image-book.png'); ?>" 
                 alt="現在の表紙" 
                 class="w-32 h-auto border rounded shadow">
        </div>
        
        <?php if (!empty($candidates)): ?>
        <div class="mb-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3">利用可能な画像</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                <?php foreach ($candidates as $key => $candidate): ?>
                <?php 
                    $url = is_array($candidate) ? $candidate['url'] : $candidate;
                    $source = is_array($candidate) ? $candidate['source'] : $key;
                    $title = is_array($candidate) ? $candidate['title'] : '';
                ?>
                <div class="text-center p-3 border border-gray-200 dark:border-gray-600 rounded hover:bg-gray-50 dark:hover:bg-gray-700 hover:shadow-md transition-all">
                    <p class="text-xs text-gray-600 dark:text-gray-400 mb-1 font-medium"><?php echo htmlspecialchars($source); ?></p>
                    <?php if ($title): ?>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-2 truncate" title="<?php echo htmlspecialchars($title); ?>">
                        <?php echo htmlspecialchars($title); ?>
                    </p>
                    <?php endif; ?>
                    <div class="relative h-48 mb-3">
                        <img src="<?php echo htmlspecialchars($url); ?>" 
                             alt="<?php echo htmlspecialchars($source); ?>" 
                             class="w-full h-full object-contain border rounded"
                             onerror="this.src='/img/no-image-book.png'; this.onerror=null;">
                    </div>
                    <form method="post" class="inline">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="image_url" value="<?php echo htmlspecialchars($url); ?>">
                        <button type="submit" class="bg-blue-600 text-white px-3 py-1.5 rounded hover:bg-blue-700 text-sm w-full">
                            この画像を使用
                        </button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php else: ?>
        <div class="mb-6 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg">
            <p class="text-sm text-yellow-800 dark:text-yellow-200">
                <i class="fas fa-info-circle mr-2"></i>
                この書籍の候補画像が見つかりませんでした。下記より画像をアップロードしてください。
            </p>
        </div>
        <?php endif; ?>
        
        <!-- デフォルト画像オプション -->
        <div class="mb-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3">デフォルト画像</h3>
            <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg border border-gray-200 dark:border-gray-600">
                <div class="flex items-center space-x-4">
                    <img src="/img/no-image-book.png" 
                         alt="デフォルト画像" 
                         class="w-20 h-28 object-contain border rounded">
                    <div class="flex-1">
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                            表紙画像を表示しない場合は、デフォルト画像を使用します。
                        </p>
                        <form method="post" class="inline">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <input type="hidden" name="image_url" value="/img/no-image-book.png">
                            <button type="submit" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700 text-sm">
                                <i class="fas fa-undo mr-2"></i>デフォルト画像に戻す
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="mb-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3">画像をアップロード</h3>
            <form method="post" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                
                <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-6 text-center hover:border-gray-400 dark:hover:border-gray-500 transition-colors">
                    <input type="file" 
                           name="image_file" 
                           id="image_file"
                           accept="image/jpeg,image/jpg,image/png,image/gif,image/webp"
                           class="hidden"
                           onchange="updateFileName(this)"
                           required>
                    <label for="image_file" class="cursor-pointer">
                        <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 dark:text-gray-500 mb-2"></i>
                        <p class="text-gray-600 dark:text-gray-400 mb-2">クリックして画像を選択、またはドラッグ&ドロップ</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">対応形式: JPEG, PNG, GIF, WebP (最大5MB)</p>
                    </label>
                    <p id="selected-file" class="mt-2 text-sm text-blue-600 dark:text-blue-400 font-medium"></p>
                </div>
                
                <button type="submit" class="w-full bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 font-medium disabled:opacity-50 disabled:cursor-not-allowed" id="upload-button" disabled>
                    <i class="fas fa-upload mr-2"></i>画像をアップロード
                </button>
            </form>
        </div>
        
        <script>
        function updateFileName(input) {
            const fileName = input.files[0]?.name || '';
            const fileInfo = document.getElementById('selected-file');
            const uploadButton = document.getElementById('upload-button');
            
            if (fileName) {
                fileInfo.textContent = '選択されたファイル: ' + fileName;
                uploadButton.disabled = false;
            } else {
                fileInfo.textContent = '';
                uploadButton.disabled = true;
            }
        }
        
        // ドラッグ&ドロップ対応
        document.addEventListener('DOMContentLoaded', function() {
            const dropZone = document.querySelector('.border-dashed');
            const fileInput = document.getElementById('image_file');
            
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, preventDefaults, false);
            });
            
            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }
            
            ['dragenter', 'dragover'].forEach(eventName => {
                dropZone.addEventListener(eventName, highlight, false);
            });
            
            ['dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, unhighlight, false);
            });
            
            function highlight(e) {
                dropZone.classList.add('border-blue-500', 'bg-blue-50', 'dark:bg-blue-900/20');
            }

            function unhighlight(e) {
                dropZone.classList.remove('border-blue-500', 'bg-blue-50', 'dark:bg-blue-900/20');
            }
            
            dropZone.addEventListener('drop', handleDrop, false);
            
            function handleDrop(e) {
                const dt = e.dataTransfer;
                const files = dt.files;
                
                if (files.length > 0) {
                    fileInput.files = files;
                    updateFileName(fileInput);
                }
            }
        });
        </script>
        
        <div class="mt-6 pt-6 border-t text-center">
            <a href="/book/<?php echo $book_id; ?>" class="text-readnest-primary hover:underline">
                <i class="fas fa-arrow-left mr-1"></i>書籍詳細に戻る
            </a>
        </div>
    </div>
</div>

<?php
$d_content = ob_get_clean();

// ベーステンプレートを使用
include(__DIR__ . '/t_base.php');
?>