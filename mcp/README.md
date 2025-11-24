# ReadNest MCP Server (PHP)

ReadNestのリモートMCPサーバー（PHP実装）

## 特徴

- ✅ PHPで実装（既存環境で即座に動作）
- ✅ 追加のデプロイ・設定不要
- ✅ API Key認証
- ✅ JSON-RPC over HTTPS

## エンドポイント

- `GET /mcp/` - ヘルスチェック
- `POST /mcp/messages.php` - MCP通信（JSON-RPC）

## Claude Desktop設定

`~/Library/Application Support/Claude/claude_desktop_config.json`:

```json
{
  "mcpServers": {
    "readnest": {
      "url": "https://readnest.jp/mcp/messages.php",
      "apiKey": "YOUR_API_KEY_HERE"
    }
  }
}
```

**API Keyの取得:** https://readnest.jp/api_keys.php で生成

## 利用可能なツール

### get_bookshelf
本棚のデータを取得

**パラメータ:**
- `status` (optional): tsundoku, reading, finished, read
- `limit` (optional): 取得件数
- `offset` (optional): オフセット

### get_reading_stats
読書統計情報を取得

## 使い方

1. API Keyを生成（https://readnest.jp/api_keys.php）
2. Claude Desktop設定ファイルに追加
3. Claude Desktopを再起動
4. 「読了した本を10冊教えて」と質問

## セキュリティ

- API Key認証必須
- user_idによる行レベルアクセス制御
- 読み取り専用
- HTTPS通信

## デプロイ

**不要！** このファイルをアップロードするだけで動作します。
