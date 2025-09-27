#!/bin/bash

# レビューembedding全件生成スクリプト
# 大量のレビューを段階的に処理

echo "=========================================="
echo "Review Embedding Generation - Full Batch"
echo "=========================================="
echo ""

# まず統計を確認
echo "Checking current statistics..."
php /home/icotfeels/readnest.jp/public_html/batch/generate_review_embeddings.php --dry-run --limit=1

echo ""
echo "=========================================="
echo ""

# ユーザーに確認
read -p "Do you want to proceed with full batch processing? (y/n): " -n 1 -r
echo ""

if [[ ! $REPLY =~ ^[Yy]$ ]]
then
    echo "Batch processing cancelled."
    exit 1
fi

echo ""
echo "Starting batch processing..."
echo ""

# バッチサイズ
BATCH_SIZE=500
MAX_ITERATIONS=200  # 最大200回 = 100,000件まで

# 処理ループ
for i in $(seq 1 $MAX_ITERATIONS)
do
    echo "=========================================="
    echo "Batch iteration $i (processing $BATCH_SIZE reviews)"
    echo "=========================================="
    
    # バッチ実行
    php /home/icotfeels/readnest.jp/public_html/batch/generate_review_embeddings.php --limit=$BATCH_SIZE
    
    # 終了コードを確認
    if [ $? -ne 0 ]; then
        echo "Error occurred in batch $i. Stopping."
        exit 1
    fi
    
    # 処理するレビューが残っているか確認
    REMAINING=$(php /home/icotfeels/readnest.jp/public_html/batch/generate_review_embeddings.php --dry-run --limit=1 2>/dev/null | grep "Target reviews:" | awk '{print $3}')
    
    echo ""
    echo "Remaining reviews: $REMAINING"
    echo ""
    
    # 残りがなければ終了
    if [ "$REMAINING" = "0" ] || [ -z "$REMAINING" ]; then
        echo "All reviews processed!"
        break
    fi
    
    # 短い待機（API レート制限対策）
    echo "Waiting 2 seconds before next batch..."
    sleep 2
done

echo ""
echo "=========================================="
echo "Final Statistics"
echo "=========================================="
php /home/icotfeels/readnest.jp/public_html/batch/generate_review_embeddings.php --dry-run --limit=1

echo ""
echo "Batch processing completed!"