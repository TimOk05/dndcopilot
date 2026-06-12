# D&D Copilot: vanilla PHP build

Проект теперь запускается как монолитный PHP front controller без NodeJS,
Composer, фреймворков, сторонних библиотек и Docker.

## Структура

- `index.php` - весь серверный runtime: роутинг, сессии, вход по email-коду, API и выдача ассетов.
- `config.php` - настройки сайта, включая время жизни кода и cooldown отправки.
- `config.local.php` - необязательные приватные переопределения. Файл игнорируется git.
- `templates/app.html` - HTML приложения, выдается только после входа.
- `public/` - vanilla CSS, vanilla JS, JSON-данные, иконки, фоны и звуки.
- `.data/dnd-copilot.sqlite` - локальная SQLite-база пользователей, кодов входа и личного хранилища.

## Локальный запуск

```bash
php -S 127.0.0.1:8000 index.php
```

Открой `http://127.0.0.1:8000`.

Для SQLite нужен встроенный PHP-модуль `pdo_sqlite`. Обычно он уже включен в
локальных PHP-сборках и на большинстве shared hosting, но на своем сервере его
иногда нужно включить в `php.ini`.

Путь к базе задается в `config.php`:

```php
'database_path' => __DIR__ . '/.data/dnd-copilot.sqlite',
```

Если рядом есть старый файл `.data/dnd-copilot.json`, приложение один раз
импортирует пользователей, коды входа и личное хранилище в SQLite, после чего
переименует JSON в файл с суффиксом `.migrated-*`.

На локальной машине, если сайт открыт через `localhost`, `127.0.0.1` или
`::1`, включается local auth mode:

- можно вводить любой email, даже если в продакшене задан `allowed_emails`;
- письмо через `mail()` не отправляется;
- код входа `111` автоматически подставляется в поле подтверждения.

Код можно изменить в `config.php`:

```php
'local_login_code' => '111',
```

В продакшене на обычном домене local auth mode не включается.

## HTTPS

Если сайт открыт не через `localhost`, `127.0.0.1` или `::1`, HTTP-запросы
автоматически перенаправляются на HTTPS с тем же хостом и адресом страницы.

Настройки:

```php
'force_https_non_local' => true,
'hsts_max_age' => 31536000,
```

Если приложение стоит за Nginx, Apache proxy, Cloudflare или другим reverse
proxy, прокси должен передавать признак HTTPS в PHP, например через
`X-Forwarded-Proto: https`, иначе возможен повторный редирект.

## Email-вход

Пользователь вводит email на `/login`, после чего `index.php` генерирует код,
сохраняет только его hash и отправляет письмо через встроенную функцию PHP
`mail()`.

Основные настройки в `config.php`:

```php
'login_code_ttl_minutes' => 10,
'login_code_cooldown_minutes' => 2,
'login_code_ip_cooldown_minutes' => 1,
'login_code_digits' => 6,
'login_code_max_attempts' => 5,
'mail_from' => 'D&D Copilot <no-reply@example.com>',
'local_login_code' => '111',
'force_https_non_local' => true,
```

`login_code_ttl_minutes` отвечает за срок действия кода, а
`login_code_cooldown_minutes` не дает повторно спамить один и тот же почтовый
ящик. Дополнительный IP-cooldown снижает риск массовой отправки на разные
адреса с одного источника.

Чтобы ограничить доступ только конкретными адресами, заполни:

```php
'allowed_emails' => [
    'dm@example.com',
    'player@example.com',
],
```

## API

Фронт продолжает использовать защищенные endpoints:

- `GET /api/me`
- `GET /api/storage`
- `GET /api/storage/{key}`
- `PUT /api/storage/{key}`
- `DELETE /api/storage/{key}`
- `POST /auth/logout`

Личное хранилище сохраняется в `.data/dnd-copilot.sqlite` и синхронизируется с
localStorage после входа.

## Публикация

Для Apache/shared hosting достаточно загрузить проект в document root с
включенным `mod_rewrite`: `.htaccess` направляет все запросы в `index.php`.

Для Nginx нужно направить все неизвестные маршруты на `index.php`, а папки
`.data`, `templates` и приватные конфиги закрыть от прямой выдачи.
