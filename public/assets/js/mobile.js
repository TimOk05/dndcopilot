/**
 * JavaScript для новой мобильной версии D&D Copilot
 */

class MobileDnDApp {
    constructor() {
        this.apiUrl = '/api/mobile-api.php';
        this.init();
    }

    init() {
        this.bindEvents();
        this.setupServiceWorker();
    }

    bindEvents() {
        // Генератор персонажей
        document.getElementById('characterForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.generateCharacter();
        });

        // Генератор таверн
        document.getElementById('tavernForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.generateTaverns();
        });

        // Генератор зелий
        document.getElementById('potionForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.generatePotions();
        });

        // Генератор врагов
        document.getElementById('enemyForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.generateEnemies();
        });

        // AI Чат
        document.getElementById('chatForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.sendChatMessage();
        });
    }

    async makeApiCall(action, data = {}) {
        try {
            this.showLoading(true);

            const formData = new FormData();
            formData.append('action', action);

            // Добавляем параметры
            Object.keys(data).forEach(key => {
                formData.append(key, data[key]);
            });

            const response = await fetch(this.apiUrl, {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.text();

            // Парсим JSON, игнорируя возможные дополнительные символы
            let jsonData;
            try {
                jsonData = JSON.parse(result);
            } catch (e) {
                // Если не удалось распарсить весь ответ, попробуем найти JSON в середине
                const jsonMatch = result.match(/\{.*\}/);
                if (jsonMatch) {
                    jsonData = JSON.parse(jsonMatch[0]);
                } else {
                    throw new Error('Не удалось распарсить ответ сервера');
                }
            }

            return jsonData;
        } catch (error) {
            console.error('API Error:', error);
            return {
                success: false,
                error: error.message
            };
        } finally {
            this.showLoading(false);
        }
    }

    async generateCharacter() {
        const form = document.getElementById('characterForm');
        const formData = new FormData(form);

        const data = {
            race: formData.get('race'),
            class: formData.get('class'),
            level: formData.get('level'),
            use_ai: 'on' // AI всегда включен
        };

        const result = await this.makeApiCall('generate_character', data);
        this.displayCharacterResult(result);
    }

    async generateTaverns() {
        const form = document.getElementById('tavernForm');
        const formData = new FormData(form);

        const data = {
            count: formData.get('count'),
            use_ai: 'on' // AI всегда включен
        };

        const result = await this.makeApiCall('generate_taverns', data);
        this.displayTavernResult(result);
    }

    async generatePotions() {
        const form = document.getElementById('potionForm');
        const formData = new FormData(form);

        const data = {
            rarity: formData.get('rarity'),
            count: formData.get('count'),
            use_ai: 'on' // AI всегда включен
        };

        const result = await this.makeApiCall('generate_potions', data);
        this.displayPotionResult(result);
    }

    async generateEnemies() {
        const form = document.getElementById('enemyForm');
        const formData = new FormData(form);

        const data = {
            threat_level: formData.get('threat_level'),
            count: formData.get('count'),
            use_ai: 'on' // AI всегда включен
        };

        const result = await this.makeApiCall('generate_enemy', data);
        this.displayEnemyResult(result);
    }

    async sendChatMessage() {
        const question = document.getElementById('chatQuestion').value.trim();

        if (!question) {
            this.showError('Введите вопрос');
            return;
        }

        const data = {
            question: question,
            use_ai: 'on' // AI всегда включен
        };

        const result = await this.makeApiCall('ai_chat', data);
        this.displayChatResult(result);
    }

    displayCharacterResult(result) {
            const resultDiv = document.getElementById('characterResult');
            const contentDiv = document.getElementById('characterContent');

            if (result.success && result.character) {
                const char = result.character;
                contentDiv.innerHTML = `
                <div class="character-card">
                    <h4>${char.name}</h4>
                    <p><strong>Раса:</strong> ${char.race}</p>
                    <p><strong>Класс:</strong> ${char.class}</p>
                    <p><strong>Уровень:</strong> ${char.level}</p>
                    <p><strong>Мировоззрение:</strong> ${char.alignment}</p>
                    <p><strong>Пол:</strong> ${char.gender}</p>
                    <p><strong>Профессия:</strong> ${char.occupation}</p>
                    
                    <div class="character-stats">
                        <div class="stat-item">
                            <div class="stat-label">СИЛ</div>
                            <div class="stat-value">${char.abilities.str}</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-label">ЛОВ</div>
                            <div class="stat-value">${char.abilities.dex}</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-label">ТЕЛ</div>
                            <div class="stat-value">${char.abilities.con}</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-label">ИНТ</div>
                            <div class="stat-value">${char.abilities.int}</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-label">МДР</div>
                            <div class="stat-value">${char.abilities.wis}</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-label">ХАР</div>
                            <div class="stat-value">${char.abilities.cha}</div>
                        </div>
                    </div>
                    
                    <p><strong>Хиты:</strong> ${char.hit_points}</p>
                    <p><strong>Класс брони:</strong> ${char.armor_class}</p>
                    <p><strong>Скорость:</strong> ${char.speed} футов</p>
                    <p><strong>Инициатива:</strong> ${char.initiative}</p>
                    <p><strong>Бонус мастерства:</strong> +${char.proficiency_bonus}</p>
                    <p><strong>Бонус атаки:</strong> +${char.attack_bonus}</p>
                    <p><strong>Урон:</strong> ${char.damage}</p>
                    <p><strong>Основное оружие:</strong> ${char.main_weapon}</p>
                    
                    <p><strong>Описание:</strong> ${char.description}</p>
                    ${char.ai_description ? `<p><strong>AI Описание:</strong> ${char.ai_description}</p>` : ''}
                </div>
            `;
        } else {
            contentDiv.innerHTML = `<p class="error">Ошибка: ${result.error || result.message || 'Неизвестная ошибка'}</p>`;
        }

        resultDiv.style.display = 'block';
        resultDiv.scrollIntoView({ behavior: 'smooth' });
    }

    displayTavernResult(result) {
        const resultDiv = document.getElementById('tavernResult');
        const contentDiv = document.getElementById('tavernContent');

        if (result.success && result.taverns) {
            let html = '';
            result.taverns.forEach((tavern, index) => {
                html += `
                    <div class="tavern-card">
                        <h4>${tavern.name}</h4>
                        <p><strong>Местоположение:</strong> ${tavern.location.text_ru}</p>
                        <p><strong>Владелец:</strong> ${tavern.owner.name} (${tavern.owner.race})</p>
                        <p><strong>Характер владельца:</strong> ${tavern.owner.personality}</p>
                        <p><strong>Прошлое:</strong> ${tavern.owner.background}</p>
                        <p><strong>Персонал:</strong> ${tavern.staff.name} - ${tavern.staff.role}</p>
                        <p><strong>Характер персонала:</strong> ${tavern.staff.personality}</p>
                        <p><strong>Событие:</strong> ${tavern.event.name}</p>
                        <p><strong>Описание события:</strong> ${tavern.event.description}</p>
                        <p><strong>Блюдо дня:</strong> ${tavern.menu_item.name}</p>
                        <p><strong>Описание блюда:</strong> ${tavern.menu_item.description}</p>
                        <p><strong>Цена:</strong> ${tavern.menu_item.price}</p>
                        ${tavern.ai_description ? `<p><strong>AI Описание:</strong> ${tavern.ai_description}</p>` : ''}
                        ${index < result.taverns.length - 1 ? '<hr>' : ''}
                    </div>
                `;
            });
            contentDiv.innerHTML = html;
        } else {
            contentDiv.innerHTML = `<p class="error">Ошибка: ${result.error || result.message || 'Неизвестная ошибка'}</p>`;
        }

        resultDiv.style.display = 'block';
        resultDiv.scrollIntoView({ behavior: 'smooth' });
    }

    displayPotionResult(result) {
        const resultDiv = document.getElementById('potionResult');
        const contentDiv = document.getElementById('potionContent');

        if (result.success && result.potions) {
            let html = '';
            result.potions.forEach((potion, index) => {
                html += `
                    <div class="potion-card">
                        <h4>${potion.name}</h4>
                        <p><strong>Редкость:</strong> ${potion.rarity}</p>
                        <p><strong>Тип:</strong> ${potion.type}</p>
                        <p><strong>Эффект:</strong> ${potion.effect}</p>
                        <p><strong>Описание:</strong> ${potion.description}</p>
                        <p><strong>Цена:</strong> ${potion.price}</p>
                        <p><strong>Вес:</strong> ${potion.weight}</p>
                        ${potion.ai_description ? `<p><strong>AI Описание:</strong> ${potion.ai_description}</p>` : ''}
                        ${index < result.potions.length - 1 ? '<hr>' : ''}
                    </div>
                `;
            });
            contentDiv.innerHTML = html;
        } else {
            contentDiv.innerHTML = `<p class="error">Ошибка: ${result.error || result.message || 'Неизвестная ошибка'}</p>`;
        }

        resultDiv.style.display = 'block';
        resultDiv.scrollIntoView({ behavior: 'smooth' });
    }

    displayEnemyResult(result) {
        const resultDiv = document.getElementById('enemyResult');
        const contentDiv = document.getElementById('enemyContent');

        if (result.success && result.enemies) {
            let html = '';
            result.enemies.forEach((enemy, index) => {
                html += `
                    <div class="enemy-card">
                        <h4>${enemy.name}</h4>
                        <p><strong>Тип:</strong> ${enemy.type}</p>
                        <p><strong>Размер:</strong> ${enemy.size}</p>
                        <p><strong>Мировоззрение:</strong> ${enemy.alignment}</p>
                        <p><strong>Хиты:</strong> ${enemy.hit_points}</p>
                        <p><strong>Класс брони:</strong> ${enemy.armor_class}</p>
                        <p><strong>Скорость:</strong> ${enemy.speed}</p>
                        <p><strong>Описание:</strong> ${enemy.description}</p>
                        ${enemy.ai_description ? `<p><strong>AI Описание:</strong> ${enemy.ai_description}</p>` : ''}
                        ${index < result.enemies.length - 1 ? '<hr>' : ''}
                    </div>
                `;
            });
            contentDiv.innerHTML = html;
        } else {
            contentDiv.innerHTML = `<p class="error">Ошибка: ${result.error || result.message || 'Неизвестная ошибка'}</p>`;
        }

        resultDiv.style.display = 'block';
        resultDiv.scrollIntoView({ behavior: 'smooth' });
    }

    displayChatResult(result) {
        const resultDiv = document.getElementById('chatResult');
        const contentDiv = document.getElementById('chatContent');

        if (result.success && result.response) {
            contentDiv.innerHTML = `
                <div class="chat-response">
                    <p>${result.response}</p>
                </div>
            `;
        } else {
            contentDiv.innerHTML = `<p class="error">Ошибка: ${result.error || result.message || 'Неизвестная ошибка'}</p>`;
        }

        resultDiv.style.display = 'block';
        resultDiv.scrollIntoView({ behavior: 'smooth' });
    }

    showLoading(show) {
        const loading = document.getElementById('loading');
        if (show) {
            loading.classList.add('show');
        } else {
            loading.classList.remove('show');
        }
    }

    showError(message) {
        // Создаем временное уведомление об ошибке
        const notification = document.createElement('div');
        notification.className = 'error-notification';
        notification.textContent = message;

        Object.assign(notification.style, {
            position: 'fixed',
            top: '80px',
            right: '20px',
            background: '#ff4444',
            color: 'white',
            padding: '12px 16px',
            borderRadius: 'var(--border-radius)',
            boxShadow: 'var(--shadow)',
            zIndex: '1001',
            fontSize: '0.9rem',
            opacity: '0',
            transform: 'translateX(100%)',
            transition: 'all 0.3s ease'
        });

        document.body.appendChild(notification);

        setTimeout(() => {
            notification.style.opacity = '1';
            notification.style.transform = 'translateX(0)';
        }, 100);

        setTimeout(() => {
            notification.style.opacity = '0';
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 3000);
    }

    setupServiceWorker() {
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js')
                .then(registration => {
                    console.log('Service Worker зарегистрирован:', registration);
                })
                .catch(error => {
                    console.log('Ошибка регистрации Service Worker:', error);
                });
        }
    }
}

// Инициализация при загрузке страницы
document.addEventListener('DOMContentLoaded', () => {
    window.mobileApp = new MobileDnDApp();
    console.log('🎮 D&D Copilot Mobile App загружен');
});