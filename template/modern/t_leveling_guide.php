<?php
/**
 * レベリングシステム説明ページテンプレート
 */

// コンテンツ部分を生成
ob_start();
?>

<!-- ヒーローセクション -->
<section class="bg-gradient-to-br from-yellow-400 to-orange-500 text-white py-12 mb-8">
    <div class="container mx-auto px-4">
        <div class="max-w-4xl mx-auto text-center">
            <h1 class="text-4xl font-bold mb-4">
                <i class="fas fa-trophy mr-3"></i>レベリングシステム
            </h1>
            <p class="text-xl opacity-90">
                読書の成果を可視化し、モチベーションを高める仕組み
            </p>
        </div>
    </div>
</section>

<div class="container mx-auto px-4 py-8 max-w-6xl">
    <!-- 現在のステータス（ログイン時のみ） -->
    <?php if ($login_flag && $current_level_info): ?>
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl shadow-lg p-8 mb-12">
        <h2 class="text-2xl font-bold mb-6 text-gray-800">
            <i class="fas fa-user-circle text-blue-600 mr-2"></i>
            あなたの現在のステータス
        </h2>
        <div class="grid md:grid-cols-3 gap-6">
            <!-- レベル情報 -->
            <div class="bg-white rounded-lg p-6 shadow-md">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-5xl font-bold text-blue-600">Lv.<?php echo $current_level_info['level']; ?></span>
                    <i class="fas fa-<?php echo h($current_level_info['title']['icon'] ?? 'book-open-reader'); ?> text-4xl text-<?php echo h($current_level_info['title']['color'] ?? 'gray'); ?>-500"></i>
                </div>
                <div class="text-lg font-semibold text-gray-700 mb-2">
                    <?php echo h($current_level_info['title']['name'] ?? '読書初心者'); ?>
                </div>
                <div class="text-sm text-gray-600">
                    <?php 
                    $next_title_level = null;
                    if ($current_level_info['level'] < 5) $next_title_level = 5;
                    elseif ($current_level_info['level'] < 10) $next_title_level = 10;
                    elseif ($current_level_info['level'] < 20) $next_title_level = 20;
                    elseif ($current_level_info['level'] < 30) $next_title_level = 30;
                    elseif ($current_level_info['level'] < 50) $next_title_level = 50;
                    elseif ($current_level_info['level'] < 75) $next_title_level = 75;
                    elseif ($current_level_info['level'] < 100) $next_title_level = 100;
                    
                    if ($next_title_level !== null):
                        echo "次の称号まで: Lv." . $next_title_level;
                    else:
                        echo "次の称号まで: -";
                    endif;
                    ?>
                </div>
            </div>
            
            <!-- 読書ページ数 -->
            <div class="bg-white rounded-lg p-6 shadow-md">
                <div class="text-sm text-gray-600 mb-2">総読書ページ数</div>
                <div class="text-3xl font-bold text-green-600 mb-4">
                    <?php echo number_format($current_level_info['total_pages']); ?>
                    <span class="text-lg text-gray-600 font-normal">ページ</span>
                </div>
                <div class="text-sm text-gray-600">
                    <?php 
                    $books_estimate = intval($current_level_info['total_pages'] / 250);
                    echo "約" . number_format($books_estimate) . "冊分";
                    ?>
                </div>
            </div>
            
            <!-- 次のレベルまで -->
            <div class="bg-white rounded-lg p-6 shadow-md">
                <div class="text-sm text-gray-600 mb-2">次のレベルまで</div>
                <div class="mb-4">
                    <div class="bg-gray-200 rounded-full h-4 overflow-hidden">
                        <div class="bg-gradient-to-r from-blue-500 to-blue-600 h-full transition-all duration-500" 
                             style="width: <?php echo $current_level_info['progress']; ?>%"></div>
                    </div>
                    <div class="text-right text-sm text-gray-600 mt-1">
                        <?php echo $current_level_info['progress']; ?>%
                    </div>
                </div>
                <div class="text-2xl font-bold text-orange-600">
                    <?php echo number_format($current_level_info['next_level_pages']); ?>
                    <span class="text-sm text-gray-600 font-normal">ページ</span>
                </div>
                <div class="text-sm text-gray-600">
                    <?php 
                    $books_to_next = ceil($current_level_info['next_level_pages'] / 250);
                    echo "約" . $books_to_next . "冊分";
                    ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- レベリングシステムの仕組み -->
    <div class="bg-white rounded-xl shadow-lg p-8 mb-8">
        <h2 class="text-2xl font-bold mb-6 text-gray-800">
            <i class="fas fa-chart-line text-green-600 mr-2"></i>
            レベリングシステムの仕組み
        </h2>
        
        <div class="grid md:grid-cols-2 gap-8">
            <div>
                <h3 class="text-lg font-semibold mb-3 text-gray-700">基本ルール</h3>
                <ul class="space-y-3 text-gray-600">
                    <li class="flex items-start">
                        <i class="fas fa-check-circle text-green-500 mt-1 mr-3"></i>
                        <div>
                            <strong>読了した本のページ数</strong>が累積されます<br>
                            <span class="text-sm">「読了」または「既読」ステータスの本が対象</span>
                        </div>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check-circle text-green-500 mt-1 mr-3"></i>
                        <div>
                            <strong>100ページから開始</strong><br>
                            <span class="text-sm">最初のレベルアップは100ページ読了時</span>
                        </div>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check-circle text-green-500 mt-1 mr-3"></i>
                        <div>
                            <strong>レベルが上がるごとに必要ページ数が増加</strong><br>
                            <span class="text-sm">レベルn → n+1には「100 + (n-1)×20」ページ必要</span>
                        </div>
                    </li>
                </ul>
            </div>
            
            <div>
                <h3 class="text-lg font-semibold mb-3 text-gray-700">計算例</h3>
                <div class="bg-gray-50 rounded-lg p-4 space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span>Lv.1 → Lv.2</span>
                        <span class="font-semibold">100ページ（累計100ページ）</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Lv.2 → Lv.3</span>
                        <span class="font-semibold">120ページ（累計220ページ）</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Lv.3 → Lv.4</span>
                        <span class="font-semibold">140ページ（累計360ページ）</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Lv.10 → Lv.11</span>
                        <span class="font-semibold">280ページ（累計1,900ページ）</span>
                    </div>
                    <div class="text-gray-500 mt-2">
                        ※ 250ページの本なら、約8冊でレベル10到達
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 称号システム -->
    <div class="bg-white rounded-xl shadow-lg p-8 mb-8">
        <h2 class="text-2xl font-bold mb-6 text-gray-800">
            <i class="fas fa-medal text-yellow-500 mr-2"></i>
            称号システム
        </h2>
        <p class="text-gray-600 mb-6">
            特定のレベルに到達すると、読書への情熱と努力を称える特別な称号を獲得できます。
        </p>
        
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4">
            <?php 
            $titles = [
                ['level' => 1, 'name' => '読書初心者', 'icon' => 'book-open-reader', 'color' => 'gray', 'desc' => '読書の世界への第一歩'],
                ['level' => 5, 'name' => '本の虫', 'icon' => 'book', 'color' => 'blue', 'desc' => '読書が習慣になりました'],
                ['level' => 10, 'name' => '読書家', 'icon' => 'book-bookmark', 'color' => 'green', 'desc' => '確かな読書習慣の持ち主'],
                ['level' => 20, 'name' => '博識者', 'icon' => 'graduation-cap', 'color' => 'purple', 'desc' => '幅広い知識を身につけました'],
                ['level' => 30, 'name' => '賢者', 'icon' => 'scroll', 'color' => 'indigo', 'desc' => '深い洞察力を獲得'],
                ['level' => 50, 'name' => '読書マスター', 'icon' => 'medal', 'color' => 'yellow', 'desc' => '読書の達人の域に到達'],
                ['level' => 75, 'name' => '読書の達人', 'icon' => 'trophy', 'color' => 'orange', 'desc' => '卓越した読書家として認定'],
                ['level' => 100, 'name' => '読書の神', 'icon' => 'crown', 'color' => 'red', 'desc' => '伝説の読書家']
            ];
            foreach ($titles as $title): 
            ?>
            <div class="border-2 border-gray-200 rounded-lg p-4 hover:border-<?php echo $title['color']; ?>-400 transition-colors">
                <div class="text-center mb-3">
                    <i class="fas fa-<?php echo $title['icon']; ?> text-4xl text-<?php echo $title['color']; ?>-500"></i>
                </div>
                <div class="text-center">
                    <div class="font-bold text-gray-800"><?php echo $title['name']; ?></div>
                    <div class="text-sm text-gray-600 mb-2">Lv.<?php echo $title['level']; ?>〜</div>
                    <div class="text-xs text-gray-500"><?php echo $title['desc']; ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- レベルバッジの表示について -->
    <div class="bg-white rounded-xl shadow-lg p-8 mb-8">
        <h2 class="text-2xl font-bold mb-6 text-gray-800">
            <i class="fas fa-id-badge text-purple-600 mr-2"></i>
            レベルバッジの表示
        </h2>
        <p class="text-gray-600 mb-6">
            あなたのレベルは、サイト内の様々な場所でバッジとして表示されます。
        </p>
        
        <div class="grid md:grid-cols-2 gap-6">
            <div>
                <h3 class="text-lg font-semibold mb-3 text-gray-700">表示される場所</h3>
                <ul class="space-y-2 text-gray-600">
                    <li class="flex items-start">
                        <i class="fas fa-check text-green-500 mt-1 mr-3"></i>
                        <span>みんなの読書活動（タイムライン）</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check text-green-500 mt-1 mr-3"></i>
                        <span>読書ランキング（月間・全期間）</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check text-green-500 mt-1 mr-3"></i>
                        <span>みんなのレビュー一覧</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check text-green-500 mt-1 mr-3"></i>
                        <span>本の詳細ページ（レビュー・読者リスト）</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check text-green-500 mt-1 mr-3"></i>
                        <span>ユーザープロフィール</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check text-green-500 mt-1 mr-3"></i>
                        <span>他のユーザーの本棚</span>
                    </li>
                </ul>
            </div>
            
            <div>
                <h3 class="text-lg font-semibold mb-3 text-gray-700">バッジの種類</h3>
                <div class="space-y-3">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center gap-1 bg-blue-500 text-white px-2 py-1 rounded-full text-xs font-medium">
                            <i class="fas fa-book text-xs"></i>
                            <span>Lv.5</span>
                        </span>
                        <span class="text-sm text-gray-600">コンパクトバッジ（通常表示）</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center gap-2 bg-purple-500 text-white px-3 py-1 rounded-full text-sm font-medium">
                            <i class="fas fa-graduation-cap"></i>
                            <span>Lv.20 博識者</span>
                        </span>
                        <span class="text-sm text-gray-600">詳細バッジ（プロフィール等）</span>
                    </div>
                </div>
                <div class="mt-4 p-4 bg-blue-50 rounded-lg">
                    <p class="text-sm text-blue-800">
                        <i class="fas fa-info-circle mr-1"></i>
                        レベルバッジは、あなたの読書への取り組みを他のユーザーに示す証です。
                        積極的に読書を続けて、より高いレベルを目指しましょう！
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- レベル到達に必要なページ数一覧 -->
    <div class="bg-white rounded-xl shadow-lg p-8">
        <h2 class="text-2xl font-bold mb-6 text-gray-800">
            <i class="fas fa-list-ol text-blue-600 mr-2"></i>
            レベル到達に必要なページ数
        </h2>
        
        <div class="mb-4 text-sm text-gray-600">
            <i class="fas fa-info-circle mr-1"></i>
            各レベルに到達するために必要な<strong>累計読書ページ数</strong>の一覧です
        </div>
        
        <div class="overflow-x-auto">
            <div class="max-h-96 overflow-y-auto border rounded-lg">
                <table class="w-full">
                    <thead class="sticky top-0 bg-gray-100 border-b">
                        <tr>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">レベル</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700">このレベルに必要</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700">累計ページ数</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700">約何冊分</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $cumulative = 0;
                        $prev_cumulative = 0;
                        for ($i = 1; $i <= 100; $i++): 
                            if ($i == 1) {
                                $required = 0;
                                $cumulative = 0;
                            } else {
                                $required = 100 + ($i - 2) * 20;
                                $cumulative += $required;
                            }
                            $is_current = ($login_flag && $current_level_info && $current_level_info['level'] == $i);
                            $is_milestone = in_array($i, [1, 5, 10, 20, 30, 50, 75, 100]);
                        ?>
                        <tr class="border-b hover:bg-gray-50 <?php echo $is_current ? 'bg-yellow-50' : ''; ?> <?php echo $is_milestone ? 'font-semibold' : ''; ?>">
                            <td class="px-4 py-2 text-sm">
                                <?php if ($is_current): ?>
                                <i class="fas fa-arrow-right text-yellow-600 mr-2"></i>
                                <?php endif; ?>
                                <?php if ($is_milestone): ?>
                                <i class="fas fa-star text-yellow-400 mr-1"></i>
                                <?php endif; ?>
                                Lv.<?php echo $i; ?>
                            </td>
                            <td class="px-4 py-2 text-right text-sm text-gray-600">
                                <?php echo $i == 1 ? '-' : '+' . number_format($required) . 'p'; ?>
                            </td>
                            <td class="px-4 py-2 text-right text-sm">
                                <?php echo number_format($cumulative); ?>ページ
                            </td>
                            <td class="px-4 py-2 text-right text-sm text-gray-500">
                                約<?php echo intval($cumulative / 250); ?>冊
                            </td>
                        </tr>
                        <?php 
                        $prev_cumulative = $cumulative;
                        endfor; 
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="mt-6 grid md:grid-cols-3 gap-4 text-center">
            <div class="bg-blue-50 rounded-lg p-4">
                <div class="text-2xl font-bold text-blue-600">Lv.10</div>
                <div class="text-sm text-gray-600">1,900ページ（約8冊）</div>
            </div>
            <div class="bg-green-50 rounded-lg p-4">
                <div class="text-2xl font-bold text-green-600">Lv.50</div>
                <div class="text-sm text-gray-600">23,100ページ（約92冊）</div>
            </div>
            <div class="bg-purple-50 rounded-lg p-4">
                <div class="text-2xl font-bold text-purple-600">Lv.100</div>
                <div class="text-sm text-gray-600">71,600ページ（約286冊）</div>
            </div>
        </div>
    </div>
    
    <!-- モチベーションメッセージ -->
    <div class="mt-8 bg-gradient-to-r from-indigo-50 to-purple-50 rounded-xl p-8 text-center">
        <i class="fas fa-book-reader text-4xl text-indigo-600 mb-4"></i>
        <h3 class="text-xl font-bold text-gray-800 mb-2">読書は知識の泉</h3>
        <p class="text-gray-600 max-w-2xl mx-auto">
            レベルは単なる数字ではなく、あなたが積み重ねてきた読書の証です。<br>
            一冊一冊があなたの世界を広げ、新しい視点をもたらします。
        </p>
    </div>
    
    <div class="mt-8 text-center">
        <a href="/" class="inline-block bg-blue-600 text-white px-8 py-3 rounded-lg hover:bg-blue-700 transition shadow-md">
            <i class="fas fa-home mr-2"></i>
            トップページに戻る
        </a>
    </div>
</div>

<?php
$d_content = ob_get_clean();

// ベーステンプレートを使用
include(__DIR__ . '/t_base.php');
?>