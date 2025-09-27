# ReadNest DateTime Migration

このディレクトリには、ReadNestのデータベースで使用されている日付フィールドをUnix timestampからMySQL DATETIME型に移行するためのスクリプトが含まれています。

## なぜこの移行が必要なのか

1. **2038年問題の回避**: Unix timestampは2038年1月19日以降の日付を扱えません
2. **データの一貫性**: 現在、日付データの形式が混在しており、処理が複雑になっています
3. **将来の拡張性**: DATETIME型により、タイムゾーン対応などが容易になります

## 移行対象

### テーブルとカラム
- `b_user`
  - `create_date`: INT → DATETIME
  - `regist_date`: INT → DATETIME
- `b_book_list`
  - `update_date`: VARCHAR/INT → DATETIME
- `b_book_event`
  - `event_date`: INT → DATETIME

## 実行手順

### 1. バックアップの作成（必須）

```bash
# データベース全体のバックアップ
mysqldump -u [username] -p [database_name] > readnest_backup_$(date +%Y%m%d_%H%M%S).sql
```

### 2. PHPコードの更新確認

以下のファイルが更新されていることを確認してください：
- `library/database.php` - time()の代わりにNOW()を使用
- `library/date_helpers.php` - 日付表示用ヘルパー関数
- テンプレートファイル - formatDate()関数を使用

### 3. マイグレーションの実行

```bash
# マイグレーションスクリプトの実行
php migrations/run_datetime_migration.php
```

または、SQLを直接実行：

```bash
mysql -u [username] -p [database_name] < migrations/001_datetime_migration.sql
```

### 4. 動作確認

マイグレーション後、以下を確認してください：
- ユーザー登録が正常に動作すること
- 本の登録・更新が正常に動作すること
- 読書進捗の記録が正常に動作すること
- 日付が正しく表示されること

## トラブルシューティング

### エラーが発生した場合

1. バックアップから復元：
```bash
mysql -u [username] -p [database_name] < readnest_backup_[timestamp].sql
```

2. エラーログを確認して問題を特定

### よくある問題

- **カラムが既に存在するエラー**: 一部のみ実行された可能性があります。手動でクリーンアップが必要です
- **データ型の不一致**: 一部のデータが想定外の形式の可能性があります

## 移行後の注意事項

1. **パフォーマンス**: インデックスが再作成されるため、最初は若干遅くなる可能性があります
2. **キャッシュ**: 日付関連のキャッシュをクリアすることを推奨します
3. **モニタリング**: 移行後数日間はエラーログを注意深く監視してください

## 技術的詳細

### Unix timestampからDATETIMEへの変換

```sql
-- 有効なUnix timestampの変換
UPDATE table_name SET date_column = FROM_UNIXTIME(unix_timestamp_column)
WHERE unix_timestamp_column > 0 AND unix_timestamp_column < 2147483647;

-- 無効な値（2147483647）の処理
UPDATE table_name SET date_column = NOW()
WHERE unix_timestamp_column = 2147483647;
```

### PHPコードの変更

変更前：
```php
$current_time = time();
$sql = "INSERT INTO table (date_column) VALUES (?)";
$db->query($sql, array($current_time));
```

変更後：
```php
$sql = "INSERT INTO table (date_column) VALUES (NOW())";
$db->query($sql, array());
```

## サポート

問題が発生した場合は、エラーメッセージと実行したコマンドを記録して、開発チームに連絡してください。