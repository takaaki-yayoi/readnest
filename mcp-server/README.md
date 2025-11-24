# ReadNest Remote MCP Server

ReadNest Remote MCP Serverは、レンタルサーバー上で動作するMCPサーバーです。

## 構成

```
┌─────────────────┐
│ Claude Desktop  │ (クライアント)
└────────┬────────┘
         │
         │ HTTPS (SSE/JSON-RPC)
         │
┌────────▼────────┐
│  MCP Server     │ (レンタルサーバー)
│   FastAPI       │
└────────┬────────┘
         │
         │ ローカル接続
         │
┌────────▼────────┐
│   MySQL DB      │ (レンタルサーバー)
└─────────────────┘
```

## 特徴

- 🌐 完全リモート実行（ローカルに何もインストール不要）
- 🔐 API Key認証
- 📊 2つのMCPツール（本棚取得、統計取得）
- ⚡ FastAPI + SSE による高速通信

## セットアップ

詳細は [DEPLOY.md](DEPLOY.md) を参照してください。

### 必要な環境

- Python 3.10以上が動作するレンタルサーバー
- MySQL データベース
- HTTPS対応

### インストール

1. ファイルをレンタルサーバーにアップロード
2. 仮想環境を作成して依存関係をインストール
3. `.env` ファイルを設定
4. uvicorn または gunicorn で起動

### Claude Desktopの設定

```json
{
  "mcpServers": {
    "readnest": {
      "url": "https://readnest.jp/mcp-server/sse",
      "headers": {
        "Authorization": "Bearer YOUR_API_KEY"
      }
    }
  }
}
```

## API エンドポイント

- `GET /sse` - SSEエンドポイント（MCP接続）
- `POST /messages` - MCPメッセージ処理

## 利用可能なツール

### get_bookshelf

本棚のデータを取得します。

**パラメータ:**
- `status` (optional): tsundoku, reading, finished, read
- `limit` (optional): 取得件数（デフォルト: 100）
- `offset` (optional): オフセット（デフォルト: 0）

### get_reading_stats

読書統計情報を取得します。

## セキュリティ

- API Key認証必須
- user_idによる行レベルアクセス制御
- 読み取り専用アクセス
- HTTPS通信

## ライセンス

Private use only
