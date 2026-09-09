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
                <a class="participant-back" href="<?php echo esc_url(home_url('/#participants')); ?>"><?php echo skysend_icon('arrow'); ?> Все партнёры</a>
                <p class="eyebrow"><?php echo esc_html($participant['kicker']); ?></p>
                <h1><?php echo esc_html($participant['hero_title']); ?></h1>
                <p><?php echo esc_html($participant['intro']); ?></p>
                <a class="button button--accent" href="#connect">Подключиться <?php echo skysend_icon('arrow'); ?></a>
            </div>

            <div class="participant-hero__visual reveal" aria-label="<?php echo esc_attr($participant['visual_title']); ?>">
                <div class="participant-hero__visual-heading">
                    <span class="participant-hero__core" aria-hidden="true"><?php echo skysend_icon($participant['icon']); ?></span>
                    <span>
                        <small>Ключевые возможности</small>
                        <strong><?php echo esc_html($participant['visual_title']); ?></strong>
                    </span>
                </div>
                <div class="participant-hero__visual-list" role="list">
                    <?php foreach ($participant['metrics'] as $index => $metric) : ?>
                        <div class="participant-metric" role="listitem">
                            <span class="participant-metric__number"><?php echo esc_html(str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)); ?></span>
                            <span class="participant-metric__copy">
                                <strong><?php echo esc_html($metric['value']); ?></strong>
                                <small><?php echo esc_html($metric['label']); ?></small>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <section class="section participant-benefits">
        <div class="shell">
            <header class="section-heading reveal">
                <p class="section-kicker">Преимущества</p>
                <div>
                    <h2>Преимущества работы с SkySend</h2>
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
                <p class="section-kicker">Возможности</p>
                <h2><?php echo esc_html($participant['offer_title']); ?></h2>
                <p><?php echo esc_html($participant['offer_text']); ?></p>
            </div>
            <div class="participant-offer__list reveal" aria-label="<?php echo esc_attr($participant['offer_list_title']); ?>">
                <div class="participant-offer__list-heading">
                    <span class="participant-offer__list-icon" aria-hidden="true"><?php echo skysend_icon($participant['icon']); ?></span>
                    <strong><?php echo esc_html($participant['offer_list_title']); ?></strong>
                </div>
                <div role="list">
                    <?php foreach ($participant['offer_items'] as $index => $item) : ?>
                        <div class="participant-offer__item" role="listitem">
                            <span><?php echo esc_html(str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)); ?></span>
                            <strong><?php echo esc_html($item); ?></strong>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <section class="participant-cta" id="connect">
        <div class="shell participant-cta__inner reveal">
            <div>
                <p class="section-kicker">Контакты</p>
                <h2>Подключиться к SkySend</h2>
                <p>Для подключения свяжитесь с менеджером по телефону или воспользуйтесь разделом контактов.</p>
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
