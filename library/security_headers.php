<?php
/**
 * Security Headers Library
 * 
 * Sets security headers for all pages
 */

/**
 * Set security headers
 * 
 * @param array $options Optional header configuration
 */
if (!function_exists('setSecurityHeaders')) {
    function setSecurityHeaders($options = []) {
    $defaults = [
        'x_frame_options' => 'DENY',
        'x_content_type_options' => 'nosniff',
        'x_xss_protection' => '1; mode=block',
        'referrer_policy' => 'strict-origin-when-cross-origin',
        'permissions_policy' => 'geolocation=(), microphone=(), camera=()',
        'content_security_policy' => null // CSP is complex, allow custom configuration
    ];
    
    $config = array_merge($defaults, $options);
    
    // X-Frame-Options: Prevent clickjacking
    if ($config['x_frame_options']) {
        header('X-Frame-Options: ' . $config['x_frame_options']);
    }
    
    // X-Content-Type-Options: Prevent MIME type sniffing
    if ($config['x_content_type_options']) {
        header('X-Content-Type-Options: ' . $config['x_content_type_options']);
    }
    
    // X-XSS-Protection: Enable XSS filter (legacy browsers)
    if ($config['x_xss_protection']) {
        header('X-XSS-Protection: ' . $config['x_xss_protection']);
    }
    
    // Referrer-Policy: Control referrer information
    if ($config['referrer_policy']) {
        header('Referrer-Policy: ' . $config['referrer_policy']);
    }
    
    // Permissions-Policy: Control browser features
    if ($config['permissions_policy']) {
        header('Permissions-Policy: ' . $config['permissions_policy']);
    }
    
    // Content-Security-Policy: Control resource loading
    if ($config['content_security_policy']) {
        header('Content-Security-Policy: ' . $config['content_security_policy']);
    }
    
    // Additional security considerations for HTTPS
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        // Strict-Transport-Security: Force HTTPS
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

/**
 * Set minimal CSP for basic protection
 */
function setBasicCSP() {
    $csp = [
        "default-src 'self'",
        "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://ajax.googleapis.com",
        "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com",
        "img-src 'self' data: https: http:",
        "font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com",
        "connect-src 'self'",
        "frame-ancestors 'none'"
    ];
    
    header('Content-Security-Policy: ' . implode('; ', $csp));
}