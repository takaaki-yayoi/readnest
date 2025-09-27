# index.php パフォーマンス改善案

## 1. データベースクエリの最適化

### 現在の問題
- 新着レビュー取得時にN+1クエリ問題が発生（各レビューごとにユーザー情報を個別に取得）
- 不要なデータを取得している可能性

### 改善案

#### A. 新着レビューのクエリ最適化
```php
// 現在のコード（N+1問題）
foreach ($new_review_data as $review) {
    $new_reviews[] = array(
        'nickname' => getNickname($review['user_id']), // 個別クエリ
        'user_photo' => getProfilePhotoURL($review['user_id']), // 個別クエリ
    );
}

// 改善案：JOINでユーザー情報も一度に取得
$new_review_sql = "
    SELECT 
        bl.book_id, bl.name, bl.memo, bl.rating, bl.user_id, bl.image_url,
        u.nickname, u.photo_url
    FROM b_book_list bl
    INNER JOIN b_user u ON bl.user_id = u.user_id
    WHERE bl.memo != '' AND bl.memo IS NOT NULL
        AND u.diary_policy = 1
    ORDER BY bl.memo_updated DESC
    LIMIT 5
";
```

#### B. インデックスの追加提案
```sql
-- パフォーマンス向上のためのインデックス
ALTER TABLE b_book_list ADD INDEX idx_memo_updated (memo_updated);
ALTER TABLE b_book_list ADD INDEX idx_update_date (update_date);
ALTER TABLE b_book_list ADD INDEX idx_user_status (user_id, status);
ALTER TABLE b_user ADD INDEX idx_diary_policy (diary_policy);
```

## 2. キャッシュ戦略の改善

### 現在の実装
- 統計情報: 1時間
- 人気の本: 15分
- 最新の活動: 5分

### 改善案
```php
// キャッシュ時間の最適化
$cacheSettings = [
    'statistics' => 7200,    // 2時間（統計はあまり変わらない）
    'popular_books' => 1800, // 30分（人気の本もゆっくり変化）
    'recent_activities' => 300, // 5分（これは現状維持）
    'new_reviews' => 600,    // 10分（新規追加）
];

// 新着レビューもキャッシュ化
$reviewsCacheKey = 'new_reviews_with_users_v1';
$new_reviews = $cache->get($reviewsCacheKey);
if ($new_reviews === false) {
    // レビュー取得処理
    $cache->set($reviewsCacheKey, $new_reviews, $cacheSettings['new_reviews']);
}
```

## 3. 画像の最適化

### 現在の問題
- 本の画像がフルサイズで読み込まれている可能性

### 改善案
```php
// 画像のリサイズやCDN利用
function getOptimizedImageUrl($original_url, $size = 'medium') {
    // サムネイルサイズを指定
    if (strpos($original_url, 'images-jp.amazon.com') !== false) {
        // Amazonの画像は._SL160_.などのサイズ指定を追加
        return preg_replace('/\._.*_\./', '._SL160_.', $original_url);
    }
    return $original_url;
}
```

## 4. 非同期読み込みの実装

### 改善案
```javascript
// 重要でないコンテンツを遅延読み込み
document.addEventListener('DOMContentLoaded', function() {
    // 統計情報を非同期で更新
    fetch('/api/statistics')
        .then(response => response.json())
        .then(data => {
            document.getElementById('total-users').textContent = data.total_users;
            // ...
        });
});
```

## 5. データベース接続プーリング

### 改善案
```php
// persistent connection を使用
$dsn = 'mysql:host=localhost;dbname=readnest_db;charset=utf8';
$options = [
    PDO::ATTR_PERSISTENT => true, // 永続接続
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
];
```

## 6. クエリの最適化

### 統計クエリの最適化
```php
// 複数のクエリを1つにまとめる
$stats_sql = "
    SELECT 
        (SELECT COUNT(*) FROM b_user WHERE regist_date IS NOT NULL) as total_users,
        (SELECT COUNT(DISTINCT book_id) FROM b_book_list) as total_books,
        (SELECT COUNT(*) FROM b_book_list WHERE memo != '' AND memo IS NOT NULL) as total_reviews,
        (SELECT SUM(CASE WHEN status = ? THEN total_page ELSE current_page END) 
         FROM b_book_list WHERE current_page > 0) as total_pages_read
";
```

## 実装優先順位

1. **高優先度**（すぐに効果が見込める）
   - 新着レビューのN+1問題解決
   - キャッシュ時間の調整
   - 統計クエリの統合

2. **中優先度**
   - インデックスの追加
   - 画像の最適化

3. **低優先度**（大きな変更が必要）
   - 非同期読み込みの実装
   - データベース接続の最適化