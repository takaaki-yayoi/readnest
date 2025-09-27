# AI機能セットアップガイド

ReadNestのAI機能を利用するには、有効なOpenAI APIキーが必要です。

## 1. OpenAI APIキーの取得

1. [OpenAI Platform](https://platform.openai.com/) にアクセス
2. アカウントを作成またはログイン
3. API Keys セクションから新しいAPIキーを生成
4. 生成されたキー（`sk-...` で始まる文字列）をコピー

## 2. APIキーの設定方法

以下のいずれかの方法でAPIキーを設定してください：

### 方法1: config.phpに直接設定（推奨）

`config.php` の該当箇所を編集：

```php
// OpenAI API設定
define('OPENAI_API_KEY', 'sk-your-actual-api-key-here');
```

### 方法2: 環境変数を使用

サーバーの環境変数に設定：

```bash
export OPENAI_API_KEY="sk-your-actual-api-key-here"
```

## 3. AI機能の確認

APIキーを設定後、以下の機能が利用可能になります：

- **AI書評アシスタント**: 本の詳細ページで書評作成をサポート
- **AI読書推薦**: 本棚ページで次に読む本を推薦
- **読書傾向分析**: あなたの読書パターンをAIが分析

## 4. トラブルシューティング

### エラー: "HTTP Error 401: Incorrect API key provided"
- APIキーが正しくコピーされているか確認
- APIキーの前後に余分なスペースがないか確認
- OpenAIのダッシュボードでAPIキーが有効か確認

### エラー: "OpenAI APIキーが設定されていません"
- config.phpの編集が保存されているか確認
- ファイルのパーミッションを確認

## 5. 料金について

AI機能は OpenAI の API を使用するため、使用量に応じて料金が発生します。
- GPT-4o-mini モデルを使用（コスト効率が良い）
- 通常の使用では月額数ドル程度
- [OpenAI Pricing](https://openai.com/pricing) で詳細を確認

## 6. セキュリティに関する注意

- APIキーを公開リポジトリにコミットしないでください
- 本番環境では環境変数の使用を推奨
- 定期的にAPIキーをローテーションすることを推奨