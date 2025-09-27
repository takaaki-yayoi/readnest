<?php
/**
 * 著者名の自動修正ライブラリ
 * AIや他のシステムが誤認識しやすい著者名を正しい表記に修正
 */

declare(strict_types=1);

class AuthorCorrections {
    /**
     * 誤認識されやすい著者名のマッピング
     * キー: 間違った表記（複数パターン可）
     * 値: 正しい表記
     */
    private static array $corrections = [
        // 帚木蓬生（ははきぎ ほうせい）
        '布施木蓬生' => '帚木蓬生',
        'ははきぎ蓬生' => '帚木蓬生',
        'ハハキギ蓬生' => '帚木蓬生',
        
        // その他の誤認識されやすい著者名を追加
        // 例: 
        // '東野圭吾' => '東野圭吾', // 全角・半角の混在を防ぐ
        // '村上春樹' => '村上春樹',
    ];
    
    /**
     * 著者名を修正
     * 
     * @param string $authorName 修正前の著者名
     * @return string 修正後の著者名
     */
    public static function correct(string $authorName): string {
        $authorName = trim($authorName);
        
        // 完全一致で修正
        if (isset(self::$corrections[$authorName])) {
            return self::$corrections[$authorName];
        }
        
        // 部分一致で修正（より複雑なケースに対応）
        foreach (self::$corrections as $wrong => $correct) {
            if (mb_strpos($authorName, $wrong) !== false) {
                $authorName = str_replace($wrong, $correct, $authorName);
            }
        }
        
        return $authorName;
    }
    
    /**
     * 複数の著者名を一括修正
     * 
     * @param array $authors 著者名の配列
     * @return array 修正後の著者名の配列
     */
    public static function correctMultiple(array $authors): array {
        return array_map([self::class, 'correct'], $authors);
    }
    
    /**
     * 読書履歴データの著者名を修正
     * 
     * @param array $readingHistory 読書履歴データ
     * @return array 著者名が修正された読書履歴データ
     */
    public static function correctReadingHistory(array $readingHistory): array {
        foreach ($readingHistory as &$book) {
            if (isset($book['author'])) {
                $book['author'] = self::correct($book['author']);
            }
        }
        return $readingHistory;
    }
    
    /**
     * AIの分析結果テキスト内の著者名を修正
     * 
     * @param string $text AI分析結果のテキスト
     * @return string 著者名が修正されたテキスト
     */
    public static function correctInText(string $text): string {
        foreach (self::$corrections as $wrong => $correct) {
            $text = str_replace($wrong, $correct, $text);
        }
        return $text;
    }
}