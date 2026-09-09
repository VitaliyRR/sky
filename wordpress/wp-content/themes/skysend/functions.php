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
            'title' => 'Платёжным агентам',
            'card_text' => 'Доходная терминальная сеть с удалённым управлением и понятной экономикой.',
            'card_metric' => 'до 50%',
            'card_metric_label' => 'меньше расходов',
            'kicker' => 'Платёжным агентам',
            'hero_title' => 'Больше дохода с каждой точки',
            'intro' => 'Переведите терминалы и операторские точки в SkySend, чтобы сократить расходы на обслуживание сети, подключить востребованные платежи и управлять оборудованием из одного центра.',
            'metrics' => array(
                array('value' => 'до 50%', 'label' => 'ниже расходы'),
                array('value' => 'до 20%', 'label' => 'рост дохода'),
                array('value' => '5 000+', 'label' => 'поставщиков услуг'),
            ),
            'benefits' => array(
                array('icon' => 'cost', 'title' => 'Снижение расходов', 'text' => 'Централизованные настройки и мониторинг уменьшают количество выездов и ручных операций.'),
                array('icon' => 'network', 'title' => 'Удалённое управление', 'text' => 'Контролируйте терминалы и операторские точки из единого рабочего пространства.'),
                array('icon' => 'percent', 'title' => 'Рост доходов', 'text' => 'Высокое агентское вознаграждение и широкий набор услуг повышают доходность каждой точки.'),
                array('icon' => 'pulse', 'title' => 'Стабильная работа', 'text' => 'Следите за состоянием сети и быстрее реагируйте на отклонения в работе оборудования.'),
            ),
            'offer_title' => 'Готовая основа для развития сети',
            'offer_text' => 'SkySend объединяет перевод действующих терминалов, запуск операторских точек и современное ПО ALLVEND в одном решении.',
            'offer_items' => array('Перевод действующих терминалов', 'Операторские точки', 'ПО ALLVEND'),
        ),
        'providers' => array(
            'icon' => 'network',
            'tone' => 'cyan',
            'title' => 'Провайдерам услуг',
            'card_text' => 'Больше мест приёма платежей без развёртывания собственной терминальной сети.',
            'card_metric' => '5 000+',
            'card_metric_label' => 'поставщиков услуг',
            'kicker' => 'Провайдерам услуг',
            'hero_title' => 'Больше точек приёма ваших платежей',
            'intro' => 'Разместите оплату своих услуг в сети SkySend и дайте клиентам удобный способ платить через терминалы, операторские точки и подключённые интерфейсы.',
            'metrics' => array(
                array('value' => 'Шире', 'label' => 'география оплаты'),
                array('value' => '0 ₽', 'label' => 'за старт интеграции'),
                array('value' => 'Авто', 'label' => 'сверка и отчётность'),
            ),
            'benefits' => array(
                array('icon' => 'network', 'title' => 'Расширение сети', 'text' => 'Получайте дополнительные точки оплаты без затрат на собственную инфраструктуру.'),
                array('icon' => 'plus', 'title' => 'Быстрое подключение', 'text' => 'Команда SkySend помогает пройти интеграцию и вывести услугу в платёжную сеть.'),
                array('icon' => 'shield', 'title' => 'Защита данных', 'text' => 'Контролируемый обмен информацией помогает сохранять целостность платёжных операций.'),
                array('icon' => 'speed', 'title' => 'Автоматизация отчётности', 'text' => 'Сверка платежей и формирование отчётов становятся быстрее и прозрачнее.'),
            ),
            'offer_title' => 'Один вход — тысячи точек оплаты',
            'offer_text' => 'Подключите услугу к SkySend и используйте действующую сеть для приёма платежей и развития клиентского сервиса.',
            'offer_items' => array('Подключение к SkySend', 'Интеграция платёжной кнопки', 'Автоматизированная отчётность'),
        ),
        'suppliers' => array(
            'icon' => 'box',
            'tone' => 'violet',
            'title' => 'Поставщикам товаров',
            'card_text' => 'Новый канал продаж через терминалы и цифровые точки платёжной сети.',
            'card_metric' => 'Новый',
            'card_metric_label' => 'рынок сбыта',
            'kicker' => 'Поставщикам товаров',
            'hero_title' => 'Новый канал продаж без собственной инфраструктуры',
            'intro' => 'Разместите товары и услуги в подключённой сети SkySend, быстро запустите продажи в новых регионах и управляйте ассортиментом централизованно.',
            'metrics' => array(
                array('value' => 'Быстро', 'label' => 'выход на рынок'),
                array('value' => 'Шире', 'label' => 'география продаж'),
                array('value' => 'XML', 'label' => 'обмен данными'),
            ),
            'benefits' => array(
                array('icon' => 'box', 'title' => 'Новый рынок сбыта', 'text' => 'Используйте действующие платёжные точки как дополнительный канал продаж.'),
                array('icon' => 'speed', 'title' => 'Быстрый запуск', 'text' => 'Проверенная инфраструктура сокращает путь от интеграции до первой продажи.'),
                array('icon' => 'percent', 'title' => 'Рост доходов', 'text' => 'Дополнительные точки контакта помогают расширять продажи без открытия филиалов.'),
                array('icon' => 'code', 'title' => 'Гибкая интеграция', 'text' => 'Передавайте справочник товаров и данные заказов через согласованный протокол.'),
            ),
            'offer_title' => 'Продажи там, где клиент уже платит',
            'offer_text' => 'SkySend помогает разместить каталог, принимать заказы и передавать статусы между платёжной точкой и вашей системой.',
            'offer_items' => array('Справочник товаров', 'Работа через кабинет', 'Интеграция по XML'),
        ),
        'advertisers' => array(
            'icon' => 'megaphone',
            'tone' => 'orange',
            'title' => 'Рекламодателям',
            'card_text' => 'Реклама на экранах и чеках в момент, когда клиент совершает платёж.',
            'card_metric' => '4',
            'card_metric_label' => 'формата размещения',
            'kicker' => 'Рекламодателям',
            'hero_title' => 'Реклама в момент принятия решения',
            'intro' => 'Показывайте предложения на экранах терминалов, в режиме инфокиоска и на платёжных чеках — с централизованным управлением кампанией.',
            'metrics' => array(
                array('value' => 'Экран', 'label' => 'яркий баннер'),
                array('value' => 'Чек', 'label' => 'предложение после оплаты'),
                array('value' => 'Видео', 'label' => 'динамичный формат'),
            ),
            'benefits' => array(
                array('icon' => 'terminal', 'title' => 'Контакт в точке оплаты', 'text' => 'Обращайтесь к аудитории в момент высокой вовлечённости и конкретного действия.'),
                array('icon' => 'megaphone', 'title' => 'Несколько форматов', 'text' => 'Используйте баннеры, видео, экран инфокиоска и сообщения на чеках.'),
                array('icon' => 'pin', 'title' => 'Точная география', 'text' => 'Подбирайте территории и точки размещения под задачи конкретной кампании.'),
                array('icon' => 'pulse', 'title' => 'Единое управление', 'text' => 'Обновляйте материалы централизованно без ручной работы на каждой точке.'),
            ),
            'offer_title' => 'Одна кампания — вся подключённая сеть',
            'offer_text' => 'Подготовим формат размещения, выберем точки и поможем запустить рекламную кампанию в инфраструктуре SkySend.',
            'offer_items' => array('Баннеры и видео', 'Реклама на чеках', 'Региональное размещение'),
        ),
        'representatives' => array(
            'icon' => 'pin',
            'tone' => 'green',
            'title' => 'Представителям',
            'card_text' => 'Готовые направления SkySend для развития платёжной сети в своём регионе.',
            'card_metric' => 'Регион',
            'card_metric_label' => 'ваша зона роста',
            'kicker' => 'Представителям',
            'hero_title' => 'Развивайте SkySend в своём регионе',
            'intro' => 'Подключайте новых участников, развивайте востребованные направления системы и получайте доход от работы созданной региональной сети.',
            'metrics' => array(
                array('value' => 'Регион', 'label' => 'своя зона развития'),
                array('value' => 'Готово', 'label' => 'решения и материалы'),
                array('value' => 'Рост', 'label' => 'ежемесячного дохода'),
            ),
            'benefits' => array(
                array('icon' => 'percent', 'title' => 'Несколько статей дохода', 'text' => 'Развивайте разные направления SkySend и получайте результат от их работы.'),
                array('icon' => 'pin', 'title' => 'Региональная экспертиза', 'text' => 'Используйте знание местного рынка для быстрого подключения участников.'),
                array('icon' => 'network', 'title' => 'Открытое партнёрство', 'text' => 'Получайте готовые решения, материалы и поддержку команды SkySend.'),
                array('icon' => 'plus', 'title' => 'Подключение участников', 'text' => 'Развивайте сеть агентов, провайдеров и поставщиков в своём регионе.'),
            ),
            'offer_title' => 'Бизнес-модель для вашего региона',
            'offer_text' => 'Выберите направления, сформируйте план развития и запустите региональную сеть вместе с командой SkySend.',
            'offer_items' => array('Региональное представительство', 'Подключение участников', 'Развитие направлений'),
        ),
        'gateways' => array(
            'icon' => 'gateway',
            'tone' => 'navy',
            'title' => 'Шлюзовикам',
            'card_text' => 'Быстрый доступ к платёжной сети и провайдерам по единому XML-протоколу.',
            'card_metric' => 'XML',
            'card_metric_label' => 'единый протокол обмена',
            'kicker' => 'Шлюзовикам',
            'hero_title' => 'Одна интеграция для развития платёжного шлюза',
            'intro' => 'Подключайтесь к SkySend по XML-протоколу, расширяйте список доступных услуг и быстрее запускайте новые платёжные направления.',
            'metrics' => array(
                array('value' => 'XML', 'label' => 'единый протокол'),
                array('value' => '5 000+', 'label' => 'поставщиков услуг'),
                array('value' => 'Быстро', 'label' => 'начало работы'),
            ),
            'benefits' => array(
                array('icon' => 'code', 'title' => 'Понятный XML-протокол', 'text' => 'Единая схема обмена упрощает подключение и дальнейшее сопровождение.'),
                array('icon' => 'speed', 'title' => 'Высокая скорость', 'text' => 'Оптимизированный обмен данными сокращает время обработки запросов.'),
                array('icon' => 'network', 'title' => 'Больше провайдеров', 'text' => 'Расширяйте каталог услуг через одну интеграцию с платёжной сетью.'),
                array('icon' => 'shield', 'title' => 'Контроль операций', 'text' => 'Следите за статусами и целостностью данных на каждом этапе проведения платежа.'),
            ),
            'offer_title' => 'Быстрый старт по готовому протоколу',
            'offer_text' => 'Согласуем схему обмена, подключим тестовый контур и поможем вывести интеграцию в рабочий режим.',
            'offer_items' => array('Техническая интеграция', 'Подключение провайдеров', 'Сопровождение запуска'),
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
        : 'SkySend — система приёма платежей для бизнеса';
    $description = $participant
        ? $participant['intro']
        : 'SkySend объединяет платёжные терминалы, точки оплаты и более 5 000 поставщиков услуг. ПО ALLVEND, выгодные условия и готовые интеграции.';
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
