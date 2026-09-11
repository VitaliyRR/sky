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

/**
 * Content shared by the participant cards and their landing pages.
 *
 * @return array<string, array<string, mixed>>
 */
function skysend_participants(): array
{
    return array(
        'agents' => array(
            'icon' => 'terminal',
            'tone' => 'blue',
            'image' => 'partner-agents.jpg',
            'title' => 'Платёжным агентам',
            'card_text' => 'Стабильная работа, снижение расходов, удалённое управление и высокое вознаграждение.',
            'card_metric' => '50%',
            'card_metric_label' => 'снижение расходов',
            'kicker' => 'Платёжным агентам',
            'hero_title' => 'Приём платежей с системой SkySend',
            'visual_title' => 'Основные условия',
            'intro' => 'Система SkySend предоставляет более 5 000 поставщиков услуг, высокое вознаграждение, отсутствие скрытых комиссий и стабильную работу терминалов.',
            'metrics' => array(
                array('value' => '50%', 'label' => 'снижение расходов'),
                array('value' => '5 000+', 'label' => 'поставщиков услуг'),
                array('value' => 'Нет', 'label' => 'скрытых комиссий'),
            ),
            'benefits' => array(
                array('icon' => 'pulse', 'title' => 'Стабильная работа', 'text' => 'FastSYS4 автоматически устраняет ошибки оборудования, анализирует работу устройств и получает удалённые обновления.'),
                array('icon' => 'cost', 'title' => 'Снижение расходов', 'text' => 'Автоматика терминала решает большинство ситуаций без выезда технического специалиста.'),
                array('icon' => 'network', 'title' => 'Удалённое управление', 'text' => 'Управление терминалами и настройками программного обеспечения выполняется удалённо.'),
                array('icon' => 'percent', 'title' => 'Высокое вознаграждение', 'text' => 'Автоматизация и низкие затраты на обслуживание позволяют предоставлять партнёрам повышенное вознаграждение.'),
            ),
            'offer_title' => 'Возможности для платёжных агентов',
            'offer_text' => 'Система SkySend предлагает перевести действующие терминалы, организовать операторские точки и использовать ПО ALLVEND.',
            'offer_list_title' => 'Что доступно агентам',
            'offer_items' => array('Перевести терминалы', 'Операторские точки', 'ПО ALLVEND'),
        ),
        'providers' => array(
            'icon' => 'network',
            'tone' => 'cyan',
            'image' => 'partner-providers.jpg',
            'title' => 'Провайдерам услуг',
            'card_text' => 'Дополнительные точки оплаты услуг в терминальной сети SkySend.',
            'card_metric' => 'Бесплатно',
            'card_metric_label' => 'подключение',
            'kicker' => 'Провайдерам услуг',
            'hero_title' => 'Дополнительные точки оплаты ваших услуг',
            'visual_title' => 'Условия подключения',
            'intro' => 'SkySend предлагает разместить кнопку оплаты услуг на платёжных терминалах системы и увеличить число мест приёма платежей.',
            'metrics' => array(
                array('value' => 'Сеть', 'label' => 'приёма платежей'),
                array('value' => 'Бесплатно', 'label' => 'подключение'),
                array('value' => 'Авто', 'label' => 'отчётность'),
            ),
            'benefits' => array(
                array('icon' => 'network', 'title' => 'Сеть приёма платежей', 'text' => 'Кнопка оплаты услуги размещается на платёжных терминалах системы SkySend.'),
                array('icon' => 'plus', 'title' => 'Бесплатное подключение', 'text' => 'Подключение провайдера услуг к системе SkySend выполняется бесплатно.'),
                array('icon' => 'shield', 'title' => 'Защита данных', 'text' => 'Информация передаётся по защищённым шифрованным каналам с использованием электронной цифровой подписи.'),
                array('icon' => 'speed', 'title' => 'Автоматизация отчётности', 'text' => 'Система автоматизирует учёт и формирование отчётности по принятым платежам.'),
            ),
            'offer_title' => 'Подключение к SkySend',
            'offer_text' => 'Провайдерам доступны сеть приёма платежей, бесплатное подключение и автоматизация отчётности.',
            'offer_list_title' => 'Что доступно провайдерам',
            'offer_items' => array('Кнопка оплаты услуг', 'Защита данных', 'Автоматизация отчётности'),
        ),
        'suppliers' => array(
            'icon' => 'box',
            'tone' => 'violet',
            'image' => 'partner-suppliers.jpg',
            'title' => 'Поставщикам товаров',
            'card_text' => 'Продажа товаров через платёжные терминалы системы SkySend.',
            'card_metric' => 'SkyMarket',
            'card_metric_label' => 'заказ товаров',
            'kicker' => 'Поставщикам товаров',
            'hero_title' => 'Продажа товаров на терминалах SkySend',
            'visual_title' => 'Возможности SkyMarket',
            'intro' => 'Проект SkyMarket позволяет разместить товары с фотографиями и подробным описанием на платёжных терминалах системы SkySend.',
            'metrics' => array(
                array('value' => 'SkyMarket', 'label' => 'торговая площадка'),
                array('value' => 'Каталог', 'label' => 'фото и описание'),
                array('value' => 'XML', 'label' => 'интеграция'),
            ),
            'benefits' => array(
                array('icon' => 'plus', 'title' => 'Бесплатное подключение', 'text' => 'Поставщики товаров подключаются к проекту SkyMarket бесплатно.'),
                array('icon' => 'box', 'title' => 'Сеть продаж товаров', 'text' => 'Товары размещаются на платёжных терминалах SkySend в нескольких регионах страны.'),
                array('icon' => 'speed', 'title' => 'Простота взаимодействия', 'text' => 'Покупатель знакомится с товаром на терминале, формирует и оплачивает заказ.'),
                array('icon' => 'code', 'title' => 'Справочник товаров', 'text' => 'В справочнике размещаются фотографии и подробные описания товаров.'),
            ),
            'offer_title' => 'Проект SkyMarket',
            'offer_text' => 'SkyMarket обеспечивает загрузку справочника товаров, формирование и оплату заказов на терминалах SkySend.',
            'offer_list_title' => 'Работа со SkyMarket',
            'offer_items' => array('Справочник товаров', 'Работа через кабинет', 'Интеграция по XML'),
        ),
        'advertisers' => array(
            'icon' => 'megaphone',
            'tone' => 'orange',
            'image' => 'partner-advertisers.jpg',
            'title' => 'Рекламодателям',
            'card_text' => 'Видеореклама на экранах терминалов, реклама на чеках и SMS-рассылка.',
            'card_metric' => 'Видео',
            'card_metric_label' => 'на экране терминала',
            'kicker' => 'Рекламодателям',
            'hero_title' => 'Реклама SkySend',
            'visual_title' => 'Виды рекламы',
            'intro' => 'Рекламная платформа SkySend поддерживает трансляцию видеороликов на экранах терминалов, печать рекламы на чеках и онлайн SMS-рассылку.',
            'metrics' => array(
                array('value' => 'Видео', 'label' => 'на экране'),
                array('value' => 'Чек', 'label' => 'рекламный текст'),
                array('value' => 'SMS', 'label' => 'онлайн-рассылка'),
            ),
            'benefits' => array(
                array('icon' => 'terminal', 'title' => 'Видеореклама', 'text' => 'Видеоролик показывается на основном экране терминала в процессе совершения платежа.'),
                array('icon' => 'megaphone', 'title' => 'Реклама на чеках', 'text' => 'Рекламный текст печатается на лицевой стороне чека после совершения платежа.'),
                array('icon' => 'pin', 'title' => 'Таргетинг', 'text' => 'Параметры показа позволяют выбирать аудиторию рекламной кампании.'),
                array('icon' => 'pulse', 'title' => 'Статистика показов', 'text' => 'Статистика трансляций используется для контроля рекламной кампании.'),
            ),
            'offer_title' => 'Виды рекламы',
            'offer_text' => 'При проведении рекламной кампании оплачивается фактически осуществлённая реклама, а эффективность контролируется по статистике трансляций.',
            'offer_list_title' => 'Форматы рекламы',
            'offer_items' => array('Видеореклама', 'Реклама на чеках', 'Онлайн SMS-рассылка'),
        ),
        'representatives' => array(
            'icon' => 'pin',
            'tone' => 'green',
            'image' => 'partner-representatives.jpg',
            'title' => 'Представителям',
            'card_text' => 'Готовые решения для развития направлений SkySend в своём регионе.',
            'card_metric' => 'Регион',
            'card_metric_label' => 'эксклюзивность',
            'kicker' => 'Представителям',
            'hero_title' => 'Представительство SkySend в регионе',
            'visual_title' => 'Условия для представителей',
            'intro' => 'Представитель может наладить работу направлений SkySend в своём регионе, подключать новых партнёров и получать доход от их работы.',
            'metrics' => array(
                array('value' => 'Доход', 'label' => 'статьи доходов'),
                array('value' => 'Скидки', 'label' => 'дилерские'),
                array('value' => 'Регион', 'label' => 'эксклюзивность'),
            ),
            'benefits' => array(
                array('icon' => 'percent', 'title' => 'Статьи доходов', 'text' => 'Представитель получает доход от работы направлений SkySend в своём регионе.'),
                array('icon' => 'network', 'title' => 'Дилерские скидки', 'text' => 'Для представителей предусмотрены дилерские скидки.'),
                array('icon' => 'pin', 'title' => 'Эксклюзивность в регионе', 'text' => 'Представитель развивает направления системы SkySend на территории своего региона.'),
                array('icon' => 'plus', 'title' => 'Подключение партнёров', 'text' => 'Представитель подключает новых партнёров к системе SkySend.'),
            ),
            'offer_title' => 'Направления работы в регионе',
            'offer_text' => 'Система предлагает представителям организацию кассы в регионе, подключение партнёров и освоение направлений SkySend.',
            'offer_list_title' => 'Направления в регионе',
            'offer_items' => array('Касса в регионе', 'Подключение партнёров', 'Освоение направлений'),
        ),
        'gateways' => array(
            'icon' => 'gateway',
            'tone' => 'navy',
            'image' => 'partner-gateways.jpg',
            'title' => 'Шлюзовикам',
            'card_text' => 'Приём платежей в пользу провайдеров SkySend по XML-протоколу.',
            'card_metric' => 'XML',
            'card_metric_label' => 'протокол',
            'kicker' => 'Шлюзовикам',
            'hero_title' => 'Работа по XML-протоколу',
            'visual_title' => 'Возможности подключения',
            'intro' => 'Собственная предпроцессинговая система агента может быть интегрирована с системой SkySend по XML-протоколу для приёма платежей.',
            'metrics' => array(
                array('value' => 'XML', 'label' => 'протокол'),
                array('value' => '5 000+', 'label' => 'поставщиков услуг'),
                array('value' => 'Интеграция', 'label' => 'с системой SkySend'),
            ),
            'benefits' => array(
                array('icon' => 'percent', 'title' => 'Высокое вознаграждение', 'text' => 'Система предоставляет высокое вознаграждение и отсутствие скрытых комиссий.'),
                array('icon' => 'code', 'title' => 'XML-протокол', 'text' => 'По XML-протоколу автоматически передаются сведения о провайдерах, условиях и платежах.'),
                array('icon' => 'speed', 'title' => 'Высокая скорость', 'text' => 'Кластерный процессинговый центр распределяет нагрузку и синхронизирует данные между серверами.'),
                array('icon' => 'network', 'title' => 'Быстрое начало работы', 'text' => 'Для запуска выполняются интеграция протокола, создание XML-точки и тестирование платежей.'),
            ),
            'offer_title' => 'Организация приёма платежей',
            'offer_text' => 'Технические специалисты консультируют по интеграции XML-протокола, запускают XML-точку и тестируют проведение платежей.',
            'offer_list_title' => 'Этапы подключения',
            'offer_items' => array('Интеграция XML-протокола', 'Запуск XML-точки', 'Тестирование платежей'),
        ),
    );
}

function skysend_participant_context(): ?array
{
    $slug = (string) get_query_var('skysend_participant');
    $participants = skysend_participants();

    return isset($participants[$slug]) ? $participants[$slug] : null;
}

function skysend_register_participant_routes(): void
{
    foreach (array_keys(skysend_participants()) as $slug) {
        add_rewrite_rule(
            '^participants/' . preg_quote($slug, '/') . '/?$',
            'index.php?skysend_participant=' . $slug,
            'top'
        );
    }
}
add_action('init', 'skysend_register_participant_routes');

function skysend_query_vars(array $vars): array
{
    $vars[] = 'skysend_participant';
    return $vars;
}
add_filter('query_vars', 'skysend_query_vars');

function skysend_participant_status(): void
{
    if (!skysend_participant_context()) {
        return;
    }

    global $wp_query;
    $wp_query->is_404 = false;
    status_header(200);
}
add_action('template_redirect', 'skysend_participant_status');

function skysend_participant_template(string $template): string
{
    if (!skysend_participant_context()) {
        return $template;
    }

    $participant_template = locate_template('participant.php');
    return $participant_template ?: $template;
}
add_filter('template_include', 'skysend_participant_template');

function skysend_participant_body_class(array $classes): array
{
    if (skysend_participant_context()) {
        $classes[] = 'participant-page';
    }
    return $classes;
}
add_filter('body_class', 'skysend_participant_body_class');

function skysend_flush_participant_routes(): void
{
    skysend_register_participant_routes();
    flush_rewrite_rules();
}
add_action('after_switch_theme', 'skysend_flush_participant_routes');

function skysend_meta_tags(): void
{
    $participant = skysend_participant_context();
    if (!is_front_page() && !$participant) {
        return;
    }

    $title = $participant
        ? $participant['title'] . ' — SkySend'
        : 'SkySend — система приёма платежей';
    $description = $participant
        ? $participant['intro']
        : 'Система SkySend предоставляет возможность совершать оплаты в пользу более 5 000 поставщиков услуг. ПО ALLVEND, РМА Windows/Linux, РМА Android и XML-шлюз.';
    $canonical = $participant
        ? home_url('/participants/' . (string) get_query_var('skysend_participant') . '/')
        : home_url('/');
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

    $schema = $participant
        ? array(
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => $title,
            'url' => $canonical,
            'description' => $description,
            'isPartOf' => array('@type' => 'WebSite', 'name' => 'SkySend', 'url' => home_url('/')),
        )
        : array(
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => 'SkySend',
            'url' => $canonical,
            'description' => $description,
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
    $participant = skysend_participant_context();
    if ($participant) {
        $parts['title'] = $participant['title'] . ' — SkySend';
        unset($parts['tagline']);
    } elseif (is_front_page()) {
        $parts['title'] = 'SkySend — система приёма платежей';
        unset($parts['tagline']);
    }
    return $parts;
}
add_filter('document_title_parts', 'skysend_document_title');

function skysend_sitemap_status($preempt, WP_Query $query)
{
    if (get_query_var('sitemap')) {
        status_header(200);
        $query->is_404 = false;
        return true;
    }

    return $preempt;
}
add_filter('pre_handle_404', 'skysend_sitemap_status', 10, 2);

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
