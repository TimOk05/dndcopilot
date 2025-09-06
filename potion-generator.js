class PotionGenerator {
    constructor() {
        this.baseUrl = 'https://www.dnd5eapi.co/api';
        this.apiUrl = '/api/generate-potions.php';
        this.initializeEventListeners();
    }

    initializeEventListeners() {
        const generateBtn = document.getElementById('generate-potions');
        if (generateBtn) {
            generateBtn.addEventListener('click', () => this.generatePotions());
        }

        const countInput = document.getElementById('potion-count');
        if (countInput) {
            countInput.addEventListener('input', (e) => {
                const value = parseInt(e.target.value);
                if (value > 20) e.target.value = '20';
                if (value < 1) e.target.value = '1';
            });
        }
    }

    async generatePotions() {
        const options = this.getUserOptions();
        if (!options) return;

        this.showLoading(true);
        
        try {
            // Используем наш PHP API вместо прямого обращения к D&D API
            const potions = await this.fetchPotionsFromAPI(options);
            this.displayResults(potions);
        } catch (error) {
            this.handleError(error);
        } finally {
            this.showLoading(false);
        }
    }

    getUserOptions() {
        const countInput = document.getElementById('potion-count');
        const typeSelect = document.getElementById('potion-type');
        const raritySelect = document.getElementById('potion-rarity');

        if (!countInput || !typeSelect || !raritySelect) {
            this.showError('Не удалось найти элементы формы');
            return null;
        }

        const count = parseInt(countInput.value);
        if (isNaN(count) || count < 1 || count > 20) {
            this.showError('Количество зелий должно быть от 1 до 20');
            return null;
        }

        return {
            count,
            type: typeSelect.value === 'all' ? '' : typeSelect.value,
            rarity: raritySelect.value === 'all' ? '' : raritySelect.value
        };
    }

    async fetchPotionsFromAPI(options) {
        try {
            console.log('Запрос зелий с параметрами:', options);
            
            // Создаем FormData для POST запроса
            const formData = new FormData();
            formData.append('count', options.count);
            if (options.type) formData.append('type', options.type);
            if (options.rarity) formData.append('rarity', options.rarity);

            const response = await fetch(this.apiUrl, {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();
            console.log('Ответ от API:', result);

            if (!result.success) {
                throw new Error(result.error || 'Неизвестная ошибка API');
            }

            if (!result.data || result.data.length === 0) {
                throw new Error('Не найдено зелий по указанным критериям');
            }

            return result.data;

        } catch (error) {
            console.error('Error fetching potions:', error);
            throw new Error(`Не удалось получить данные о зельях: ${error.message}`);
        }
    }

    displayResults(potions) {
        const resultsContainer = document.getElementById('potion-results');
        if (!resultsContainer) return;

        if (potions.length === 0) {
            resultsContainer.innerHTML = '<p class="no-results">Не найдено зелий по указанным критериям</p>';
            return;
        }

        const potionsHTML = potions.map(potion => this.createPotionCard(potion)).join('');
        resultsContainer.innerHTML = potionsHTML;
    }

    createPotionCard(potion) {
        const rarity = potion.rarity || 'Неизвестно';
        const cost = potion.cost || 'Не указана';
        const description = potion.description || 'Описание отсутствует';
        const type = potion.type || 'Неизвестный тип';
        const icon = potion.icon || '🧪';

        // Очищаем название редкости для CSS класса
        const rarityClass = rarity.toLowerCase().replace(/\s+/g, '-');

        return `
            <div class="potion-card">
                <div class="potion-header">
                    <span class="potion-icon">${icon}</span>
                    <h3 class="potion-name">${potion.name}</h3>
                </div>
                <div class="potion-details">
                    <p><strong>Тип:</strong> ${type}</p>
                    <p><strong>Редкость:</strong> <span class="rarity-${rarityClass}">${rarity}</span></p>
                    <p><strong>Стоимость:</strong> ${cost}</p>
                </div>
                <div class="potion-description">
                    <p>${description}</p>
                </div>
                ${potion.effects && potion.effects.length > 0 ? `
                <div class="potion-effects">
                    <strong>Эффекты:</strong> ${potion.effects.join(', ')}
                </div>
                ` : ''}
            </div>
        `;
    }

    showLoading(show) {
        const loadingElement = document.getElementById('loading');
        const generateBtn = document.getElementById('generate-potions');
        
        if (loadingElement) {
            loadingElement.style.display = show ? 'block' : 'none';
        }
        
        if (generateBtn) {
            generateBtn.disabled = show;
            generateBtn.textContent = show ? 'Генерация...' : 'Сгенерировать зелья';
        }
    }

    handleError(error) {
        console.error('Potion generation error:', error);
        const errorMessage = error instanceof Error ? error.message : 'Произошла неизвестная ошибка';
        this.showError(errorMessage);
    }

    showError(message) {
        const resultsContainer = document.getElementById('potion-results');
        if (resultsContainer) {
            resultsContainer.innerHTML = `<div class="error-message">${message}</div>`;
        }
    }
}

// Инициализация при загрузке страницы
document.addEventListener('DOMContentLoaded', () => {
    console.log('Инициализация генератора зелий...');
    new PotionGenerator();
    console.log('Генератор зелий готов к работе!');
});
