# Google Books API キーの設定方法

## 1. Google Cloud Consoleでプロジェクトを作成

1. [Google Cloud Console](https://console.cloud.google.com/)にアクセス
2. Googleアカウントでログイン
3. 「プロジェクトを作成」をクリック
4. プロジェクト名を入力（例：`readnest-books`）
5. 「作成」をクリック

## 2. Google Books APIを有効化

1. 左側メニューから「APIとサービス」→「ライブラリ」を選択
2. 検索バーに「Google Books API」と入力
3. 「Google Books API」を選択
4. 「有効にする」ボタンをクリック

## 3. APIキーを作成

1. 左側メニューから「APIとサービス」→「認証情報」を選択
2. 「+ 認証情報を作成」→「APIキー」をクリック
3. APIキーが生成される（例：`AIzaSyD...`）
4. 「キーを制限」をクリック（推奨）

## 4. APIキーの制限設定（推奨）

### アプリケーションの制限
- **HTTPリファラー**を選択
- ウェブサイトの制限に追加：
  ```
  https://readnest.jp/*
  http://localhost/*
  ```

### APIの制限
- 「キーを制限」を選択
- 「Google Books API」のみにチェック

## 5. ReadNestへの設定

`config.php`に以下を追加：

```php
// Google Books API設定
define('GOOGLE_BOOKS_API_KEY', 'AIzaSyD...(あなたのAPIキー)');
```

## 6. 割り当ての増加申請（必要に応じて）

1日1,000回以上必要な場合：

1. Google Cloud Consoleで「APIとサービス」→「割り当て」
2. Google Books APIを選択
3. 「割り当ての増加をリクエスト」
4. 理由を記入して申請（例：「書籍レコメンデーションサービスで使用」）

通常、正当な理由があれば**1日10万回**まで無料で増やせます。

## 料金

- **完全無料**
- クレジットカード登録不要
- 割り当て増加も無料

## 注意事項

- APIキーは公開しない（GitHubにコミットしない）
- 本番環境では必ずHTTPリファラー制限を設定
- 定期的に使用状況を確認