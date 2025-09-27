# Enhanced Embedding Generator - 使用ガイド

## 概要
`generate_enhanced_embeddings_cli.php`は、ReadNestの全書籍データに対してエンベディングを生成する強化版CLIツールです。

## 基本的な使い方

```bash
php generate_enhanced_embeddings_cli.php [mode] [limit] [offset]
```

## モード説明

### 1. `all` - 全データ処理（デフォルト）
embeddingがない本を優先的に処理します。

```bash
# 100件処理
php generate_enhanced_embeddings_cli.php all 100

# 100件スキップして次の100件
php generate_enhanced_embeddings_cli.php all 100 100
```

### 2. `user` - 特定ユーザーの本
指定ユーザーの本のみ処理します。

```bash
# user_id=12の50件
php generate_enhanced_embeddings_cli.php user 50

# user_id=5の20件
php generate_enhanced_embeddings_cli.php user 5 20
```

### 3. `high` - 高評価本のみ
全ユーザーの高評価本を処理します。

```bash
# ★4以上の100件
php generate_enhanced_embeddings_cli.php high 100

# ★5のみの50件
php generate_enhanced_embeddings_cli.php high 5 50
```

### 4. `missing` - 未生成のみ
embeddingが生成されていない本のみ処理します。

```bash
# embeddingがない200件
php generate_enhanced_embeddings_cli.php missing 200
```

## 大量データの処理方法

### 方法1: バッチスクリプトを使用

```bash
# 実行権限を付与
chmod +x process_all_embeddings.sh

# 実行
./process_all_embeddings.sh
```

### 方法2: 手動でバッチ処理

```bash
# ステップ1: テスト（10件）
php generate_enhanced_embeddings_cli.php all 10

# ステップ2: 最初の100件
php generate_enhanced_embeddings_cli.php all 100 0

# ステップ3: 次の100件
php generate_enhanced_embeddings_cli.php all 100 100

# ステップ4: さらに次の100件
php generate_enhanced_embeddings_cli.php all 100 200

# 以降、offsetを100ずつ増やして継続
```

### 方法3: ループ処理

```bash
# 1000件を100件ずつ処理
for offset in 0 100 200 300 400 500 600 700 800 900; do
    echo "Processing offset $offset..."
    php generate_enhanced_embeddings_cli.php all 100 $offset
    sleep 5  # API制限対策で5秒待機
done
```

## 処理の優先順位

`all`モードでは以下の優先順位で処理されます：

1. **combined_embeddingがない本**（最優先）
2. **多くのユーザーが登録している本**
3. **高評価の本**

## 進捗の確認

### リアルタイム確認
```bash
# 統計情報のみ表示（処理なし）
php generate_enhanced_embeddings_cli.php all 0
```

### Web UIで確認
```
https://readnest.jp/admin/embedding_debug_enhanced.php
```

### ログファイル確認
```bash
tail -f /home/icotfeels/readnest.jp/log/embedding_process.log
```

## 推奨される処理手順

### 初回処理（全データ）

1. **現在の状況確認**
```bash
php generate_enhanced_embeddings_cli.php all 0
```

2. **テスト実行（10件）**
```bash
php generate_enhanced_embeddings_cli.php all 10
```

3. **段階的な処理**
```bash
# 100件ずつ処理（API制限を考慮）
for i in {0..9}; do
    offset=$((i * 100))
    echo "Batch $((i + 1)): Processing 100 books (offset: $offset)"
    php generate_enhanced_embeddings_cli.php all 100 $offset
    
    # 進捗確認
    echo "Progress check..."
    php generate_enhanced_embeddings_cli.php all 0 | head -10
    
    # API制限対策
    sleep 10
done
```

4. **結果確認**
- Web UI: `https://readnest.jp/admin/embedding_debug_enhanced.php`
- 統計: `php generate_enhanced_embeddings_cli.php all 0`

## トラブルシューティング

### "Rate limit exceeded"エラー
```bash
# 処理間隔を長くする
sleep 30  # 30秒待機
```

### メモリ不足
```bash
# より少ない件数で処理
php generate_enhanced_embeddings_cli.php all 50
```

### タイムアウト
```bash
# PHPの実行時間制限を延長
php -d max_execution_time=600 generate_enhanced_embeddings_cli.php all 100
```

## 処理時間の目安

- 1件あたり: 約2-3秒
- 100件: 約3-5分
- 1000件: 約30-50分
- 10000件: 約5-8時間

## APIコスト見積もり

OpenAI text-embedding-3-small（2024年時点）:
- 料金: $0.02 / 1M tokens
- 1冊平均: 500-1000 tokens
- 1000冊: 約$0.01-0.02
- 10000冊: 約$0.10-0.20

## 注意事項

1. **API制限**: 連続処理時は適切な間隔を空ける
2. **重複防止**: 既に生成済みのembeddingは自動的にスキップ
3. **エラー処理**: エラーが発生しても次の本の処理を継続
4. **バックグラウンド実行**: 
```bash
nohup php generate_enhanced_embeddings_cli.php all 1000 > process.log 2>&1 &
```

## サポート

問題が発生した場合：
1. エラーログを確認: `/home/icotfeels/readnest.jp/log/`
2. API キーの有効性を確認
3. データベース接続を確認