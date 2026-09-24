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
$check('No Customizer CSS/MU plugins', wp_get_custom_css() === '' && count(wp_get_mu_plugins()) === 0);
$check('Page cache plugin installed', in_array('wp-super-cache/wp-cache.php', get_option('active_plugins', []), true));
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
$sections = array_values(array_filter($blocks, static fn($block) => ($block['blockName'] ?? '') === 'core/group'));
$check('Eight landing sections', count($sections) === 8);
$check('Banner min-height 500', ($sections[0]['attrs']['style']['dimensions']['minHeight'] ?? '') === '500px');
$check('Compact partners and six full-height content sections', count($sections) === 8
    && !isset($sections[1]['attrs']['style']['dimensions']['minHeight'])
    && !array_filter(array_slice($sections, 2), static fn($section) => ($section['attrs']['style']['dimensions']['minHeight'] ?? '') !== '600px'));
$pageContent = get_post_field('post_content', 67);
$check('Obsolete archive disclaimer removed', !str_contains($pageContent, 'Архивные материалы: условия и контакты'));
$check('FastSYS 5 approved copy', str_contains($pageContent, 'FastSYS 5 поставляется с ПО ALLVEND как готовое решение в виде ISO образа и обеспечивает стабильную работу устройств на протяжении десятилетий.'));
$check('Native tabs', WP_Block_Type_Registry::get_instance()->is_registered('core/tabs'));
$findBlock = static function(array $items, string $wanted) use (&$findBlock): ?array {
    foreach ($items as $item) {
        if (($item['blockName'] ?? '') === $wanted) { return $item; }
        $found = $findBlock($item['innerBlocks'] ?? [], $wanted);
        if ($found) { return $found; }
    }
    return null;
};
$financeSlide = $carousel['innerBlocks'][1] ?? [];
$financeImage = $findBlock($financeSlide['innerBlocks'] ?? [], 'core/image');
$financeId = (int) ($financeImage['attrs']['id'] ?? 0);
$financeUrl = $financeId ? (string) wp_get_attachment_url($financeId) : '';
$check('Finance income embedded in banner image', $financeId > 0
    && basename((string) parse_url($financeUrl, PHP_URL_PATH)) === 'banner-finance-bag-income-20260924.webp'
    && get_post_type($financeId) === 'attachment'
    && is_file((string) get_attached_file($financeId))
    && str_contains($financeImage['innerHTML'] ?? '', 'Доход +20%')
    && !str_contains($pageContent, '"name":"Доход +20%"'));
$tabs = $findBlock($blocks, 'core/tabs');
$tabList = $tabs['innerBlocks'][0] ?? [];
$tabPanels = $tabs['innerBlocks'][1] ?? [];
$expectedProviders = [
    'Операторы связи' => 13,
    'Интернет-провайдеры' => 14,
    'Телевидение' => 24,
    'Банки и кошельки' => 73,
    'Игры и соцсети' => 39,
    'ЖКХ' => 20,
    'ГИБДД и автоуслуги' => 18,
    'Госуслуги и налоги' => 35,
    'Товары и другие услуги' => 19,
    'Такси и транспорт' => 24,
];
$actualLabels = array_map(static fn($panel) => $panel['attrs']['label'] ?? '', $tabPanels['innerBlocks'] ?? []);
$check('Providers: native list and ten panels', ($tabList['blockName'] ?? '') === 'core/tab-list'
    && ($tabPanels['blockName'] ?? '') === 'core/tab-panels' && $actualLabels === array_keys($expectedProviders));
$actualProviderCounts = []; $providerImageIds = []; $providerStructureValid = true;
foreach ($tabPanels['innerBlocks'] ?? [] as $panel) {
    $label = $panel['attrs']['label'] ?? '';
    $carousel = $panel['innerBlocks'][0] ?? [];
    $slides = $carousel['innerBlocks'] ?? [];
    $expectedCount = $expectedProviders[$label] ?? 0;
    if (($carousel['blockName'] ?? '') !== 'cb/carousel-v2'
        // True is the block's built-in default, so Gutenberg omits these attributes.
        || !($carousel['attrs']['navigation'] ?? true) || !($carousel['attrs']['pagination'] ?? true)
        || count($slides) !== (int) ceil($expectedCount / 9)) { $providerStructureValid = false; }
    $names = [];
    foreach ($slides as $slide) {
        $grid = $slide['innerBlocks'][0] ?? [];
        $cards = $grid['innerBlocks'] ?? [];
        if (($slide['blockName'] ?? '') !== 'cb/slide-v2' || ($grid['blockName'] ?? '') !== 'core/group'
            || !str_contains($grid['attrs']['className'] ?? '', 'sky-provider-grid')
            || count($cards) < 1 || count($cards) > 9) { $providerStructureValid = false; }
        foreach ($cards as $card) {
            $name = trim((string) ($card['attrs']['metadata']['name'] ?? ''));
            $image = $findBlock($card['innerBlocks'] ?? [], 'core/image');
            $id = (int) ($image['attrs']['id'] ?? 0);
            $imageUrl = $id ? (string) wp_get_attachment_url($id) : '';
            if (($card['blockName'] ?? '') !== 'core/group' || $name === '' || $id < 1
                || get_post_type($id) !== 'attachment' || !str_starts_with((string) get_post_mime_type($id), 'image/')
                || $imageUrl === '' || !str_contains($image['innerHTML'] ?? '', $imageUrl)) {
                $providerStructureValid = false;
            }
            $names[] = $name;
            $providerImageIds[] = $id;
        }
    }
    $actualProviderCounts[$label] = count($names);
}
$check('Providers: navigable nine-card slides', $providerStructureValid);
$check('Providers: 279 historical catalogue cards', $actualProviderCounts === $expectedProviders
    // The original catalogue lists different services under the same brand, so
    // reusing that brand's media attachment is valid and avoids duplicate files.
    && count($providerImageIds) === 279
    && !in_array(0, $providerImageIds, true));
$findNamedGroup = static function(array $items, string $wanted) use (&$findNamedGroup): ?array {
    foreach ($items as $item) {
        if (($item['blockName'] ?? '') === 'core/group' && ($item['attrs']['metadata']['name'] ?? '') === $wanted) { return $item; }
        $found = $findNamedGroup($item['innerBlocks'] ?? [], $wanted);
        if ($found) { return $found; }
    }
    return null;
};
$gatewayGroup = $findNamedGroup($blocks, 'Шлюзовикам');
$gatewayImage = $findBlock($gatewayGroup['innerBlocks'] ?? [], 'core/image');
$gatewayId = (int) ($gatewayImage['attrs']['id'] ?? 0);
$gatewayUrl = $gatewayId ? (string) wp_get_attachment_url($gatewayId) : '';
$check('Gateway image includes XML', $gatewayId > 0
    && basename((string) parse_url($gatewayUrl, PHP_URL_PATH)) === 'partner-gateways-xml-20260924.webp'
    && get_post_type($gatewayId) === 'attachment'
    && is_file((string) get_attached_file($gatewayId))
    && str_contains($gatewayImage['innerHTML'] ?? '', 'XML-шлюза')
    && !str_contains($gatewayImage['innerHTML'] ?? '', 'partner-gateways-no-xml-20260924.webp'));
$posGroup = $findNamedGroup($blocks, 'Безналичная оплата');
$gearGroup = $findNamedGroup($blocks, 'Настройка интерфейса');
$posImage = $findBlock($posGroup['innerBlocks'] ?? [], 'core/image');
$gearImage = $findBlock($gearGroup['innerBlocks'] ?? [], 'core/image');
$posId = (int) ($posImage['attrs']['id'] ?? 0);
$gearId = (int) ($gearImage['attrs']['id'] ?? 0);
$check('ALLVEND POS and gear icons replaced', $posId > 0 && $gearId > 0 && $posId !== 113 && $gearId !== 116
    && get_post_type($posId) === 'attachment' && get_post_type($gearId) === 'attachment');
$headerLogo = $findBlock(parse_blocks(get_post_field('post_content', 68)), 'core/image');
$footerLogo = $findBlock(parse_blocks(get_post_field('post_content', 69)), 'core/image');
$headerLogoId = (int) ($headerLogo['attrs']['id'] ?? 0);
$footerLogoId = (int) ($footerLogo['attrs']['id'] ?? 0);
$siteIconId = (int) get_option('site_icon');
$siteIconMeta = $siteIconId ? wp_get_attachment_metadata($siteIconId) : [];
$check('New header and footer logo assets', $headerLogoId > 0 && $footerLogoId > 0
    && $headerLogoId !== 8 && $footerLogoId !== 8 && $headerLogoId !== $footerLogoId
    && is_file((string) get_attached_file($headerLogoId)) && is_file((string) get_attached_file($footerLogoId)));
$check('New square favicon', $siteIconId > 0 && $siteIconId !== 105
    && is_file((string) get_attached_file($siteIconId))
    && ($siteIconMeta['width'] ?? 0) === ($siteIconMeta['height'] ?? -1)
    && ($siteIconMeta['width'] ?? 0) >= 512);
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
