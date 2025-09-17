// Простая система загрузки SVG иконок
(function() {
    'use strict';

    // Кэш иконок
    const iconCache = {};

    // Функция для загрузки иконки
    async function loadIcon(iconName) {
        if (iconCache[iconName]) {
            return iconCache[iconName];
        }

        try {
            const response = await fetch(`/icons/${iconName}.svg`);
            if (!response.ok) {
                console.warn(`Icon ${iconName} not found`);
                return null;
            }

            const svgContent = await response.text();
            iconCache[iconName] = svgContent;
            return svgContent;
        } catch (error) {
            console.warn(`Error loading icon ${iconName}:`, error);
            return null;
        }
    }

    // Функция для замены элементов с data-icon
    async function replaceIcons() {
        const elements = document.querySelectorAll('[data-icon]');
        console.log(`Found ${elements.length} elements with data-icon`);

        for (const element of elements) {
            const iconName = element.getAttribute('data-icon');
            if (!iconName) continue;

            try {
                const svgContent = await loadIcon(iconName);
                if (svgContent) {
                    element.innerHTML = svgContent;
                    element.removeAttribute('data-icon');
                    console.log(`Loaded icon: ${iconName}`);
                } else {
                    console.warn(`Failed to load icon: ${iconName}`);
                }
            } catch (error) {
                console.error(`Error processing icon ${iconName}:`, error);
            }
        }
    }

    // Инициализация при загрузке DOM
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', replaceIcons);
    } else {
        replaceIcons();
    }

    // Дополнительная попытка через небольшую задержку
    setTimeout(replaceIcons, 500);

})();