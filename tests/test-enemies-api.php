<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Тест API противников D&D</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { margin: 20px 0; padding: 20px; border: 1px solid #ccc; border-radius: 8px; }
        .result { background: #f5f5f5; padding: 10px; margin: 10px 0; border-radius: 4px; max-height: 400px; overflow-y: auto; }
        button { padding: 10px 20px; margin: 5px; cursor: pointer; }
        input, select { padding: 8px; margin: 5px; }
        .error { color: red; background: #ffe6e6; padding: 10px; border-radius: 4px; }
        .success { color: green; background: #e6ffe6; padding: 10px; border-radius: 4px; }
        .loading { color: blue; background: #e6f3ff; padding: 10px; border-radius: 4px; }
        .enemy-card { border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 8px; background: #fafafa; }
        .enemy-header { font-weight: bold; font-size: 18px; margin-bottom: 10px; }
        .enemy-cr { color: #666; font-size: 14px; }
        .enemy-details { margin: 10px 0; }
    </style>
</head>
<body>
    <h1>🐉 Тест API противников D&D</h1>
    
    <div class="test-section">
        <h3>Генерация противников</h3>
        <label>Уровень угрозы: 
            <select id="threatLevel">
                <option value="easy">Легкий (CR 0-3)</option>
                <option value="medium" selected>Средний (CR 1-7)</option>
                <option value="hard">Сложный (CR 5-12)</option>
                <option value="deadly">Смертельный (CR 10-20)</option>
                <option value="random">Случайный</option>
            </select>
        </label>
        <label>Количество: <input type="number" id="count" value="1" min="1" max="10"></label>
        <label>Тип: 
            <select id="enemyType">
                <option value="">Любой</option>
                <option value="humanoid">Гуманоиды</option>
                <option value="beast">Звери</option>
                <option value="undead">Нежить</option>
                <option value="giant">Великаны</option>
                <option value="dragon">Драконы</option>
            </select>
        </label>
        <label>Среда: 
            <select id="environment">
                <option value="">Любая</option>
                <option value="forest">Лес</option>
                <option value="mountain">Горы</option>
                <option value="urban">Город</option>
                <option value="underdark">Подземелье</option>
            </select>
        </label>
        <br><br>
        <button onclick="testEnemyGeneration()">🎲 Сгенерировать противников</button>
        <div id="enemyResult" class="result"></div>
    </div>
    
    <div class="test-section">
        <h3>Тест D&D API подключения</h3>
        <button onclick="testDnDApiConnection()">🌐 Проверить подключение к D&D API</button>
        <div id="connectionResult" class="result"></div>
    </div>
    
    <div class="test-section">
        <h3>Получить список монстров</h3>
        <button onclick="testMonstersList()">📋 Получить список монстров</button>
        <div id="monstersResult" class="result"></div>
    </div>

    <script>
        async function testEnemyGeneration() {
            const threatLevel = document.getElementById('threatLevel').value;
            const count = document.getElementById('count').value;
            const enemyType = document.getElementById('enemyType').value;
            const environment = document.getElementById('environment').value;
            
            const formData = new FormData();
            formData.append('threat_level', threatLevel);
            formData.append('count', count);
            formData.append('use_ai', 'on');
            if (enemyType) formData.append('enemy_type', enemyType);
            if (environment) formData.append('environment', environment);
            
            try {
                document.getElementById('enemyResult').innerHTML = '<div class="loading">⏳ Генерация противников...</div>';
                
                const response = await fetch('api/generate-enemies.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success && data.enemies) {
                    let html = `<div class="success">✅ Успешно сгенерировано противников: ${data.enemies.length}</div>`;
                    html += `<h4>Уровень угрозы: ${data.threat_level_display || data.threat_level}</h4>`;
                    
                    data.enemies.forEach((enemy, index) => {
                        html += `
                            <div class="enemy-card">
                                <div class="enemy-header">${enemy.name || 'Безымянный противник'}</div>
                                <div class="enemy-cr">CR: ${enemy.challenge_rating || enemy.cr || '?'}</div>
                                <div class="enemy-details">
                                    <strong>Тип:</strong> ${enemy.type || 'Неизвестен'}<br>
                                    <strong>Хиты:</strong> ${enemy.hit_points || enemy.hp || '?'}<br>
                                    <strong>Класс доспеха:</strong> ${enemy.armor_class || enemy.ac || '?'}<br>
                                    <strong>Скорость:</strong> ${enemy.speed || 'Неизвестна'}<br>
                                    <strong>Среда:</strong> ${enemy.environment || 'Не указана'}
                                </div>
                                ${enemy.description ? `<div><strong>Описание:</strong> ${enemy.description}</div>` : ''}
                                ${enemy.tactics ? `<div><strong>Тактика:</strong> ${enemy.tactics}</div>` : ''}
                            </div>
                        `;
                    });
                    
                    document.getElementById('enemyResult').innerHTML = html;
                } else {
                    let errorMsg = data.error || 'Неизвестная ошибка';
                    document.getElementById('enemyResult').innerHTML = `<div class="error">❌ Ошибка: ${errorMsg}</div>`;
                }
            } catch (error) {
                document.getElementById('enemyResult').innerHTML = `<div class="error">❌ Ошибка сети: ${error.message}</div>`;
            }
        }
        
        async function testDnDApiConnection() {
            try {
                document.getElementById('connectionResult').innerHTML = '<div class="loading">⏳ Проверка подключения...</div>';
                
                const response = await fetch('https://www.dnd5eapi.co/api/');
                const data = await response.json();
                
                if (data && typeof data === 'object') {
                    document.getElementById('connectionResult').innerHTML = `
                        <div class="success">✅ D&D API доступен</div>
                        <h4>Доступные эндпоинты:</h4>
                        <pre>${JSON.stringify(data, null, 2)}</pre>
                    `;
                } else {
                    document.getElementById('connectionResult').innerHTML = `<div class="error">❌ D&D API недоступен</div>`;
                }
            } catch (error) {
                document.getElementById('connectionResult').innerHTML = `<div class="error">❌ Ошибка подключения к D&D API: ${error.message}</div>`;
            }
        }
        
        async function testMonstersList() {
            try {
                document.getElementById('monstersResult').innerHTML = '<div class="loading">⏳ Загрузка списка монстров...</div>';
                
                const response = await fetch('https://www.dnd5eapi.co/api/monsters');
                const data = await response.json();
                
                if (data && data.results) {
                    let html = `<div class="success">✅ Получен список монстров: ${data.results.length}</div>`;
                    html += '<h4>Первые 10 монстров:</h4><ul>';
                    
                    data.results.slice(0, 10).forEach(monster => {
                        html += `<li><strong>${monster.name}</strong> (index: ${monster.index})</li>`;
                    });
                    
                    html += '</ul>';
                    document.getElementById('monstersResult').innerHTML = html;
                } else {
                    document.getElementById('monstersResult').innerHTML = `<div class="error">❌ Не удалось получить список монстров</div>`;
                }
            } catch (error) {
                document.getElementById('monstersResult').innerHTML = `<div class="error">❌ Ошибка получения списка монстров: ${error.message}</div>`;
            }
        }
    </script>
</body>
</html>
