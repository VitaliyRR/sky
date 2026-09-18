<?php
/** Checks the restored private clone with its standard theme/plugins loaded. */
if (!defined('WP_CLI') || !WP_CLI) { exit(1); }
global $wpdb;
if (!preg_match('/^skysend_pkg_[0-9]{8}_[0-9]{6}$/', DB_NAME) || DB_USER !== DB_NAME) { WP_CLI::error('Not an isolated clone'); }
$checks = [];
$check = static function($name, $passed) use (&$checks): void { $checks[$name] = (bool) $passed; };
$check('Front page 67', get_option('show_on_front') === 'page' && (int) get_option('page_on_front') === 67 && get_post_status(67) === 'publish');
$check('Administrator available', (bool) get_user_by('login', 'skysend_admin'));
$check('Sessions and app passwords cleared', !(int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key IN ('session_tokens','_application_passwords')"));
$check('Reset keys cleared', !(int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users} WHERE user_activation_key<>''"));
$check('No custom CSS/MU plugins', wp_get_custom_css() === '' && count(wp_get_mu_plugins()) === 0);
$blocks = parse_blocks(get_post_field('post_content', 67));
$queue = $blocks; $count = 0; $unregistered = []; $custom = [];
while ($queue) {
    $block = array_pop($queue); $name = $block['blockName'];
    if ($name) {
        $count++;
        if (!WP_Block_Type_Registry::get_instance()->is_registered($name)) { $unregistered[] = $name; }
        if (in_array($name, ['core/html','core/freeform','core/shortcode','core/code'], true)) { $custom[] = $name; }
    }
    array_push($queue, ...$block['innerBlocks']);
}
$check('All blocks registered', !$unregistered);
$check('No custom code blocks', !$custom);
$carousel = $blocks[0]['innerBlocks'][0] ?? [];
$check('Three autoplay banners', ($carousel['blockName'] ?? '') === 'cb/carousel-v2' && count($carousel['innerBlocks']) === 3 && ($carousel['attrs']['autoplay'] ?? false) && ($carousel['attrs']['autoplaySpeed'] ?? 0) === 6000);
$check('Banner min-height 600', ($blocks[0]['attrs']['style']['dimensions']['minHeight'] ?? '') === '600px');
$check('Native tabs', WP_Block_Type_Registry::get_instance()->is_registered('core/tabs'));
$check('Core sitemap enabled', wp_sitemaps_get_server()->sitemaps_enabled());
$check('Native sitemap not replaced by SEO plugin', (get_option('seopress_toggle')['toggle-xml-sitemap'] ?? '') === '0');
$attachmentCount = 0; $missing = [];
foreach (get_posts(['post_type'=>'attachment', 'post_status'=>'inherit', 'posts_per_page'=>-1]) as $attachment) {
    $attachmentCount++;
    if (!is_file(get_attached_file($attachment->ID))) { $missing[] = $attachment->ID; }
}
$check('All original attachments available', !$missing);
$result = ['blockCount' => $count, 'attachmentCount' => $attachmentCount, 'checks' => $checks];
echo wp_json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;
if (in_array(false, $checks, true)) { WP_CLI::error('Clone verification failed'); }
