# ReadNest MCP Server

ReadNest MCP ServerはModel Context Protocol (MCP)を使用して、あなたの読書データにアクセスできるようにします。

## 特徴

- 📚 本棚データの取得
- 📊 読書統計情報の取得
- 🔐 API Key認証
- 🛡️ 安全な読み取り専用アクセス

## セットアップ

### 1. 依存関係のインストール

```bash
cd readnest-mcp
pip install -e .
```

### 2. 環境変数の設定

`.env.example`をコピーして`.env`を作成:

```bash
cp .env.example .env
```

`.env`ファイルを編集:

```
READNEST_API_KEY=your_api_key_here
READNEST_API_BASE_URL=https://readnest.jp
```

### 3. API Keyの生成

ReadNestサーバー上で以下のコマンドを実行:

```bash
php admin/generate_api_key.php <your_user_id> "MCP Server"
```

生成されたAPI Keyを`.env`ファイルに設定してください。

### 4. Claude Desktopの設定

`~/Library/Application Support/Claude/claude_desktop_config.json`を編集:

```json
{
  "mcpServers": {
    "readnest": {
      "command": "python",
      "args": ["-m", "readnest_mcp.server"],
      "env": {
        "READNEST_API_KEY": "your_api_key_here",
        "READNEST_API_BASE_URL": "https://readnest.jp"
      }
    }
  }
}
```

## 使用可能なツール

### get_bookshelf

本棚のデータを取得します。

**パラメータ:**
- `status` (optional): 本のステータス (tsundoku, reading, finished, read)
- `limit` (optional): 取得件数 (デフォルト: 100)
- `offset` (optional): オフセット (デフォルト: 0)

**例:**
```
「読了した本を10冊教えて」
```

### get_reading_stats

読書統計情報を取得します。

**例:**
```
「今年は何冊読んだ?」
「読書統計を教えて」
```

## セキュリティ

詳細は[SECURITY.md](SECURITY.md)を参照してください。

### 主な安全対策

- ✅ 読み取り専用アクセス
- ✅ API Key認証
- ✅ 自分のデータのみアクセス可能
- ✅ HTTPS通信

## トラブルシューティング

### API Keyが無効

- API Keyが正しく`.env`に設定されているか確認
- API Keyの有効期限を確認

### 接続エラー

- `READNEST_API_BASE_URL`が正しいか確認
- ネットワーク接続を確認

## ライセンス

Private use only
