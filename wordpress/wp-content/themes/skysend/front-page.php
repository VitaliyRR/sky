<?php
/**
 * SkySend landing page.
 *
 * @package SkySend
 */

get_header();

$solutions = array(
    array('icon' => 'terminal', 'title' => 'Платёжным агентам', 'text' => 'Терминалы и операторские точки в единой системе с удалённым управлением.'),
    array('icon' => 'network', 'title' => 'Провайдерам услуг', 'text' => 'Дополнительные точки приёма платежей и автоматизированная отчётность.'),
    array('icon' => 'box', 'title' => 'Поставщикам товаров', 'text' => 'Продажа товаров и услуг через подключённую платёжную сеть.'),
    array('icon' => 'megaphone', 'title' => 'Рекламодателям', 'text' => 'Таргетированное размещение рекламы на экранах и платёжных чеках.'),
    array('icon' => 'pin', 'title' => 'Представителям', 'text' => 'Развитие региональной сети и подключение новых участников системы.'),
    array('icon' => 'gateway', 'title' => 'Шлюзовикам', 'text' => 'Быстрый старт, XML-протокол и подключение новых провайдеров.'),
);

$benefits = array(
    array(
        'icon' => 'percent',
        'number' => '01',
        'title' => 'Высокое вознаграждение',
        'lead' => 'Финансовые условия, рассчитанные на рост сети.',
        'details' => 'Гибкая модель вознаграждения помогает повышать доходность действующих точек и планировать расширение бизнеса.',
    ),
    array(
        'icon' => 'cost',
        'number' => '02',
        'title' => 'Снижение расходов',
        'lead' => 'До 50% экономии на обслуживании терминальной сети.',
        'details' => 'Централизованные настройки, обновления и мониторинг сокращают количество выездов и ручных операций.',
    ),
    array(
        'icon' => 'pulse',
        'number' => '03',
        'title' => 'Стабильная работа',
        'lead' => 'Контроль состояния каждой точки и предсказуемая обработка платежей.',
        'details' => 'Система помогает быстро выявлять отклонения, контролировать доступность оборудования и поддерживать рабочий ритм сети.',
    ),
    array(
        'icon' => 'spark',
        'number' => '04',
        'title' => 'Уникальные инновации',
        'lead' => 'Современные сценарии оплаты и инструменты управления.',
        'details' => 'Архитектура SkySend поддерживает развитие новых сервисов, интеграций и пользовательских сценариев без замены всей инфраструктуры.',
    ),
    array(
        'icon' => 'speed',
        'number' => '05',
        'title' => 'Высокая скорость',
        'lead' => 'Быстрая передача запросов и проведение платежей.',
        'details' => 'Оптимизированный обмен данными сокращает ожидание плательщика и делает работу операторских точек удобнее.',
    ),
    array(
        'icon' => 'shield',
        'number' => '06',
        'title' => 'Защита данных',
        'lead' => 'Контроль операций и безопасный обмен информацией.',
        'details' => 'Разграничение доступа и защищённые каналы связи помогают сохранять целостность данных на каждом этапе проведения платежа.',
    ),
);

$programs = array(
    array('icon' => 'terminal', 'title' => 'ПО ALLVEND', 'tag' => 'Главное решение', 'text' => 'Современное программное обеспечение для платёжных терминалов: гибкая настройка интерфейса, продажа товаров и услуг, рекламные сценарии и централизованное управление.'),
    array('icon' => 'windows', 'title' => 'РМА Windows / Linux', 'tag' => 'Для операторских точек', 'text' => 'Рабочее место агента для приёма платежей на компьютерах под управлением Windows и Linux.'),
    array('icon' => 'android', 'title' => 'РМА Android', 'tag' => 'Для мобильной работы', 'text' => 'Компактное рабочее место для приёма платежей на совместимых Android-устройствах.'),
    array('icon' => 'code', 'title' => 'XML-шлюз', 'tag' => 'Для интеграции', 'text' => 'Протокол обмена для подключения внешних систем, провайдеров и платёжных интерфейсов.'),
);
?>

<main id="main">
    <section class="hero-slider" aria-label="Главные предложения SkySend" data-slider>
        <div class="hero-slides">
            <article class="hero-slide is-active" style="--slide-image: url('<?php echo esc_url(get_theme_file_uri('/assets/images/providers.jpg')); ?>')" data-slide aria-hidden="false">
                <div class="hero-overlay"></div>
                <div class="shell hero-content">
                    <p class="eyebrow">Платёжная система для бизнеса</p>
                    <h1>Более 5000<br>провайдеров услуг</h1>
                    <p>Единая точка доступа к востребованным платежам для терминалов, операторских точек и онлайн-сервисов.</p>
                    <a class="button button--blue" href="#contacts">Подключить SkySend <?php echo skysend_icon('arrow'); ?></a>
                </div>
            </article>

            <article class="hero-slide" style="--slide-image: url('<?php echo esc_url(get_theme_file_uri('/assets/images/conditions.jpg')); ?>')" data-slide aria-hidden="true">
                <div class="hero-overlay"></div>
                <div class="shell hero-content">
                    <p class="eyebrow">Экономика платёжной сети</p>
                    <h2>Лучшие финансовые<br>условия</h2>
                    <p>Высокие ставки вознаграждения, прозрачная модель работы и инструменты для снижения расходов.</p>
                    <a class="button button--blue" href="#benefits">Посмотреть преимущества <?php echo skysend_icon('arrow'); ?></a>
                </div>
            </article>

            <article class="hero-slide" style="--slide-image: url('<?php echo esc_url(get_theme_file_uri('/assets/images/allvend.jpg')); ?>')" data-slide aria-hidden="true">
                <div class="hero-overlay"></div>
                <div class="shell hero-content">
                    <p class="eyebrow">Программы</p>
                    <h2>ПО ALLVEND</h2>
                    <p>Новая платформа для платёжных терминалов с гибким интерфейсом, рекламой и управлением из единого центра.</p>
                    <a class="button button--blue" href="#software">Возможности программы <?php echo skysend_icon('arrow'); ?></a>
                </div>
            </article>
        </div>

        <div class="shell slider-controls">
            <div class="slider-dots" role="group" aria-label="Выбор слайда">
                <button class="is-active" type="button" aria-label="Более 5000 провайдеров услуг" aria-current="true" data-slide-to="0"><span>01</span></button>
                <button type="button" aria-label="Лучшие финансовые условия" data-slide-to="1"><span>02</span></button>
                <button type="button" aria-label="ПО ALLVEND" data-slide-to="2"><span>03</span></button>
            </div>
            <button class="slider-toggle" type="button" aria-label="Приостановить слайдер" aria-pressed="false" data-slider-toggle><span></span><span></span></button>
        </div>
    </section>

    <section class="section solutions" id="solutions">
        <div class="shell">
            <header class="section-heading reveal">
                <p class="section-kicker">Решения</p>
                <div>
                    <h2>SkySend для вашего бизнеса</h2>
                    <p>Готовые сценарии подключения для всех участников платёжной инфраструктуры.</p>
                </div>
            </header>

            <div class="solution-grid">
                <?php foreach ($solutions as $index => $solution) : ?>
                    <article class="solution-card reveal">
                        <div class="solution-icon"><?php echo skysend_icon($solution['icon']); ?></div>
                        <span class="card-index"><?php echo esc_html(str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)); ?></span>
                        <h3><?php echo esc_html($solution['title']); ?></h3>
                        <p><?php echo esc_html($solution['text']); ?></p>
                        <a href="#contacts" aria-label="Связаться: <?php echo esc_attr($solution['title']); ?>"><?php echo skysend_icon('arrow'); ?></a>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="section benefits" id="benefits">
        <div class="shell">
            <header class="section-heading section-heading--inverse reveal">
                <p class="section-kicker">Преимущества</p>
                <div>
                    <h2>Сильная сеть начинается с надёжной системы</h2>
                    <p>Ключевые преимущества SkySend собраны на одной странице. Подробности открываются по нажатию.</p>
                </div>
            </header>

            <div class="benefit-list" data-accordion-group>
                <?php foreach ($benefits as $benefit) : ?>
                    <details class="benefit-item reveal">
                        <summary>
                            <span class="benefit-number"><?php echo esc_html($benefit['number']); ?></span>
                            <span class="benefit-icon"><?php echo skysend_icon($benefit['icon']); ?></span>
                            <span class="benefit-title">
                                <strong><?php echo esc_html($benefit['title']); ?></strong>
                                <small><?php echo esc_html($benefit['lead']); ?></small>
                            </span>
                            <span class="benefit-plus"><?php echo skysend_icon('plus'); ?></span>
                        </summary>
                        <div class="benefit-detail">
                            <p><?php echo esc_html($benefit['details']); ?></p>
                        </div>
                    </details>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="section programs" id="software">
        <div class="shell">
            <header class="section-heading reveal">
                <p class="section-kicker">Программы</p>
                <div>
                    <h2>Программные решения SkySend</h2>
                    <p>Только актуальные продукты для терминалов, операторских точек и интеграций.</p>
                </div>
            </header>

            <div class="program-list" data-accordion-group>
                <?php foreach ($programs as $index => $program) : ?>
                    <details class="program-item reveal" <?php echo 0 === $index ? 'open' : ''; ?>>
                        <summary>
                            <span class="program-icon"><?php echo skysend_icon($program['icon']); ?></span>
                            <strong><?php echo esc_html($program['title']); ?></strong>
                            <span class="program-tag"><?php echo esc_html($program['tag']); ?></span>
                            <span class="program-plus"><?php echo skysend_icon('plus'); ?></span>
                        </summary>
                        <div class="program-detail">
                            <p><?php echo esc_html($program['text']); ?></p>
                            <a href="#contacts">Узнать о подключении <?php echo skysend_icon('arrow'); ?></a>
                        </div>
                    </details>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="section contacts" id="contacts">
        <div class="shell contacts-layout">
            <div class="contacts-intro reveal">
                <p class="section-kicker">Контакты</p>
                <h2>Подключение и поддержка</h2>
                <p>Обсудите подключение к SkySend или обратитесь по техническому вопросу.</p>
            </div>

            <div class="contact-options reveal">
                <article>
                    <span>01</span>
                    <h3>Подключение к системе</h3>
                    <p>Для новых агентов, провайдеров, поставщиков и рекламодателей.</p>
                    <a href="<?php echo esc_attr(skysend_phone_href()); ?>"><?php echo skysend_icon('phone'); ?> <?php echo esc_html(skysend_phone()); ?></a>
                </article>
                <article>
                    <span>02</span>
                    <h3>Техническая поддержка</h3>
                    <p>Для действующих участников и вопросов по работе программ.</p>
                    <a href="https://t.me/<?php echo esc_attr(skysend_telegram()); ?>" target="_blank" rel="noopener"><?php echo skysend_icon('telegram'); ?> @<?php echo esc_html(skysend_telegram()); ?></a>
                </article>
            </div>

        </div>
    </section>
</main>

<?php
get_footer();
