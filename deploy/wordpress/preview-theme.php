<?php
/**
 * Isolated visual preview ONLY. Not deployed into the WordPress public directory.
 * Run: SKYSEND_PREVIEW_THEME=/path/to/theme php -S 127.0.0.1:8085 -t /path/to/theme preview-theme.php
 * Expose only via an SSH localhost tunnel. Real WordPress must be checked separately.
 */
$theme_path = getenv('SKYSEND_PREVIEW_THEME');
if (!$theme_path || !is_dir($theme_path)) {
    http_response_code(500);
    exit('SKYSEND_PREVIEW_THEME must point to an existing theme directory.');
}
$request_path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if (!is_string($request_path) || $request_path === '') {
    http_response_code(404);
    exit('Not found');
}
$legacy_paths = array(
    '/participants/agents',
    '/participants/providers',
    '/participants/suppliers',
    '/participants/retailers',
    '/participants/representatives',
    '/participants/gateways',
    '/participants/advertisers',
);
foreach ($legacy_paths as $legacy_path) {
    if ($request_path === $legacy_path || $request_path === $legacy_path . '/') {
        header('Location: /#participants', true, 301);
        exit;
    }
}
if (str_starts_with($request_path, '/assets/')) {
    $assets_path = realpath($theme_path . '/assets');
    $asset_path = realpath($theme_path . $request_path);
    if ($assets_path && $asset_path && is_file($asset_path) && str_starts_with($asset_path, $assets_path . DIRECTORY_SEPARATOR)) {
        return false;
    }
}
if ($request_path !== '/') {
    http_response_code(404);
    exit('Not found');
}
define('ABSPATH', __DIR__ . '/');
function add_action(...$args) {}
function add_filter(...$args) {}
function esc_html($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
function esc_attr($value) { return esc_html($value); }
function esc_url($value) { return esc_html($value); }
function get_theme_mod($key, $default = '') { return $default; }
function home_url($path = '/') { return $path; }
function trailingslashit($value) { return rtrim($value, '/') . '/'; }
function get_theme_file_uri($path) { return $path; }
function get_theme_file_path($path) { return $GLOBALS['theme_path'] . $path; }
function is_front_page() { return $GLOBALS['request_path'] === '/'; }
function language_attributes() { echo 'lang="ru"'; }
function bloginfo($key) { if ($key === 'charset') { echo 'UTF-8'; } }
function body_class() { echo 'class="home"'; }
function wp_body_open() {}
function wp_date($format) { return date($format); }
function wp_head() { echo '<title>SkySend — preview</title><link rel="stylesheet" href="/assets/css/site.css"><link rel="stylesheet" href="/assets/css/landing.css">'; }
function wp_footer() { echo '<script src="/assets/js/site.js" defer></script>'; }
function get_header() { include $GLOBALS['theme_path'] . '/header.php'; }
function get_footer() { include $GLOBALS['theme_path'] . '/footer.php'; }
require $theme_path . '/functions.php';
require $theme_path . '/front-page.php';
