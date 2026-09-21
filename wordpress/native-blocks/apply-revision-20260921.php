<?php
/** Apply the checked customer revision from 21.09.2026; never loaded by the site. */
if (!defined('WP_CLI') || !WP_CLI) { exit(1); }
$directory = $args[0] ?? '';
$manifest = json_decode(file_get_contents($directory.'/manifest.json'), true);
if (count($manifest ?? []) !== 1 || !is_plugin_active('carousel-block/plugin.php') || !defined('CB_VERSION') || CB_VERSION !== '2.1.5' || get_stylesheet() !== 'twentytwentyfive') { WP_CLI::error('Unexpected plugin, theme or manifest'); }
$item = $manifest[0];
if (($item['id'] ?? 0) !== 67 || ($item['name'] ?? '') !== 'page' || ($item['type'] ?? '') !== 'page' || ($item['operation'] ?? '') !== 'customer-revision-20260921' || ($item['pluginVersion'] ?? '') !== CB_VERSION) { WP_CLI::error('Unexpected revision target'); }
global $wpdb;
$engine = $wpdb->get_var($wpdb->prepare('SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=%s', $wpdb->posts));
if (strtolower((string)$engine) !== 'innodb') { WP_CLI::error('Transactional storage required'); }
$wpdb->query('START TRANSACTION');
$fail = static function(string $message) use ($wpdb): void { $wpdb->query('ROLLBACK'); WP_CLI::error($message); };
$post = $wpdb->get_row($wpdb->prepare("SELECT ID,post_type,post_content FROM {$wpdb->posts} WHERE ID=%d FOR UPDATE", 67));
if (!$post || $post->post_type !== 'page' || hash('sha256', $post->post_content) !== $item['beforeHash']) { $fail('Editor changes detected: export the current page and rebuild the revision'); }
$text = file_get_contents($directory.'/page.html');
if (hash('sha256', $text) !== $item['afterHash']) { $fail('Revision file checksum mismatch'); }
$after = parse_blocks($text);
$hero = null;
$partners = null;
foreach ($after as $block) {
    if (($block['attrs']['metadata']['name'] ?? '') === 'Три баннера') { $hero = $block; }
    if (($block['attrs']['anchor'] ?? '') === 'participants') { $partners = $block; }
}
if (!$hero || !$partners || ($hero['attrs']['style']['dimensions']['minHeight'] ?? '') !== '500px') { $fail('Missing revised banner or partner section'); }
$carousel = $hero['innerBlocks'][0] ?? [];
if (($carousel['blockName'] ?? '') !== 'cb/carousel-v2' || count($carousel['innerBlocks'] ?? []) !== 3 || ($carousel['attrs']['autoplay'] ?? false) !== true || ($carousel['attrs']['autoplaySpeed'] ?? 0) !== 6000) { $fail('Unexpected carousel settings'); }
$required = ['ALLVEND — единое ПО для разных типов устройств самообслуживания.','Ресторанам быстрого питания','Автоматизация клиентского обслуживания','Внедрение самообслуживания','20 лет +','Работаем с 2006 года','Поставщиков и партнеров','Оборот системы, Р','Проведено транзакций'];
foreach ($required as $phrase) { if (!str_contains($text, $phrase)) { $fail('Missing required content: '.$phrase); } }
$forbidden = ['Поставщикам товаров','Автоматизация клиентского обслуживания и продажи товаров.','Самообслуживание, заказ товаров и оплата услуг на ALLVEND.','Оборот через разработки, ₽','Проведённых транзакций','partner-gateways-no-xml-20260916'];
foreach ($forbidden as $phrase) { if (str_contains($text, $phrase)) { $fail('Old content remains: '.$phrase); } }
$queue = $after;
while ($queue) {
    $block = array_pop($queue);
    $name = $block['blockName'];
    if (!$name) { if (trim($block['innerHTML'])) { $fail('Unstructured HTML'); } continue; }
    $allowed = str_starts_with($name,'core/') || in_array($name,['cb/carousel-v2','cb/slide-v2'],true);
    if (!$allowed || in_array($name,['core/html','core/freeform','core/code','core/shortcode'],true) || !WP_Block_Type_Registry::get_instance()->is_registered($name)) { $fail('Unexpected or unregistered block: '.$name); }
    array_push($queue,...$block['innerBlocks']);
}
clean_post_cache(67);
$result = wp_update_post(wp_slash(['ID'=>67,'post_content'=>$text]),true);
if (is_wp_error($result)) { $fail($result->get_error_message()); }
if (hash('sha256',get_post_field('post_content',67)) !== $item['afterHash']) { $fail('Stored content mismatch'); }
$wpdb->query('COMMIT');
WP_CLI::success('Applied customer revision 21.09.2026 to editable page #67');
