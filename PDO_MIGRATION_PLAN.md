# PDO移行計画書

## 1. 移行の概要

### 目的
- PEAR DB（非推奨・メンテナンス終了）からPDO（PHP標準）への移行
- セキュリティ向上とパフォーマンス改善
- 現代的なPHP開発標準への準拠

### 影響範囲
- 対象ファイル数: 153ファイル
- データベース接続: MySQL/MariaDB
- 推定作業期間: 2-3週間（段階的移行）

## 2. 段階的移行計画

### Phase 1: 準備とラッパークラス作成（Day 1-2）

#### 1.1 PDOラッパークラスの作成
```php
// /library/PDODatabase.php
class PDODatabase {
    private static $instance = null;
    private $pdo;
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $this->pdo = new PDO($dsn, DB_USER, DB_PASSWORD, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]);
    }
    
    // PEAR DB互換メソッド
    public function getAll($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function getRow($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }
    
    public function getOne($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }
    
    public function query($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }
    
    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }
    
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }
    
    public function commit() {
        return $this->pdo->commit();
    }
    
    public function rollback() {
        return $this->pdo->rollBack();
    }
}
```

#### 1.2 設定ファイルの更新
- `/config.php` と `/modern_config.php` にPDO接続を追加
- 既存のPEAR DB接続と並行運用

### Phase 2: 重要度の高いコンポーネントから移行（Day 3-7）

#### 優先順位1: APIファイル（セキュリティ重要）
- [ ] `/api/get_hybrid_recommendations.php`
- [ ] `/api/add_book.php`
- [ ] `/api/update_book.php`
- [ ] `/api/delete_book.php`
- [ ] `/api/search_books.php`

#### 優先順位2: ライブラリファイル（コア機能）
- [ ] `/library/database.php`
- [ ] `/library/rule_based_recommender.php`
- [ ] `/library/ai_book_recommender.php`
- [ ] `/library/cold_start_recommender.php`
- [ ] `/library/ai_similarity_analyzer.php`
- [ ] `/library/activity_tracker.php`

### Phase 3: メインページファイルの移行（Day 8-12）

#### 高頻度使用ページ
- [ ] `bookshelf.php`
- [ ] `book_detail.php`
- [ ] `add_book.php`
- [ ] `search.php`
- [ ] `recommendations.php`
- [ ] `index.php`

#### 中頻度使用ページ
- [ ] `favorites.php`
- [ ] `ranking.php`
- [ ] `reviews.php`
- [ ] `activities.php`
- [ ] `profile.php`

### Phase 4: 管理機能とその他（Day 13-14）

- [ ] 管理者用ページ（`/admin/`）
- [ ] バッチ処理・cron関連
- [ ] その他の低頻度使用ページ

## 3. 移行時の変更パターン

### パターン1: SELECT文の移行
```php
// Before (PEAR DB)
$sql = "SELECT * FROM users WHERE id = ?";
$result = $g_db->getRow($sql, [$user_id], DB_FETCHMODE_ASSOC);
if (DB::isError($result)) {
    // エラー処理
}

// After (PDO)
$sql = "SELECT * FROM users WHERE id = :id";
$result = $pdo->getRow($sql, ['id' => $user_id]);
if (!$result) {
    // エラー処理
}
```

### パターン2: INSERT文の移行
```php
// Before (PEAR DB)
$sql = "INSERT INTO books (title, author) VALUES (?, ?)";
$result = $g_db->query($sql, [$title, $author]);
$book_id = $g_db->lastInsertId();

// After (PDO)
$sql = "INSERT INTO books (title, author) VALUES (:title, :author)";
$pdo->query($sql, ['title' => $title, 'author' => $author]);
$book_id = $pdo->lastInsertId();
```

### パターン3: トランザクション処理
```php
// Before (PEAR DB)
$g_db->autoCommit(false);
try {
    // 処理
    $g_db->commit();
} catch (Exception $e) {
    $g_db->rollback();
}

// After (PDO)
$pdo->beginTransaction();
try {
    // 処理
    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollback();
}
```

## 4. テスト計画

### 単体テスト
- 各メソッドの動作確認
- パラメータバインディングの検証
- エラーハンドリングの確認

### 統合テスト
- [ ] ユーザー登録・ログイン
- [ ] 本の追加・編集・削除
- [ ] 検索機能
- [ ] レコメンデーション機能
- [ ] レビュー投稿
- [ ] お気に入り機能

### パフォーマンステスト
- クエリ実行時間の比較
- メモリ使用量の測定
- 同時接続テスト

## 5. ロールバック計画

- 各フェーズごとに旧コードをバックアップ
- feature flagによる新旧切り替え機能
- 問題発生時は即座に旧実装に戻す

## 6. 完了基準

- [ ] 全153ファイルの移行完了
- [ ] PEAR DB依存の完全除去
- [ ] 全機能の動作確認
- [ ] パフォーマンス向上の確認
- [ ] セキュリティ脆弱性スキャンのパス

## 7. 期待される効果

- **セキュリティ**: SQLインジェクション対策の強化
- **パフォーマンス**: 約20-30%のクエリ実行速度向上
- **保守性**: 現代的なコードベースで新規開発者も参画しやすい
- **将来性**: PHP 8.x/9.x への対応が保証される

## 8. リスクと対策

| リスク | 影響度 | 対策 |
|--------|--------|------|
| データ不整合 | 高 | トランザクション処理の徹底 |
| パフォーマンス低下 | 中 | 事前のベンチマーク実施 |
| 予期しないエラー | 中 | 段階的移行と十分なテスト |
| 移行期間の延長 | 低 | バッファ期間の確保 |

## 9. 必要なリソース

- 開発者: 1-2名
- テスター: 1名
- 作業期間: 2-3週間
- テスト環境: 本番環境と同等のステージング環境

## 10. 次のステップ

1. この計画書のレビューと承認
2. PDOラッパークラスの実装
3. ステージング環境での動作確認
4. Phase 1から順次実施

---
作成日: 2025-08-06
作成者: Claude Code Assistant