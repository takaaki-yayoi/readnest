<?php
if(!defined('CONFIG')) {
    error_log('direct access detected...');
    die('reference for this file is not allowed.');
}

// CSRFトークン生成
generateCSRFToken();

// コンテンツ部分を生成
ob_start();
?>

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
                    <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                    <a href="/bookshelf.php" class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300">本棚</a>
                </li>
                <li class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                    <a href="/book_detail.php?book_id=<?php echo $book_id; ?>" class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300">
                        <?php echo html($book['title']); ?>
                    </a>
                </li>
                <li class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                    <span class="text-gray-700 dark:text-gray-300">読書履歴編集</span>
                </li>
            </ol>
        </nav>

        <!-- ヘッダー -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mb-6">
            <div class="flex items-start space-x-4">
                <?php if (!empty($book['image_url'])): ?>
                <img src="<?php echo html($book['image_url']); ?>" 
                     alt="<?php echo html($book['title']); ?>"
                     class="w-20 h-28 object-cover rounded shadow-sm">
                <?php else: ?>
                <div class="w-20 h-28 bg-gray-200 dark:bg-gray-700 rounded flex items-center justify-center">
                    <i class="fas fa-book text-gray-400 text-2xl"></i>
                </div>
                <?php endif; ?>
                
                <div class="flex-1">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-1">
                        <?php echo html($book['title']); ?>
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400 mb-2"><?php echo html($book['author']); ?></p>
                    <div class="flex items-center space-x-4 text-sm text-gray-500 dark:text-gray-400">
                        <span>
                            <i class="fas fa-book-open mr-1"></i>
                            総ページ数: <?php echo $book['total_page'] ?: '未設定'; ?>
                        </span>
                        <span>
                            <i class="fas fa-bookmark mr-1"></i>
                            現在: <?php echo $book['current_page'] ?: 0; ?>ページ
                        </span>
                        <?php if ($book['total_page'] > 0): ?>
                        <span>
                            <i class="fas fa-percentage mr-1"></i>
                            進捗: <?php echo round(($book['current_page'] / $book['total_page']) * 100, 1); ?>%
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- 新規進捗追加フォーム -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center">
                <i class="fas fa-plus-circle text-green-600 mr-2"></i>
                新しい進捗を追加
            </h2>
            
            <form id="add-progress-form" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?php echo html($_SESSION['csrf_token']); ?>">
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            日付と時刻 <span class="text-red-500">*</span>
                        </label>
                        <div class="flex gap-2">
                            <input type="date"
                                   name="event_date"
                                   value="<?php echo date('Y-m-d'); ?>"
                                   max="<?php echo date('Y-m-d'); ?>"
                                   required
                                   class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <input type="time"
                                   name="event_time"
                                   value="<?php echo date('H:i'); ?>"
                                   required
                                   class="px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            ページ数 <span class="text-red-500">*</span>
                        </label>
                        <input type="number"
                               name="page_number"
                               min="1"
                               max="<?php echo $book['total_page'] ?: 9999; ?>"
                               required
                               placeholder="読んだページ数"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            メモ（任意）
                        </label>
                        <input type="text"
                               name="memo"
                               placeholder="読書メモ"
                               maxlength="255"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                
                <div class="flex justify-end">
                    <button type="submit" 
                            class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                        <i class="fas fa-plus mr-2"></i>進捗を追加
                    </button>
                </div>
            </form>
        </div>

        <!-- 読書履歴一覧 -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center justify-between">
                <span>
                    <i class="fas fa-history text-blue-600 mr-2"></i>
                    読書履歴
                </span>
                <span class="text-sm text-gray-500 dark:text-gray-400">
                    全<?php echo count($events); ?>件
                </span>
            </h2>
            
            <?php if (empty($events)): ?>
            <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                <i class="fas fa-book-reader text-4xl mb-2"></i>
                <p>まだ読書履歴がありません</p>
                <p class="text-sm mt-1">上のフォームから進捗を追加してください</p>
            </div>
            <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($events as $event): ?>
                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                     data-event-id="<?php echo $event['event_id']; ?>">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center space-x-4 mb-2">
                                <span class="font-medium text-gray-900 dark:text-gray-100">
                                    <?php echo date('Y年m月d日 H:i', strtotime($event['event_date'])); ?>
                                </span>
                                <span class="px-2 py-1 bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-200 rounded text-sm">
                                    <?php echo $event['page']; ?>ページ
                                </span>
                                <?php
                                // 進捗率計算
                                if ($book['total_page'] > 0) {
                                    $progress = round(($event['page'] / $book['total_page']) * 100, 1);
                                    echo '<span class="text-sm text-gray-500 dark:text-gray-400">' . $progress . '%</span>';
                                }
                                ?>
                            </div>
                            
                            <?php if (!empty($event['memo'])): ?>
                            <p class="text-gray-600 dark:text-gray-400 text-sm">
                                <i class="fas fa-comment-dots text-gray-400 mr-1"></i>
                                <?php echo html($event['memo']); ?>
                            </p>
                            <?php endif; ?>

                            <div class="text-xs text-gray-400 mt-2">
                                記録日時: <?php echo date('Y/m/d H:i', strtotime($event['created_at'])); ?>
                            </div>
                        </div>
                        
                        <div class="flex items-center space-x-2 ml-4">
                            <button onclick="editProgress(<?php echo $event['event_id']; ?>)" 
                                    class="text-blue-600 hover:text-blue-800 p-2"
                                    title="編集">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="deleteProgress(<?php echo $event['event_id']; ?>)" 
                                    class="text-red-600 hover:text-red-800 p-2"
                                    title="削除">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- 編集フォーム（初期は非表示） -->
                    <div id="edit-form-<?php echo $event['event_id']; ?>" class="hidden mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <form onsubmit="updateProgress(event, <?php echo $event['event_id']; ?>); return false;" 
                              class="space-y-3">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">日付と時刻</label>
                                    <div class="flex gap-2">
                                        <input type="date"
                                               name="event_date"
                                               value="<?php echo date('Y-m-d', strtotime($event['event_date'])); ?>"
                                               max="<?php echo date('Y-m-d'); ?>"
                                               required
                                               class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <input type="time"
                                               name="event_time"
                                               value="<?php echo date('H:i', strtotime($event['event_date'])); ?>"
                                               required
                                               class="px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    </div>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ページ数</label>
                                    <input type="number"
                                           name="page_number"
                                           value="<?php echo $event['page']; ?>"
                                           min="1"
                                           max="<?php echo $book['total_page'] ?: 9999; ?>"
                                           required
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">メモ</label>
                                    <input type="text"
                                           name="memo"
                                           value="<?php echo html($event['memo']); ?>"
                                           maxlength="255"
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                            </div>
                            
                            <div class="flex justify-end space-x-2">
                                <button type="button"
                                        onclick="cancelEdit(<?php echo $event['event_id']; ?>)"
                                        class="px-3 py-1 text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">
                                    キャンセル
                                </button>
                                <button type="submit" 
                                        class="px-4 py-1 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                    更新
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- 注意事項 -->
        <div class="mt-6 p-4 bg-amber-50 dark:bg-amber-900 border border-amber-200 dark:border-amber-700 rounded-lg">
            <h3 class="font-semibold text-amber-800 dark:text-amber-200 mb-2">
                <i class="fas fa-info-circle mr-1"></i>
                注意事項
            </h3>
            <ul class="text-sm text-amber-700 dark:text-amber-300 space-y-1">
                <li>• 読書履歴の修正や追加を行っても、本の「更新日」は変更されません</li>
                <li>• 過去の日付で進捗を追加すると、読書カレンダーと連続記録に反映されます</li>
                <li>• 最新の進捗日のページ数が「現在のページ数」として表示されます</li>
                <li>• 削除した進捗は復元できません</li>
            </ul>
        </div>
    </div>
</div>

<script>
// CSRFトークン
const csrfToken = '<?php echo html($_SESSION['csrf_token']); ?>';

// 新規進捗追加
document.getElementById('add-progress-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'add_progress');
    
    // 時刻が未入力の場合はデフォルト値を設定
    if (!formData.get('event_time')) {
        formData.set('event_time', '12:00');
    }
    
    try {
        const response = await fetch('', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert(result.message);
            location.reload();
        } else {
            alert(result.error || 'エラーが発生しました');
        }
    } catch (error) {
        alert('通信エラーが発生しました');
    }
});

// 進捗編集
function editProgress(eventId) {
    // 他の編集フォームを閉じる
    document.querySelectorAll('[id^="edit-form-"]').forEach(form => {
        form.classList.add('hidden');
    });
    
    // 対象の編集フォームを表示
    document.getElementById('edit-form-' + eventId).classList.remove('hidden');
}

// 編集キャンセル
function cancelEdit(eventId) {
    document.getElementById('edit-form-' + eventId).classList.add('hidden');
}

// 進捗更新
async function updateProgress(e, eventId) {
    e.preventDefault();
    
    const form = e.target;
    const formData = new FormData(form);
    formData.append('action', 'update_progress');
    formData.append('event_id', eventId);
    formData.append('csrf_token', csrfToken);
    
    // 時刻が未入力の場合はデフォルト値を設定
    if (!formData.get('event_time')) {
        formData.set('event_time', '12:00');
    }
    
    try {
        const response = await fetch('', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert(result.message);
            location.reload();
        } else {
            alert(result.error || 'エラーが発生しました');
        }
    } catch (error) {
        alert('通信エラーが発生しました');
    }
}

// 進捗削除
async function deleteProgress(eventId) {
    if (!confirm('この進捗を削除してもよろしいですか？\n削除した進捗は復元できません。')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'delete_progress');
    formData.append('event_id', eventId);
    formData.append('csrf_token', csrfToken);
    
    try {
        const response = await fetch('', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert(result.message);
            location.reload();
        } else {
            alert(result.error || 'エラーが発生しました');
        }
    } catch (error) {
        alert('通信エラーが発生しました');
    }
}
</script>

<?php
$d_content = ob_get_clean();

// ベーステンプレートを使用
include(__DIR__ . '/t_base.php');
?>