# OpenAI API設定ガイド

## 概要
ReadNest AIアシスタントは、OpenAI APIを使用して高度な会話機能を提供します。

## 設定手順

### 1. OpenAI APIキーの取得
1. [OpenAI Platform](https://platform.openai.com/)にアクセス
2. アカウントにログイン（未登録の場合は新規登録）
3. [API Keys](https://platform.openai.com/api-keys)ページへ移動
4. 「Create new secret key」をクリック
5. キーに名前を付けて作成（例：「ReadNest」）
6. 表示されたAPIキーをコピー（この時点でしか表示されません）

### 2. APIキーの設定
1. `/config/openai.php`ファイルを開く
2. `OPENAI_API_KEY`の値を実際のAPIキーに置き換える：
   ```php
   define('OPENAI_API_KEY', 'sk-YOUR_ACTUAL_API_KEY_HERE');
   ```

### 3. 設定の確認
- ブラウザでReadNestにログイン
- 右下のAIアシスタントアイコンをクリック
- メッセージを送信して動作確認

## 設定オプション

### `/config/openai.php`で変更可能な設定：

- **OPENAI_MODEL**: 使用するGPTモデル（デフォルト: `gpt-4o-mini`）
  - 軽量版: `gpt-4o-mini`（最速・最低コスト、推奨）
  - 高性能版: `gpt-4o`（最高品質）
  - 旧バージョン: `gpt-3.5-turbo`（互換性用）

- **OPENAI_MAX_TOKENS**: 最大応答トークン数（デフォルト: 800）
- **OPENAI_TEMPERATURE**: 応答の創造性（0.0〜2.0、デフォルト: 0.7）
- **OPENAI_TIMEOUT**: API呼び出しタイムアウト（秒、デフォルト: 30）
- **OPENAI_ENABLED**: APIの有効/無効（デフォルト: true）

## フォールバックモード

以下の場合、AIアシスタントは自動的にフォールバックモードで動作します：
- APIキーが設定されていない
- APIキーが無効
- APIエラーが発生した
- `OPENAI_ENABLED`が`false`に設定されている

フォールバックモードでも基本的な応答は可能です。

## トラブルシューティング

### APIキーエラー
- キーが正しくコピーされているか確認
- キーに余分なスペースが含まれていないか確認
- OpenAIアカウントの支払い情報が設定されているか確認

### レート制限エラー
- 無料枠の制限に達している可能性があります
- [Usage](https://platform.openai.com/usage)ページで使用状況を確認

### タイムアウトエラー
- `OPENAI_TIMEOUT`の値を増やしてみてください（例：60）

## 料金について

- GPT-4o-miniは低コストで高性能
- 料金詳細: [OpenAI Pricing](https://openai.com/pricing)
- 使用量は[OpenAI Usage](https://platform.openai.com/usage)で確認可能

## セキュリティ注意事項

- APIキーは絶対に公開しないでください
- Gitにコミットしないよう`.gitignore`に`/config/openai.php`を追加することを推奨
- 本番環境では環境変数の使用を検討してください