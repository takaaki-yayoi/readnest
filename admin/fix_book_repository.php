<?php
/**
 * b_book_repository修復ツール（管理画面版）
 * 欠落している本をb_book_repositoryに追加
 */

declare(strict_types=1);

// 設定を読み込み
require_once(dirname(__DIR__) . '/config.php');
require_once(dirname(__DIR__) . '/library/database.php');

// データベース接続を先に初期化
$g_db = DB_Connect();

require_once(__DIR__ . '/admin_auth.php');
require_once(__DIR__ . '/admin_helpers.php');

// 管理者認証チェック
if (!isAdmin()) {
    http_response_code(403);
    include('403.php');
    exit;
}

$page_title = 'b_book_repository修復ツール';

// 処理結果メッセージ
$message = '';
$message_type = '';

// AJAXリクエストの処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    $action = $_POST['action'];
    $response = ['success' => false, 'message' => ''];
    
    try {
        switch ($action) {
            case 'analyze':
                // 欠落している本を分析
                $analysis = analyzeMMissingBooks();
                $response['success'] = true;
                $response['data'] = $analysis;
                break;
                
            case 'fix':
                // 修復実行
                $limit = isset($_POST['limit']) ? (int)$_POST['limit'] : 100;
                $offset = isset($_POST['offset']) ? (int)$_POST['offset'] : 0;
                
                $result = fixMissingBooks($limit, $offset);
                $response['success'] = true;
                $response['data'] = $result;
                break;
                
            default:
                $response['message'] = '無効なアクションです';
        }
    } catch (Exception $e) {
        $response['success'] = false;
        $response['message'] = $e->getMessage();
        error_log('fix_book_repository error: ' . $e->getMessage());
    }
    
    echo json_encode($response);
    exit;
}

// 欠落している本を分析
function analyzeMMissingBooks() {
    global $g_db;
    
    // 総数を取得
    $total_sql = "
        SELECT COUNT(DISTINCT bl.amazon_id) as total
        FROM b_book_list bl
        LEFT JOIN b_book_repository br ON bl.amazon_id = br.asin
        WHERE br.asin IS NULL
        AND bl.amazon_id IS NOT NULL
        AND bl.amazon_id != ''
    ";
    
    $total = $g_db->getOne($total_sql);
    if (DB::isError($total)) {
        throw new Exception('分析エラー: ' . $total->getMessage());
    }
    
    // ASINタイプ別の統計
    $type_sql = "
        SELECT 
            CASE 
                WHEN amazon_id LIKE 'google-%' THEN 'Google Books'
                WHEN amazon_id LIKE 'rakuten-%' THEN '楽天ブックス'
                WHEN LENGTH(amazon_id) = 10 THEN 'Amazon ASIN'
                WHEN LENGTH(amazon_id) = 13 THEN 'ISBN-13'
                ELSE 'その他'
            END as asin_type,
            COUNT(DISTINCT amazon_id) as count
        FROM b_book_list bl
        LEFT JOIN b_book_repository br ON bl.amazon_id = br.asin
        WHERE br.asin IS NULL
        AND bl.amazon_id IS NOT NULL
        AND bl.amazon_id != ''
        GROUP BY asin_type
        ORDER BY count DESC
    ";
    
    $types = $g_db->getAll($type_sql, [], DB_FETCHMODE_ASSOC);
    if (DB::isError($types)) {
        throw new Exception('タイプ別統計エラー: ' . $types->getMessage());
    }
    
    // サンプルデータ（最新10件）
    $sample_sql = "
        SELECT 
            bl.amazon_id,
            bl.name as title,
            bl.author,
            COUNT(DISTINCT bl.user_id) as user_count,
            MIN(bl.create_date) as first_added
        FROM b_book_list bl
        LEFT JOIN b_book_repository br ON bl.amazon_id = br.asin
        WHERE br.asin IS NULL
        AND bl.amazon_id IS NOT NULL
        AND bl.amazon_id != ''
        GROUP BY bl.amazon_id, bl.name, bl.author
        ORDER BY user_count DESC
        LIMIT 10
    ";
    
    $samples = $g_db->getAll($sample_sql, [], DB_FETCHMODE_ASSOC);
    if (DB::isError($samples)) {
        throw new Exception('サンプル取得エラー: ' . $samples->getMessage());
    }
    
    return [
        'total' => $total,
        'types' => $types,
        'samples' => $samples
    ];
}

// 欠落している本を修復
function fixMissingBooks($limit = 100, $offset = 0) {
    global $g_db;
    
    // 修復対象を取得
    $books_sql = "
        SELECT DISTINCT
            bl.amazon_id,
            bl.name as title,
            bl.author,
            bl.image_url,
            COUNT(DISTINCT bl.user_id) as user_count
        FROM b_book_list bl
        LEFT JOIN b_book_repository br ON bl.amazon_id = br.asin
        WHERE br.asin IS NULL
        AND bl.amazon_id IS NOT NULL
        AND bl.amazon_id != ''
        GROUP BY bl.amazon_id, bl.name, bl.author, bl.image_url
        ORDER BY user_count DESC
        LIMIT ? OFFSET ?
    ";
    
    $books = $g_db->getAll($books_sql, [$limit, $offset], DB_FETCHMODE_ASSOC);
    if (DB::isError($books)) {
        throw new Exception('本の取得エラー: ' . $books->getMessage());
    }
    
    $stats = [
        'processed' => 0,
        'success' => 0,
        'skipped' => 0,
        'error' => 0,
        'details' => []
    ];
    
    foreach ($books as $book) {
        $stats['processed']++;
        
        // 必須フィールドのチェック
        if (empty($book['title']) && empty($book['author'])) {
            $stats['skipped']++;
            $stats['details'][] = [
                'asin' => $book['amazon_id'],
                'status' => 'skipped',
                'reason' => 'タイトルと著者が両方空'
            ];
            continue;
        }
        
        try {
            // b_book_repositoryに追加
            if (function_exists('addBookToRepository')) {
                addBookToRepository(
                    $book['amazon_id'],
                    $book['title'] ?? '',
                    $book['image_url'] ?? '',
                    $book['author'] ?? ''
                );
            } else {
                // 直接SQL実行
                $insert_sql = "INSERT INTO b_book_repository (asin, title, image_url, author) VALUES (?, ?, ?, ?)";
                $result = $g_db->query($insert_sql, [
                    $book['amazon_id'],
                    $book['title'] ?? '',
                    $book['image_url'] ?? '',
                    $book['author'] ?? ''
                ]);
                
                if (DB::isError($result)) {
                    throw new Exception($result->getMessage());
                }
            }
            
            $stats['success']++;
            $stats['details'][] = [
                'asin' => $book['amazon_id'],
                'title' => $book['title'],
                'status' => 'success'
            ];
            
        } catch (Exception $e) {
            $stats['error']++;
            $stats['details'][] = [
                'asin' => $book['amazon_id'],
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }
    
    return $stats;
}

include('layout/header.php');
?>

<div class="bg-white rounded-lg shadow p-6">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-900 mb-2">b_book_repository修復ツール</h2>
        <p class="text-gray-600">b_book_listに存在するがb_book_repositoryに存在しない本を修復します。</p>
    </div>
    
    <!-- 警告メッセージ -->
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-exclamation-triangle text-yellow-400 text-lg"></i>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-yellow-800">注意事項</h3>
                <div class="mt-2 text-sm text-yellow-700">
                    <ul class="list-disc list-inside space-y-1">
                        <li>このツールはb_book_repositoryテーブルにレコードを追加します</li>
                        <li>実行前に必ずデータベースのバックアップを取得してください</li>
                        <li>大量のデータがある場合は、バッチ処理で段階的に実行されます</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 分析セクション -->
    <div id="analysisSection">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">ステップ1: 欠落データの分析</h3>
        <button onclick="analyzeData()" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition-colors">
            <i class="fas fa-search mr-2"></i>分析開始
        </button>
        
        <div id="analysisResult" class="mt-6 hidden">
            <!-- 分析結果がここに表示される -->
        </div>
    </div>
    
    <!-- 修復セクション -->
    <div id="fixSection" class="hidden mt-8 pt-8 border-t">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">ステップ2: データ修復</h3>
        
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">
                一度に処理する件数
            </label>
            <select id="batchSize" class="border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                <option value="10">10件</option>
                <option value="50">50件</option>
                <option value="100" selected>100件</option>
                <option value="500">500件</option>
            </select>
        </div>
        
        <button onclick="startFix()" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition-colors">
            <i class="fas fa-wrench mr-2"></i>修復開始
        </button>
        
        <div id="fixProgress" class="mt-6 hidden">
            <!-- 進捗状況がここに表示される -->
        </div>
    </div>
</div>

<script>
let totalMissing = 0;
let fixedCount = 0;

// データ分析
async function analyzeData() {
    const button = event.target;
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>分析中...';
    
    try {
        const response = await fetch('fix_book_repository.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=analyze'
        });
        
        const result = await response.json();
        
        if (result.success) {
            showAnalysisResult(result.data);
        } else {
            alert('エラー: ' + result.message);
        }
    } catch (error) {
        alert('通信エラー: ' + error.message);
    } finally {
        button.disabled = false;
        button.innerHTML = '<i class="fas fa-search mr-2"></i>分析開始';
    }
}

// 分析結果を表示
function showAnalysisResult(data) {
    totalMissing = data.total;
    
    let html = `
        <div class="bg-gray-50 rounded-lg p-6">
            <h4 class="text-lg font-semibold text-gray-900 mb-4">分析結果</h4>
            
            <div class="mb-6">
                <p class="text-2xl font-bold text-red-600">${data.total.toLocaleString()}件</p>
                <p class="text-sm text-gray-600">の本がb_book_repositoryに存在しません</p>
            </div>
            
            <div class="mb-6">
                <h5 class="font-medium text-gray-900 mb-2">ASINタイプ別内訳</h5>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">タイプ</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">件数</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
    `;
    
    data.types.forEach(type => {
        html += `
            <tr>
                <td class="px-4 py-2 text-sm text-gray-900">${type.asin_type}</td>
                <td class="px-4 py-2 text-sm text-gray-900 text-right">${type.count.toLocaleString()}</td>
            </tr>
        `;
    });
    
    html += `
                    </tbody>
                </table>
            </div>
            
            <div>
                <h5 class="font-medium text-gray-900 mb-2">サンプル（利用者数の多い順）</h5>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">ASIN</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">タイトル</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">著者</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">利用者数</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
    `;
    
    data.samples.forEach(book => {
        html += `
            <tr>
                <td class="px-4 py-2 text-xs font-mono">${book.amazon_id}</td>
                <td class="px-4 py-2">${book.title || '(なし)'}</td>
                <td class="px-4 py-2">${book.author || '(なし)'}</td>
                <td class="px-4 py-2 text-right">${book.user_count}</td>
            </tr>
        `;
    });
    
    html += `
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    `;
    
    document.getElementById('analysisResult').innerHTML = html;
    document.getElementById('analysisResult').classList.remove('hidden');
    document.getElementById('fixSection').classList.remove('hidden');
}

// 修復開始
async function startFix() {
    if (!confirm(`${totalMissing}件の本を修復します。よろしいですか？`)) {
        return;
    }
    
    const button = event.target;
    button.disabled = true;
    
    const batchSize = parseInt(document.getElementById('batchSize').value);
    fixedCount = 0;
    
    // 進捗表示を初期化
    const progressHtml = `
        <div class="bg-blue-50 rounded-lg p-6">
            <h4 class="text-lg font-semibold text-gray-900 mb-4">修復進捗</h4>
            <div class="mb-4">
                <div class="flex justify-between text-sm text-gray-600 mb-1">
                    <span>進捗状況</span>
                    <span id="progressText">0 / ${totalMissing}</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2.5">
                    <div id="progressBar" class="bg-blue-600 h-2.5 rounded-full" style="width: 0%"></div>
                </div>
            </div>
            <div id="fixLog" class="mt-4 max-h-64 overflow-y-auto bg-white rounded border p-4 text-sm font-mono">
                修復を開始します...<br>
            </div>
        </div>
    `;
    
    document.getElementById('fixProgress').innerHTML = progressHtml;
    document.getElementById('fixProgress').classList.remove('hidden');
    
    // バッチ処理
    await processBatches(batchSize);
    
    button.disabled = false;
}

// バッチ処理
async function processBatches(batchSize) {
    const log = document.getElementById('fixLog');
    
    while (fixedCount < totalMissing) {
        try {
            const response = await fetch('fix_book_repository.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=fix&limit=${batchSize}&offset=${fixedCount}`
            });
            
            const result = await response.json();
            
            if (result.success) {
                const stats = result.data;
                fixedCount += stats.processed;
                
                // 進捗更新
                const progress = Math.round((fixedCount / totalMissing) * 100);
                document.getElementById('progressBar').style.width = progress + '%';
                document.getElementById('progressText').textContent = `${fixedCount} / ${totalMissing}`;
                
                // ログ追加
                log.innerHTML += `バッチ処理完了: 処理${stats.processed}件, 成功${stats.success}件, スキップ${stats.skipped}件, エラー${stats.error}件<br>`;
                
                // エラーがある場合は詳細を表示
                if (stats.error > 0) {
                    stats.details.filter(d => d.status === 'error').forEach(detail => {
                        log.innerHTML += `<span class="text-red-600">エラー: ${detail.asin} - ${detail.error}</span><br>`;
                    });
                }
                
                log.scrollTop = log.scrollHeight;
                
                // 処理完了チェック
                if (stats.processed === 0) {
                    break;
                }
                
                // 次のバッチまで少し待機
                await new Promise(resolve => setTimeout(resolve, 500));
                
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            log.innerHTML += `<span class="text-red-600">エラー: ${error.message}</span><br>`;
            log.scrollTop = log.scrollHeight;
            break;
        }
    }
    
    // 完了メッセージ
    log.innerHTML += `<br><strong class="text-green-600">修復処理が完了しました！</strong><br>`;
    log.scrollTop = log.scrollHeight;
    
    // 再分析ボタンを表示
    log.innerHTML += `<button onclick="location.reload()" class="mt-4 bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700 transition-colors text-xs">
        <i class="fas fa-redo mr-2"></i>ページを更新して再分析
    </button>`;
}
</script>

<?php include('layout/footer.php'); ?>