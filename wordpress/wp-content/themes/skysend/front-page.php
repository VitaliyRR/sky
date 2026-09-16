<?php
/** SkySend landing page. @package SkySend */
get_header();
$partners = skysend_partners();
// The group celebrates its anniversary on May 15 (official ISG news, 2024).
$company_years = max(0, (int) wp_date('Y') - 2006 - (wp_date('m-d') < '05-15' ? 1 : 0));
$partner_offers = array(
    'agents' => 'Лучшие условия и стабильная работа.',
    'providers' => 'Надёжный и качественный приём платежей.',
    'suppliers' => 'Автоматизация клиентского обслуживания и продажи товаров.',
    'retailers' => 'Самообслуживание, заказ товаров и оплата услуг на ALLVEND.',
    'representatives' => 'Развитие региональной сети и подключение партнёров.',
    'gateways' => 'Интеграция вашей платёжной системы со SkySend.',
);
$software = array(
    array('icon' => 'terminal', 'title' => 'ПО ALLVEND', 'text' => 'Единое ПО для платёжных терминалов, инфокиосков и других устройств самообслуживания.', 'url' => '#allvend'),
    array('icon' => 'windows', 'title' => 'РМА Windows / Linux', 'text' => 'Рабочее место агента для приёма платежей с компьютера или ноутбука.', 'url' => 'https://www.isg.dev/ru/products/skysend/'),
    array('icon' => 'android', 'title' => 'РМА Android', 'text' => 'Приём платежей со смартфона или планшета под управлением Android.', 'url' => 'https://www.isg.dev/ru/products/skysend-app/'),
    array('icon' => 'code', 'title' => 'XML-шлюз', 'text' => 'Подключение собственной предпроцессинговой системы агента к SkySend.', 'url' => 'https://www.isg.dev/ru/products/skysend/'),
);
$provider_categories = skysend_provider_categories();
?>
<main id="main" class="landing-home">
    <section class="masthead" aria-label="Главные предложения SkySend" aria-roledescription="карусель" data-slider>
        <div class="masthead-slides">
            <article class="masthead-slide is-active" data-slide aria-hidden="false">
                <div class="shell masthead-inner">
                    <div class="masthead-copy">
                        <h1>Более 5 000<br>провайдеров услуг</h1>
                        <a class="button button--small button--line" href="#providers">Подробнее</a>
                    </div>
                    <div class="masthead-media">
                        <img src="<?php echo esc_url(get_theme_file_uri('/assets/images/banner-providers-20260914.webp')); ?>" width="1774" height="887" alt="Банки, операторы связи и поставщики услуг" fetchpriority="high" decoding="async">
                    </div>
                </div>
            </article>
            <article class="masthead-slide" data-slide aria-hidden="true" inert>
                <div class="shell masthead-inner">
                    <div class="masthead-copy">
                        <h2>Лучшие финансовые условия</h2>
                        <p>Высокое вознаграждение, снижение расходов на обслуживание и отсутствие скрытых комиссий.</p>
                        <a class="button button--small button--line" href="#participants">Подробнее</a>
                    </div>
                    <div class="masthead-media">
                        <img src="<?php echo esc_url(get_theme_file_uri('/assets/images/banner-finance-20260914.webp')); ?>" width="1774" height="887" alt="Платежи и финансовые условия для партнёров" decoding="async">
                    </div>
                </div>
            </article>
            <article class="masthead-slide" data-slide aria-hidden="true" inert>
                <div class="shell masthead-inner">
                    <div class="masthead-copy">
                        <h2>Уникальное программное обеспечение</h2>
                        <p>ALLVEND — единое решение для разных устройств самообслуживания.</p>
                        <a class="button button--small button--line" href="#allvend">Подробнее</a>
                    </div>
                    <div class="masthead-media masthead-media--allvend">
                        <img src="<?php echo esc_url(get_theme_file_uri('/assets/images/banner-allvend-20260914.webp')); ?>" width="1774" height="887" alt="Платёжный терминал, настольный инфокиоск, информационная панель и паркомат на ПО ALLVEND" decoding="async">
                        <img class="allvend-brand" src="<?php echo esc_url(get_theme_file_uri('/assets/images/allvend-logo.png')); ?>" width="320" height="200" alt="ALLVEND">
                    </div>
                </div>
            </article>
        </div>
        <div class="shell masthead-controls">
            <div class="masthead-pagination" role="group" aria-label="Выбор баннера">
                <button class="is-active" type="button" aria-label="Более 5 000 провайдеров услуг" aria-current="true" data-slide-to="0"></button>
                <button type="button" aria-label="Лучшие финансовые условия" data-slide-to="1"></button>
                <button type="button" aria-label="Уникальное программное обеспечение" data-slide-to="2"></button>
            </div>
        </div>
    </section>

    <section class="landing-panel landing-panel--partners" id="participants" aria-label="Партнерам">
        <div class="shell landing-panel-inner partners-layout">
            <div class="partner-offers">
                <?php foreach ($partners as $slug => $partner) : ?>
                    <article class="partner-offer reveal">
                        <img class="partner-offer-photo" src="<?php echo esc_url(get_theme_file_uri('/assets/images/' . $partner['image'])); ?>" width="453" height="367" alt="" loading="lazy" decoding="async">
                        <div class="partner-offer-copy">
                            <h3><?php echo esc_html($partner['title']); ?></h3>
                            <p><?php echo esc_html($partner_offers[$slug]); ?></p>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            <div class="partner-proof reveal" role="group" aria-label="Показатели группы компаний «Информ-Системы»">
                <p class="partner-proof-caption">Группа компаний «Информ-Системы» в цифрах</p>
                <dl class="partner-statistics">
                    <div><dt>Опыт работы, лет</dt><dd><?php echo esc_html((string) $company_years); ?></dd></div>
                    <div><dt>Партнёров группы</dt><dd>1 800+</dd></div>
                    <div><dt>Оборот через разработки, ₽</dt><dd>50 млрд+</dd></div>
                    <div><dt>Проведённых транзакций</dt><dd>150 млн+</dd></div>
                </dl>
                <p class="partner-proof-note">Оборот и транзакции — по презентации группы от 07.02.2021.</p>
            </div>
        </div>
    </section>

    <section class="landing-panel landing-panel--ice" id="allvend" aria-labelledby="allvend-title">
        <div class="shell landing-panel-inner feature-layout">
            <div class="product-art reveal">
                <img class="product-art-image" src="<?php echo esc_url(get_theme_file_uri('/assets/images/banner-allvend-20260914.webp')); ?>" width="1774" height="887" alt="Разные устройства самообслуживания с единым ПО ALLVEND" loading="lazy" decoding="async">
                <img class="product-art-brand" src="<?php echo esc_url(get_theme_file_uri('/assets/images/allvend-logo.png')); ?>" width="320" height="200" alt="ALLVEND" loading="lazy">
            </div>
            <div class="feature-copy reveal">
                <h2 id="allvend-title">Уникальное ПО ALLVEND</h2>
                <p class="panel-lead">Автоматизация продаж, оплаты услуг и обслуживания клиентов на разных устройствах.</p>
                <ul class="feature-list">
                    <li><?php echo skysend_icon('terminal'); ?><span><strong>Единое решение</strong>Платёжные терминалы, электронные кассиры, инфокиоски и информационные панели.</span></li>
                    <li><?php echo skysend_icon('box'); ?><span><strong>Товары и услуги</strong>Формирование заказов, оплата наличными и банковскими картами.</span></li>
                    <li><?php echo skysend_icon('network'); ?><span><strong>Удалённое управление</strong>Настройки, дизайн, мониторинг устройств и транзакций из онлайн-кабинета.</span></li>
                </ul>
                <a class="more-link" href="https://www.isg.dev/ru/products/allvend/" target="_blank" rel="noopener">Подробнее <?php echo skysend_icon('arrow'); ?></a>
            </div>
        </div>
    </section>

    <section class="landing-panel" id="capabilities" aria-labelledby="processing-title">
        <div class="shell landing-panel-inner feature-layout">
            <figure class="processing-art reveal">
                <div class="processing-stack" aria-hidden="true">
                    <?php for ($server = 0; $server < 3; $server++) : ?>
                        <div class="server-unit"><?php echo skysend_icon('server'); ?><i></i><i></i><i></i></div>
                    <?php endfor; ?>
                </div>
                <figcaption><strong>UNIX / FreeBSD</strong><span>Кластерная архитектура процессинга</span></figcaption>
            </figure>
            <div class="feature-copy reveal">
                <h2 id="processing-title">Высокая скорость обработки транзакций</h2>
                <p class="panel-lead">Серверы системы синхронизируют данные и распределяют поступающую нагрузку.</p>
                <ul class="feature-list">
                    <li><?php echo skysend_icon('network'); ?><span><strong>Кластер серверов</strong>Совместная обработка платежей и распределение нагрузки.</span></li>
                    <li><?php echo skysend_icon('server'); ?><span><strong>UNIX / FreeBSD</strong>Система разработана на базе FreeBSD и открытого программного обеспечения.</span></li>
                    <li><?php echo skysend_icon('shield'); ?><span><strong>Распределённая архитектура</strong>Серверы в разных центрах обработки данных связаны шифрованными туннелями IPSEC.</span></li>
                </ul>
                <a class="more-link" href="https://www.isg.dev/ru/products/skysend/" target="_blank" rel="noopener">Подробнее <?php echo skysend_icon('arrow'); ?></a>
            </div>
        </div>
    </section>

    <section class="landing-panel landing-panel--navy" id="security" aria-labelledby="security-title">
        <div class="shell landing-panel-inner security-layout">
            <header class="panel-heading reveal">
                <h2 id="security-title">Безопасность</h2>
                <p>Защита информации на серверах, при передаче данных и на устройствах самообслуживания.</p>
            </header>
            <div class="security-cards">
                <article class="security-card reveal"><?php echo skysend_icon('server'); ?><h3>Данные на серверах</h3><p>Хранение информации на криптографических разделах с шифрованием AES-XTS.</p></article>
                <article class="security-card reveal"><?php echo skysend_icon('shield'); ?><h3>Каналы связи</h3><p>Защищённые шифрованные каналы и электронная цифровая подпись.</p></article>
                <article class="security-card reveal"><?php echo skysend_icon('key'); ?><h3>Защита терминального ПО</h3><p>Шифрование образов ПО и данных, криптографическая привязка к устройству.</p></article>
            </div>
            <a class="more-link" href="https://www.isg.dev/ru/products/skysend/" target="_blank" rel="noopener">Подробнее <?php echo skysend_icon('arrow'); ?></a>
        </div>
    </section>

    <section class="landing-panel landing-panel--ice" id="fastsys" aria-labelledby="fastsys-title">
        <div class="shell landing-panel-inner feature-layout">
            <div class="fastsys-art reveal">
                <div class="fastsys-mark"><?php echo skysend_icon('chip'); ?></div>
                <strong>FastSYS <span>5</span></strong>
                <p>Операционная система для устройств самообслуживания</p>
                <div class="os-platforms"><span>Linux</span><span>Flash-накопитель</span></div>
            </div>
            <div class="feature-copy reveal">
                <h2 id="fastsys-title">Собственная операционная система</h2>
                <p class="panel-lead">FastSYS 5 поставляется с ПО ALLVEND как готовое решение для устройств самообслуживания.</p>
                <ul class="feature-list">
                    <li><?php echo skysend_icon('terminal'); ?><span><strong>Работа с flash-накопителя</strong>ОС и прикладное ПО устанавливаются вместе.</span></li>
                    <li><?php echo skysend_icon('pulse'); ?><span><strong>Бинарные обновления</strong>Обновление программного обеспечения и удалённый анализ работы.</span></li>
                    <li><?php echo skysend_icon('shield'); ?><span><strong>Криптографическая защита</strong>Шифрование образов ПО, хранимых и передаваемых данных.</span></li>
                </ul>
                <a class="more-link" href="https://www.isg.dev/ru/products/fastsys/" target="_blank" rel="noopener">Подробнее <?php echo skysend_icon('arrow'); ?></a>
            </div>
        </div>
    </section>

    <section class="landing-panel" id="software" aria-labelledby="software-title">
        <div class="shell landing-panel-inner software-layout">
            <header class="panel-heading reveal"><h2 id="software-title">Полный набор клиентского софта</h2><p>Для устройств самообслуживания, компьютеров, смартфонов и интеграции собственной системы.</p></header>
            <div class="client-software-grid">
                <?php foreach ($software as $program) : ?>
                    <article class="client-software-card reveal">
                        <div class="client-software-icon"><?php echo skysend_icon($program['icon']); ?></div>
                        <h3><?php echo esc_html($program['title']); ?></h3>
                        <p><?php echo esc_html($program['text']); ?></p>
                        <a class="more-link" href="<?php echo esc_url($program['url']); ?>"<?php if (str_starts_with($program['url'], 'https://')) : ?> target="_blank" rel="noopener"<?php endif; ?>>Подробнее <?php echo skysend_icon('arrow'); ?></a>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="landing-panel landing-panel--ice" id="providers" aria-labelledby="providers-title">
        <div class="shell landing-panel-inner providers-layout">
            <header class="panel-heading reveal"><h2 id="providers-title">Провайдеры услуг</h2></header>
            <div class="provider-browser" data-provider-tabs>
                <div class="provider-categories" role="tablist" aria-label="Категории провайдеров" aria-orientation="vertical">
                    <?php $category_index = 0; foreach ($provider_categories as $slug => $category) : ?>
                        <button type="button" role="tab" id="provider-tab-<?php echo esc_attr($slug); ?>" aria-controls="provider-panel-<?php echo esc_attr($slug); ?>" aria-selected="<?php echo $category_index === 0 ? 'true' : 'false'; ?>" tabindex="<?php echo $category_index === 0 ? '0' : '-1'; ?>" data-provider-tab="<?php echo esc_attr($slug); ?>"><?php echo esc_html($category['title']); ?> <?php echo skysend_icon('arrow'); ?></button>
                    <?php $category_index++; endforeach; ?>
                </div>
                <div class="provider-panels">
                    <?php $category_index = 0; foreach ($provider_categories as $slug => $category) : ?>
                        <div class="provider-panel" role="tabpanel" id="provider-panel-<?php echo esc_attr($slug); ?>" aria-labelledby="provider-tab-<?php echo esc_attr($slug); ?>" tabindex="0" data-provider-panel="<?php echo esc_attr($slug); ?>"<?php if ($category_index !== 0) : ?> hidden<?php endif; ?>>
                            <div class="provider-logo-grid<?php echo count($category['items']) === 6 ? ' provider-logo-grid--six' : ''; ?>">
                                <?php foreach ($category['items'] as $provider) : ?>
                                    <div class="provider-logo"><img src="<?php echo esc_url(get_theme_file_uri('/assets/images/providers/' . $provider['image'])); ?>" alt="" loading="lazy" decoding="async"><span><?php echo esc_html($provider['name']); ?></span></div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php $category_index++; endforeach; ?>
                </div>
            </div>
        </div>
    </section>
</main>
<?php get_footer(); ?>
