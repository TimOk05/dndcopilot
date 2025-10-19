<?php
/**
 * Главная страница D&D Generator
 * Упрощенная версия с разделенной логикой
 */

session_start();
require_once '../app/Middleware/auth.php';
require_once '../app/Handlers/AjaxHandler.php';

// Проверяем авторизацию
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Получаем данные пользователя
$currentUser = $_SESSION['username'] ?? 'Пользователь';
$currentLanguage = 'ru';

// Обрабатываем AJAX запросы
if (isset($_POST['fast_action'])) {
    AjaxHandler::handleRequest();
}

// Инициализируем заметки
NotesHandler::initNotes();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>D&D Generator - Главная</title>
    <link rel="stylesheet" href="assets/css/utilities.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .header h1 {
            color: #333;
            margin: 0 0 10px 0;
        }
        
        .user-info {
            color: #666;
            font-size: 14px;
        }
        
        .main-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .card h3 {
            margin-top: 0;
            color: #333;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 10px;
        }
        
        .dice-section {
            text-align: center;
        }
        
        .dice-input {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
            align-items: center;
            justify-content: center;
        }
        
        .dice-input input {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        
        .dice-input input[type="text"] {
            width: 80px;
        }
        
        .dice-input input[type="text"]:last-child {
            width: 200px;
        }
        
        .btn {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s;
        }
        
        .btn:hover {
            background: #45a049;
        }
        
        .btn-secondary {
            background: #2196F3;
        }
        
        .btn-secondary:hover {
            background: #1976D2;
        }
        
        .btn-danger {
            background: #f44336;
        }
        
        .btn-danger:hover {
            background: #d32f2f;
        }
        
        .result {
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            margin-top: 15px;
            min-height: 50px;
            white-space: pre-wrap;
            font-family: monospace;
        }
        
        .notes-section .note-item {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 5px;
            padding: 10px;
            margin-bottom: 10px;
        }
        
        .notes-section .note-item.empty {
            text-align: center;
            color: #666;
            font-style: italic;
        }
        
        .notes-section .note-content {
            margin-bottom: 10px;
        }
        
        .notes-section .remove-note {
            background: #f44336;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 12px;
        }
        
        .character-section {
            text-align: center;
        }
        
        .character-templates {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .template-btn {
            background: #e3f2fd;
            color: #1976d2;
            border: 1px solid #bbdefb;
            padding: 10px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.3s;
        }
        
        .template-btn:hover {
            background: #bbdefb;
        }
        
        .character-result {
            background: #f0f8ff;
            border: 1px solid #b3d9ff;
            border-radius: 5px;
            padding: 15px;
            margin-top: 15px;
            text-align: left;
        }
        
        .character-name {
            font-size: 18px;
            font-weight: bold;
            color: #1976d2;
            margin-bottom: 10px;
        }
        
        .character-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-bottom: 10px;
        }
        
        .stat {
            background: white;
            padding: 8px;
            border-radius: 3px;
            text-align: center;
            font-size: 12px;
        }
        
        .stat-name {
            font-weight: bold;
            color: #666;
        }
        
        .stat-value {
            font-size: 16px;
            font-weight: bold;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎲 D&D Generator</h1>
            <div class="user-info">Добро пожаловать, <?= htmlspecialchars($currentUser) ?>!</div>
        </div>
        
        <div class="main-content">
            <!-- Секция костей -->
            <div class="card dice-section">
                <h3>🎲 Кости</h3>
                <div class="dice-input">
                    <input type="text" id="dice-input" placeholder="1d20" value="1d20">
                    <input type="text" id="dice-label" placeholder="Комментарий (необязательно)">
                    <button class="btn" onclick="rollDice()">Бросить</button>
                </div>
                <div class="result" id="dice-result">Нажмите "Бросить" для броска костей</div>
            </div>
            
            <!-- Секция персонажей -->
            <div class="card character-section">
                <h3>⚔️ Генератор персонажей</h3>
                <div class="character-templates">
                    <button class="template-btn" onclick="generateQuickCharacter('human_fighter')">Человек-Воин</button>
                    <button class="template-btn" onclick="generateQuickCharacter('elf_wizard')">Эльф-Волшебник</button>
                    <button class="template-btn" onclick="generateQuickCharacter('dwarf_cleric')">Дварф-Жрец</button>
                    <button class="template-btn" onclick="generateQuickCharacter('halfling_rogue')">Полурослик-Плут</button>
                    <button class="template-btn" onclick="generateRandomCharacter()">Случайный</button>
                </div>
                <div class="character-result" id="character-result" style="display: none;">
                    <!-- Результат генерации персонажа -->
                </div>
            </div>
            
            <!-- Секция заметок -->
            <div class="card notes-section">
                <h3>📝 Заметки</h3>
                <div style="margin-bottom: 15px;">
                    <input type="text" id="note-title" placeholder="Заголовок (необязательно)" style="width: 100%; margin-bottom: 5px; padding: 8px; border: 1px solid #ddd; border-radius: 3px;">
                    <textarea id="note-content" placeholder="Содержимое заметки" style="width: 100%; height: 60px; padding: 8px; border: 1px solid #ddd; border-radius: 3px; resize: vertical;"></textarea>
                    <button class="btn" onclick="addNote()" style="margin-top: 5px;">Добавить заметку</button>
                </div>
                <div id="notes-container">
                    <!-- Заметки будут загружены здесь -->
                </div>
            </div>
        </div>
    </div>

    <script>
        // Функция для броска костей
        function rollDice() {
            const dice = document.getElementById('dice-input').value;
            const label = document.getElementById('dice-label').value;
            const resultDiv = document.getElementById('dice-result');
            
            resultDiv.textContent = 'Бросаем кости...';
            
            const formData = new FormData();
            formData.append('fast_action', 'dice_result');
            formData.append('dice', dice);
            formData.append('label', label);
            
            fetch('index_new.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                resultDiv.innerHTML = data;
            })
            .catch(error => {
                resultDiv.textContent = 'Ошибка: ' + error;
            });
        }
        
        // Функция для генерации персонажа по шаблону
        function generateQuickCharacter(template) {
            const resultDiv = document.getElementById('character-result');
            resultDiv.style.display = 'block';
            resultDiv.innerHTML = '<div style="text-align: center;">Генерируем персонажа...</div>';
            
            const formData = new FormData();
            formData.append('fast_action', 'generate_quick_character');
            formData.append('template', template);
            
            fetch('index_new.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayCharacter(data.character);
                } else {
                    resultDiv.innerHTML = '<div style="color: red;">Ошибка: ' + data.message + '</div>';
                }
            })
            .catch(error => {
                resultDiv.innerHTML = '<div style="color: red;">Ошибка: ' + error + '</div>';
            });
        }
        
        // Функция для генерации случайного персонажа
        function generateRandomCharacter() {
            const resultDiv = document.getElementById('character-result');
            resultDiv.style.display = 'block';
            resultDiv.innerHTML = '<div style="text-align: center;">Генерируем случайного персонажа...</div>';
            
            const formData = new FormData();
            formData.append('fast_action', 'generate_random_character');
            
            fetch('index_new.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayCharacter(data.character);
                } else {
                    resultDiv.innerHTML = '<div style="color: red;">Ошибка: ' + data.message + '</div>';
                }
            })
            .catch(error => {
                resultDiv.innerHTML = '<div style="color: red;">Ошибка: ' + error + '</div>';
            });
        }
        
        // Функция для отображения персонажа
        function displayCharacter(character) {
            const resultDiv = document.getElementById('character-result');
            
            let html = `
                <div class="character-name">${character.name}</div>
                <div><strong>Раса:</strong> ${character.race}</div>
                <div><strong>Класс:</strong> ${character.class} (${character.level} уровень)</div>
                <div><strong>Мировоззрение:</strong> ${character.alignment}</div>
                <div class="character-stats">
            `;
            
            // Характеристики
            const abilities = ['str', 'dex', 'con', 'int', 'wis', 'cha'];
            const abilityNames = ['СИЛ', 'ЛОВ', 'ТЕЛ', 'ИНТ', 'МДР', 'ХАР'];
            
            abilities.forEach((ability, index) => {
                const value = character.abilities[ability];
                const modifier = character.modifiers[ability];
                html += `
                    <div class="stat">
                        <div class="stat-name">${abilityNames[index]}</div>
                        <div class="stat-value">${value} (${modifier >= 0 ? '+' : ''}${modifier})</div>
                    </div>
                `;
            });
            
            html += `
                </div>
                <div><strong>Хиты:</strong> ${character.hit_points}</div>
                <div><strong>КД:</strong> ${character.armor_class}</div>
                <div><strong>Скорость:</strong> ${character.speed} футов</div>
                <div><strong>Описание:</strong> ${character.description}</div>
            `;
            
            resultDiv.innerHTML = html;
        }
        
        // Функция для добавления заметки
        function addNote() {
            const title = document.getElementById('note-title').value;
            const content = document.getElementById('note-content').value;
            
            if (!content.trim()) {
                alert('Введите содержимое заметки');
                return;
            }
            
            const formData = new FormData();
            formData.append('fast_action', 'save_note');
            formData.append('title', title);
            formData.append('content', content);
            
            fetch('index_new.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                if (data === 'OK') {
                    document.getElementById('note-title').value = '';
                    document.getElementById('note-content').value = '';
                    loadNotes();
                } else {
                    alert('Ошибка: ' + data);
                }
            })
            .catch(error => {
                alert('Ошибка: ' + error);
            });
        }
        
        // Функция для загрузки заметок
        function loadNotes() {
            const formData = new FormData();
            formData.append('fast_action', 'update_notes');
            
            fetch('index_new.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                document.getElementById('notes-container').innerHTML = data;
            })
            .catch(error => {
                document.getElementById('notes-container').innerHTML = '<div style="color: red;">Ошибка загрузки заметок</div>';
            });
        }
        
        // Функция для удаления заметки
        function removeNote(index) {
            if (!confirm('Удалить эту заметку?')) return;
            
            const formData = new FormData();
            formData.append('fast_action', 'remove_note');
            formData.append('remove_note', index);
            
            fetch('index_new.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                if (data === 'OK') {
                    loadNotes();
                } else {
                    alert('Ошибка: ' + data);
                }
            })
            .catch(error => {
                alert('Ошибка: ' + error);
            });
        }
        
        // Загружаем заметки при загрузке страницы
        document.addEventListener('DOMContentLoaded', function() {
            loadNotes();
        });
    </script>
</body>
</html>
