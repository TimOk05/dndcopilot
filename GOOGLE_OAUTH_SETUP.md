# Настройка Google OAuth для DnD Copilot

## Шаг 1: Создание проекта в Google Cloud Console

1. Перейдите на [Google Cloud Console](https://console.cloud.google.com/)
2. Создайте новый проект или выберите существующий
3. Включите Google+ API:
   - Перейдите в "APIs & Services" > "Library"
   - Найдите "Google+ API" и включите его

## Шаг 2: Создание OAuth 2.0 учетных данных

1. Перейдите в "APIs & Services" > "Credentials"
2. Нажмите "Create Credentials" > "OAuth 2.0 Client IDs"
3. Выберите тип приложения "Web application"
4. Заполните форму:
   - **Name**: DnD Copilot
   - **Authorized JavaScript origins**: 
     ```
     https://tim.dat-studio.com
     ```
   - **Authorized redirect URIs**:
     ```
     https://tim.dat-studio.com/dnd/google-auth.php
     ```

## Шаг 3: Получение ключей

После создания вы получите:
- **Client ID** (например: `123456789-abcdef.apps.googleusercontent.com`)
- **Client Secret** (например: `GOCSPX-abcdefghijklmnop`)

## Шаг 4: Обновление .env файла

Замените в файле `.env`:
```bash
GOOGLE_CLIENT_ID=your_google_client_id_here
GOOGLE_CLIENT_SECRET=your_google_client_secret_here
```

На ваши реальные ключи:
```bash
GOOGLE_CLIENT_ID=your_actual_client_id_here
GOOGLE_CLIENT_SECRET=your_actual_client_secret_here
```

**Важно:** Файл `.env` уже добавлен в `.gitignore` и не будет загружен в Git.

## Шаг 5: Проверка работы

1. Перезапустите сервер
2. Откройте `test-oauth.php` для проверки настроек
3. Перейдите на страницу входа
4. Нажмите "Войти через Google"
5. Должна открыться страница авторизации Google

## Безопасность

- ✅ **Секретные ключи в .env файле** - не попадают в Git
- ✅ **Файл .env в .gitignore** - защищен от случайной публикации
- ✅ **Переменные окружения** - безопасная загрузка ключей
- ⚠️ **Используйте HTTPS** в продакшене
- ⚠️ **Регулярно обновляйте ключи** при необходимости

## Устранение проблем

### Ошибка "Missing required parameter: client_id"
- Проверьте, что GOOGLE_CLIENT_ID правильно установлен в .env
- Убедитесь, что файл .env загружается
- Откройте `test-oauth.php` для диагностики

### Ошибка "redirect_uri_mismatch"
- Проверьте, что redirect URI в Google Console точно совпадает с GOOGLE_REDIRECT_URI в коде
- Убедитесь, что используется HTTPS

### Ошибка "invalid_client"
- Проверьте правильность Client ID и Client Secret
- Убедитесь, что OAuth 2.0 включен в проекте
