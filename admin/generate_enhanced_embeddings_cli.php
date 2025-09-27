<?php
/**
 * Enhanced Embedding Generator - CLI専用版
 * セッションエラーを回避したCLI実行用スクリプト
 */

// CLIチェック
if (php_sapi_name() !== 'cli') {
    die("This script must be run from command line\n");
}

// エラー表示
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 必要な環境変数を設定（セッションエラー回避）
$_SERVER['HTTP_HOST'] = 'readnest.jp';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['REQUEST_METHOD'] = 'GET';

echo "Enhanced Embedding Generator CLI\n";
echo str_repeat("=", 60) . "\n\n";

// データベース設定（リモート接続）
define('DB_HOST', 'localhost');
define('DB_NAME', 'icotfeels_book');
define('DB_USER', 'icotfeels_book');
define('DB_PASS', 'dokushonoteigi');

// config.phpから必要な定数を読み込み
$config_file = dirname(__DIR__) . '/config.php';
if (file_exists($config_file)) {
    $config_content = file_get_contents($config_file);
    
    // OPENAI_API_KEYを探す
    if (preg_match("/define\s*\(\s*['\"]OPENAI_API_KEY['\"]\s*,\s*['\"]([^'\"]+)['\"]\s*\)/", $config_content, $matches)) {
        define('OPENAI_API_KEY', $matches[1]);
        echo "✓ OpenAI API Key loaded\n";
    } else {
        die("✗ OPENAI_API_KEY not found in config.php\n");
    }
    
    // GOOGLE_BOOKS_API_KEYを探す（オプション）
    if (preg_match("/define\s*\(\s*['\"]GOOGLE_BOOKS_API_KEY['\"]\s*,\s*['\"]([^'\"]+)['\"]\s*\)/", $config_content, $matches)) {
        define('GOOGLE_BOOKS_API_KEY', $matches[1]);
        echo "✓ Google Books API Key loaded\n";
    } else {
        echo "⚠ Google Books API Key not found (descriptions will not be fetched)\n";
    }
}

echo "\n";

// PDOでデータベース接続（ソケット接続を試行）
try {
    // ソケットパスを探す
    $socket_paths = [
        '/tmp/mysql.sock',
        '/var/run/mysqld/mysqld.sock',
        '/var/mysql/mysql.sock',
        '/Applications/MAMP/tmp/mysql/mysql.sock',
        '/usr/local/var/mysql/mysql.sock'
    ];
    
    $socket_found = '';
    foreach ($socket_paths as $socket) {
        if (file_exists($socket)) {
            $socket_found = $socket;
            break;
        }
    }
    
    if ($socket_found) {
        $dsn = "mysql:unix_socket=" . $socket_found . ";dbname=" . DB_NAME . ";charset=utf8";
        echo "✓ Using socket: $socket_found\n";
    } else {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8";
        echo "✓ Using TCP connection\n";
    }
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Database connected\n\n";
} catch (PDOException $e) {
    die("✗ Database connection failed: " . $e->getMessage() . "\n");
}

class SimpleEmbeddingGenerator {
    private $pdo;
    private $targetUserId = null;  // nullの場合は全ユーザー
    private $minRating = null;      // nullの場合は全評価
    private $mode = 'all';          // 'all', 'user', 'high_rated'
    private $googleBooksApiKey;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->googleBooksApiKey = defined('GOOGLE_BOOKS_API_KEY') ? GOOGLE_BOOKS_API_KEY : null;
    }
    
    /**
     * OpenAI APIでembeddingを生成
     */
    private function generateEmbedding($text) {
        if (empty($text)) return null;
        
        $text = mb_substr($text, 0, 30000); // トークン制限
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.openai.com/v1/embeddings');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . OPENAI_API_KEY
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'model' => 'text-embedding-3-small',
            'input' => $text
        ]));
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode == 200) {
            $data = json_decode($response, true);
            if (isset($data['data'][0]['embedding'])) {
                return json_encode($data['data'][0]['embedding']);
            }
        }
        
        return null;
    }
    
    /**
     * Google Books APIから書籍情報を取得
     */
    private function fetchGoogleBooksInfo($isbn) {
        if (empty($this->googleBooksApiKey)) {
            return null;
        }
        
        $clean_isbn = str_replace('-', '', $isbn);
        $url = "https://www.googleapis.com/books/v1/volumes?q=isbn:{$clean_isbn}&key={$this->googleBooksApiKey}";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode == 200) {
            $data = json_decode($response, true);
            if (!empty($data['items'][0]['volumeInfo']['description'])) {
                return $data['items'][0]['volumeInfo']['description'];
            }
        }
        
        return null;
    }
    
    /**
     * モード設定
     */
    public function setMode($mode, $userId = null, $minRating = null) {
        $this->mode = $mode;
        if ($mode === 'user') {
            $this->targetUserId = $userId ?: 12;
            $this->minRating = $minRating;
        } elseif ($mode === 'high_rated') {
            $this->targetUserId = null;
            $this->minRating = $minRating ?: 4;
        } else {
            // 'all' mode
            $this->targetUserId = null;
            $this->minRating = null;
        }
    }
    
    /**
     * 処理実行
     */
    public function process($limit = 20, $offset = 0) {
        // 統計情報を表示
        $stats = $this->getStatistics();
        
        // モードによって表示を変更
        if ($this->mode === 'user') {
            echo "Target: User ID {$this->targetUserId}";
            if ($this->minRating) echo ", Rating >= {$this->minRating}";
            echo "\n";
            echo "Books: {$stats['target_books']} / {$stats['total_books']} total\n";
        } elseif ($this->mode === 'high_rated') {
            echo "Target: All users, Rating >= {$this->minRating}\n";
            echo "High-rated books: {$stats['target_books']} / {$stats['total_books']} total\n";
        } else {
            echo "Target: All books from all users\n";
            echo "Total books: {$stats['total_books']}\n";
            echo "Books without embedding: {$stats['books_without_embedding']}\n";
        }
        
        echo "Current embeddings: {$stats['with_combined']} combined, {$stats['with_title']} title\n";
        echo str_repeat("-", 60) . "\n\n";
        
        // 対象の本を取得（モードによって変更）
        if ($this->mode === 'all') {
            // 全ての本（既にembeddingがあるものは除外）
            $sql = "
                SELECT DISTINCT
                    bl.book_id,
                    bl.name as book_title,
                    bl.author,
                    bl.amazon_id as asin,
                    bl.rating,
                    br.description,
                    br.title_embedding,
                    br.description_embedding,
                    br.combined_embedding
                FROM b_book_list bl
                LEFT JOIN b_book_repository br ON bl.amazon_id = br.asin
                WHERE bl.amazon_id IS NOT NULL
                AND bl.amazon_id != ''
                AND (br.combined_embedding IS NULL OR br.title_embedding IS NULL)
                GROUP BY bl.amazon_id
                ORDER BY 
                    CASE WHEN br.combined_embedding IS NULL THEN 0 ELSE 1 END,
                    COUNT(bl.book_id) DESC,
                    MAX(bl.rating) DESC
                LIMIT :limit
                OFFSET :offset
            ";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        } elseif ($this->mode === 'user') {
            // 特定ユーザーの本
            $sql = "
                SELECT 
                    bl.book_id,
                    bl.name as book_title,
                    bl.author,
                    bl.amazon_id as asin,
                    bl.rating,
                    br.description,
                    br.title_embedding,
                    br.description_embedding,
                    br.combined_embedding
                FROM b_book_list bl
                LEFT JOIN b_book_repository br ON bl.amazon_id = br.asin
                WHERE bl.user_id = :user_id
            ";
            if ($this->minRating) {
                $sql .= " AND bl.rating >= :min_rating";
            }
            $sql .= "
                AND bl.amazon_id IS NOT NULL
                AND bl.amazon_id != ''
                ORDER BY bl.rating DESC, bl.update_date DESC
                LIMIT :limit
                OFFSET :offset
            ";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':user_id', $this->targetUserId, PDO::PARAM_INT);
            if ($this->minRating) {
                $stmt->bindValue(':min_rating', $this->minRating, PDO::PARAM_INT);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        } else {
            // 高評価の本（全ユーザー）
            $sql = "
                SELECT DISTINCT
                    bl.book_id,
                    bl.name as book_title,
                    bl.author,
                    bl.amazon_id as asin,
                    AVG(bl.rating) as rating,
                    br.description,
                    br.title_embedding,
                    br.description_embedding,
                    br.combined_embedding
                FROM b_book_list bl
                LEFT JOIN b_book_repository br ON bl.amazon_id = br.asin
                WHERE bl.rating >= :min_rating
                AND bl.amazon_id IS NOT NULL
                AND bl.amazon_id != ''
                GROUP BY bl.amazon_id
                ORDER BY AVG(bl.rating) DESC, COUNT(bl.book_id) DESC
                LIMIT :limit
                OFFSET :offset
            ";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':min_rating', $this->minRating, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $total = count($books);
        $processed = 0;
        $success = 0;
        
        echo "Found {$total} books to process\n\n";
        
        foreach ($books as $index => $book) {
            $num = $index + 1;
            echo "[{$num}/{$total}] {$book['book_title']}\n";
            echo "  ASIN: {$book['asin']} | Rating: ★{$book['rating']}\n";
            
            $actions = [];
            
            try {
                // b_book_repositoryにレコードを作成
                if (empty($book['description']) && empty($book['title_embedding'])) {
                    $stmt = $this->pdo->prepare("
                        INSERT INTO b_book_repository (asin, title, author)
                        VALUES (:asin, :title, :author)
                        ON DUPLICATE KEY UPDATE title = VALUES(title)
                    ");
                    $stmt->execute([
                        ':asin' => $book['asin'],
                        ':title' => $book['book_title'],
                        ':author' => $book['author']
                    ]);
                    $actions[] = 'Created repository record';
                }
                
                // Google Books APIから説明文を取得
                if (empty($book['description']) && $this->googleBooksApiKey) {
                    echo "  → Fetching description from Google Books...\n";
                    $description = $this->fetchGoogleBooksInfo($book['asin']);
                    if ($description) {
                        $stmt = $this->pdo->prepare("
                            UPDATE b_book_repository 
                            SET description = :desc 
                            WHERE asin = :asin
                        ");
                        $stmt->execute([
                            ':desc' => $description,
                            ':asin' => $book['asin']
                        ]);
                        $book['description'] = $description;
                        $actions[] = 'Fetched description';
                        echo "    ✓ Description found (" . mb_strlen($description) . " chars)\n";
                    } else {
                        echo "    ✗ No description found\n";
                    }
                }
                
                // タイトルembeddingを生成
                if (empty($book['title_embedding'])) {
                    echo "  → Generating title embedding...\n";
                    $embedding = $this->generateEmbedding($book['book_title']);
                    if ($embedding) {
                        $stmt = $this->pdo->prepare("
                            UPDATE b_book_repository 
                            SET title_embedding = :embedding 
                            WHERE asin = :asin
                        ");
                        $stmt->execute([
                            ':embedding' => $embedding,
                            ':asin' => $book['asin']
                        ]);
                        $actions[] = 'Generated title embedding';
                        echo "    ✓ Title embedding created\n";
                    }
                }
                
                // 説明文embeddingを生成
                if (!empty($book['description']) && empty($book['description_embedding'])) {
                    echo "  → Generating description embedding...\n";
                    $embedding = $this->generateEmbedding($book['description']);
                    if ($embedding) {
                        $stmt = $this->pdo->prepare("
                            UPDATE b_book_repository 
                            SET description_embedding = :embedding 
                            WHERE asin = :asin
                        ");
                        $stmt->execute([
                            ':embedding' => $embedding,
                            ':asin' => $book['asin']
                        ]);
                        $actions[] = 'Generated description embedding';
                        echo "    ✓ Description embedding created\n";
                    }
                }
                
                // Combined embeddingを生成
                if (empty($book['combined_embedding'])) {
                    $combined_text = $book['book_title'];
                    if (!empty($book['description'])) {
                        $combined_text .= "\n\n" . $book['description'];
                    }
                    
                    echo "  → Generating combined embedding...\n";
                    $embedding = $this->generateEmbedding($combined_text);
                    if ($embedding) {
                        $stmt = $this->pdo->prepare("
                            UPDATE b_book_repository 
                            SET combined_embedding = :embedding,
                                embedding_generated_at = NOW(),
                                embedding_model = 'text-embedding-3-small'
                            WHERE asin = :asin
                        ");
                        $stmt->execute([
                            ':embedding' => $embedding,
                            ':asin' => $book['asin']
                        ]);
                        $actions[] = 'Generated combined embedding';
                        echo "    ✓ Combined embedding created\n";
                    }
                }
                
                if (empty($actions)) {
                    echo "  ✓ All embeddings already exist\n";
                } else {
                    echo "  ✓ Complete: " . implode(', ', $actions) . "\n";
                }
                
                $success++;
                
            } catch (Exception $e) {
                echo "  ✗ Error: " . $e->getMessage() . "\n";
            }
            
            $processed++;
            echo "\n";
            
            // API レート制限対策
            if ($num < $total) {
                sleep(1);
            }
        }
        
        echo str_repeat("=", 60) . "\n";
        echo "Processing Complete!\n";
        echo "  Processed: {$processed}\n";
        echo "  Success: {$success}\n";
        echo "  Failed: " . ($processed - $success) . "\n";
    }
    
    /**
     * 統計情報を取得
     */
    private function getStatistics() {
        $stats = [];
        
        // 全体の統計
        if ($this->mode === 'all') {
            // 全体の本の数
            $stmt = $this->pdo->prepare("
                SELECT COUNT(DISTINCT amazon_id) as total_books
                FROM b_book_list
                WHERE amazon_id IS NOT NULL AND amazon_id != ''
            ");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['total_books'] = $result['total_books'];
            
            // embeddingがない本の数
            $stmt = $this->pdo->prepare("
                SELECT COUNT(DISTINCT bl.amazon_id) as books_without_embedding
                FROM b_book_list bl
                LEFT JOIN b_book_repository br ON bl.amazon_id = br.asin
                WHERE bl.amazon_id IS NOT NULL AND bl.amazon_id != ''
                AND (br.combined_embedding IS NULL OR br.asin IS NULL)
            ");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['books_without_embedding'] = $result['books_without_embedding'];
            $stats['target_books'] = $stats['books_without_embedding'];
            
        } elseif ($this->mode === 'user') {
            // ユーザー指定の統計
            $sql = "
                SELECT 
                    COUNT(*) as total_books,
                    COUNT(CASE WHEN rating >= :min_rating THEN 1 END) as target_books
                FROM b_book_list
                WHERE user_id = :user_id
            ";
            $params = [':user_id' => $this->targetUserId];
            if ($this->minRating) {
                $params[':min_rating'] = $this->minRating;
            } else {
                $sql = str_replace('COUNT(CASE WHEN rating >= :min_rating THEN 1 END)', 'COUNT(*)', $sql);
            }
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $user_stats = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats = array_merge($stats, $user_stats);
            
        } else {
            // 高評価本の統計
            $stmt = $this->pdo->prepare("
                SELECT 
                    COUNT(DISTINCT amazon_id) as total_books,
                    COUNT(DISTINCT CASE WHEN rating >= :min_rating THEN amazon_id END) as target_books
                FROM b_book_list
                WHERE amazon_id IS NOT NULL AND amazon_id != ''
            ");
            $stmt->execute([':min_rating' => $this->minRating]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats = array_merge($stats, $result);
        }
        
        // Repository統計（共通）
        $stmt = $this->pdo->prepare("
            SELECT 
                COUNT(DISTINCT asin) as total_repo,
                COUNT(DISTINCT CASE WHEN combined_embedding IS NOT NULL THEN asin END) as with_combined,
                COUNT(DISTINCT CASE WHEN title_embedding IS NOT NULL THEN asin END) as with_title,
                COUNT(DISTINCT CASE WHEN description IS NOT NULL THEN asin END) as with_desc
            FROM b_book_repository
        ");
        $stmt->execute();
        $repo_stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return array_merge($stats, $repo_stats);
    }
}

// メイン処理
try {
    // コマンドライン引数の解析
    $mode = isset($argv[1]) ? $argv[1] : 'all';
    $limit = isset($argv[2]) ? intval($argv[2]) : 20;
    $offset = isset($argv[3]) ? intval($argv[3]) : 0;
    
    // ヘルプ表示
    if ($mode === 'help' || $mode === '--help') {
        echo "\nUsage:\n";
        echo "  php " . basename(__FILE__) . " [mode] [limit] [offset]\n\n";
        echo "Modes:\n";
        echo "  all              - 全ての本を処理（embeddingがないもの優先）\n";
        echo "  user [id]        - 特定ユーザーの本を処理（デフォルト: user_id=12）\n";
        echo "  high [rating]    - 高評価本を処理（デフォルト: rating>=4）\n";
        echo "  missing          - embeddingがない本のみ処理\n";
        echo "\nExamples:\n";
        echo "  php " . basename(__FILE__) . " all 100        # 全本から100件\n";
        echo "  php " . basename(__FILE__) . " all 100 100    # 100件スキップして100件\n";
        echo "  php " . basename(__FILE__) . " user 50        # user_id=12の50件\n";
        echo "  php " . basename(__FILE__) . " high 100       # ★４以上100件\n";
        echo "  php " . basename(__FILE__) . " missing 200    # embeddingがない200件\n";
        exit(0);
    }
    
    $generator = new SimpleEmbeddingGenerator($pdo);
    
    // モードによって処理を分岐
    switch ($mode) {
        case 'user':
            $userId = isset($argv[2]) && is_numeric($argv[2]) ? intval($argv[2]) : 12;
            $limit = isset($argv[3]) ? intval($argv[3]) : 20;
            $offset = isset($argv[4]) ? intval($argv[4]) : 0;
            $generator->setMode('user', $userId);
            echo "Processing user {$userId}'s books...\n";
            break;
            
        case 'high':
            $rating = isset($argv[2]) && is_numeric($argv[2]) ? intval($argv[2]) : 4;
            $limit = isset($argv[3]) ? intval($argv[3]) : 20;
            $offset = isset($argv[4]) ? intval($argv[4]) : 0;
            $generator->setMode('high_rated', null, $rating);
            echo "Processing high-rated books (rating >= {$rating})...\n";
            break;
            
        case 'missing':
        case 'all':
        default:
            $generator->setMode('all');
            echo "Processing all books (missing embeddings first)...\n";
            break;
    }
    
    echo "Limit: {$limit}, Offset: {$offset}\n\n";
    $generator->process($limit, $offset);
    
} catch (Exception $e) {
    echo "\nError: " . $e->getMessage() . "\n";
    exit(1);
}
?>