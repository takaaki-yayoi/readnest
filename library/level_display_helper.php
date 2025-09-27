<?php
/**
 * レベル表示ヘルパー関数
 */

declare(strict_types=1);

/**
 * レベルバッジのHTMLを生成（コンパクト版）
 * 
 * @param array $level_info getReadingLevel()の戻り値
 * @param string $size 'xs', 'sm', 'md' のいずれか
 * @return string HTML
 */
function getLevelBadgeHtml(array $level_info, string $size = 'sm'): string {
    $level = $level_info['level'] ?? 1;
    $title = $level_info['title'] ?? ['name' => '読書初心者', 'icon' => 'book-open-reader', 'color' => 'gray'];
    
    // サイズに応じたクラス
    $size_classes = [
        'xs' => 'text-xs px-1.5 py-0.5',
        'sm' => 'text-xs px-2 py-1',
        'md' => 'text-sm px-2.5 py-1'
    ];
    
    $size_class = $size_classes[$size] ?? $size_classes['sm'];
    
    // 色のマッピング
    $color_classes = [
        'gray' => 'bg-gray-100 text-gray-700',
        'blue' => 'bg-blue-100 text-blue-700',
        'green' => 'bg-green-100 text-green-700',
        'purple' => 'bg-purple-100 text-purple-700',
        'indigo' => 'bg-indigo-100 text-indigo-700',
        'yellow' => 'bg-yellow-100 text-yellow-700',
        'orange' => 'bg-orange-100 text-orange-700',
        'red' => 'bg-red-100 text-red-700'
    ];
    
    $color_class = $color_classes[$title['color']] ?? $color_classes['gray'];
    
    // アイコンとレベル表示
    $icon = $title['icon'] ?? 'book-open-reader';
    
    return sprintf(
        '<span class="inline-flex items-center gap-1 %s %s rounded-full font-medium">
            <i class="fas fa-%s text-xs"></i>
            <span>Lv.%d</span>
        </span>',
        $color_class,
        $size_class,
        htmlspecialchars($icon),
        $level
    );
}

/**
 * レベルバッジのHTMLを生成（詳細版）
 * 称号名も表示する
 * 
 * @param array $level_info getReadingLevel()の戻り値
 * @return string HTML
 */
function getLevelBadgeDetailHtml(array $level_info): string {
    $level = $level_info['level'] ?? 1;
    $title = $level_info['title'] ?? ['name' => '読書初心者', 'icon' => 'book-open-reader', 'color' => 'gray'];
    
    // 色のマッピング
    $color_classes = [
        'gray' => 'bg-gray-100 text-gray-700 border-gray-300',
        'blue' => 'bg-blue-100 text-blue-700 border-blue-300',
        'green' => 'bg-green-100 text-green-700 border-green-300',
        'purple' => 'bg-purple-100 text-purple-700 border-purple-300',
        'indigo' => 'bg-indigo-100 text-indigo-700 border-indigo-300',
        'yellow' => 'bg-yellow-100 text-yellow-700 border-yellow-300',
        'orange' => 'bg-orange-100 text-orange-700 border-orange-300',
        'red' => 'bg-red-100 text-red-700 border-red-300'
    ];
    
    $color_class = $color_classes[$title['color']] ?? $color_classes['gray'];
    
    // アイコン
    $icon = $title['icon'] ?? 'book-open-reader';
    
    return sprintf(
        '<span class="inline-flex items-center gap-1.5 %s border rounded-full text-xs px-3 py-1 font-medium">
            <i class="fas fa-%s"></i>
            <span>Lv.%d %s</span>
        </span>',
        $color_class,
        htmlspecialchars($icon),
        $level,
        htmlspecialchars($title['name'])
    );
}

/**
 * インラインレベル表示（テキストのみ）
 * 
 * @param int $level レベル
 * @return string HTML
 */
function getLevelInlineText(int $level): string {
    return sprintf('<span class="text-xs text-gray-500">(Lv.%d)</span>', $level);
}
?>