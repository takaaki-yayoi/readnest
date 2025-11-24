# ReadNest Remote MCP Server - デプロイガイド

## 前提条件

- Python 3.10以上が利用可能なレンタルサーバー
- SSH アクセス権限
- MySQL データベース
- ReadNest API Keyが生成済み

## デプロイ手順

### 1. ファイルのアップロード

レンタルサーバーにSSH接続して、適切なディレクトリにファイルをアップロードします:

```bash
# ローカルからサーバーへアップロード
scp -r mcp-server user@your-server.com:/path/to/readnest/
```

または、gitを使用:

```bash
ssh user@your-server.com
cd /path/to/readnest
git pull origin master
```

### 2. Python仮想環境のセットアップ

```bash
cd /path/to/readnest/mcp-server

# 仮想環境を作成
python3 -m venv venv

# 仮想環境を有効化
source venv/bin/activate

# 依存関係をインストール
pip install -r requirements.txt
```

### 3. 環境変数の設定

`.env` ファイルを作成:

```bash
cp .env.example .env
nano .env
```

以下の内容を設定:

```
DB_HOST=localhost
DB_USER=your_db_user
DB_PASSWORD=your_db_password
DB_NAME=readnest
```

### 4. サーバーの起動

#### 開発環境（テスト用）

```bash
# uvicornで直接起動
uvicorn app:app --host 0.0.0.0 --port 8000
```

#### 本番環境

gunicornを使用:

```bash
# requirements.txtにgunicornを追加
pip install gunicorn

# gunicornで起動（4ワーカー）
gunicorn -w 4 -k uvicorn.workers.UvicornWorker wsgi:application --bind 0.0.0.0:8000
```

#### systemdサービスとして起動（推奨）

`/etc/systemd/system/readnest-mcp.service` を作成:

```ini
[Unit]
Description=ReadNest MCP Server
After=network.target

[Service]
Type=notify
User=your_user
Group=your_group
WorkingDirectory=/path/to/readnest/mcp-server
Environment="PATH=/path/to/readnest/mcp-server/venv/bin"
ExecStart=/path/to/readnest/mcp-server/venv/bin/gunicorn -w 4 -k uvicorn.workers.UvicornWorker wsgi:application --bind 0.0.0.0:8000

[Install]
WantedBy=multi-user.target
```

サービスを有効化して起動:

```bash
sudo systemctl enable readnest-mcp
sudo systemctl start readnest-mcp
sudo systemctl status readnest-mcp
```

### 5. リバースプロキシの設定（nginx）

`/etc/nginx/sites-available/readnest-mcp` を作成:

```nginx
server {
    listen 443 ssl http2;
    server_name readnest.jp;

    # SSL証明書の設定（既存のReadNest設定を流用）
    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;

    # MCPサーバー用のロケーション
    location /mcp-server/ {
        proxy_pass http://127.0.0.1:8000/;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;

        # SSE用の設定
        proxy_buffering off;
        proxy_cache off;
        proxy_read_timeout 86400;
    }

    # 既存のPHP設定...
}
```

nginxを再起動:

```bash
sudo nginx -t
sudo systemctl reload nginx
```

### 6. 動作確認

```bash
# ヘルスチェック
curl https://readnest.jp/mcp-server/sse \
  -H "Authorization: Bearer YOUR_API_KEY"
```

### 7. Claude Desktopの設定

`~/Library/Application Support/Claude/claude_desktop_config.json` を編集:

```json
{
  "mcpServers": {
    "readnest": {
      "url": "https://readnest.jp/mcp-server/sse",
      "headers": {
        "Authorization": "Bearer YOUR_API_KEY_HERE"
      }
    }
  }
}
```

Claude Desktopを再起動して動作確認:

```
「読了した本を10冊教えて」
```

## トラブルシューティング

### サーバーが起動しない

```bash
# ログを確認
sudo journalctl -u readnest-mcp -f

# Pythonのバージョン確認
python3 --version

# 依存関係を再インストール
pip install -r requirements.txt --force-reinstall
```

### データベース接続エラー

```bash
# MySQL接続テスト
mysql -h localhost -u your_user -p readnest

# .envファイルの確認
cat .env
```

### Claude Desktopから接続できない

```bash
# nginxのエラーログを確認
sudo tail -f /var/log/nginx/error.log

# MCPサーバーのログを確認
sudo journalctl -u readnest-mcp -f

# ファイアウォール設定を確認
sudo ufw status
```

## パフォーマンスチューニング

### gunicornワーカー数の調整

```bash
# CPU数の2倍+1が推奨
# 例: 2コアなら5ワーカー
gunicorn -w 5 -k uvicorn.workers.UvicornWorker wsgi:application
```

### データベース接続プールの設定

`app.py` でコネクションプールを使用:

```python
from mysql.connector import pooling

db_pool = pooling.MySQLConnectionPool(
    pool_name="readnest_pool",
    pool_size=10,
    **DB_CONFIG
)
```

## セキュリティ

- API Keyは環境変数で管理
- HTTPS必須
- ファイアウォールで不要なポートを閉じる
- 定期的なセキュリティアップデート

## 更新手順

```bash
cd /path/to/readnest
git pull origin master
cd mcp-server
source venv/bin/activate
pip install -r requirements.txt
sudo systemctl restart readnest-mcp
```

## バックアップ

定期的にバックアップを取得:

```bash
# アプリケーションコード
tar -czf readnest-mcp-backup-$(date +%Y%m%d).tar.gz /path/to/readnest/mcp-server

# .envファイルは別途安全に保管
```
