# 🎲 D&D Copilot - AI Assistant for D&D

## 📁 Новая архитектура проекта

```
dnd/
├── app/                          # Основное приложение
│   ├── Controllers/              # Контроллеры
│   │   ├── admin.php            # Админ панель
│   │   └── stats.php            # Статистика
│   ├── Services/                 # Бизнес-логика
│   │   ├── ai-service.php       # AI сервис
│   │   ├── dnd-api-service.php  # D&D API сервис
│   │   ├── language-service.php # Языковой сервис
│   │   ├── CacheService.php     # Кэш сервис
│   │   └── CharacterService.php # Сервис персонажей
│   ├── Models/                   # Модели данных
│   └── Middleware/               # Промежуточное ПО
│       └── auth.php             # Авторизация
├── public/                       # Публичные файлы
│   ├── api/                      # API endpoints
│   │   ├── generate-characters.php
│   │   ├── generate-enemies.php
│   │   ├── generate-potions.php
│   │   ├── ai-chat.php
│   │   └── mobile-api.php
│   ├── assets/                   # Статические ресурсы
│   │   ├── css/
│   │   ├── js/
│   │   ├── images/
│   │   └── backgrounds/
│   ├── index.php                 # Главная страница
│   ├── login.php                 # Авторизация
│   ├── mobile.html               # Мобильная версия
│   └── *.html                    # Остальные страницы
├── config/                       # Конфигурация
│   └── config.php               # Основная конфигурация
├── data/                         # Данные приложения
│   ├── cache/                    # Кэш
│   ├── logs/                     # Логи
│   ├── pdf/                      # PDF документы
│   └── users.json               # Пользователи
├── docs/                         # Документация
├── vendor/                       # Зависимости
└── index.php                     # Точка входа (редирект)
```

## 🚀 Основные возможности

- **Генерация персонажей** - создание персонажей с полными характеристиками
- **Генерация противников** - создание подходящих противников для группы
- **AI чат** - интеллектуальный помощник для ответов на вопросы по D&D
- **Система заметок** - сохранение и управление игровыми заметками
- **PWA поддержка** - работает как нативное приложение на мобильных устройствах
- **Многоязычность** - поддержка русского и английского языков

## 🛠️ Технический стек

- **Backend**: PHP 7.4+
- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **AI API**: DeepSeek, OpenAI, Google Gemini
- **D&D API**: D&D 5e API, Open5e API
- **База данных**: JSON файлы + кэширование
- **PWA**: Service Worker, Web App Manifest

## ⚠️ ВАЖНЫЕ ПРАВИЛА РАЗРАБОТКИ

### 🚫 ЗАПРЕЩЕНО ИСПОЛЬЗОВАТЬ FALLBACK ДАННЫЕ

**КРИТИЧЕСКОЕ ПРАВИЛО**: Никогда не используйте fallback (резервные) данные в коде. Все данные должны поступать исключительно из внешних API:

- ✅ **Разрешено**: Получение данных из D&D 5e API, Open5e API
- ✅ **Разрешено**: Использование AI API для генерации контента
- ❌ **Запрещено**: Хардкод данных о расах, классах, заклинаниях
- ❌ **Запрещено**: Fallback массивы с игровыми данными
- ❌ **Запрещено**: Статические данные в коде

**Причина**: Проект должен работать только с актуальными данными из официальных источников.

## 🚀 Установка и запуск

1. **Клонируйте репозиторий**:
   ```bash
   git clone [repository-url]
   cd dnd
   ```

2. **Настройте веб-сервер** (Apache/Nginx) для работы с PHP

3. **Настройте API ключи** в `config/config.php`:
   ```php
   define('DEEPSEEK_API_KEY', 'your-key');
   define('OPENAI_API_KEY', 'your-key');
   define('GOOGLE_API_KEY', 'your-key');
   ```

4. **Откройте в браузере**: `http://localhost/dnd/`

## 🔧 Конфигурация

### API Ключи

В файле `config/config.php` настройте следующие API ключи:

- `DEEPSEEK_API_KEY` - для DeepSeek AI
- `OPENAI_API_KEY` - для OpenAI GPT
- `GOOGLE_API_KEY` - для Google Gemini

### Настройки кэширования

- `CACHE_DURATION` - время жизни кэша (по умолчанию 1 час)
- `AI_CACHE_DURATION` - время жизни кэша AI (по умолчанию 30 минут)

## 📊 Мониторинг и логи

- Логи приложения: `data/logs/app.log`
- Логи ошибок: `data/logs/error.log`
- Кэш AI: `data/cache/ai/`
- Кэш D&D API: `data/cache/dnd_api/`

## 🤝 Вклад в проект

1. **Следуйте правилам разработки** (особенно запрет на fallback данные)
2. **Тестируйте изменения** перед коммитом
3. **Документируйте новые функции**
4. **Используйте понятные имена переменных и функций**

## 📄 Лицензия

Проект использует данные из D&D 5e, которые являются собственностью Wizards of the Coast.

## 🔗 Полезные ссылки

- [D&D 5e API](https://www.dnd5eapi.co/)
- [Open5e API](https://open5e.com/)
- [DeepSeek API](https://platform.deepseek.com/)
- [OpenAI API](https://platform.openai.com/)
- [Google AI Studio](https://aistudio.google.com/)

---

**Помните**: Никогда не используйте fallback данные! Все данные должны поступать из внешних API.