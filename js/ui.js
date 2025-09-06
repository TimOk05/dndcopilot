/**
 * Модуль управления пользовательским интерфейсом
 * Обеспечивает взаимодействие с UI элементами
 */

class UIManager {
    constructor() {
        this.modals = new Map();
        this.forms = new Map();
        this.init();
    }

    init() {
        this.initializeModals();
        this.initializeForms();
        this.bindEvents();
    }

    /**
     * Инициализация модальных окон
     */
    initializeModals() {
        // Генератор персонажей
        const characterModal = document.getElementById('character-modal');
        if (characterModal) {
            this.modals.set('character', characterModal);
        }

        // Генератор противников
        const enemyModal = document.getElementById('enemy-modal');
        if (enemyModal) {
            this.modals.set('enemy', enemyModal);
        }

        // Генератор зелий
        const potionModal = document.getElementById('potion-modal');
        if (potionModal) {
            this.modals.set('potion', potionModal);
        }
    }

    /**
     * Инициализация форм
     */
    initializeForms() {
        // Форма генератора персонажей
        const characterForm = document.getElementById('character-form');
        if (characterForm) {
            this.forms.set('character', characterForm);
        }

        // Форма генератора противников
        const enemyForm = document.getElementById('enemy-form');
        if (enemyForm) {
            this.forms.set('enemy', enemyForm);
        }

        // Форма генератора зелий
        const potionForm = document.getElementById('potion-form');
        if (potionForm) {
            this.forms.set('potion', potionForm);
        }
    }

    /**
     * Привязка событий
     */
    bindEvents() {
        // Обработчики для кнопок открытия модальных окон
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-modal]')) {
                const modalName = e.target.getAttribute('data-modal');
                this.openModal(modalName);
            }
        });

        // Обработчики для кнопок закрытия модальных окон
        document.addEventListener('click', (e) => {
            if (e.target.matches('.modal-close, .modal-backdrop')) {
                this.closeModal();
            }
        });

        // Обработчики для форм
        document.addEventListener('submit', (e) => {
            if (e.target.matches('#character-form')) {
                e.preventDefault();
                this.handleCharacterForm(e.target);
            } else if (e.target.matches('#enemy-form')) {
                e.preventDefault();
                this.handleEnemyForm(e.target);
            } else if (e.target.matches('#potion-form')) {
                e.preventDefault();
                this.handlePotionForm(e.target);
            }
        });
    }

    /**
     * Открыть модальное окно
     */
    openModal(modalName) {
        const modal = this.modals.get(modalName);
        if (modal) {
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
            console.log(`Открыто модальное окно: ${modalName}`);
        }
    }

    /**
     * Закрыть модальное окно
     */
    closeModal() {
        this.modals.forEach((modal) => {
            modal.style.display = 'none';
        });
        document.body.style.overflow = 'auto';
        console.log('Закрыто модальное окно');
    }

    /**
     * Обработка формы генератора персонажей
     */
    async handleCharacterForm(form) {
        const formData = new FormData(form);
        const characterData = {
            race: formData.get('race'),
            class: formData.get('class'),
            level: formData.get('level'),
            gender: formData.get('gender')
        };

        console.log('Данные персонажа:', characterData);

        // Показываем индикатор загрузки
        this.showLoading('character');

        try {
            const result = await window.apiManager.generateCharacter(characterData);
            this.handleCharacterResult(result);
        } catch (error) {
            this.showError('Ошибка генерации персонажа: ' + error.message);
        } finally {
            this.hideLoading('character');
        }
    }

    /**
     * Обработка формы генератора противников
     */
    async handleEnemyForm(form) {
        const formData = new FormData(form);
        const enemyData = {
            count: formData.get('count'),
            difficulty: formData.get('difficulty'),
            type: formData.get('type')
        };

        console.log('Данные противников:', enemyData);
        this.showLoading('enemy');

        // Здесь будет логика генерации противников
        setTimeout(() => {
            this.hideLoading('enemy');
            this.showSuccess('Противники сгенерированы!');
        }, 2000);
    }

    /**
     * Обработка формы генератора зелий
     */
    async handlePotionForm(form) {
        const formData = new FormData(form);
        const count = parseInt(formData.get('count')) || 1;

        console.log('Количество зелий:', count);
        this.showLoading('potion');

        try {
            const result = await window.apiManager.generatePotions(count);
            this.handlePotionResult(result);
        } catch (error) {
            this.showError('Ошибка генерации зелий: ' + error.message);
        } finally {
            this.hideLoading('potion');
        }
    }

    /**
     * Обработка результата генерации персонажа
     */
    handleCharacterResult(result) {
        if (result.success) {
            this.showCharacterResult(result.data);
        } else {
            this.showError('Ошибка: ' + (result.error || 'Неизвестная ошибка'));
        }
    }

    /**
     * Обработка результата генерации зелий
     */
    handlePotionResult(result) {
        if (result.success) {
            this.showPotionResult(result.data);
        } else {
            this.showError('Ошибка: ' + (result.error || 'Неизвестная ошибка'));
        }
    }

    /**
     * Показать результат генерации персонажа
     */
    showCharacterResult(character) {
            const resultDiv = document.getElementById('character-result');
            if (resultDiv) {
                resultDiv.innerHTML = `
                <h3>${character.name || 'Персонаж'}</h3>
                <p><strong>Раса:</strong> ${character.race || 'Не указана'}</p>
                <p><strong>Класс:</strong> ${character.class || 'Не указан'}</p>
                <p><strong>Уровень:</strong> ${character.level || 'Не указан'}</p>
                <p><strong>Пол:</strong> ${character.gender || 'Не указан'}</p>
                ${character.description ? `<p><strong>Описание:</strong> ${character.description}</p>` : ''}
            `;
            resultDiv.style.display = 'block';
        }
    }
    
    /**
     * Показать результат генерации зелий
     */
    showPotionResult(potions) {
        const resultDiv = document.getElementById('potion-result');
        if (resultDiv) {
            let html = '<h3>Сгенерированные зелья:</h3>';
            potions.forEach((potion, index) => {
                html += `
                    <div class="potion-item">
                        <h4>${potion.name || `Зелье ${index + 1}`}</h4>
                        <p><strong>Тип:</strong> ${potion.type || 'Не указан'}</p>
                        <p><strong>Редкость:</strong> ${potion.rarity || 'Не указана'}</p>
                        ${potion.description ? `<p><strong>Описание:</strong> ${potion.description}</p>` : ''}
                        ${potion.effect ? `<p><strong>Эффект:</strong> ${potion.effect}</p>` : ''}
                    </div>
                `;
            });
            resultDiv.innerHTML = html;
            resultDiv.style.display = 'block';
        }
    }
    
    /**
     * Показать индикатор загрузки
     */
    showLoading(type) {
        const loadingElement = document.getElementById(`${type}-loading`);
        if (loadingElement) {
            loadingElement.style.display = 'block';
        }
    }
    
    /**
     * Скрыть индикатор загрузки
     */
    hideLoading(type) {
        const loadingElement = document.getElementById(`${type}-loading`);
        if (loadingElement) {
            loadingElement.style.display = 'none';
        }
    }
    
    /**
     * Показать сообщение об ошибке
     */
    showError(message) {
        console.error('UI Error:', message);
        // Здесь можно добавить показ уведомления
        alert(message);
    }
    
    /**
     * Показать сообщение об успехе
     */
    showSuccess(message) {
        console.log('UI Success:', message);
        // Здесь можно добавить показ уведомления
        alert(message);
    }
}

// Создаем глобальный экземпляр
window.uiManager = new UIManager();