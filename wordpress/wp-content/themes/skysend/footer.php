<?php
/** Shared footer. Verified public materials: https://www.isg.dev/ru/products/allvend/ */
$downloads = array(
    array('title' => 'Установочный образ ALLVEND · ZIP', 'url' => 'https://ftp.isg.dev/soft/allvend/fastsys5_allvend.iso.zip'),
    array('title' => 'Установка ALLVEND · PDF', 'url' => 'https://ftp.isg.dev/docs/instruction_installation_allvend.pdf'),
    array('title' => 'Настройка и сервисный режим · PDF', 'url' => 'https://ftp.isg.dev/docs/instruction_setup_and_service_mode_allvend.pdf'),
    array('title' => 'Подключение оборудования · PDF', 'url' => 'https://ftp.isg.dev/docs/instruction_setup_devices_kiosks.pdf'),
    array('title' => 'Конфигурация ALLVEND · PDF', 'url' => 'https://ftp.isg.dev/docs/description_configuration_allvend.pdf'),
    array('title' => 'Разработка и сопровождение ПО · PDF', 'url' => 'https://ftp.isg.dev/offer/allvend/presentation_support_software_development.pdf'),
);
$social_links = array(
    'Telegram' => 'https://t.me/' . skysend_telegram(),
    'YouTube' => 'https://www.youtube.com/c/infsysgroup',
    'Facebook' => 'https://www.facebook.com/infsysgroup',
    'Instagram' => 'https://www.instagram.com/infsysgroup',
    'X (Twitter)' => 'https://twitter.com/infsysgroup',
);
?>
<footer class="site-footer footer-rebuilt" id="contacts" aria-label="Контакты и загрузки">
    <div class="shell footer-main">
        <a class="footer-brand" href="<?php echo esc_url(home_url('/')); ?>" aria-label="SkySend — главная"><img src="<?php echo esc_url(get_theme_file_uri('/assets/images/skysend-logo.png')); ?>" width="158" height="100" alt="SkySend" loading="lazy"></a>
        <div class="footer-column">
            <h2>Контакты компании</h2>
            <address><a href="<?php echo esc_attr(skysend_phone_href()); ?>"><?php echo esc_html(skysend_phone()); ?></a><a href="mailto:info@isg.dev">info@isg.dev</a></address>
        </div>
        <div class="footer-column">
            <h2>Техническая поддержка</h2>
            <address><a href="https://t.me/<?php echo esc_attr(skysend_telegram()); ?>" target="_blank" rel="noopener">@<?php echo esc_html(skysend_telegram()); ?></a><a href="mailto:support@isg.dev">support@isg.dev</a></address>
        </div>
        <div class="footer-column footer-column--downloads">
            <h2>Популярные загрузки</h2>
            <ul class="footer-downloads">
                <?php foreach ($downloads as $download) : ?>
                    <li><a href="<?php echo esc_url($download['url']); ?>" target="_blank" rel="noopener"><?php echo esc_html($download['title']); ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <div class="footer-bottom">
        <div class="shell footer-bottom-inner">
            <p>© 2006 - <?php echo esc_html(wp_date('Y')); ?> Группа компаний «Информ-Системы»</p>
            <nav class="footer-socials" aria-label="Социальные сети">
                <?php $social_index = 0; foreach ($social_links as $name => $url) : ?><?php if ($social_index++ > 0) : ?>, <?php endif; ?><a href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener"><?php echo esc_html($name); ?></a><?php endforeach; ?>
            </nav>
        </div>
    </div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
