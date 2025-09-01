<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Тест генератора персонажей v4 - Исправления</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .test-section {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .test-result {
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
            border-left: 4px solid #007cba;
        }
        .success { border-left-color: #28a745; background-color: #d4edda; }
        .error { border-left-color: #dc3545; background-color: #f8d7da; }
        .warning { border-left-color: #ffc107; background-color: #fff3cd; }
        .info { border-left-color: #17a2b8; background-color: #d1ecf1; }
        button {
            background: #007cba;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            margin: 5px;
        }
        button:hover { background: #005a87; }
        .character-display {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            margin: 10px 0;
        }
        .feature-item {
            background: #e9ecef;
            padding: 8px;
            margin: 5px 0;
            border-radius: 4px;
            cursor: pointer;
        }
        .feature-item:hover { background: #dee2e6; }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-content {
            background-color: white;
            margin: 15% auto;
            padding: 20px;
            border-radius: 8px;
            width: 80%;
            max-width: 600px;
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .close:hover { color: black; }
    </style>
</head>
<body>
    <h1>Тест генератора персонажей v4 - Исправления</h1>
    
    <div class="test-section">
        <h2>Тест 1: Генерация Ааракокра 10 уровня Варвара</h2>
        <button onclick="testAarakocraBarbarian()">Тестировать</button>
        <div id="test1-result"></div>
    </div>
    
    <div class="test-section">
        <h2>Тест 2: Проверка урона оружия по уровням</h2>
        <button onclick="testWeaponDamage()">Тестировать</button>
        <div id="test2-result"></div>
    </div>
    
    <div class="test-section">
        <h2>Тест 3: Проверка способностей классов</h2>
        <button onclick="testClassFeatures()">Тестировать</button>
        <div id="test3-result"></div>
    </div>
    
    <div class="test-section">
        <h2>Тест 4: Проверка системы заклинаний</h2>
        <button onclick="testSpellSystem()">Тестировать</button>
        <div id="test4-result"></div>
    </div>
    
    <!-- Модальное окно для отображения способности -->
    <div id="featureModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3 id="featureTitle"></h3>
            <p id="featureDescription"></p>
        </div>
    </div>

    <script>
        // Закрытие модального окна
        document.querySelector('.close').onclick = function() {
            document.getElementById('featureModal').style.display = 'none';
        }
        
        window.onclick = function(event) {
            if (event.target == document.getElementById('featureModal')) {
                document.getElementById('featureModal').style.display = 'none';
            }
        }
        
        // Показать описание способности
        function showFeatureDescription(name, description) {
            document.getElementById('featureTitle').textContent = name;
            document.getElementById('featureDescription').textContent = description;
            document.getElementById('featureModal').style.display = 'block';
        }
        
        // Тест 1: Генерация Ааракокра 10 уровня Варвара
        async function testAarakocraBarbarian() {
            const resultDiv = document.getElementById('test1-result');
            resultDiv.innerHTML = '<div class="info">Тестируем генерацию Ааракокра 10 уровня Варвара...</div>';
            
            try {
                const response = await fetch('api/generate-characters-v4.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'race=aarakocra&class=barbarian&level=10&alignment=neutral&gender=male&use_ai=on'
                });
                
                const result = await response.json();
                
                if (result.success) {
                    const character = result.character;
                    let html = '<div class="success">✅ Персонаж успешно сгенерирован!</div>';
                    html += '<div class="character-display">';
                    html += `<h3>${character.name}</h3>`;
                    html += `<p><strong>Раса:</strong> ${character.race}</p>`;
                    html += `<p><strong>Класс:</strong> ${character.class}</p>`;
                    html += `<p><strong>Уровень:</strong> ${character.level}</p>`;
                    html += `<p><strong>Урон:</strong> ${character.damage}</p>`;
                    html += `<p><strong>Владения:</strong> ${character.proficiencies.join(', ')}</p>`;
                    
                    if (character.features && character.features.length > 0) {
                        html += '<h4>Способности:</h4>';
                        character.features.forEach(feature => {
                            html += `<div class="feature-item" onclick="showFeatureDescription('${feature.name}', '${feature.description}')">`;
                            html += `<strong>${feature.name}</strong> - Нажмите для описания`;
                            html += '</div>';
                        });
                    }
                    
                    html += '</div>';
                    resultDiv.innerHTML = html;
                } else {
                    resultDiv.innerHTML = `<div class="error">❌ Ошибка: ${result.error}</div>`;
                }
            } catch (error) {
                resultDiv.innerHTML = `<div class="error">❌ Ошибка запроса: ${error.message}</div>`;
            }
        }
        
        // Тест 2: Проверка урона оружия по уровням
        async function testWeaponDamage() {
            const resultDiv = document.getElementById('test2-result');
            resultDiv.innerHTML = '<div class="info">Тестируем урон оружия по уровням...</div>';
            
            const testCases = [
                {race: 'human', class: 'barbarian', level: 1},
                {race: 'human', class: 'barbarian', level: 5},
                {race: 'human', class: 'barbarian', level: 10},
                {race: 'human', class: 'fighter', level: 1},
                {race: 'human', class: 'fighter', level: 5},
                {race: 'human', class: 'fighter', level: 11},
                {race: 'human', class: 'fighter', level: 20}
            ];
            
            let html = '<div class="success">✅ Тест урона оружия:</div>';
            html += '<div class="character-display">';
            
            for (const testCase of testCases) {
                try {
                    const response = await fetch('api/generate-characters-v4.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `race=${testCase.race}&class=${testCase.class}&level=${testCase.level}&alignment=neutral&gender=male&use_ai=off`
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        const character = result.character;
                        html += `<p><strong>${character.race} ${character.class} ${character.level} уровня:</strong> Урон: ${character.damage}</p>`;
                    } else {
                        html += `<p class="error">❌ Ошибка для ${testCase.race} ${testCase.class} ${testCase.level} уровня: ${result.error}</p>`;
                    }
                } catch (error) {
                    html += `<p class="error">❌ Ошибка запроса для ${testCase.race} ${testCase.class} ${testCase.level} уровня: ${error.message}</p>`;
                }
            }
            
            html += '</div>';
            resultDiv.innerHTML = html;
        }
        
        // Тест 3: Проверка способностей классов
        async function testClassFeatures() {
            const resultDiv = document.getElementById('test3-result');
            resultDiv.innerHTML = '<div class="info">Тестируем способности классов...</div>';
            
            const testCases = [
                {race: 'human', class: 'barbarian', level: 10},
                {race: 'human', class: 'fighter', level: 10},
                {race: 'human', class: 'rogue', level: 10},
                {race: 'human', class: 'monk', level: 10}
            ];
            
            let html = '<div class="success">✅ Тест способностей классов:</div>';
            
            for (const testCase of testCases) {
                try {
                    const response = await fetch('api/generate-characters-v4.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `race=${testCase.race}&class=${testCase.class}&level=${testCase.level}&alignment=neutral&gender=male&use_ai=off`
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        const character = result.character;
                        html += `<div class="character-display">`;
                        html += `<h4>${character.race} ${character.class} ${character.level} уровня</h4>`;
                        
                        if (character.features && character.features.length > 0) {
                            character.features.forEach(feature => {
                                html += `<div class="feature-item" onclick="showFeatureDescription('${feature.name}', '${feature.description}')">`;
                                html += `<strong>${feature.name}</strong> - Нажмите для описания`;
                                html += '</div>';
                            });
                        } else {
                            html += '<p class="warning">⚠️ Способности не найдены</p>';
                        }
                        
                        html += '</div>';
                    } else {
                        html += `<p class="error">❌ Ошибка для ${testCase.race} ${testCase.class} ${testCase.level} уровня: ${result.error}</p>`;
                    }
                } catch (error) {
                    html += `<p class="error">❌ Ошибка запроса для ${testCase.race} ${testCase.class} ${testCase.level} уровня: ${error.message}</p>`;
                }
            }
            
            resultDiv.innerHTML = html;
        }
        
        // Тест 4: Проверка системы заклинаний
        async function testSpellSystem() {
            const resultDiv = document.getElementById('test4-result');
            resultDiv.innerHTML = '<div class="info">Тестируем систему заклинаний...</div>';
            
            const testCases = [
                {race: 'human', class: 'wizard', level: 5},
                {race: 'human', class: 'cleric', level: 10},
                {race: 'human', class: 'paladin', level: 8}
            ];
            
            let html = '<div class="success">✅ Тест системы заклинаний:</div>';
            
            for (const testCase of testCases) {
                try {
                    const response = await fetch('api/generate-characters-v4.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `race=${testCase.race}&class=${testCase.class}&level=${testCase.level}&alignment=neutral&gender=male&use_ai=off`
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        const character = result.character;
                        html += `<div class="character-display">`;
                        html += `<h4>${character.race} ${character.class} ${character.level} уровня</h4>`;
                        
                        if (character.spells && character.spells.spell_slots) {
                            html += `<p><strong>Слоты заклинаний:</strong> ${character.spells.spell_slots.join(', ')}</p>`;
                            html += `<p><strong>Способность заклинаний:</strong> ${character.spells.spellcasting_ability}</p>`;
                        } else {
                            html += '<p class="warning">⚠️ Система заклинаний не найдена</p>';
                        }
                        
                        html += '</div>';
                    } else {
                        html += `<p class="error">❌ Ошибка для ${testCase.race} ${testCase.class} ${testCase.level} уровня: ${result.error}</p>`;
                    }
                } catch (error) {
                    html += `<p class="error">❌ Ошибка запроса для ${testCase.race} ${testCase.class} ${testCase.level} уровня: ${error.message}</p>`;
                }
            }
            
            resultDiv.innerHTML = html;
        }
    </script>
</body>
</html>
