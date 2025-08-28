# 🌐 Публичный доступ к AI для всех пользователей

## 🎯 **Проблема и решение**

### **Проблема:**
- API ключи видны в коде (небезопасно)
- Каждый пользователь должен иметь свой ключ
- Нет централизованного управления
- Ограничения на количество ключей

### **Решение: Backend-прокси**
- Централизованный API ключ на сервере
- Публичный доступ для всех пользователей
- Контроль лимитов и мониторинг
- Безопасность и масштабируемость

## 🏗️ **Архитектура**

### **Схема работы:**
```
Пользователь → Frontend → Backend API → DeepSeek API → Ответ
```

### **Компоненты:**
1. **Frontend** - HTML/JS интерфейс
2. **Backend API** - Node.js/Next.js прокси
3. **DeepSeek API** - Внешний AI сервис
4. **База данных** - Логирование и лимиты

## 🔧 **Настройка Backend-прокси**

### **1. Обновление API маршрута**

В файле `apps/web/src/app/api/orchestrate/route.ts`:

```typescript
import { NextRequest, NextResponse } from 'next/server'
import { DeepSeekClient } from '@/lib/ai'

const deepseek = new DeepSeekClient()

// Добавить лимиты и мониторинг
const RATE_LIMIT = {
  requestsPerMinute: 10,
  requestsPerHour: 100,
  maxTokensPerRequest: 1000
}

export async function POST(request: NextRequest) {
  try {
    const body = await request.json()
    const { type, context, sessionStage } = body

    // Проверка лимитов (можно добавить Redis для распределенного хранения)
    // const clientIP = request.headers.get('x-forwarded-for') || 'unknown'
    // const isRateLimited = await checkRateLimit(clientIP)
    // if (isRateLimited) {
    //   return NextResponse.json({ error: 'Rate limit exceeded' }, { status: 429 })
    // }

    // Генерация подсказки
    const suggestion = await deepseek.generateSuggestion({
      type,
      context,
      sessionStage
    })

    // Логирование использования
    await logUsage({
      type,
      tokens: suggestion.tokens || 0,
      timestamp: new Date()
    })

    return NextResponse.json({
      suggestion: suggestion.content,
      tokens: suggestion.tokens,
      type
    })

  } catch (error) {
    console.error('AI generation error:', error)
    return NextResponse.json(
      { error: 'Failed to generate suggestion' },
      { status: 500 }
    )
  }
}
```

### **2. Добавление мониторинга**

Создать файл `apps/web/src/lib/monitoring.ts`:

```typescript
interface UsageLog {
  type: string
  tokens: number
  timestamp: Date
  ip?: string
  userAgent?: string
}

export async function logUsage(log: UsageLog) {
  // Сохранение в базу данных
  try {
    // await prisma.usageLog.create({
    //   data: {
    //     type: log.type,
    //     tokens: log.tokens,
    //     timestamp: log.timestamp,
    //     ip: log.ip,
    //     userAgent: log.userAgent
    //   }
    // })
    
    // Или просто в консоль для демо
    console.log('Usage logged:', log)
  } catch (error) {
    console.error('Failed to log usage:', error)
  }
}

export async function checkRateLimit(ip: string): Promise<boolean> {
  // Проверка лимитов по IP
  // Можно использовать Redis или базу данных
  return false // Пока отключено
}
```

### **3. Обновление схемы базы данных**

В `packages/database/prisma/schema.prisma`:

```prisma
model UsageLog {
  id        String   @id @default(cuid())
  type      String
  tokens    Int
  timestamp DateTime @default(now())
  ip        String?
  userAgent String?
  
  @@index([timestamp])
  @@index([ip])
}
```

## 🚀 **Деплой для публичного доступа**

### **Вариант 1: Vercel (Рекомендуется)**

1. **Подготовка проекта:**
```bash
# В корне проекта
npm install -g vercel
vercel login
```

2. **Настройка переменных окружения:**
```bash
vercel env add DEEPSEEK_API_KEY
# Введите ваш API ключ: sk-1e898ddba737411e948af435d767e893
```

3. **Деплой:**
```bash
vercel --prod
```

4. **Получение URL:**
```
https://dm-copilot.vercel.app
```

### **Вариант 2: Netlify**

1. **Создать `netlify.toml`:**
```toml
[build]
  command = "npm run build"
  publish = "apps/web/.next"

[build.environment]
  NODE_VERSION = "18"

[[redirects]]
  from = "/api/*"
  to = "/.netlify/functions/api/:splat"
  status = 200
```

2. **Деплой через Netlify CLI:**
```bash
npm install -g netlify-cli
netlify login
netlify deploy --prod
```

### **Вариант 3: Railway**

1. **Подключить GitHub репозиторий**
2. **Настроить переменные окружения**
3. **Автоматический деплой**

## 📊 **Мониторинг и аналитика**

### **Метрики для отслеживания:**

1. **Использование AI:**
   - Количество запросов в день/час
   - Популярные типы подсказок
   - Среднее время ответа
   - Количество токенов

2. **Пользовательская активность:**
   - Уникальные посетители
   - Время на сайте
   - Популярные функции

3. **Технические метрики:**
   - Ошибки API
   - Время отклика
   - Доступность сервиса

### **Инструменты мониторинга:**

```typescript
// Добавить в API маршрут
import { Analytics } from '@vercel/analytics'

// Отправка событий
Analytics.track('ai_generation', {
  type: 'plot_twist',
  tokens: 150,
  success: true
})
```

## 🔒 **Безопасность**

### **Меры защиты:**

1. **Rate Limiting:**
```typescript
const rateLimit = {
  windowMs: 15 * 60 * 1000, // 15 минут
  max: 100 // максимум 100 запросов
}
```

2. **Валидация входных данных:**
```typescript
const validateRequest = (body: any) => {
  if (!body.type || !['plot_twist', 'npc_dialogue', 'scene_description'].includes(body.type)) {
    throw new Error('Invalid type')
  }
  if (body.context && body.context.length > 1000) {
    throw new Error('Context too long')
  }
}
```

3. **CORS настройки:**
```typescript
// В Next.js config
const nextConfig = {
  async headers() {
    return [
      {
        source: '/api/:path*',
        headers: [
          { key: 'Access-Control-Allow-Origin', value: '*' },
          { key: 'Access-Control-Allow-Methods', value: 'GET,POST,OPTIONS' }
        ]
      }
    ]
  }
}
```

## 💰 **Монетизация (опционально)**

### **Модели монетизации:**

1. **Freemium:**
   - Бесплатно: 10 запросов в день
   - Премиум: неограниченно

2. **Pay-per-use:**
   - $0.01 за 1000 токенов

3. **Подписка:**
   - $5/месяц - неограниченный доступ

### **Реализация лимитов:**

```typescript
async function checkUserLimits(userId: string) {
  const today = new Date().toISOString().split('T')[0]
  const usage = await prisma.usageLog.count({
    where: {
      userId,
      timestamp: {
        gte: new Date(today)
      }
    }
  })
  
  return usage < 10 // 10 запросов в день
}
```

## 📈 **Масштабирование**

### **Для большого количества пользователей:**

1. **Кэширование:**
```typescript
import { Redis } from 'ioredis'
const redis = new Redis(process.env.REDIS_URL)

// Кэш популярных запросов
const cacheKey = `ai:${type}:${hash(context)}`
const cached = await redis.get(cacheKey)
if (cached) return JSON.parse(cached)
```

2. **Очереди:**
```typescript
import { Queue } from 'bull'
const aiQueue = new Queue('ai-generation')

// Асинхронная обработка
await aiQueue.add('generate', { type, context })
```

3. **CDN:**
- Cloudflare для статических файлов
- Vercel Edge Functions для API

## 🎯 **Результат**

После настройки у вас будет:

✅ **Публичный URL** - доступен всем пользователям  
✅ **Централизованный AI** - один API ключ для всех  
✅ **Мониторинг** - статистика использования  
✅ **Безопасность** - защита от злоупотреблений  
✅ **Масштабируемость** - готовность к росту  

**Пример публичного URL:**
```
https://dm-copilot.vercel.app
```

Теперь любой пользователь может открыть ссылку и использовать AI без настройки API ключей! 🚀
