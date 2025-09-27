# データベースセットアップ

## エンベディング機能の有効化

エンベディングベースの類似本検索を使用するには、データベースに新しいカラムを追加する必要があります。

### 1. SQLファイルの実行

以下のコマンドを実行してデータベースを更新してください：

```bash
# ローカル環境の場合
mysql -u root -p readnest < sql/add_embedding_columns.sql

# 本番環境の場合
mysql -u icotfeels_book -p icotfeels_book < sql/add_embedding_columns.sql
```

### 2. 追加されるカラム

`b_book_repository`テーブルに以下のカラムが追加されます：

| カラム名 | 型 | 説明 |
|----------|-----|------|
| `title_embedding` | TEXT | タイトルのエンベディング（JSON配列） |
| `description_embedding` | TEXT | 説明文のエンベディング（JSON配列） |
| `combined_embedding` | TEXT | タイトル+説明文の結合エンベディング |
| `embedding_generated_at` | TIMESTAMP | エンベディング生成日時 |
| `embedding_model` | VARCHAR(50) | 使用したモデル名 |

### 3. エラーが発生する場合

もしカラムが既に存在するというエラーが出る場合は、以下のSQLで確認できます：

```sql
-- カラムの存在確認
SHOW COLUMNS FROM b_book_repository LIKE '%embedding%';

-- 既存のカラムを削除する場合（注意：データが失われます）
ALTER TABLE b_book_repository 
DROP COLUMN IF EXISTS title_embedding,
DROP COLUMN IF EXISTS description_embedding,
DROP COLUMN IF EXISTS combined_embedding,
DROP COLUMN IF EXISTS embedding_generated_at,
DROP COLUMN IF EXISTS embedding_model;
```

### 4. 動作確認

管理画面でエンベディングの統計が表示されれば成功です：
- `/admin/embeddings.php`にアクセス
- 「総書籍数」などの統計が表示されることを確認

## トラブルシューティング

### エラー: Unknown column 'combined_embedding'

エンベディング用のカラムが追加されていません。上記のSQLファイルを実行してください。

### エラー: Cannot use object of type DB_Error as array

データベース接続エラーです。以下を確認してください：
1. データベースの接続情報が正しいか（config.php）
2. MySQLサーバーが起動しているか
3. ユーザーに適切な権限があるか

### 統計が0のまま

1. 書籍データが存在するか確認
```sql
SELECT COUNT(*) FROM b_book_repository;
```

2. 説明文が存在するか確認
```sql
SELECT COUNT(*) FROM b_book_repository WHERE description IS NOT NULL;
```

3. エンベディングが生成されているか確認
```sql
SELECT COUNT(*) FROM b_book_repository WHERE combined_embedding IS NOT NULL;
```