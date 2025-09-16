// SVG Icons Manager for DnD Copilot
class IconManager {
    constructor() {
        this.iconCache = new Map();
        this.iconPath = '/icons/';
    }

    // Получить SVG иконку по имени
    async getIcon(iconName, className = 'svg-icon') {
        // Проверяем кэш
        if (this.iconCache.has(iconName)) {
            return this.createIconElement(this.iconCache.get(iconName), className);
        }

        try {
            // Загружаем SVG файл
            const response = await fetch(`${this.iconPath}${iconName}.svg`);
            if (!response.ok) {
                throw new Error(`Icon ${iconName} not found`);
            }

            const svgContent = await response.text();
            this.iconCache.set(iconName, svgContent);

            return this.createIconElement(svgContent, className);
        } catch (error) {
            console.error(`Failed to load icon ${iconName}:`, error);
            throw error; // Не используем fallback согласно политике NO FALLBACK
        }
    }

    // Создать элемент иконки
    createIconElement(svgContent, className) {
        const wrapper = document.createElement('span');
        wrapper.className = className;
        wrapper.innerHTML = svgContent;

        // Удаляем атрибуты размера из SVG, чтобы использовать CSS
        const svg = wrapper.querySelector('svg');
        if (svg) {
            svg.removeAttribute('width');
            svg.removeAttribute('height');
        }

        return wrapper;
    }

    // Fallback система удалена согласно политике NO FALLBACK

    // Синхронно получить иконку для использования в innerHTML
    getIconHTML(iconName, className = 'svg-icon') {
        if (this.iconCache.has(iconName)) {
            const svgContent = this.iconCache.get(iconName);
            // Удаляем XML declaration и упрощаем SVG для встраивания
            let cleanSvg = svgContent
                .replace(/<\?xml[^>]*>/g, '')
                .replace(/<!DOCTYPE[^>]*>/g, '')
                .replace(/<!--[^>]*-->/g, '')
                .trim();

            // Добавляем класс к SVG
            cleanSvg = cleanSvg.replace('<svg', `<svg class="${className}"`);
            return cleanSvg;
        }

        // Fallback система удалена согласно политике NO FALLBACK
        throw new Error(`Icon ${iconName} not loaded in cache`);
    }

    // Предзагрузить все иконки
    async preloadIcons() {
        const iconNames = [
            'enemy', 'potion', 'initiative', 'dice',
            'hero', 'skull', 'crystal-ball-magic-svgrepo-com', 'description', 'loading'
        ];

        const loadPromises = iconNames.map(name => this.getIcon(name));
        await Promise.allSettled(loadPromises);
    }

    // Заменить эмодзи на SVG иконки в существующих элементах
    replaceEmojisWithSVG() {
        // Карта замен эмодзи -> иконка
        const emojiMap = {
            '👹': 'enemy',
            '🧪': 'potion',
            '⚡': 'initiative',
            '🎲': 'dice',
            '🧙‍♂️': 'hero',
            '💀': 'skull',
            '🔮': 'crystal-ball-magic-svgrepo-com',
            '📝': 'description',
            '🏷️': 'description',
            '📊': 'description',
            '🛡️': 'description',
            '📖': 'description',
            '🎒': 'description'
        };

        // Находим все элементы с эмодзи и заменяем их
        document.querySelectorAll('*').forEach(element => {
            if (element.children.length === 0) { // Только текстовые узлы
                let text = element.textContent;
                let hasEmoji = false;

                for (const [emoji, iconName] of Object.entries(emojiMap)) {
                    if (text.includes(emoji)) {
                        hasEmoji = true;
                        const iconHTML = this.getIconHTML(iconName, 'svg-icon');
                        text = text.replace(new RegExp(emoji, 'g'), iconHTML);
                    }
                }

                if (hasEmoji) {
                    element.innerHTML = text;
                }
            }
        });
    }
}

// Создаем глобальный экземпляр
window.iconManager = new IconManager();

// Инициализация при загрузке страницы
document.addEventListener('DOMContentLoaded', async() => {
    await window.iconManager.preloadIcons();

    // Заменяем все элементы с data-icon атрибутами
    replaceDataIconElements();

    console.log('Icons preloaded and replaced');
});

// Функция для замены элементов с data-icon атрибутами
async function replaceDataIconElements() {
    const elements = document.querySelectorAll('[data-icon]');

    for (const element of elements) {
        const iconName = element.getAttribute('data-icon');
        if (iconName) {
            try {
                const iconElement = await window.iconManager.getIcon(iconName, element.className);
                if (iconElement) {
                    // Заменяем содержимое элемента на иконку
                    element.innerHTML = iconElement.innerHTML;
                }
            } catch (error) {
                console.warn(`Failed to load icon ${iconName}:`, error);
            }
        }
    }
}