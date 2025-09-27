<?php
/**
 * Meta Description Generator
 * 
 * This file contains functions to generate optimized meta descriptions
 * for various page types to improve SEO.
 */

declare(strict_types=1);

/**
 * Generate meta description for book detail page
 * 
 * @param array $book Book data
 * @param float $average_rating Average rating
 * @param int $total_users Total number of users reading
 * @param int $total_reviews Total number of reviews
 * @return string Meta description
 */
function generateBookMetaDescription(
    array $book, 
    float $average_rating = 0, 
    int $total_users = 0, 
    int $total_reviews = 0
): string {
    $parts = [];
    
    // 基本情報
    $title = $book['title'] ?? $book['name'] ?? '';
    $author = $book['author'] ?? '';
    
    if (!empty($title) && !empty($author)) {
        $parts[] = sprintf('「%s」（%s著）', $title, $author);
    } elseif (!empty($title)) {
        $parts[] = sprintf('「%s」', $title);
    }
    
    // 読者数と評価
    if ($total_users > 0) {
        $parts[] = sprintf('%d人が読んでいます', $total_users);
    }
    
    if ($average_rating > 0 && $total_reviews > 0) {
        $parts[] = sprintf('平均評価%.1f（%d件のレビュー）', $average_rating, $total_reviews);
    }
    
    // 説明文（あれば）
    if (!empty($book['description'])) {
        $cleaned_desc = cleanMetaDescription($book['description'], 80);
        if (strlen($cleaned_desc) > 20) {
            $parts[] = $cleaned_desc;
        }
    }
    
    // サイト名を追加
    $parts[] = 'ReadNestで読書の進捗を管理';
    
    $description = implode('。', $parts) . '。';
    
    // 最大160文字に調整
    return cleanMetaDescription($description, 160);
}

/**
 * Generate meta description for user profile page
 * 
 * @param array $user User data
 * @param int $book_count Total books in bookshelf
 * @param array $recent_books Recent books (optional)
 * @return string Meta description
 */
function generateProfileMetaDescription(
    array $user, 
    int $book_count = 0, 
    array $recent_books = []
): string {
    $parts = [];
    
    $nickname = $user['nickname'] ?? 'ユーザー';
    
    // 基本情報
    $parts[] = sprintf('%sさんの読書記録', $nickname);
    
    // 本の数
    if ($book_count > 0) {
        $parts[] = sprintf('%d冊の本を管理', $book_count);
    }
    
    // 最近読んだ本
    if (!empty($recent_books)) {
        $recent_titles = array_slice(array_map(function($book) {
            return $book['title'] ?? $book['name'] ?? '';
        }, $recent_books), 0, 2);
        
        if (!empty($recent_titles)) {
            $parts[] = '最近：' . implode('、', $recent_titles);
        }
    }
    
    // プロフィールテキスト（あれば）
    if (!empty($user['profile_text'])) {
        $cleaned_profile = cleanMetaDescription($user['profile_text'], 50);
        if (strlen($cleaned_profile) > 10) {
            $parts[] = $cleaned_profile;
        }
    }
    
    // サイト名
    $parts[] = 'ReadNestで読書仲間とつながろう';
    
    $description = implode('。', $parts) . '。';
    
    return cleanMetaDescription($description, 160);
}

/**
 * Generate meta description for search results page
 * 
 * @param string $query Search query
 * @param string $type Search type (book, review, author, tag)
 * @param int $result_count Number of results
 * @return string Meta description
 */
function generateSearchMetaDescription(
    string $query, 
    string $type = 'book', 
    int $result_count = 0
): string {
    $type_labels = [
        'book' => '本',
        'review' => 'レビュー',
        'author' => '著者',
        'tag' => 'タグ'
    ];
    
    $type_label = $type_labels[$type] ?? '検索結果';
    
    if ($result_count > 0) {
        $description = sprintf(
            '「%s」の%s検索結果：%d件見つかりました。ReadNestで本を探して読書記録を始めましょう。',
            $query,
            $type_label,
            $result_count
        );
    } else {
        $description = sprintf(
            '「%s」の%s検索結果。ReadNestで本を探して読書記録を始めましょう。',
            $query,
            $type_label
        );
    }
    
    return cleanMetaDescription($description, 160);
}

/**
 * Generate meta description for ranking page
 * 
 * @param string $ranking_type Type of ranking
 * @param string $period Time period
 * @return string Meta description
 */
function generateRankingMetaDescription(
    string $ranking_type = 'popular', 
    string $period = 'all'
): string {
    $type_labels = [
        'popular' => '人気の本',
        'review' => '評価の高い本',
        'recent' => '新着の本',
        'reading' => '読書中の本'
    ];
    
    $period_labels = [
        'daily' => '今日の',
        'weekly' => '今週の',
        'monthly' => '今月の',
        'all' => ''
    ];
    
    $type_label = $type_labels[$ranking_type] ?? 'ランキング';
    $period_label = $period_labels[$period] ?? '';
    
    $description = sprintf(
        'ReadNest %s%sランキング。みんなが読んでいる本、話題の本を見つけて読書を楽しもう。',
        $period_label,
        $type_label
    );
    
    return cleanMetaDescription($description, 160);
}

/**
 * Generate meta description for tag page
 * 
 * @param string $tag Tag name
 * @param int $book_count Number of books with this tag
 * @return string Meta description
 */
function generateTagMetaDescription(string $tag, int $book_count = 0): string {
    if ($book_count > 0) {
        $description = sprintf(
            '「%s」タグが付いた本：%d冊。ReadNestでタグから本を探して読書の幅を広げよう。',
            $tag,
            $book_count
        );
    } else {
        $description = sprintf(
            '「%s」タグが付いた本一覧。ReadNestでタグから本を探して読書の幅を広げよう。',
            $tag
        );
    }
    
    return cleanMetaDescription($description, 160);
}

/**
 * Generate meta description for author page
 * 
 * @param string $author Author name
 * @param int $book_count Number of books by this author
 * @param array $popular_books Popular books by this author
 * @return string Meta description
 */
function generateAuthorMetaDescription(
    string $author, 
    int $book_count = 0, 
    array $popular_books = []
): string {
    $parts = [];
    
    // 基本情報
    $parts[] = sprintf('%sの作品一覧', $author);
    
    // 作品数
    if ($book_count > 0) {
        $parts[] = sprintf('%d作品', $book_count);
    }
    
    // 人気作品
    if (!empty($popular_books)) {
        $titles = array_slice(array_map(function($book) {
            return '「' . ($book['title'] ?? $book['name'] ?? '') . '」';
        }, $popular_books), 0, 2);
        
        if (!empty($titles)) {
            $parts[] = '代表作：' . implode('、', $titles);
        }
    }
    
    // サイト名
    $parts[] = 'ReadNestで著者の全作品をチェック';
    
    $description = implode('。', $parts) . '。';
    
    return cleanMetaDescription($description, 160);
}

/**
 * Generate meta description for review page
 * 
 * @param array $review Review data
 * @param array $book Book data
 * @return string Meta description
 */
function generateReviewMetaDescription(array $review, array $book): string {
    $reviewer = $review['nickname'] ?? $review['reviewer_name'] ?? 'ユーザー';
    $title = $book['title'] ?? $book['name'] ?? '';
    $author = $book['author'] ?? '';
    
    $parts = [];
    
    // 基本情報
    if (!empty($title) && !empty($author)) {
        $parts[] = sprintf('%sさんによる「%s」（%s著）のレビュー', $reviewer, $title, $author);
    } else {
        $parts[] = sprintf('%sさんのレビュー', $reviewer);
    }
    
    // 評価
    if (!empty($review['rating'])) {
        $parts[] = sprintf('評価：%d/5', $review['rating']);
    }
    
    // レビュー本文の抜粋
    if (!empty($review['comment']) || !empty($review['review_text'])) {
        $review_text = $review['comment'] ?? $review['review_text'] ?? '';
        $excerpt = cleanMetaDescription($review_text, 80);
        if (strlen($excerpt) > 20) {
            $parts[] = $excerpt;
        }
    }
    
    // サイト名
    $parts[] = 'ReadNest';
    
    $description = implode('。', $parts) . '。';
    
    return cleanMetaDescription($description, 160);
}

/**
 * Generate dynamic meta keywords based on page content
 * 
 * @param array $data Page data
 * @param string $page_type Type of page
 * @return string Meta keywords
 */
function generateMetaKeywords(array $data, string $page_type): string {
    $keywords = ['ReadNest', '読書', '本'];
    
    switch ($page_type) {
        case 'book':
            if (!empty($data['title'])) {
                $keywords[] = $data['title'];
            }
            if (!empty($data['author'])) {
                $keywords[] = $data['author'];
            }
            if (!empty($data['tags'])) {
                $keywords = array_merge($keywords, array_slice($data['tags'], 0, 3));
            }
            $keywords[] = '書評';
            $keywords[] = 'レビュー';
            break;
            
        case 'profile':
            if (!empty($data['nickname'])) {
                $keywords[] = $data['nickname'];
            }
            $keywords[] = '本棚';
            $keywords[] = '読書記録';
            break;
            
        case 'author':
            if (!empty($data['author'])) {
                $keywords[] = $data['author'];
                $keywords[] = $data['author'] . ' 作品';
            }
            break;
            
        case 'tag':
            if (!empty($data['tag'])) {
                $keywords[] = $data['tag'];
                $keywords[] = $data['tag'] . ' 本';
            }
            break;
    }
    
    // 重複を削除して最大10個まで
    $keywords = array_unique($keywords);
    $keywords = array_slice($keywords, 0, 10);
    
    return implode(',', $keywords);
}