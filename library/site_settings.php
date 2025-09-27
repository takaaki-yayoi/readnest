<?php
/**
 * サイト設定を管理するライブラリ
 * PHP 5.6以上対応版
 */

/**
 * サイト設定を取得
 */
function getSiteSettings() {
    $default_settings = array(
        'show_latest_activities' => true,
        'show_new_reviews' => true,
        'show_popular_books' => true,
        'show_popular_tags' => true
    );
    
    $settings_file = dirname(__DIR__) . '/config/site_settings.json';
    
    if (file_exists($settings_file)) {
        $loaded_settings = json_decode(file_get_contents($settings_file), true);
        if ($loaded_settings) {
            // 設定値をbooleanに変換
            foreach ($loaded_settings as $key => $value) {
                if (in_array($key, array('show_latest_activities', 'show_new_reviews', 'show_popular_books', 'show_popular_tags'))) {
                    $loaded_settings[$key] = (bool)$value;
                }
            }
            return array_merge($default_settings, $loaded_settings);
        }
    }
    
    return $default_settings;
}

/**
 * 特定の設定値を取得
 */
function getSiteSetting($key, $default = null) {
    $settings = getSiteSettings();
    return isset($settings[$key]) ? $settings[$key] : $default;
}

/**
 * サイト設定を保存
 */
function setSiteSettings($settings) {
    $settings_file = dirname(__DIR__) . '/config/site_settings.json';
    $settings_dir = dirname($settings_file);
    
    // ディレクトリが存在しない場合は作成
    if (!is_dir($settings_dir)) {
        mkdir($settings_dir, 0755, true);
    }
    
    return file_put_contents($settings_file, json_encode($settings, JSON_PRETTY_PRINT));
}

/**
 * 特定の設定値を更新
 */
function setSiteSetting($key, $value) {
    $settings = getSiteSettings();
    $settings[$key] = $value;
    return setSiteSettings($settings);
}

/**
 * 最新の活動セクションが有効か確認
 */
function isLatestActivitiesEnabled() {
    return getSiteSetting('show_latest_activities', true);
}

/**
 * 新着レビューセクションが有効か確認
 */
function isNewReviewsEnabled() {
    return getSiteSetting('show_new_reviews', true);
}

/**
 * 人気の本セクションが有効か確認
 */
function isPopularBooksEnabled() {
    return getSiteSetting('show_popular_books', true);
}

/**
 * 人気のタグセクションが有効か確認
 */
function isPopularTagsEnabled() {
    return getSiteSetting('show_popular_tags', true);
}
?>