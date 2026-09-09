<?php
/**
 * SkySend landing page.
 *
 * @package SkySend
 */

get_header();

$participants = skysend_participants();

$capabilities = array(
    array(
        'tone' => 'ink',
        'icon' => 'percent',
        'metric' => 'Нет',
        'label' => 'скрытых комиссий',
        'title' => 'Высокое вознаграждение',
        'text' => 'Система SkySend полностью автоматизирована, поэтому затраты на её обслуживание снижены, а партнёрам предоставляется повышенное вознаграждение.',
    ),
    array(
        'tone' => 'ice',
        'icon' => 'cost',
        'metric' => '50%',
        'label' => 'экономия на обслуживании',
        'title' => 'Низкие расходы',
        'text' => 'Автоматическое устранение ошибок оборудования, удалённые обновления и автоматическая отладка сокращают количество технических выездов.',
    ),
    array(
        'tone' => 'cobalt',
        'icon' => 'pulse',
        'metric' => 'FastSYS4',
        'label' => 'ОС на базе Linux',
        'title' => 'Стабильная работа',
        'text' => 'FastSYS4 поддерживает автоматическое устранение ошибок устройств, анализ работы оборудования и удалённые обновления программного обеспечения.',
    ),
    array(
        'tone' => 'sand',
        'icon' => 'spark',
        'metric' => 'SkyMarket',
        'label' => 'заказ товаров',
        'title' => 'Уникальные инновации',
        'text' => 'Система включает рекламную платформу SkySend, проект заказа товаров SkyMarket и программное обеспечение с настраиваемым интерфейсом.',
    ),
    array(
        'tone' => 'aqua',
        'icon' => 'speed',
        'metric' => 'UNIX',
        'label' => 'кластерный процессинг',
        'title' => 'Высокая скорость',
        'text' => 'Серверы SkySend работают под управлением операционных систем семейства UNIX, синхронизируют данные и распределяют поступающую нагрузку.',
    ),
    array(
        'tone' => 'violet',
        'icon' => 'shield',
        'metric' => 'IPSEC',
        'label' => 'шифрованные каналы',
        'title' => 'Защита данных',
        'text' => 'Для передачи информации используются защищённые шифрованные каналы и электронная цифровая подпись.',
    ),
);
?>

<main id="main">
    <section class="hero-slider" aria-label="Главные предложения SkySend" data-slider>
        <div class="hero-slides">
            <article class="hero-slide hero-slide--violet is-active" style="--slide-image: url('<?php echo esc_url(get_theme_file_uri('/assets/images/providers.jpg')); ?>')" data-slide aria-hidden="false">
                <div class="hero-overlay"></div>
                <div class="shell hero-content">
                    <p class="eyebrow">Система приёма платежей</p>
                    <h1>Более 5 000<br>поставщиков услуг</h1>
                    <p>Система SkySend предоставляет возможность совершать оплаты в пользу более 5 000 поставщиков услуг.</p>
                    <a class="button button--accent" href="#participants">Партнерам <?php echo skysend_icon('arrow'); ?></a>
                </div>
            </article>

            <article class="hero-slide hero-slide--mint" style="--slide-image: url('<?php echo esc_url(get_theme_file_uri('/assets/images/conditions.jpg')); ?>')" data-slide aria-hidden="true">
                <div class="hero-overlay"></div>
                <div class="shell hero-content">
                    <p class="eyebrow">Преимущества SkySend</p>
                    <h2>Лучшие финансовые<br>условия</h2>
                    <p>Высокие ставки вознаграждения, экономия 50% на обслуживании и отсутствие скрытых комиссий.</p>
                    <a class="button button--accent" href="#capabilities">Преимущества <?php echo skysend_icon('arrow'); ?></a>
                </div>
            </article>

            <article class="hero-slide hero-slide--blue" style="--slide-image: url('<?php echo esc_url(get_theme_file_uri('/assets/images/allvend.jpg')); ?>')" data-slide aria-hidden="true">
                <div class="hero-overlay"></div>
                <div class="shell hero-content">
                    <p class="eyebrow">Программы</p>
                    <h2>ПО ALLVEND</h2>
                    <p>Универсальное программное обеспечение для платёжных терминалов и других устройств самообслуживания.</p>
                    <a class="button button--accent" href="#software">ПО ALLVEND <?php echo skysend_icon('arrow'); ?></a>
                </div>
            </article>
        </div>

        <div class="shell slider-controls">
            <div class="slider-dots" role="group" aria-label="Выбор слайда">
                <button class="is-active" type="button" aria-label="Более 5 000 поставщиков услуг" aria-current="true" data-slide-to="0"><span>01</span></button>
                <button type="button" aria-label="Лучшие финансовые условия" data-slide-to="1"><span>02</span></button>
                <button type="button" aria-label="ПО ALLVEND" data-slide-to="2"><span>03</span></button>
            </div>
        </div>
    </section>

    <section class="section participants" id="participants">
        <div class="shell">
            <header class="section-heading section-heading--compact reveal">
                <p class="section-kicker">Партнерам</p>
                <div>
                    <h2>Партнерам SkySend</h2>
                    <p>Информация для платёжных агентов, провайдеров услуг, поставщиков товаров, рекламодателей, представителей и шлюзовиков.</p>
                </div>
            </header>

            <div class="participant-grid">
                <?php foreach ($participants as $slug => $participant) : ?>
                    <a class="participant-card participant-card--<?php echo esc_attr($participant['tone']); ?> reveal" href="<?php echo esc_url(home_url('/participants/' . $slug . '/')); ?>">
                        <span class="participant-art">
                            <span class="participant-art__core"><?php echo skysend_icon($participant['icon']); ?></span>
                            <span class="participant-art__metric">
                                <strong><?php echo esc_html($participant['card_metric']); ?></strong>
                                <small><?php echo esc_html($participant['card_metric_label']); ?></small>
                            </span>
                        </span>
                        <span class="participant-card__copy">
                            <strong><?php echo esc_html($participant['title']); ?></strong>
                            <span><?php echo esc_html($participant['card_text']); ?></span>
                            <span class="participant-card__more">Подробнее <?php echo skysend_icon('arrow'); ?></span>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="capability-stack" id="capabilities" aria-labelledby="capability-title">
        <header class="capability-intro">
            <div class="shell capability-intro__inner reveal">
                <p class="section-kicker">Преимущества</p>
                <h2 id="capability-title">Преимущества системы SkySend</h2>
                <p>Высокое вознаграждение, низкие расходы, стабильная работа, уникальные инновации, высокая скорость и защита данных.</p>
            </div>
        </header>

        <?php foreach ($capabilities as $index => $capability) : ?>
            <article class="capability-band capability-band--<?php echo esc_attr($capability['tone']); ?>">
                <div class="shell capability-band__inner reveal">
                    <div class="capability-band__copy">
                        <span class="capability-band__number"><?php echo esc_html(str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)); ?></span>
                        <h3><?php echo esc_html($capability['title']); ?></h3>
                        <p><?php echo esc_html($capability['text']); ?></p>
                    </div>
                    <div class="capability-graphic" aria-label="<?php echo esc_attr($capability['metric'] . ' — ' . $capability['label']); ?>">
                        <span class="capability-graphic__icon"><?php echo skysend_icon($capability['icon']); ?></span>
                        <span class="capability-graphic__caption">Ключевой факт</span>
                        <strong><?php echo esc_html($capability['metric']); ?></strong>
                        <small><?php echo esc_html($capability['label']); ?></small>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </section>

    <section class="section programs" id="software">
        <div class="shell">
            <header class="section-heading reveal">
                <p class="section-kicker">Программы</p>
                <div>
                    <h2>Программы SkySend</h2>
                    <p>ПО ALLVEND, рабочее место агента для Windows и Linux, приложение для Android и подключение по XML-протоколу.</p>
                </div>
            </header>

            <div class="program-grid">
                <article class="program-card program-card--allvend reveal">
                    <div class="program-card__visual program-card__visual--photo" style="--program-image: url('<?php echo esc_url(get_theme_file_uri('/assets/images/allvend.jpg')); ?>')">
                        <span class="program-chip">Для систем самообслуживания</span>
                        <span class="program-screen" aria-hidden="true"><i></i><i></i><i></i><i></i></span>
                    </div>
                    <div class="program-card__copy">
                        <span class="program-icon"><?php echo skysend_icon('terminal'); ?></span>
                        <h3>ПО ALLVEND</h3>
                        <p>Универсальное ПО для устройств самообслуживания: настройка интерфейса, реклама, формирование и оплата заказов.</p>
                        <a href="#contacts">Подключение <?php echo skysend_icon('arrow'); ?></a>
                    </div>
                </article>

                <article class="program-card reveal">
                    <div class="program-card__visual program-card__visual--desktop" aria-hidden="true">
                        <span class="device-desktop"><i></i><b></b></span>
                        <span class="program-signal program-signal--one"></span>
                        <span class="program-signal program-signal--two"></span>
                    </div>
                    <div class="program-card__copy">
                        <span class="program-icon"><?php echo skysend_icon('windows'); ?></span>
                        <h3>РМА Windows / Linux</h3>
                        <p>Программа приёма платежей на стационарном компьютере или ноутбуке под управлением Windows или Linux.</p>
                        <a href="#contacts">Подключение <?php echo skysend_icon('arrow'); ?></a>
                    </div>
                </article>

                <article class="program-card reveal">
                    <div class="program-card__visual program-card__visual--mobile" aria-hidden="true">
                        <span class="device-phone"><i></i><i></i><i></i><i></i></span>
                        <span class="device-pulse"></span>
                    </div>
                    <div class="program-card__copy">
                        <span class="program-icon"><?php echo skysend_icon('android'); ?></span>
                        <h3>РМА Android</h3>
                        <p>Приложение для приёма платежей с планшета или смартфона под управлением Android.</p>
                        <a href="#contacts">Подключение <?php echo skysend_icon('arrow'); ?></a>
                    </div>
                </article>

                <article class="program-card reveal">
                    <div class="program-card__visual program-card__visual--code" aria-hidden="true">
                        <span class="code-window"><i>&lt;request&gt;</i><i>&nbsp;&nbsp;&lt;payment /&gt;</i><i>&lt;/request&gt;</i></span>
                        <span class="code-route code-route--one"></span>
                        <span class="code-route code-route--two"></span>
                    </div>
                    <div class="program-card__copy">
                        <span class="program-icon"><?php echo skysend_icon('code'); ?></span>
                        <h3>XML-шлюз</h3>
                        <p>Интеграция собственной предпроцессинговой системы агента с системой SkySend по XML-протоколу.</p>
                        <a href="<?php echo esc_url(home_url('/participants/gateways/')); ?>">Подробнее <?php echo skysend_icon('arrow'); ?></a>
                    </div>
                </article>
            </div>
        </div>
    </section>

    <section class="section contacts" id="contacts">
        <div class="shell contacts-layout">
            <div class="contacts-intro reveal">
                <p class="section-kicker">Контакты</p>
                <h2>Подключение и техническая поддержка</h2>
                <p>Контакты для подключения к системе SkySend и технической поддержки.</p>
                <span class="contacts-note">Телефон и Telegram</span>
            </div>

            <div class="contact-methods reveal">
                <a class="contact-method" href="<?php echo esc_attr(skysend_phone_href()); ?>">
                    <span class="contact-method__icon"><?php echo skysend_icon('phone'); ?></span>
                    <span class="contact-method__copy">
                        <small>Подключение</small>
                        <strong>Подключение к системе</strong>
                        <span>Для подключения к системе SkySend.</span>
                        <b><?php echo esc_html(skysend_phone()); ?> <?php echo skysend_icon('arrow'); ?></b>
                    </span>
                </a>
                <a class="contact-method contact-method--telegram" href="https://t.me/<?php echo esc_attr(skysend_telegram()); ?>" target="_blank" rel="noopener">
                    <span class="contact-method__icon"><?php echo skysend_icon('telegram'); ?></span>
                    <span class="contact-method__copy">
                        <small>Поддержка</small>
                        <strong>Техническая поддержка</strong>
                        <span>Служба технической поддержки SkySend.</span>
                        <b>@<?php echo esc_html(skysend_telegram()); ?> <?php echo skysend_icon('arrow'); ?></b>
                    </span>
                </a>
            </div>
        </div>
        <span class="contacts-orbit contacts-orbit--one" aria-hidden="true"></span>
        <span class="contacts-orbit contacts-orbit--two" aria-hidden="true"></span>
    </section>
</main>

<?php
get_footer();
