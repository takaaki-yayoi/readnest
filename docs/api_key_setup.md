# APIキーの設定方法

## 設定ファイル
すべてのAPIキーは `config.php` に設定します。

## 1. OpenAI APIキー（既に設定済み）

現在の設定：
```php
define('OPENAI_API_KEY', getenv('OPENAI_API_KEY') ?: 'sk-proj-...');
```

### 推奨設定方法：
環境変数で設定（セキュリティ向上）：
```bash
export OPENAI_API_KEY="your-actual-api-key"
```

または直接設定：
```php
define('OPENAI_API_KEY', 'sk-proj-あなたのAPIキー');
```

## 2. Google Books APIキー（新規追加）

`config.php` の適切な場所（例：OpenAI APIキーの下）に追加：

```php
// Google Books API設定（オプション - なくても動作します）
define('GOOGLE_BOOKS_API_KEY', 'AIzaSy...');
```

### 環境変数を使う場合（推奨）：
```php
// Google Books API設定
define('GOOGLE_BOOKS_API_KEY', getenv('GOOGLE_BOOKS_API_KEY') ?: '');
```

そして環境変数を設定：
```bash
export GOOGLE_BOOKS_API_KEY="AIzaSy..."
```

## config.php の例

```php
<?php
// ... 既存の設定 ...

// === 外部API設定 ===

// OpenAI API設定（エンベディング、AI推薦用）
define('OPENAI_API_KEY', getenv('OPENAI_API_KEY') ?: 'sk-proj-...');

// Google Books API設定（書籍情報取得用）
// 完全無料、設定は任意（なくても動作）
define('GOOGLE_BOOKS_API_KEY', getenv('GOOGLE_BOOKS_API_KEY') ?: '');

// ... 他の設定 ...
```

## 設定の確認方法

### 1. 管理画面で確認
- 説明文更新: `/admin_update_descriptions.php`
- エンベディング: `/admin_embeddings.php`

各管理画面でAPIキーの設定状態が表示されます。

### 2. PHPで確認
```php
// APIキーが設定されているか確認
if (defined('GOOGLE_BOOKS_API_KEY') && !empty(GOOGLE_BOOKS_API_KEY)) {
    echo "Google Books APIキーが設定されています";
} else {
    echo "Google Books APIキーが設定されていません";
}
```

## セキュリティ上の注意

1. **Gitにコミットしない**
   - `.gitignore` に `config.php` を追加
   - または環境変数を使用

2. **本番環境では環境変数を推奨**
   ```bash
   # .env ファイルや環境設定で
   export OPENAI_API_KEY="sk-..."
   export GOOGLE_BOOKS_API_KEY="AIzaSy..."
   ```

3. **APIキーの制限を設定**
   - Google Cloud Console でHTTPリファラー制限
   - OpenAI Dashboard で使用制限を設定

## トラブルシューティング

### APIキーが認識されない場合
1. `config.php` の構文エラーを確認
2. キーの前後に不要な空白がないか確認
3. Apache/Nginxを再起動
4. PHPのキャッシュをクリア

### 環境変数が読み込まれない場合
1. Webサーバーのユーザー権限を確認
2. `.htaccess` や `php.ini` の設定を確認
3. `phpinfo()` で環境変数を確認