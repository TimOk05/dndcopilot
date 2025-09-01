<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Тест API зелий D&D</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { margin: 20px 0; padding: 20px; border: 1px solid #ccc; border-radius: 8px; }
        .result { background: #f5f5f5; padding: 10px; margin: 10px 0; border-radius: 4px; }
        button { padding: 10px 20px; margin: 5px; cursor: pointer; }
        input, select { padding: 8px; margin: 5px; }
        .error { color: red; background: #ffe6e6; padding: 10px; border-radius: 4px; }
        .success { color: green; background: #e6ffe6; padding: 10px; border-radius: 4px; }
    </style>
</head>
<body>
    <h1>🧪 Тест API зелий D&D (Новая версия)</h1>
    
    <div class="test-section">
        <h3>Случайные зелья</h3>
        <label>Количество: <input type="number" id="count" value="3" min="1" max="10"></label>
        <label>Редкость: 
            <select id="rarity">
                <option value="">Любая</option>
                <option value="common">Обычное</option>
                <option value="uncommon">Необычное</option>
                <option value="rare">Редкое</option>
                <option value="very rare">Очень редкое</option>
                <option value="legendary">Легендарное</option>
            </select>
        </label>
        <label>Тип: 
            <select id="type">
                <option value="">Любой</option>
                <option value="Восстановление">Восстановление</option>
                <option value="Усиление">Усиление</option>
                <option value="Защита">Защита</option>
                <option value="Иллюзия">Иллюзия</option>
                <option value="Трансмутация">Трансмутация</option>
                <option value="Некромантия">Некромантия</option>
                <option value="Прорицание">Прорицание</option>
            </select>
        </label>
        <br>
        <button onclick="testRandomPotions()">🎲 Сгенерировать зелья</button>
        <div id="randomResult" class="result"></div>
    </div>
    
    <div class="test-section">
        <h3>Доступные редкости</h3>
        <button onclick="testRarities()">📋 Получить редкости</button>
        <div id="raritiesResult" class="result"></div>
    </div>
    
    <div class="test-section">
        <h3>Доступные типы</h3>
        <button onclick="testTypes()">🔍 Получить типы</button>
        <div id="typesResult" class="result"></div>
    </div>
    
    <div class="test-section">
        <h3>Зелья по типу</h3>
        <label>Тип: 
            <select id="typeFilter">
                <option value="Восстановление">Восстановление</option>
                <option value="Усиление">Усиление</option>
                <option value="Защита">Защита</option>
                <option value="Иллюзия">Иллюзия</option>
                <option value="Трансмутация">Трансмутация</option>
                <option value="Некромантия">Некромантия</option>
                <option value="Прорицание">Прорицание</option>
            </select>
        </label>
        <button onclick="testPotionsByType()">🔍 Найти по типу</button>
        <div id="typeResult" class="result"></div>
    </div>

    <script>
        async function testRandomPotions() {
            const count = document.getElementById('count').value;
            const rarity = document.getElementById('rarity').value;
            const type = document.getElementById('type').value;
            
            const params = new URLSearchParams();
            params.append('action', 'random');
            params.append('count', count);
            if (rarity) params.append('rarity', rarity);
            if (type) params.append('type', type);
            
            try {
                document.getElementById('randomResult').innerHTML = '<div class="loading">Загрузка...</div>';
                
                const response = await fetch(`api/generate-potions.php?${params.toString()}`);
                const data = await response.json();
                
                if (data.success) {
                    let html = '<div class="success">✅ Успешно получено зелий: ' + data.data.length + '</div>';
                    html += '<h4>Результат:</h4>';
                    data.data.forEach((potion, index) => {
                        html += `
                            <div style="border: 1px solid #ddd; padding: 10px; margin: 10px 0; border-radius: 4px; border-left: 4px solid ${potion.color}">
                                <h5>${potion.icon} ${potion.name}</h5>
                                <p><strong>Редкость:</strong> <span style="color: ${potion.color}">${potion.rarity}</span></p>
                                <p><strong>Тип:</strong> ${potion.icon} ${potion.type}</p>
                                <p><strong>Описание:</strong> ${potion.description}</p>
                                <p><strong>Стоимость:</strong> ${potion.value}</p>
                                <p><strong>Вес:</strong> ${potion.weight}</p>
                                <p><strong>Свойства:</strong> ${potion.properties.join(', ')}</p>
                            </div>
                        `;
                    });
                    document.getElementById('randomResult').innerHTML = html;
                } else {
                    document.getElementById('randomResult').innerHTML = `<div class="error">❌ Ошибка: ${data.error}</div>`;
                }
            } catch (error) {
                document.getElementById('randomResult').innerHTML = `<div class="error">❌ Ошибка сети: ${error.message}</div>`;
            }
        }
        
        async function testRarities() {
            try {
                document.getElementById('raritiesResult').innerHTML = '<div class="loading">Загрузка...</div>';
                
                const response = await fetch('api/generate-potions.php?action=rarities');
                const data = await response.json();
                
                if (data.success) {
                    document.getElementById('raritiesResult').innerHTML = `
                        <div class="success">✅ Успешно получены редкости</div>
                        <h4>Доступные редкости:</h4>
                        <ul>${data.data.map(rarity => `<li>${rarity}</li>`).join('')}</ul>
                    `;
                } else {
                    document.getElementById('raritiesResult').innerHTML = `<div class="error">❌ Ошибка: ${data.error}</div>`;
                }
            } catch (error) {
                document.getElementById('raritiesResult').innerHTML = `<div class="error">❌ Ошибка сети: ${error.message}</div>`;
            }
        }
        
        async function testTypes() {
            try {
                document.getElementById('typesResult').innerHTML = '<div class="loading">Загрузка...</div>';
                
                const response = await fetch('api/generate-potions.php?action=types');
                const data = await response.json();
                
                if (data.success) {
                    document.getElementById('typesResult').innerHTML = `
                        <div class="success">✅ Успешно получены типы</div>
                        <h4>Доступные типы:</h4>
                        <ul>${data.data.map(type => `<li>${type}</li>`).join('')}</ul>
                    `;
                } else {
                    document.getElementById('typesResult').innerHTML = `<div class="error">❌ Ошибка: ${data.error}</div>`;
                }
            } catch (error) {
                document.getElementById('typesResult').innerHTML = `<div class="error">❌ Ошибка сети: ${error.message}</div>`;
            }
        }
        
        async function testPotionsByType() {
            const type = document.getElementById('typeFilter').value;
            
            try {
                document.getElementById('typeResult').innerHTML = '<div class="loading">Загрузка...</div>';
                
                const response = await fetch(`api/generate-potions.php?action=by_type&type=${encodeURIComponent(type)}`);
                const data = await response.json();
                
                if (data.success) {
                    let html = `<div class="success">✅ Успешно найдены зелья типа "${type}"</div>`;
                    html += `<h4>Зелья типа "${type}":</h4>`;
                    if (data.data.length > 0) {
                        data.data.forEach((potion, index) => {
                            html += `
                                <div style="border: 1px solid #ddd; padding: 10px; margin: 10px 0; border-radius: 4px; border-left: 4px solid ${potion.color}">
                                    <h5>${potion.icon} ${potion.name}</h5>
                                    <p><strong>Редкость:</strong> <span style="color: ${potion.color}">${potion.rarity}</span></p>
                                    <p><strong>Описание:</strong> ${potion.description}</p>
                                    <p><strong>Стоимость:</strong> ${potion.value}</p>
                                </div>
                            `;
                        });
                    } else {
                        html += '<p>Зелья данного типа не найдены.</p>';
                    }
                    document.getElementById('typeResult').innerHTML = html;
                } else {
                    document.getElementById('typeResult').innerHTML = `<div class="error">❌ Ошибка: ${data.error}</div>`;
                }
            } catch (error) {
                document.getElementById('typeResult').innerHTML = `<div class="error">❌ Ошибка сети: ${error.message}</div>`;
            }
        }
    </script>
</body>
</html>
