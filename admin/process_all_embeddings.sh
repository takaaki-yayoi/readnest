#!/bin/bash

# 全データのembedding生成を段階的に実行するスクリプト
# Usage: ./process_all_embeddings.sh

PHP_SCRIPT="/home/icotfeels/readnest.jp/public_html/admin/generate_enhanced_embeddings_cli.php"
LOG_FILE="/home/icotfeels/readnest.jp/log/embedding_process.log"
BATCH_SIZE=100

echo "========================================" | tee -a $LOG_FILE
echo "Embedding Generation Started: $(date)" | tee -a $LOG_FILE
echo "========================================" | tee -a $LOG_FILE

# 統計情報を取得
echo "Getting statistics..." | tee -a $LOG_FILE
php $PHP_SCRIPT all 0 0 2>&1 | head -20 | tee -a $LOG_FILE

# 処理対象の総数を取得（この部分は手動で調整が必要）
# まず少数でテスト
echo "" | tee -a $LOG_FILE
echo "Phase 1: Test run with 10 books" | tee -a $LOG_FILE
php $PHP_SCRIPT all 10 0 2>&1 | tee -a $LOG_FILE

# 問題なければ段階的に処理
if [ $? -eq 0 ]; then
    echo "" | tee -a $LOG_FILE
    echo "Phase 2: Processing first 100 books" | tee -a $LOG_FILE
    php $PHP_SCRIPT all 100 0 2>&1 | tee -a $LOG_FILE
    
    echo "" | tee -a $LOG_FILE
    echo "Phase 3: Processing next 100 books" | tee -a $LOG_FILE
    php $PHP_SCRIPT all 100 100 2>&1 | tee -a $LOG_FILE
    
    echo "" | tee -a $LOG_FILE
    echo "Phase 4: Processing next 100 books" | tee -a $LOG_FILE
    php $PHP_SCRIPT all 100 200 2>&1 | tee -a $LOG_FILE
    
    # さらに続ける場合は以下のようにループ
    # for offset in $(seq 300 100 1000); do
    #     echo "" | tee -a $LOG_FILE
    #     echo "Processing offset $offset" | tee -a $LOG_FILE
    #     php $PHP_SCRIPT all $BATCH_SIZE $offset 2>&1 | tee -a $LOG_FILE
    #     sleep 5  # API制限対策
    # done
fi

echo "" | tee -a $LOG_FILE
echo "========================================" | tee -a $LOG_FILE
echo "Embedding Generation Completed: $(date)" | tee -a $LOG_FILE
echo "========================================" | tee -a $LOG_FILE

# 最終統計を表示
echo "" | tee -a $LOG_FILE
echo "Final Statistics:" | tee -a $LOG_FILE
php $PHP_SCRIPT all 0 0 2>&1 | head -20 | tee -a $LOG_FILE