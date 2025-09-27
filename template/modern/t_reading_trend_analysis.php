<?php
if(!defined('CONFIG')) {
    error_log('direct access detected...');
    die('reference for this file is not allowed.');
}

// ヘルパー関数を読み込み
require_once(dirname(dirname(__DIR__)) . '/library/form_helpers.php');

// メインコンテンツを生成
ob_start();
?>

<div class="container mx-auto px-4 py-8 max-w-7xl">
    <h1 class="text-3xl font-bold mb-8 text-gray-800">📊 読書傾向分析</h1>
    
    <!-- 基本統計 -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-sm text-gray-600 mb-2">総読書数</div>
            <div class="text-3xl font-bold text-blue-600">
                <?php echo number_format((int)($summary['stats']['total_books'] ?? 0)); ?>
            </div>
            <div class="text-xs text-gray-500 mt-1">冊</div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-sm text-gray-600 mb-2">読了済み</div>
            <div class="text-3xl font-bold text-green-600">
                <?php echo number_format((int)($summary['stats']['finished_books'] ?? 0)); ?>
            </div>
            <div class="text-xs text-gray-500 mt-1">冊</div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-sm text-gray-600 mb-2">平均評価</div>
            <div class="text-3xl font-bold text-yellow-600">
                <?php echo number_format((float)($summary['stats']['avg_rating'] ?? 0), 1); ?>
            </div>
            <div class="text-xs text-gray-500 mt-1">/ 5.0</div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-sm text-gray-600 mb-2">多様性スコア</div>
            <div class="text-3xl font-bold text-purple-600">
                <?php echo number_format($diversityScore, 1); ?>
            </div>
            <div class="text-xs text-gray-500 mt-1">/ 100</div>
        </div>
    </div>
    
    <!-- ジャンル分布 -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">
        <h2 class="text-xl font-semibold mb-4">📚 ジャンル分布</h2>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div>
                <canvas id="genreChart"></canvas>
            </div>
            <div class="space-y-2">
                <?php foreach ($summary['genres'] as $genre): ?>
                <div class="flex items-center justify-between py-2 border-b">
                    <div class="flex items-center">
                        <div class="w-4 h-4 rounded mr-3" style="background-color: <?php echo getGenreColor($genre['genre']); ?>"></div>
                        <span class="text-sm"><?php echo html($genre['genre']); ?></span>
                    </div>
                    <div class="text-sm text-gray-600">
                        <span class="font-semibold"><?php echo number_format((int)$genre['count']); ?></span> 冊
                        <?php if($genre['avg_rating'] > 0): ?>
                        <span class="text-xs text-gray-500">(★<?php echo number_format((float)$genre['avg_rating'], 1); ?>)</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <!-- 読書ペース -->
    <?php if (!empty($summary['reading_pace']['monthly_data'])): ?>
    <div class="bg-white rounded-lg shadow p-6 mb-8">
        <h2 class="text-xl font-semibold mb-4">📈 読書ペース（過去12ヶ月）</h2>
        <div class="mb-4">
            <div class="flex items-center gap-4">
                <div class="text-sm text-gray-600">
                    月平均: <span class="font-semibold text-lg"><?php echo number_format($summary['reading_pace']['avg_per_month'], 1); ?></span> 冊
                </div>
                <div class="text-sm text-gray-600">
                    年間合計: <span class="font-semibold text-lg"><?php echo number_format($summary['reading_pace']['total_last_year']); ?></span> 冊
                </div>
            </div>
        </div>
        <canvas id="paceChart"></canvas>
    </div>
    <?php endif; ?>
    
    <!-- レビュー特性 -->
    <?php if (!empty($summary['review_characteristics'])): ?>
    <div class="bg-white rounded-lg shadow p-6 mb-8">
        <h2 class="text-xl font-semibold mb-4">✍️ レビュー特性</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="text-center">
                <div class="text-3xl font-bold text-indigo-600">
                    <?php echo number_format((int)($summary['review_characteristics']['total_reviews'] ?? 0)); ?>
                </div>
                <div class="text-sm text-gray-600">総レビュー数</div>
            </div>
            <div class="text-center">
                <div class="text-3xl font-bold text-indigo-600">
                    <?php echo number_format((int)($summary['review_characteristics']['avg_review_length'] ?? 0)); ?>
                </div>
                <div class="text-sm text-gray-600">平均文字数</div>
            </div>
            <div class="text-center">
                <div class="text-3xl font-bold text-indigo-600">
                    <?php echo number_format((int)($summary['review_characteristics']['long_reviews'] ?? 0)); ?>
                </div>
                <div class="text-sm text-gray-600">詳細レビュー (500文字以上)</div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- レビュークラスタ（AI自動分類） -->
    <?php if (!empty($clusters)): ?>
    <div class="bg-white rounded-lg shadow p-6 mb-8">
        <h2 class="text-xl font-semibold mb-4">🤖 AI による読書傾向の自動分類</h2>
        <p class="text-sm text-gray-600 mb-4">レビューの内容をAIが分析し、似た傾向の本を自動的にグループ化しました</p>
        <div class="space-y-4">
            <?php foreach ($clusters as $cluster): ?>
            <div class="border rounded-lg p-4 hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-semibold text-lg">
                        <span class="inline-block w-8 h-8 rounded-full bg-gradient-to-r from-purple-500 to-pink-500 text-white text-center leading-8 mr-2"><?php echo $cluster['id'] + 1; ?></span>
                        <?php echo html($cluster['name']); ?>
                    </h3>
                    <div class="text-right">
                        <span class="text-sm text-gray-600">
                            <?php echo $cluster['size']; ?>冊
                        </span>
                        <div class="text-xs text-yellow-600">
                            ★<?php echo number_format($cluster['avg_rating'], 1); ?>
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($cluster['keywords'])): ?>
                <div class="mb-3">
                    <div class="flex flex-wrap gap-1">
                        <?php foreach ($cluster['keywords'] as $keyword): ?>
                        <span class="inline-block px-2 py-1 text-xs bg-purple-100 text-purple-700 rounded">
                            <?php echo html($keyword); ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
                    <?php foreach (array_slice($cluster['books'], 0, 3) as $book): ?>
                    <div class="text-sm bg-gray-50 rounded p-2">
                        <div class="font-medium truncate" title="<?php echo html($book['title']); ?>">
                            <?php echo html($book['title']); ?>
                        </div>
                        <div class="text-xs text-gray-600 truncate"><?php echo html($book['author']); ?></div>
                        <?php if($book['rating'] > 0): ?>
                        <div class="text-xs text-yellow-600">★<?php echo number_format($book['rating'], 1); ?></div>
                        <?php endif; ?>
                        <?php if(!empty($book['review_snippet'])): ?>
                        <div class="text-xs text-gray-500 mt-1 line-clamp-2">
                            <?php echo html(mb_substr($book['review_snippet'], 0, 50)) . '...'; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                    <?php if($cluster['size'] > 3): ?>
                    <div class="text-sm bg-gray-50 rounded p-2 flex items-center justify-center text-gray-500">
                        他<?php echo $cluster['size'] - 3; ?>冊
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($cluster['characteristics'])): ?>
                <div class="mt-2 pt-2 border-t text-xs text-gray-500">
                    平均レビュー文字数: <?php echo number_format($cluster['characteristics']['review_length_avg']); ?>文字
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- 類似読者 -->
    <?php if (!empty($similarReaders)): ?>
    <div class="bg-white rounded-lg shadow p-6 mb-8">
        <h2 class="text-xl font-semibold mb-4">👥 あなたと似た読書傾向のユーザー</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($similarReaders as $reader): ?>
            <div class="border rounded-lg p-4">
                <div class="flex items-center mb-2">
                    <div class="w-10 h-10 bg-gray-300 rounded-full mr-3"></div>
                    <div>
                        <div class="font-semibold"><?php echo html($reader['nickname']); ?></div>
                        <div class="text-xs text-gray-600">
                            共通の本: <?php echo number_format($reader['common_books']); ?>冊
                        </div>
                    </div>
                </div>
                <div class="text-sm text-gray-600">
                    平均評価: ★<?php echo number_format((float)$reader['avg_rating'], 1); ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- 著者の多様性 -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">
        <h2 class="text-xl font-semibold mb-4">🌈 読書の多様性</h2>
        <div class="mb-4">
            <div class="flex justify-between items-center mb-2">
                <span class="text-sm font-medium">著者の多様性</span>
                <span class="text-sm text-gray-600">
                    <?php echo number_format((int)($summary['stats']['unique_authors'] ?? 0)); ?>人の著者
                </span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-3">
                <div class="bg-gradient-to-r from-blue-500 to-purple-600 h-3 rounded-full transition-all duration-500" 
                     style="width: <?php echo min(100, ($summary['stats']['unique_authors'] ?? 0) / max(1, ($summary['stats']['total_books'] ?? 1)) * 100); ?>%">
                </div>
            </div>
            <p class="text-xs text-gray-500 mt-1">
                <?php 
                $author_ratio = ($summary['stats']['total_books'] ?? 0) > 0 
                    ? ($summary['stats']['unique_authors'] ?? 0) / ($summary['stats']['total_books'] ?? 1) 
                    : 0;
                echo "1冊あたり " . number_format($author_ratio, 2) . " 人の著者";
                ?>
            </p>
        </div>
        
        <div class="mt-6 p-4 bg-gray-50 rounded-lg">
            <div class="text-sm text-gray-700">
                <p class="font-semibold mb-2">多様性スコア: <?php echo number_format($diversityScore, 1); ?>/100</p>
                <p class="text-xs">
                    <?php if ($diversityScore >= 80): ?>
                    素晴らしい多様性！幅広いジャンルと著者の本を楽しんでいます。
                    <?php elseif ($diversityScore >= 60): ?>
                    良好な多様性。さまざまな本を読んでいますが、新しいジャンルも探してみましょう。
                    <?php elseif ($diversityScore >= 40): ?>
                    一定の多様性があります。新しい著者やジャンルに挑戦してみませんか？
                    <?php else: ?>
                    特定のジャンルや著者に集中しています。時には新しい分野も探索してみましょう。
                    <?php endif; ?>
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// ジャンル分布グラフ
const genreCtx = document.getElementById('genreChart');
if (genreCtx) {
    const genreData = <?php echo json_encode($summary['genres']); ?>;
    new Chart(genreCtx, {
        type: 'doughnut',
        data: {
            labels: genreData.map(g => g.genre),
            datasets: [{
                data: genreData.map(g => g.count),
                backgroundColor: [
                    '#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6',
                    '#EC4899', '#14B8A6', '#F97316', '#6366F1', '#84CC16'
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}

// 読書ペースグラフ
const paceCtx = document.getElementById('paceChart');
if (paceCtx) {
    const paceData = <?php echo json_encode($summary['reading_pace']['monthly_data'] ?? []); ?>;
    
    // 月名を生成
    const monthNames = paceData.map(d => {
        const year = d.year;
        const month = String(d.month).padStart(2, '0');
        return `${year}/${month}`;
    }).reverse();
    
    new Chart(paceCtx, {
        type: 'line',
        data: {
            labels: monthNames,
            datasets: [{
                label: '読了冊数',
                data: paceData.map(d => d.books_read).reverse(),
                borderColor: '#3B82F6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
}
</script>

<?php
// ジャンルの色を取得する関数
function getGenreColor($genre) {
    $colors = [
        '#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6',
        '#EC4899', '#14B8A6', '#F97316', '#6366F1', '#84CC16'
    ];
    return $colors[abs(crc32($genre)) % count($colors)];
}

$d_content = ob_get_clean();

// ベーステンプレートを使用
include(__DIR__ . '/t_base.php');
?>