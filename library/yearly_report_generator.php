<?php
/**
 * 年間読書レポート生成ライブラリ
 * データ取得と画像生成を担当
 */

declare(strict_types=1);

require_once(__DIR__ . '/monthly_goals.php');

class YearlyReportGenerator {
    private $width = 1200;
    private $height = 630;  // Twitter/X推奨サイズ
    private $margin = 40;
    private $fontPath;
    private $boldFontPath;

    // ReadNestブランドカラー
    private $primaryColor = [26, 77, 62];    // #1a4d3e
    private $accentColor = [56, 161, 130];   // #38a182
    private $bgColor = [245, 241, 232];      // #f5f1e8

    public function __construct() {
        $this->fontPath = $this->findJapaneseFont();
        $this->boldFontPath = $this->findJapaneseFont(true);
    }

    /**
     * 年間レポートデータを取得
     * @param string|int $user_id ユーザーID
     * @param int $year 年
     * @return array レポートデータ
     */
    public function getReportData($user_id, int $year): array {
        global $g_db;

        $user_id = (string)$user_id;
        $start_date = sprintf('%04d-01-01', $year);
        $end_date = sprintf('%04d-12-31', $year);
        $end_datetime = $end_date . ' 23:59:59';

        // 統計データを取得
        $statistics = $this->getStatistics($user_id, $year, $start_date, $end_datetime);

        // 月別データを取得
        $monthly_data = $this->getMonthlyData($user_id, $year);

        // 読了本リストを取得
        $books = $this->getFinishedBooks($user_id, $start_date, $end_datetime);

        // ジャンル分布を取得
        $genres = $this->getGenreDistribution($user_id, $start_date, $end_datetime);

        return [
            'year' => $year,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'statistics' => $statistics,
            'monthly_data' => $monthly_data,
            'books' => $books,
            'genres' => $genres,
            'has_data' => $statistics['books_finished'] > 0 || count($books) > 0
        ];
    }

    /**
     * 統計データを取得
     */
    private function getStatistics($user_id, int $year, string $start_date, string $end_datetime): array {
        global $g_db;

        // 読了冊数
        $books_finished = 0;
        for ($month = 1; $month <= 12; $month++) {
            $books_finished += getMonthlyAchievement($user_id, $year, $month);
        }

        // ページ数を正しく計算（累積値の差分）
        $pages_read = $this->calculatePagesRead($user_id, $start_date, $end_datetime);

        // 読書日数を取得
        $reading_days_sql = "SELECT COUNT(DISTINCT DATE(event_date)) as reading_days
                             FROM b_book_event
                             WHERE user_id = ?
                             AND event_date >= ?
                             AND event_date <= ?
                             AND event IN (?, ?, ?)";

        $reading_days_result = $g_db->getOne($reading_days_sql, [
            $user_id, $start_date, $end_datetime,
            READING_NOW, READING_FINISH, READ_BEFORE
        ]);
        $reading_days = DB::isError($reading_days_result) ? 0 : (int)$reading_days_result;

        // 年の日数
        $current_year = (int)date('Y');
        if ($year == $current_year) {
            // 当年の場合は今日までの日数
            $days_in_year = (int)date('z') + 1;
        } else {
            // 過去の年は365または366日
            $days_in_year = date('L', strtotime("$year-01-01")) ? 366 : 365;
        }

        // 日平均ページ数
        $daily_average = $days_in_year > 0 ? round($pages_read / $days_in_year, 1) : 0;

        // 月平均冊数（過去の年は12ヶ月、当年は現在月まで）
        $months_count = ($year == $current_year) ? (int)date('n') : 12;
        $monthly_average = $months_count > 0 ? round($books_finished / $months_count, 1) : 0;

        // ベスト月を取得
        $best_month = $this->getBestMonth($user_id, $year);

        // 最長連続日数を取得
        $longest_streak = $this->getLongestStreak($user_id, $start_date, $end_datetime);

        // 最高評価の本を取得
        $best_rated_book = $this->getBestRatedBook($user_id, $start_date, $end_datetime);

        // レビュー数を取得
        $reviews_sql = "SELECT COUNT(*) FROM b_book_list
                        WHERE user_id = ?
                        AND status IN (?, ?)
                        AND memo IS NOT NULL AND memo != ''
                        AND (
                            (finished_date IS NOT NULL AND finished_date >= ? AND finished_date <= ?)
                            OR
                            (finished_date IS NULL AND memo_updated >= ? AND memo_updated <= ?)
                        )";

        $reviews_result = $g_db->getOne($reviews_sql, [
            $user_id,
            READING_FINISH, READ_BEFORE,
            $start_date, $end_datetime,
            strtotime($start_date), strtotime($end_datetime)
        ]);
        $reviews_count = DB::isError($reviews_result) ? 0 : (int)$reviews_result;

        return [
            'books_finished' => $books_finished,
            'pages_read' => $pages_read,
            'reading_days' => $reading_days,
            'daily_average' => $daily_average,
            'monthly_average' => $monthly_average,
            'best_month' => $best_month,
            'longest_streak' => $longest_streak,
            'best_rated_book' => $best_rated_book,
            'reviews_count' => $reviews_count
        ];
    }

    /**
     * 月別データを取得（グラフ用）
     */
    private function getMonthlyData($user_id, int $year): array {
        $data = [];

        for ($month = 1; $month <= 12; $month++) {
            $start_date = sprintf('%04d-%02d-01', $year, $month);
            $end_date = date('Y-m-t', strtotime($start_date));
            $end_datetime = $end_date . ' 23:59:59';

            $books = getMonthlyAchievement($user_id, $year, $month);
            $pages = $this->calculatePagesRead($user_id, $start_date, $end_datetime);

            $data[] = [
                'month' => $month,
                'books' => $books,
                'pages' => $pages
            ];
        }

        return $data;
    }

    /**
     * ベスト月（最多読了月）を取得
     */
    private function getBestMonth($user_id, int $year): array {
        $best = ['month' => 0, 'count' => 0];

        for ($month = 1; $month <= 12; $month++) {
            $count = getMonthlyAchievement($user_id, $year, $month);
            if ($count > $best['count']) {
                $best = ['month' => $month, 'count' => $count];
            }
        }

        return $best;
    }

    /**
     * 最長連続読書日数を取得
     */
    private function getLongestStreak($user_id, string $start_date, string $end_datetime): int {
        global $g_db;

        // 読書した日を取得
        $sql = "SELECT DISTINCT DATE(event_date) as reading_date
                FROM b_book_event
                WHERE user_id = ?
                AND event_date >= ?
                AND event_date <= ?
                AND event IN (?, ?, ?)
                ORDER BY reading_date ASC";

        $results = $g_db->getAll($sql, [
            $user_id, $start_date, $end_datetime,
            READING_NOW, READING_FINISH, READ_BEFORE
        ], DB_FETCHMODE_ASSOC);

        if (DB::isError($results) || empty($results)) {
            return 0;
        }

        $longest = 0;
        $current = 0;
        $prevDate = null;

        foreach ($results as $row) {
            $date = new DateTime($row['reading_date']);

            if ($prevDate !== null) {
                $diff = $prevDate->diff($date)->days;
                if ($diff === 1) {
                    $current++;
                } else {
                    $longest = max($longest, $current);
                    $current = 1;
                }
            } else {
                $current = 1;
            }

            $prevDate = $date;
        }

        $longest = max($longest, $current);

        return $longest;
    }

    /**
     * 最高評価の本を取得
     */
    private function getBestRatedBook($user_id, string $start_date, string $end_datetime): ?array {
        global $g_db;

        $sql = "SELECT
                    book_id,
                    amazon_id,
                    name,
                    author,
                    image_url,
                    rating
                FROM b_book_list
                WHERE user_id = ?
                AND status IN (?, ?)
                AND rating IS NOT NULL AND rating > 0
                AND (
                    (finished_date IS NOT NULL AND finished_date >= ? AND finished_date <= ?)
                    OR
                    (finished_date IS NULL AND update_date >= ? AND update_date <= ?)
                )
                ORDER BY rating DESC, finished_date DESC
                LIMIT 1";

        $result = $g_db->getRow($sql, [
            $user_id,
            READING_FINISH, READ_BEFORE,
            $start_date, $end_datetime,
            $start_date, $end_datetime
        ], DB_FETCHMODE_ASSOC);

        if (DB::isError($result) || empty($result)) {
            return null;
        }

        return $result;
    }

    /**
     * 期間内の読んだページ数を計算（累積値の差分）
     */
    private function calculatePagesRead($user_id, string $start_date, string $end_datetime): int {
        global $g_db;

        // 対象期間のイベントを取得
        $sql = "SELECT book_id, page
                FROM b_book_event
                WHERE user_id = ?
                AND event_date >= ?
                AND event_date <= ?
                AND event IN (?, ?, ?)
                ORDER BY book_id, event_date ASC";

        $results = $g_db->getAll($sql, [
            $user_id, $start_date, $end_datetime,
            READING_NOW, READING_FINISH, READ_BEFORE
        ], DB_FETCHMODE_ASSOC);

        if (DB::isError($results) || empty($results)) {
            return 0;
        }

        // 各本の期間開始前の最終ページ位置を取得
        $book_ids = array_unique(array_column($results, 'book_id'));
        $prev_pages = [];

        if (!empty($book_ids)) {
            $placeholders = implode(',', array_fill(0, count($book_ids), '?'));
            $prev_sql = "SELECT book_id, MAX(page) as last_page
                         FROM b_book_event
                         WHERE user_id = ?
                         AND event_date < ?
                         AND book_id IN ({$placeholders})
                         GROUP BY book_id";

            $params = array_merge([$user_id, $start_date], $book_ids);
            $prev_results = $g_db->getAll($prev_sql, $params, DB_FETCHMODE_ASSOC);

            if (!DB::isError($prev_results)) {
                foreach ($prev_results as $row) {
                    $prev_pages[$row['book_id']] = (int)$row['last_page'];
                }
            }
        }

        // 本ごとの最大ページ数を集計
        $book_max_pages = [];
        foreach ($results as $row) {
            $book_id = $row['book_id'];
            $page = (int)$row['page'];

            if (!isset($book_max_pages[$book_id]) || $page > $book_max_pages[$book_id]) {
                $book_max_pages[$book_id] = $page;
            }
        }

        // 差分を計算
        $total_pages = 0;
        foreach ($book_max_pages as $book_id => $max_page) {
            $prev_page = $prev_pages[$book_id] ?? 0;
            $pages_read = max(0, $max_page - $prev_page);
            $total_pages += $pages_read;
        }

        return $total_pages;
    }

    /**
     * 読了本リストを取得
     */
    private function getFinishedBooks($user_id, string $start_date, string $end_datetime): array {
        global $g_db;

        $sql = "SELECT
                    bl.book_id,
                    bl.amazon_id,
                    bl.name,
                    bl.author,
                    bl.image_url,
                    bl.rating,
                    bl.total_page,
                    bl.finished_date,
                    bl.update_date,
                    bl.memo,
                    MONTH(COALESCE(bl.finished_date, bl.update_date)) as finished_month
                FROM b_book_list bl
                WHERE bl.user_id = ?
                AND bl.status IN (?, ?)
                AND (
                    (bl.finished_date IS NOT NULL AND bl.finished_date >= ? AND bl.finished_date <= ?)
                    OR
                    (bl.finished_date IS NULL AND bl.update_date >= ? AND bl.update_date <= ?)
                )
                ORDER BY COALESCE(bl.finished_date, bl.update_date) ASC";

        $results = $g_db->getAll($sql, [
            $user_id,
            READING_FINISH, READ_BEFORE,
            $start_date, $end_datetime,
            $start_date, $end_datetime
        ], DB_FETCHMODE_ASSOC);

        if (DB::isError($results)) {
            return [];
        }

        return $results;
    }

    /**
     * ジャンル分布を取得
     */
    private function getGenreDistribution($user_id, string $start_date, string $end_datetime): array {
        global $g_db;

        $sql = "SELECT
                    bt.tag_id,
                    t.name as tag_name,
                    COUNT(*) as count
                FROM b_book_list bl
                INNER JOIN b_book_tag bt ON bl.book_id = bt.book_id
                INNER JOIN b_tag t ON bt.tag_id = t.tag_id
                WHERE bl.user_id = ?
                AND bl.status IN (?, ?)
                AND (
                    (bl.finished_date IS NOT NULL AND bl.finished_date >= ? AND bl.finished_date <= ?)
                    OR
                    (bl.finished_date IS NULL AND bl.update_date >= ? AND bl.update_date <= ?)
                )
                GROUP BY bt.tag_id, t.name
                ORDER BY count DESC
                LIMIT 10";

        $results = $g_db->getAll($sql, [
            $user_id,
            READING_FINISH, READ_BEFORE,
            $start_date, $end_datetime,
            $start_date, $end_datetime
        ], DB_FETCHMODE_ASSOC);

        if (DB::isError($results)) {
            return [];
        }

        return $results;
    }

    /**
     * 読書データがある年リストを取得
     * @param string|int $user_id ユーザーID
     * @return array [['year' => 2024, 'book_count' => 42], ...]
     */
    public function getAvailableYears($user_id): array {
        global $g_db;

        $user_id = (string)$user_id;

        // finished_date または update_date から年を抽出し、冊数をカウント
        $sql = "SELECT
                    YEAR(COALESCE(finished_date, update_date)) as year,
                    COUNT(*) as book_count
                FROM b_book_list
                WHERE user_id = ?
                AND status IN (?, ?)
                AND (finished_date IS NOT NULL OR update_date IS NOT NULL)
                GROUP BY year
                ORDER BY year DESC";

        $results = $g_db->getAll($sql, [
            $user_id,
            READING_FINISH, READ_BEFORE
        ], DB_FETCHMODE_ASSOC);

        if (DB::isError($results)) {
            return [];
        }

        return array_map(function($row) {
            return [
                'year' => (int)$row['year'],
                'book_count' => (int)$row['book_count']
            ];
        }, $results);
    }

    /**
     * 年間レポート画像を生成
     * @param array $reportData レポートデータ
     * @param string $userName ユーザー名
     * @return string|null 画像ファイルパス
     */
    public function generateImage(array $reportData, string $userName): ?string {
        // GD拡張の確認
        if (!extension_loaded('gd')) {
            return null;
        }

        // 画像を作成
        $image = imagecreatetruecolor($this->width, $this->height);
        if (!$image) {
            return null;
        }

        // 色を定義
        $bg = imagecolorallocate($image, ...$this->bgColor);
        $primary = imagecolorallocate($image, ...$this->primaryColor);
        $accent = imagecolorallocate($image, ...$this->accentColor);
        $white = imagecolorallocate($image, 255, 255, 255);
        $text = imagecolorallocate($image, 31, 41, 55);
        $lightGray = imagecolorallocate($image, 156, 163, 175);

        // 背景を塗りつぶし
        imagefilledrectangle($image, 0, 0, $this->width, $this->height, $bg);

        // ヘッダーバー
        imagefilledrectangle($image, 0, 0, $this->width, 100, $primary);

        // タイトル
        $title = "{$reportData['year']}年の読書レポート";
        $this->drawText($image, $title, 32, $this->margin, 55, $white, true);

        // ユーザー名
        $this->drawText($image, "@{$userName}", 18, $this->margin, 85, $white);

        // ReadNestロゴ（右上）
        $this->drawText($image, "ReadNest", 24, $this->width - $this->margin - 120, 60, $white, true);

        // 統計カードエリア
        $stats = $reportData['statistics'];
        $cardY = 130;
        $cardWidth = 250;
        $cardHeight = 100;
        $cardGap = 30;

        // カード1: 読了冊数
        $this->drawStatCard($image, $this->margin, $cardY, $cardWidth, $cardHeight,
            '読了', $stats['books_finished'] . '冊', $accent, $white, $text);

        // カード2: ページ数
        $this->drawStatCard($image, $this->margin + $cardWidth + $cardGap, $cardY, $cardWidth, $cardHeight,
            'ページ', number_format($stats['pages_read']), $accent, $white, $text);

        // カード3: 読書日数
        $this->drawStatCard($image, $this->margin + ($cardWidth + $cardGap) * 2, $cardY, $cardWidth, $cardHeight,
            '読書日数', $stats['reading_days'] . '日', $accent, $white, $text);

        // カード4: 月平均冊数
        $this->drawStatCard($image, $this->margin + ($cardWidth + $cardGap) * 3, $cardY, $cardWidth, $cardHeight,
            '月平均', $stats['monthly_average'] . '冊', $accent, $white, $text);

        // 本の表紙エリア（最大5冊）
        $books = array_slice($reportData['books'], 0, 5);
        if (!empty($books)) {
            $bookY = 260;
            $bookWidth = 80;
            $bookHeight = 120;
            $bookGap = 20;
            $startX = $this->margin;

            $this->drawText($image, '読了した本', 18, $startX, $bookY, $text, true);

            $bookY += 30;
            foreach ($books as $i => $book) {
                $bookX = $startX + ($bookWidth + $bookGap) * $i;
                $this->drawBookCover($image, $bookX, $bookY, $bookWidth, $bookHeight, $book);
            }

            // 残りの本がある場合
            if (count($reportData['books']) > 5) {
                $moreText = '+' . (count($reportData['books']) - 5) . '冊';
                $moreX = $startX + ($bookWidth + $bookGap) * 5;
                $this->drawText($image, $moreText, 20, $moreX + 10, $bookY + 60, $lightGray, true);
            }
        }

        // フッター
        $footerY = $this->height - 40;
        $this->drawText($image, 'readnest.jp', 16, $this->width - $this->margin - 100, $footerY, $lightGray);

        // 一時ファイルに保存
        $tempDir = sys_get_temp_dir();
        if (!is_writable($tempDir)) {
            imagedestroy($image);
            return null;
        }

        $tempFile = tempnam($tempDir, 'yearly_report_');
        if ($tempFile === false) {
            imagedestroy($image);
            return null;
        }

        $tempFile .= '.png';
        $result = imagepng($image, $tempFile);
        imagedestroy($image);

        if (!$result) {
            return null;
        }

        return $tempFile;
    }

    /**
     * 統計カードを描画
     */
    private function drawStatCard($image, int $x, int $y, int $width, int $height,
                                   string $label, string $value, $accentColor, $white, $textColor): void {
        // カード背景（白、角丸風に影を追加）
        $shadow = imagecolorallocate($image, 200, 200, 200);
        imagefilledrectangle($image, $x + 3, $y + 3, $x + $width + 3, $y + $height + 3, $shadow);
        imagefilledrectangle($image, $x, $y, $x + $width, $y + $height, $white);

        // 左側にアクセントバー
        imagefilledrectangle($image, $x, $y, $x + 5, $y + $height, $accentColor);

        // ラベル
        $this->drawText($image, $label, 14, $x + 20, $y + 35, $textColor);

        // 値
        $this->drawText($image, $value, 28, $x + 20, $y + 75, $textColor, true);
    }

    /**
     * 本の表紙を描画
     */
    private function drawBookCover($image, int $x, int $y, int $width, int $height, array $book): void {
        $gray = imagecolorallocate($image, 200, 200, 200);

        // 背景（画像がない場合のフォールバック）
        imagefilledrectangle($image, $x, $y, $x + $width, $y + $height, $gray);

        // 画像を読み込み
        if (!empty($book['image_url'])) {
            try {
                $coverImage = @$this->loadImageFromUrl($book['image_url']);
                if ($coverImage) {
                    $srcWidth = imagesx($coverImage);
                    $srcHeight = imagesy($coverImage);
                    imagecopyresampled($image, $coverImage, $x, $y, 0, 0,
                                       $width, $height, $srcWidth, $srcHeight);
                    imagedestroy($coverImage);
                }
            } catch (Exception $e) {
                // 画像読み込み失敗時は灰色背景のまま
            } catch (Error $e) {
                // 画像読み込み失敗時は灰色背景のまま
            }
        }

        // 枠線
        imagerectangle($image, $x, $y, $x + $width, $y + $height, $gray);
    }

    /**
     * URLから画像を読み込み
     */
    private function loadImageFromUrl(string $url) {
        try {
            // ローカルパスの場合（/uploads/... など）
            if (strpos($url, '/') === 0 && strpos($url, '//') !== 0) {
                $localPath = $_SERVER['DOCUMENT_ROOT'] . $url;
                if (!$localPath) {
                    $localPath = dirname(__DIR__) . $url;
                }

                if (!file_exists($localPath)) {
                    return null;
                }

                $imageData = @file_get_contents($localPath);
            } else {
                // 外部URL
                $context = stream_context_create([
                    'http' => [
                        'timeout' => 3,
                        'user_agent' => 'ReadNest/1.0'
                    ],
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false
                    ]
                ]);

                $imageData = @file_get_contents($url, false, $context);
            }

            if ($imageData === false) {
                return null;
            }

            $image = @imagecreatefromstring($imageData);
            if (!$image) {
                return null;
            }

            return $image;
        } catch (Exception $e) {
            return null;
        } catch (Error $e) {
            return null;
        }
    }

    /**
     * テキストを描画
     */
    private function drawText($image, string $text, int $size, int $x, int $y, $color, bool $bold = false): void {
        $fontToUse = ($bold && $this->boldFontPath) ? $this->boldFontPath : $this->fontPath;

        if ($fontToUse) {
            imagettftext($image, $size, 0, $x, $y, $color, $fontToUse, $text);
            if ($bold && !$this->boldFontPath) {
                // 太字フォントがない場合は重ね描き
                imagettftext($image, $size, 0, $x + 1, $y, $color, $fontToUse, $text);
            }
        } else {
            // フォールバック
            $font = $bold ? 5 : 3;
            imagestring($image, $font, $x, $y - 15, $text, $color);
        }
    }

    /**
     * 日本語フォントを探す
     */
    private function findJapaneseFont(bool $bold = false): ?string {
        $fontPaths = [];

        if ($bold) {
            $fontPaths = [
                '/home/icotfeels/fonts/MPlus/MPLUSRounded1c-Bold.ttf',
                '/home/icotfeels/fonts/MPlus/MPLUSRounded1c-Regular.ttf',
                '/home/icotfeels/fonts/NotoSansJP-Bold.ttf',
                '/System/Library/Fonts/ヒラギノ角ゴシック W6.ttc',
                '/usr/share/fonts/truetype/fonts-japanese-gothic-bold.ttf',
            ];
        } else {
            $fontPaths = [
                '/home/icotfeels/fonts/MPlus/MPLUSRounded1c-Regular.ttf',
                '/home/icotfeels/fonts/NotoSansJP-VariableFont_wght.ttf',
                '/System/Library/Fonts/ヒラギノ角ゴシック W3.ttc',
                '/usr/share/fonts/truetype/fonts-japanese-gothic.ttf',
            ];
        }

        foreach ($fontPaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }
}
?>
