<?php
if(!defined('CONFIG')) {
  error_log('direct access detected. file:' . __FILE__ . ' line:' . __LINE__ . ' ip:' . $_SERVER['REMOTE_ADDR'] . ' agent:' . $_SERVER['HTTP_USER_AGENT']);
  die('reference for this file is not allowed.');
}

// コンテンツ部分を生成
ob_start();
?>

<!-- ヘッダーセクション - レスポンシブ対応 -->
<section class="bg-gradient-to-r from-readnest-primary to-readnest-accent dark:from-gray-800 dark:to-gray-700 text-white py-6 sm:py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h1 class="text-2xl sm:text-3xl md:text-4xl font-bold mb-2 sm:mb-4">
                <i class="fas fa-plus-circle mr-2 sm:mr-3 text-xl sm:text-2xl md:text-3xl"></i>本を追加
                <a href="/help.php#add-books" class="ml-3 text-base text-white opacity-80 hover:opacity-100 transition-opacity" title="本の追加方法">
                    <i class="fas fa-question-circle"></i>
                </a>
            </h1>
            <p class="text-base sm:text-lg md:text-xl text-white opacity-90">
                お気に入りの本を見つけて、あなたの本棚に追加しましょう
            </p>
            <div class="mt-3 flex items-center justify-center text-sm sm:text-base text-white opacity-80">
                <i class="fas fa-magic mr-2"></i>
                AI検索対応：「泣ける恋愛小説」「夏に読みたい本」などの自然な言葉でも検索できます
            </div>
        </div>
    </div>
</section>

<!-- メッセージ表示 -->
<?php if (!empty($d_message)): ?>
<section class="bg-green-50 dark:bg-green-900/20 border-l-4 border-green-400 dark:border-green-600 p-4 mb-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-check-circle text-green-400"></i>
            </div>
            <div class="ml-3">
                <p class="text-green-700 dark:text-green-400"><?php echo $d_message; ?></p>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- エラーメッセージ表示 -->
<?php if (!empty($g_error)): ?>
<section class="bg-red-50 dark:bg-red-900/20 border-l-4 border-red-400 dark:border-red-600 p-4 mb-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-exclamation-circle text-red-400"></i>
            </div>
            <div class="ml-3">
                <p class="text-red-700 dark:text-red-400"><?php echo html($g_error); ?></p>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- 検索セクション - レスポンシブ対応 -->
<section class="py-6 sm:py-8 bg-gray-50 dark:bg-gray-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-2xl mx-auto">
            <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="get" class="space-y-3 sm:space-y-4">
                <div class="relative">
                    <label for="keyword" class="sr-only">検索キーワード</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400"></i>
                        </div>
                        <input type="text"
                               name="keyword"
                               id="keyword"
                               class="block w-full pl-10 pr-3 py-2.5 sm:py-3 border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-readnest-primary focus:border-transparent text-base sm:text-lg placeholder-gray-400 dark:placeholder-gray-500"
                               placeholder="本のタイトル、著者名で検索..."
                               value="<?php echo html(isset($d_keyword) ? $d_keyword : ''); ?>"
                               autofocus>
                    </div>
                </div>
                
                <!-- AI検索トグル -->
                <div class="bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/30 dark:to-pink-900/30 rounded-lg p-3 mb-3 border border-purple-200 dark:border-purple-700 cursor-pointer hover:border-purple-300 dark:hover:border-purple-600 transition-colors"
                     onclick="document.getElementById('use-ai-search').click()">
                    <div class="flex items-center justify-center">
                        <div class="flex items-center">
                            <input type="checkbox" 
                                   id="use-ai-search" 
                                   name="ai_search"
                                   class="mr-2 h-4 w-4 text-readnest-primary focus:ring-readnest-primary border-gray-300 rounded cursor-pointer"
                                   onclick="event.stopPropagation()"
                                   <?php echo (isset($_GET['ai_search']) && $_GET['ai_search'] === 'on') ? 'checked' : ''; ?>>
                            <label for="use-ai-search" class="text-sm font-medium text-gray-800 dark:text-gray-200 cursor-pointer select-none">
                                <i class="fas fa-magic mr-1 text-purple-600"></i>
                                AI検索を使用
                            </label>
                        </div>
                    </div>
                    <p class="text-xs text-gray-600 dark:text-gray-400 text-center mt-1 select-none">
                        「感動する小説」「ビジネスで役立つ本」など、自然な言葉で本を探せます
                    </p>
                </div>
                
                <div class="grid grid-cols-1 xs:grid-cols-3 gap-2 sm:gap-3">
                    <button type="submit" 
                            id="searchButton"
                            class="xs:col-span-1 bg-readnest-primary text-white py-2.5 sm:py-3 px-4 sm:px-6 rounded-lg hover:bg-readnest-accent transition-colors font-semibold text-sm sm:text-base">
                        <i class="fas fa-search mr-1.5 sm:mr-2"></i>検索
                    </button>
                    <button type="button" 
                            onclick="toggleBarcodeScanner()"
                            class="xs:col-span-1 bg-blue-500 text-white py-2.5 sm:py-3 px-4 sm:px-6 rounded-lg hover:bg-blue-600 transition-colors font-semibold text-sm sm:text-base relative group">
                        <i class="fas fa-barcode mr-1.5 sm:mr-2"></i>バーコード
                        <a href="/help.php#add-books" target="_blank" 
                           class="absolute -top-1 -right-1 bg-yellow-400 text-gray-800 rounded-full w-5 h-5 flex items-center justify-center text-xs hover:bg-yellow-500 transition-colors"
                           title="バーコード読み取りの使い方"
                           onclick="event.stopPropagation()">
                            <i class="fas fa-question"></i>
                        </a>
                    </button>
                    <button type="button" 
                            onclick="document.getElementById('keyword').value=''; document.getElementById('keyword').focus();"
                            class="xs:col-span-1 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 py-2.5 sm:py-3 px-4 sm:px-6 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors text-sm sm:text-base">
                        <i class="fas fa-times mr-1.5 sm:mr-2"></i>リセット
                    </button>
                </div>
            </form>
            
            <!-- 代替オプション - レスポンシブ対応 -->
            <div class="mt-4 sm:mt-6 text-center">
                <p class="text-sm sm:text-base text-gray-600 dark:text-gray-400 mb-2 sm:mb-3">検索で見つからない本がありますか？</p>
                <a href="add_original_book.php" 
                   class="inline-flex items-center px-3 sm:px-4 py-1.5 sm:py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors text-sm sm:text-base">
                    <i class="fas fa-plus mr-1.5 sm:mr-2"></i>手動で本を追加
                </a>
            </div>
        </div>
        
        <!-- バーコードスキャナーモーダル - レスポンシブ対応 -->
        <div id="barcodeModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
            <div class="flex items-center justify-center min-h-screen p-2 sm:p-4">
                <div class="bg-white dark:bg-gray-800 rounded-lg max-w-md w-full mx-2 sm:mx-4">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold">バーコード読み取り</h3>
                            <button onclick="closeBarcodeScanner()" class="text-gray-400 hover:text-gray-600">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        
                        <div class="space-y-4">
                            <div class="text-center">
                                <p class="text-gray-600 mb-4">本の裏面にあるISBNバーコードをカメラで読み取ってください</p>
                                
                                <!-- 読み取りのコツ -->
                                <div class="bg-blue-50 rounded-lg p-3 mb-4">
                                    <div class="text-xs text-blue-800 space-y-1">
                                        <div class="flex items-center justify-center space-x-1">
                                            <i class="fas fa-lightbulb text-yellow-500"></i>
                                            <span>📏 バーコードから10-15cm離してください</span>
                                        </div>
                                        <div class="flex items-center justify-center space-x-1">
                                            <i class="fas fa-sun text-yellow-500"></i>
                                            <span>💡 明るい場所で読み取ってください</span>
                                        </div>
                                        <div class="flex items-center justify-center space-x-1">
                                            <i class="fas fa-hand text-blue-500"></i>
                                            <span>📱 手振れに注意してゆっくり動かしてください</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- カメラプレビュー -->
                                <div id="barcodePreview" class="bg-gray-100 rounded-lg overflow-hidden relative" style="height: 300px;">
                                    <video id="barcodeVideo" class="w-full h-full object-cover cursor-pointer" autoplay playsinline muted title="タップしてフォーカス"></video>
                                    <!-- フォーカスガイド -->
                                    <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                                        <div class="border-2 border-red-500 bg-red-500 bg-opacity-10 rounded" style="width: 80%; height: 100px;">
                                            <div class="text-white text-xs mt-1 text-center drop-shadow-lg">📱 バーコードをここに合わせてください</div>
                                            <div class="text-white text-xs text-center drop-shadow-lg">👆 画面をタップしてフォーカス調整</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- スキャン状態表示 -->
                                <div id="scanStatus" class="mt-4 text-sm text-gray-600">
                                    カメラを起動中...
                                </div>
                                
                                <!-- エラーメッセージ -->
                                <div id="barcodeError" class="mt-4 text-sm text-red-600 hidden"></div>
                            </div>
                            
                            <div class="text-center">
                                <p class="text-xs text-gray-500">バーコードが読み取れない場合は、右上の×ボタンで閉じてキーワード検索をお試しください</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </div>
    </div>
</section>

<!-- 作家情報ポータル表示 -->
<?php if (!empty($d_author_info_html)): ?>
<section class="py-6 sm:py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <?php echo $d_author_info_html; ?>
    </div>
</section>
<?php endif; ?>

<!-- 検索結果セクション - レスポンシブ対応 -->
<?php if (!empty($d_total_hit) || !empty($d_book_list)): ?>
<section class="py-6 sm:py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- 検索結果ヘッダー -->
        <?php if (!empty($d_total_hit)): ?>
        <div class="mb-4 sm:mb-6">
            <h2 class="text-xl sm:text-2xl font-bold text-gray-900 mb-1 sm:mb-2">検索結果</h2>
            <p class="text-sm sm:text-base text-gray-600"><?php echo html($d_total_hit); ?></p>
            
            <!-- AI検索情報の表示 -->
            <?php if (isset($ai_search_intent) && !empty($ai_search_intent)): ?>
            <div class="mt-3 p-3 bg-blue-50 rounded-lg">
                <p class="text-sm text-blue-800">
                    <i class="fas fa-magic mr-1"></i>
                    AI検索: 
                    <?php 
                    $intent_labels = [
                        'genre' => 'ジャンル',
                        'mood' => '気分・雰囲気',
                        'similar' => '類似本',
                        'author' => '著者',
                        'theme' => 'テーマ',
                        'specific' => '特定の本'
                    ];
                    $intents = array_map(function($intent) use ($intent_labels) {
                        return $intent_labels[$intent] ?? $intent;
                    }, $ai_search_intent);
                    echo html(implode('、', $intents)) . 'で検索しました';
                    ?>
                </p>
                <?php if (isset($ai_expanded_keywords) && !empty($ai_expanded_keywords)): ?>
                <p class="text-xs text-blue-700 mt-1">
                    拡張キーワード: <?php echo html(implode('、', array_slice($ai_expanded_keywords, 0, 5))); ?>
                </p>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- ページネーション（上部） - レスポンシブ対応 -->
        <?php if (!empty($d_pager)): ?>
        <div class="mb-4 sm:mb-6">
            <nav class="flex justify-center overflow-x-auto">
                <?php echo $d_pager; ?>
            </nav>
        </div>
        <?php endif; ?>
        
        <!-- 本リスト -->
        <?php if (!empty($d_book_list)): ?>
        <div class="mb-6 sm:mb-8">
            <?php echo $d_book_list; ?>
        </div>
        <?php endif; ?>
        
        <!-- ページネーション（下部） - レスポンシブ対応 -->
        <?php if (!empty($d_pager)): ?>
        <div class="mt-6 sm:mt-8">
            <nav class="flex justify-center overflow-x-auto">
                <?php echo $d_pager; ?>
            </nav>
        </div>
        <?php endif; ?>
        
    </div>
</section>
<?php endif; ?>

<!-- 使い方ガイド - レスポンシブ対応 -->
<?php if (empty($d_book_list) && empty($d_total_hit)): ?>
<section class="py-8 sm:py-12">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-6 sm:mb-8">
            <h2 class="text-xl sm:text-2xl font-bold text-gray-900 mb-2 sm:mb-4">本の追加方法</h2>
            <p class="text-sm sm:text-base text-gray-600">ReadNestに本を追加する簡単な手順をご紹介します</p>
        </div>
        
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 sm:gap-4 md:gap-6">
            <!-- ステップ1: 検索方法 -->
            <div class="text-center">
                <div class="w-14 h-14 sm:w-16 sm:h-16 bg-readnest-primary rounded-full flex items-center justify-center mx-auto mb-3 sm:mb-4">
                    <i class="fas fa-search text-xl sm:text-2xl text-white"></i>
                </div>
                <h3 class="text-base sm:text-lg font-semibold mb-2">1. 本を探す</h3>
                <div class="space-y-1.5 sm:space-y-2 text-xs sm:text-sm text-gray-600">
                    <div class="flex items-center justify-center space-x-1.5 sm:space-x-2">
                        <i class="fas fa-keyboard text-blue-500 text-xs sm:text-sm"></i>
                        <span>キーワード検索</span>
                    </div>
                    <div class="flex items-center justify-center space-x-1.5 sm:space-x-2">
                        <i class="fas fa-barcode text-blue-500 text-xs sm:text-sm"></i>
                        <span>バーコード読み取り</span>
                    </div>
                </div>
            </div>
            
            <!-- ステップ2 -->
            <div class="text-center">
                <div class="w-14 h-14 sm:w-16 sm:h-16 bg-readnest-accent rounded-full flex items-center justify-center mx-auto mb-3 sm:mb-4">
                    <i class="fas fa-cog text-xl sm:text-2xl text-white"></i>
                </div>
                <h3 class="text-base sm:text-lg font-semibold mb-2">2. ステータス設定</h3>
                <p class="text-xs sm:text-sm text-gray-600">読書ステータス（読み中、読了済みなど）とページ数を設定</p>
            </div>
            
            <!-- ステップ3 -->
            <div class="text-center">
                <div class="w-14 h-14 sm:w-16 sm:h-16 bg-green-500 rounded-full flex items-center justify-center mx-auto mb-3 sm:mb-4">
                    <i class="fas fa-plus text-xl sm:text-2xl text-white"></i>
                </div>
                <h3 class="text-base sm:text-lg font-semibold mb-2">3. 本棚に追加</h3>
                <p class="text-xs sm:text-sm text-gray-600">「本棚に追加」ボタンをクリックして、あなたの本棚に保存</p>
            </div>
        </div>
        
        <!-- 検索のコツ - レスポンシブ対応 -->
        <div class="mt-8 sm:mt-12 bg-blue-50 rounded-lg p-4 sm:p-6">
            <h3 class="text-base sm:text-lg font-semibold text-blue-900 mb-2 sm:mb-3">
                <i class="fas fa-lightbulb mr-1.5 sm:mr-2"></i>検索のコツ
            </h3>
            <ul class="space-y-2 text-xs sm:text-sm text-blue-800">
                <li class="flex items-start">
                    <i class="fas fa-barcode text-blue-500 mt-0.5 sm:mt-1 mr-1.5 sm:mr-2 flex-shrink-0 text-xs sm:text-sm"></i>
                    <span><strong>バーコード読み取り：</strong>本の裏面にあるISBNバーコードを読み取ると、正確な本の情報で検索できます</span>
                </li>
                <li class="flex items-start">
                    <i class="fas fa-search text-blue-500 mt-0.5 sm:mt-1 mr-1.5 sm:mr-2 flex-shrink-0 text-xs sm:text-sm"></i>
                    <span><strong>キーワード検索：</strong>正確なタイトルがわからない場合は、キーワードの一部でも検索できます</span>
                </li>
                <li class="flex items-start">
                    <i class="fas fa-user text-blue-500 mt-0.5 sm:mt-1 mr-1.5 sm:mr-2 flex-shrink-0 text-xs sm:text-sm"></i>
                    <span><strong>著者名検索：</strong>著者名での検索も可能です</span>
                </li>
                <li class="flex items-start">
                    <i class="fas fa-plus text-blue-500 mt-0.5 sm:mt-1 mr-1.5 sm:mr-2 flex-shrink-0 text-xs sm:text-sm"></i>
                    <span><strong>手動追加：</strong>検索で見つからない場合は「手動で本を追加」をご利用ください</span>
                </li>
            </ul>
        </div>
        
        <!-- バーコード読み取りの説明 - レスポンシブ対応 -->
        <div class="mt-4 sm:mt-6 bg-green-50 dark:bg-green-900/20 rounded-lg p-4 sm:p-6">
            <h3 class="text-base sm:text-lg font-semibold text-green-900 dark:text-green-400 mb-2 sm:mb-3">
                <i class="fas fa-barcode mr-1.5 sm:mr-2"></i>バーコード読み取りについて
            </h3>
            <div class="space-y-2 sm:space-y-3 text-xs sm:text-sm text-green-800">
                <p><strong>対応バーコード：</strong>ISBN-10、ISBN-13のバーコードに対応しています</p>
                <p><strong>カメラ権限：</strong>初回使用時にカメラの使用許可を求められます</p>
                <p><strong>読み取り位置：</strong>本の裏面にあるバーコードをカメラに向けてください</p>
                <p><strong>読み取り完了：</strong>バーコードが正常に読み取られると、自動的に検索が開始されます</p>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- 追加のスクリプト -->
<?php
ob_start();
?>
<!-- バーコードスキャナーライブラリ -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/quagga/0.12.1/quagga.min.js"></script>
<script src="https://unpkg.com/@zxing/library@latest/umd/index.min.js"></script>
<script src="js/camera-focus-helper.js?v=<?php echo date('YmdHis'); ?>"></script>
<script src="js/barcode-scanner.js?v=<?php echo date('YmdHis'); ?>"></script>

<script>
// バーコードスキャナー関連
let barcodeScanner = null;
let currentScanner = null;

// バーコードスキャナーモーダルの開閉
function toggleBarcodeScanner() {
    const modal = document.getElementById('barcodeModal');
    if (modal.classList.contains('hidden')) {
        modal.classList.remove('hidden');
        initBarcodeScanner();
    } else {
        closeBarcodeScanner();
    }
}

function closeBarcodeScanner() {
    const modal = document.getElementById('barcodeModal');
    modal.classList.add('hidden');
    
    if (currentScanner) {
        currentScanner.destroy();
        currentScanner = null;
    }
}

// バーコードスキャナーの初期化
async function initBarcodeScanner() {
    const video = document.getElementById('barcodeVideo');
    const statusDiv = document.getElementById('scanStatus');
    const errorDiv = document.getElementById('barcodeError');
    
    statusDiv.textContent = 'カメラを起動中...';
    errorDiv.classList.add('hidden');
    
    try {
        // 優先順位: Native BarcodeDetector → ZXing → QuaggaJS
        // Native が一番高速・高精度（Android Chrome / Edge）。iOS Safari は Native 非対応のため ZXing にフォールバック。
        if (typeof NativeBarcodeScanner !== 'undefined' && await NativeBarcodeScanner.isSupported()) {
            currentScanner = new NativeBarcodeScanner();
        } else if (typeof ZXing !== 'undefined') {
            currentScanner = new ZXingBarcodeScanner();
        } else if (typeof Quagga !== 'undefined') {
            currentScanner = new BarcodeScanner();
        } else {
            throw new Error('バーコードスキャナーライブラリが利用できません');
        }
        
        const success = await currentScanner.init(video, handleBarcodeResult);
        
        if (success) {
            // 自動的にスキャンを開始
            statusDiv.textContent = 'スキャン中...バーコードをカメラに向けてください';
            currentScanner.start();
        } else {
            throw new Error('スキャナーの初期化に失敗しました');
        }
    } catch (error) {
        console.error('バーコードスキャナーエラー:', error);
        errorDiv.textContent = error.message || 'カメラの起動に失敗しました';
        errorDiv.classList.remove('hidden');
        statusDiv.textContent = '';
    }
}

// バーコードスキャン開始（使用されていないが互換性のため残す）
function startBarcodeScanner() {
    if (currentScanner) {
        currentScanner.start();
        document.getElementById('scanStatus').textContent = 'スキャン中...バーコードをカメラに向けてください';
    }
}

// バーコードスキャン停止（使用されていないが互換性のため残す）
function stopBarcodeScanner() {
    if (currentScanner) {
        currentScanner.stop();
        document.getElementById('scanStatus').textContent = 'スキャンを停止しました';
    }
}

// バーコード読み取り結果処理
function handleBarcodeResult(result) {
    if (result.error) {
        const errorDiv = document.getElementById('barcodeError');
        errorDiv.textContent = result.message;
        errorDiv.classList.remove('hidden');
        return;
    }
    
    if (result.isISBN) {
        // スキャン成功
        document.getElementById('scanStatus').textContent = `ISBN: ${result.code} を読み取りました`;
        
        // 少し待ってから自動的に検索
        setTimeout(() => {
            closeBarcodeScanner();
            // ISBNで検索を実行
            window.location.href = `${window.location.pathname}?isbn=${encodeURIComponent(result.code)}`;
        }, 1500);
    }
}

// 検索フォームのエンターキー対応
document.addEventListener('DOMContentLoaded', function() {
    const keywordInput = document.getElementById('keyword');
    const searchForm = document.querySelector('form[method="get"]');
    const searchButton = document.getElementById('searchButton');
    const aiSearchCheckbox = document.getElementById('use-ai-search');
    
    // AI検索の初期化
    if (typeof AISearchEnhancer !== 'undefined') {
        new AISearchEnhancer();
    }
    
    if (keywordInput) {
        keywordInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const form = this.closest('form');
                if (form) {
                    // submitイベントを発火させるためにrequestSubmitを使用
                    if (form.requestSubmit) {
                        form.requestSubmit();
                    } else {
                        // 古いブラウザ用のフォールバック
                        const submitEvent = new Event('submit', { bubbles: true, cancelable: true });
                        form.dispatchEvent(submitEvent);
                        if (!submitEvent.defaultPrevented) {
                            form.submit();
                        }
                    }
                }
            }
        });
    }
    
    // フォーム送信時のAI検索進捗表示
    if (searchForm && aiSearchCheckbox) {
        searchForm.addEventListener('submit', function(e) {
            if (aiSearchCheckbox.checked && keywordInput && keywordInput.value.trim()) {
                // フォームの通常送信を許可しつつ、進捗表示を追加
                const progressModal = createSimpleProgressModal();
                document.body.appendChild(progressModal);
                
                // ボタンを無効化
                if (searchButton) {
                    searchButton.disabled = true;
                    searchButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>AI検索中...';
                }
            }
        });
    }
    
    // フォーム送信時のローディング表示
    const forms = document.querySelectorAll('form[method="post"]');
    forms.forEach(form => {
        form.addEventListener('submit', function() {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>追加中...';
            }
        });
    });
    
    // URLパラメータからISBNが渡された場合の処理
    const urlParams = new URLSearchParams(window.location.search);
    const isbn = urlParams.get('isbn');
    if (isbn) {
        // ISBNをキーワード入力欄に設定
        if (keywordInput) {
            keywordInput.value = isbn;
        }
        
        // 成功メッセージを表示
        showNotification('バーコードから本を検索しています...', 'info');
    }
});

// 通知表示機能
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg ${
        type === 'success' ? 'bg-green-500' : 
        type === 'error' ? 'bg-red-500' : 
        'bg-blue-500'
    } text-white`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// AI検索の進捗表示付き実行
async function performAISearchWithProgress() {
    const keywordInput = document.getElementById('keyword');
    const searchButton = document.getElementById('searchButton');
    const keyword = keywordInput.value.trim();
    
    if (!keyword) return;
    
    // 進捗モーダルを表示
    const progressModal = createProgressModal();
    document.body.appendChild(progressModal);
    
    // ボタンを無効化
    searchButton.disabled = true;
    searchButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>AI検索中...';
    
    try {
        // ステップ1: クエリ解析
        updateProgress(progressModal, 'analyzing', 'クエリを解析中...');
        await simulateDelay(500);
        
        // ステップ2: 意図検出
        updateProgress(progressModal, 'intent', '検索意図を検出中...');
        
        // AI検索APIを非同期で呼び出し
        const response = await fetch(`/api/ai_search_api.php?q=${encodeURIComponent(keyword)}&progress=1`);
        const data = await response.json();
        
        if (data.success) {
            // ステップ3: キーワード展開
            if (data.expanded_keywords && data.expanded_keywords.length > 0) {
                updateProgress(progressModal, 'keywords', 
                    `キーワードを展開中: ${data.expanded_keywords.slice(0, 3).join(', ')}...`);
                await simulateDelay(300);
            }
            
            // ステップ4: 検索実行
            updateProgress(progressModal, 'searching', '本を検索中...');
            await simulateDelay(200);
            
            // 通常のフォーム送信で結果画面へ遷移
            const form = document.querySelector('form[method="get"]');
            form.submit();
        } else {
            throw new Error(data.error || 'AI検索でエラーが発生しました');
        }
    } catch (error) {
        console.error('AI検索エラー:', error);
        showNotification('AI検索に失敗しました。通常検索を実行します。', 'error');
        
        // エラー時は通常検索にフォールバック
        setTimeout(() => {
            const form = document.querySelector('form[method="get"]');
            const aiCheckbox = document.getElementById('use-ai-search');
            aiCheckbox.checked = false;
            form.submit();
        }, 1500);
    } finally {
        // プログレスモーダルを削除
        if (progressModal && progressModal.parentNode) {
            progressModal.remove();
        }
    }
}

// 進捗モーダルを作成
function createProgressModal() {
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
    modal.innerHTML = `
        <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
            <h3 class="text-lg font-semibold mb-4 text-center">
                <i class="fas fa-magic text-readnest-primary mr-2"></i>AI検索処理中
            </h3>
            <div class="space-y-3">
                <div id="progress-analyzing" class="flex items-center opacity-50">
                    <i class="fas fa-circle-notch fa-spin mr-3 text-gray-400"></i>
                    <span class="text-gray-600">クエリを解析中...</span>
                </div>
                <div id="progress-intent" class="flex items-center opacity-50">
                    <i class="fas fa-circle-notch fa-spin mr-3 text-gray-400"></i>
                    <span class="text-gray-600">検索意図を検出中...</span>
                </div>
                <div id="progress-keywords" class="flex items-center opacity-50">
                    <i class="fas fa-circle-notch fa-spin mr-3 text-gray-400"></i>
                    <span class="text-gray-600">キーワードを展開中...</span>
                </div>
                <div id="progress-searching" class="flex items-center opacity-50">
                    <i class="fas fa-circle-notch fa-spin mr-3 text-gray-400"></i>
                    <span class="text-gray-600">本を検索中...</span>
                </div>
            </div>
            <p class="text-xs text-gray-500 mt-4 text-center">AIが最適な本を探しています...</p>
        </div>
    `;
    return modal;
}

// 進捗を更新
function updateProgress(modal, step, message) {
    const steps = ['analyzing', 'intent', 'keywords', 'searching'];
    const currentIndex = steps.indexOf(step);
    
    steps.forEach((s, index) => {
        const element = modal.querySelector(`#progress-${s}`);
        if (element) {
            if (index < currentIndex) {
                // 完了したステップ
                element.classList.remove('opacity-50');
                element.querySelector('i').className = 'fas fa-check-circle mr-3 text-green-500';
            } else if (index === currentIndex) {
                // 現在のステップ
                element.classList.remove('opacity-50');
                element.querySelector('i').className = 'fas fa-circle-notch fa-spin mr-3 text-readnest-primary';
                if (message) {
                    element.querySelector('span').textContent = message;
                }
            }
        }
    });
}

// 遅延をシミュレート
function simulateDelay(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

// シンプルな進捗モーダルを作成
function createSimpleProgressModal() {
    // CSSアニメーションを追加
    if (!document.getElementById('ai-search-progress-styles')) {
        const style = document.createElement('style');
        style.id = 'ai-search-progress-styles';
        style.innerHTML = `
            @keyframes progress-bar {
                0% { width: 0%; }
                50% { width: 70%; }
                100% { width: 100%; }
            }
            .animate-progress-bar {
                animation: progress-bar 3s ease-in-out infinite;
            }
        `;
        document.head.appendChild(style);
    }
    
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
    modal.innerHTML = `
        <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
            <div class="flex flex-col items-center">
                <i class="fas fa-magic text-4xl text-readnest-primary mb-4 animate-pulse"></i>
                <h3 class="text-lg font-semibold mb-2">AI検索処理中...</h3>
                <p class="text-sm text-gray-600 text-center mb-4">
                    AIがあなたのリクエストを理解し、<br>
                    最適な本を探しています
                </p>
                <div class="w-full bg-gray-200 rounded-full h-2 mb-4">
                    <div class="bg-readnest-primary h-2 rounded-full animate-progress-bar"></div>
                </div>
                <p class="text-xs text-gray-500">しばらくお待ちください...</p>
            </div>
        </div>
    `;
    return modal;
}
</script>
<?php
$d_additional_scripts = ob_get_clean();

$d_content = ob_get_clean();

// ベーステンプレートを使用
include(__DIR__ . '/t_base.php');
?>