<?php
/**
 * 読書傾向分析を画像に変換するライブラリ
 */

declare(strict_types=1);

class AnalysisImageGenerator {
    private $width = 1800;     // 1200 * 1.5
    private $height = 945;     // 630 * 1.5 実際の高さ（動的に変更）
    private $minHeight = 945;  // 630 * 1.5 Twitter/X推奨最小サイズ
    private $margin = 60;      // 40 * 1.5
    private $lineHeight = 45;  // 30 * 1.5
    private $fontPath;
    private $boldFontPath;
    private $sectionSpacing = 36; // 24 * 1.5 セクション間の余白
    private $maxHeight = 3000; // 2000 * 1.5 最大高さ制限
    
    public function __construct() {
        // 日本語フォントのパス（環境によって調整が必要）
        $this->fontPath = $this->findJapaneseFont();
        $this->boldFontPath = $this->findJapaneseFont(true);
    }
    
    /**
     * 読書傾向分析を画像に変換
     */
    public function generateImage(string $analysisContent, string $userName, string $createdAt): ?string {
        // まず必要な高さを計算
        $lines = $this->processAnalysisContent($analysisContent);
        $estimatedHeight = $this->calculateRequiredHeight($lines);
        $this->height = intval(min(max($estimatedHeight, $this->minHeight), $this->maxHeight));
        
        // 画像を作成
        $image = imagecreatetruecolor($this->width, $this->height);
        if (!$image) {
            return null;
        }
        
        // 色を定義
        $bgColor = imagecolorallocate($image, 255, 255, 255); // 白に変更
        $primaryColor = imagecolorallocate($image, 99, 102, 241); // indigo-500
        $textColor = imagecolorallocate($image, 31, 41, 55); // gray-800
        $accentColor = imagecolorallocate($image, 79, 70, 229); // indigo-600
        $white = imagecolorallocate($image, 255, 255, 255);
        
        // 背景を白で塗りつぶし
        imagefilledrectangle($image, 0, 0, $this->width, $this->height, $bgColor);
        
        // ヘッダー部分の背景は白のまま（全体が白なので描画不要）
        
        // ヘッダー下部に薄い線を追加
        $lightGray = imagecolorallocate($image, 230, 230, 230);
        imageline($image, 0, 150, $this->width, 150, $lightGray);
        
        // タイトル（左側）- 黒文字に変更
        $this->drawText($image, "読書傾向分析", 46, $this->margin, 68, $textColor, true); // 51 * 0.9
        $this->drawText($image, "@" . $userName . " さんの読書傾向", 30, $this->margin, 120, $textColor, true); // 33 * 0.9
        
        // ReadNestロゴを右側に配置
        $iconPath = dirname(__DIR__) . '/apple-touch-icon.png';
        if (file_exists($iconPath)) {
            $icon = imagecreatefrompng($iconPath);
            if ($icon) {
                // アイコンサイズを取得
                $iconWidth = imagesx($icon);
                $iconHeight = imagesy($icon);
                
                // 60pxの高さに収まるようにリサイズ (40 * 1.5)
                $newHeight = 60;
                $newWidth = intval($iconWidth * ($newHeight / $iconHeight));
                
                // ReadNestテキストの幅を概算（文字数 × フォントサイズ × 0.6）
                $textWidth = intval(mb_strlen("ReadNest") * 30 * 0.6);
                
                // アイコンとテキストを右寄せで配置（間隔を狭める）
                $spacing = 10; // 20から10に変更（アイコンとテキストの間隔）
                $totalWidth = $newWidth + $spacing + $textWidth; // アイコン幅 + 間隔 + テキスト幅
                $startX = $this->width - $this->margin - $totalWidth;
                
                // アイコンとテキストの垂直中心を揃える
                $textY = 90; // ReadNestテキストのY座標
                // テキストの中心とアイコンの中心を揃える
                $iconY = $textY - 30 - intval($newHeight / 2) + 10; // テキスト中心 - アイコン半分の高さ
                imagecopyresampled($image, $icon, intval($startX), intval($iconY), 0, 0, 
                                   $newWidth, $newHeight, $iconWidth, $iconHeight);
                
                // ReadNestテキストを配置（黒文字で）
                $this->drawText($image, "ReadNest", 30, intval($startX + $newWidth + $spacing), intval($textY), $textColor, true);
                
                imagedestroy($icon);
            }
        }
        
        // 分析内容を処理
        $y = 195 + 23; // (130 + 15) * 1.5
        $lines = $this->processAnalysisContent($analysisContent);
        
        foreach ($lines as $line) {
            if ($y > $this->height - 30) break; // 下部の余白
            
            if (strpos($line, '【') === 0) {
                // セクションヘッダー
                // セクション前に余白を追加（最初のセクション以外）
                if ($y > 195) { // 130 * 1.5
                    $y += $this->sectionSpacing;
                }
                // セクションタイトルも折り返し処理
                $wrappedLines = $this->wrapText($line, 32, $this->width - ($this->margin * 2), true); // 36 * 0.9
                foreach ($wrappedLines as $wrappedLine) {
                    if ($y > $this->height - 30) break;
                    $this->drawText($image, $wrappedLine, 32, $this->margin, $y, $accentColor, true);
                    $y += 65; // 72 * 0.9
                }
            } elseif (strpos($line, '◆') === 0) {
                // サブセクション（太字）
                $wrappedLines = $this->wrapText($line, 26, $this->width - ($this->margin * 2) - 30, true); // 29 * 0.9
                foreach ($wrappedLines as $wrappedLine) {
                    if ($y > $this->height - 30) break;
                    $this->drawText($image, $wrappedLine, 26, $this->margin + 30, $y, $accentColor, true);
                    $y += 49; // 54 * 0.9
                }
            } elseif (strpos($line, '-') === 0 || strpos($line, '・') === 0) {
                // リスト項目（太字）
                $wrappedLines = $this->wrapText($line, 23, $this->width - ($this->margin * 2) - 90, true); // 26 * 0.9
                foreach ($wrappedLines as $wrappedLine) {
                    if ($y > $this->height - 30) break;
                    $this->drawText($image, $wrappedLine, 23, $this->margin + 60, $y, $textColor, true);
                    $y += 42; // 47 * 0.9
                }
            } else {
                // 通常のテキスト（太字）
                $wrappedLines = $this->wrapText($line, 23, $this->width - ($this->margin * 2) - 60, true); // 26 * 0.9
                foreach ($wrappedLines as $wrappedLine) {
                    if ($y > $this->height - 30) break;
                    $this->drawText($image, $wrappedLine, 23, $this->margin + 30, $y, $textColor, true);
                    $y += 42; // 47 * 0.9
                }
            }
        }
        
        // フッターは削除（アイコンとReadNestはヘッダーに移動済み）
        
        // 一時ファイルに保存
        $tempFile = tempnam(sys_get_temp_dir(), 'analysis_') . '.png';
        imagepng($image, $tempFile);
        imagedestroy($image);
        
        return $tempFile;
    }
    
    /**
     * 分析内容を処理して表示用に整形
     */
    private function processAnalysisContent(string $content): array {
        $lines = explode("\n", $content);
        $processed = [];
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            // Markdown記法を簡略化
            $line = preg_replace('/\*\*(.+?)\*\*/', '$1', $line);
            $line = preg_replace('/^##\s+(.+)$/', '【$1】', $line);
            $line = preg_replace('/^###\s+(.+)$/', '◆ $1', $line);
            
            $processed[] = $line;
        }
        
        return $processed;
    }
    
    /**
     * テキストを描画
     */
    private function drawText($image, string $text, int $size, int $x, int $y, $color, bool $bold = false) {
        if ($this->fontPath) {
            // 太字フォントがある場合は使用
            if ($bold && $this->boldFontPath) {
                $fontToUse = $this->boldFontPath;
                imagettftext($image, $size, 0, $x, $y, $color, $fontToUse, $text);
            } else if ($bold) {
                // 太字フォントがない場合は、通常フォントを2回重ねて描画して太字効果を出す
                imagettftext($image, $size, 0, $x, $y, $color, $this->fontPath, $text);
                imagettftext($image, $size, 0, $x + 1, $y, $color, $this->fontPath, $text);
            } else {
                // 通常のテキスト
                imagettftext($image, $size, 0, $x, $y, $color, $this->fontPath, $text);
            }
        } else {
            // フォールバック: 組み込みフォント（日本語は文字化けする可能性）
            $font = $bold ? 5 : 3;
            imagestring($image, $font, $x, $y - 15, $text, $color);
        }
    }
    
    /**
     * テキストを折り返し
     */
    private function wrapText(string $text, int $fontSize, int $maxWidth, bool $bold = false): array {
        if (!$this->fontPath) {
            // フォントがない場合は簡易的な折り返し
            return str_split($text, 60);
        }
        
        // 太字の場合は太字フォントを使用（なければ通常フォント）
        $fontToUse = ($bold && $this->boldFontPath) ? $this->boldFontPath : $this->fontPath;
        
        $words = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);
        $lines = [];
        $currentLine = '';
        
        foreach ($words as $char) {
            $testLine = $currentLine . $char;
            $bbox = imagettfbbox($fontSize, 0, $fontToUse, $testLine);
            $width = abs($bbox[4] - $bbox[0]);
            
            if ($width > $maxWidth && $currentLine !== '') {
                $lines[] = $currentLine;
                $currentLine = $char;
            } else {
                $currentLine = $testLine;
            }
        }
        
        if ($currentLine !== '') {
            $lines[] = $currentLine;
        }
        
        return $lines;
    }
    
    /**
     * 必要な高さを計算
     */
    private function calculateRequiredHeight(array $lines): int {
        $height = 195 + 23; // (130 + 15) * 1.5 ヘッダー部分 + 0.5行分の余白
        
        $isFirstSection = true;
        foreach ($lines as $line) {
            if (strpos($line, '【') === 0) {
                // セクション間の余白を追加（最初のセクション以外）
                if (!$isFirstSection) {
                    $height += $this->sectionSpacing;
                }
                $isFirstSection = false;
                $height += 73; // 81 * 0.9
            } elseif (strpos($line, '◆') === 0) {
                $height += 57; // 63 * 0.9
            } elseif (strpos($line, '-') === 0 || strpos($line, '・') === 0) {
                $height += 49; // 54 * 0.9
            } else {
                // 通常テキストは折り返しを考慮
                $estimatedLines = ceil(mb_strlen($line) / 45); // 概算（文字が小さくなったため調整）
                $height += intval(49 * $estimatedLines); // 54 * 0.9
            }
        }
        
        $height += 30; // 下部の余白のみ
        
        return intval($height);
    }
    
    /**
     * 日本語フォントを探す
     */
    private function findJapaneseFont(bool $bold = false): ?string {
        $fontPaths = [];
        
        if ($bold) {
            // 太字フォントのパス
            $fontPaths = [
                '/home/icotfeels/fonts/MPlus/MPLUSRounded1c-Bold.ttf', // 本番環境（M PLUS Rounded 1c 太字）
                '/home/icotfeels/fonts/MPlus/MPLUSRounded1c-Regular.ttf', // フォールバック（通常）
                '/home/icotfeels/fonts/NotoSansJP-Bold.ttf', // フォールバック（太字）
                '/System/Library/Fonts/ヒラギノ角ゴシック W6.ttc', // macOS（太字）
                '/usr/share/fonts/truetype/fonts-japanese-gothic-bold.ttf', // Ubuntu（太字）
                '/usr/share/fonts/truetype/takao-gothic/TakaoGothicBold.ttf', // Debian（太字）
                '/usr/share/fonts/noto-cjk/NotoSansCJK-Bold.ttc', // Fedora（太字）
                dirname(__FILE__) . '/../fonts/NotoSansJP-Bold.ttf', // カスタムフォント（太字）
            ];
        } else {
            // 通常フォントのパス
            $fontPaths = [
                '/home/icotfeels/fonts/MPlus/MPLUSRounded1c-Regular.ttf', // 本番環境（M PLUS Rounded 1c）
                '/home/icotfeels/fonts/NotoSansJP-VariableFont_wght.ttf', // フォールバック
                '/System/Library/Fonts/ヒラギノ角ゴシック W3.ttc', // macOS
                '/usr/share/fonts/truetype/fonts-japanese-gothic.ttf', // Ubuntu
                '/usr/share/fonts/truetype/takao-gothic/TakaoGothic.ttf', // Debian
                '/usr/share/fonts/noto-cjk/NotoSansCJK-Regular.ttc', // Fedora
                dirname(__FILE__) . '/../fonts/NotoSansJP-Regular.ttf', // カスタムフォント
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