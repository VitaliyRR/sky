<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
    <script>document.documentElement.classList.add('js');</script>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#main">К основному содержанию</a>

<?php
$front_url = trailingslashit(home_url('/'));
$is_participant = (bool) skysend_participant_context();
$nav_url = static function (string $anchor) use ($front_url, $is_participant): string {
    return is_front_page() && !$is_participant ? '#' . $anchor : $front_url . '#' . $anchor;
};
?>

<header class="site-header" data-header>
    <div class="shell header-inner">
        <a class="brand" href="<?php echo esc_url(home_url('/')); ?>" aria-label="SkySend — главная">
            <img src="<?php echo esc_url(get_theme_file_uri('/assets/images/skysend-logo.png')); ?>" width="158" height="100" alt="SkySend">
        </a>

        <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="site-navigation" data-menu-toggle>
            <span></span><span></span>
            <span class="screen-reader-text">Открыть меню</span>
        </button>

        <nav class="main-nav" id="site-navigation" aria-label="Основная навигация" data-navigation>
            <a href="<?php echo esc_url($nav_url('participants')); ?>">Партнерам</a>
            <a href="<?php echo esc_url($nav_url('capabilities')); ?>">Возможности</a>
            <a href="<?php echo esc_url($nav_url('software')); ?>">Программы</a>
            <a href="<?php echo esc_url($nav_url('contacts')); ?>">Контакты</a>
        </nav>

        <div class="header-actions">
            <a class="header-phone" href="<?php echo esc_attr(skysend_phone_href()); ?>"><?php echo esc_html(skysend_phone()); ?></a>
            <a class="button button--small button--line header-login" href="https://cluster.skysend.ru/" target="_blank" rel="noopener"><span class="header-login__signin">Войти</span><span class="header-login__separator" aria-hidden="true">|</span><span class="header-login__register">Регистрация</span></a>
        </div>
    </div>
</header>
