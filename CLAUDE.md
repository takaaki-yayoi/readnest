# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Development Notes
- 問題が解決したらデバッグ用コードをクリーンアップ

## 追加メモリ
- update_dateは「ユーザーの更新」によるもの。管理者の更新は関係ない
- **重要**: `update_date`は読書状態（ステータス、評価、レビューなど）の更新時のみ更新すること
  - 更新する場合: 読書ステータス変更、**読書進捗更新（current_page）**、評価追加、レビュー投稿
  - 更新しない場合: 画像URL変更、タイトル修正、著者名修正、ページ数更新、その他書誌情報の変更
  - **⚠️ 詳細な実装ルールは DEVELOPMENT_RULES.md を必ず参照すること**
- getCol()メソッドは存在しない

## テンプレートシステムに関する重要な注意点（2025年8月1日追加）

### テンプレート変数名
**重要**: モダンテンプレートシステムでは、コンテンツ変数名は`$d_content`を使用すること。
```php
// 正しい
$d_content = ob_get_clean();

// 間違い（動作しない）
$content = ob_get_clean();
```

### 新規ページ作成時のパターン
1. メインPHPファイルの基本構造：
```php
<?php
require_once('modern_config.php');

// ログインチェック
$login_flag = checkLogin();
if (!$login_flag) {
    header('Location: /');
    exit;
}

$mine_user_id = $_SESSION['AUTH_USER'];

// ビジネスロジック

// ページメタ情報
$d_site_title = 'ページタイトル - ReadNest';
$g_meta_description = 'メタディスクリプション';
$g_meta_keyword = 'キーワード';

// テンプレートを読み込み
include(getTemplatePath('t_ファイル名.php'));
?>
```

2. テンプレートファイルの基本構造：
```php
<?php
if(!defined('CONFIG')) {
    error_log('direct access detected...');
    die('reference for this file is not allowed.');
}

// ヘルパー関数を読み込み
require_once(dirname(dirname(__DIR__)) . '/library/form_helpers.php');

// メインコンテンツを生成
ob_start();
?>

<!-- HTMLコンテンツ -->

<?php
$d_content = ob_get_clean();

// ベーステンプレートを使用
include(__DIR__ . '/t_base.php');
?>
```

### デバッグ時の推奨手順
1. 最初から体系的なデバッグメッセージを埋め込む
2. HTMLコメントで出力してブラウザのソース表示で確認
3. 既存の動作しているページと比較してパターンを確認

## Domain Information
- 私のドメインはreadnest.jp

## 外部API設定
- **OpenAI API**: `config.php`に`OPENAI_API_KEY`定数として設定済み
  - 作家情報取得（`/library/author_info_fetcher.php`）で使用
  - WikipediaにないLLMによる作家説明生成に利用
- OpenAI APIは活用できるので、利用できるところでは遠慮せずに活用する実装にしてください
- ローカルでデータベースは稼働していない
- 実装を行う際に他の類似ページの実装を必ず確認
- 現状を確認してから実装計画を立てる。機能の重複は避ける。事実に基づかないコメントはしない
- 既存の機能を消す際には確認する