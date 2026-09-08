# SkySend на WordPress

Актуальная версия сайта SkySend — одностраничная тема для WordPress 7.1.
Инфраструктура рассчитана на Ubuntu, Apache, PHP 8.3+ и MariaDB.

## Структура

- `wordpress/wp-content/themes/skysend` — кастомная тема лендинга;
- `wordpress/public` — `robots.txt` и правила Apache;
- `deploy/wordpress` — установка WordPress и настройка сервера;
- `docs/qa-report.md` — результаты технической проверки и лист согласования;
- React-прототип в корне сохранён как история первой версии и не используется
  текущим сайтом.

## Развёртывание

На сервер передаются каталоги `wordpress` и `deploy/wordpress`, после чего
выполняется:

```bash
sudo bash deploy/wordpress/install-wordpress.sh \
  wordpress/wp-content/themes/skysend \
  wordpress/public \
  http://31.129.98.28
```

Скрипт устанавливает Apache, MariaDB, PHP и WordPress 7.1, создаёт базу данных,
активирует тему, включает постоянные ссылки и проверяет целостность ядра.
Учётные данные администратора сохраняются только на сервере в файле
`/root/skysend-wordpress-admin.txt` с правами `0600`.

После переноса DNS на новый сервер нужно заменить URL сайта на HTTPS и выпустить
TLS-сертификат.
