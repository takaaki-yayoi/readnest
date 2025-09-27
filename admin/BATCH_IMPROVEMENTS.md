# バッチ処理の改善内容

## 問題点
1. Google Books APIで説明文が見つからない本に対して、毎回APIを呼び出していた
2. OpenAI APIが全く呼ばれていなかった（説明文がない本はembeddingを生成しなかった）
3. 同じ本に対して無駄にAPIリクエストを繰り返していた

## 実装した対策

### 1. 重複API呼び出しの防止
- `process_attempts` カラムで処理試行回数を追跡
- 3回以上失敗した本は処理対象から除外
- `google_books_checked` フラグで、Google Books APIで確認済みの本を記録

### 2. 説明文なしでもEmbedding生成
- Google Books APIで説明文が見つからなくても、タイトルと著者情報でembeddingを生成
- `embedding_type` で説明文の有無を区別（'title_author_only' vs 通常）
- 後から説明文が追加された場合は再生成可能

### 3. データベース変更
以下のカラムを追加：
- `google_books_checked`: Google Books APIで確認済みフラグ
- `google_books_checked_at`: 確認日時
- `process_attempts`: 処理試行回数
- `last_error_message`: 最後のエラーメッセージ
- `embedding_type`: embeddingの種類
- `embedding_has_description`: 説明文を含むembeddingか

## 効果
- 無駄なGoogle Books API呼び出しを削減（950回 → 必要な分のみ）
- OpenAI APIが適切に呼ばれるようになる
- 説明文がない本でも検索可能になる

## マイグレーション
```bash
mysql -u your_user -p readnest < admin/migrations/add_google_books_checked_column.sql
```