<?php
/**
 * SEO Helper Functions
 * 
 * This file contains functions to generate SEO-friendly meta tags,
 * Open Graph tags, Twitter Cards, and structured data.
 */

declare(strict_types=1);

/**
 * Generate Open Graph meta tags
 * 
 * @param array $data Array containing og data
 * @return string HTML meta tags
 */
function generateOpenGraphTags(array $data): string {
    $defaults = [
        'site_name' => 'ReadNest',
        'locale' => 'ja_JP',
        'type' => 'website'
    ];
    
    $data = array_merge($defaults, $data);
    $tags = [];
    
    // Required OG tags
    $required = ['title', 'description', 'url', 'image'];
    foreach ($required as $prop) {
        if (isset($data[$prop]) && !empty($data[$prop])) {
            $tags[] = sprintf('<meta property="og:%s" content="%s">', $prop, htmlspecialchars($data[$prop], ENT_QUOTES, 'UTF-8'));
        }
    }
    
    // Additional OG tags
    $additional = ['type', 'site_name', 'locale'];
    foreach ($additional as $prop) {
        if (isset($data[$prop]) && !empty($data[$prop])) {
            $tags[] = sprintf('<meta property="og:%s" content="%s">', $prop, htmlspecialchars($data[$prop], ENT_QUOTES, 'UTF-8'));
        }
    }
    
    // Image dimensions if available
    if (isset($data['image:width']) && isset($data['image:height'])) {
        $tags[] = sprintf('<meta property="og:image:width" content="%s">', $data['image:width']);
        $tags[] = sprintf('<meta property="og:image:height" content="%s">', $data['image:height']);
    }
    
    return implode("\n", $tags);
}

/**
 * Generate Twitter Card meta tags
 * 
 * @param array $data Array containing twitter card data
 * @return string HTML meta tags
 */
function generateTwitterCardTags(array $data): string {
    $defaults = [
        'card' => 'summary_large_image',
        'site' => '@readnest_jp'  // TwitterアカウントがあればSomeday
    ];
    
    $data = array_merge($defaults, $data);
    $tags = [];
    
    // Twitter card properties
    $properties = ['card', 'site', 'title', 'description', 'image', 'creator'];
    foreach ($properties as $prop) {
        if (isset($data[$prop]) && !empty($data[$prop])) {
            $tags[] = sprintf('<meta name="twitter:%s" content="%s">', $prop, htmlspecialchars($data[$prop], ENT_QUOTES, 'UTF-8'));
        }
    }
    
    return implode("\n", $tags);
}

/**
 * Generate canonical URL tag
 * 
 * @param string $url The canonical URL
 * @return string HTML link tag
 */
function generateCanonicalTag(string $url): string {
    return sprintf('<link rel="canonical" href="%s">', htmlspecialchars($url, ENT_QUOTES, 'UTF-8'));
}

/**
 * Generate Book structured data (Schema.org)
 * 
 * @param array $book Book data
 * @return string JSON-LD script
 */
function generateBookSchema(array $book): string {
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'Book',
        'name' => $book['title'] ?? '',
        'author' => [
            '@type' => 'Person',
            'name' => $book['author'] ?? ''
        ]
    ];
    
    // Optional fields
    if (!empty($book['isbn'])) {
        $schema['isbn'] = $book['isbn'];
    }
    
    if (!empty($book['description'])) {
        $schema['description'] = mb_substr($book['description'], 0, 300);
    }
    
    if (!empty($book['image_url']) && strpos($book['image_url'], 'noimage') === false) {
        $schema['image'] = $book['image_url'];
    }
    
    if (!empty($book['publisher'])) {
        $schema['publisher'] = [
            '@type' => 'Organization',
            'name' => $book['publisher']
        ];
    }
    
    if (!empty($book['published_date'])) {
        $schema['datePublished'] = $book['published_date'];
    }
    
    if (!empty($book['pages'])) {
        $schema['numberOfPages'] = $book['pages'];
    }
    
    // Aggregate rating if available
    if (!empty($book['rating_average']) && !empty($book['rating_count'])) {
        $schema['aggregateRating'] = [
            '@type' => 'AggregateRating',
            'ratingValue' => $book['rating_average'],
            'reviewCount' => $book['rating_count']
        ];
    }
    
    return sprintf(
        '<script type="application/ld+json">%s</script>',
        json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
    );
}

/**
 * Generate Review structured data
 * 
 * @param array $review Review data
 * @return string JSON-LD script
 */
function generateReviewSchema(array $review): string {
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'Review',
        'itemReviewed' => [
            '@type' => 'Book',
            'name' => $review['book_title'] ?? '',
            'author' => [
                '@type' => 'Person',
                'name' => $review['book_author'] ?? ''
            ]
        ],
        'author' => [
            '@type' => 'Person',
            'name' => $review['reviewer_name'] ?? ''
        ],
        'reviewBody' => mb_substr($review['review_text'] ?? '', 0, 500),
        'datePublished' => $review['created_date'] ?? date('Y-m-d')
    ];
    
    if (!empty($review['rating'])) {
        $schema['reviewRating'] = [
            '@type' => 'Rating',
            'ratingValue' => $review['rating'],
            'bestRating' => 5,
            'worstRating' => 1
        ];
    }
    
    return sprintf(
        '<script type="application/ld+json">%s</script>',
        json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
    );
}

/**
 * Generate Organization structured data for homepage
 * 
 * @return string JSON-LD script
 */
function generateOrganizationSchema(): string {
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'WebSite',
        'name' => 'ReadNest',
        'alternateName' => 'ReadNest - あなたの読書の巣',
        'url' => 'https://readnest.jp/',
        'description' => '読書の進捉を記録し、レビューを書き、本を整理するための居心地のよい空間です。',
        'potentialAction' => [
            '@type' => 'SearchAction',
            'target' => [
                '@type' => 'EntryPoint',
                'urlTemplate' => 'https://readnest.jp/search_results.php?q={search_term_string}'
            ],
            'query-input' => 'required name=search_term_string'
        ]
    ];
    
    return sprintf(
        '<script type="application/ld+json">%s</script>',
        json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
    );
}

/**
 * Generate Person structured data for profile pages
 * 
 * @param array $user User data
 * @return string JSON-LD script
 */
function generatePersonSchema(array $user): string {
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'Person',
        'name' => $user['nickname'] ?? '',
        'url' => 'https://readnest.jp/user/' . ($user['user_id'] ?? '')
    ];
    
    if (!empty($user['profile_text'])) {
        $schema['description'] = mb_substr($user['profile_text'], 0, 160);
    }
    
    return sprintf(
        '<script type="application/ld+json">%s</script>',
        json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
    );
}

/**
 * Generate breadcrumb structured data
 * 
 * @param array $items Array of breadcrumb items ['name' => '', 'url' => '']
 * @return string JSON-LD script
 */
function generateBreadcrumbSchema(array $items): string {
    $itemListElement = [];
    
    foreach ($items as $position => $item) {
        $itemListElement[] = [
            '@type' => 'ListItem',
            'position' => $position + 1,
            'name' => $item['name'],
            'item' => $item['url']
        ];
    }
    
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => $itemListElement
    ];
    
    return sprintf(
        '<script type="application/ld+json">%s</script>',
        json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
    );
}

/**
 * Generate all SEO meta tags for a page
 * 
 * @param array $seoData All SEO data for the page
 * @return string Combined HTML meta tags
 */
function generateSEOTags(array $seoData): string {
    $tags = [];
    
    // Basic meta tags
    if (!empty($seoData['title'])) {
        $tags[] = sprintf('<title>%s</title>', htmlspecialchars($seoData['title'], ENT_QUOTES, 'UTF-8'));
    }
    
    if (!empty($seoData['description'])) {
        $tags[] = sprintf('<meta name="description" content="%s">', htmlspecialchars($seoData['description'], ENT_QUOTES, 'UTF-8'));
    }
    
    // Canonical URL
    if (!empty($seoData['canonical_url'])) {
        $tags[] = generateCanonicalTag($seoData['canonical_url']);
    }
    
    // Open Graph tags
    if (!empty($seoData['og'])) {
        $tags[] = generateOpenGraphTags($seoData['og']);
    }
    
    // Twitter Card tags
    if (!empty($seoData['twitter'])) {
        $tags[] = generateTwitterCardTags($seoData['twitter']);
    }
    
    // Structured data
    if (!empty($seoData['schema'])) {
        foreach ($seoData['schema'] as $schema) {
            $tags[] = $schema;
        }
    }
    
    return implode("\n", $tags);
}

/**
 * Clean and truncate text for meta descriptions
 * 
 * @param string $text Text to clean
 * @param int $length Maximum length
 * @return string Cleaned text
 */
function cleanMetaDescription(string $text, int $length = 160): string {
    // Remove HTML tags
    $text = strip_tags($text);
    
    // Remove multiple whitespaces
    $text = preg_replace('/\s+/', ' ', $text);
    
    // Trim
    $text = trim($text);
    
    // Truncate
    if (mb_strlen($text) > $length) {
        $text = mb_substr($text, 0, $length - 3) . '...';
    }
    
    return $text;
}

/**
 * Get the full URL for the current page
 * 
 * @return string Full URL
 */
function getCurrentUrl(): string {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'readnest.jp';
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    
    return $protocol . $host . $uri;
}

/**
 * Get the base URL of the site
 * 
 * @return string Base URL
 */
function getBaseUrl(): string {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'readnest.jp';
    
    return $protocol . $host;
}