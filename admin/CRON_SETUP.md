# Embedding生成バッチのcron設定

## 概要
このドキュメントでは、Embedding生成バッチ処理を自動実行するためのcron設定方法を説明します。

## バッチ処理の仕様

### 実行モード
- `./run_embedding_batch.sh` または `./run_embedding_batch.sh max`
  - Google Books APIの日次制限（1000件）まで自動処理
  - 95%（950件）で安全停止
  - 処理対象がなくなったら自動終了

- `./run_embedding_batch.sh 50`
  - 指定件数（この場合50件）を処理

### API制限
- **Google Books API**: 1000件/日、10件/分
- **OpenAI API**: 60件/分（日次制限なし）

## cron設定例

### 1. crontabの編集
```bash
crontab -e
```

### 2. 推奨設定パターン

#### パターン1: 毎日深夜に実行（推奨）
```cron
# 毎日午前2時にAPI制限まで処理
0 2 * * * /Users/yayoi/workspace/subversion/readnest/admin/run_embedding_batch.sh max >> /Users/yayoi/workspace/subversion/readnest/admin/logs/cron.log 2>&1
```

#### パターン2: 1日2回実行（朝夕）
```cron
# 午前6時と午後6時に500件ずつ処理
0 6 * * * /Users/yayoi/workspace/subversion/readnest/admin/run_embedding_batch.sh 500 >> /Users/yayoi/workspace/subversion/readnest/admin/logs/cron.log 2>&1
0 18 * * * /Users/yayoi/workspace/subversion/readnest/admin/run_embedding_batch.sh 500 >> /Users/yayoi/workspace/subversion/readnest/admin/logs/cron.log 2>&1
```

#### パターン3: 平日のみ実行
```cron
# 平日（月〜金）の午前3時にAPI制限まで処理
0 3 * * 1-5 /Users/yayoi/workspace/subversion/readnest/admin/run_embedding_batch.sh max >> /Users/yayoi/workspace/subversion/readnest/admin/logs/cron.log 2>&1
```

#### パターン4: 3時間ごとに少量処理
```cron
# 3時間ごとに100件処理（1日800件）
0 */3 * * * /Users/yayoi/workspace/subversion/readnest/admin/run_embedding_batch.sh 100 >> /Users/yayoi/workspace/subversion/readnest/admin/logs/cron.log 2>&1
```

## 動作確認

### 1. 手動実行テスト
```bash
cd /Users/yayoi/workspace/subversion/readnest/admin
./run_embedding_batch.sh 10  # 10件だけテスト実行
```

### 2. ログの確認
```bash
# 最新のログを確認
tail -f /Users/yayoi/workspace/subversion/readnest/admin/logs/embedding_batch_*.log

# サマリーファイルを確認
cat /Users/yayoi/workspace/subversion/readnest/admin/logs/latest_summary.txt
```

### 3. cron実行の確認
```bash
# cronログを確認
tail -f /Users/yayoi/workspace/subversion/readnest/admin/logs/cron.log

# システムログでcron実行を確認（macOS/Linux）
grep CRON /var/log/syslog  # Ubuntu/Debian
grep CRON /var/log/cron    # CentOS/RHEL
log show --predicate 'process == "cron"' --last 1h  # macOS
```

## トラブルシューティング

### PHPパスの確認
```bash
which php
# 結果を確認して、必要に応じてrun_embedding_batch.shの29行目を修正
```

### 権限の確認
```bash
# 実行権限を付与
chmod +x /Users/yayoi/workspace/subversion/readnest/admin/run_embedding_batch.sh
chmod +x /Users/yayoi/workspace/subversion/readnest/admin/batch_generate_embeddings.php
```

### データベース接続の確認
- `/config.php`にデータベース接続情報が正しく設定されているか確認
- cronから実行される際の環境変数の違いに注意

### API制限に到達した場合
- 翌日0時（UTC）にリセットされるまで待機
- 進捗管理ページ（`/admin/embedding_progress.php`）で状況を確認

## 監視とアラート

### 簡易監視スクリプトの例
```bash
#!/bin/bash
# check_embedding_batch.sh

SUMMARY_FILE="/Users/yayoi/workspace/subversion/readnest/admin/logs/latest_summary.txt"
if [ -f "$SUMMARY_FILE" ]; then
    if grep -q "エラー" "$SUMMARY_FILE"; then
        echo "Embedding batch error detected" | mail -s "Embedding Batch Error" admin@example.com
    fi
fi
```

### 監視用cron設定
```cron
# 毎日午前9時にバッチ処理の状態を確認
0 9 * * * /path/to/check_embedding_batch.sh
```

## 注意事項

1. **API制限**: Google Books APIは1日1000件の制限があります
2. **処理時間**: 1000件処理には約2-3時間かかる場合があります
3. **重複実行防止**: 前回の処理が終了していない場合は新規実行を避けてください
4. **ログ管理**: ログファイルは定期的に削除または圧縮してください

## ログのローテーション設定（オプション）

```bash
# /etc/logrotate.d/embedding_batch
/Users/yayoi/workspace/subversion/readnest/admin/logs/*.log {
    daily
    rotate 30
    compress
    delaycompress
    missingok
    notifempty
}
```