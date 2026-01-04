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
        $bgColor = imagecolorallocate($image, 249, 250, 251); // gray-50（明るいグレー背景）
        $primaryColor = imagecolorallocate($image, 99, 102, 241); // indigo-500
        $headerColor = imagecolorallocate($image, 79, 70, 229); // indigo-600
        $textColor = imagecolorallocate($image, 31, 41, 55); // gray-800
        $accentColor = imagecolorallocate($image, 79, 70, 229); // indigo-600
        $white = imagecolorallocate($image, 255, 255, 255);
        $lightGray = imagecolorallocate($image, 229, 231, 235); // gray-200

        // 背景を塗りつぶし
        imagefilledrectangle($image, 0, 0, $this->width, $this->height, $bgColor);

        // ヘッダーバー（indigo色）
        imagefilledrectangle($image, 0, 0, $this->width, 120, $headerColor);

        // ヘッダーにタイトル（白文字）
        $this->drawText($image, "読書傾向分析", 42, $this->margin, 50, $white, true);
        $this->drawText($image, "@" . $userName . " さんの読書傾向", 26, $this->margin, 95, $white, false);

        // ReadNestロゴをヘッダー右側に配置
        $iconPath = dirname(__DIR__) . '/apple-touch-icon.png';
        if (file_exists($iconPath)) {
            $icon = imagecreatefrompng($iconPath);
            if ($icon) {
                $iconWidth = imagesx($icon);
                $iconHeight = imagesy($icon);
                $newHeight = 50;
                $newWidth = intval($iconWidth * ($newHeight / $iconHeight));

                // 右上に配置
                $iconX = $this->width - $this->margin - $newWidth - 150;
                $iconY = 35;
                imagecopyresampled($image, $icon, intval($iconX), intval($iconY), 0, 0,
                                   $newWidth, $newHeight, $iconWidth, $iconHeight);

                // ReadNestテキスト（白文字）
                $this->drawText($image, "ReadNest", 26, intval($iconX + $newWidth + 10), intval($iconY + 35), $white, true);

                imagedestroy($icon);
            }
        }

        // コンテンツエリアの白い背景
        $contentTop = 140;
        $contentPadding = 30;
        imagefilledrectangle($image, $contentPadding, $contentTop, $this->width - $contentPadding, $this->height - $contentPadding, $white);

        // コンテンツエリアの枠線
        imagerectangle($image, $contentPadding, $contentTop, $this->width - $contentPadding, $this->height - $contentPadding, $lightGray);
        
        // 分析内容を処理
        $y = 180; // コンテンツエリア内（140 + 40のパディング）
        $contentMargin = $this->margin + $contentPadding; // コンテンツ内のマージン
        $lines = $this->processAnalysisContent($analysisContent);
        
        $contentWidth = $this->width - ($contentPadding * 2) - ($this->margin * 2);

        foreach ($lines as $line) {
            if ($y > $this->height - 60) break; // 下部の余白

            if (strpos($line, '【') === 0) {
                // セクションヘッダー
                if ($y > 200) {
                    $y += $this->sectionSpacing;
                }
                $wrappedLines = $this->wrapText($line, 28, $contentWidth, true);
                foreach ($wrappedLines as $wrappedLine) {
                    if ($y > $this->height - 60) break;
                    $this->drawText($image, $wrappedLine, 28, $contentMargin, $y, $accentColor, true);
                    $y += 55;
                }
            } elseif (strpos($line, '◆') === 0) {
                // サブセクション
                $wrappedLines = $this->wrapText($line, 24, $contentWidth - 30, true);
                foreach ($wrappedLines as $wrappedLine) {
                    if ($y > $this->height - 60) break;
                    $this->drawText($image, $wrappedLine, 24, $contentMargin + 20, $y, $accentColor, true);
                    $y += 45;
                }
            } elseif (strpos($line, '-') === 0 || strpos($line, '・') === 0) {
                // リスト項目
                $wrappedLines = $this->wrapText($line, 22, $contentWidth - 60, true);
                foreach ($wrappedLines as $wrappedLine) {
                    if ($y > $this->height - 60) break;
                    $this->drawText($image, $wrappedLine, 22, $contentMargin + 40, $y, $textColor, true);
                    $y += 38;
                }
            } else {
                // 通常のテキスト
                $wrappedLines = $this->wrapText($line, 22, $contentWidth - 40, true);
                foreach ($wrappedLines as $wrappedLine) {
                    if ($y > $this->height - 60) break;
                    $this->drawText($image, $wrappedLine, 22, $contentMargin + 20, $y, $textColor, true);
                    $y += 38;
                }
            }
        }
        
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
        $height = 180; // ヘッダー(120) + コンテンツ開始位置(40) + 余白(20)

        $isFirstSection = true;
        foreach ($lines as $line) {
            if (strpos($line, '【') === 0) {
                if (!$isFirstSection) {
                    $height += $this->sectionSpacing;
                }
                $isFirstSection = false;
                $height += 55;
            } elseif (strpos($line, '◆') === 0) {
                $height += 45;
            } elseif (strpos($line, '-') === 0 || strpos($line, '・') === 0) {
                $height += 38;
            } else {
                $estimatedLines = ceil(mb_strlen($line) / 50);
                $height += intval(38 * $estimatedLines);
            }
        }

        $height += 60; // 下部の余白

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