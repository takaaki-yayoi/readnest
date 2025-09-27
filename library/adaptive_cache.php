<?php
/**
 * 適応型キャッシュシステム
 * トラフィックや時間帯に応じてキャッシュ時間を動的に調整
 */

require_once dirname(__FILE__) . '/cache.php';

class AdaptiveCache {
    private $cache;
    private $stats_file;
    
    // デフォルトキャッシュ時間（秒）
    const DEFAULT_TTL = 300; // 5分
    const MIN_TTL = 60;      // 最小1分
    const MAX_TTL = 3600;    // 最大1時間
    
    // キャッシュ戦略設定
    const CACHE_STRATEGIES = [
        'activities' => [
            'base_ttl' => 300,
            'peak_hours' => [12, 13, 19, 20, 21], // ピーク時間帯
            'peak_multiplier' => 0.5,              // ピーク時はキャッシュ時間を短く
            'off_peak_multiplier' => 2.0,          // オフピーク時は長く
            'weekend_multiplier' => 1.5,           // 週末は少し長く
        ],
        'recent_activities' => [
            'base_ttl' => 180,
            'peak_hours' => [12, 13, 19, 20, 21],
            'peak_multiplier' => 0.3,
            'off_peak_multiplier' => 3.0,
            'weekend_multiplier' => 2.0,
        ],
        'user_stats' => [
            'base_ttl' => 600,
            'peak_hours' => [],
            'peak_multiplier' => 1.0,
            'off_peak_multiplier' => 1.0,
            'weekend_multiplier' => 1.0,
        ],
        'book_rankings' => [
            'base_ttl' => 1800,
            'peak_hours' => [],
            'peak_multiplier' => 1.0,
            'off_peak_multiplier' => 1.0,
            'weekend_multiplier' => 1.0,
        ],
    ];
    
    public function __construct() {
        $this->cache = getCache();
        $this->stats_file = dirname(__DIR__) . '/cache/cache_stats.json';
    }
    
    /**
     * 適応型キャッシュ取得
     */
    public function get($key, $strategy_name = null) {
        $this->recordAccess($key, 'get');
        return $this->cache->get($key);
    }
    
    /**
     * 適応型キャッシュ保存
     */
    public function set($key, $value, $strategy_name = null, $custom_ttl = null) {
        if ($custom_ttl !== null) {
            $ttl = $custom_ttl;
        } else {
            $ttl = $this->calculateAdaptiveTTL($strategy_name);
        }
        
        $this->recordAccess($key, 'set', $ttl);
        return $this->cache->set($key, $value, $ttl);
    }
    
    /**
     * 動的TTLを計算
     */
    private function calculateAdaptiveTTL($strategy_name) {
        if (!$strategy_name || !isset(self::CACHE_STRATEGIES[$strategy_name])) {
            return self::DEFAULT_TTL;
        }
        
        $strategy = self::CACHE_STRATEGIES[$strategy_name];
        $base_ttl = $strategy['base_ttl'];
        
        // 現在の時間情報
        $current_hour = (int)date('G');
        $current_day = (int)date('w'); // 0=日曜日, 6=土曜日
        $is_weekend = ($current_day == 0 || $current_day == 6);
        
        // 基本TTL
        $ttl = $base_ttl;
        
        // 週末調整
        if ($is_weekend) {
            $ttl = $ttl * $strategy['weekend_multiplier'];
        }
        
        // ピーク時間調整
        if (in_array($current_hour, $strategy['peak_hours'])) {
            $ttl = $ttl * $strategy['peak_multiplier'];
        } else {
            $ttl = $ttl * $strategy['off_peak_multiplier'];
        }
        
        // アクセス頻度に基づく調整
        $access_rate = $this->getAccessRate($strategy_name);
        if ($access_rate > 100) { // 高頻度アクセス
            $ttl = $ttl * 0.7;
        } elseif ($access_rate < 10) { // 低頻度アクセス
            $ttl = $ttl * 1.5;
        }
        
        // 範囲内に収める
        $ttl = max(self::MIN_TTL, min(self::MAX_TTL, (int)$ttl));
        
        return $ttl;
    }
    
    /**
     * アクセス記録
     */
    private function recordAccess($key, $type, $ttl = null) {
        $stats = $this->loadStats();
        
        $time_slot = date('Y-m-d H:00:00');
        if (!isset($stats[$time_slot])) {
            $stats[$time_slot] = [];
        }
        
        $key_hash = substr(md5($key), 0, 8); // キーの短縮版
        if (!isset($stats[$time_slot][$key_hash])) {
            $stats[$time_slot][$key_hash] = [
                'get' => 0,
                'set' => 0,
                'ttl' => []
            ];
        }
        
        $stats[$time_slot][$key_hash][$type]++;
        if ($ttl !== null) {
            $stats[$time_slot][$key_hash]['ttl'][] = $ttl;
        }
        
        // 古いデータを削除（24時間以上前）
        $cutoff = date('Y-m-d H:00:00', strtotime('-24 hours'));
        foreach (array_keys($stats) as $slot) {
            if ($slot < $cutoff) {
                unset($stats[$slot]);
            }
        }
        
        $this->saveStats($stats);
    }
    
    /**
     * アクセスレートを取得（過去1時間）
     */
    private function getAccessRate($strategy_name) {
        $stats = $this->loadStats();
        $current_hour = date('Y-m-d H:00:00');
        
        if (!isset($stats[$current_hour])) {
            return 0;
        }
        
        $total_accesses = 0;
        foreach ($stats[$current_hour] as $key_data) {
            $total_accesses += $key_data['get'] + $key_data['set'];
        }
        
        return $total_accesses;
    }
    
    /**
     * 統計情報を読み込み
     */
    private function loadStats() {
        if (!file_exists($this->stats_file)) {
            return [];
        }
        
        $content = file_get_contents($this->stats_file);
        if ($content === false) {
            return [];
        }
        
        $stats = json_decode($content, true);
        return $stats ?: [];
    }
    
    /**
     * 統計情報を保存
     */
    private function saveStats($stats) {
        $dir = dirname($this->stats_file);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        file_put_contents($this->stats_file, json_encode($stats));
    }
    
    /**
     * キャッシュ統計レポートを生成
     */
    public function generateReport() {
        $stats = $this->loadStats();
        $report = [
            'time_slots' => count($stats),
            'total_accesses' => 0,
            'average_ttl' => 0,
            'peak_hours' => [],
            'cache_efficiency' => 0
        ];
        
        $hourly_accesses = [];
        $all_ttls = [];
        
        foreach ($stats as $time_slot => $slot_data) {
            $hour = (int)date('G', strtotime($time_slot));
            if (!isset($hourly_accesses[$hour])) {
                $hourly_accesses[$hour] = 0;
            }
            
            foreach ($slot_data as $key_data) {
                $accesses = $key_data['get'] + $key_data['set'];
                $hourly_accesses[$hour] += $accesses;
                $report['total_accesses'] += $accesses;
                
                if (!empty($key_data['ttl'])) {
                    $all_ttls = array_merge($all_ttls, $key_data['ttl']);
                }
            }
        }
        
        // ピーク時間を特定
        arsort($hourly_accesses);
        $report['peak_hours'] = array_slice(array_keys($hourly_accesses), 0, 3);
        
        // 平均TTLを計算
        if (!empty($all_ttls)) {
            $report['average_ttl'] = array_sum($all_ttls) / count($all_ttls);
        }
        
        // キャッシュ効率（ゲット率）
        $total_gets = 0;
        $total_sets = 0;
        foreach ($stats as $slot_data) {
            foreach ($slot_data as $key_data) {
                $total_gets += $key_data['get'];
                $total_sets += $key_data['set'];
            }
        }
        
        if ($total_gets + $total_sets > 0) {
            $report['cache_efficiency'] = $total_gets / ($total_gets + $total_sets) * 100;
        }
        
        return $report;
    }
    
    /**
     * キャッシュをクリア
     */
    public function clear() {
        return $this->cache->clear();
    }
    
    /**
     * 特定のパターンにマッチするキャッシュを削除
     */
    public function deletePattern($pattern) {
        // SimpleCacheは直接パターン削除をサポートしないので、
        // 将来的に実装を追加する必要がある
        return false;
    }
}

/**
 * グローバル適応型キャッシュインスタンスを取得
 */
function getAdaptiveCache() {
    static $cache = null;
    
    if ($cache === null) {
        $cache = new AdaptiveCache();
    }
    
    return $cache;
}