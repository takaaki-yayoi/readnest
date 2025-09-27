# ReadNest タイムスタンプ移行ガイド

## 概要

ReadNestプロジェクトにおける2038年問題対策とタイムスタンプ処理の改善について説明します。

## 問題の背景

### 2038年問題とは
- Unix timestampは32ビット符号付き整数で表現される
- 最大値は2147483647（2038年1月19日 03:14:07 UTC）
- この値を超えるとオーバーフローが発生

### 影響を受けるテーブルとカラム

1. **b_user**
   - `create_date`: ユーザー登録日
   - `regist_date`: 本登録日

2. **b_book_list**
   - `create_date`: 本の登録日
   - `update_date`: 最終更新日
   - `memo_updated`: メモの更新日

3. **b_book_event**
   - `event_date`: イベント発生日

## 実施した対策

### 1. データベース層での対策

#### Unix timestampからDATETIMEへの変換
- すべての日付カラムをDATETIME型に移行
- 既存のUnix timestampデータをFROM_UNIXTIME()で変換

#### 修正スクリプト
```bash
# 包括的な修正スクリプトの実行
php fix_all_timestamp_issues.php

# 個別の修正スクリプト
mysql readnest_db < sql/fix_date_2038_problem.sql  # SQLのみ
php update_date_fixer.php      # b_book_listの修正
php fix_user_dates_immediately.php  # b_userの修正
```

### 2. アプリケーション層での対策

#### time()関数の置き換え
以下の関数でtime()をNOW()に置き換えました：

1. **createBook()** (library/database.php)
   - 変更前: `$current_time = time();`
   - 変更後: SQL内で`NOW()`を使用

2. **boughtBook()** (library/database.php)
   - 変更前: `$update_time = time();`
   - 変更後: SQL内で`NOW()`を使用

3. **updateBook()** (library/database.php)
   - 変更前: `$update_time = time();`
   - 変更後: SQL内で`NOW()`を使用

4. **createEvent()** (library/database.php)
   - 変更前: `$event_time = time();`
   - 変更後: SQL内で`NOW()`を使用

#### 不要なtime()の削除
- addProfilePhoto()
- removeProfilePhoto()
- registerProfilePhoto()

これらの関数で定義されていた`$create_date = time();`は実際に使用されていなかったため削除。

### 3. ヘルパー関数の導入

`library/date_helpers.php`に以下の関数を実装：

- **formatDate()**: Unix timestampとDATETIME両方に対応した日付フォーマット
- **formatRelativeTime()**: 相対時間表示（「3時間前」など）
- **compareDates()**: 安全な日付比較
- **isValidDate()**: 日付の妥当性チェック

## 今後の開発指針

### ✅ 推奨事項

1. **新規開発時**
   - 常にDATETIME型を使用
   - SQL内でNOW()関数を使用
   - PHPではDateTimeクラスを使用

2. **日付の保存**
   ```php
   // 推奨
   $sql = "INSERT INTO table (created_at) VALUES (NOW())";
   
   // 非推奨
   $time = time();
   $sql = "INSERT INTO table (created_at) VALUES ($time)";
   ```

3. **日付の表示**
   ```php
   // date_helpers.phpの関数を使用
   echo formatDate($row['update_date']);
   echo formatRelativeTime($row['create_date']);
   ```

### ❌ 避けるべきこと

1. time()関数の使用（セッション管理以外）
2. Unix timestampの直接保存
3. INT型での日付カラム定義

## 定期メンテナンス

### 月次チェック
```bash
# データベース健全性チェック
php database_health_check.php
```

### 問題が見つかった場合
```bash
# 包括的修正スクリプトの実行
php fix_all_timestamp_issues.php
```

## セッション管理について

`library/session.php`でのtime()使用は継続：
- セッションの有効期限管理は短期的
- 2038年問題の影響を受けにくい
- 変更の必要なし

## 移行状況の確認

### SQLでの確認方法
```sql
-- Unix timestampの残存確認
SELECT COUNT(*) FROM b_book_list 
WHERE update_date REGEXP '^[0-9]+$';

-- 2038年データの確認
SELECT COUNT(*) FROM b_book_list 
WHERE update_date LIKE '2038%';
```

## トラブルシューティング

### Q: まだ2038年の日付が表示される
A: `fix_all_timestamp_issues.php`を実行してください

### Q: 新規登録時にエラーが発生
A: library/database.phpが最新版か確認してください

### Q: 古いデータの日付が正しく表示されない
A: date_helpers.phpをインクルードして、formatDate()関数を使用してください

## 参考資料

- [2038年問題 - Wikipedia](https://ja.wikipedia.org/wiki/2038%E5%B9%B4%E5%95%8F%E9%A1%8C)
- [PHP DateTime クラス](https://www.php.net/manual/ja/class.datetime.php)
- [MySQL 日時データ型](https://dev.mysql.com/doc/refman/8.0/ja/datetime.html)