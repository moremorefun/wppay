<?php
/**
 * Fix URL detection behind Cloudflare Tunnel proxy.
 *
 * Dynamically sets home/siteurl based on request origin:
 * - Cloudflare Tunnel: https://macwp.dayelaiwan.com
 * - Local: http://localhost:8888
 */

// Detect if request is coming through Cloudflare
$is_cloudflare = isset($_SERVER['HTTP_CF_CONNECTING_IP']) ||
    (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'dayelaiwan.com') !== false);

if ($is_cloudflare) {
    // 必须在 WordPress 读取之前设置这些
    $_SERVER['HTTPS'] = 'on';
    $_SERVER['SERVER_PORT'] = 443;
    $_SERVER['HTTP_HOST'] = 'macwp.dayelaiwan.com';
    $_SERVER['SERVER_NAME'] = 'macwp.dayelaiwan.com';

    // 使用常量定义（最高优先级，但需在 wp-settings.php 加载前）
    // 由于 mu-plugins 加载较晚，只能用 filter
}

// Use filters to override URLs (works even if constants are defined)
add_filter('option_home', 'ptf_dynamic_url');
add_filter('option_siteurl', 'ptf_dynamic_url');
add_filter('home_url', 'ptf_fix_url', 1, 4);
add_filter('site_url', 'ptf_fix_url', 1, 4);
add_filter('plugins_url', 'ptf_fix_plugins_url', 1, 3);
add_filter('content_url', 'ptf_fix_content_url', 1, 2);

function ptf_dynamic_url($url) {
    $is_cloudflare = isset($_SERVER['HTTP_CF_CONNECTING_IP']) ||
        (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'dayelaiwan.com') !== false);

    if ($is_cloudflare) {
        return 'https://macwp.dayelaiwan.com';
    }
    return 'http://localhost:8888';
}

function ptf_fix_url($url, $path = '', $scheme = null, $blog_id = null) {
    return ptf_replace_localhost($url);
}

function ptf_fix_plugins_url($url, $path = '', $plugin = '') {
    return ptf_replace_localhost($url);
}

function ptf_fix_content_url($url, $path = '') {
    return ptf_replace_localhost($url);
}

function ptf_replace_localhost($url) {
    $is_cloudflare = isset($_SERVER['HTTP_CF_CONNECTING_IP']) ||
        (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'dayelaiwan.com') !== false);

    if ($is_cloudflare) {
        $url = str_replace('http://localhost:8888', 'https://macwp.dayelaiwan.com', $url);
        $url = str_replace('https://localhost:8888', 'https://macwp.dayelaiwan.com', $url);
    }
    return $url;
}
