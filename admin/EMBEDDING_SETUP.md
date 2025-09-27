# エンベディング生成システムのセットアップガイド

## 概要
このガイドでは、ReadNestの本レコメンデーション機能を強化するためのエンベディング生成システムのセットアップ方法を説明します。

## 必要な設定

### 1. OpenAI APIキーの設定

config.phpの92行目付近に以下の設定があります：

```php
define('OPENAI_API_KEY', 'your-actual-api-key-here');
```

**重要**: 現在設定されているAPIキーは無効です。有効なOpenAI APIキーに置き換える必要があります。

OpenAI APIキーの取得方法：
1. https://platform.openai.com/api-keys にアクセス
2. アカウントにログイン（またはサインアップ）
3. 「Create new secret key」をクリック
4. 生成されたキー（sk-...で始まる）をコピー
5. config.phpの該当箇所に貼り付け

### 2. Google Books APIキー（設定済み）

Google Books APIキーはすでに設定されています（config.php 96行目）。
このAPIキーは書籍の説明文を取得するために使用されます。

## スクリプトの使用方法

### ローカル環境でのテスト

#### APIのみのテスト（データベース不要）
```bash
php /Users/yayoi/workspace/subversion/readnest/admin/test_api_only.php
```

このスクリプトは：
- OpenAI APIの接続をテスト
- Google Books APIの接続をテスト
- データベース接続は不要

### サーバー環境での実行

#### 1. テストスクリプト
サーバー上でAPIとデータベースの接続をテスト：
```bash
php /path/to/readnest/admin/test_embedding_cli.php
```

#### 2. エンベディング生成スクリプト
user_id=12の高評価本（★4以上）のエンベディングを生成：
```bash
# 20件処理
php /path/to/readnest/admin/generate_enhanced_embeddings_cli.php 20

# 50件処理
php /path/to/readnest/admin/generate_enhanced_embeddings_cli.php 50
```

### データベースのクリア

すべてのエンベディングデータをクリアする場合：
```sql
-- /admin/clear_embeddings.sql の内容を実行
UPDATE b_book_repository 
SET 
    combined_embedding = NULL,
    title_embedding = NULL,
    description_embedding = NULL,
    embedding_generated_at = NULL,
    embedding_model = NULL
WHERE 1=1;
```

## スクリプトの機能

### generate_enhanced_embeddings_cli.php
このスクリプトは以下の処理を行います：

1. **書籍情報の取得**
   - user_id=12の★4以上の評価の本を対象
   - ASINがある本のみ処理

2. **Google Books APIからの説明文取得**
   - 書籍のISBN/ASINを使用して説明文を取得
   - 説明文がない場合はタイトルのみ使用

3. **3種類のエンベディング生成**
   - `title_embedding`: タイトルのみのエンベディング
   - `description_embedding`: 説明文のエンベディング（説明文がある場合）
   - `combined_embedding`: タイトル+説明文の結合エンベディング

4. **進捗表示**
   - 処理中の本の情報を表示
   - 成功/失敗の統計を表示

## トラブルシューティング

### "OPENAI_API_KEY not found"エラー
- config.phpにOpenAI APIキーが設定されていることを確認
- APIキーが正しい形式（sk-...）であることを確認

### "Database connection failed"エラー
- ローカル環境では発生します（正常）
- サーバー環境で発生する場合は、データベース設定を確認

### "OpenAI API error (HTTP 401)"エラー
- APIキーが無効です
- 新しいAPIキーを生成して設定してください

### "No description found in Google Books"
- その本に関する説明文がGoogle Booksにない場合
- タイトルのみでエンベディングが生成されます

## 管理画面での確認

管理画面からエンベディングの生成状況を確認できます：
- `/admin/embedding_debug.php` - エンベディング検索のデバッグ
- `/admin/generate_enhanced_embeddings.php` - Web UIでの生成（進捗確認のみ推奨）

## 注意事項

1. **APIレート制限**
   - OpenAI API: 1秒間に多数のリクエストを送らないよう、スクリプトには1秒のスリープが入っています
   - Google Books API: 1日あたりの無料枠に制限があります

2. **コスト**
   - OpenAI APIは有料です（text-embedding-3-smallモデル使用）
   - Google Books APIは無料枠内で使用可能

3. **処理時間**
   - 1冊あたり約2-3秒かかります
   - 100冊処理する場合、約5分程度必要

## 次のステップ

1. OpenAI APIキーを有効なものに設定
2. ローカルでAPIテストを実行して接続確認
3. スクリプトをサーバーにアップロード
4. サーバー上でエンベディング生成を実行
5. 管理画面で生成状況を確認

## サポート

問題が発生した場合は、以下の情報を確認してください：
- エラーログ: `/home/icotfeels/readnest.jp/log/dokusho_error_log.txt`
- PHP バージョン: 8.2以上推奨
- 必要な拡張: curl, json, pdo_mysql