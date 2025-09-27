<?php
if(!defined('CONFIG')) {
    error_log('direct access detected. file:' . __FILE__ . ' line:' . __LINE__ . ' ip:' . $_SERVER['REMOTE_ADDR'] . ' agent:' . $_SERVER['HTTP_USER_AGENT']);
    die('reference for this file is not allowed.');
}

ob_start();
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
        <!-- ヘッダー -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-2">月間目標設定</h1>
            <p class="text-gray-600 dark:text-gray-400">毎月の読書目標を設定して、継続的な読書習慣を作りましょう。</p>
        </div>

        <?php if ($message): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <?php echo html($message); ?>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php echo html($error); ?>
        </div>
        <?php endif; ?>

        <!-- 年間目標情報 -->
        <div class="bg-blue-50 dark:bg-blue-900 rounded-lg p-4 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">年間目標</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-gray-100"><?php echo $yearly_goal; ?>冊</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">自動配分（月間）</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-gray-100"><?php echo $auto_monthly_goal; ?>冊</p>
                </div>
                <?php if ($consecutive_months > 0): ?>
                <div class="text-center">
                    <p class="text-sm text-gray-600 dark:text-gray-400">連続達成</p>
                    <p class="text-2xl font-bold text-yellow-600"><?php echo $consecutive_months; ?>ヶ月</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- 今年の達成状況 -->
        <div class="mb-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4"><?php echo $current_year; ?>年の達成状況</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($achievements as $month => $data): ?>
                <?php 
                $is_current_month = $month == (int)date('n');
                $is_future = $month > (int)date('n');
                $is_achieved = $data['achieved'] >= $data['goal'] && $data['goal'] > 0;
                ?>
                <div class="border rounded-lg p-4 <?php echo $is_current_month ? 'border-blue-500 bg-blue-50 dark:bg-blue-900' : 'border-gray-200 dark:border-gray-700'; ?>">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="font-medium text-gray-900 dark:text-gray-100">
                            <?php echo $month; ?>月
                            <?php if ($is_current_month): ?>
                            <span class="text-xs text-blue-600 ml-1">（今月）</span>
                            <?php endif; ?>
                        </h3>
                        <?php if ($is_achieved && !$is_future): ?>
                        <span class="achieved-badge">達成!</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-2">
                        <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400 mb-1">
                            <span><?php echo $data['achieved']; ?> / <?php echo $data['goal']; ?>冊</span>
                            <span><?php echo round($data['progress']); ?>%</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo min(100, $data['progress']); ?>%"></div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- 目標設定フォーム -->
        <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">目標タイプ</h2>
            
            <form method="post" action="" id="goal-form">
                <?php csrfFieldTag(); ?>
                
                <div class="space-y-4 mb-6">
                    <!-- 自動配分 -->
                    <label class="flex items-start cursor-pointer">
                        <input type="radio" name="goal_type" value="auto" 
                               <?php echo $goal_type === 'auto' ? 'checked' : ''; ?>
                               class="mt-1 text-readnest-primary focus:ring-readnest-primary">
                        <div class="ml-3">
                            <p class="font-medium text-gray-900 dark:text-gray-100">自動配分</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">年間目標を12ヶ月で均等に配分します（月<?php echo $auto_monthly_goal; ?>冊）</p>
                        </div>
                    </label>
                    
                    <!-- カスタム設定 -->
                    <label class="flex items-start cursor-pointer">
                        <input type="radio" name="goal_type" value="custom" 
                               <?php echo $goal_type === 'custom' ? 'checked' : ''; ?>
                               class="mt-1 text-readnest-primary focus:ring-readnest-primary">
                        <div class="ml-3">
                            <p class="font-medium text-gray-900 dark:text-gray-100">カスタム設定</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">月ごとに異なる目標を設定できます</p>
                        </div>
                    </label>
                </div>
                
                <!-- カスタム目標入力 -->
                <div id="custom-goals" class="<?php echo $goal_type === 'custom' ? '' : 'hidden'; ?> mb-6">
                    <h3 class="text-md font-medium text-gray-900 dark:text-gray-100 mb-3">月別目標冊数</h3>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                        <?php for ($month = 1; $month <= 12; $month++): ?>
                        <?php 
                        $custom_value = isset($custom_goals[$month]) ? $custom_goals[$month] : $auto_monthly_goal;
                        ?>
                        <div class="flex items-center">
                            <label class="text-sm text-gray-700 dark:text-gray-300 mr-2 w-12"><?php echo $month; ?>月:</label>
                            <input type="number"
                                   name="month_<?php echo $month; ?>"
                                   value="<?php echo $custom_value; ?>"
                                   min="0"
                                   max="999"
                                   class="month-goal-input border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-2 py-1 text-sm">
                            <span class="text-sm text-gray-600 dark:text-gray-400 ml-1">冊</span>
                        </div>
                        <?php endfor; ?>
                    </div>
                    
                    <div class="mt-3 text-sm text-gray-600 dark:text-gray-400">
                        <p>合計: <span id="total-custom-goals" class="font-bold">0</span>冊</p>
                    </div>
                </div>
                
                <div class="flex gap-3">
                    <button type="submit" 
                            name="action" 
                            value="set_auto"
                            id="btn-auto"
                            class="btn-primary <?php echo $goal_type === 'auto' ? '' : 'hidden'; ?>">
                        自動配分を維持
                    </button>
                    
                    <button type="submit" 
                            name="action" 
                            value="set_custom"
                            id="btn-custom"
                            class="btn-primary <?php echo $goal_type === 'custom' ? '' : 'hidden'; ?>">
                        カスタム目標を保存
                    </button>
                    
                    <button type="button" onclick="history.back();" class="btn-secondary">キャンセル</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const radioButtons = document.querySelectorAll('input[name="goal_type"]');
    const customGoalsDiv = document.getElementById('custom-goals');
    const btnAuto = document.getElementById('btn-auto');
    const btnCustom = document.getElementById('btn-custom');
    const monthInputs = document.querySelectorAll('.month-goal-input');
    
    // ラジオボタン変更時の処理
    radioButtons.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'custom') {
                customGoalsDiv.classList.remove('hidden');
                btnAuto.classList.add('hidden');
                btnCustom.classList.remove('hidden');
                updateTotalGoals();
            } else {
                customGoalsDiv.classList.add('hidden');
                btnAuto.classList.remove('hidden');
                btnCustom.classList.add('hidden');
            }
        });
    });
    
    // カスタム目標の合計を更新
    function updateTotalGoals() {
        let total = 0;
        monthInputs.forEach(input => {
            total += parseInt(input.value) || 0;
        });
        document.getElementById('total-custom-goals').textContent = total;
    }
    
    // 月別目標入力時の処理
    monthInputs.forEach(input => {
        input.addEventListener('input', updateTotalGoals);
    });
    
    // 初期表示時に合計を計算
    if (document.querySelector('input[name="goal_type"]:checked').value === 'custom') {
        updateTotalGoals();
    }
});
</script>

<?php
$d_content = ob_get_clean();
include('t_base.php');
?>