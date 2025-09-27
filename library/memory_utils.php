<?php
/**
 * メモリ関連ユーティリティ
 */

declare(strict_types=1);

/**
 * メモリ制限値をバイトに変換
 * @param string $val PHP ini設定値（例: 128M, 1G）
 * @return int バイト数
 */
function convertToBytes(string $val): int {
    $val = trim($val);
    if (empty($val)) {
        return 0;
    }
    
    $last = strtolower($val[strlen($val)-1]);
    $num = (int)$val;
    
    switch($last) {
        case 'g':
            $num *= 1024;
        case 'm':
            $num *= 1024;
        case 'k':
            $num *= 1024;
    }
    
    return $num;
}

/**
 * 現在のメモリ使用状況を取得
 * @return array メモリ情報
 */
function getMemoryInfo(): array {
    $memory_limit = ini_get('memory_limit');
    $memory_limit_bytes = ($memory_limit === '-1') ? -1 : convertToBytes($memory_limit);
    $current_usage = memory_get_usage(true);
    $peak_usage = memory_get_peak_usage(true);
    
    return [
        'limit' => $memory_limit,
        'limit_bytes' => $memory_limit_bytes,
        'current' => $current_usage,
        'current_mb' => round($current_usage / 1024 / 1024, 2),
        'peak' => $peak_usage,
        'peak_mb' => round($peak_usage / 1024 / 1024, 2),
        'usage_percent' => ($memory_limit_bytes > 0) ? round(($current_usage / $memory_limit_bytes) * 100, 2) : 0
    ];
}

/**
 * メモリ使用量が制限に近いかチェック
 * @param float $threshold 閾値（デフォルト: 80%）
 * @return bool 制限に近い場合true
 */
function isMemoryUsageHigh(float $threshold = 0.8): bool {
    $info = getMemoryInfo();
    
    if ($info['limit_bytes'] === -1) {
        return false; // 無制限の場合
    }
    
    return ($info['current'] / $info['limit_bytes']) > $threshold;
}

/**
 * 大きな変数を解放してメモリを節約
 * @param mixed &$var 解放する変数（参照渡し）
 */
function freeMemory(&$var): void {
    $var = null;
    unset($var);
}

/**
 * ガベージコレクションを強制実行
 * @return int 解放されたサイクル数
 */
function forceGarbageCollection(): int {
    if (function_exists('gc_collect_cycles')) {
        return gc_collect_cycles();
    }
    return 0;
}