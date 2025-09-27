# ReadNest キャッシュ実装分析レポート

## 概要
ReadNestのindex.phpとcache_warmer.phpにおけるキャッシュ実装を分析しました。

## 1. キャッシュのヒット率

### 現在の実装
- **キャッシュシステム**: シンプルなファイルベースキャッシュ（`SimpleCache`クラス）
- **保存場所**: `/cache`ディレクトリ内にMD5ハッシュ化されたファイル名で保存
- **シリアライズ形式**: PHPのserialize/unserialize関数を使用

### ヒット率を向上させる要因
1. **適切なTTL設定**:
   - サイト統計: 24時間（変化が少ない）
   - 新着レビュー: 10分（頻繁に更新される）
   - 人気の本: 1時間（中程度の更新頻度）
   - 最新活動: 5分（リアルタイム性重視）

2. **cache_warmer.phpによる事前キャッシュ**:
   - cronで定期的に実行してキャッシュを温める
   - ユーザーアクセス時にはキャッシュヒットする可能性が高い

### ヒット率の問題点
- キャッシュ統計機能（`getStats()`）はあるが、実際のヒット/ミス率を記録していない
- ログにキャッシュヒット/ミスの情報が記録されていない

## 2. キャッシュミス時のクエリの重さ

### サイト統計クエリ（最も軽い）
```sql
SELECT 
    (SELECT COUNT(*) FROM b_user WHERE regist_date IS NOT NULL) as total_users,
    (SELECT COUNT(DISTINCT book_id) FROM b_book_list) as total_books,
    (SELECT COUNT(*) FROM b_book_list WHERE memo != '' AND memo IS NOT NULL) as total_reviews,
    (SELECT SUM(CASE WHEN status = ? THEN total_page ELSE current_page END) 
     FROM b_book_list WHERE current_page > 0) as total_pages_read
```
- **パフォーマンス**: 中程度
- **問題点**: 4つのサブクエリを使用しているが、それぞれがフルテーブルスキャンの可能性
- **改善案**: インデックスの追加（特に`b_book_list.memo`、`b_book_list.current_page`）

### 新着レビュークエリ（最適化済み）
```sql
SELECT bl.*, 
       bu.user_id, bu.diary_policy, bu.nickname, 
       bu.photo
FROM b_book_list bl
INNER JOIN b_user bu ON bl.user_id = bu.user_id
WHERE bl.memo IS NOT NULL 
    AND bl.memo != '' 
    AND bu.diary_policy = ?
ORDER BY bl.memo_updated DESC, bl.update_date DESC 
LIMIT 5
```
- **パフォーマンス**: 良好（最適化済み）
- **改善点**: 
  - N+1問題を解決済み（JOINで一度に取得）
  - `memo_updated`と`update_date`にインデックスが必要
  - `WHERE memo IS NOT NULL AND memo != ''`が非効率（関数インデックスまたはフラグカラムの追加を検討）

### 人気の本クエリ（最も重い）
```sql
SELECT 
    MIN(bl.book_id) as book_id,
    bl.name as title,
    bl.image_url,
    COUNT(DISTINCT bl.user_id) as bookmark_count
FROM b_book_list bl
INNER JOIN b_user u ON bl.user_id = u.user_id
WHERE u.diary_policy = 1 
    AND bl.name IS NOT NULL 
    AND bl.name != ''
    AND bl.image_url IS NOT NULL
    AND bl.image_url != ''
    AND bl.image_url NOT LIKE '%noimage%'
GROUP BY bl.name, bl.image_url
HAVING COUNT(DISTINCT bl.user_id) > 0
ORDER BY bookmark_count DESC, MAX(bl.update_date) DESC
LIMIT 9
```
- **パフォーマンス**: 非常に重い
- **問題点**:
  1. `GROUP BY`で`name`と`image_url`という長いテキストカラムを使用
  2. `COUNT(DISTINCT)`の使用
  3. `LIKE '%noimage%'`のワイルドカード検索
  4. 複数の`IS NOT NULL`チェック
- **改善案**:
  1. 本の正規化（別テーブルで管理）
  2. 集計テーブルの作成
  3. `image_url`のフラグカラム追加（has_image）

### 最新活動クエリ（インデックス最適化済み）
```sql
SELECT 
    be.book_id, be.event_date, be.event, be.memo, be.page, be.user_id,
    bl.name as book_name, bl.image_url as book_image_url,
    u.nickname, u.photo as user_photo, u.photo_url as user_photo_url
FROM b_book_event be USE INDEX (idx_event_date)
INNER JOIN b_user u ON be.user_id = u.user_id
LEFT JOIN b_book_list bl ON be.book_id = bl.book_id AND be.user_id = bl.user_id
WHERE u.diary_policy = 1 
    AND be.event IN (?, ?)
ORDER BY be.event_date DESC
LIMIT 10
```
- **パフォーマンス**: 良好
- **利点**: 
  - インデックスヒント使用
  - LEFT JOINで本情報がなくても取得可能

## 3. キャッシュキーの設計

### 現在のキー設計
- `site_statistics_v1`
- `new_reviews_v3`
- `popular_reading_books_v1`
- `recent_activities_formatted_v3`
- `popular_tags_v1`

### 評価
- **良い点**:
  - バージョン番号付き（スキーマ変更時の対応）
  - 意味のある名前
  - グローバルキー（ユーザー固有ではない）

- **改善点**:
  - ユーザー固有のキャッシュがない（パーソナライズに対応できない）
  - 言語やデバイス別のキャッシュがない

## 4. キャッシュの有効期限

### 現在の設定
| キャッシュ | TTL | 評価 |
|---------|-----|-----|
| サイト統計 | 24時間 | 適切（統計は頻繁に変わらない） |
| 新着レビュー | 10分 | 適切（リアルタイム性と負荷のバランス） |
| 人気の本 | 1時間 | やや長い（30分程度でも良い） |
| 最新活動 | 5分 | 適切（リアルタイム性重視） |
| 人気タグ | 30分 | 適切 |

## 5. パフォーマンスボトルネック

### 主要な問題
1. **人気の本クエリ**
   - GROUP BYの最適化が必要
   - 正規化されていないデータ構造

2. **キャッシュミス時の同時実行**
   - キャッシュスタンピード問題への対策なし
   - 複数のリクエストが同時に重いクエリを実行する可能性

3. **インデックスの不足**
   - `b_book_list.memo`
   - `b_book_list.memo_updated`
   - 複合インデックスの不足

## 6. 改善提案

### 短期的改善
1. **キャッシュヒット率のモニタリング追加**
```php
public function get($key) {
    // ヒット/ミスをログに記録
    $hit = $this->has($key);
    error_log("Cache " . ($hit ? "HIT" : "MISS") . ": " . $key);
    // ...
}
```

2. **人気の本クエリの最適化**
   - 集計用の中間テーブル作成
   - またはマテリアライズドビューの使用

3. **キャッシュスタンピード対策**
```php
// ロック機構の追加
public function getOrSet($key, $callback, $ttl = null) {
    $value = $this->get($key);
    if ($value !== false) return $value;
    
    $lockKey = $key . '.lock';
    if ($this->acquireLock($lockKey)) {
        $value = $callback();
        $this->set($key, $value, $ttl);
        $this->releaseLock($lockKey);
    } else {
        // 他のプロセスが更新中なので待機
        sleep(1);
        return $this->get($key);
    }
    return $value;
}
```

### 長期的改善
1. **Redis/Memcachedへの移行**
   - より高速なインメモリキャッシュ
   - 分散キャッシュ対応

2. **データベース構造の最適化**
   - 本情報の正規化
   - 集計テーブルの追加

3. **CDNの活用**
   - 静的コンテンツのキャッシュ
   - エッジキャッシュの活用

## まとめ
現在のキャッシュ実装は基本的な機能を備えていますが、モニタリングとパフォーマンス最適化の余地があります。特に「人気の本」クエリは大幅な改善が必要です。