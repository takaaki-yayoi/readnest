<?php
/**
 * ベクトル類似度計算ライブラリ
 * JSON形式のembeddingを正しく比較
 */

class VectorSimilarity {
    
    /**
     * コサイン類似度を計算
     * @param string $embedding1 JSON形式のembedding
     * @param string $embedding2 JSON形式のembedding
     * @return float 0〜1の類似度（1が最も類似）
     */
    public static function cosineSimilarity($embedding1, $embedding2) {
        // JSONをデコード
        $vec1 = json_decode($embedding1, true);
        $vec2 = json_decode($embedding2, true);
        
        // デコード失敗時
        if ($vec1 === null || $vec2 === null) {
            error_log("Failed to decode embeddings");
            return 0;
        }
        
        // ベクトルの長さが異なる場合
        if (count($vec1) !== count($vec2)) {
            error_log("Vector dimensions don't match: " . count($vec1) . " vs " . count($vec2));
            return 0;
        }
        
        // コサイン類似度の計算
        $dotProduct = 0;
        $magnitude1 = 0;
        $magnitude2 = 0;
        
        for ($i = 0; $i < count($vec1); $i++) {
            $dotProduct += $vec1[$i] * $vec2[$i];
            $magnitude1 += $vec1[$i] * $vec1[$i];
            $magnitude2 += $vec2[$i] * $vec2[$i];
        }
        
        $magnitude1 = sqrt($magnitude1);
        $magnitude2 = sqrt($magnitude2);
        
        if ($magnitude1 == 0 || $magnitude2 == 0) {
            return 0;
        }
        
        // -1〜1の範囲を0〜1に正規化
        $similarity = $dotProduct / ($magnitude1 * $magnitude2);
        return ($similarity + 1) / 2;
    }
    
    /**
     * ユークリッド距離ベースの類似度
     * @param string $embedding1 JSON形式のembedding
     * @param string $embedding2 JSON形式のembedding
     * @return float 0〜1の類似度（1が最も類似）
     */
    public static function euclideanSimilarity($embedding1, $embedding2) {
        $vec1 = json_decode($embedding1, true);
        $vec2 = json_decode($embedding2, true);
        
        if ($vec1 === null || $vec2 === null || count($vec1) !== count($vec2)) {
            return 0;
        }
        
        $distance = 0;
        for ($i = 0; $i < count($vec1); $i++) {
            $diff = $vec1[$i] - $vec2[$i];
            $distance += $diff * $diff;
        }
        
        $distance = sqrt($distance);
        
        // 距離を0-1の類似度に変換（距離が0なら類似度1）
        // 正規化のため、最大距離を仮定（例：10）
        $maxDistance = 10;
        return max(0, 1 - ($distance / $maxDistance));
    }
    
    /**
     * データベースから類似本を検索
     * @param PDO $pdo データベース接続
     * @param string $targetEmbedding 検索対象のembedding
     * @param string $targetTitle 検索対象のタイトル（除外用）
     * @param string $embeddingField 使用するembeddingフィールド名
     * @param int $limit 取得件数
     * @return array 類似本のリスト
     */
    public static function findSimilarBooks($pdo, $targetEmbedding, $targetTitle, $embeddingField = 'combined_embedding', $limit = 20) {
        // まず候補となる本を取得（embeddingがあるもの全て）
        $sql = "
            SELECT 
                MIN(br.asin) as asin,
                br.title,
                GROUP_CONCAT(DISTINCT br.author SEPARATOR ', ') as author,
                MIN(br.description) as description,
                MAX(LENGTH(br.description)) as desc_length,
                MIN(br.$embeddingField) as embedding,
                COUNT(DISTINCT bl.user_id) as user_count,
                AVG(bl.rating) as avg_rating
            FROM b_book_repository br
            LEFT JOIN b_book_list bl ON br.asin = bl.amazon_id
            WHERE br.$embeddingField IS NOT NULL
            AND br.title != :title
            GROUP BY br.title
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['title' => $targetTitle]);
        $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 各本との類似度を計算
        $results = [];
        foreach ($books as $book) {
            $similarity = self::cosineSimilarity($targetEmbedding, $book['embedding']);
            
            $results[] = [
                'asin' => $book['asin'],
                'title' => $book['title'],
                'author' => $book['author'],
                'description' => $book['description'],
                'desc_length' => $book['desc_length'],
                'user_count' => $book['user_count'],
                'avg_rating' => $book['avg_rating'],
                'similarity' => $similarity
            ];
        }
        
        // 類似度でソート
        usort($results, function($a, $b) {
            return $b['similarity'] <=> $a['similarity'];
        });
        
        // 上位N件を返す
        return array_slice($results, 0, $limit);
    }
}

/**
 * グローバル関数：Embeddingベースで類似本を検索
 * @param string $targetEmbedding 検索対象のembedding
 * @param array $excludeAsins 除外するASINのリスト
 * @param int $limit 取得件数
 * @return array 類似本のリスト
 */
function getEmbeddingSimilarBooks($targetEmbedding, $excludeAsins = [], $limit = 20) {
    global $g_db;
    
    // embeddingがある本を候補として取得
    // escapeSimpleの代わりにaddslashesを使用
    $exclude_list = !empty($excludeAsins) ? "AND br.asin NOT IN ('" . implode("','", array_map(function($a) { 
        return addslashes($a); 
    }, $excludeAsins)) . "')" : "";
    
    $sql = "
        SELECT 
            br.asin as amazon_id,
            br.title,
            br.author,
            br.image_url,
            br.description,
            br.combined_embedding,
            (SELECT AVG(rating) FROM b_book_list WHERE amazon_id = br.asin AND rating > 0) as avg_rating,
            (SELECT COUNT(*) FROM b_book_list WHERE amazon_id = br.asin) as reader_count
        FROM b_book_repository br
        WHERE br.combined_embedding IS NOT NULL
        $exclude_list
        ORDER BY RAND()
        LIMIT 200
    ";
    
    $candidates = $g_db->getAll($sql, [], DB_FETCHMODE_ASSOC);
    
    if (DB::isError($candidates)) {
        error_log("Error fetching candidates: " . $candidates->getMessage());
        return [];
    }
    
    // 各候補との類似度を計算
    $results = [];
    foreach ($candidates as $book) {
        $similarity = VectorSimilarity::cosineSimilarity($targetEmbedding, $book['combined_embedding']);
        
        if ($similarity > 0.65) { // 閾値以上のものだけを含める
            $book['similarity'] = round($similarity * 100, 1);
            unset($book['combined_embedding']); // embeddingデータは返さない
            $results[] = $book;
        }
    }
    
    // 類似度でソート
    usort($results, function($a, $b) {
        return $b['similarity'] <=> $a['similarity'];
    });
    
    // 上位N件を返す
    return array_slice($results, 0, $limit);
}
?>