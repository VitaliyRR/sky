<?php
/** One-time SEOPress setup through WordPress options; never loaded by the website. */
if (!defined('WP_CLI') || !WP_CLI) { exit(1); }

if (get_option('skysend_seo_setup_complete')) {
    WP_CLI::error('SEO was already configured. Use the WordPress SEO settings; do not overwrite editor changes.');
}
if (!is_plugin_active('wp-seopress/seopress.php') || !defined('SEOPRESS_VERSION') || SEOPRESS_VERSION !== '10.2') {
    WP_CLI::error('Verified SEOPress 10.2 must be installed and active first.');
}
if (version_compare(get_bloginfo('version'), '7.1.1', '<') || get_stylesheet() !== 'twentytwentyfive') {
    WP_CLI::error('Update the standard WordPress installation to at least 7.1.1 first.');
}
$profile = json_decode(file_get_contents($args[0] ?? __DIR__.'/seo-settings.json'), true, 512, JSON_THROW_ON_ERROR);
$pageId = (int) $profile['pageId'];
if ($pageId !== 67 || (int) get_option('page_on_front') !== $pageId || get_option('show_on_front') !== 'page' || get_post_status($pageId) !== 'publish') {
    WP_CLI::error('Unexpected front page.');
}
foreach (['_seopress_titles_title', '_seopress_titles_desc', '_seopress_robots_canonical', '_seopress_robots_index'] as $key) {
    if (get_post_meta($pageId, $key, true) !== '') {
        WP_CLI::error('Existing page SEO settings found; review them in the editor before any migration.');
    }
}
$attachment = static function(int $id, string $filename): string {
    $file = get_post_meta($id, '_wp_attached_file', true);
    $url = wp_get_attachment_url($id);
    if (!wp_attachment_is_image($id) || basename($file) !== $filename || !$url || !is_file(get_attached_file($id))) {
        WP_CLI::error('Unexpected or missing media attachment: '.$id);
    }
    return $url;
};
$image = $attachment((int) $profile['socialImageId'], $profile['socialImageFile']);
$org = $profile['organization'];
$logo = $attachment((int) $org['logoId'], $org['logoFile']);
$titles = get_option('seopress_titles_option_name', []);
$social = get_option('seopress_social_option_name', []);
$sitemaps = get_option('seopress_xml_sitemap_option_name', []);
$toggles = get_option('seopress_toggle', []);
if (!is_array($titles) || !is_array($social) || !is_array($sitemaps) || !is_array($toggles)) {
    WP_CLI::error('Unexpected SEOPress option format.');
}
foreach (['seopress_titles_home_site_title' => '%%sitetitle%%', 'seopress_titles_home_site_desc' => '%%tagline%%'] as $key => $default) {
    if (($titles[$key] ?? $default) !== $default) {
        WP_CLI::error('Homepage SEO settings have already been edited; configure through the UI instead.');
    }
}
$hashes = [];
foreach ([67, 68, 69, 66, 70] as $id) { $hashes[$id] = hash('sha256', get_post_field('post_content', $id)); }

// Keep unrelated settings. Only title/social modules are needed for this landing.
$titles['seopress_titles_home_site_title'] = $profile['title'];
$titles['seopress_titles_home_site_desc'] = $profile['description'];
$social = array_replace($social, [
    'seopress_social_facebook_og' => '1',
    'seopress_social_facebook_img' => $image,
    'seopress_social_facebook_img_attachment_id' => (string) $profile['socialImageId'],
    'seopress_social_facebook_img_default' => '1',
    'seopress_social_twitter_card' => '1',
    'seopress_social_twitter_card_og' => '1',
    'seopress_social_twitter_card_img' => $image,
    'seopress_social_twitter_card_img_size' => 'large',
    'seopress_social_knowledge_type' => 'Organization',
    'seopress_social_knowledge_name' => $org['name'],
    'seopress_social_knowledge_img' => $logo,
    'seopress_social_knowledge_desc' => $profile['description'],
    'seopress_social_knowledge_email' => $org['email'],
    'seopress_social_knowledge_phone' => $org['phone'],
    'seopress_social_knowledge_contact_type' => $org['contactType'],
]);
foreach ($org['socialAccounts'] as $network => $url) { $social['seopress_social_accounts_'.$network] = $url; }
// No office address, legal/tax identifiers, unconfirmed numbers or new claims.
// Disabling just general_enable is NOT enough: the module toggle suppresses core sitemaps.
$toggles['toggle-titles'] = '1';
$toggles['toggle-social'] = '1';
foreach (['xml-sitemap', 'google-analytics', 'instant-indexing', 'advanced', 'dublin-core', 'local-business', 'rich-snippets', 'breadcrumbs', 'robots', 'llms', '404', 'bot', 'inspect-url', 'ai'] as $module) {
    $toggles['toggle-'.$module] = '0';
}
$sitemaps['seopress_xml_sitemap_general_enable'] = '0';
update_option('seopress_titles_option_name', $titles);
update_option('seopress_social_option_name', $social);
update_option('seopress_xml_sitemap_option_name', $sitemaps);
update_option('seopress_toggle', $toggles);

foreach ($hashes as $id => $hash) {
    if (hash('sha256', get_post_field('post_content', $id)) !== $hash) { WP_CLI::error('Editor content changed during setup: '.$id); }
}
update_option('skysend_seo_setup_complete', ['date' => '2026-09-18', 'plugin' => SEOPRESS_VERSION], false);
WP_CLI::success('SEO configured. Page, header, footer, template and styles were not changed.');
WP_CLI::log('Next: run wp rewrite flush in a separate WP-CLI process to register the restored core sitemap routes (without --hard).');
