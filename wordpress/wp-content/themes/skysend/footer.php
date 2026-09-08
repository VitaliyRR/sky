<footer class="site-footer">
    <div class="shell footer-line">
        <span>© 2006–<?php echo esc_html(wp_date('Y')); ?> Группа компаний «Информ-Системы»</span>
        <span class="footer-separator" aria-hidden="true">·</span>
        <a href="<?php echo esc_attr(skysend_phone_href()); ?>"><?php echo esc_html(skysend_phone()); ?></a>
        <span class="footer-separator" aria-hidden="true">·</span>
        <a href="https://t.me/<?php echo esc_attr(skysend_telegram()); ?>" target="_blank" rel="noopener">Telegram @<?php echo esc_html(skysend_telegram()); ?></a>
    </div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
