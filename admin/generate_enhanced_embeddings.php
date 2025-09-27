<?php
/**
 * Google Books APIから説明文を取得し、複数のembeddingを生成
 * user_id=12の高評価本を対象とした検証用スクリプト
 */

require_once(dirname(__DIR__) . '/config.php');
require_once(dirname(__DIR__) . '/library/database.php');
require_once(dirname(__DIR__) . '/library/openai_client.php');

// コマンドライン実行チェック
$is_cli = (php_sapi_name() === 'cli');

if (!$is_cli) {
    // Web経由の場合は管理者認証チェック
    require_once(__DIR__ . '/admin_auth.php');
    require_once(__DIR__ . '/admin_helpers.php');
    
    if (!isAdmin()) {
        http_response_code(403);
        include('403.php');
        exit;
    }
}

class EnhancedEmbeddingGenerator {
    private $db;
    private $openai;
    private $googleBooksApiKey;
    private $targetUserId = 12;
    private $minRating = 4; // 4以上の評価の本のみ
    
    public function __construct() {
        global $g_db;
        $this->db = $g_db;
        
        // OpenAI クライアント初期化
        if (!defined('OPENAI_API_KEY') || empty(OPENAI_API_KEY)) {
            throw new Exception('OpenAI API key is not configured. Please add OPENAI_API_KEY to config.php');
        }
        
        try {
            $this->openai = new OpenAIClient(OPENAI_API_KEY);
        } catch (Exception $e) {
            throw new Exception('Failed to initialize OpenAI client: ' . $e->getMessage());
        }
        
        // Google Books API キー
        $this->googleBooksApiKey = defined('GOOGLE_BOOKS_API_KEY') ? GOOGLE_BOOKS_API_KEY : null;
        
        if (php_sapi_name() === 'cli') {
            echo "Initialized: OpenAI API " . (OPENAI_API_KEY ? "✓" : "✗") . "\n";
            echo "Initialized: Google Books API " . ($this->googleBooksApiKey ? "✓" : "✗") . "\n";
        }
    }
    
    /**
     * Google Books APIから書籍情報を取得
     */
    private function fetchGoogleBooksInfo($isbn) {
        if (empty($this->googleBooksApiKey)) {
            echo "    ⚠ Google Books API key not configured\n";
            return null;
        }
        
        // ISBNをクリーンアップ（ハイフンを除去）
        $clean_isbn = str_replace('-', '', $isbn);
        
        // ISBN-10の場合、ISBN-13も試す
        $isbns = [$clean_isbn];
        if (strlen($clean_isbn) == 10) {
            // ISBN-10 to ISBN-13変換（978プレフィックスを追加）
            $isbn13 = '978' . substr($clean_isbn, 0, 9);
            // チェックディジット再計算
            $sum = 0;
            for ($i = 0; $i < 12; $i++) {
                $sum += intval($isbn13[$i]) * (($i % 2 == 0) ? 1 : 3);
            }
            $check = (10 - ($sum % 10)) % 10;
            $isbn13 .= $check;
            $isbns[] = $isbn13;
        }
        
        foreach ($isbns as $try_isbn) {
            $url = "https://www.googleapis.com/books/v1/volumes?q=isbn:{$try_isbn}&key={$this->googleBooksApiKey}";
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_USERAGENT, 'ReadNest/1.0');
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode !== 200 || empty($response)) {
                continue;
            }
            
            $data = json_decode($response, true);
            
            if (!empty($data['items']) && isset($data['items'][0]['volumeInfo'])) {
                $volumeInfo = $data['items'][0]['volumeInfo'];
                
                return [
                    'title' => $volumeInfo['title'] ?? null,
                    'authors' => $volumeInfo['authors'] ?? [],
                    'description' => $volumeInfo['description'] ?? null,
                    'categories' => $volumeInfo['categories'] ?? [],
                    'pageCount' => $volumeInfo['pageCount'] ?? null,
                    'publishedDate' => $volumeInfo['publishedDate'] ?? null,
                    'publisher' => $volumeInfo['publisher'] ?? null,
                ];
            }
        }
        
        return null;
    }
    
    /**
     * OpenAIでembeddingを生成
     */
    private function generateEmbedding($text) {
        if (empty($text)) {
            return null;
        }
        
        // テキストを適切な長さに制限（8192トークンまで）
        $text = mb_substr($text, 0, 30000);
        
        try {
            $response = $this->openai->createEmbedding($text, 'text-embedding-3-small');
            
            if (isset($response['data'][0]['embedding'])) {
                return json_encode($response['data'][0]['embedding']);
            }
        } catch (Exception $e) {
            error_log("Failed to generate embedding: " . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * 対象の本を取得
     */
    public function getTargetBooks($limit = 50, $offset = 0) {
        $sql = "
            SELECT 
                bl.book_id,
                bl.name as book_title,
                bl.author,
                bl.amazon_id as asin,
                bl.rating,
                br.title as repo_title,
                br.description,
                br.title_embedding,
                br.description_embedding,
                br.combined_embedding
            FROM b_book_list bl
            LEFT JOIN b_book_repository br ON bl.amazon_id = br.asin
            WHERE bl.user_id = ?
            AND bl.rating >= ?
            AND bl.amazon_id IS NOT NULL
            AND bl.amazon_id != ''
            ORDER BY bl.rating DESC, bl.update_date DESC
            LIMIT ? OFFSET ?
        ";
        
        $books = $this->db->getAll($sql, [$this->targetUserId, $this->minRating, $limit, $offset], DB_FETCHMODE_ASSOC);
        
        if (DB::isError($books)) {
            throw new Exception("Database error: " . $books->getMessage());
        }
        
        return $books;
    }
    
    /**
     * 単一の本を処理
     */
    public function processBook($book) {
        $result = [
            'book_id' => $book['book_id'],
            'title' => $book['book_title'],
            'asin' => $book['asin'],
            'rating' => $book['rating'],
            'actions' => [],
            'success' => true,
            'error' => null
        ];
        
        try {
            // b_book_repositoryにレコードがない場合は作成
            if (empty($book['repo_title'])) {
                $insert_sql = "
                    INSERT INTO b_book_repository (asin, title, author, created_at)
                    VALUES (?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE
                    title = VALUES(title),
                    author = VALUES(author)
                ";
                $this->db->query($insert_sql, [$book['asin'], $book['book_title'], $book['author']]);
                $result['actions'][] = 'Repository record created';
                
                // 再取得
                $check_sql = "SELECT description FROM b_book_repository WHERE asin = ?";
                $desc = $this->db->getOne($check_sql, [$book['asin']]);
                if (!DB::isError($desc)) {
                    $book['description'] = $desc;
                }
            }
            
            // 1. Google Books APIから説明文を取得（説明文がない場合）
            if (empty($book['description']) && $this->googleBooksApiKey) {
                echo "    → Fetching from Google Books API...\n";
                $googleInfo = $this->fetchGoogleBooksInfo($book['asin']);
                
                if ($googleInfo && !empty($googleInfo['description'])) {
                    $update_sql = "UPDATE b_book_repository SET description = ?, description_source = 'google_books' WHERE asin = ?";
                    $this->db->query($update_sql, [$googleInfo['description'], $book['asin']]);
                    $book['description'] = $googleInfo['description'];
                    $result['actions'][] = 'Description fetched from Google Books';
                    echo "      ✓ Description found (" . mb_strlen($googleInfo['description']) . " chars)\n";
                } else {
                    echo "      ✗ No description in Google Books\n";
                }
            }
            
            // 説明文として使用するテキストを決定
            $description_text = $book['description'] ?? '';
            $description_source = 'google_books';
            
            if (empty($description_text)) {
                // 説明文がない場合はタイトルのみ使用
                $description_text = '';
                $description_source = 'none';
                $result['actions'][] = 'No description available, using title only';
            }
            
            // 2. タイトルembeddingを生成
            if (empty($book['title_embedding'])) {
                echo "    → Generating title embedding...\n";
                $title_embedding = $this->generateEmbedding($book['book_title']);
                
                if ($title_embedding) {
                    $update_sql = "UPDATE b_book_repository SET title_embedding = ? WHERE asin = ?";
                    $this->db->query($update_sql, [$title_embedding, $book['asin']]);
                    $result['actions'][] = 'Title embedding generated';
                    echo "      ✓ Title embedding created\n";
                }
            } else {
                echo "    ✓ Title embedding already exists\n";
            }
            
            // 3. 説明文embeddingを生成（説明文がある場合のみ）
            if (!empty($description_text) && empty($book['description_embedding'])) {
                echo "    → Generating description embedding...\n";
                $desc_embedding = $this->generateEmbedding($description_text);
                
                if ($desc_embedding) {
                    $update_sql = "UPDATE b_book_repository SET description_embedding = ? WHERE asin = ?";
                    $this->db->query($update_sql, [$desc_embedding, $book['asin']]);
                    $result['actions'][] = 'Description embedding generated';
                    echo "      ✓ Description embedding created\n";
                }
            } elseif (!empty($book['description_embedding'])) {
                echo "    ✓ Description embedding already exists\n";
            }
            
            // 4. Combined embedding を生成（タイトル + 説明文）
            if (empty($book['combined_embedding'])) {
                $combined_text = $book['book_title'];
                if (!empty($description_text)) {
                    $combined_text .= "\n\n" . $description_text;
                }
                
                echo "    → Generating combined embedding...\n";
                $combined_embedding = $this->generateEmbedding($combined_text);
                
                if ($combined_embedding) {
                    $update_sql = "
                        UPDATE b_book_repository 
                        SET combined_embedding = ?,
                            embedding_generated_at = NOW(),
                            embedding_model = 'text-embedding-3-small'
                        WHERE asin = ?
                    ";
                    $this->db->query($update_sql, [$combined_embedding, $book['asin']]);
                    $result['actions'][] = 'Combined embedding generated';
                    echo "      ✓ Combined embedding created\n";
                }
            } else {
                echo "    ✓ Combined embedding already exists\n";
            }
            
            if (empty($result['actions'])) {
                $result['actions'][] = 'All embeddings already exist';
            }
            
        } catch (Exception $e) {
            $result['success'] = false;
            $result['error'] = $e->getMessage();
            echo "    ✗ Error: " . $e->getMessage() . "\n";
        }
        
        return $result;
    }
    
    /**
     * バッチ処理
     */
    public function processBatch($limit = 50) {
        $books = $this->getTargetBooks($limit);
        
        $results = [
            'total' => count($books),
            'processed' => 0,
            'success' => 0,
            'failed' => 0,
            'details' => []
        ];
        
        echo "\n";
        echo "Found {$results['total']} books (rating >= {$this->minRating}) for user {$this->targetUserId}\n";
        echo str_repeat("=", 60) . "\n\n";
        
        foreach ($books as $index => $book) {
            $num = $index + 1;
            echo "[$num/{$results['total']}] {$book['book_title']}\n";
            echo "    ASIN: {$book['asin']} | Rating: ★{$book['rating']}\n";
            
            $result = $this->processBook($book);
            $results['details'][] = $result;
            $results['processed']++;
            
            if ($result['success']) {
                $results['success']++;
                echo "    ✓ Complete: " . implode(', ', $result['actions']) . "\n";
            } else {
                $results['failed']++;
            }
            
            echo "\n";
            
            // API レート制限対策
            if ($num < $results['total']) {
                sleep(1);
            }
        }
        
        return $results;
    }
    
    /**
     * 統計情報を取得
     */
    public function getStatistics() {
        // 対象ユーザーの本の統計
        $user_stats_sql = "
            SELECT 
                COUNT(*) as total_books,
                COUNT(CASE WHEN rating >= ? THEN 1 END) as high_rated_books,
                COUNT(CASE WHEN amazon_id IS NOT NULL AND amazon_id != '' THEN 1 END) as books_with_asin,
                AVG(rating) as avg_rating
            FROM b_book_list
            WHERE user_id = ?
        ";
        $user_stats = $this->db->getRow($user_stats_sql, [$this->minRating, $this->targetUserId], DB_FETCHMODE_ASSOC);
        
        // Repository統計（対象ユーザーの高評価本のみ）
        $repo_stats_sql = "
            SELECT 
                COUNT(DISTINCT br.asin) as total_records,
                COUNT(DISTINCT CASE WHEN br.description IS NOT NULL THEN br.asin END) as with_description,
                COUNT(DISTINCT CASE WHEN br.title_embedding IS NOT NULL THEN br.asin END) as with_title_embedding,
                COUNT(DISTINCT CASE WHEN br.description_embedding IS NOT NULL THEN br.asin END) as with_desc_embedding,
                COUNT(DISTINCT CASE WHEN br.combined_embedding IS NOT NULL THEN br.asin END) as with_combined_embedding
            FROM b_book_repository br
            INNER JOIN b_book_list bl ON bl.amazon_id = br.asin
            WHERE bl.user_id = ?
            AND bl.rating >= ?
        ";
        $repo_stats = $this->db->getRow($repo_stats_sql, [$this->targetUserId, $this->minRating], DB_FETCHMODE_ASSOC);
        
        // 全体のRepository統計
        $all_repo_stats_sql = "
            SELECT 
                COUNT(*) as total_all,
                COUNT(CASE WHEN combined_embedding IS NOT NULL THEN 1 END) as all_with_combined
            FROM b_book_repository
        ";
        $all_repo_stats = $this->db->getRow($all_repo_stats_sql, [], DB_FETCHMODE_ASSOC);
        
        return [
            'user' => $user_stats,
            'repository' => $repo_stats,
            'all_repository' => $all_repo_stats
        ];
    }
}

// 実行部分
if ($is_cli) {
    // CLIから実行
    echo "Starting Enhanced Embedding Generator...\n";
    
    try {
        $generator = new EnhancedEmbeddingGenerator();
        
        // 統計を表示
        echo "\n";
        echo str_repeat("=", 60) . "\n";
        echo " Enhanced Embedding Generator\n";
        echo str_repeat("=", 60) . "\n\n";
        
        $stats = $generator->getStatistics();
        echo "Target Settings:\n";
        echo "  User ID: 12\n";
        echo "  Min Rating: ★4\n\n";
        
        echo "User Statistics:\n";
        echo "  Total books: " . number_format($stats['user']['total_books']) . "\n";
        echo "  High-rated books (★4+): " . number_format($stats['user']['high_rated_books']) . "\n";
        echo "  Books with ASIN: " . number_format($stats['user']['books_with_asin']) . "\n";
        echo "  Average rating: " . number_format($stats['user']['avg_rating'], 1) . "\n\n";
        
        echo "Target Books Repository Status:\n";
        echo "  Total records: " . number_format($stats['repository']['total_records']) . "\n";
        echo "  With description: " . number_format($stats['repository']['with_description']) . 
             " (" . round($stats['repository']['with_description'] / max(1, $stats['repository']['total_records']) * 100, 1) . "%)\n";
        echo "  Title embedding: " . number_format($stats['repository']['with_title_embedding']) . 
             " (" . round($stats['repository']['with_title_embedding'] / max(1, $stats['repository']['total_records']) * 100, 1) . "%)\n";
        echo "  Description embedding: " . number_format($stats['repository']['with_desc_embedding']) . 
             " (" . round($stats['repository']['with_desc_embedding'] / max(1, $stats['repository']['total_records']) * 100, 1) . "%)\n";
        echo "  Combined embedding: " . number_format($stats['repository']['with_combined_embedding']) . 
             " (" . round($stats['repository']['with_combined_embedding'] / max(1, $stats['repository']['total_records']) * 100, 1) . "%)\n\n";
        
        echo "All Repository Status:\n";
        echo "  Total: " . number_format($stats['all_repository']['total_all']) . "\n";
        echo "  With combined embedding: " . number_format($stats['all_repository']['all_with_combined']) . "\n";
        
        // バッチ処理を実行
        $limit = isset($argv[1]) ? intval($argv[1]) : 20;
        echo "\nProcessing up to $limit books...\n";
        
        $results = $generator->processBatch($limit);
        
        // 結果サマリー
        echo str_repeat("=", 60) . "\n";
        echo "Processing Complete!\n";
        echo str_repeat("=", 60) . "\n";
        echo "  Processed: {$results['processed']}\n";
        echo "  Success: {$results['success']}\n";
        echo "  Failed: {$results['failed']}\n";
        
        if ($results['failed'] > 0) {
            echo "\nFailed items:\n";
            foreach ($results['details'] as $detail) {
                if (!$detail['success']) {
                    echo "  - {$detail['title']}: {$detail['error']}\n";
                }
            }
        }
        
    } catch (Exception $e) {
        echo "\nError: " . $e->getMessage() . "\n";
        exit(1);
    }
    
} else {
    // Web UIとして表示
    $page_title = 'Enhanced Embedding Generator';
    include(__DIR__ . '/layout/header.php');
    
    try {
        $generator = new EnhancedEmbeddingGenerator();
        $stats = $generator->getStatistics();
        
        // POSTリクエストの処理
        $results = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process'])) {
            set_time_limit(300);
            $limit = intval($_POST['limit'] ?? 10);
            $results = $generator->processBatch($limit);
        }
        ?>
        
        <div class="container mx-auto px-4 py-8">
            <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                <h1 class="text-2xl font-bold mb-4">
                    <i class="fas fa-magic text-purple-600 mr-2"></i>
                    Enhanced Embedding Generator
                </h1>
                <p class="text-gray-600">User ID 12の高評価本（★4以上）を対象に、Google Books APIから説明文を取得し、3種類のembeddingを生成します。</p>
            </div>
            
            <!-- 統計情報 -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <!-- ユーザー統計 -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold mb-4 flex items-center">
                        <i class="fas fa-user text-blue-600 mr-2"></i>
                        User Statistics
                    </h3>
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-gray-600">総書籍数:</span>
                            <span class="font-bold"><?php echo number_format($stats['user']['total_books']); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">高評価本 (★4+):</span>
                            <span class="font-bold text-green-600"><?php echo number_format($stats['user']['high_rated_books']); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">ASIN付き:</span>
                            <span class="font-bold"><?php echo number_format($stats['user']['books_with_asin']); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">平均評価:</span>
                            <span class="font-bold">★<?php echo number_format($stats['user']['avg_rating'], 1); ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- 対象本のRepository統計 -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold mb-4 flex items-center">
                        <i class="fas fa-database text-purple-600 mr-2"></i>
                        Target Books Status
                    </h3>
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-gray-600">対象本数:</span>
                            <span class="font-bold"><?php echo number_format($stats['repository']['total_records']); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">説明文あり:</span>
                            <span class="font-bold"><?php echo number_format($stats['repository']['with_description']); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Title Emb:</span>
                            <span class="font-bold text-blue-600"><?php echo number_format($stats['repository']['with_title_embedding']); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Desc Emb:</span>
                            <span class="font-bold text-purple-600"><?php echo number_format($stats['repository']['with_desc_embedding']); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Combined:</span>
                            <span class="font-bold text-green-600"><?php echo number_format($stats['repository']['with_combined_embedding']); ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- 進捗 -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold mb-4 flex items-center">
                        <i class="fas fa-chart-line text-green-600 mr-2"></i>
                        Progress
                    </h3>
                    <?php 
                    $total = max(1, $stats['repository']['total_records']);
                    $title_pct = round($stats['repository']['with_title_embedding'] / $total * 100, 1);
                    $desc_pct = round($stats['repository']['with_desc_embedding'] / $total * 100, 1);
                    $combined_pct = round($stats['repository']['with_combined_embedding'] / $total * 100, 1);
                    ?>
                    <div class="space-y-3">
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span>Title</span>
                                <span><?php echo $title_pct; ?>%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo $title_pct; ?>%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span>Description</span>
                                <span><?php echo $desc_pct; ?>%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-purple-600 h-2 rounded-full" style="width: <?php echo $desc_pct; ?>%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span>Combined</span>
                                <span><?php echo $combined_pct; ?>%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-green-600 h-2 rounded-full" style="width: <?php echo $combined_pct; ?>%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 処理フォーム -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h3 class="text-lg font-semibold mb-4">バッチ処理</h3>
                <form method="POST" class="flex gap-4 items-end">
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700 mb-2">処理件数</label>
                        <select name="limit" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            <option value="5">5件</option>
                            <option value="10" selected>10件</option>
                            <option value="20">20件</option>
                            <option value="50">50件</option>
                            <option value="100">100件</option>
                        </select>
                    </div>
                    <button type="submit" name="process" value="1" 
                            class="bg-purple-600 text-white px-6 py-2 rounded-md hover:bg-purple-700">
                        <i class="fas fa-play mr-2"></i>処理開始
                    </button>
                </form>
                
                <?php if (!defined('GOOGLE_BOOKS_API_KEY')): ?>
                <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded text-sm">
                    <i class="fas fa-exclamation-triangle text-yellow-600 mr-2"></i>
                    Google Books API キーが設定されていません。config.phpに追加してください。
                </div>
                <?php endif; ?>
            </div>
            
            <!-- 処理結果 -->
            <?php if ($results): ?>
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h3 class="text-lg font-semibold mb-4">処理結果</h3>
                <div class="mb-4 flex gap-4">
                    <span class="px-3 py-1 bg-gray-100 rounded">
                        処理数: <strong><?php echo $results['total']; ?></strong>件
                    </span>
                    <span class="px-3 py-1 bg-green-100 text-green-800 rounded">
                        成功: <strong><?php echo $results['success']; ?></strong>件
                    </span>
                    <?php if ($results['failed'] > 0): ?>
                    <span class="px-3 py-1 bg-red-100 text-red-800 rounded">
                        失敗: <strong><?php echo $results['failed']; ?></strong>件
                    </span>
                    <?php endif; ?>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-4 py-2 text-left">#</th>
                                <th class="px-4 py-2 text-left">タイトル</th>
                                <th class="px-4 py-2 text-left">ASIN</th>
                                <th class="px-4 py-2 text-center">評価</th>
                                <th class="px-4 py-2 text-left">処理内容</th>
                                <th class="px-4 py-2 text-center">状態</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results['details'] as $idx => $detail): ?>
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-4 py-2"><?php echo $idx + 1; ?></td>
                                <td class="px-4 py-2">
                                    <span class="font-medium"><?php echo htmlspecialchars(mb_substr($detail['title'], 0, 30)); ?></span>
                                    <?php if (mb_strlen($detail['title']) > 30) echo '...'; ?>
                                </td>
                                <td class="px-4 py-2 font-mono text-xs"><?php echo htmlspecialchars($detail['asin']); ?></td>
                                <td class="px-4 py-2 text-center">★<?php echo $detail['rating']; ?></td>
                                <td class="px-4 py-2 text-xs">
                                    <?php foreach ($detail['actions'] as $action): ?>
                                        <span class="inline-block px-2 py-0.5 bg-blue-100 text-blue-800 rounded mr-1 mb-1">
                                            <?php echo htmlspecialchars($action); ?>
                                        </span>
                                    <?php endforeach; ?>
                                </td>
                                <td class="px-4 py-2 text-center">
                                    <?php if ($detail['success']): ?>
                                        <span class="text-green-600 text-xl">✓</span>
                                    <?php else: ?>
                                        <span class="text-red-600 text-xl" title="<?php echo htmlspecialchars($detail['error']); ?>">✗</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- コマンドライン実行の案内 -->
            <div class="bg-gradient-to-r from-purple-50 to-blue-50 border border-purple-200 rounded-lg p-6">
                <h4 class="font-semibold text-purple-900 mb-3">
                    <i class="fas fa-terminal mr-2"></i>
                    コマンドライン実行
                </h4>
                <div class="bg-white rounded p-3 mb-3">
                    <code class="text-sm">
                        # 10件処理<br>
                        php <?php echo __FILE__; ?> 10<br><br>
                        
                        # 50件処理<br>
                        php <?php echo __FILE__; ?> 50
                    </code>
                </div>
                <p class="text-sm text-purple-800">
                    <i class="fas fa-info-circle mr-1"></i>
                    CLIから実行すると詳細なログが表示されます
                </p>
            </div>
        </div>
        
        <?php
    } catch (Exception $e) {
        echo '<div class="container mx-auto px-4 py-8">';
        echo '<div class="bg-red-50 border border-red-200 rounded-lg p-4">';
        echo '<h3 class="font-semibold text-red-900">エラー</h3>';
        echo '<p class="text-red-800">' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '</div>';
        echo '</div>';
    }
    
    include(__DIR__ . '/layout/footer.php');
}
?>