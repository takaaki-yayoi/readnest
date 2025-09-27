<?php
/**
 * モダン版本追加ページ
 * PHP 8.2.28対応版
 */

declare(strict_types=1);

// モダン設定を読み込み
require_once('modern_config.php');
require_once('library/book_search.php');
require_once('library/form_helpers.php');
require_once('library/csrf.php');  // CSRF機能を明示的に読み込み
require_once('library/rate_limiter.php');
// require_once('library/genre_detector.php'); // ジャンル機能一時無効化

// ログインチェック
if (!checkLogin()) {
    header('Location: https://readnest.jp');
    exit;
}

$user_id = (int)$_SESSION['AUTH_USER'];
$d_nickname = getNickname($user_id);
$d_message = '';

// ページタイトル設定
$d_site_title = "本を追加 - ReadNest";

// メタ情報
$g_meta_description = "ReadNestに新しい本を追加しましょう。キーワード検索で本を見つけて、あなたの本棚に追加できます。";
$g_meta_keyword = "本追加,検索,ReadNest,本棚,読書記録";

// ユーザー情報取得
$user_info_array = getUserInformation($user_id);

// 本追加処理
if (isset($_POST['asin'])) {
    // レート制限チェック（1分間に20回まで）
    if (!checkRateLimitByUser((int)$user_id, 'book_add', 20, 60)) {
        $remaining_time = getRateLimitRemainingTime("user_{$user_id}", 'book_add', 60);
        $d_message = '<div class="text-red-600">短時間に多くの本を追加しています。' . $remaining_time . '秒後に再度お試しください。</div>';
        
        // レート制限エラーをログに記録
        error_log(sprintf(
            "Rate limit exceeded for book_add: user_id=%d, ip=%s, remaining_time=%d seconds",
            $user_id,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $remaining_time
        ));
    }
    // CSRFトークン検証（有効期限を4時間に延長）
    elseif (!verifyCSRFToken($_POST['csrf_token'] ?? null, 14400)) {
        $d_message = '<div class="text-red-600">セキュリティエラーが発生しました。ページを再読み込みしてもう一度お試しください。</div>';
        
        // 新しいトークンを生成（次回のために）
        generateCSRFToken();
        
        // CSRFトークンエラーをログに記録（詳細情報を追加）
        error_log(sprintf(
            "CSRF token validation failed for book_add: user_id=%d, ip=%s, posted_token=%s, session_token=%s",
            $user_id,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            substr($_POST['csrf_token'] ?? 'none', 0, 10) . '...',
            substr($_SESSION['csrf_token'] ?? 'none', 0, 10) . '...'
        ));
    } else {
        // 入力値の取得と検証
        $book_asin = trim($_POST['asin'] ?? '');
        $book_isbn = trim($_POST['isbn'] ?? '');
        $book_name = trim($_POST['product_name'] ?? '');
        $status = (int)($_POST['status_list'] ?? NOT_STARTED);
        $number_of_pages = max(0, (int)($_POST['number_of_pages'] ?? 0)); // 負数を防ぐ
        $detail_url = trim($_POST['detail_url'] ?? '');
        $image_url = trim($_POST['image_url'] ?? '');
        $author = trim($_POST['author'] ?? '');
        $finished_date = null;
        
        // 必須フィールドの検証
        if (empty($book_name)) {
            $d_message = '<div class="text-red-600">本のタイトルが指定されていません。</div>';
        } else {
            // 読了日の処理と検証
            if (($status == READING_FINISH || $status == READ_BEFORE) && !empty($_POST['finished_date'])) {
                $date = DateTime::createFromFormat('Y-m-d', $_POST['finished_date']);
                if ($date && $date->format('Y-m-d') === $_POST['finished_date']) {
                    $finished_date = $_POST['finished_date'];
                } else {
                    $d_message = '<div class="text-red-600">無効な読了日形式です。</div>';
                }
            }
            
            // エラーがない場合のみ処理を続行
            if (empty($d_message)) {
                $memo = '';
                
                // カテゴリ情報をデコード
                $categories = null;
                if (!empty($_POST['categories'])) {
                    $categories = json_decode($_POST['categories'], true);
                }
                
                try {
                    // 読みかけの本が無い場合に追加処理
                    $result = is_bookmarked_finished($user_id, $book_asin);
                    
                    if (!$result || is_array($result)) {
                        $book_id = createBook($user_id, $book_name, $book_asin, $book_isbn, $author, $memo, $number_of_pages, $status, $detail_url, $image_url, $finished_date, $categories);
                        
                        if ($book_id && $status == READING_FINISH) {
                            createEvent($user_id, $book_id, $memo, $number_of_pages);
                        }
                        
                        $book_name_escaped = html($book_name);
                        $d_message = "「<a href=\"/book/{$book_id}\" class=\"text-readnest-primary hover:underline\">{$book_name_escaped}</a>」を本棚に追加しました。";
                    } else {
                        $d_message = '<div class="text-yellow-600">すでに本棚にあります。</div>';
                    }
                } catch (Exception $e) {
                    error_log("Book creation error: " . $e->getMessage());
                    $d_message = '<div class="text-red-600">本の追加中にエラーが発生しました。しばらくしてから再度お試しください。</div>';
                }
            }
        }
    }
}

// 検索処理
$keyword = '';
$page = 1;
$d_book_list = '';
$d_total_hit = '';
$d_pager = '';

// グローバル検索からの遷移をサポート
$prefilled_title = '';
$prefilled_author = '';
$prefilled_isbn = '';
$prefilled_asin = '';
$prefilled_image_url = '';
$prefilled_total_page = '';
if (isset($_GET['from']) && $_GET['from'] === 'global_search') {
    $prefilled_title = trim($_GET['title'] ?? '');
    $prefilled_author = trim($_GET['author'] ?? '');
    $prefilled_isbn = trim($_GET['isbn'] ?? '');
    $prefilled_asin = trim($_GET['asin'] ?? '');
    $prefilled_image_url = trim($_GET['image_url'] ?? '');
    $prefilled_total_page = trim($_GET['total_page'] ?? '');
    
    // タイトルをキーワードとして検索を自動実行
    if (!empty($prefilled_title)) {
        $keyword = $prefilled_title;
    }
}

// バーコードスキャンからのISBN検索をサポート
if (isset($_GET['isbn']) && !isset($_GET['from'])) {
    $isbn = trim($_GET['isbn']);
    // ISBNをキーワードとして検索
    $keyword = $isbn;
}

// search_wordパラメータの処理（作家クラウドからの遷移対応）
if (isset($_GET['search_word']) && !isset($_GET['keyword'])) {
    $_GET['keyword'] = $_GET['search_word'];
}

// searchパラメータの処理（レコメンデーションページからの遷移対応）
if (isset($_GET['search']) && !isset($_GET['keyword'])) {
    $_GET['keyword'] = $_GET['search'];
}

// search_typeがauthorの場合の処理
$search_type = $_GET['search_type'] ?? null;
$d_author_info_html = ''; // 作家情報表示用HTML

if (isset($_GET['keyword']) || isset($_POST['keyword']) || isset($_GET['isbn']) || !empty($keyword)) {
    $keyword = trim($_GET['keyword'] ?? $_POST['keyword'] ?? $keyword ?? '');
    $page = max(1, (int)($_GET['page'] ?? $_POST['page'] ?? 1)); // ページ番号は最低1
    
    $d_keyword = htmlspecialchars((string)$keyword, ENT_QUOTES, 'UTF-8'); // 表示用にエスケープ（型安全）
    
    // 本検索処理
    if (!empty($keyword)) {
        // 作家検索の場合、作家情報を取得
        if ($search_type === 'author' && $page == 1) {
            require_once('library/author_info_fetcher.php');
            $author_fetcher = new AuthorInfoFetcher();
            $d_author_info_html = $author_fetcher->generateAuthorInfoHtml($keyword);
        }
        // 検索レート制限（1分間に20回まで）
        if (!checkRateLimitByUser((int)$user_id, 'book_search', 20, 60)) {
            $remaining_time = getRateLimitRemainingTime("user_{$user_id}", 'book_search', 60);
            $d_total_hit = '<div class="text-red-600">検索回数の制限に達しました。' . $remaining_time . '秒後に再度お試しください。</div>';
            $d_book_list = '';
            $d_pager = '';
            
            // レート制限エラーをログに記録
            error_log(sprintf(
                "Rate limit exceeded for book_search: user_id=%d, ip=%s, keyword=%s, remaining_time=%d seconds",
                $user_id,
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $keyword,
                $remaining_time
            ));
        } else {
            try {
                // AI検索の判定
                $use_ai_search = isset($_GET['ai_search']) && $_GET['ai_search'] === 'on';
                
                if ($use_ai_search && file_exists('library/ai_search_engine.php')) {
                    // AI検索を使用
                    require_once('library/ai_search_engine.php');
                    $ai_search = new AISearchEngine();
                    $ai_result = $ai_search->search($keyword, $page, 20);
                    
                    // AI検索結果を通常の検索結果フォーマットに変換
                    if ($ai_result['success'] && !empty($ai_result['results'])) {
                        $g_book_array = $ai_result['results'];
                        $g_book_total_num = $ai_result['total'];
                        $g_book_total_pages = (int)ceil($g_book_total_num / 20);
                        
                        // AI検索の意図を表示用に保存
                        $ai_search_intent = $ai_result['intent'] ?? [];
                        $ai_expanded_keywords = $ai_result['keywords'] ?? [];
                    } else {
                        // AI検索が失敗した場合は通常検索にフォールバック
                        $search_result = searchBooks($keyword, $page, 20);
                        $g_book_array = $search_result['books'];
                        $g_book_total_num = $search_result['total'];
                        $g_book_total_pages = (int)ceil($g_book_total_num / 20);
                    }
                } else {
                    // 通常の検索を使用
                    $search_result = searchBooks($keyword, $page, 20);
                    $g_book_array = $search_result['books'];
                    $g_book_total_num = $search_result['total'];
                    $g_book_total_pages = (int)ceil($g_book_total_num / 20);
                }
            
            $actual_count = count($g_book_array);
            
            if ($actual_count > 0) {
                // 実際に表示している件数を基準とした表示
                $start_num = ($page - 1) * 20 + 1;
                $end_num = ($page - 1) * 20 + $actual_count;
                
                if ($page == 1 && $actual_count < 20) {
                    // 1ページ目で20件未満の場合、それが全件
                    $d_total_hit = "{$actual_count}件がヒットしました。";
                } else {
                    // 複数ページまたは満額の場合
                    if ($g_book_total_num > $actual_count) {
                        $d_total_hit = "約{$g_book_total_num}件がヒットしました。({$start_num}〜{$end_num}件目を表示)";
                    } else {
                        $d_total_hit = "{$actual_count}件がヒットしました。";
                    }
                }
                
                $d_book_list = generateBookList($g_book_array, $user_id, $d_keyword, $page);
                
                // ページネーション生成（表示用エスケープ済みキーワードを使用）
                $ai_search_param = isset($_GET['ai_search']) && $_GET['ai_search'] === 'on' ? '&ai_search=on' : '';
                $d_pager = generatePagination($page, $g_book_total_pages, $d_keyword, $ai_search_param);
            } else {
                $d_total_hit = "「{$d_keyword}」に該当する本が見つかりませんでした。別のキーワードで検索してください。";
                $d_book_list = '';
                $d_pager = '';
            }
        } catch (Exception $e) {
            error_log("Book search error: " . $e->getMessage());
            $d_total_hit = "検索中にエラーが発生しました。しばらくしてから再度お試しください。";
            $d_book_list = '';
            $d_pager = '';
            }
        }
    }
} elseif (isset($_GET['asin']) && !empty($_GET['asin'])) {
    // ASINが直接指定された場合
    $asin = trim($_GET['asin']);
    $d_keyword = $asin;
    
    // ASINで直接検索
    try {
        $search_result = searchBooks($asin, 1, 1);
        $g_book_array = $search_result['books'];
        $g_book_total_num = $search_result['total'];
        
        if (count($g_book_array) > 0) {
            $d_total_hit = "指定された本が見つかりました。";
            $d_book_list = generateBookList($g_book_array, $user_id, $asin, 1);
            $d_pager = '';
        } else {
            // ASINで見つからない場合はリポジトリから取得
            $book_sql = "SELECT * FROM b_book_repository WHERE asin = ?";
            $book_data = $g_db->getRow($book_sql, [$asin], DB_FETCHMODE_ASSOC);
            
            if (!DB::isError($book_data) && $book_data) {
                // リポジトリの情報を表示用に整形
                $g_book_array = [[
                    'Title' => $book_data['title'],
                    'Author' => $book_data['author'],
                    'ASIN' => $book_data['asin'],
                    'ISBN' => $book_data['isbn'] ?? '',
                    'LargeImage' => $book_data['image_url'] ?? '',
                    'DetailPageURL' => "https://www.amazon.co.jp/dp/{$book_data['asin']}",
                    'NumberOfPages' => $book_data['pages'] ?? 0
                ]];
                $d_total_hit = "指定された本が見つかりました。";
                $d_book_list = generateBookList($g_book_array, $user_id, $asin, 1);
                $d_pager = '';
            } else {
                $d_total_hit = "指定された本が見つかりませんでした。";
                $d_book_list = '';
                $d_pager = '';
            }
        }
    } catch (Exception $e) {
        error_log("ASIN search error: " . $e->getMessage());
        $d_total_hit = "検索中にエラーが発生しました。";
        $d_book_list = '';
        $d_pager = '';
    }
} else {
    $d_keyword = '';
}


// 本リスト生成関数
function generateBookList(array $books, int $user_id, string $keyword, int $page): string {
    $html = '<div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3 sm:gap-4 md:gap-6">';
    
    foreach ($books as $i => $book) {
        $product_name = html($book['Title']);
        $product_img = $book['LargeImage'];
        $asin = $book['ASIN'];
        $isbn = $book['ISBN'];
        $author = html($book['Author']);
        $amazon_link = $book['DetailPageURL'];
        $number_of_pages = $book['NumberOfPages'];
        
        // 画像表示（エラー時の複数フォールバック対応）
        if (!empty($product_img)) {
            $image_part = "<img src=\"{$product_img}\" alt=\"{$product_name}\" class=\"w-full h-48 object-contain rounded-lg\" 
                         onerror=\"this.onerror=null; this.src='/img/no-image-book.png';\" 
                         loading=\"lazy\">";
        } else {
            $image_part = "<img src=\"/img/no-image-book.png\" alt=\"{$product_name}\" class=\"w-full h-48 object-contain rounded-lg\" loading=\"lazy\">";
        }
        
        // 本棚にあるかチェック
        $bookmarked_result = is_bookmarked($user_id, $asin);
        
        // タイトルのリンク先を決定
        if ($bookmarked_result) {
            $title_link = "/book/{$bookmarked_result}";
            $title_target = "";
            $title_icon = "";
            $add_button = "<a href=\"/book/{$bookmarked_result}\" class=\"btn bg-gray-100 text-gray-600 w-full\"><i class=\"fas fa-check mr-2\"></i>本棚にあります</a>";
        } else {
            $title_link = $amazon_link;
            $title_target = "target=\"_blank\" rel=\"noopener noreferrer\"";
            $title_icon = "<i class=\"fas fa-external-link-alt ml-1 text-xs opacity-60\"></i>";
            $add_button = generateAddBookForm($book, $keyword, $page);
        }
        
        $pages_display = $number_of_pages ?: '-';
        
        $html .= "
        <div class=\"bg-white rounded-lg shadow-sm border p-3 sm:p-4 hover:shadow-md transition-shadow\">
            <div class=\"mb-3 sm:mb-4 bg-gray-50 rounded-lg p-2\">
                {$image_part}
            </div>
            <div class=\"space-y-1.5 sm:space-y-2\">
                <h3 class=\"font-semibold text-sm sm:text-base text-gray-900 line-clamp-2\">
                    <a href=\"{$title_link}\" {$title_target} class=\"hover:text-readnest-primary transition-colors\">
                        {$product_name}{$title_icon}
                    </a>
                </h3>
                <p class=\"text-xs sm:text-sm text-gray-600 line-clamp-1\">{$author}</p>
                <p class=\"text-xs text-gray-500\">総ページ数: {$pages_display}</p>
                <div class=\"pt-1.5 sm:pt-2\">
                    {$add_button}
                </div>
            </div>
        </div>";
    }
    
    $html .= '</div>';
    return $html;
}

// 本追加フォーム生成関数
function generateAddBookForm(array $book, string $keyword, int $page): string {
    $asin = html($book['ASIN'] ?? '');
    $isbn = html($book['ISBN'] ?? '');
    $author = html($book['Author'] ?? '');
    $product_name = html($book['Title'] ?? '');
    $number_of_pages = (int)($book['NumberOfPages'] ?? 0);
    $detail_url = html($book['DetailPageURL'] ?? '');
    $image_url = html($book['LargeImage'] ?? '');
    
    // カテゴリ情報をJSON形式で保存
    $categories_json = !empty($book['Categories']) ? htmlspecialchars(json_encode($book['Categories'], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') : '';
    
    // エスケープ済みのキーワードを使用
    $keyword_escaped = htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8');
    
    
    return "
    <form id=\"add-book-{$asin}\" action=\"" . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . "\" method=\"post\" class=\"space-y-3\">
        " . csrfField() . "
        <div class=\"space-y-2\">
            <label class=\"block text-sm font-medium text-gray-700\">ステータス</label>
            <select name=\"status_list\" class=\"w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-readnest-primary focus:border-transparent text-sm\">
                <option value=\"" . BUY_SOMEDAY . "\">いつか買う</option>
                <option value=\"" . NOT_STARTED . "\" selected>買ったけどまだ読んでない</option>
                <option value=\"" . READING_NOW . "\">読んでいるところ</option>
                <option value=\"" . READING_FINISH . "\">読み終わった！</option>
                <option value=\"" . READ_BEFORE . "\">昔読んだ</option>
            </select>
        </div>
        <div class=\"space-y-2 finished-date-container\" style=\"display: none;\">
            <label class=\"block text-sm font-medium text-gray-700\">読了日</label>
            <input type=\"date\" name=\"finished_date\" class=\"w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-readnest-primary focus:border-transparent text-sm\" max=\"" . date('Y-m-d') . "\">
        </div>
        " . (empty($number_of_pages) ? "
        <div class=\"space-y-2\">
            <label class=\"block text-sm font-medium text-gray-700\">総ページ数</label>
            <input type=\"number\" name=\"number_of_pages\" min=\"0\" max=\"99999\" class=\"w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-readnest-primary focus:border-transparent text-sm\" placeholder=\"ページ数を入力\">
        </div>
        " : "<input type=\"hidden\" name=\"number_of_pages\" value=\"{$number_of_pages}\">") . "
        <input type=\"hidden\" name=\"asin\" value=\"{$asin}\">
        <input type=\"hidden\" name=\"isbn\" value=\"{$isbn}\">
        <input type=\"hidden\" name=\"author\" value=\"{$author}\">
        <input type=\"hidden\" name=\"product_name\" value=\"{$product_name}\">
        <input type=\"hidden\" name=\"detail_url\" value=\"{$detail_url}\">
        <input type=\"hidden\" name=\"image_url\" value=\"{$image_url}\">
        <input type=\"hidden\" name=\"categories\" value=\"{$categories_json}\">
        <input type=\"hidden\" name=\"keyword\" value=\"{$keyword_escaped}\">
        <input type=\"hidden\" name=\"page\" value=\"{$page}\">
        <div class=\"flex gap-2\">
            <button type=\"submit\" class=\"btn bg-readnest-primary text-white flex-1 hover:bg-readnest-accent transition-colors\" onclick=\"return confirm('「{$product_name}」を本棚に追加しますか？')\">
                <i class=\"fas fa-plus mr-2\"></i>本棚に追加
            </button>
            <a href=\"https://www.amazon.co.jp/s?k=" . urlencode($product_name . ' ' . $author) . "\" 
               target=\"_blank\"
               class=\"btn bg-orange-500 text-white px-4 hover:bg-orange-600 transition-colors flex items-center justify-center\">
                <i class=\"fab fa-amazon\"></i>
            </a>
        </div>
    </form>
    <script>
    // ステータス変更時に読了日フィールドを表示/非表示
    (function() {
        const currentForm = document.getElementById('add-book-{$book['ASIN']}');
        if (!currentForm) return;
        
        const statusSelect = currentForm.querySelector('select[name=\"status_list\"]');
        const dateContainer = currentForm.querySelector('.finished-date-container');
        
        if (statusSelect && dateContainer) {
            statusSelect.addEventListener('change', function() {
                const status = parseInt(this.value);
                if (status === " . READING_FINISH . " || status === " . READ_BEFORE . ") {
                    dateContainer.style.display = 'block';
                } else {
                    dateContainer.style.display = 'none';
                }
            });
        }
    })();
    </script>";
}

// ページネーション生成関数
function generatePagination(int $current_page, int $total_pages, string $keyword, string $additional_params = ''): string {
    if ($total_pages <= 1) {
        return '';
    }
    
    $html = '<nav class="flex justify-center mt-8">';
    $html .= '<div class="flex flex-wrap items-center gap-1 sm:gap-2">';
    
    // 前のページ
    if ($current_page > 1) {
        $prev_page = $current_page - 1;
        $html .= "<a href=\"?keyword=" . urlencode($keyword) . "&page={$prev_page}{$additional_params}\" class=\"px-2 sm:px-3 py-1.5 sm:py-2 text-sm sm:text-base bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors\">&laquo; <span class=\"hidden sm:inline\">前</span></a>";
    }
    
    // モバイル用の簡略表示
    $is_mobile = true; // レスポンシブ対応として常に簡略表示を使用
    
    if ($is_mobile && $total_pages > 5) {
        // モバイルの場合は現在ページ付近のみ表示
        $start = max(1, $current_page - 1);
        $end = min($total_pages, $current_page + 1);
    } else {
        // PCの場合は通常表示
        $start = max(1, $current_page - 2);
        $end = min($total_pages, $current_page + 2);
    }
    
    if ($start > 1) {
        $html .= "<a href=\"?keyword=" . urlencode($keyword) . "&page=1{$additional_params}\" class=\"px-2 sm:px-3 py-1.5 sm:py-2 text-sm sm:text-base bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors\">1</a>";
        if ($start > 2) {
            $html .= "<span class=\"px-1 sm:px-3 py-1.5 sm:py-2 text-sm sm:text-base\">...</span>";
        }
    }
    
    for ($i = $start; $i <= $end; $i++) {
        if ($i == $current_page) {
            $html .= "<span class=\"px-2 sm:px-3 py-1.5 sm:py-2 text-sm sm:text-base bg-readnest-primary text-white rounded-md\">{$i}</span>";
        } else {
            $html .= "<a href=\"?keyword=" . urlencode($keyword) . "&page={$i}{$additional_params}\" class=\"px-2 sm:px-3 py-1.5 sm:py-2 text-sm sm:text-base bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors\">{$i}</a>";
        }
    }
    
    if ($end < $total_pages) {
        if ($end < $total_pages - 1) {
            $html .= "<span class=\"px-1 sm:px-3 py-1.5 sm:py-2 text-sm sm:text-base\">...</span>";
        }
        $html .= "<a href=\"?keyword=" . urlencode($keyword) . "&page={$total_pages}{$additional_params}\" class=\"px-2 sm:px-3 py-1.5 sm:py-2 text-sm sm:text-base bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors\">{$total_pages}</a>";
    }
    
    // 次のページ
    if ($current_page < $total_pages) {
        $next_page = $current_page + 1;
        $html .= "<a href=\"?keyword=" . urlencode($keyword) . "&page={$next_page}{$additional_params}\" class=\"px-2 sm:px-3 py-1.5 sm:py-2 text-sm sm:text-base bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors\"><span class=\"hidden sm:inline\">次</span> &raquo;</a>";
    }
    
    $html .= '</div></nav>';
    return $html;
}

// Analytics設定
$g_analytics = '<!-- Google Analytics code would go here -->';

// モダンテンプレートを使用してページを表示
include(getTemplatePath('t_add_book.php'));