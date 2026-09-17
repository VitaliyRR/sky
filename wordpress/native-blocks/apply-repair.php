<?php
/** One-time CLI application of checked native settings; never loaded by the site. */
if (!defined('WP_CLI') || !WP_CLI) { exit(1); }
$directory = $args[0] ?? '';
$manifest = json_decode(file_get_contents($directory.'/manifest.json'), true);
if (!$manifest || get_stylesheet() !== 'twentytwentyfive') { WP_CLI::error('Missing manifest or unexpected theme'); }
$allowed = ['page'=>67, 'header'=>68, 'footer'=>69, 'styles'=>66];
global $wpdb;
$engine = $wpdb->get_var($wpdb->prepare('SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=%s', $wpdb->posts));
if (strtolower((string)$engine) !== 'innodb') { WP_CLI::error('Transactional post storage is required'); }
$wpdb->query('START TRANSACTION');
$fail = static function(string $message) use ($wpdb): void { $wpdb->query('ROLLBACK'); WP_CLI::error($message); };
// Lock all exact targets before comparing: a save cannot race the native update.
foreach ($manifest as $item) {
    if (($allowed[$item['name']] ?? 0) !== $item['id']) { $fail('Unexpected repair target'); }
    $post = $wpdb->get_row($wpdb->prepare("SELECT ID,post_type,post_content FROM {$wpdb->posts} WHERE ID=%d FOR UPDATE", $item['id']));
    if (!$post || $post->post_type !== $item['type'] || hash('sha256', $post->post_content) !== $item['beforeHash']) { $fail('Editor changes detected: '.$item['name']); }
    clean_post_cache($item['id']);
    $file = $directory.'/'.$item['name'].($item['name']==='styles'?'.json':'.html');
    $text = file_get_contents($file);
    if (hash('sha256', $text) !== $item['afterHash']) { $fail('Repair file checksum mismatch'); }
    if ($item['name'] !== 'styles') {
        $queue = parse_blocks($text);
        while ($queue) {
            $block = array_pop($queue);
            if (!$block['blockName']) { if (trim($block['innerHTML'])) { $fail('Unstructured HTML'); } continue; }
            if (!str_starts_with($block['blockName'], 'core/') || in_array($block['blockName'], ['core/html','core/freeform','core/code','core/shortcode'], true) || !WP_Block_Type_Registry::get_instance()->is_registered($block['blockName'])) { $fail('Not a native block'); }
            array_push($queue, ...$block['innerBlocks']);
        }
    } elseif (!is_array(json_decode($text,true))) { $fail('Invalid global settings'); }
}
foreach ($manifest as $item) {
    $file = $directory.'/'.$item['name'].($item['name']==='styles'?'.json':'.html');
    $result = wp_update_post(wp_slash(['ID'=>$item['id'], 'post_content'=>file_get_contents($file)]), true);
    if (is_wp_error($result)) { $fail($result->get_error_message()); }
    if (hash('sha256',get_post_field('post_content',$item['id'])) !== $item['afterHash']) { $fail('Stored content mismatch'); }
    WP_CLI::success('Updated native '.$item['name'].' #'.$item['id']);
}
$wpdb->query('COMMIT');
