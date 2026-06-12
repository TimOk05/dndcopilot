# Серверная авторизация D&D Copilot

Проект теперь можно запускать не как чистую статику, а через Node/Express-сервер. Сервер:

- закрывает `public/` за авторизацией;
- добавляет Google OAuth;
- хранит пользователей и будущую библиотеку в локальном JSON-файле `.data/dnd-copilot.json`;
- даёт защищённый endpoint для будущего AI-помощника;
- держит секреты в `.env`, а не во фронтенде.

## Что нужно зарегистрировать

### 1. Домен

Купленный домен нужно направить на хостинг, где будет работать Node-сервер.

Рекомендуемый вариант:

- `dnd.example.com` или другой поддомен для приложения;
- DNS `A`-запись на IP VPS или `CNAME` на адрес платформы хостинга;
- HTTPS-сертификат через хостинг, Caddy, Nginx + Certbot или Cloudflare.

Приложение должно открываться по HTTPS, например:

```text
https://dnd.example.com
```

### 2. Google OAuth

В Google Cloud Console нужно создать OAuth client для Web application.

Authorized redirect URIs:

```text
http://localhost:3000/auth/google/callback
https://dnd.example.com/auth/google/callback
```

Authorized JavaScript origins:

```text
http://localhost:3000
https://dnd.example.com
```

На OAuth consent screen добавь свой домен в Authorized domains. Для входа нужны только scopes `profile` и `email`, поэтому верификация Google обычно проще, чем для приложений с доступом к Drive/Gmail.

### 3. OpenAI API

Для будущего AI-помощника нужен API key в OpenAI Platform. Ключ кладётся только в `.env` на сервере:

```env
OPENAI_API_KEY=...
OPENAI_MODEL=gpt-5.5
```

Фронтенд не должен видеть этот ключ.

## Локальный запуск

1. Установить зависимости:

```bash
npm install
```

2. Создать `.env` из `.env.example`.

3. В Google Cloud Console создать OAuth Client ID:

- Application type: Web application
- Authorized redirect URI: `http://localhost:3000/auth/google/callback`

4. Заполнить:

```env
GOOGLE_CLIENT_ID=...
GOOGLE_CLIENT_SECRET=...
SESSION_SECRET=long-random-string
```

5. Запустить:

```bash
npm run dev
```

Открыть `http://localhost:3000`.

## Настройка для продакшена

Пример `.env` для домена:

```env
NODE_ENV=production
PORT=3000
AUTH_BASE_URL=https://dnd.example.com
TRUST_PROXY=1
SESSION_SECRET=long-random-string
GOOGLE_CLIENT_ID=...
GOOGLE_CLIENT_SECRET=...
ALLOWED_EMAILS=
OPENAI_API_KEY=...
OPENAI_MODEL=gpt-5.5
AI_RATE_LIMIT_WINDOW_MS=60000
AI_RATE_LIMIT_MAX=20
DATA_DIR=.data
```

`AUTH_BASE_URL` должен совпадать с публичным HTTPS-адресом сайта. Если приложение стоит за Nginx, Caddy, Cloudflare, Render, Railway, Fly.io или похожим proxy, оставь `TRUST_PROXY=1`.

## Docker-запуск

Можно использовать пример:

```bash
copy docker-compose.example.yml docker-compose.yml
docker compose up -d --build
```

Volume `dnd-copilot-data` хранит `.data`, то есть пользователей, сессии и серверную библиотеку.

## Ограничение доступа

Если сайт должен быть доступен только конкретным Google-аккаунтам, заполни:

```env
ALLOWED_EMAILS=dm@example.com,player@example.com
```

Если оставить пустым, войти сможет любой Google-аккаунт.

## Пользовательские данные

Фронтенд сохраняет данные моментально в `localStorage` и синхронизирует личные разделы с сервером после входа. При первом входе старые локальные данные автоматически импортируются в серверное хранилище.

Серверное API:

- `GET /api/storage`
- `GET /api/storage/:key`
- `PUT /api/storage/:key`
- `DELETE /api/storage/:key`

Важно: текущий файловый store удобен для старта, VPS и небольшого приватного приложения. Для большого публичного запуска лучше заменить `.data/dnd-copilot.json` на PostgreSQL/Supabase/Neon и добавить регулярные бэкапы.

## AI-помощник

Endpoint `POST /api/ai/chat` уже закрыт авторизацией и читает `OPENAI_API_KEY` только на сервере.

Пример тела запроса:

```json
{
  "input": "Сгенерируй завязку для приключения в болотной деревне"
}
```

На endpoint уже стоит простой лимит частоты запросов через `AI_RATE_LIMIT_WINDOW_MS` и `AI_RATE_LIMIT_MAX`. Для большого продакшена лучше заменить его на Redis/платформенный rate limit, добавить учёт расхода токенов и потоковые ответы.

## Минимальная схема публикации на своём домене

1. Арендовать VPS или выбрать Node-хостинг с постоянным диском.
2. Настроить DNS домена на этот сервер.
3. Выпустить HTTPS-сертификат.
4. Запустить приложение через `npm start`, PM2 или Docker.
5. В `.env` указать `AUTH_BASE_URL=https://your-domain`.
6. В Google OAuth добавить production redirect URI.
7. Проверить вход через Google и `GET /api/me`.
