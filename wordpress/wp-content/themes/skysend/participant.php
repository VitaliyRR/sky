<?php
/**
 * Landing page for a SkySend participant type.
 *
 * @package SkySend
 */

$participant = skysend_participant_context();
$slug = (string) get_query_var('skysend_participant');

if (!$participant) {
    status_header(404);
    get_template_part('index');
    return;
}

get_header();
?>

<main id="main" class="participant-main">
    <section class="participant-hero participant-hero--<?php echo esc_attr($participant['tone']); ?>">
        <div class="shell participant-hero__inner">
            <div class="participant-hero__copy reveal">
                <a class="participant-back" href="<?php echo esc_url(home_url('/#participants')); ?>"><?php echo skysend_icon('arrow'); ?> Все участники</a>
                <p class="eyebrow"><?php echo esc_html($participant['kicker']); ?></p>
                <h1><?php echo esc_html($participant['hero_title']); ?></h1>
                <p><?php echo esc_html($participant['intro']); ?></p>
                <a class="button button--accent" href="#connect">Обсудить подключение <?php echo skysend_icon('arrow'); ?></a>
            </div>

            <div class="participant-hero__visual reveal" aria-label="Ключевые показатели">
                <span class="participant-hero__core" aria-hidden="true"><?php echo skysend_icon($participant['icon']); ?></span>
                <span class="participant-hero__route participant-hero__route--one" aria-hidden="true"></span>
                <span class="participant-hero__route participant-hero__route--two" aria-hidden="true"></span>
                <span class="participant-hero__route participant-hero__route--three" aria-hidden="true"></span>
                <?php foreach ($participant['metrics'] as $index => $metric) : ?>
                    <span class="participant-metric participant-metric--<?php echo esc_attr((string) ($index + 1)); ?>">
                        <strong><?php echo esc_html($metric['value']); ?></strong>
                        <small><?php echo esc_html($metric['label']); ?></small>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="section participant-benefits">
        <div class="shell">
            <header class="section-heading reveal">
                <p class="section-kicker">Главное для вас</p>
                <div>
                    <h2>Практические возможности для ежедневной работы</h2>
                    <p>От финансового результата до удалённого контроля — каждый инструмент решает конкретную задачу вашей сети.</p>
                </div>
            </header>

            <div class="participant-benefit-grid">
                <?php foreach ($participant['benefits'] as $benefit) : ?>
                    <article class="participant-benefit reveal">
                        <span class="participant-benefit__icon"><?php echo skysend_icon($benefit['icon']); ?></span>
                        <h3><?php echo esc_html($benefit['title']); ?></h3>
                        <p><?php echo esc_html($benefit['text']); ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="participant-offer">
        <div class="shell participant-offer__inner">
            <div class="participant-offer__copy reveal">
                <p class="section-kicker">Как это работает</p>
                <h2><?php echo esc_html($participant['offer_title']); ?></h2>
                <p><?php echo esc_html($participant['offer_text']); ?></p>
            </div>
            <div class="participant-offer__map reveal" aria-label="Состав решения">
                <span class="participant-offer__core"><?php echo skysend_icon($participant['icon']); ?><b>SkySend</b></span>
                <?php foreach ($participant['offer_items'] as $index => $item) : ?>
                    <span class="participant-offer__item participant-offer__item--<?php echo esc_attr((string) ($index + 1)); ?>">
                        <strong><?php echo esc_html($item); ?></strong>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="participant-cta" id="connect">
        <div class="shell participant-cta__inner reveal">
            <div>
                <p class="section-kicker">Следующий шаг</p>
                <h2>Подключиться к SkySend</h2>
                <p>Расскажите о своей задаче — команда предложит подходящий формат и план запуска.</p>
            </div>
            <div class="participant-cta__actions">
                <a class="button button--light" href="<?php echo esc_attr(skysend_phone_href()); ?>"><?php echo esc_html(skysend_phone()); ?></a>
                <a class="button button--accent" href="<?php echo esc_url(home_url('/#contacts')); ?>">Все контакты <?php echo skysend_icon('arrow'); ?></a>
            </div>
        </div>
    </section>
</main>

<?php
get_footer();
