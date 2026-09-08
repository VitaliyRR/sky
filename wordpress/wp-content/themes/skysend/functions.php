<?php
/**
 * SkySend theme setup and shared helpers.
 *
 * @package SkySend
 */

if (!defined('ABSPATH')) {
    exit;
}

function skysend_setup(): void
{
    add_theme_support('title-tag');
    add_theme_support('html5', array('search-form', 'gallery', 'caption', 'style', 'script'));
    add_theme_support('responsive-embeds');
    add_theme_support('custom-logo', array('height' => 100, 'width' => 158, 'flex-height' => true, 'flex-width' => true));
}
add_action('after_setup_theme', 'skysend_setup');

function skysend_assets(): void
{
    $version = wp_get_theme()->get('Version');
    $css_path = get_theme_file_path('/assets/css/site.css');
    $js_path = get_theme_file_path('/assets/js/site.js');

    wp_enqueue_style(
        'skysend-site',
        get_theme_file_uri('/assets/css/site.css'),
        array(),
        is_file($css_path) ? (string) filemtime($css_path) : $version
    );
    wp_enqueue_script(
        'skysend-site',
        get_theme_file_uri('/assets/js/site.js'),
        array(),
        is_file($js_path) ? (string) filemtime($js_path) : $version,
        true
    );
    wp_script_add_data('skysend-site', 'strategy', 'defer');
}
add_action('wp_enqueue_scripts', 'skysend_assets');

function skysend_clean_head(): void
{
    remove_action('wp_head', 'wp_generator');
    remove_action('wp_head', 'wlwmanifest_link');
    remove_action('wp_head', 'rsd_link');
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('wp_print_styles', 'print_emoji_styles');
}
add_action('init', 'skysend_clean_head');
add_filter('the_generator', '__return_empty_string');

function skysend_theme_customizer(WP_Customize_Manager $customizer): void
{
    $customizer->add_section('skysend_contacts', array(
        'title' => __('Контакты SkySend', 'skysend'),
        'priority' => 30,
    ));

    $customizer->add_setting('skysend_phone', array(
        'default' => '+7 (861) 201-12-21',
        'sanitize_callback' => 'sanitize_text_field',
    ));
    $customizer->add_control('skysend_phone', array(
        'label' => __('Телефон', 'skysend'),
        'section' => 'skysend_contacts',
        'type' => 'text',
    ));

    $customizer->add_setting('skysend_telegram', array(
        'default' => 'infsysgroup',
        'sanitize_callback' => 'sanitize_text_field',
    ));
    $customizer->add_control('skysend_telegram', array(
        'label' => __('Telegram без символа @', 'skysend'),
        'section' => 'skysend_contacts',
        'type' => 'text',
    ));
}
add_action('customize_register', 'skysend_theme_customizer');

function skysend_phone(): string
{
    return (string) get_theme_mod('skysend_phone', '+7 (861) 201-12-21');
}

function skysend_phone_href(): string
{
    return 'tel:+' . preg_replace('/\D+/', '', skysend_phone());
}

function skysend_telegram(): string
{
    return ltrim((string) get_theme_mod('skysend_telegram', 'infsysgroup'), '@');
}

function skysend_icon(string $name): string
{
    $icons = array(
        'terminal' => '<rect x="5" y="3" width="14" height="18" rx="2"/><path d="M8 7h8M8 11h5M9 17h6"/>',
        'network' => '<rect x="9" y="3" width="6" height="5" rx="1"/><rect x="3" y="16" width="6" height="5" rx="1"/><rect x="15" y="16" width="6" height="5" rx="1"/><path d="M12 8v4M6 16v-2h12v2"/>',
        'box' => '<path d="m4 8 8-4 8 4-8 4-8-4Z"/><path d="m4 8v8l8 4 8-4V8M12 12v8"/>',
        'megaphone' => '<path d="m3 11 14-6v14L3 13v-2Z"/><path d="M7 14v5h4l1-3"/>',
        'pin' => '<path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2"/>',
        'gateway' => '<path d="M4 5h16v14H4zM8 9h8M8 13h5"/><path d="m15 16 2-2-2-2"/>',
        'percent' => '<path d="m6 18 12-12"/><circle cx="7" cy="7" r="2"/><circle cx="17" cy="17" r="2"/>',
        'cost' => '<path d="M5 7h14v11H5zM8 11h8M8 14h5"/><path d="M8 4h8"/>',
        'pulse' => '<path d="M3 12h4l2-5 4 10 2-5h6"/>',
        'spark' => '<path d="M12 3v4M12 17v4M3 12h4M17 12h4"/><path d="m5.6 5.6 2.8 2.8m7.2 7.2 2.8 2.8m0-12.8-2.8 2.8m-7.2 7.2-2.8 2.8"/>',
        'speed' => '<path d="M4 17a8 8 0 1 1 16 0"/><path d="m12 13 4-4"/><path d="M7 17h10"/>',
        'shield' => '<path d="M12 3 5 6v5c0 5 3 8 7 10 4-2 7-5 7-10V6l-7-3Z"/><path d="m9 12 2 2 4-4"/>',
        'windows' => '<path d="M4 5.5 11 4v7H4V5.5ZM13 3.7l7-1.2V11h-7V3.7ZM4 13h7v7l-7-1.2V13ZM13 13h7v8.5L13 20.3V13Z"/>',
        'android' => '<path d="M6 10h12v9H6zM8 10a4 4 0 0 1 8 0M8 6 6.5 4M16 6l1.5-2"/><path d="M4 11v6M20 11v6M9 19v2M15 19v2"/>',
        'code' => '<path d="m8 8-4 4 4 4M16 8l4 4-4 4M14 5l-4 14"/>',
        'phone' => '<path d="M8 3H5a2 2 0 0 0-2 2c0 8.8 7.2 16 16 16a2 2 0 0 0 2-2v-3l-4-1-2 2c-3.5-1.5-6.5-4.5-8-8l2-2-1-4Z"/>',
        'telegram' => '<path d="m3 11 18-8-6 18-4-7-8-3Z"/><path d="m11 14 4-4"/>',
        'arrow' => '<path d="M5 12h14M14 7l5 5-5 5"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
    );

    if (!isset($icons[$name])) {
        return '';
    }

    return '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">' . $icons[$name] . '</svg>';
}

function skysend_meta_tags(): void
{
    if (!is_front_page()) {
        return;
    }

    $title = 'SkySend — система приёма платежей для бизнеса';
    $description = 'SkySend объединяет платёжные терминалы, точки оплаты и более 5 000 поставщиков услуг. ПО ALLVEND, выгодные условия и готовые интеграции.';
    $canonical = home_url('/');
    $phone = skysend_phone();

    echo '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
    echo '<link rel="canonical" href="' . esc_url($canonical) . '">' . "\n";
    echo '<meta property="og:locale" content="ru_RU">' . "\n";
    echo '<meta property="og:type" content="website">' . "\n";
    echo '<meta property="og:title" content="' . esc_attr($title) . '">' . "\n";
    echo '<meta property="og:description" content="' . esc_attr($description) . '">' . "\n";
    echo '<meta property="og:url" content="' . esc_url($canonical) . '">' . "\n";
    echo '<meta property="og:site_name" content="SkySend">' . "\n";
    echo '<meta name="twitter:card" content="summary">' . "\n";
    echo '<link rel="icon" href="' . esc_url(get_theme_file_uri('/assets/images/favicon.png')) . '" sizes="16x16">' . "\n";

    $schema = array(
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => 'SkySend',
        'url' => $canonical,
        'description' => $description,
        'address' => array(
            '@type' => 'PostalAddress',
            'postalCode' => '350049',
            'addressLocality' => 'Краснодар',
            'streetAddress' => 'ул. Монтажников, д. 1/4',
            'addressCountry' => 'RU',
        ),
        'contactPoint' => array(
            '@type' => 'ContactPoint',
            'telephone' => $phone,
            'contactType' => 'customer support',
            'availableLanguage' => 'Russian',
        ),
    );

    echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>' . "\n";
}
add_action('wp_head', 'skysend_meta_tags', 2);

function skysend_document_title(array $parts): array
{
    if (is_front_page()) {
        $parts['title'] = 'SkySend — система приёма платежей для бизнеса';
        unset($parts['tagline']);
    }
    return $parts;
}
add_filter('document_title_parts', 'skysend_document_title');

function skysend_robots_meta(array $robots): array
{
    if (get_option('blog_public')) {
        $robots['index'] = true;
        $robots['follow'] = true;
        $robots['max-image-preview'] = 'large';
    }
    return $robots;
}
add_filter('wp_robots', 'skysend_robots_meta');
