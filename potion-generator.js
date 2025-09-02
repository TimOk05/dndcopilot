class PotionGenerator {
    constructor() {
        this.baseUrl = 'https://www.dnd5eapi.co/api';
        this.potionTypes = {
            healing: 'healing',
            poison: 'poison', 
            buff: 'buff',
            utility: 'utility',
            all: 'all'
        };

        this.rarityLevels = {
            common: 'common',
            uncommon: 'uncommon',
            rare: 'rare',
            very_rare: 'very-rare',
            legendary: 'legendary'
        };

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
            const potions = await this.fetchPotions(options);
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
            type: typeSelect.value,
            rarity: raritySelect.value
        };
    }

    async fetchPotions(options) {
        try {
            // Сначала получаем все зелья
            const response = await fetch(`${this.baseUrl}/magic-items`);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            const magicItems = data.results || [];

            // Фильтруем зелья по типу и редкости
            let filteredPotions = await this.filterPotions(magicItems, options);
            
            // Если не хватает зелий, добавляем случайные
            if (filteredPotions.length < options.count) {
                const additionalPotions = await this.getRandomPotions(magicItems, options.count - filteredPotions.length);
                filteredPotions = [...filteredPotions, ...additionalPotions];
            }

            // Ограничиваем количество
            return filteredPotions.slice(0, options.count);

        } catch (error) {
            console.error('Error fetching potions:', error);
            throw new Error('Не удалось получить данные о зельях');
        }
    }

    async filterPotions(magicItems, options) {
        const potions = [];
        
        for (const item of magicItems) {
            if (potions.length >= options.count) break;
            
            try {
                const itemDetails = await this.fetchItemDetails(item.index);
                
                if (this.matchesFilters(itemDetails, options)) {
                    potions.push(itemDetails);
                }
            } catch (error) {
                console.warn(`Failed to fetch details for ${item.index}:`, error);
                continue;
            }
        }

        return potions;
    }

    async fetchItemDetails(index) {
        const response = await fetch(`${this.baseUrl}/magic-items/${index}`);
        if (!response.ok) {
            throw new Error(`Failed to fetch item details for ${index}`);
        }
        return await response.json();
    }

    matchesFilters(potion, options) {
        // Проверяем редкость
        if (options.rarity !== 'all' && potion.rarity?.name !== options.rarity) {
            return false;
        }

        // Проверяем тип (по названию и описанию)
        if (options.type !== 'all') {
            const name = potion.name.toLowerCase();
            const description = potion.desc?.join(' ').toLowerCase() || '';
            
            switch (options.type) {
                case 'healing':
                    return name.includes('healing') || name.includes('cure') || 
                           description.includes('heal') || description.includes('cure');
                case 'poison':
                    return name.includes('poison') || description.includes('poison') ||
                           description.includes('damage') || description.includes('harm');
                case 'buff':
                    return name.includes('strength') || name.includes('power') ||
                           description.includes('enhance') || description.includes('boost') ||
                           description.includes('advantage');
                case 'utility':
                    return name.includes('utility') || name.includes('tool') ||
                           description.includes('use') || description.includes('tool');
                default:
                    return true;
            }
        }

        return true;
    }

    async getRandomPotions(magicItems, count) {
        const randomItems = this.shuffleArray([...magicItems]).slice(0, count);
        const potions = [];

        for (const item of randomItems) {
            try {
                const itemDetails = await this.fetchItemDetails(item.index);
                potions.push(itemDetails);
            } catch (error) {
                console.warn(`Failed to fetch random item ${item.index}:`, error);
                continue;
            }
        }

        return potions;
    }

    shuffleArray(array) {
        const shuffled = [...array];
        for (let i = shuffled.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [shuffled[i], shuffled[j]] = [shuffled[j], shuffled[i]];
        }
        return shuffled;
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
        const rarity = potion.rarity?.name || 'Неизвестно';
        const cost = potion.cost ? `${potion.cost.quantity} ${potion.cost.unit}` : 'Не указана';
        const description = potion.desc?.join(' ') || 'Описание отсутствует';

        return `
            <div class="potion-card">
                <h3 class="potion-name">${potion.name}</h3>
                <div class="potion-details">
                    <p><strong>Редкость:</strong> <span class="rarity-${rarity.toLowerCase().replace(' ', '-')}">${rarity}</span></p>
                    <p><strong>Стоимость:</strong> ${cost}</p>
                </div>
                <div class="potion-description">
                    <p>${description}</p>
                </div>
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
    new PotionGenerator();
});
