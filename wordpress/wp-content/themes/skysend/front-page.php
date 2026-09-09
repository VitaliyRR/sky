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
        'metric' => 'до 20%',
        'label' => 'рост дохода сети',
        'title' => 'Больше зарабатывайте на действующей сети',
        'text' => 'Высокое вознаграждение и широкий выбор услуг помогают повышать доходность терминалов и операторских точек.',
    ),
    array(
        'tone' => 'ice',
        'icon' => 'cost',
        'metric' => '−50%',
        'label' => 'расходов на обслуживание',
        'title' => 'Сокращайте ручную работу и выезды',
        'text' => 'Централизованные настройки, обновления и мониторинг позволяют обслуживать сеть быстрее и экономичнее.',
    ),
    array(
        'tone' => 'cobalt',
        'icon' => 'pulse',
        'metric' => 'Онлайн',
        'label' => 'контроль каждой точки',
        'title' => 'Видите состояние сети в одном окне',
        'text' => 'Контролируйте доступность оборудования, статусы операций и отклонения до того, как они повлияют на клиентов.',
    ),
    array(
        'tone' => 'sand',
        'icon' => 'spark',
        'metric' => 'Гибко',
        'label' => 'новые сценарии',
        'title' => 'Запускайте новые услуги без замены инфраструктуры',
        'text' => 'Архитектура SkySend поддерживает новые платёжные продукты, рекламу, продажи и интеграции в существующей сети.',
    ),
    array(
        'tone' => 'aqua',
        'icon' => 'speed',
        'metric' => 'Быстро',
        'label' => 'обработка запросов',
        'title' => 'Проводите платежи без лишнего ожидания',
        'text' => 'Оптимизированный обмен данными делает работу плательщика и оператора быстрее на каждом этапе.',
    ),
    array(
        'tone' => 'violet',
        'icon' => 'shield',
        'metric' => 'Защита',
        'label' => 'контроль доступа',
        'title' => 'Защищайте данные и целостность операций',
        'text' => 'Разграничение доступа и контролируемые каналы обмена помогают сохранять платёжные данные на всём маршруте.',
    ),
);
?>

<main id="main">
    <section class="hero-slider" aria-label="Главные предложения SkySend" data-slider>
        <div class="hero-slides">
            <article class="hero-slide hero-slide--violet is-active" style="--slide-image: url('<?php echo esc_url(get_theme_file_uri('/assets/images/providers.jpg')); ?>')" data-slide aria-hidden="false">
                <div class="hero-overlay"></div>
                <div class="shell hero-content">
                    <p class="eyebrow">Единая платёжная инфраструктура</p>
                    <h1>Более 5 000<br>поставщиков услуг</h1>
                    <p>Подключите востребованные платежи к терминалам, операторским точкам и цифровым сервисам через одну систему.</p>
                    <a class="button button--accent" href="#participants">Кому подходит SkySend <?php echo skysend_icon('arrow'); ?></a>
                </div>
            </article>

            <article class="hero-slide hero-slide--mint" style="--slide-image: url('<?php echo esc_url(get_theme_file_uri('/assets/images/conditions.jpg')); ?>')" data-slide aria-hidden="true">
                <div class="hero-overlay"></div>
                <div class="shell hero-content">
                    <p class="eyebrow">Для действующих платёжных сетей</p>
                    <h2>До 50% меньше расходов.<br>До 20% больше дохода.</h2>
                    <p>Переведите сеть на SkySend, чтобы сократить обслуживание точек и улучшить финансовый результат.</p>
                    <a class="button button--accent" href="<?php echo esc_url(home_url('/participants/agents/')); ?>">Решение для агентов <?php echo skysend_icon('arrow'); ?></a>
                </div>
            </article>

            <article class="hero-slide hero-slide--blue" style="--slide-image: url('<?php echo esc_url(get_theme_file_uri('/assets/images/allvend.jpg')); ?>')" data-slide aria-hidden="true">
                <div class="hero-overlay"></div>
                <div class="shell hero-content">
                    <p class="eyebrow">ПО для терминальной сети</p>
                    <h2>ALLVEND — управление<br>из одного центра</h2>
                    <p>Меняйте интерфейс, запускайте новые услуги и рекламу, контролируйте терминалы удалённо.</p>
                    <a class="button button--accent" href="#software">Посмотреть программы <?php echo skysend_icon('arrow'); ?></a>
                </div>
            </article>
        </div>

        <div class="shell slider-controls">
            <div class="slider-dots" role="group" aria-label="Выбор слайда">
                <button class="is-active" type="button" aria-label="Более 5 000 поставщиков услуг" aria-current="true" data-slide-to="0"><span>01</span></button>
                <button type="button" aria-label="До 50% меньше расходов и до 20% больше дохода" data-slide-to="1"><span>02</span></button>
                <button type="button" aria-label="ALLVEND — управление из одного центра" data-slide-to="2"><span>03</span></button>
            </div>
        </div>
    </section>

    <section class="section participants" id="participants">
        <div class="shell">
            <header class="section-heading section-heading--compact reveal">
                <p class="section-kicker">Партнерам</p>
                <div>
                    <h2>SkySend для каждого партнера</h2>
                    <p>Выберите свою задачу — на отдельной странице собраны ключевые возможности и сценарий подключения.</p>
                </div>
            </header>

            <div class="participant-grid">
                <?php foreach ($participants as $slug => $participant) : ?>
                    <a class="participant-card participant-card--<?php echo esc_attr($participant['tone']); ?> reveal" href="<?php echo esc_url(home_url('/participants/' . $slug . '/')); ?>">
                        <span class="participant-art">
                            <span class="participant-art__line participant-art__line--one"></span>
                            <span class="participant-art__line participant-art__line--two"></span>
                            <span class="participant-art__node participant-art__node--one"></span>
                            <span class="participant-art__node participant-art__node--two"></span>
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
                <p class="section-kicker">Что меняется с SkySend</p>
                <h2 id="capability-title">Шесть сильных сторон одной системы</h2>
                <p>Каждая возможность работает на конкретный результат: меньше затрат, больше дохода и прозрачнее управление сетью.</p>
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
                    <div class="capability-graphic">
                        <span class="capability-graphic__icon"><?php echo skysend_icon($capability['icon']); ?></span>
                        <span class="capability-graphic__orbit capability-graphic__orbit--one"></span>
                        <span class="capability-graphic__orbit capability-graphic__orbit--two"></span>
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
                    <h2>Инструменты для каждой точки</h2>
                    <p>Терминалы, операторские места, мобильные устройства и внешние системы работают в общей инфраструктуре.</p>
                </div>
            </header>

            <div class="program-grid">
                <article class="program-card program-card--allvend reveal">
                    <div class="program-card__visual program-card__visual--photo" style="--program-image: url('<?php echo esc_url(get_theme_file_uri('/assets/images/allvend.jpg')); ?>')">
                        <span class="program-chip">Главное решение</span>
                        <span class="program-screen" aria-hidden="true"><i></i><i></i><i></i><i></i></span>
                    </div>
                    <div class="program-card__copy">
                        <span class="program-icon"><?php echo skysend_icon('terminal'); ?></span>
                        <h3>ПО ALLVEND</h3>
                        <p>Гибкий интерфейс терминала, продажа товаров и услуг, рекламные сценарии и централизованное управление сетью.</p>
                        <a href="#contacts">Обсудить подключение <?php echo skysend_icon('arrow'); ?></a>
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
                        <p>Рабочее место агента для приёма платежей на компьютере — с быстрым доступом к услугам и операциям.</p>
                        <a href="#contacts">Подключить рабочее место <?php echo skysend_icon('arrow'); ?></a>
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
                        <p>Мобильное рабочее место для приёма платежей на совместимых Android-устройствах.</p>
                        <a href="#contacts">Узнать о совместимости <?php echo skysend_icon('arrow'); ?></a>
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
                        <p>Единый протокол обмена для внешних систем, провайдеров и платёжных интерфейсов.</p>
                        <a href="<?php echo esc_url(home_url('/participants/gateways/')); ?>">Посмотреть сценарий <?php echo skysend_icon('arrow'); ?></a>
                    </div>
                </article>
            </div>
        </div>
    </section>

    <section class="section contacts" id="contacts">
        <div class="shell contacts-layout">
            <div class="contacts-intro reveal">
                <p class="section-kicker">Контакты</p>
                <h2>Давайте обсудим вашу задачу</h2>
                <p>Поможем подобрать сценарий подключения или разберём технический вопрос по действующей системе.</p>
                <span class="contacts-note">Отвечаем по телефону и в Telegram</span>
            </div>

            <div class="contact-methods reveal">
                <a class="contact-method" href="<?php echo esc_attr(skysend_phone_href()); ?>">
                    <span class="contact-method__icon"><?php echo skysend_icon('phone'); ?></span>
                    <span class="contact-method__copy">
                        <small>Новым партнерам</small>
                        <strong>Подключение к системе</strong>
                        <span>Обсудить формат работы и получить следующий шаг.</span>
                        <b><?php echo esc_html(skysend_phone()); ?> <?php echo skysend_icon('arrow'); ?></b>
                    </span>
                </a>
                <a class="contact-method contact-method--telegram" href="https://t.me/<?php echo esc_attr(skysend_telegram()); ?>" target="_blank" rel="noopener">
                    <span class="contact-method__icon"><?php echo skysend_icon('telegram'); ?></span>
                    <span class="contact-method__copy">
                        <small>Действующим партнерам</small>
                        <strong>Техническая поддержка</strong>
                        <span>Задать вопрос по программам, точкам и операциям.</span>
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
