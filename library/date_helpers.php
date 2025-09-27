<?php
/**
 * 日付ヘルパー関数
 * Unix timestampとDATETIME形式の両方に対応
 */

/**
 * 日付を適切にフォーマットして表示
 * Unix timestampとDATETIME形式の両方に対応
 * 
 * @param mixed $date Unix timestampまたはDATETIME文字列
 * @param string $format 出力フォーマット
 * @return string フォーマット済み日付
 */
function formatDate($date, $format = 'Y年n月j日 H:i') {
    if (empty($date)) {
        return '';
    }
    
    // Unix timestamp (数値) の場合
    if (is_numeric($date)) {
        $timestamp = (int)$date;
        
        // 2038年問題の範囲をチェック
        if ($timestamp > 2147483647) {
            // 2038年以降の不正な値の場合は現在日時を返す
            return date($format);
        }
        
        return date($format, $timestamp);
    }
    
    // DATETIME文字列の場合
    try {
        $datetime = new DateTime($date);
        return $datetime->format($format);
    } catch (Exception $e) {
        // パースできない場合はそのまま返す
        return $date;
    }
}

/**
 * 相対時間を表示（「3時間前」など）
 * 
 * @param mixed $date Unix timestampまたはDATETIME文字列
 * @return string 相対時間
 */
function formatRelativeTime($date) {
    if (empty($date)) {
        return '';
    }
    
    // Unix timestamp (数値) の場合
    if (is_numeric($date)) {
        $timestamp = (int)$date;
        
        // 2038年問題の範囲をチェック
        if ($timestamp > 2147483647) {
            return '不明';
        }
        
        $datetime = new DateTime();
        $datetime->setTimestamp($timestamp);
    } else {
        // DATETIME文字列の場合
        try {
            $datetime = new DateTime($date);
        } catch (Exception $e) {
            return $date;
        }
    }
    
    $now = new DateTime();
    $diff = $now->diff($datetime);
    
    if ($diff->days > 7) {
        return $datetime->format('Y年n月j日');
    } elseif ($diff->days > 0) {
        return $diff->days . '日前';
    } elseif ($diff->h > 0) {
        return $diff->h . '時間前';
    } elseif ($diff->i > 0) {
        return $diff->i . '分前';
    } else {
        return 'たった今';
    }
}

/**
 * 安全な日付比較
 * 
 * @param mixed $date1 日付1
 * @param mixed $date2 日付2
 * @return int -1, 0, 1 (比較結果)
 */
function compareDates($date1, $date2) {
    try {
        $dt1 = is_numeric($date1) ? new DateTime('@' . $date1) : new DateTime($date1);
        $dt2 = is_numeric($date2) ? new DateTime('@' . $date2) : new DateTime($date2);
        
        if ($dt1 < $dt2) return -1;
        if ($dt1 > $dt2) return 1;
        return 0;
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * 日付が有効かどうかをチェック
 * 
 * @param mixed $date チェックする日付
 * @return bool 有効かどうか
 */
function isValidDate($date) {
    if (empty($date)) {
        return false;
    }
    
    if (is_numeric($date)) {
        $timestamp = (int)$date;
        return $timestamp > 0 && $timestamp <= 2147483647;
    }
    
    try {
        new DateTime($date);
        return true;
    } catch (Exception $e) {
        return false;
    }
}