# SkySend landing page

Одностраничный сайт платёжной системы SkySend. Проект собирается как статический
сайт и разворачивается на виртуальной машине в отдельном chroot jail `www`.

## Локальный запуск

```bash
npm install
npm run dev
```

## Сборка

```bash
npm run build
```

Готовые файлы находятся в `dist/client`.

## Развёртывание в jail `www`

На Ubuntu используется минимальный chroot `/srv/jails/www`. Внутри jail находится
статический BusyBox HTTP-сервер и только собранные файлы сайта. Сервис запускается
от системного пользователя `www` и дополнительно ограничен настройками systemd.

```bash
sudo ./deploy/install-jail.sh dist/client
```
