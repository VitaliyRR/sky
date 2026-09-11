<footer class="site-footer">
    <div class="shell footer-contacts" id="contacts">
        <a class="footer-contact" href="<?php echo esc_attr(skysend_phone_href()); ?>">
            <span class="footer-contact__icon"><?php echo skysend_icon('phone'); ?></span>
            <span>
                <small>Подключение к системе</small>
                <strong><?php echo esc_html(skysend_phone()); ?></strong>
            </span>
        </a>
        <a class="footer-contact footer-contact--support" href="https://t.me/<?php echo esc_attr(skysend_telegram()); ?>" target="_blank" rel="noopener">
            <span class="footer-contact__icon"><?php echo skysend_icon('telegram'); ?></span>
            <span>
                <small>Техническая поддержка</small>
                <strong>@<?php echo esc_html(skysend_telegram()); ?></strong>
            </span>
        </a>
    </div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
