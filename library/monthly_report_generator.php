<?php
/**
 * 月間読書レポート生成ライブラリ
 * データ取得と画像生成を担当
 */

declare(strict_types=1);

require_once(__DIR__ . '/monthly_goals.php');

class MonthlyReportGenerator {
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
     * 月間レポートデータを取得
     * @param string|int $user_id ユーザーID
     * @param int $year 年
     * @param int $month 月
     * @return array レポートデータ
     */
    public function getReportData($user_id, int $year, int $month): array {
        global $g_db;

        $user_id = (string)$user_id;
        $start_date = sprintf('%04d-%02d-01', $year, $month);
        $end_date = date('Y-m-t', strtotime($start_date));
        $end_datetime = $end_date . ' 23:59:59';

        // 統計データを取得
        $statistics = $this->getStatistics($user_id, $year, $month, $start_date, $end_datetime);

        // 日別アクティビティを取得
        $daily_activity = $this->getDailyActivity($user_id, $start_date, $end_date);

        // 読了本リストを取得
        $books = $this->getFinishedBooks($user_id, $start_date, $end_datetime);

        return [
            'year' => $year,
            'month' => $month,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'statistics' => $statistics,
            'daily_activity' => $daily_activity,
            'books' => $books,
            'has_data' => $statistics['books_finished'] > 0 || count($books) > 0
        ];
    }

    /**
     * 統計データを取得
     */
    private function getStatistics($user_id, int $year, int $month, string $start_date, string $end_datetime): array {
        global $g_db;

        // 読了冊数（既存関数を利用）
        $books_finished = getMonthlyAchievement($user_id, $year, $month);

        // ページ数を取得（b_book_eventから）
        $pages_sql = "SELECT COALESCE(SUM(page), 0) as total_pages
                      FROM b_book_event
                      WHERE user_id = ?
                      AND event_date >= ?
                      AND event_date <= ?
                      AND event IN (?, ?)";

        $pages_result = $g_db->getOne($pages_sql, [
            $user_id, $start_date, $end_datetime,
            READING_NOW, READING_FINISH
        ]);
        $total_pages = DB::isError($pages_result) ? 0 : (int)$pages_result;

        // 読了本のページ数合計も取得
        $finished_pages_sql = "SELECT COALESCE(SUM(bl.total_page), 0) as finished_pages
                               FROM b_book_list bl
                               WHERE bl.user_id = ?
                               AND bl.status IN (?, ?)
                               AND (
                                   (bl.finished_date IS NOT NULL AND bl.finished_date >= ? AND bl.finished_date <= ?)
                                   OR
                                   (bl.finished_date IS NULL AND bl.update_date >= ? AND bl.update_date <= ?)
                               )";

        $finished_pages_result = $g_db->getOne($finished_pages_sql, [
            $user_id,
            READING_FINISH, READ_BEFORE,
            $start_date, $end_datetime,
            $start_date, $end_datetime
        ]);
        $finished_pages = DB::isError($finished_pages_result) ? 0 : (int)$finished_pages_result;

        // ページ数は読了本のページ数を優先（より正確）
        $pages_read = max($total_pages, $finished_pages);

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

        // 月の日数
        $days_in_month = (int)date('t', strtotime($start_date));

        // 当月の場合は今日までの日数
        $current_year = (int)date('Y');
        $current_month = (int)date('n');
        if ($year == $current_year && $month == $current_month) {
            $days_in_month = (int)date('j');
        }

        // 日平均ページ数
        $daily_average = $days_in_month > 0 ? round($pages_read / $days_in_month, 1) : 0;

        // 目標データを取得
        $goal_data = getMonthlyGoal($user_id, $year, $month);
        $goal = $goal_data['goal'];
        $goal_progress = calculateMonthlyProgress($books_finished, $goal);

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
            'goal' => $goal,
            'goal_progress' => $goal_progress,
            'goal_achieved' => $goal > 0 && $books_finished >= $goal,
            'reviews_count' => $reviews_count
        ];
    }

    /**
     * 日別アクティビティを取得
     */
    private function getDailyActivity($user_id, string $start_date, string $end_date): array {
        global $g_db;

        $sql = "SELECT
                    DATE(event_date) as date,
                    SUM(page) as pages,
                    COUNT(DISTINCT CASE WHEN event = ? THEN book_id END) as books_finished
                FROM b_book_event
                WHERE user_id = ?
                AND event_date >= ?
                AND event_date <= ?
                AND event IN (?, ?, ?)
                GROUP BY DATE(event_date)
                ORDER BY date";

        $results = $g_db->getAll($sql, [
            READING_FINISH,
            $user_id, $start_date, $end_date . ' 23:59:59',
            READING_NOW, READING_FINISH, READ_BEFORE
        ], DB_FETCHMODE_ASSOC);

        if (DB::isError($results)) {
            return [];
        }

        // 月の全日を含む配列を作成
        $activity = [];
        $current = new DateTime($start_date);
        $end = new DateTime($end_date);

        while ($current <= $end) {
            $dateStr = $current->format('Y-m-d');
            $activity[$dateStr] = [
                'date' => $dateStr,
                'day' => (int)$current->format('j'),
                'pages' => 0,
                'books_finished' => 0
            ];
            $current->modify('+1 day');
        }

        // 取得したデータをマージ
        foreach ($results as $row) {
            if (isset($activity[$row['date']])) {
                $activity[$row['date']]['pages'] = (int)$row['pages'];
                $activity[$row['date']]['books_finished'] = (int)$row['books_finished'];
            }
        }

        return array_values($activity);
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
                    bl.memo
                FROM b_book_list bl
                WHERE bl.user_id = ?
                AND bl.status IN (?, ?)
                AND (
                    (bl.finished_date IS NOT NULL AND bl.finished_date >= ? AND bl.finished_date <= ?)
                    OR
                    (bl.finished_date IS NULL AND bl.update_date >= ? AND bl.update_date <= ?)
                )
                ORDER BY COALESCE(bl.finished_date, bl.update_date) DESC";

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
     * 月間レポート画像を生成
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
        $title = "{$reportData['year']}年{$reportData['month']}月の読書レポート";
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

        // カード3: 日平均
        $this->drawStatCard($image, $this->margin + ($cardWidth + $cardGap) * 2, $cardY, $cardWidth, $cardHeight,
            '日平均', $stats['daily_average'] . 'p', $accent, $white, $text);

        // カード4: 目標達成
        $goalText = $stats['goal'] > 0 ? round($stats['goal_progress']) . '%' : '-';
        $goalColor = $stats['goal_achieved'] ? [34, 197, 94] : $this->accentColor;
        $goalColorAlloc = imagecolorallocate($image, ...$goalColor);
        $this->drawStatCard($image, $this->margin + ($cardWidth + $cardGap) * 3, $cardY, $cardWidth, $cardHeight,
            '目標達成', $goalText, $goalColorAlloc, $white, $text);

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

        $tempFile = tempnam($tempDir, 'monthly_report_');
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

        // 画像を読み込み（タイムアウト対策で無効化可能）
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
     * @param string $url 画像URL（外部URLまたはローカルパス）
     * @return resource|object|null GD画像リソース（PHP7ではresource、PHP8ではGdImage）
     */
    private function loadImageFromUrl(string $url) {
        try {
            // ローカルパスの場合（/uploads/... など）
            if (strpos($url, '/') === 0 && strpos($url, '//') !== 0) {
                // ドキュメントルートからのパスに変換
                $localPath = $_SERVER['DOCUMENT_ROOT'] . $url;
                if (!$localPath) {
                    // DOCUMENT_ROOTが取得できない場合
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

/**
 * X(Twitter)シェアURLを生成
 */
function getXShareUrl(string $text, string $url, array $hashtags = []): string {
    $params = [
        'text' => $text,
        'url' => $url
    ];

    if (!empty($hashtags)) {
        $params['hashtags'] = implode(',', $hashtags);
    }

    return 'https://twitter.com/intent/tweet?' . http_build_query($params);
}
?>
