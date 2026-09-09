<?php
/**
 * Not found page.
 *
 * @package SkySend
 */

get_header();
?>
<main class="error-page" id="main">
    <section class="error-page__section">
        <div class="shell error-page__inner">
            <div class="error-page__copy">
                <p class="section-kicker">Ошибка 404</p>
                <h1>Такой страницы нет</h1>
                <p>Возможно, адрес изменился или ссылка устарела. Вернитесь на главную либо свяжитесь с нами — поможем найти нужную информацию.</p>
                <div class="error-page__actions">
                    <a class="button button--accent" href="<?php echo esc_url(home_url('/')); ?>">На главную <?php echo skysend_icon('arrow'); ?></a>
                    <a class="button button--line" href="<?php echo esc_url(home_url('/#contacts')); ?>">Контакты</a>
                </div>
            </div>
            <div class="error-page__code" aria-hidden="true">
                <span>4</span>
                <i><?php echo skysend_icon('gateway'); ?></i>
                <span>4</span>
            </div>
        </div>
    </section>
</main>
<?php
get_footer();
