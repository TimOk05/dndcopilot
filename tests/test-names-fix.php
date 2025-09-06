<?php
/**
 * Тест исправлений имен персонажей
 * Проверяет правильность генерации имен в зависимости от пола
 */

require_once 'config.php';
require_once 'auth.php';

// Временная функция isAdmin для тестирования
function isAdmin() {
    if (!isset($_SESSION['username'])) {
        return false;
    }
    $adminUsers = ['admin', 'administrator', 'root', 'master'];
    return in_array(strtolower($_SESSION['username']), $adminUsers);
}

// Проверяем авторизацию
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$currentUser = $_SESSION['username'] ?? 'Гость';
$isAdmin = isAdmin();

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Тест исправлений имен - DnD Copilot</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background: #f5f5f5;
        }
        .test-container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .test-section {
            margin: 20px 0;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .test-title {
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
            font-size: 1.1em;
        }
        .test-result {
            padding: 10px;
            border-radius: 4px;
            margin: 5px 0;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        .test-buttons {
            margin: 20px 0;
            text-align: center;
        }
        .test-btn {
            background: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            margin: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .test-btn:hover {
            background: #0056b3;
        }
        .test-btn.success {
            background: #28a745;
        }
        .test-btn.success:hover {
            background: #1e7e34;
        }
        .test-btn.warning {
            background: #ffc107;
            color: #212529;
        }
        .test-btn.warning:hover {
            background: #e0a800;
        }
        .code-block {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 4px;
            padding: 10px;
            font-family: monospace;
            font-size: 0.9em;
            margin: 10px 0;
            overflow-x: auto;
        }
        .api-test {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 4px;
            padding: 15px;
            margin: 10px 0;
        }
        .api-test button {
            background: #007bff;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            margin: 5px;
        }
        .api-test button:hover {
            background: #0056b3;
        }
        .api-result {
            margin-top: 10px;
            padding: 10px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 0.9em;
            max-height: 300px;
            overflow-y: auto;
        }
        .character-card {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 4px;
            padding: 15px;
            margin: 10px 0;
        }
        .character-name {
            font-weight: bold;
            font-size: 1.1em;
            color: #333;
        }
        .character-details {
            margin-top: 5px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <h1>🧪 Тест исправлений имен персонажей</h1>
        
        <div class="test-section">
            <div class="test-title">1. Исправления генерации имен</div>
            <div class="test-result success">
                ✅ Исправлена логика выбора имен в зависимости от пола
            </div>
            <div class="test-result success">
                ✅ Добавлены унисекс имена в fallback
            </div>
            <div class="test-result success">
                ✅ Улучшены fallback имена (D&D тематика)
            </div>
            <div class="test-result info">
                ℹ️ Теперь имена генерируются правильно для каждого пола
            </div>
        </div>
        
        <div class="test-section">
            <div class="test-title">2. Тест генерации персонажей разных полов</div>
            <div class="api-test">
                <p>Нажмите кнопки для тестирования генерации персонажей разных полов:</p>
                <button onclick="testCharacterGeneration('male')">Тест мужского персонажа</button>
                <button onclick="testCharacterGeneration('female')">Тест женского персонажа</button>
                <button onclick="testCharacterGeneration('random')">Тест случайного пола</button>
                <div id="api-result" class="api-result" style="display: none;"></div>
            </div>
        </div>
        
        <div class="test-section">
            <div class="test-title">3. Код исправлений</div>
            <div class="code-block">
// Исправление логики выбора имен
// Сначала пробуем имена для конкретного пола
if ($gender === 'male' && !empty($raceData['male'])) {
    $nameList = $raceData['male'];
} elseif ($gender === 'female' && !empty($raceData['female'])) {
    $nameList = $raceData['female'];
}

// Потом унисекс имена
if (empty($nameList) && !empty($raceData['unisex'])) {
    $nameList = $raceData['unisex'];
}

// Только в крайнем случае используем имена другого пола

// Улучшенные fallback имена
$fallbackNames = [
    'male' => ['Торин', 'Гимли', 'Леголас', 'Арагорн', 'Боромир'],
    'female' => ['Арвен', 'Галадриэль', 'Эовин', 'Розмари', 'Лютиэн'],
    'unisex' => ['Ривен', 'Скай', 'Тейлор', 'Морган', 'Кейси']
];
            </div>
        </div>
        
        <div class="test-buttons">
            <a href="index.php" class="test-btn success">✅ Перейти к основному интерфейсу</a>
            <a href="test-generation-fix.php" class="test-btn">🧪 Тест генерации</a>
        </div>
        
        <div class="test-section">
            <div class="test-title">4. Инструкции по тестированию</div>
            <div class="test-result info">
                1. Нажмите кнопки тестирования выше для проверки генерации имен
            </div>
            <div class="test-result info">
                2. Перейдите к основному интерфейсу и попробуйте генерацию персонажей разных полов
            </div>
            <div class="test-result info">
                3. Убедитесь, что мужские персонажи получают мужские имена
            </div>
            <div class="test-result info">
                4. Убедитесь, что женские персонажи получают женские имена
            </div>
        </div>
        
        <div class="test-section">
            <div class="test-title">5. Ожидаемые результаты</div>
            <div class="test-result success">
                ✅ Мужские персонажи получают мужские имена (Торин, Гимли, Леголас)
            </div>
            <div class="test-result success">
                ✅ Женские персонажи получают женские имена (Арвен, Галадриэль, Эовин)
            </div>
            <div class="test-result success">
                ✅ Нет больше случаев типа "Тимофей" для женского персонажа
            </div>
            <div class="test-result success">
                ✅ Имена соответствуют D&D тематике
            </div>
        </div>
    </div>

    <script>
        function testCharacterGeneration(gender) {
            const resultDiv = document.getElementById('api-result');
            resultDiv.style.display = 'block';
            resultDiv.innerHTML = 'Тестирование генерации...';
            
            const formData = new FormData();
            formData.append('race', 'human');
            formData.append('class', 'fighter');
            formData.append('level', '1');
            formData.append('alignment', 'neutral');
            formData.append('gender', gender);
            formData.append('use_ai', 'off');
            
            fetch('api/generate-characters.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                console.log('API Response:', data);
                
                if (data.success) {
                    const character = data.character || data.npc;
                    if (character && character.name) {
                        const genderText = gender === 'male' ? 'Мужской' : 
                                          gender === 'female' ? 'Женский' : 'Случайный';
                        
                        resultDiv.innerHTML = `
                            <div style="color: green; font-weight: bold;">✅ Генерация успешна!</div>
                            <div class="character-card">
                                <div class="character-name">${character.name}</div>
                                <div class="character-details">
                                    <strong>Пол:</strong> ${character.gender} (запрошен: ${genderText})<br>
                                    <strong>Раса:</strong> ${character.race}<br>
                                    <strong>Класс:</strong> ${character.class}<br>
                                    <strong>Уровень:</strong> ${character.level}
                                </div>
                            </div>
                            <div style="margin-top: 10px;">
                                <strong>Проверка:</strong> 
                                ${isAppropriateName(character.name, character.gender) ? 
                                    '✅ Имя подходит для данного пола' : 
                                    '⚠️ Имя может не подходить для данного пола'}
                            </div>
                        `;
                    } else {
                        resultDiv.innerHTML = `
                            <div style="color: orange; font-weight: bold;">⚠️ API вернул успех, но данные некорректны</div>
                            <pre>${JSON.stringify(data, null, 2)}</pre>
                        `;
                    }
                } else {
                    resultDiv.innerHTML = `
                        <div style="color: red; font-weight: bold;">❌ Ошибка API</div>
                        <div>${data.error || 'Неизвестная ошибка'}</div>
                        <pre>${JSON.stringify(data, null, 2)}</pre>
                    `;
                }
            })
            .catch(error => {
                resultDiv.innerHTML = `
                    <div style="color: red; font-weight: bold;">❌ Ошибка сети</div>
                    <div>${error.message}</div>
                `;
            });
        }
        
        function isAppropriateName(name, gender) {
            const maleNames = ['Торин', 'Гимли', 'Леголас', 'Арагорн', 'Боромир', 'Гэндальф', 'Фродо', 'Сэм', 'Мерри', 'Пиппин'];
            const femaleNames = ['Арвен', 'Галадриэль', 'Эовин', 'Розмари', 'Лютиэн', 'Идриль', 'Анкалиме', 'Нимродэль', 'Элвинг', 'Аэрин'];
            const unisexNames = ['Ривен', 'Скай', 'Тейлор', 'Морган', 'Кейси', 'Джордан', 'Алексис', 'Дрю', 'Ким', 'Пэт'];
            
            if (gender === 'Мужчина') {
                return maleNames.includes(name) || unisexNames.includes(name);
            } else if (gender === 'Женщина') {
                return femaleNames.includes(name) || unisexNames.includes(name);
            }
            
            return true; // Для неизвестного пола считаем подходящим
        }
    </script>
</body>
</html>
