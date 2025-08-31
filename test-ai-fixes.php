<?php
/**
 * Тест исправлений AI и D&D API
 * Проверяет исправления чата ИИ, генерации имен из D&D API и обязательной генерации с ИИ
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
    <title>Тест исправлений AI и D&D API - DnD Copilot</title>
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
        <h1>🧪 Тест исправлений AI и D&D API</h1>
        
        <div class="test-section">
            <div class="test-title">1. Исправления чата ИИ</div>
            <div class="test-result success">
                ✅ Исправлена ошибка с `users.php` → заменено на `auth.php`
            </div>
            <div class="test-result success">
                ✅ Исправлена ошибка с `MAX_FILE_SIZE` → заменено на конкретное значение
            </div>
            <div class="test-result info">
                ℹ️ Теперь чат ИИ должен работать без ошибок
            </div>
        </div>
        
        <div class="test-section">
            <div class="test-title">2. Генерация имен из D&D API</div>
            <div class="test-result success">
                ✅ Добавлена функция `getNamesFromDndApi()` для получения имен из D&D 5e API
            </div>
            <div class="test-result success">
                ✅ Добавлен fallback на JSON файл если API недоступен
            </div>
            <div class="test-result success">
                ✅ Добавлен маппинг рас для корректного обращения к API
            </div>
            <div class="test-result info">
                ℹ️ Теперь имена берутся из официальной библиотеки D&D
            </div>
        </div>
        
        <div class="test-section">
            <div class="test-title">3. Обязательная генерация с ИИ</div>
            <div class="test-result success">
                ✅ Генерация описания персонажа теперь обязательно использует ИИ
            </div>
            <div class="test-result success">
                ✅ Генерация предыстории персонажа теперь обязательно использует ИИ
            </div>
            <div class="test-result warning">
                ⚠️ Требуется настройка API ключей в `config.php`
            </div>
        </div>
        
        <div class="test-section">
            <div class="test-title">4. Тест чата ИИ</div>
            <div class="api-test">
                <p>Нажмите кнопку для тестирования чата ИИ:</p>
                <button onclick="testAIChat()">Тест чата ИИ</button>
                <div id="ai-chat-result" class="api-result" style="display: none;"></div>
            </div>
        </div>
        
        <div class="test-section">
            <div class="test-title">5. Тест генерации персонажа с ИИ</div>
            <div class="api-test">
                <p>Нажмите кнопку для тестирования генерации персонажа с ИИ:</p>
                <button onclick="testCharacterWithAI()">Тест генерации с ИИ</button>
                <div id="character-ai-result" class="api-result" style="display: none;"></div>
            </div>
        </div>
        
        <div class="test-section">
            <div class="test-title">6. Код исправлений</div>
            <div class="code-block">
// Исправление чата ИИ
require_once __DIR__ . '/../auth.php';  // было users.php
$max_size = 10 * 1024 * 1024;  // было MAX_FILE_SIZE

// Генерация имен из D&D API
private function getNamesFromDndApi($race, $gender) {
    $names = $this->callDndApi("races/{$apiRace}");
    // Fallback на JSON если API недоступен
    return $this->getNamesFromJson($race, $gender);
}

// Обязательная генерация с ИИ
private function generateDescription($character) {
    if (!$this->deepseek_api_key) {
        return "Описание недоступно. Проверьте AI API.";
    }
    // Обязательно используем ИИ
}

private function generateBackground($character) {
    if (!$this->deepseek_api_key) {
        return "Предыстория недоступна. Проверьте AI API.";
    }
    // Обязательно используем ИИ
}
            </div>
        </div>
        
        <div class="test-buttons">
            <a href="index.php" class="test-btn success">✅ Перейти к основному интерфейсу</a>
            <a href="test-names-fix.php" class="test-btn">🧪 Тест имен</a>
        </div>
        
        <div class="test-section">
            <div class="test-title">7. Инструкции по тестированию</div>
            <div class="test-result info">
                1. Нажмите "Тест чата ИИ" для проверки исправления ошибки
            </div>
            <div class="test-result info">
                2. Нажмите "Тест генерации с ИИ" для проверки обязательной генерации
            </div>
            <div class="test-result info">
                3. Перейдите к основному интерфейсу и попробуйте чат ИИ
            </div>
            <div class="test-result info">
                4. Попробуйте генерацию персонажа (должна использовать ИИ)
            </div>
        </div>
        
        <div class="test-section">
            <div class="test-title">8. Ожидаемые результаты</div>
            <div class="test-result success">
                ✅ Чат ИИ работает без ошибок PHP
            </div>
                <div class="test-result success">
                ✅ Имена персонажей берутся из D&D API
            </div>
            <div class="test-result success">
                ✅ Описание и предыстория генерируются с помощью ИИ
            </div>
            <div class="test-result warning">
                ⚠️ Требуется настройка API ключей для полной функциональности
            </div>
        </div>
    </div>

    <script>
        function testAIChat() {
            const resultDiv = document.getElementById('ai-chat-result');
            resultDiv.style.display = 'block';
            resultDiv.innerHTML = 'Тестирование чата ИИ...';
            
            const formData = new FormData();
            formData.append('message', 'Привет! Расскажи о правилах D&D 5e');
            
            fetch('api/ai-chat.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                console.log('AI Chat Response:', data);
                
                if (data.success) {
                    resultDiv.innerHTML = `
                        <div style="color: green; font-weight: bold;">✅ Чат ИИ работает!</div>
                        <div style="margin-top: 10px;">
                            <strong>Ответ ИИ:</strong><br>
                            <div style="background: #f8f9fa; padding: 10px; border-radius: 4px; margin-top: 5px;">
                                ${data.response}
                            </div>
                        </div>
                    `;
                } else {
                    resultDiv.innerHTML = `
                        <div style="color: orange; font-weight: bold;">⚠️ Чат ИИ вернул ошибку</div>
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
        
        function testCharacterWithAI() {
            const resultDiv = document.getElementById('character-ai-result');
            resultDiv.style.display = 'block';
            resultDiv.innerHTML = 'Тестирование генерации персонажа с ИИ...';
            
            const formData = new FormData();
            formData.append('race', 'human');
            formData.append('class', 'fighter');
            formData.append('level', '1');
            formData.append('alignment', 'neutral');
            formData.append('gender', 'male');
            formData.append('use_ai', 'on');
            
            fetch('api/generate-characters.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                console.log('Character Generation Response:', data);
                
                if (data.success) {
                    const character = data.character || data.npc;
                    if (character && character.name) {
                        resultDiv.innerHTML = `
                            <div style="color: green; font-weight: bold;">✅ Генерация с ИИ успешна!</div>
                            <div class="character-card">
                                <div class="character-name">${character.name}</div>
                                <div class="character-details">
                                    <strong>Раса:</strong> ${character.race}<br>
                                    <strong>Класс:</strong> ${character.class}<br>
                                    <strong>Уровень:</strong> ${character.level}<br>
                                    <strong>Пол:</strong> ${character.gender}
                                </div>
                            </div>
                            <div style="margin-top: 10px;">
                                <strong>Описание (ИИ):</strong><br>
                                <div style="background: #f8f9fa; padding: 10px; border-radius: 4px; margin-top: 5px;">
                                    ${character.description || 'Описание недоступно'}
                                </div>
                            </div>
                            <div style="margin-top: 10px;">
                                <strong>Предыстория (ИИ):</strong><br>
                                <div style="background: #f8f9fa; padding: 10px; border-radius: 4px; margin-top: 5px;">
                                    ${character.background || 'Предыстория недоступна'}
                                </div>
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
                        <div style="color: red; font-weight: bold;">❌ Ошибка генерации</div>
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
