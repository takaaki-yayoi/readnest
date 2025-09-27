# 著者情報管理ツール一覧

## 概要
b_book_listとb_book_repositoryテーブル間の著者情報を管理・復元するためのツール群です。

## ツール一覧

### 1. 同期・復元ツール

#### sync_authors.php
- **機能**: b_book_list → b_book_repository への著者情報同期
- **用途**: listに著者情報があってrepositoryにない場合の同期
- **特徴**: バッチ処理でメモリ効率的、進捗表示、ログ記録

#### batch_fix_authors.php  
- **機能**: 著者情報の同期（Google Books API機能付き・現在無効）
- **用途**: 将来的にGoogle Books APIを使用する場合に利用
- **特徴**: 3段階処理が可能（現在はStep 1のみ有効）

### 2. 確認・診断ツール

#### quick_check_restore.php ⭐推奨
- **機能**: 全バックアップから復元可能数を一覧表示
- **用途**: どのバックアップが最も効果的か確認
- **特徴**: 復元可能数でランキング表示

#### check_backup_tables.php
- **機能**: バックアップテーブルの一覧と著者情報保有率表示
- **用途**: 各テーブルの状況確認
- **特徴**: プレビューボタン付き

#### check_missing_authors.php
- **機能**: 著者情報欠落状況の詳細統計
- **用途**: 現在の欠落状況を把握
- **特徴**: ISBNあり/なし別の統計

#### check_author_loss.php
- **機能**: バックアップと現在のデータを詳細比較
- **用途**: 著者情報が失われた経緯を調査
- **特徴**: 失われたデータのサンプル表示

### 3. プレビュー・実行ツール

#### preview_restore.php
- **機能**: 復元内容の詳細プレビュー
- **用途**: 復元実行前の最終確認
- **特徴**: 著者別・出版社別統計、サンプル50件表示

#### restore_authors.php
- **機能**: バックアップからの復元実行
- **用途**: 実際の復元処理
- **特徴**: 復元結果の表示、ログ記録

### 4. 進捗管理ツール

#### sync_authors_progress.php
- **機能**: 同期処理の進捗確認
- **用途**: リアルタイムで処理状況を監視
- **特徴**: 自動更新、ログファイル閲覧

### 5. 手動編集ツール

#### missing_authors.php
- **機能**: 著者情報の手動編集
- **用途**: 個別に著者情報を修正
- **特徴**: Google Books API個別検索機能付き

## 推奨ワークフロー

1. **診断**: `quick_check_restore.php` で復元可能数を確認
2. **プレビュー**: `preview_restore.php` で詳細確認
3. **復元**: 「復元を実行」ボタンで復元
4. **同期**: `sync_authors.php` でlist→repositoryを同期
5. **確認**: `check_missing_authors.php` で残りの欠落を確認
6. **手動修正**: 必要に応じて `missing_authors.php` で個別編集

## 注意事項

- 復元は既存の著者情報を上書きしません（安全）
- 複数のバックアップから順番に復元可能
- b_book_repository に著者情報があれば、サイト上では正しく表示される
- Google Books API は現在無効化中（必要時に有効化可能）

## ログファイル

- `/logs/author_sync_*.log` - 同期処理のログ
- `/logs/author_restore_*.log` - 復元処理のログ
- `/logs/author_fix_*.log` - バッチ処理のログ

## データベース構造

- `b_book_list.author` - ユーザーの本棚の著者情報
- `b_book_repository.author` - 共有リポジトリの著者情報
- サイト表示時は `COALESCE(br.author, bl.author, '')` で優先順位付き表示