<?php
if(!defined('CONFIG')) {
  error_log('direct access detected. file:' . __FILE__ . ' line:' . __LINE__ . ' ip:' . $_SERVER['REMOTE_ADDR'] . ' agent:' . $_SERVER['HTTP_USER_AGENT']);
  die('reference for this file is not allowed.');
}

// コンテンツ部分を生成
ob_start();
?>

<!-- ヘッダーセクション -->
<section class="bg-gradient-to-r from-readnest-primary to-readnest-accent dark:from-gray-800 dark:to-gray-700 text-white py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h1 class="text-3xl sm:text-4xl font-bold mb-4">
                <i class="fas fa-edit mr-3"></i>手動で本を追加
            </h1>
            <p class="text-xl text-white opacity-90">
                検索で見つからない本を手動で本棚に追加できます
            </p>
        </div>
    </div>
</section>

<!-- パンくずナビ -->
<section class="bg-gray-50 py-3">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <nav class="text-sm">
            <a href="/" class="text-readnest-primary hover:underline">ホーム</a>
            <span class="mx-2 text-gray-500">/</span>
            <a href="/add_book.php" class="text-readnest-primary hover:underline">本を追加</a>
            <span class="mx-2 text-gray-500">/</span>
            <span class="text-gray-700">手動で本を追加</span>
        </nav>
    </div>
</section>

<!-- メッセージ表示 -->
<?php if (!empty($d_message)): ?>
<section class="bg-green-50 border-l-4 border-green-400 p-4 mb-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-check-circle text-green-400"></i>
            </div>
            <div class="ml-3">
                <p class="text-green-700"><?php echo $d_message; ?></p>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- エラーメッセージ表示 -->
<?php if (!empty($errors)): ?>
<section class="bg-red-50 border-l-4 border-red-400 p-4 mb-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-exclamation-circle text-red-400"></i>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-red-800">以下のエラーを修正してください：</h3>
                <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                    <?php foreach ($errors as $error): ?>
                    <li><?php echo html($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- メインコンテンツ -->
<section class="py-8">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">

        <?php if ($step === 'complete'): ?>
        <!-- 完了画面 -->
        <div class="bg-white rounded-lg shadow-sm border p-8 text-center">
            <div class="mb-6">
                <i class="fas fa-check-circle text-6xl text-green-500"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 mb-4">本の追加が完了しました！</h2>
            <div class="space-y-4">
                <a href="/bookshelf.php" 
                   class="btn bg-readnest-primary text-white px-8 py-3 text-lg">
                    <i class="fas fa-book-open mr-2"></i>本棚を見る
                </a>
                <a href="/add_book.php" 
                   class="btn bg-gray-200 text-gray-700 px-8 py-3 text-lg ml-4">
                    <i class="fas fa-plus mr-2"></i>他の本を追加
                </a>
            </div>
        </div>

        <?php elseif ($step === 'confirm'): ?>
        <!-- 確認画面 -->
        <div class="bg-white rounded-lg shadow-sm border p-6">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">入力内容の確認</h2>
            
            <div class="space-y-4 mb-8">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 py-4 border-b">
                    <div class="font-medium text-gray-700">タイトル</div>
                    <div class="md:col-span-2 text-gray-900"><?php echo html($form_data['title']); ?></div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 py-4 border-b">
                    <div class="font-medium text-gray-700">著者</div>
                    <div class="md:col-span-2 text-gray-900"><?php echo html($form_data['author']); ?></div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 py-4 border-b">
                    <div class="font-medium text-gray-700">ページ数</div>
                    <div class="md:col-span-2 text-gray-900">
                        <?php echo html($form_data['number_of_pages']); ?>ページ
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 py-4 border-b">
                    <div class="font-medium text-gray-700">読書ステータス</div>
                    <div class="md:col-span-2 text-gray-900"><?php echo html($status_options[$form_data['status_list']]); ?></div>
                </div>
                
            </div>
            
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <form action="" method="post" class="inline">
                    <?php csrfFieldTag(); ?>
                    <?php foreach ($form_data as $key => $value): ?>
                    <input type="hidden" name="<?php echo html($key); ?>" value="<?php echo html($value); ?>">
                    <?php endforeach; ?>
                    <input type="hidden" name="confirm" value="yes">
                    <button type="submit" 
                            class="btn bg-readnest-primary text-white px-8 py-3 text-lg"
                            onclick="return confirm('この内容で本棚に追加しますか？')">
                        <i class="fas fa-check mr-2"></i>本棚に追加
                    </button>
                </form>
                
                <form action="" method="post" class="inline">
                    <?php csrfFieldTag(); ?>
                    <?php foreach ($form_data as $key => $value): ?>
                    <input type="hidden" name="<?php echo html($key); ?>" value="<?php echo html($value); ?>">
                    <?php endforeach; ?>
                    <input type="hidden" name="confirm" value="no">
                    <button type="submit" class="btn bg-gray-200 text-gray-700 px-8 py-3 text-lg">
                        <i class="fas fa-edit mr-2"></i>修正する
                    </button>
                </form>
            </div>
        </div>

        <?php else: ?>
        <!-- 入力画面 -->
        <div class="bg-white rounded-lg shadow-sm border p-6">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">本の情報を入力</h2>
            
            <form action="" method="post" class="space-y-6">
                <?php csrfFieldTag(); ?>
                <!-- タイトル（必須） -->
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                        タイトル <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           name="title" 
                           id="title"
                           value="<?php echo html($form_data['title']); ?>"
                           required
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-readnest-primary focus:border-transparent dark:bg-gray-700 dark:text-gray-200 dark:placeholder-gray-400"
                           placeholder="本のタイトルを入力してください">
                </div>
                
                <!-- 著者（必須） -->
                <div>
                    <label for="author" class="block text-sm font-medium text-gray-700 mb-2">
                        著者 <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           name="author" 
                           id="author"
                           value="<?php echo html($form_data['author']); ?>"
                           required
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-readnest-primary focus:border-transparent dark:bg-gray-700 dark:text-gray-200 dark:placeholder-gray-400"
                           placeholder="著者名を入力してください">
                </div>
                
                <!-- ページ数（必須） -->
                <div>
                    <label for="number_of_pages" class="block text-sm font-medium text-gray-700 mb-2">
                        ページ数 <span class="text-red-500">*</span>
                    </label>
                    <input type="number" 
                           name="number_of_pages" 
                           id="number_of_pages"
                           value="<?php echo html($form_data['number_of_pages']); ?>"
                           required
                           min="1" 
                           max="9999"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-readnest-primary focus:border-transparent dark:bg-gray-700 dark:text-gray-200 dark:placeholder-gray-400"
                           placeholder="ページ数を入力してください">
                </div>
                
                <!-- 読書ステータス -->
                <div>
                    <label for="status_list" class="block text-sm font-medium text-gray-700 mb-2">
                        読書ステータス
                    </label>
                    <select name="status_list" 
                            id="status_list"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-readnest-primary focus:border-transparent dark:bg-gray-700 dark:text-gray-200 dark:placeholder-gray-400">
                        <?php foreach ($status_options as $value => $label): ?>
                        <option value="<?php echo $value; ?>" <?php echo $form_data['status_list'] == $value ? 'selected' : ''; ?>>
                            <?php echo html($label); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                
                <!-- 送信ボタン -->
                <div class="flex flex-col sm:flex-row gap-4 justify-center pt-4">
                    <button type="submit" 
                            class="btn bg-readnest-primary text-white px-8 py-3 text-lg">
                        <i class="fas fa-check mr-2"></i>確認画面へ
                    </button>
                    <a href="/add_book.php" 
                       class="btn bg-gray-200 text-gray-700 px-8 py-3 text-lg text-center">
                        <i class="fas fa-arrow-left mr-2"></i>検索に戻る
                    </a>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- 使い方ガイド -->
        <?php if ($step === 'input'): ?>
        <div class="mt-8 bg-blue-50 rounded-lg p-6">
            <h3 class="text-lg font-semibold text-blue-900 mb-3">
                <i class="fas fa-info-circle mr-2"></i>手動追加について
            </h3>
            <ul class="space-y-2 text-blue-800">
                <li class="flex items-start">
                    <i class="fas fa-check-circle text-blue-500 mt-1 mr-2 flex-shrink-0"></i>
                    <span>検索で見つからない本や、古い本、自費出版本などを追加できます</span>
                </li>
                <li class="flex items-start">
                    <i class="fas fa-check-circle text-blue-500 mt-1 mr-2 flex-shrink-0"></i>
                    <span>タイトル、著者名、ページ数は必須項目です</span>
                </li>
                <li class="flex items-start">
                    <i class="fas fa-check-circle text-blue-500 mt-1 mr-2 flex-shrink-0"></i>
                    <span>ページ数は読書進捗の記録に使用されます</span>
                </li>
                <li class="flex items-start">
                    <i class="fas fa-check-circle text-blue-500 mt-1 mr-2 flex-shrink-0"></i>
                    <span>追加後でも本の詳細ページから情報を編集できます</span>
                </li>
            </ul>
        </div>
        <?php endif; ?>

    </div>
</section>

<!-- 追加のスクリプト -->
<?php
ob_start();
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // フォームバリデーション
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            const title = document.getElementById('title');
            const author = document.getElementById('author');
            const pages = document.getElementById('number_of_pages');
            
            // 基本的なクライアントサイドバリデーション
            if (title && !title.value.trim()) {
                e.preventDefault();
                alert('タイトルを入力してください。');
                title.focus();
                return false;
            }
            
            if (author && !author.value.trim()) {
                e.preventDefault();
                alert('著者名を入力してください。');
                author.focus();
                return false;
            }
            
            if (pages && !pages.value.trim()) {
                e.preventDefault();
                alert('ページ数を入力してください。');
                pages.focus();
                return false;
            }
        });
    }
    
    // 文字数カウンター（必要に応じて）
    const titleInput = document.getElementById('title');
    const authorInput = document.getElementById('author');
    
    if (titleInput) {
        titleInput.addEventListener('input', function() {
            if (this.value.length > 200) {
                this.setCustomValidity('タイトルは200文字以内で入力してください。');
            } else {
                this.setCustomValidity('');
            }
        });
    }
    
    if (authorInput) {
        authorInput.addEventListener('input', function() {
            if (this.value.length > 100) {
                this.setCustomValidity('著者名は100文字以内で入力してください。');
            } else {
                this.setCustomValidity('');
            }
        });
    }
});
</script>
<?php
$d_additional_scripts = ob_get_clean();

$d_content = ob_get_clean();

// ベーステンプレートを使用
include(__DIR__ . '/t_base.php');
?>