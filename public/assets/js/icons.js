// SVG Icons Manager for DnD Copilot
class IconManager {
    constructor() {
        this.iconCache = new Map();
        this.iconPath = '/icons/';
    }

    // Получить SVG иконку по имени
    async getIcon(iconName, className = 'svg-icon') {
        console.log(`Getting icon: ${iconName} with class: ${className}`);

        // Проверяем кэш
        if (this.iconCache.has(iconName)) {
            console.log(`Icon ${iconName} found in cache`);
            return this.createIconElement(this.iconCache.get(iconName), className);
        }

        try {
            // Загружаем SVG файл
            const url = `${this.iconPath}${iconName}.svg`;
            console.log(`Fetching icon from: ${url}`);

            const response = await fetch(url);
            if (!response.ok) {
                throw new Error(`Icon ${iconName} not found (HTTP ${response.status})`);
            }

            const svgContent = await response.text();
            console.log(`Loaded SVG content for ${iconName}:`, svgContent.substring(0, 100) + '...');

            // Проверяем, что содержимое является валидным SVG
            if (!svgContent.includes('<svg') || !svgContent.includes('</svg>')) {
                throw new Error(`Invalid SVG content for ${iconName}`);
            }

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

        // Проверяем, что SVG содержимое корректно
        if (!svgContent || svgContent.trim() === '') {
            console.error('Empty SVG content');
            return wrapper;
        }

        try {
            wrapper.innerHTML = svgContent;

            // Удаляем атрибуты размера из SVG, чтобы использовать CSS
            const svg = wrapper.querySelector('svg');
            if (svg) {
                svg.removeAttribute('width');
                svg.removeAttribute('height');
                // Убеждаемся, что SVG имеет правильные стили
                svg.style.width = '1em';
                svg.style.height = '1em';
                svg.style.display = 'inline-block';
            } else {
                console.error('No SVG element found in content:', svgContent);
            }
        } catch (error) {
            console.error('Error creating icon element:', error);
            wrapper.innerHTML = '';
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

// Дополнительная инициализация через небольшую задержку
setTimeout(async() => {
    if (document.readyState === 'complete') {
        console.log('Page fully loaded, checking for missed icons...');
        try {
            await replaceDataIconElements();
        } catch (error) {
            console.error('Error in delayed icon initialization:', error);
        }
    }
}, 1000);

// Инициализация при загрузке страницы
document.addEventListener('DOMContentLoaded', async() => {
    console.log('DOM loaded, initializing icons...');

    try {
        await window.iconManager.preloadIcons();
        console.log('Icons preloaded successfully');

        // Заменяем все элементы с data-icon атрибутами
        replaceDataIconElements();

        console.log('Icons initialization completed');
    } catch (error) {
        console.error('Error during icons initialization:', error);
    }
});

// Функция для замены элементов с data-icon атрибутами
async function replaceDataIconElements() {
    const elements = document.querySelectorAll('[data-icon]');
    console.log(`Found ${elements.length} elements with data-icon attributes`);

    for (const element of elements) {
        const iconName = element.getAttribute('data-icon');
        console.log(`Processing icon: ${iconName} for element:`, element);

        if (iconName) {
            try {
                const iconElement = await window.iconManager.getIcon(iconName, element.className);
                if (iconElement) {
                    console.log(`Successfully loaded icon ${iconName}:`, iconElement);
                    // Заменяем содержимое элемента на иконку
                    element.innerHTML = iconElement.innerHTML;
                } else {
                    console.warn(`No icon element returned for ${iconName}`);
                }
            } catch (error) {
                console.error(`Failed to load icon ${iconName}:`, error);
                // Оставляем элемент пустым при ошибке
                element.innerHTML = '';
            }
        }
    }
}