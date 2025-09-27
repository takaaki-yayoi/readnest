# ReadNest 開発ガイドライン

## 新規ページ作成時のチェックリスト

### 1. メインPHPファイル
- [ ] `modern_config.php`を読み込む
- [ ] ログインチェックが必要な場合は`checkLogin()`を使用
- [ ] テンプレートは`include(getTemplatePath('t_ファイル名.php'))`で読み込む
- [ ] CONFIGの定義は不要（modern_config.php経由で自動的に定義される）

### 2. テンプレートファイル（template/modern/）
- [ ] ファイル先頭でCONFIGチェックを行う
```php
<?php
if(!defined('CONFIG')) {
    error_log('direct access detected...');
    die('reference for this file is not allowed.');
}
```
- [ ] コンテンツは`ob_start()`と`ob_get_clean()`で囲む
- [ ] **重要**: 変数名は`$d_content`を使用（`$content`ではない）
- [ ] 最後に`include(__DIR__ . '/t_base.php')`を呼び出す

### 3. 既存パターンの確認
新規ページを作成する前に、類似の既存ページを参考にする：
- プロフィールページ: `profile.php` → `t_profile.php`
- 本棚ページ: `bookshelf.php` → `t_bookshelf.php`
- 書籍詳細ページ: `book_detail.php` → `t_book_detail.php`

### 4. デバッグ手法
問題が発生した場合：
1. HTMLコメントでデバッグメッセージを出力
```php
echo "<!-- Debug: 処理名 -->\n";
```
2. 変数の存在確認
```php
echo "<!-- Debug: 変数名 = " . (isset($変数) ? '存在' : '未定義') . " -->\n";
```
3. テンプレートの読み込み確認
```php
$template_path = getTemplatePath('t_ファイル名.php');
if (file_exists($template_path)) {
    include($template_path);
} else {
    error_log("Template not found: " . $template_path);
}
```

## よくある間違い

### 1. 変数名の間違い
- ❌ `$content = ob_get_clean();`
- ✅ `$d_content = ob_get_clean();`

### 2. CONFIG定義の重複
- ❌ メインファイルで`define('CONFIG', true)`を記述
- ✅ `modern_config.php`に任せる

### 3. セッション管理
- ❌ `session_start()`を呼び出す
- ✅ `modern_config.php`が自動的に処理

### 4. ログインユーザーID
- ❌ `$g_login_id`を使用
- ✅ `$_SESSION['AUTH_USER']`または`$mine_user_id`を使用

## テンプレート変数一覧

### 必須変数
- `$d_content`: メインコンテンツ（HTML）
- `$d_site_title`: ページタイトル
- `$g_meta_description`: メタディスクリプション
- `$g_meta_keyword`: メタキーワード

### オプション変数
- `$d_additional_scripts`: 追加のJavaScript（</body>前に挿入）
- `$g_seo_tags`: SEO関連のメタタグ
- `$g_analytics`: アナリティクスコード