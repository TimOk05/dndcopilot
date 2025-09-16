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
            console.warn(`Failed to load icon ${iconName}:`, error);
            return this.getFallbackIcon(iconName, className);
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

    // Получить fallback иконку (эмодзи)
    getFallbackIcon(iconName, className) {
        const fallbackMap = {
            'enemy': '👹',
            'potion': '🧪',
            'initiative': '⚡',
            'dice': '🎲',
            'hero': '🧙‍♂️',
            'skull': '💀',
            'crystal-ball-magic-svgrepo-com': '🔮',
            'description': '📝'
        };

        const wrapper = document.createElement('span');
        wrapper.className = className;
        wrapper.textContent = fallbackMap[iconName] || '❓';
        return wrapper;
    }

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
        
        // Fallback эмодзи
        const fallbackMap = {
            'enemy': '👹',
            'potion': '🧪',
            'initiative': '⚡',
            'dice': '🎲',
            'hero': '🧙‍♂️',
            'skull': '💀',
            'crystal-ball-magic-svgrepo-com': '🔮',
            'description': '📝'
        };
        
        return `<span class="${className}">${fallbackMap[iconName] || '❓'}</span>`;
    }

    // Предзагрузить все иконки
    async preloadIcons() {
        const iconNames = [
            'enemy', 'potion', 'initiative', 'dice', 
            'hero', 'skull', 'crystal-ball-magic-svgrepo-com', 'description'
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
document.addEventListener('DOMContentLoaded', async () => {
    await window.iconManager.preloadIcons();
    console.log('Icons preloaded');
});
