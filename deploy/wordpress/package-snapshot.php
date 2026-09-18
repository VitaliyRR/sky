<?php
/** Read-only snapshot for a one-time package build, not website runtime. */
if (!defined('WP_CLI') || !WP_CLI) { exit(1); }
global $wpdb;
require_once ABSPATH.'wp-admin/includes/plugin.php';
$tables = [];
foreach (['posts', 'postmeta', 'terms', 'termmeta', 'term_taxonomy', 'term_relationships', 'comments', 'commentmeta'] as $suffix) {
    $table = $wpdb->prefix.$suffix;
    $rows = $wpdb->get_results("SELECT * FROM `$table`", ARRAY_A);
    // A deterministic order independent of physical row ordering.
    $encoded = array_map(static fn($row) => wp_json_encode($row), $rows);
    sort($encoded, SORT_STRING);
    $tables[$suffix] = ['rows' => count($rows), 'sha256' => hash('sha256', implode("\n", $encoded))];
}
$critical = [];
foreach ($wpdb->get_col("SELECT option_name FROM {$wpdb->options} ORDER BY option_name") as $name) {
    if (in_array($name, ['home','siteurl','show_on_front','page_on_front','page_for_posts','stylesheet','template','permalink_structure','blog_public','blogname','blogdescription','timezone_string','WPLANG','active_plugins','site_icon','sidebars_widgets','nav_menu_options'], true)
        || str_starts_with($name, 'theme_mods_') || str_starts_with($name, 'skysend_')
        || str_starts_with($name, 'seopress_titles_') || str_starts_with($name, 'seopress_social_')
        || $name === 'seopress_toggle' || $name === 'seopress_xml_sitemap_option_name'
        || $name === $wpdb->prefix.'user_roles') {
        $critical[$name] = get_option($name);
    }
}
$users = $wpdb->get_results("SELECT * FROM {$wpdb->users} ORDER BY ID", ARRAY_A);
$authHash = hash('sha256', wp_json_encode($users));
foreach ($users as &$row) { unset($row['user_pass'], $row['user_activation_key']); }
unset($row);
$plugins = [];
foreach (get_option('active_plugins', []) as $plugin) {
    $plugins[$plugin] = get_plugin_data(WP_PLUGIN_DIR.'/'.$plugin, false, false)['Version'];
}
$content = [];
foreach ([67,68,69,66,70] as $id) { $content[$id] = hash('sha256', get_post_field('post_content', $id)); }
echo wp_json_encode([
    'wordpress' => get_bloginfo('version'), 'locale' => get_locale(),
    'theme' => get_stylesheet(), 'themeVersion' => wp_get_theme()->get('Version'),
    'plugins' => $plugins, 'prefix' => $wpdb->prefix,
    'sourceUrl' => get_option('home'), 'databaseVersion' => $wpdb->get_var('SELECT VERSION()'),
    'tables' => $tables, 'contentHashes' => $content,
    'criticalOptionsHash' => hash('sha256', wp_json_encode($critical)),
    'userCount' => count($users), 'userIdentityHash' => hash('sha256', wp_json_encode($users)),
    // Only used privately to prove the source users have not been changed.
    'privateUserAuthHash' => $authHash,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL;
