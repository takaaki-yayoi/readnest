<?php
/**
 * X (Twitter) Share Button Component
 * Uses Web Intents (no API key required)
 */

/**
 * Generate X share URL
 * @param string $text Tweet text
 * @param string $url URL to share (optional)
 * @param array $hashtags Array of hashtags (optional)
 * @return string Share URL
 */
function getXShareUrl($text, $url = '', $hashtags = []) {
    $params = [
        'text' => $text
    ];
    
    if ($url) {
        $params['url'] = $url;
    }
    
    if (!empty($hashtags)) {
        $params['hashtags'] = implode(',', $hashtags);
    }
    
    return 'https://x.com/intent/tweet?' . http_build_query($params);
}

/**
 * Generate X share button HTML
 * @param string $text Tweet text
 * @param string $url URL to share (optional)
 * @param array $options Button options
 * @return string HTML
 */
function renderXShareButton($text, $url = '', $options = []) {
    $defaults = [
        'hashtags' => ['読書記録', 'ReadNest'],
        'class' => 'x-share-button',
        'target' => '_blank',
        'text_label' => 'Xでシェア'
    ];
    
    $options = array_merge($defaults, $options);
    $share_url = getXShareUrl($text, $url, $options['hashtags']);
    
    return sprintf(
        '<a href="%s" class="%s" target="%s" rel="noopener noreferrer">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" style="vertical-align: middle; margin-right: 4px;">
                <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
            </svg>
            %s
        </a>',
        htmlspecialchars($share_url),
        htmlspecialchars($options['class']),
        htmlspecialchars($options['target']),
        htmlspecialchars($options['text_label'])
    );
}

// CSS for the share button
?>
<style>
.x-share-button {
    display: inline-flex;
    align-items: center;
    padding: 8px 16px;
    background-color: #000;
    color: #fff;
    text-decoration: none;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 500;
    transition: background-color 0.2s;
}

.x-share-button:hover {
    background-color: #1a1a1a;
}

.x-share-mini {
    padding: 4px 12px;
    font-size: 12px;
}

.x-share-large {
    padding: 12px 24px;
    font-size: 16px;
}
</style>