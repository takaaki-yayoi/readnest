# ReadNest Cron設定ガイド

## 推奨されるcron設定

以下のcronジョブを設定することで、サイトのパフォーマンスと機能を最適化できます。

### 1. 人気タグキャッシュ更新（重要度：高）
```bash
# 毎時0分に実行
0 * * * * /usr/bin/php /home/icotfeels/readnest.jp/public_html/cron/update_popular_tags_cache.php
```
- **目的**: 人気タグの事前計算とキャッシュ更新
- **影響**: タグクラウドとタグ検索のパフォーマンス向上

### 2. タグ検索サマリー更新（重要度：高）
```bash
# 毎日午前3時に実行
0 3 * * * /usr/bin/php /home/icotfeels/readnest.jp/public_html/cron/update_tag_search_summary.php
```
- **目的**: 人気タグの検索結果を事前計算
- **影響**: タグ検索の99.98%高速化

### 3. 人気の本キャッシュ更新（重要度：中）
```bash
# 2時間ごとに実行
0 */2 * * * /usr/bin/php /home/icotfeels/readnest.jp/public_html/cron/update_popular_books.php
```
- **目的**: トップページの「みんなの読んでいる本」更新
- **影響**: トップページの表示速度向上

### 4. キャッシュウォーマー（重要度：中）
```bash
# 10分ごとに実行
*/10 * * * * /usr/bin/php /home/icotfeels/readnest.jp/public_html/cron/cache_warmer.php
```
- **目的**: 重要なキャッシュを事前に生成
- **影響**: ユーザーの初回アクセス時の速度向上

### 5. アクティビティキャッシュクリア（重要度：低）
```bash
# 30分ごとに実行
*/30 * * * * /usr/bin/php /home/icotfeels/readnest.jp/public_html/cron/clear_activities_cache.php
```
- **目的**: 古いアクティビティキャッシュのクリア
- **影響**: キャッシュストレージの最適化

### 6. データベース更新（重要度：低）
```bash
# 毎日午前4時に実行
0 4 * * * /usr/bin/php /home/icotfeels/readnest.jp/public_html/cron/updateDB.php
```
- **目的**: Amazon APIから書籍情報を更新
- **影響**: 書籍情報の鮮度維持

### 7. ユーザー読書統計更新（重要度：高）
```bash
# 毎日午前3時に実行
0 3 * * * /usr/bin/php /home/icotfeels/readnest.jp/public_html/cron/update_user_reading_stats.php
```
- **目的**: ユーザーの読書統計（月間・累計読了数）を更新
- **影響**: ランキング表示の正確性確保
- **注意**: 読了イベント時に自動更新されますが、データ整合性のため定期実行を推奨

### 8. 仮登録ユーザークリーンアップ（重要度：高）
```bash
# 10分ごとに実行
*/10 * * * * /usr/bin/php /home/icotfeels/readnest.jp/public_html/cron/clean_interim_users.php
```
- **目的**: 1時間経過した仮登録ユーザーを自動削除
- **影響**: データベースのクリーン化とメールアドレスの再利用可能化
- **注意**: 仮登録のURLは1時間で無効になるため、必ず設定してください

### 9. 月間レポート通知（重要度：中）
```bash
# 毎月1日午前9時に実行
0 9 1 * * /usr/bin/php /home/icotfeels/readnest.jp/public_html/cron/generate_monthly_notifications.php
```
- **目的**: 前月に読書活動があったユーザーに月間レポート通知を送信
- **影響**: ユーザーエンゲージメントの向上
- **注意**: b_notificationsテーブルが必要

## cron設定方法

### 1. crontabを編集
```bash
crontab -e
```

### 2. 上記の設定を追加
必要なジョブを選んでcrontabファイルに追加してください。

### 3. 設定を確認
```bash
crontab -l
```

## 重要な注意事項

1. **PHPパスの確認**
   - `/usr/bin/php`のパスが正しいか確認してください
   - 確認コマンド: `which php`

2. **ファイルパスの確認**
   - 実際のファイルパスに合わせて調整してください
   - 本番環境: `/home/icotfeels/readnest.jp/public_html/`

3. **実行権限**
   - cronスクリプトに実行権限があることを確認
   - `chmod +x /path/to/script.php`

4. **ログの確認**
   - cronの実行ログを確認: `/var/log/cron`
   - PHPエラーログも確認

## 最小限の設定

パフォーマンスを重視する場合、最低限以下の2つを設定してください：

```bash
# 人気タグキャッシュ（毎時）
0 * * * * /usr/bin/php /home/icotfeels/readnest.jp/public_html/cron/update_popular_tags_cache.php

# タグ検索サマリー（毎日）
0 3 * * * /usr/bin/php /home/icotfeels/readnest.jp/public_html/cron/update_tag_search_summary.php
```

これにより、タグ関連機能の高速化が実現されます。