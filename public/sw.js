// ===== SERVICE WORKER ДЛЯ PWA =====

const CACHE_NAME = 'dnd-chat-v1.0.0';
const urlsToCache = [
    './',
    './index.php',
    './template.html',
    './assets/js/mobile.js',
    './favicon.svg',
    './offline.html',
    './login.php',
    '../app/Controllers/stats.php',
    '../app/Controllers/admin.php',
    'https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&family=UnifrakturCook:wght@700&family=IM+Fell+English+SC&display=swap',
    'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?auto=format&fit=crop&w=1500&q=80',
    'https://images.unsplash.com/photo-1506744038136-46273834b3fb?auto=format&fit=crop&w=1500&q=80',
    'https://www.transparenttextures.com/patterns/old-mathematics.png'
];

// Установка Service Worker
self.addEventListener('install', function(event) {
    event.waitUntil(
        caches.open(CACHE_NAME)
        .then(function(cache) {
            console.log('Opened cache');
            return cache.addAll(urlsToCache);
        })
    );
});

// Активация Service Worker
self.addEventListener('activate', function(event) {
    event.waitUntil(
        caches.keys().then(function(cacheNames) {
            return Promise.all(
                cacheNames.map(function(cacheName) {
                    if (cacheName !== CACHE_NAME) {
                        console.log('Deleting old cache:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
});

// Перехват запросов
self.addEventListener('fetch', function(event) {
    event.respondWith(
        caches.match(event.request)
        .then(function(response) {
            // Возвращаем кэшированный ответ, если он есть
            if (response) {
                return response;
            }

            // Клонируем запрос, так как он может быть использован только один раз
            const fetchRequest = event.request.clone();

            return fetch(fetchRequest).then(
                function(response) {
                    // Проверяем, что ответ валидный
                    if (!response || response.status !== 200 || response.type !== 'basic') {
                        return response;
                    }

                    // Клонируем ответ, так как он может быть использован только один раз
                    const responseToCache = response.clone();

                    caches.open(CACHE_NAME)
                        .then(function(cache) {
                            cache.put(event.request, responseToCache);
                        });

                    return response;
                }
            );
        })
        .catch(function() {
            // Возвращаем офлайн страницу для навигационных запросов
            if (event.request.mode === 'navigate') {
                return caches.match('./offline.html');
            }
        })
    );
});

// Обработка push-уведомлений
self.addEventListener('push', function(event) {
    if (event.data) {
        const data = event.data.json();
        const options = {
            body: data.body,
            icon: './favicon.svg',
            badge: './favicon.svg',
            vibrate: [100, 50, 100],
            data: {
                dateOfArrival: Date.now(),
                primaryKey: 1
            },
            actions: [{
                    action: 'explore',
                    title: 'Открыть',
                    icon: './favicon.svg'
                },
                {
                    action: 'close',
                    title: 'Закрыть',
                    icon: './favicon.svg'
                }
            ]
        };

        event.waitUntil(
            self.registration.showNotification(data.title, options)
        );
    }
});

// Обработка кликов по уведомлениям
self.addEventListener('notificationclick', function(event) {
    event.notification.close();

    if (event.action === 'explore') {
        event.waitUntil(
            clients.openWindow('./')
        );
    }
});

// Обработка фоновой синхронизации
self.addEventListener('sync', function(event) {
    if (event.tag === 'background-sync') {
        event.waitUntil(doBackgroundSync());
    }
});

function doBackgroundSync() {
    // Здесь можно добавить логику фоновой синхронизации
    console.log('Background sync triggered');
}

// Обработка сообщений от основного потока
self.addEventListener('message', function(event) {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});