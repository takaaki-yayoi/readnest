<?php
/**
 * 管理画面共通ヘルパー関数
 * PHP 8.2.28対応版
 */

declare(strict_types=1);

/**
 * 安全にデータベース結果を取得
 * @param mixed $result データベース結果
 * @param mixed $default エラー時のデフォルト値
 * @return mixed 安全な値
 */
function safeDbResult($result, $default = 0) {
    if (DB::isError($result)) {
        error_log('Database error: ' . $result->getMessage());
        return $default;
    }
    return $result;
}

/**
 * 安全に日付をフォーマット
 * @param mixed $date 日付値（文字列またはUnixタイムスタンプ）
 * @param string $format 日付フォーマット
 * @param string $default デフォルト値
 * @return string フォーマットされた日付またはデフォルト値
 */
function safeDate($date, string $format = 'Y/m/d', string $default = '-'): string {
    if (!$date) {
        return $default;
    }
    
    // Unixタイムスタンプの場合（数値または数値文字列）
    if (is_numeric($date)) {
        $timestamp = (int)$date;
        // 妥当なタイムスタンプかチェック（2000年以降、2100年以前）
        if ($timestamp > 946684800 && $timestamp < 4102444800) {
            return date($format, $timestamp);
        }
    }
    
    // 文字列の日付の場合
    if (is_string($date)) {
        // '0000-00-00' のような無効な日付をチェック
        if ($date === '0000-00-00' || $date === '0000-00-00 00:00:00') {
            return $default;
        }
        
        $timestamp = strtotime($date);
        if ($timestamp !== false && $timestamp > 0) {
            return date($format, $timestamp);
        }
    }
    
    return $default;
}

/**
 * 安全に数値をフォーマット
 * @param mixed $number 数値
 * @param int $decimals 小数点以下桁数
 * @return string フォーマットされた数値
 */
function safeNumber($number, int $decimals = 0): string {
    if (!is_numeric($number)) {
        return '0';
    }
    return number_format((float)$number, $decimals);
}

/**
 * 安全にHTMLエスケープ
 * @param mixed $value 値
 * @return string エスケープされた文字列
 */
function safeHtml($value): string {
    if ($value === null || $value === false) {
        return '';
    }
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/**
 * ステータスのラベルを取得
 * @param string $status ステータス
 * @param array $labels ラベル配列
 * @return string ラベル
 */
function getStatusLabel(string $status, array $labels): string {
    return $labels[$status] ?? $status;
}

/**
 * ページネーション情報を生成
 * @param int $current_page 現在のページ
 * @param int $total_count 総件数
 * @param int $per_page 1ページあたりの件数
 * @return array ページネーション情報
 */
function getPaginationInfo(int $current_page, int $total_count, int $per_page): array {
    // ゼロ除算を防ぐ
    if ($per_page <= 0) {
        $per_page = 20; // デフォルト値
    }
    
    $total_pages = (int)ceil($total_count / $per_page);
    $offset = ($current_page - 1) * $per_page;
    
    return [
        'current_page' => $current_page,
        'total_pages' => $total_pages,
        'total_count' => $total_count,
        'per_page' => $per_page,
        'offset' => $offset,
        'has_prev' => $current_page > 1,
        'has_next' => $current_page < $total_pages,
        'prev_page' => max(1, $current_page - 1),
        'next_page' => min($total_pages, $current_page + 1)
    ];
}

/**
 * SQLのWHERE条件を安全に構築
 * @param array $conditions 条件配列
 * @return string WHERE句
 */
function buildWhereClause(array $conditions): string {
    if (empty($conditions)) {
        return '';
    }
    return ' WHERE ' . implode(' AND ', $conditions);
}

/**
 * CSV出力用のヘッダーを設定
 * @param string $filename ファイル名
 */
function setCsvHeaders(string $filename): void {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: 0');
}

/**
 * 管理画面用のフラッシュメッセージを表示
 * @param string $type メッセージタイプ (success, error, warning, info)
 * @param string $message メッセージ内容
 */
function showFlashMessage(string $type, string $message): void {
    $icons = [
        'success' => 'fa-check-circle',
        'error' => 'fa-exclamation-circle',
        'warning' => 'fa-exclamation-triangle',
        'info' => 'fa-info-circle'
    ];
    
    $colors = [
        'success' => 'green',
        'error' => 'red',
        'warning' => 'yellow',
        'info' => 'blue'
    ];
    
    $icon = $icons[$type] ?? 'fa-info-circle';
    $color = $colors[$type] ?? 'blue';
    
    echo "<div class=\"mb-6 p-4 bg-{$color}-50 border border-{$color}-200 rounded-md alert-auto-hide\">";
    echo "<div class=\"flex\">";
    echo "<i class=\"fas {$icon} text-{$color}-400 mr-2\"></i>";
    echo "<p class=\"text-sm text-{$color}-800\">" . safeHtml($message) . "</p>";
    echo "</div>";
    echo "</div>";
}

/**
 * 管理画面用の確認ダイアログHTML
 * @param string $message 確認メッセージ
 * @param string $action アクション名
 * @param array $hidden_fields 隠しフィールド
 * @param string $button_text ボタンテキスト
 * @param string $button_class ボタンクラス
 * @return string HTMLフォーム
 */
function confirmationForm(string $message, string $action, array $hidden_fields = [], string $button_text = '実行', string $button_class = 'text-red-600 hover:text-red-900'): string {
    $html = "<form method=\"post\" action=\"\" class=\"inline-block\" onsubmit=\"return confirm('" . safeHtml($message) . "');\">";
    
    // CSRF token
    if (isset($_SESSION['csrf_token'])) {
        $html .= "<input type=\"hidden\" name=\"csrf_token\" value=\"" . safeHtml($_SESSION['csrf_token']) . "\">";
    }
    
    // Action
    $html .= "<input type=\"hidden\" name=\"action\" value=\"" . safeHtml($action) . "\">";
    
    // Hidden fields
    foreach ($hidden_fields as $name => $value) {
        $html .= "<input type=\"hidden\" name=\"" . safeHtml($name) . "\" value=\"" . safeHtml($value) . "\">";
    }
    
    $html .= "<button type=\"submit\" class=\"{$button_class}\">{$button_text}</button>";
    $html .= "</form>";
    
    return $html;
}

/**
 * 読書ステータスのラベルを取得
 * @param int $status ステータス値
 * @return string ラベル
 */
function getReadingStatusLabel(int $status): string {
    $labels = [
        BUY_SOMEDAY => 'いつか買う',
        NOT_STARTED => '積読',
        READING_NOW => '読書中',
        READING_FINISH => '読了',
        READ_BEFORE => '昔読んだ'
    ];
    
    return $labels[$status] ?? '不明';
}

/**
 * 読書ステータスのCSSクラスを取得
 * @param int $status ステータス値
 * @return string CSSクラス
 */
function getReadingStatusClass(int $status): string {
    $classes = [
        BUY_SOMEDAY => 'bg-gray-100 text-gray-800',
        NOT_STARTED => 'bg-yellow-100 text-yellow-800',
        READING_NOW => 'bg-blue-100 text-blue-800',
        READING_FINISH => 'bg-green-100 text-green-800',
        READ_BEFORE => 'bg-purple-100 text-purple-800'
    ];
    
    return $classes[$status] ?? 'bg-gray-100 text-gray-800';
}
?>