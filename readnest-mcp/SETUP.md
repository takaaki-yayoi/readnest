# ReadNest MCP Server - セットアップガイド

## 概要

ReadNest MCP Serverを使用すると、Claude Desktopから自分の読書データにアクセスできます。

## 前提条件

- Python 3.10以上
- ReadNestアカウント
- Claude Desktop

## セットアップ手順

### 1. データベーステーブルの作成

ReadNestサーバー上で以下のSQLを実行してAPI Key管理テーブルを作成します:

```bash
mysql -u root -p readnest < sql/create_api_keys_table.sql
```

### 2. API Keyの生成

ReadNestサーバー上で以下のコマンドを実行:

```bash
cd /path/to/readnest
php admin/generate_api_key.php <your_user_id> "MCP Server"
```

例:
```bash
php admin/generate_api_key.php 1 "MCP Server"
```

出力例:
```
API Key generated successfully!

User ID: 1
Name: MCP Server
API Key: 1a2b3c4d5e6f7g8h9i0j1k2l3m4n5o6p7q8r9s0t1u2v3w4x5y6z7a8b9c0d1e2f

IMPORTANT: Save this API key securely. It will not be shown again.

To use this API key, add it to your .env file:
READNEST_API_KEY=1a2b3c4d5e6f7g8h9i0j1k2l3m4n5o6p7q8r9s0t1u2v3w4x5y6z7a8b9c0d1e2f
```

**重要**: 生成されたAPI Keyを安全な場所に保存してください。

### 3. MCPサーバーのインストール

```bash
cd readnest-mcp
pip install -e .
```

### 4. 環境変数の設定

`.env`ファイルを作成:

```bash
cp .env.example .env
```

`.env`ファイルを編集して、生成したAPI Keyを設定:

```
READNEST_API_KEY=1a2b3c4d5e6f7g8h9i0j1k2l3m4n5o6p7q8r9s0t1u2v3w4x5y6z7a8b9c0d1e2f
READNEST_API_BASE_URL=https://readnest.jp
```

### 5. Claude Desktopの設定

Claude Desktopの設定ファイルを編集します:

**macOS**: `~/Library/Application Support/Claude/claude_desktop_config.json`
**Windows**: `%APPDATA%\Claude\claude_desktop_config.json`

以下の内容を追加:

```json
{
  "mcpServers": {
    "readnest": {
      "command": "python",
      "args": ["-m", "readnest_mcp.server"],
      "env": {
        "READNEST_API_KEY": "1a2b3c4d5e6f7g8h9i0j1k2l3m4n5o6p7q8r9s0t1u2v3w4x5y6z7a8b9c0d1e2f",
        "READNEST_API_BASE_URL": "https://readnest.jp"
      }
    }
  }
}
```

**注意**: API Keyは直接設定ファイルに記載するか、環境変数から読み込むことができます。

### 6. Claude Desktopを再起動

設定を反映させるため、Claude Desktopを完全に終了して再起動してください。

## 動作確認

Claude Desktopで以下のように質問してみてください:

1. **本棚データの取得**
   - 「読了した本を10冊教えて」
   - 「積読リストを見せて」
   - 「今読んでる本は？」

2. **統計情報の取得**
   - 「今年は何冊読んだ?」
   - 「読書統計を教えて」
   - 「積読は何冊ある?」

## トラブルシューティング

### MCPサーバーが認識されない

1. Claude Desktopの設定ファイルのJSONが正しいか確認
2. Python 3.10以上がインストールされているか確認
3. `pip install -e .` が正しく実行されたか確認
4. Claude Desktopを完全に再起動

### API認証エラー

1. `.env`ファイルのAPI Keyが正しいか確認
2. API Keyが有効か確認（データベースの`b_api_keys`テーブルを確認）
3. `is_active = 1`になっているか確認
4. 有効期限が切れていないか確認

### 接続エラー

1. `READNEST_API_BASE_URL`が正しいか確認
2. ReadNestサーバーが稼働しているか確認
3. ネットワーク接続を確認
4. ファイアウォールの設定を確認

### デバッグログの確認

MCPサーバーのログを確認するには:

```bash
# 手動でサーバーを起動してログを確認
python -m readnest_mcp.server
```

## セキュリティに関する注意

- API Keyは絶対に他人と共有しないでください
- API Keyをgitにコミットしないでください（`.gitignore`に`.env`が含まれています）
- API Keyが漏洩した場合は、すぐにデータベースで`is_active = 0`に設定してください
- 定期的にAPI Keyをローテーションすることを推奨します

## API Keyの無効化

API Keyを無効化する場合は、データベースで以下のSQLを実行:

```sql
UPDATE b_api_keys
SET is_active = 0
WHERE api_key = 'your_api_key_here';
```

または、レコードを削除:

```sql
DELETE FROM b_api_keys
WHERE api_key = 'your_api_key_here';
```

## 次のステップ

- より多くのツールの追加（レビュー取得、タグ検索など）
- Webhookによる自動更新通知
- 統計情報のグラフ化

詳細は[README.md](README.md)と[SECURITY.md](SECURITY.md)を参照してください。
