# 📁 Структура проекта D&D Generator

## 🎯 **Очищенная структура (v3.0)**

```
dnd/
├── 📁 app/                          # Основная логика приложения
│   ├── 📁 Controllers/              # Контроллеры
│   │   ├── CharacterController.php  # Старый контроллер (можно удалить)
│   │   └── UnifiedController.php    # ✅ Новый унифицированный контроллер
│   ├── 📁 Middleware/               # Промежуточное ПО
│   │   └── auth.php                 # Авторизация
│   ├── 📁 Models/                   # Модели данных
│   │   └── Character.php            # Модель персонажа
│   └── 📁 Services/                 # Бизнес-логика
│       ├── AIService.php            # AI генерация
│       ├── CacheService.php         # Кэширование
│       ├── PotionService.php        # Зелья
│       ├── SpellService.php         # Заклинания
│       └── UnifiedCharacterService.php # ✅ Основной сервис
├── 📁 config/                       # Конфигурация
│   └── config.php                   # Основные настройки
├── 📁 data/                         # Данные приложения
│   ├── 📁 logs/                     # Логи
│   ├── 📁 заклинания/               # Заклинания
│   ├── 📁 зелья/                    # Зелья
│   └── 📁 персонажи/                # Персонажи
│       ├── 📁 имена/                # Имена
│       ├── 📁 классы/                # Классы
│       ├── 📁 расы/                  # Расы
│       └── 📁 снаряжение/            # Снаряжение
├── 📁 docs/                         # Документация
│   └── 📁 schemas/                   # JSON схемы
├── 📁 names/                        # Имена персонажей
├── 📁 public/                       # Публичные файлы
│   ├── 📁 api/                      # API endpoints
│   │   ├── error-handler.php        # Обработка ошибок
│   │   ├── generate-enemies.php     # Генерация врагов
│   │   ├── generate-potions.php     # Генерация зелий
│   │   ├── generate-spells.php      # Генерация заклинаний
│   │   ├── save-note.php            # Сохранение заметок
│   │   ├── test-generator.php       # ✅ Тест генератора
│   │   ├── users.php                # Пользователи
│   │   └── 📁 v3/                   # ✅ API v3
│   │       └── characters.php       # Унифицированный API
│   ├── 📁 assets/                   # Ресурсы
│   │   ├── 📁 backgrounds/          # Фоны
│   │   ├── 📁 css/                  # Стили
│   │   ├── 📁 images/               # Изображения
│   │   └── 📁 js/                   # JavaScript
│   ├── 📁 icons/                    # Иконки
│   ├── 📁 sound/                    # Звуки
│   ├── admin.php                    # Админ панель
│   ├── character-generator.html     # Генератор персонажей
│   ├── index.php                    # Главная страница
│   ├── login.php                    # Авторизация
│   ├── modal-character-generator.html # Модальный генератор
│   ├── template.html                # Шаблон
│   └── test-v3.html                 # ✅ Тестовая страница v3
├── 📁 vendor/                       # Зависимости
├── IMPROVEMENTS.md                  # ✅ Документация улучшений
├── PROJECT_STRUCTURE.md             # ✅ Эта документация
├── README.md                        # Описание проекта
├── gitFix.bat                       # Git утилиты
├── gitPull.bat                      # Git утилиты
├── gitUp.bat                        # Git утилиты
└── index.php                        # Точка входа
```

## ✅ **Что было очищено:**

### 🗑️ **Удаленные дублирующиеся файлы:**
- `public/api/generate-characters.php` (старый API)
- `public/api/improved-generate-characters.php` (старый API)
- `public/api/unified.php` (старый API)
- `public/api/v2/generate-character.php` (старый API)
- `public/index_improved.php` (старый интерфейс)
- `public/index_unified.php` (старый интерфейс)
- `public/index_v2.php` (старый интерфейс)

### 🗑️ **Удаленные старые сервисы:**
- `app/Services/CharacterService.php` (старый)
- `app/Services/ImprovedCharacterService.php` (старый)
- `app/Services/UnifiedCharacterGenerator.php` (старый)

### 🗑️ **Удаленные тестовые файлы:**
- `test_controller.php`
- `test.php`
- `public/test_generator.php`
- `public/test-api-simple.php`
- `debug_sound.html`
- `simple_sound_test.html`
- `test_sound.html`
- `test_files.html`

### 🗑️ **Удаленные дублирующиеся папки:**
- `sound/` (дублировала `public/sound/`)
- `public/api/v2/` (пустая папка)

## 🎯 **Текущая архитектура:**

### **API v3 (рекомендуемый):**
- **Endpoint:** `public/api/v3/characters.php`
- **Контроллер:** `app/Controllers/UnifiedController.php`
- **Сервис:** `app/Services/UnifiedCharacterService.php`

### **Тестирование:**
- **Веб-интерфейс:** `public/test-v3.html`
- **Консольный тест:** `public/api/test-generator.php`

## 📋 **Рекомендации по дальнейшей очистке:**

### 1. **Можно удалить (если не используются):**
- `app/Controllers/CharacterController.php` (старый)
- `public/character-generator.html` (если есть новый интерфейс)
- `public/modal-character-generator.html` (если не используется)

### 2. **Можно объединить:**
- `public/index.php` и `index.php` (выбрать один)
- API для зелий, заклинаний, врагов (создать единый API)

### 3. **Можно оптимизировать:**
- Структуру папки `data/` (переименовать на английский)
- Организацию API endpoints

## 🚀 **Следующие шаги:**

1. **Протестировать новую структуру**
2. **Обновить основной интерфейс для использования API v3**
3. **Добавить поддержку AI генерации**
4. **Создать единый API для всех функций**

---
*Обновлено: 2025-10-19*  
*Версия структуры: 3.0*
