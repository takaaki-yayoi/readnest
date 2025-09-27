# ファイルクリーンアップリスト
日付: 2025-01-09

## 削除可能なファイル（デバッグ・テスト用）

### エンベディング関連の旧バージョン・テストファイル
- [ ] test_embedding_generator.php （test_embedding_cli.phpに統合済み）
- [ ] generate_embeddings_with_description.php （generate_enhanced_embeddings.phpに統合済み）
- [ ] generate_title_embeddings.php （generate_enhanced_embeddings.phpに統合済み）
- [ ] batch_generate_embeddings.php （旧バージョン）
- [ ] auto_generate_embeddings.php （auto_generate_embeddings_cli.phpに置き換え済み）
- [ ] check_embedding_status.php （embedding_debug_enhanced.phpに機能統合）
- [ ] embedding_debug.php （embedding_debug_enhanced.phpに置き換え済み）

### タグ関連のデバッグファイル（問題解決済み）
- [ ] analyze_tag_problem.php
- [ ] analyze_tag_query_plan.php
- [ ] check_index_performance.php
- [ ] check_index_performance_simple.php
- [ ] compare_tag_search_performance.php
- [ ] diagnose_popular_tags.php
- [ ] diagnose_popular_tags_new.php
- [ ] disable_popular_tags.php
- [ ] enable_popular_tags.php
- [ ] emergency_fix_tags.php
- [ ] optimize_tag_search_immediate.php
- [ ] quick_tag_diagnosis.php
- [ ] run_minimal_tag_update.php
- [ ] apply_tag_search_optimization.php

### 画像修正関連の一時ファイル（処理完了）
- [ ] analyze_image_urls.php
- [ ] check_book_images.php
- [ ] check_image_patterns.php
- [ ] diagnose_book_images.php
- [ ] fix_all_book_images_final.php
- [ ] fix_all_broken_images.php
- [ ] fix_book_images_simple.php
- [ ] fix_images_with_preview.php
- [ ] fix_noimage_references.php
- [ ] fix_not_available_images.php
- [ ] fix_single_book_image.php
- [ ] bulk_fix_book_images.php

### ユーザーデータ修正の一時ファイル（処理完了）
- [ ] check_null_nicknames.php
- [ ] check_nickname_cache.php
- [ ] fix_empty_nicknames.php
- [ ] fix_deleted_users.php
- [ ] check_user_12_stats.php
- [ ] fix_user_12_stats.php
- [ ] fix_user_stats.php
- [ ] check_june_reading_pages.php
- [ ] check_status_5_books.php

### 日付・データ修正の一時ファイル（処理完了）
- [ ] check_datetime_issues.php
- [ ] fix_invalid_dates.php
- [ ] fix_update_dates.php
- [ ] fix_regist_date_null.php

### キャッシュ関連のデバッグファイル
- [ ] cache_diagnostics.php
- [ ] cache_inspector.php
- [ ] clear_popular_books_cache.php
- [ ] clear_popular_tags_cache.php
- [ ] clear_review_cache.php
- [ ] force_clear_activities_cache.php

### その他の一時ファイル
- [ ] check_total_pages.php
- [ ] check_book_table_structure.php
- [ ] check_author_issue.php
- [ ] check_author_data.php
- [ ] check_popular_books.php
- [ ] check_tables.php
- [ ] update_existing_users_tutorial.php
- [ ] reset_tutorial_flag.php

## 保持すべきファイル

### エンベディング関連（最新版）
- ✅ generate_enhanced_embeddings_cli.php （メインCLIスクリプト）
- ✅ test_embedding_cli.php （環境テスト）
- ✅ test_api_only.php （API接続テスト）
- ✅ embedding_debug_enhanced.php （デバッグUI）
- ✅ clear_embeddings.sql （データクリア用）
- ✅ EMBEDDING_SETUP.md （セットアップガイド）
- ✅ UPLOAD_FILES.txt （アップロードリスト）

### 管理機能（必須）
- ✅ index.php
- ✅ login.php
- ✅ admin_auth.php
- ✅ admin_helpers.php
- ✅ users.php
- ✅ statistics.php
- ✅ logs.php
- ✅ announcements.php
- ✅ contacts.php
- ✅ site_settings.php
- ✅ x_integration.php
- ✅ registration_logs.php
- ✅ cron_management.php
- ✅ cron_status.php
- ✅ image_management.php
- ✅ manage_uploaded_images.php
- ✅ book_processing.php
- ✅ recommendation_verification.php
- ✅ level_distribution.php

### 初期設定・マイグレーション（保持）
- ✅ add_reading_analysis_table.php
- ✅ add_google_auth_table.php
- ✅ add_performance_indexes.php
- ✅ add_tag_indexes.php
- ✅ add_finished_date_column.php
- ✅ add_first_login_flag.php
- ✅ apply_performance_indexes.php
- ✅ apply_x_oauth_migration.php
- ✅ migrate_user_status.php
- ✅ datetime_migration.php
- ✅ setup_favorites.php
- ✅ setup_sakka_cloud.php
- ✅ setup_upload_dirs.php
- ✅ check_upload_dirs.php
- ✅ optimize_database.php

### その他の機能
- ✅ clean_interim_users.php （定期実行用）
- ✅ update_popular_books.php （定期更新）
- ✅ update_popular_tags_manually.php （手動更新）
- ✅ regenerate_sakka_cloud.php （作家クラウド再生成）
- ✅ fix_activities_cache.php （キャッシュ修正）
- ✅ fix_book_repository.php （リポジトリ修正）
- ✅ check_mail_config.php （メール設定確認）
- ✅ cache_clear.php （キャッシュクリア）
- ✅ update_descriptions.php （説明文更新）

### レイアウトファイル（必須）
- ✅ layout/footer.php
- ✅ layout/header.php
- ✅ layout/submenu.php
- ✅ layout/utility_menu.php

### ドキュメント
- ✅ setup_openai_key.md （APIキー設定ガイド）

## 削除コマンド（サーバー上で実行）

```bash
# 削除前に必ずバックアップを作成
cd /home/icotfeels/readnest.jp/public_html/admin
tar -czf backup_admin_files_20250109.tar.gz *.php

# 削除実行（慎重に！）
rm -f test_embedding_generator.php
rm -f generate_embeddings_with_description.php
rm -f generate_title_embeddings.php
rm -f batch_generate_embeddings.php
rm -f auto_generate_embeddings.php
rm -f check_embedding_status.php
rm -f embedding_debug.php

# タグ関連デバッグファイルの削除
rm -f analyze_tag_problem.php analyze_tag_query_plan.php
rm -f check_index_performance*.php compare_tag_search_performance.php
rm -f diagnose_popular_tags*.php disable_popular_tags.php enable_popular_tags.php
rm -f emergency_fix_tags.php optimize_tag_search_immediate.php
rm -f quick_tag_diagnosis.php run_minimal_tag_update.php
rm -f apply_tag_search_optimization.php

# 画像修正関連の削除
rm -f analyze_image_urls.php check_book_images.php check_image_patterns.php
rm -f diagnose_book_images.php fix_all_book_images_final.php
rm -f fix_all_broken_images.php fix_book_images_simple.php
rm -f fix_images_with_preview.php fix_noimage_references.php
rm -f fix_not_available_images.php fix_single_book_image.php
rm -f bulk_fix_book_images.php

# ユーザーデータ修正関連の削除
rm -f check_null_nicknames.php check_nickname_cache.php
rm -f fix_empty_nicknames.php fix_deleted_users.php
rm -f check_user_12_stats.php fix_user_12_stats.php fix_user_stats.php
rm -f check_june_reading_pages.php check_status_5_books.php

# その他の一時ファイル削除
rm -f check_datetime_issues.php fix_invalid_dates.php
rm -f fix_update_dates.php fix_regist_date_null.php
rm -f cache_diagnostics.php cache_inspector.php
rm -f clear_popular_books_cache.php clear_popular_tags_cache.php
rm -f clear_review_cache.php force_clear_activities_cache.php
rm -f check_total_pages.php check_book_table_structure.php
rm -f check_author_issue.php check_author_data.php
rm -f check_popular_books.php check_tables.php
rm -f update_existing_users_tutorial.php reset_tutorial_flag.php
```

## 削除後の確認

```bash
# ファイル数の確認
ls -la | wc -l

# 削除されたことを確認
ls -la | grep -E "(test_embedding_generator|batch_generate|analyze_tag)"
```

## 注意事項
- 削除前に必ずバックアップを作成すること
- 実行中のcronジョブが参照していないか確認
- 他のスクリプトから呼び出されていないか確認