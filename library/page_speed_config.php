<?php
/**
 * Page Speed Optimization Configuration
 * 
 * This file contains functions and configurations to improve
 * page loading speed and Core Web Vitals scores.
 */

declare(strict_types=1);

/**
 * Get critical CSS for above-the-fold content
 * 
 * @param string $page_type Type of page
 * @return string Critical CSS
 */
function getCriticalCSS(string $page_type = 'default'): string {
    // 共通の重要なCSS
    $common_css = '
    <style>
        /* Critical CSS for above-the-fold content */
        body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        #header { background: #1a4d3e; color: white; padding: 1rem; }
        #container { max-width: 1200px; margin: 0 auto; }
        .book-cover { width: 120px; height: 180px; object-fit: cover; }
        .loading { opacity: 0.5; }
        
        /* Prevent layout shift */
        img { max-width: 100%; height: auto; }
        img[width][height] { height: auto; }
        
        /* Font loading optimization */
        .fonts-loaded body { font-family: "Noto Sans JP", sans-serif; }
    </style>
    ';
    
    // ページタイプ別の追加CSS
    $page_specific_css = [
        'index' => '
            .hero-section { min-height: 400px; }
            .book-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 1rem; }
        ',
        'book_detail' => '
            .book-detail-header { display: flex; gap: 2rem; margin-bottom: 2rem; }
            .book-info { flex: 1; }
            .review-section { margin-top: 3rem; }
        ',
        'profile' => '
            .profile-header { display: flex; align-items: center; gap: 1rem; margin-bottom: 2rem; }
            .user-photo { width: 100px; height: 100px; border-radius: 50%; }
        ',
        'bookshelf' => '
            .bookshelf-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1.5rem; }
            .book-item { background: #f5f5f5; padding: 1rem; border-radius: 8px; }
        '
    ];
    
    $specific_css = $page_specific_css[$page_type] ?? '';
    
    return $common_css . ($specific_css ? '<style>' . $specific_css . '</style>' : '');
}

/**
 * Generate resource hints for faster loading
 * 
 * @param array $resources Array of resources with their hint types
 * @return string HTML link tags
 */
function generateResourceHints(array $resources): string {
    $hints = [];
    
    foreach ($resources as $url => $type) {
        switch ($type) {
            case 'preconnect':
                $hints[] = sprintf('<link rel="preconnect" href="%s">', htmlspecialchars($url, ENT_QUOTES, 'UTF-8'));
                $hints[] = sprintf('<link rel="preconnect" href="%s" crossorigin>', htmlspecialchars($url, ENT_QUOTES, 'UTF-8'));
                break;
                
            case 'dns-prefetch':
                $hints[] = sprintf('<link rel="dns-prefetch" href="%s">', htmlspecialchars($url, ENT_QUOTES, 'UTF-8'));
                break;
                
            case 'prefetch':
                $hints[] = sprintf('<link rel="prefetch" href="%s">', htmlspecialchars($url, ENT_QUOTES, 'UTF-8'));
                break;
                
            case 'prerender':
                $hints[] = sprintf('<link rel="prerender" href="%s">', htmlspecialchars($url, ENT_QUOTES, 'UTF-8'));
                break;
        }
    }
    
    return implode("\n", $hints);
}

/**
 * Generate preload tags for critical resources
 * 
 * @param array $resources Array of resources to preload
 * @return string HTML link tags
 */
function generatePreloadTags(array $resources): string {
    $tags = [];
    
    foreach ($resources as $resource) {
        $tag = '<link rel="preload"';
        
        if (isset($resource['href'])) {
            $tag .= sprintf(' href="%s"', htmlspecialchars($resource['href'], ENT_QUOTES, 'UTF-8'));
        }
        
        if (isset($resource['as'])) {
            $tag .= sprintf(' as="%s"', htmlspecialchars($resource['as'], ENT_QUOTES, 'UTF-8'));
        }
        
        if (isset($resource['type'])) {
            $tag .= sprintf(' type="%s"', htmlspecialchars($resource['type'], ENT_QUOTES, 'UTF-8'));
        }
        
        if (isset($resource['crossorigin'])) {
            $tag .= ' crossorigin';
        }
        
        $tags[] = $tag . '>';
    }
    
    return implode("\n", $tags);
}

/**
 * Defer JavaScript loading
 * 
 * @param string $src Script source URL
 * @param array $attributes Additional attributes
 * @return string Script tag with defer
 */
function deferScript(string $src, array $attributes = []): string {
    $script_attributes = array_merge(['defer' => true], $attributes);
    
    $tag = '<script src="' . htmlspecialchars($src, ENT_QUOTES, 'UTF-8') . '"';
    
    foreach ($script_attributes as $key => $value) {
        if ($value === true) {
            $tag .= ' ' . $key;
        } else {
            $tag .= sprintf(' %s="%s"', $key, htmlspecialchars($value, ENT_QUOTES, 'UTF-8'));
        }
    }
    
    $tag .= '></script>';
    
    return $tag;
}

/**
 * Generate inline script with performance optimization
 * 
 * @param string $script JavaScript code
 * @param bool $defer Whether to defer execution
 * @return string Script tag
 */
function inlineScript(string $script, bool $defer = false): string {
    if ($defer) {
        return sprintf(
            '<script>
                if (document.readyState === "loading") {
                    document.addEventListener("DOMContentLoaded", function() {
                        %s
                    });
                } else {
                    %s
                }
            </script>',
            $script,
            $script
        );
    }
    
    return sprintf('<script>%s</script>', $script);
}

/**
 * Optimize CSS delivery
 * 
 * @param string $href CSS file URL
 * @param bool $critical Whether this is critical CSS
 * @return string Link tag
 */
function optimizedCSS(string $href, bool $critical = false): string {
    if ($critical) {
        // Critical CSS should be loaded normally
        return sprintf(
            '<link rel="stylesheet" href="%s">',
            htmlspecialchars($href, ENT_QUOTES, 'UTF-8')
        );
    }
    
    // Non-critical CSS should be loaded asynchronously
    return sprintf(
        '<link rel="preload" href="%s" as="style" onload="this.onload=null;this.rel=\'stylesheet\'">
        <noscript><link rel="stylesheet" href="%s"></noscript>',
        htmlspecialchars($href, ENT_QUOTES, 'UTF-8'),
        htmlspecialchars($href, ENT_QUOTES, 'UTF-8')
    );
}

/**
 * Get optimized font loading CSS
 * 
 * @return string Font loading CSS
 */
function getFontLoadingCSS(): string {
    return '
    <style>
        /* Font loading optimization */
        @font-face {
            font-family: "Noto Sans JP";
            src: url("/fonts/NotoSansJP-Regular.woff2") format("woff2");
            font-weight: 400;
            font-display: swap;
        }
        
        @font-face {
            font-family: "Noto Sans JP";
            src: url("/fonts/NotoSansJP-Bold.woff2") format("woff2");
            font-weight: 700;
            font-display: swap;
        }
    </style>
    ';
}

/**
 * Generate performance monitoring script
 * 
 * @return string Performance monitoring JavaScript
 */
function getPerformanceMonitoringScript(): string {
    return '
    <script>
        // Performance monitoring
        if ("performance" in window && "PerformanceObserver" in window) {
            // Largest Contentful Paint
            new PerformanceObserver((entryList) => {
                for (const entry of entryList.getEntries()) {
                    console.log("LCP:", entry.startTime);
                    // Send to analytics if needed
                    if (typeof gtag !== "undefined") {
                        gtag("event", "page_performance", {
                            event_category: "Web Vitals",
                            event_label: "LCP",
                            value: Math.round(entry.startTime)
                        });
                    }
                }
            }).observe({type: "largest-contentful-paint", buffered: true});
            
            // First Input Delay
            new PerformanceObserver((entryList) => {
                for (const entry of entryList.getEntries()) {
                    const delay = entry.processingStart - entry.startTime;
                    console.log("FID:", delay);
                    // Send to analytics if needed
                    if (typeof gtag !== "undefined") {
                        gtag("event", "page_performance", {
                            event_category: "Web Vitals",
                            event_label: "FID",
                            value: Math.round(delay)
                        });
                    }
                }
            }).observe({type: "first-input", buffered: true});
            
            // Cumulative Layout Shift
            let clsScore = 0;
            new PerformanceObserver((entryList) => {
                for (const entry of entryList.getEntries()) {
                    if (!entry.hadRecentInput) {
                        clsScore += entry.value;
                        console.log("CLS:", clsScore);
                    }
                }
            }).observe({type: "layout-shift", buffered: true});
            
            // Send final CLS on page unload
            addEventListener("visibilitychange", () => {
                if (document.visibilityState === "hidden" && typeof gtag !== "undefined") {
                    gtag("event", "page_performance", {
                        event_category: "Web Vitals",
                        event_label: "CLS",
                        value: Math.round(clsScore * 1000)
                    });
                }
            });
        }
    </script>
    ';
}

/**
 * Generate service worker registration for offline support
 * 
 * @return string Service worker registration script
 */
function getServiceWorkerScript(): string {
    return '
    <script>
        // Service Worker registration for offline support
        if ("serviceWorker" in navigator) {
            window.addEventListener("load", () => {
                navigator.serviceWorker.register("/sw.js")
                    .then(registration => console.log("SW registered:", registration))
                    .catch(error => console.log("SW registration failed:", error));
            });
        }
    </script>
    ';
}