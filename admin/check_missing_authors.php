<?php
/**
 * 著者情報の欠落状況を確認するスクリプト
 */

require_once(dirname(__DIR__) . '/modern_config.php');
require_once('admin_auth.php');
requireAdmin();

// 統計を取得
$sql = "
    SELECT 
        COUNT(*) as total_books,
        SUM(CASE 
            WHEN bl.author IS NOT NULL AND bl.author != '' 
            THEN 1 ELSE 0 
        END) as has_author_in_list,
        SUM(CASE 
            WHEN br.author IS NOT NULL AND br.author != '' 
            THEN 1 ELSE 0 
        END) as has_author_in_repo,
        SUM(CASE 
            WHEN (bl.author IS NULL OR bl.author = '') 
            AND (br.author IS NULL OR br.author = '' OR br.author IS NULL) 
            THEN 1 ELSE 0 
        END) as missing_both,
        SUM(CASE 
            WHEN bl.isbn IS NOT NULL AND bl.isbn != '' 
            AND (bl.author IS NULL OR bl.author = '') 
            AND (br.author IS NULL OR br.author = '' OR br.author IS NULL)
            THEN 1 ELSE 0 
        END) as missing_both_with_isbn,
        SUM(CASE 
            WHEN bl.amazon_id IS NOT NULL AND bl.amazon_id != '' 
            AND (bl.author IS NULL OR bl.author = '') 
            AND (br.author IS NULL OR br.author = '' OR br.author IS NULL)
            THEN 1 ELSE 0 
        END) as missing_both_with_asin
    FROM b_book_list bl
    LEFT JOIN b_book_repository br ON bl.amazon_id = br.asin
";

$stats = $g_db->getRow($sql, null, DB_FETCHMODE_ASSOC);

// サンプルデータを取得
$sample_sql = "
    SELECT 
        bl.book_id,
        bl.name as title,
        bl.isbn,
        bl.amazon_id,
        bl.author as list_author,
        br.author as repo_author
    FROM b_book_list bl
    LEFT JOIN b_book_repository br ON bl.amazon_id = br.asin
    WHERE (bl.author IS NULL OR bl.author = '') 
    AND (br.author IS NULL OR br.author = '' OR br.author IS NULL)
    ORDER BY 
        CASE WHEN bl.isbn IS NOT NULL THEN 0 ELSE 1 END,
        bl.book_id DESC
    LIMIT 10
";

$samples = $g_db->getAll($sample_sql, null, DB_FETCHMODE_ASSOC);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>著者情報欠落状況確認</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-6xl mx-auto">
        <h1 class="text-3xl font-bold mb-6">著者情報欠落状況</h1>
        
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">統計情報</h2>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                <div class="p-4 bg-gray-50 rounded">
                    <div class="text-2xl font-bold"><?php echo number_format($stats['total_books']); ?></div>
                    <div class="text-sm text-gray-600">総書籍数</div>
                </div>
                <div class="p-4 bg-green-50 rounded">
                    <div class="text-2xl font-bold text-green-600">
                        <?php echo number_format($stats['has_author_in_list']); ?>
                    </div>
                    <div class="text-sm text-gray-600">list著者あり</div>
                </div>
                <div class="p-4 bg-blue-50 rounded">
                    <div class="text-2xl font-bold text-blue-600">
                        <?php echo number_format($stats['has_author_in_repo']); ?>
                    </div>
                    <div class="text-sm text-gray-600">repo著者あり</div>
                </div>
                <div class="p-4 bg-red-50 rounded">
                    <div class="text-2xl font-bold text-red-600">
                        <?php echo number_format($stats['missing_both']); ?>
                    </div>
                    <div class="text-sm text-gray-600">両方著者なし</div>
                </div>
                <div class="p-4 bg-orange-50 rounded">
                    <div class="text-2xl font-bold text-orange-600">
                        <?php echo number_format($stats['missing_both_with_isbn']); ?>
                    </div>
                    <div class="text-sm text-gray-600">著者なし（ISBN有）</div>
                </div>
                <div class="p-4 bg-purple-50 rounded">
                    <div class="text-2xl font-bold text-purple-600">
                        <?php echo number_format($stats['missing_both_with_asin']); ?>
                    </div>
                    <div class="text-sm text-gray-600">著者なし（ASIN有）</div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">Google Books API処理の見込み</h2>
            <div class="space-y-2">
                <p class="text-gray-700">
                    <strong>ISBNがある本:</strong> 
                    <span class="text-orange-600 font-bold"><?php echo number_format($stats['missing_both_with_isbn']); ?>冊</span>
                    - Google Books APIで高確率で著者情報を取得可能
                </p>
                <p class="text-gray-700">
                    <strong>ASINのみの本:</strong> 
                    <span class="text-purple-600 font-bold"><?php echo number_format($stats['missing_both_with_asin'] - $stats['missing_both_with_isbn']); ?>冊</span>
                    - タイトルで検索（精度は落ちる）
                </p>
                <p class="text-gray-700">
                    <strong>どちらもない本:</strong> 
                    <span class="text-red-600 font-bold"><?php echo number_format($stats['missing_both'] - $stats['missing_both_with_asin']); ?>冊</span>
                    - タイトルのみで検索（最も精度が低い）
                </p>
            </div>
            
            <?php if ($stats['missing_both'] > 0): ?>
            <div class="mt-4 p-4 bg-yellow-50 border-l-4 border-yellow-400">
                <p class="text-sm text-yellow-800">
                    <strong>Google Books API制限:</strong><br>
                    • 1日あたり1,000リクエストまで<br>
                    • 1分あたり10リクエストまで<br>
                    • 全<?php echo number_format($stats['missing_both']); ?>冊の処理には最低<?php echo ceil($stats['missing_both'] / 1000); ?>日必要
                </p>
            </div>
            <?php endif; ?>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4">サンプル（著者情報なしの本）</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">タイトル</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">ISBN</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">ASIN</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($samples as $book): ?>
                        <tr>
                            <td class="px-4 py-2 text-sm"><?php echo $book['book_id']; ?></td>
                            <td class="px-4 py-2 text-sm"><?php echo htmlspecialchars($book['title']); ?></td>
                            <td class="px-4 py-2 text-sm">
                                <?php if ($book['isbn']): ?>
                                    <span class="text-green-600"><?php echo htmlspecialchars($book['isbn']); ?></span>
                                <?php else: ?>
                                    <span class="text-gray-400">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-2 text-sm">
                                <?php if ($book['amazon_id']): ?>
                                    <span class="text-blue-600"><?php echo htmlspecialchars($book['amazon_id']); ?></span>
                                <?php else: ?>
                                    <span class="text-gray-400">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-6 flex gap-4">
            <a href="/admin/missing_authors.php" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                手動編集画面へ
            </a>
            <a href="/admin/sync_authors_progress.php" class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700">
                進捗確認画面へ
            </a>
        </div>
    </div>
</body>
</html>