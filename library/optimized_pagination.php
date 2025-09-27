<?php
/**
 * 最適化されたページネーションライブラリ
 * 大量データに対応した効率的なページング処理
 */

declare(strict_types=1);

class OptimizedPagination {
    private $db;
    private $per_page;
    private $current_page;
    private $total_count;
    private $use_estimate = false;
    
    public function __construct($db, int $per_page = 30) {
        $this->db = $db;
        $this->per_page = $per_page;
        $this->current_page = 1;
    }
    
    /**
     * 現在のページを設定
     */
    public function setCurrentPage(int $page): void {
        $this->current_page = max(1, $page);
    }
    
    /**
     * 推定カウントを使用するかどうか
     * 大量データの場合はtrueにすることで高速化
     */
    public function useEstimateCount(bool $use): void {
        $this->use_estimate = $use;
    }
    
    /**
     * 総件数を取得（キャッシュ対応）
     */
    public function getTotalCount(string $count_sql, array $params = []): int {
        global $cache;
        
        // キャッシュキーを生成
        $cache_key = 'pagination_count_' . md5($count_sql . serialize($params));
        
        if ($cache && !$this->use_estimate) {
            $cached_count = $cache->get($cache_key);
            if ($cached_count !== false) {
                $this->total_count = (int)$cached_count;
                return $this->total_count;
            }
        }
        
        // 推定カウントを使用する場合
        if ($this->use_estimate) {
            $this->total_count = $this->getEstimatedCount($count_sql, $params);
        } else {
            // 正確なカウント
            $count = $this->db->getOne($count_sql, $params);
            if (DB::isError($count)) {
                error_log('Pagination count error: ' . $count->getMessage());
                $this->total_count = 0;
            } else {
                $this->total_count = (int)$count;
            }
        }
        
        // キャッシュに保存（5分間）
        if ($cache && $this->total_count > 0) {
            $cache->set($cache_key, $this->total_count, 300);
        }
        
        return $this->total_count;
    }
    
    /**
     * 推定カウントを取得（高速化のため）
     */
    private function getEstimatedCount(string $count_sql, array $params): int {
        // EXPLAIN を使用してクエリプランから推定行数を取得
        $explain_sql = "EXPLAIN " . str_replace('COUNT(*)', '*', $count_sql);
        $explain_result = $this->db->getAll($explain_sql, $params, DB_FETCHMODE_ASSOC);
        
        if (!DB::isError($explain_result) && !empty($explain_result)) {
            $estimated_rows = 0;
            foreach ($explain_result as $row) {
                if (isset($row['rows'])) {
                    $estimated_rows = max($estimated_rows, (int)$row['rows']);
                }
            }
            return $estimated_rows;
        }
        
        // フォールバック: テーブル統計情報を使用
        if (preg_match('/FROM\s+(\w+)/i', $count_sql, $matches)) {
            $table_name = $matches[1];
            $stats_sql = "SELECT table_rows FROM information_schema.tables 
                         WHERE table_schema = DATABASE() AND table_name = ?";
            $table_rows = $this->db->getOne($stats_sql, [$table_name]);
            
            if (!DB::isError($table_rows)) {
                return (int)$table_rows;
            }
        }
        
        return 10000; // デフォルト値
    }
    
    /**
     * オフセットを取得
     */
    public function getOffset(): int {
        return ($this->current_page - 1) * $this->per_page;
    }
    
    /**
     * LIMIT句を取得
     */
    public function getLimitClause(): string {
        return sprintf(" LIMIT %d OFFSET %d", $this->per_page, $this->getOffset());
    }
    
    /**
     * 総ページ数を取得
     */
    public function getTotalPages(): int {
        return (int)ceil($this->total_count / $this->per_page);
    }
    
    /**
     * ページネーションリンクを生成
     */
    public function generateLinks(string $base_url, array $query_params = []): array {
        $links = [];
        $total_pages = $this->getTotalPages();
        
        // 表示するページ番号の範囲を決定
        $window = 5; // 現在のページの前後に表示するページ数
        $start = max(1, $this->current_page - $window);
        $end = min($total_pages, $this->current_page + $window);
        
        // 前へリンク
        if ($this->current_page > 1) {
            $query_params['page'] = $this->current_page - 1;
            $links['prev'] = $base_url . '?' . http_build_query($query_params);
        }
        
        // ページ番号リンク
        $links['pages'] = [];
        
        // 最初のページ
        if ($start > 1) {
            $query_params['page'] = 1;
            $links['pages'][1] = $base_url . '?' . http_build_query($query_params);
            if ($start > 2) {
                $links['pages']['...1'] = null; // 省略記号
            }
        }
        
        // ページ番号
        for ($i = $start; $i <= $end; $i++) {
            $query_params['page'] = $i;
            $links['pages'][$i] = $base_url . '?' . http_build_query($query_params);
        }
        
        // 最後のページ
        if ($end < $total_pages) {
            if ($end < $total_pages - 1) {
                $links['pages']['...2'] = null; // 省略記号
            }
            $query_params['page'] = $total_pages;
            $links['pages'][$total_pages] = $base_url . '?' . http_build_query($query_params);
        }
        
        // 次へリンク
        if ($this->current_page < $total_pages) {
            $query_params['page'] = $this->current_page + 1;
            $links['next'] = $base_url . '?' . http_build_query($query_params);
        }
        
        $links['current_page'] = $this->current_page;
        $links['total_pages'] = $total_pages;
        $links['total_count'] = $this->total_count;
        
        return $links;
    }
    
    /**
     * カーソルベースのページネーション用メソッド
     * 大量データに最適
     */
    public function getCursorQuery(string $base_sql, string $order_column, $last_value = null): string {
        if ($last_value !== null) {
            // WHERE句を追加
            if (stripos($base_sql, 'WHERE') !== false) {
                $base_sql = str_replace('WHERE', "WHERE $order_column > ? AND ", $base_sql);
            } else {
                $base_sql .= " WHERE $order_column > ?";
            }
        }
        
        $base_sql .= " ORDER BY $order_column LIMIT " . $this->per_page;
        
        return $base_sql;
    }
    
    /**
     * パフォーマンス統計を取得
     */
    public function getStats(): array {
        return [
            'current_page' => $this->current_page,
            'per_page' => $this->per_page,
            'total_count' => $this->total_count,
            'total_pages' => $this->getTotalPages(),
            'offset' => $this->getOffset(),
            'use_estimate' => $this->use_estimate,
            'showing_from' => $this->getOffset() + 1,
            'showing_to' => min($this->getOffset() + $this->per_page, $this->total_count)
        ];
    }
}

/**
 * ページネーションHTMLを生成するヘルパー関数
 */
function renderPagination(array $links, string $css_class = 'pagination'): string {
    if (empty($links['pages'])) {
        return '';
    }
    
    $html = '<nav class="' . htmlspecialchars($css_class) . '">';
    $html .= '<ul class="flex items-center space-x-2">';
    
    // 前へボタン
    if (isset($links['prev'])) {
        $html .= '<li><a href="' . htmlspecialchars($links['prev']) . '" class="px-3 py-2 bg-white border rounded hover:bg-gray-50">前へ</a></li>';
    } else {
        $html .= '<li><span class="px-3 py-2 bg-gray-100 border rounded text-gray-400">前へ</span></li>';
    }
    
    // ページ番号
    foreach ($links['pages'] as $page => $url) {
        if ($url === null) {
            $html .= '<li><span class="px-3 py-2">...</span></li>';
        } elseif ($page == $links['current_page']) {
            $html .= '<li><span class="px-3 py-2 bg-blue-500 text-white border rounded">' . $page . '</span></li>';
        } else {
            $html .= '<li><a href="' . htmlspecialchars($url) . '" class="px-3 py-2 bg-white border rounded hover:bg-gray-50">' . $page . '</a></li>';
        }
    }
    
    // 次へボタン
    if (isset($links['next'])) {
        $html .= '<li><a href="' . htmlspecialchars($links['next']) . '" class="px-3 py-2 bg-white border rounded hover:bg-gray-50">次へ</a></li>';
    } else {
        $html .= '<li><span class="px-3 py-2 bg-gray-100 border rounded text-gray-400">次へ</span></li>';
    }
    
    $html .= '</ul>';
    
    // 件数表示
    $html .= '<div class="mt-2 text-sm text-gray-600">';
    $html .= sprintf('全%s件中 %sページ目を表示', 
        number_format($links['total_count']), 
        number_format($links['current_page'])
    );
    $html .= '</div>';
    
    $html .= '</nav>';
    
    return $html;
}