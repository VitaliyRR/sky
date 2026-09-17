<?php
/** One-time WP-CLI migration; never loaded by the website. */
if (!defined('WP_CLI') || !WP_CLI) { exit(1); }
$directory = $args[0] ?? '';
$manifest = json_decode(file_get_contents($directory.'/manifest.json'), true);
if (count($manifest ?? []) !== 1 || !is_plugin_active('carousel-block/plugin.php') || !defined('CB_VERSION') || CB_VERSION !== '2.1.5' || get_stylesheet() !== 'twentytwentyfive') { WP_CLI::error('Unexpected plugin, theme or manifest'); }
$item = $manifest[0];
if ($item['id'] !== 67 || $item['name'] !== 'page' || $item['type'] !== 'page' || $item['pluginVersion'] !== CB_VERSION) { WP_CLI::error('Unexpected target'); }
global $wpdb;
$engine = $wpdb->get_var($wpdb->prepare('SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=%s', $wpdb->posts));
if (strtolower((string)$engine) !== 'innodb') { WP_CLI::error('Transactional storage required'); }
$wpdb->query('START TRANSACTION');
$fail = static function(string $message) use ($wpdb): void { $wpdb->query('ROLLBACK'); WP_CLI::error($message); };
$post = $wpdb->get_row($wpdb->prepare("SELECT ID,post_type,post_content FROM {$wpdb->posts} WHERE ID=%d FOR UPDATE", 67));
if (!$post || $post->post_type !== 'page' || hash('sha256', $post->post_content) !== $item['beforeHash']) { $fail('Editor changes detected: export the current page again'); }
$text = file_get_contents($directory.'/page.html');
if (hash('sha256', $text) !== $item['afterHash']) { $fail('File checksum mismatch'); }
$before = parse_blocks($post->post_content);
$after = parse_blocks($text);
if (($before[0]['attrs']['metadata']['name'] ?? '') !== 'Три баннера' || ($after[0]['attrs']['metadata']['name'] ?? '') !== 'Три баннера') { $fail('Missing banner section'); }
if (serialize_blocks(array_slice($before,1)) !== serialize_blocks(array_slice($after,1))) { $fail('Changes outside banners'); }
$slider = $after[0]['innerBlocks'][0] ?? [];
if (($item['operation'] ?? 'migration') === 'height') {
    $expected = $before[0]['attrs'];
    $expected['style']['dimensions']['minHeight'] = '600px';
    $expected['style']['spacing']['padding']['top'] = '12px';
    $expected['style']['spacing']['padding']['bottom'] = '20px';
    if (($slider['blockName'] ?? '') !== 'cb/carousel-v2' || $expected !== $after[0]['attrs'] || serialize_blocks($before[0]['innerBlocks']) !== serialize_blocks($after[0]['innerBlocks'])) { $fail('Only outer banner height and padding may change'); }
} elseif (($item['operation'] ?? 'migration') === 'migration') {
    $panels = $before[0]['innerBlocks'][0]['innerBlocks'][1]['innerBlocks'] ?? [];
    if (($slider['blockName'] ?? '') !== 'cb/carousel-v2' || count($slider['innerBlocks']) !== 3 || count($panels) !== 3 || ($slider['attrs']['autoplay'] ?? false) !== true || ($slider['attrs']['autoplaySpeed'] ?? 0) !== 6000) { $fail('Unexpected carousel'); }
    foreach ($slider['innerBlocks'] as $index => $slide) {
        if ($slide['blockName'] !== 'cb/slide-v2' || serialize_blocks($slide['innerBlocks']) !== serialize_blocks($panels[$index]['innerBlocks'])) { $fail('Slide content changed'); }
    }
} else {
    $fail('Unexpected operation');
}
$queue = $after;
while ($queue) {
    $block = array_pop($queue);
    $name = $block['blockName'];
    if (!$name) { if (trim($block['innerHTML'])) { $fail('Unstructured HTML'); } continue; }
    if ((!str_starts_with($name,'core/') && !in_array($name,['cb/carousel-v2','cb/slide-v2'],true)) || in_array($name,['core/html','core/freeform','core/code','core/shortcode'],true) || !WP_Block_Type_Registry::get_instance()->is_registered($name)) { $fail('Unexpected or unregistered block'); }
    array_push($queue,...$block['innerBlocks']);
}
clean_post_cache(67);
$result = wp_update_post(wp_slash(['ID'=>67,'post_content'=>$text]),true);
if (is_wp_error($result)) { $fail($result->get_error_message()); }
if (hash('sha256',get_post_field('post_content',67)) !== $item['afterHash']) { $fail('Stored content mismatch'); }
$wpdb->query('COMMIT');
WP_CLI::success('Updated editable banner carousel #67');
