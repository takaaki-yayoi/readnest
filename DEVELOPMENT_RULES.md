# ReadNest 開発ルール

## 重要な開発規則

### 1. update_date フィールドの更新ルール

**絶対厳守**: `b_book_list.update_date` は以下の場合に必ず更新すること

#### 更新が必要な操作
- ✅ 読書ステータスの変更（読みたい、読書中、読了、中断）
- ✅ 読書進捗の更新（current_pageの変更）
- ✅ 評価（rating）の追加・変更
- ✅ レビュー（review）の投稿・編集
- ✅ メモの更新

#### 更新してはいけない操作
- ❌ 書籍画像URLの変更
- ❌ タイトルの修正
- ❌ 著者名の修正
- ❌ ISBN/ASINの変更
- ❌ その他の書誌情報の変更

### 2. 重要なファイルと関数

#### library/database.php
- `createEvent()` 関数（1082行目付近）
  - 読書進捗・ステータス変更時の処理
  - **必ず** `update_date=NOW()` を含むこと
  - 1167行目: `$sql = 'update b_book_list set status=?, update_date=NOW(), current_page=? where book_id=?';`

- `updateBook()` 関数（946行目付近）
  - レビュー・評価更新時の処理
  - **必ず** `update_date=NOW()` を含むこと

### 3. コード変更時のチェックリスト

#### createEvent() 関数を修正する場合
```php
// ✅ 正しい実装（必ずupdate_dateを更新）
$sql = 'update b_book_list set status=?, update_date=NOW(), current_page=? where book_id=?';

// ❌ 間違った実装（条件付きでupdate_dateを更新）
if ($status != $book_status) {
    $sql = 'update b_book_list set status=?, update_date=NOW(), current_page=? where book_id=?';
} else {
    $sql = 'update b_book_list set status=?, current_page=? where book_id=?';  // NG!
}
```

### 4. 問題が発生した場合の対処

#### 管理画面から修正
1. `/admin/fix_update_dates.php` - update_dateの不整合を修正
2. `/admin/fix_invalid_dates.php` - 無効な日付（-0001-11-30等）を修正

#### コマンドラインから修正
```bash
php /path/to/readnest/scripts/check_update_dates.php  # チェック
php /path/to/readnest/scripts/fix_update_dates.php    # 修正
```

## なぜこのルールが重要か

### 背景
- お気に入りページや本棚では「更新日」でソートされる
- ユーザーは最近読んだ本を上位に表示したい
- 読書進捗を更新しても更新日が変わらないと、古い本として扱われてしまう

### 過去の問題
- 2025年8月2日: 読書進捗更新時にupdate_dateが更新されない問題が発生
- 原因: createEvent()関数で条件付き更新になっていた
- 影響: お気に入りページで本の並び順が正しくない

## テスト方法

### 読書進捗更新のテスト
1. 本の詳細ページで進捗を更新
2. データベースで確認：
```sql
SELECT book_id, name, update_date, current_page 
FROM b_book_list 
WHERE book_id = [対象のbook_id];
```
3. update_dateが現在時刻に更新されていることを確認

### 自動テスト（将来実装予定）
```php
// tests/UpdateDateTest.php
public function testProgressUpdateChangesUpdateDate() {
    $before = getBookUpdateDate($bookId);
    updateProgress($bookId, $userId, 100);
    $after = getBookUpdateDate($bookId);
    
    $this->assertGreaterThan($before, $after);
}
```

## CLAUDE.md との関係

このファイルは `CLAUDE.md` の内容を補完し、より詳細な技術仕様を提供します。
`CLAUDE.md` にある以下の記載を具体的に実装するためのガイドです：

> **重要**: `update_date`は読書状態（ステータス、評価、レビューなど）の更新時のみ更新すること

---

最終更新: 2025年8月2日
作成者: ReadNest開発チーム