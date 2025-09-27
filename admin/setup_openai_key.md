# OpenAI APIキーの設定方法

## 現在の状況
OpenAI APIキーが無効になっています（HTTP 401エラー）。

## 設定方法

### 方法1: config.phpを直接編集（推奨）

1. OpenAI APIキーを取得
   - https://platform.openai.com/api-keys にアクセス
   - ログイン後、「Create new secret key」をクリック
   - 生成されたキー（sk-...で始まる）をコピー

2. config.phpを編集
   ```bash
   # サーバー上で
   vi /home/icotfeels/readnest.jp/public_html/config.php
   ```

3. 92行目付近を以下のように修正：
   ```php
   // 現在の無効なキー
   define('OPENAI_API_KEY', getenv('OPENAI_API_KEY') ?: 'sk-proj-WWg23...');
   
   // ↓ 以下のように変更
   
   // 新しい有効なキー
   define('OPENAI_API_KEY', 'sk-新しいAPIキーをここに貼り付け');
   ```

### 方法2: 環境変数を使用（セキュリティ重視）

1. サーバーの.bashrcまたは.bash_profileに追加：
   ```bash
   export OPENAI_API_KEY="sk-新しいAPIキーをここに貼り付け"
   ```

2. 設定を反映：
   ```bash
   source ~/.bashrc
   ```

3. 確認：
   ```bash
   echo $OPENAI_API_KEY
   ```

## APIキーの要件

OpenAI APIキーは以下の条件を満たす必要があります：

1. **有効なプロジェクトキー**: sk-proj-... または sk-... で始まる
2. **十分なクレジット**: 残高があること
3. **適切な権限**: Embeddings APIへのアクセス権限

## テスト方法

設定後、以下のコマンドでテスト：

```bash
# API接続テスト
php /home/icotfeels/readnest.jp/public_html/admin/test_api_only.php
```

成功すると以下のような表示になります：
```
Testing OpenAI API:
  ✓ OpenAI API works (embedding dimension: 1536)
```

## トラブルシューティング

### "Incorrect API key provided"エラーの場合
- APIキーが正しくコピーされているか確認
- キーの前後に余分なスペースがないか確認
- 新しいキーを生成して再試行

### "Insufficient quota"エラーの場合
- OpenAIアカウントに残高があるか確認
- https://platform.openai.com/usage で使用状況を確認

### "Rate limit exceeded"エラーの場合
- 少し時間を置いてから再試行
- スクリプトの処理件数を減らす

## セキュリティの注意点

1. **APIキーをGitにコミットしない**
2. **定期的にキーをローテーション**
3. **使用量を定期的に監視**

## 料金について

text-embedding-3-small モデルの料金（2024年時点）：
- $0.02 / 1M tokens
- 1冊あたり約500-1000 tokens
- 1000冊処理しても約$0.02程度

## 次のステップ

1. 有効なAPIキーを設定
2. `test_api_only.php`でテスト
3. 成功したら`generate_enhanced_embeddings_cli.php`を実行