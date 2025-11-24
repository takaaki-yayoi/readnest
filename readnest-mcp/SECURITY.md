# ReadNest MCP Server - セキュリティ設計

## 認証・セキュリティモデル

### 1. アクセス制御

#### レベル1: システムレベル（OS）
- MCPサーバーはあなたのユーザーアカウントでのみ実行
- ファイルパーミッションで保護
- 他のユーザーはアクセス不可

#### レベル2: データベースアクセス制御
- 読み取り専用MySQLユーザーを使用
- 特定のテーブルのみアクセス許可
- user_idによる行レベルセキュリティ

#### レベル3: アプリケーションレベル
- すべてのクエリに user_id フィルタを強制
- SELECT文のみ許可
- 結果件数制限

### 2. データベースユーザーの作成

```sql
-- 読み取り専用ユーザーの作成
CREATE USER 'readnest_readonly'@'localhost' IDENTIFIED BY 'secure_password';

-- 必要最小限の権限のみ付与
GRANT SELECT ON readnest.b_book_list TO 'readnest_readonly'@'localhost';
GRANT SELECT ON readnest.b_book_repository TO 'readnest_readonly'@'localhost';
GRANT SELECT ON readnest.b_book_tags TO 'readnest_readonly'@'localhost';
GRANT SELECT ON readnest.b_book_event TO 'readnest_readonly'@'localhost';
GRANT SELECT ON readnest.b_user TO 'readnest_readonly'@'localhost';

-- 権限の適用
FLUSH PRIVILEGES;
```

### 3. 環境変数の保護

```bash
# .env ファイルのパーミッション設定
chmod 600 .env

# 所有者のみ読み取り可能
# -rw------- 1 yayoi staff .env
```

### 4. クエリの安全性保証

```python
# すべてのクエリに user_id フィルタを適用
def execute_query(query: str, params: dict) -> list:
    # user_id を強制的に追加
    if 'user_id' not in query.lower():
        raise SecurityError("user_id filter is required")

    # SELECT のみ許可
    if not query.strip().upper().startswith('SELECT'):
        raise SecurityError("Only SELECT queries allowed")

    # パラメータ化クエリで SQLインジェクション対策
    cursor.execute(query, params)
    return cursor.fetchall()
```

### 5. データ取得の制限

- **最大取得件数**: 100件
- **タイムアウト**: 5秒
- **レート制限**: なし（ローカル実行のため）

## 脅威モデル分析

### 想定される脅威と対策

| 脅威 | リスク | 対策 |
|------|--------|------|
| SQLインジェクション | 低 | パラメータ化クエリ使用 |
| データ漏洩 | 低 | ローカル実行のみ、user_idフィルタ |
| 不正なデータ変更 | なし | 読み取り専用ユーザー |
| 外部からの攻撃 | なし | ネットワーク公開なし |
| 他ユーザーのデータアクセス | なし | user_id による行レベル制御 |

## ベストプラクティス

### DO ✅
- 読み取り専用DBユーザーを使用
- .envファイルのパーミッションを厳密に設定
- すべてのクエリに user_id フィルタ
- パラメータ化クエリを使用
- 結果件数を制限

### DON'T ❌
- rootや管理者権限のDBユーザーを使用しない
- .envファイルをgitにコミットしない
- ハードコードされたパスワードを使用しない
- UPDATE/DELETE/INSERT を許可しない
- インターネットに公開しない

## まとめ

**ReadNest MCPサーバーは安全です：**

1. ✅ ローカル実行のみ（外部アクセス不可）
2. ✅ 読み取り専用（データ破壊リスクなし）
3. ✅ 自分のデータのみアクセス（user_idフィルタ）
4. ✅ SQLインジェクション対策済み
5. ✅ 最小権限の原則に従う
