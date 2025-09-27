#!/bin/bash

# Embedding生成バッチ処理実行スクリプト（cron対応版）
# 使用方法: 
#   ./run_embedding_batch.sh        # API制限まで処理
#   ./run_embedding_batch.sh max     # API制限まで処理（明示的）
#   ./run_embedding_batch.sh 50      # 50件処理

# デフォルトはAPI制限まで処理
LIMIT=${1:-max}

# スクリプトの絶対パスを取得（cronでも動作するように）
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
cd "$SCRIPT_DIR"

# ログディレクトリの作成
LOG_DIR="$SCRIPT_DIR/logs"
if [ ! -d "$LOG_DIR" ]; then
    mkdir -p "$LOG_DIR"
fi

# ログファイル名（日付付き）
LOG_FILE="$LOG_DIR/embedding_batch_$(date +%Y%m%d_%H%M%S).log"

# cronから実行される場合のPATH設定
export PATH="/usr/local/bin:/usr/bin:/bin:$PATH"

# PHPのパスを明示的に指定（必要に応じて変更）
PHP_BIN=$(which php || echo "/usr/bin/php")

echo "========================================" | tee -a "$LOG_FILE"
echo "Embedding生成バッチ処理を開始します" | tee -a "$LOG_FILE"
echo "処理モード: $LIMIT" | tee -a "$LOG_FILE"
echo "開始時刻: $(date '+%Y-%m-%d %H:%M:%S')" | tee -a "$LOG_FILE"
echo "実行ディレクトリ: $SCRIPT_DIR" | tee -a "$LOG_FILE"
echo "PHPパス: $PHP_BIN" | tee -a "$LOG_FILE"
echo "ログファイル: $LOG_FILE" | tee -a "$LOG_FILE"
echo "========================================" | tee -a "$LOG_FILE"

# PHPスクリプトの実行
$PHP_BIN batch_generate_embeddings.php "$LIMIT" 2>&1 | tee -a "$LOG_FILE"

# 実行結果の確認
EXIT_CODE=${PIPESTATUS[0]}

echo "========================================" | tee -a "$LOG_FILE"
echo "終了時刻: $(date '+%Y-%m-%d %H:%M:%S')" | tee -a "$LOG_FILE"

if [ $EXIT_CODE -eq 0 ]; then
    echo "✓ バッチ処理が正常に完了しました" | tee -a "$LOG_FILE"
    
    # 成功時にサマリーファイルも更新（cron実行時の確認用）
    SUMMARY_FILE="$LOG_DIR/latest_summary.txt"
    echo "最終実行: $(date '+%Y-%m-%d %H:%M:%S')" > "$SUMMARY_FILE"
    echo "ステータス: 成功" >> "$SUMMARY_FILE"
    echo "ログ: $LOG_FILE" >> "$SUMMARY_FILE"
else
    echo "✗ バッチ処理でエラーが発生しました (Exit Code: $EXIT_CODE)" | tee -a "$LOG_FILE"
    
    # エラー時にサマリーファイルも更新
    SUMMARY_FILE="$LOG_DIR/latest_summary.txt"
    echo "最終実行: $(date '+%Y-%m-%d %H:%M:%S')" > "$SUMMARY_FILE"
    echo "ステータス: エラー (Exit Code: $EXIT_CODE)" >> "$SUMMARY_FILE"
    echo "ログ: $LOG_FILE" >> "$SUMMARY_FILE"
fi

echo "========================================" | tee -a "$LOG_FILE"

exit $EXIT_CODE