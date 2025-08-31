<?php
/**
 * Тест исправлений генерации
 * Проверяет исправления API и UI проблем
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
    <title>Тест исправлений генерации - DnD Copilot</title>
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
    </style>
</head>
<body>
    <div class="test-container">
        <h1>🧪 Тест исправлений генерации</h1>
        
        <div class="test-section">
            <div class="test-title">1. Исправления API генерации персонажей</div>
            <div class="test-result success">
                ✅ Исправлен формат ответа API: `npc` → `character`
            </div>
            <div class="test-result success">
                ✅ Добавлена поддержка старого и нового формата в JavaScript
            </div>
            <div class="test-result info">
                ℹ️ Теперь API возвращает данные в правильном формате
            </div>
        </div>
        
        <div class="test-section">
            <div class="test-title">2. Исправления UI</div>
            <div class="test-result success">
                ✅ Убрана иконка Google из интерфейса
            </div>
            <div class="test-result success">
                ✅ Исправлены проблемы с tooltip (обрезание, выход за границы)
            </div>
            <div class="test-result success">
                ✅ Добавлено правильное позиционирование tooltip
            </div>
            <div class="test-result info">
                ℹ️ Tooltip теперь корректно отображается при наведении
            </div>
        </div>
        
        <div class="test-section">
            <div class="test-title">3. Тест API генерации персонажей</div>
            <div class="api-test">
                <p>Нажмите кнопку для тестирования API генерации персонажей:</p>
                <button onclick="testCharacterAPI()">Тест генерации персонажа</button>
                <div id="api-result" class="api-result" style="display: none;"></div>
            </div>
        </div>
        
        <div class="test-section">
            <div class="test-title">4. Код исправлений</div>
            <div class="code-block">
// Исправление API - изменение формата ответа
return [
    'success' => true,
    'character' => $character  // было 'npc'
];

// Исправление JavaScript - поддержка обоих форматов
const character = data.character || data.npc;

// Исправление UI - убрана иконка Google
// Удален блок с google-badge

// Исправление tooltip - добавлено ограничение ширины
max-width: min(200px, calc(100vw - 40px));
word-wrap: break-word;
white-space: normal;
            </div>
        </div>
        
        <div class="test-buttons">
            <a href="index.php" class="test-btn success">✅ Перейти к основному интерфейсу</a>
            <a href="test-final-fixes.php" class="test-btn">🎯 Финальный тест</a>
        </div>
        
        <div class="test-section">
            <div class="test-title">5. Инструкции по тестированию</div>
            <div class="test-result info">
                1. Нажмите "Тест генерации персонажа" выше для проверки API
            </div>
            <div class="test-result info">
                2. Перейдите к основному интерфейсу и попробуйте генерацию (F2)
            </div>
            <div class="test-result info">
                3. Проверьте, что при наведении на кнопки статистики и админа tooltip отображается корректно
            </div>
            <div class="test-result info">
                4. Убедитесь, что иконка Google больше не отображается
            </div>
        </div>
        
        <div class="test-section">
            <div class="test-title">6. Ожидаемые результаты</div>
            <div class="test-result success">
                ✅ Генерация персонажей работает без ошибки "Некорректные данные персонажа"
            </div>
            <div class="test-result success">
                ✅ Tooltip отображается корректно, не выходит за границы
            </div>
            <div class="test-result success">
                ✅ Иконка Google убрана из интерфейса
            </div>
            <div class="test-result success">
                ✅ Нет лишних элементов в виде квадратиков
            </div>
        </div>
    </div>

    <script>
        function testCharacterAPI() {
            const resultDiv = document.getElementById('api-result');
            resultDiv.style.display = 'block';
            resultDiv.innerHTML = 'Тестирование API...';
            
            const formData = new FormData();
            formData.append('race', 'human');
            formData.append('class', 'fighter');
            formData.append('level', '1');
            formData.append('alignment', 'neutral');
            formData.append('gender', 'random');
            formData.append('use_ai', 'on');
            
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
                        resultDiv.innerHTML = `
                            <div style="color: green; font-weight: bold;">✅ API работает корректно!</div>
                            <div><strong>Персонаж:</strong> ${character.name}</div>
                            <div><strong>Раса:</strong> ${character.race}</div>
                            <div><strong>Класс:</strong> ${character.class}</div>
                            <div><strong>Уровень:</strong> ${character.level}</div>
                            <div><strong>Формат данных:</strong> ${data.character ? 'character' : 'npc'}</div>
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
    </script>
</body>
</html>
