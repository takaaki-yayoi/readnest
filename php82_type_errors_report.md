# PHP 8.2 型エラーになりやすい関数の調査結果

## 概要
PHP 8.2では型チェックが厳密になり、NULL値や不適切な型を渡すとエラーになる関数があります。
以下は、ReadNestプロジェクトで修正が必要な箇所のリストです。

## 修正が必要な箇所

### 1. strlen() / mb_strlen() - NULLを渡すとエラー

#### 高優先度（エラーになる可能性が高い）

1. **search_review.php (L87, L135)**
   ```php
   $result['short_memo'] = mb_strlen($result['memo']) > 100 ? ...
   ```
   - `$result['memo']` がNULLの可能性
   - 修正案: `mb_strlen($result['memo'] ?? '') > 100`

2. **api/reading_progress_api.php (L98)**
   ```php
   'content' => mb_substr($event['comment'], 0, 20) . (mb_strlen($event['comment']) > 20 ? '...' : '')
   ```
   - `$event['comment']` がNULLの可能性
   - 修正案: `mb_strlen($event['comment'] ?? '') > 20`

### 2. count() - NULLを渡すとエラー

#### 高優先度（エラーになる可能性が高い）

1. **announcements.php (L39)**
   ```php
   $total_announcements = count($announcements);
   ```
   - エラー発生時に `$announcements = [];` で初期化しているが、データベース接続失敗時にNULLの可能性
   - 修正案: `count($announcements ?? [])`

2. **reading_calendar.php (L132)**
   ```php
   $total_reading_days = count($reading_days);
   ```
   - `$reading_days` がNULLの可能性
   - 修正案: `count($reading_days ?? [])`

3. **bookshelf.php (L137)**
   ```php
   $enable_tag_display = !isset($_GET['disable_tags']) && count($books) <= 100;
   ```
   - `$books` がNULLの可能性
   - 修正案: `count($books ?? []) <= 100`

### 3. array_keys() / array_values() - NULLを渡すとエラー

#### 中優先度

1. **admin/check_user_12_stats.php (L235)**
   ```php
   print_r(array_keys($debug_user));
   ```
   - `$debug_user` がNULLの可能性
   - 修正案: `array_keys($debug_user ?? [])`

2. **admin/check_tables.php (L134)**
   ```php
   echo "<p>利用可能なカラム: " . implode(', ', array_keys($sample)) . "</p>";
   ```
   - `$sample` がFALSEの可能性（fetch失敗時）
   - 修正案: `array_keys($sample ?: [])`

### 4. explode() - 型が厳密にチェックされる

#### 高優先度（エラーになる可能性が高い）

1. **reading_calendar.php (L109-111)**
   ```php
   $book_ids = explode(',', $day['book_ids']);
   $book_names = explode('|||', $day['book_names']);
   $book_images = explode('|||', $day['book_images']);
   ```
   - `$day['book_ids']`、`$day['book_names']`、`$day['book_images']` がNULLの可能性
   - 修正案: `explode(',', $day['book_ids'] ?? '')`

2. **api/user_tags.php (L171)**
   ```php
   $tag['statuses'] = !empty($tag['statuses']) ? explode(',', $tag['statuses']) : [];
   ```
   - すでに対策済み（`!empty()` チェック）

3. **library/genre_detector.php (L225)**
   ```php
   $tags = explode(',', $result['tags']);
   ```
   - `$result['tags']` がNULLの可能性
   - 修正案: `explode(',', $result['tags'] ?? '')`

### 5. implode() - 型が厳密にチェックされる

#### 中優先度

1. **bookshelf.php (L146)**
   ```php
   $placeholders = implode(',', array_fill(0, count($limited_book_ids), '?'));
   ```
   - `$limited_book_ids` がNULLの場合、`count()` でエラーになる
   - 修正案: `count($limited_book_ids ?? [])`

2. **cron/clear_activities_cache.php (L59)**
   ```php
   addLog("問題のあるユーザーID: " . implode(', ', array_unique($problematicUsers)));
   ```
   - `$problematicUsers` がNULLの可能性は低い（配列として初期化されている）

### 6. str_replace() / preg_replace() - 型が厳密にチェックされる

#### 低優先度（既に型チェックされている場合が多い）

1. **library/amazon.php (L9)**
   ```php
   return str_replace('%7E', '~', rawurlencode((string)$str));
   ```
   - すでに `(string)` でキャストしているため安全

2. **api/reading_suggestions.php (L259-260)**
   ```php
   $cleanResponse = preg_replace('/```json\s*/', '', $cleanResponse);
   ```
   - `$cleanResponse` がNULLの可能性は低い（前処理済み）

## 推奨される修正方針

1. **NULL合体演算子の活用**
   ```php
   // 修正前
   mb_strlen($value) > 100
   
   // 修正後
   mb_strlen($value ?? '') > 100
   ```

2. **型キャストの活用**
   ```php
   // 修正前
   count($array)
   
   // 修正後
   count((array)$array)
   ```

3. **事前チェックの追加**
   ```php
   if (!empty($value) && mb_strlen($value) > 100) {
       // 処理
   }
   ```

## 優先順位

1. **最優先**: search_review.php, reading_calendar.php, announcements.php
2. **高優先**: api/reading_progress_api.php, bookshelf.php
3. **中優先**: 管理画面系のファイル（admin/配下）
4. **低優先**: 既に型チェックが入っているファイル

これらの修正により、PHP 8.2での型エラーを防ぐことができます。