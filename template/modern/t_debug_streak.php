<?php
if(!defined('CONFIG')) {
    error_log('direct access detected...');
    die('reference for this file is not allowed.');
}

// コンテンツ部分を生成
ob_start();
?>

<style>
.result-card {
    border-left: 4px solid #10b981;
}
.input-section {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}
</style>

<!-- ヘッダーセクション -->
<section class="input-section text-white py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold mb-2">
            <i class="fas fa-bug mr-3"></i>
            連続日数デバッグツール
        </h1>
        <p class="text-lg opacity-90">
            連続日数を入力して、表示されるメッセージを確認できます
        </p>
    </div>
</section>

<!-- 入力フォーム -->
<section class="py-8 bg-gray-50 dark:bg-gray-900">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <form method="get" action="" class="space-y-4">
                <div>
                    <label for="streak" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        連続日数を入力してください
                    </label>
                    <div class="flex gap-2">
                        <input type="number"
                               id="streak"
                               name="streak"
                               min="0"
                               max="3650"
                               value="<?php echo $test_streak; ?>"
                               class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="例: 100">
                        <button type="submit"
                                class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            <i class="fas fa-search mr-2"></i>
                            確認
                        </button>
                    </div>
                </div>

                <!-- クイックボタン -->
                <div class="flex flex-wrap gap-2">
                    <span class="text-sm text-gray-600 dark:text-gray-400">クイック選択:</span>
                    <button type="submit" name="streak" value="1" class="px-3 py-1 text-sm bg-gray-200 hover:bg-gray-300 rounded">1日</button>
                    <button type="submit" name="streak" value="3" class="px-3 py-1 text-sm bg-gray-200 hover:bg-gray-300 rounded">3日</button>
                    <button type="submit" name="streak" value="7" class="px-3 py-1 text-sm bg-gray-200 hover:bg-gray-300 rounded">7日</button>
                    <button type="submit" name="streak" value="30" class="px-3 py-1 text-sm bg-gray-200 hover:bg-gray-300 rounded">30日</button>
                    <button type="submit" name="streak" value="100" class="px-3 py-1 text-sm bg-gray-200 hover:bg-gray-300 rounded">100日</button>
                    <button type="submit" name="streak" value="365" class="px-3 py-1 text-sm bg-gray-200 hover:bg-gray-300 rounded">1年</button>
                    <button type="submit" name="streak" value="730" class="px-3 py-1 text-sm bg-gray-200 hover:bg-gray-300 rounded">2年</button>
                    <button type="submit" name="streak" value="1095" class="px-3 py-1 text-sm bg-gray-200 hover:bg-gray-300 rounded">3年</button>
                    <button type="submit" name="streak" value="1825" class="px-3 py-1 text-sm bg-gray-200 hover:bg-gray-300 rounded">5年</button>
                    <button type="submit" name="streak" value="3650" class="px-3 py-1 text-sm bg-gray-200 hover:bg-gray-300 rounded">10年</button>
                </div>
            </form>
        </div>
    </div>
</section>

<!-- 結果表示 -->
<?php if (!empty($results)): ?>
<section class="py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        <!-- 入力値表示 -->
        <div class="bg-gradient-to-r from-purple-500 to-indigo-600 text-white rounded-lg p-6 text-center">
            <div class="text-5xl font-bold mb-2"><?php echo $results['streak']; ?>日</div>
            <div class="text-lg opacity-90">連続記録</div>
        </div>

        <!-- 読書カレンダーページ - 統計カード -->
        <div class="result-card bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4 flex items-center">
                <i class="fas fa-calendar-check text-emerald-600 mr-3"></i>
                読書カレンダーページ - 統計カード
            </h2>
            <div class="bg-gradient-to-r from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 rounded-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">連続記録</p>
                        <p class="text-3xl font-bold text-blue-600">
                            <?php echo $results['streak']; ?>
                            <span class="text-lg font-normal">日</span>
                        </p>
                    </div>
                    <div class="text-4xl text-blue-400">
                        <i class="fas fa-fire"></i>
                    </div>
                </div>
                <?php if ($results['calendar_stat_card'] !== '(表示なし)'): ?>
                <p class="text-xs text-blue-600 mt-2">
                    <i class="fas fa-trophy mr-1"></i>
                    <?php echo $results['calendar_stat_card']; ?>
                </p>
                <?php else: ?>
                <p class="text-xs text-gray-500 mt-2 italic">メッセージなし</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- 読書カレンダーページ - モチベーションメッセージ -->
        <div class="result-card bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4 flex items-center">
                <i class="fas fa-comment-dots text-emerald-600 mr-3"></i>
                読書カレンダーページ - モチベーションメッセージ
            </h2>
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg p-6">
                <div class="text-center">
                    <i class="<?php echo $results['calendar_motivation']['icon']; ?> text-4xl text-<?php echo $results['calendar_motivation']['color']; ?> mb-3"></i>
                    <h3 class="text-xl font-bold text-gray-900 mb-2"><?php echo $results['calendar_motivation']['title']; ?></h3>
                    <p class="text-gray-700"><?php echo $results['calendar_motivation']['message']; ?></p>
                </div>
            </div>
        </div>

        <!-- ホームページ - マイルストーン -->
        <div class="result-card bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4 flex items-center">
                <i class="fas fa-home text-emerald-600 mr-3"></i>
                ホームページ - マイルストーン
            </h2>
            <div class="space-y-4">
                <?php if ($results['home_milestone']['current']): ?>
                <div class="bg-gradient-to-r from-yellow-50 to-orange-50 rounded-lg p-4">
                    <h3 class="font-semibold text-gray-900 mb-2">現在の達成</h3>
                    <div class="flex items-center gap-3">
                        <i class="fas fa-<?php echo $results['home_milestone']['current']['icon']; ?> text-2xl text-<?php echo $results['home_milestone']['current']['color']; ?>-500"></i>
                        <div>
                            <div class="font-bold text-lg"><?php echo $results['home_milestone']['current']['title']; ?></div>
                            <div class="text-sm text-gray-600"><?php echo $results['home_milestone']['current']['days']; ?>日達成</div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($results['home_milestone']['next']): ?>
                <div class="bg-gradient-to-r from-gray-50 to-gray-100 rounded-lg p-4">
                    <h3 class="font-semibold text-gray-900 mb-2">次の目標</h3>
                    <div class="flex items-center gap-3 mb-3">
                        <i class="fas fa-<?php echo $results['home_milestone']['next']['icon']; ?> text-2xl text-gray-400"></i>
                        <div>
                            <div class="font-bold text-lg"><?php echo $results['home_milestone']['next']['title']; ?></div>
                            <div class="text-sm text-gray-600">あと<?php echo $results['home_milestone']['days_to_next']; ?>日</div>
                        </div>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-blue-600 h-2 rounded-full transition-all"
                             style="width: <?php echo $results['home_milestone']['progress']; ?>%"></div>
                    </div>
                    <div class="text-xs text-gray-600 mt-1 text-right"><?php echo $results['home_milestone']['progress']; ?>%</div>
                </div>
                <?php else: ?>
                <div class="bg-gradient-to-r from-purple-50 to-pink-50 rounded-lg p-4">
                    <div class="text-center">
                        <i class="fas fa-crown text-4xl text-purple-500 mb-2"></i>
                        <div class="font-bold text-lg">すべてのマイルストーン達成！</div>
                        <div class="text-sm text-gray-600 mt-1">素晴らしい！この調子で続けましょう</div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ホームページ - モチベーションメッセージ -->
        <div class="result-card bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4 flex items-center">
                <i class="fas fa-lightbulb text-emerald-600 mr-3"></i>
                ホームページ - モチベーションメッセージ
            </h2>
            <div class="bg-gradient-to-r from-indigo-50 to-purple-50 rounded-lg p-4">
                <div class="flex items-start gap-3">
                    <i class="fas fa-quote-left text-2xl text-indigo-400 mt-1"></i>
                    <p class="text-gray-700 text-lg flex-1"><?php echo $results['home_motivational']; ?></p>
                    <i class="fas fa-quote-right text-2xl text-indigo-400 mt-1"></i>
                </div>
            </div>
        </div>

        <!-- マイルストーン一覧 -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4 flex items-center">
                <i class="fas fa-list text-emerald-600 mr-3"></i>
                全マイルストーン一覧
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <?php
                $all_milestones = [
                    3 => ['title' => '読書習慣スタート', 'icon' => 'seedling', 'color' => 'green'],
                    7 => ['title' => '1週間達成', 'icon' => 'fire', 'color' => 'orange'],
                    14 => ['title' => '2週間達成', 'icon' => 'fire-flame-curved', 'color' => 'orange'],
                    21 => ['title' => '3週間達成', 'icon' => 'fire-flame-simple', 'color' => 'red'],
                    30 => ['title' => '1ヶ月達成', 'icon' => 'medal', 'color' => 'yellow'],
                    50 => ['title' => '50日達成', 'icon' => 'trophy', 'color' => 'purple'],
                    100 => ['title' => '100日達成', 'icon' => 'crown', 'color' => 'yellow'],
                    150 => ['title' => '150日達成', 'icon' => 'star', 'color' => 'yellow'],
                    200 => ['title' => '200日達成', 'icon' => 'award', 'color' => 'orange'],
                    300 => ['title' => '300日達成', 'icon' => 'gem', 'color' => 'purple'],
                    365 => ['title' => '1年達成', 'icon' => 'crown', 'color' => 'indigo'],
                    500 => ['title' => '500日達成', 'icon' => 'trophy', 'color' => 'yellow'],
                    730 => ['title' => '2年達成', 'icon' => 'crown', 'color' => 'red'],
                    1000 => ['title' => '1000日達成', 'icon' => 'gem', 'color' => 'purple'],
                    1095 => ['title' => '3年達成', 'icon' => 'crown', 'color' => 'purple'],
                    1460 => ['title' => '4年達成', 'icon' => 'crown', 'color' => 'indigo'],
                    1825 => ['title' => '5年達成', 'icon' => 'gem', 'color' => 'pink'],
                    2190 => ['title' => '6年達成', 'icon' => 'crown', 'color' => 'red'],
                    2555 => ['title' => '7年達成', 'icon' => 'trophy', 'color' => 'purple'],
                    2920 => ['title' => '8年達成', 'icon' => 'crown', 'color' => 'indigo'],
                    3285 => ['title' => '9年達成', 'icon' => 'gem', 'color' => 'pink'],
                    3650 => ['title' => '10年達成', 'icon' => 'crown', 'color' => 'gold'],
                ];

                foreach ($all_milestones as $days => $milestone):
                    $achieved = $results['streak'] >= $days;
                    $is_current = $results['home_milestone']['current'] && $results['home_milestone']['current']['days'] == $days;
                    $is_next = $results['home_milestone']['next'] && $results['home_milestone']['next']['days'] == $days;
                ?>
                <div class="flex items-center gap-3 p-3 rounded-lg <?php echo $achieved ? 'bg-green-50 border border-green-200' : 'bg-gray-50 border border-gray-200'; ?>">
                    <i class="fas fa-<?php echo $milestone['icon']; ?> text-xl <?php echo $achieved ? 'text-' . $milestone['color'] . '-500' : 'text-gray-400'; ?>"></i>
                    <div class="flex-1">
                        <div class="font-semibold <?php echo $achieved ? 'text-gray-900' : 'text-gray-500'; ?>">
                            <?php echo $milestone['title']; ?>
                        </div>
                        <div class="text-xs text-gray-500"><?php echo $days; ?>日</div>
                    </div>
                    <?php if ($achieved): ?>
                        <i class="fas fa-check-circle text-green-500"></i>
                    <?php endif; ?>
                    <?php if ($is_current): ?>
                        <span class="text-xs bg-blue-500 text-white px-2 py-1 rounded">現在</span>
                    <?php endif; ?>
                    <?php if ($is_next): ?>
                        <span class="text-xs bg-orange-500 text-white px-2 py-1 rounded">次の目標</span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>
</section>
<?php endif; ?>

<?php
$d_content = ob_get_clean();

// ベーステンプレートに渡す
include(getTemplatePath('t_base.php'));
?>
