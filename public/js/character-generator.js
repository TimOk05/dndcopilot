// === ГЕНЕРАТОР ПЕРСОНАЖЕЙ ===
// Отдельный файл для генератора персонажей D&D 5e

console.log('Character generator script loaded');

// --- Тестовая функция для проверки ---
function testCharacterModal() {
    console.log('=== TEST CHARACTER MODAL ===');
    console.log('Testing modal elements...');

    const modalContent = document.getElementById('modal-content');
    const modalBg = document.getElementById('modal-bg');

    console.log('Modal content element:', modalContent);
    console.log('Modal bg element:', modalBg);

    if (modalContent && modalBg) {
        console.log('Modal elements found, opening modal...');
        modalContent.innerHTML = '<div style="padding: 20px; text-align: center;"><h2>🎭 Тест генератора</h2><p>Модальное окно работает!</p><button onclick="closeModal()" class="btn btn-primary">Закрыть</button></div>';
        modalBg.classList.add('active');
        console.log('Modal opened successfully!');
    } else {
        console.error('Modal elements not found!');
        alert('Ошибка: элементы модального окна не найдены');
    }
}

// --- Функция открытия генерации персонажей ---
function openCharacterModal() {
    console.log('=== OPEN CHARACTER MODAL CALLED ===');
    console.log('Function openCharacterModal is working!');

    const content = `
        <div class="character-generator">
            <h2>🎭 Генератор персонажей</h2>
            <p>Создайте уникального персонажа D&D 5e</p>
            
            <div class="form-group">
                <label for="char-race">Раса</label>
                <select id="char-race" required>
                    <option value="">Выберите расу</option>
                    <option value="race_human">Человек</option>
                    <option value="race_elf">Эльф</option>
                    <option value="race_dwarf">Дварф</option>
                    <option value="race_halfling">Полурослик</option>
                    <option value="race_dragonborn">Драконорожденный</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="char-class">Класс</label>
                <select id="char-class" required>
                    <option value="">Выберите класс</option>
                    <option value="fighter">Воин</option>
                    <option value="wizard">Маг</option>
                    <option value="rogue">Плут</option>
                    <option value="cleric">Жрец</option>
                    <option value="ranger">Следопыт</option>
                </select>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-primary" onclick="generateSimpleCharacter()">
                    🎲 Создать персонажа
                </button>
                <button type="button" class="btn btn-secondary" onclick="closeModal()">
                    ❌ Отмена
                </button>
            </div>
            
            <div id="char-progress" style="display: none; margin-top: 20px;">
                <div style="background: #333; border-radius: 10px; overflow: hidden;">
                    <div id="char-progress-bar" style="background: #007bff; height: 20px; width: 0%; transition: width 0.3s;"></div>
                </div>
                <div id="char-progress-text" style="text-align: center; margin-top: 10px;">Создание персонажа...</div>
            </div>
            
            <div id="char-result" style="display: none; margin-top: 20px;">
                <!-- Результат будет вставлен сюда -->
            </div>
        </div>
    `;

    showModal(content);
}

// --- Функция генерации персонажа ---
function generateSimpleCharacter() {
    console.log('=== GENERATE SIMPLE CHARACTER ===');

    const race = document.getElementById('char-race').value;
    const charClass = document.getElementById('char-class').value;

    if (!race || !charClass) {
        alert('Пожалуйста, выберите расу и класс');
        return;
    }

    console.log('Generating character:', { race, class: charClass });

    // Показываем прогресс
    document.getElementById('char-progress').style.display = 'block';
    document.getElementById('char-result').style.display = 'none';

    const progressBar = document.getElementById('char-progress-bar');
    const progressText = document.getElementById('char-progress-text');

    let progress = 0;
    const interval = setInterval(() => {
        progress += Math.random() * 20;
        if (progress > 90) progress = 90;
        progressBar.style.width = progress + '%';

        if (progress < 30) {
            progressText.textContent = 'Загрузка данных...';
        } else if (progress < 60) {
            progressText.textContent = 'Генерация персонажа...';
        } else {
            progressText.textContent = 'Создание описания...';
        }
    }, 200);

    // Создаем FormData для API
    const formData = new FormData();
    formData.append('race', race);
    formData.append('class', charClass);
    formData.append('level', '1');
    formData.append('gender', 'random');
    formData.append('alignment', 'random');
    formData.append('use_ai', '1');

    fetch('api/generate-characters.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('API response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('API response data:', data);
            clearInterval(interval);
            progressBar.style.width = '100%';
            progressText.textContent = 'Готово!';

            setTimeout(() => {
                document.getElementById('char-progress').style.display = 'none';
                document.getElementById('char-result').style.display = 'block';

                if (data.success && data.character) {
                    const character = data.character;
                    const resultHtml = `
                    <div style="background: rgba(255,255,255,0.1); padding: 20px; border-radius: 10px; margin-top: 20px;">
                        <h3 style="color: #fff; margin-bottom: 15px;">${character.name}</h3>
                        <div style="color: #ccc; margin-bottom: 10px;">
                            <strong>${character.race} ${character.class}</strong> (${character.level} уровень)
                        </div>
                        
                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin: 15px 0;">
                            <div style="text-align: center; background: rgba(0,0,0,0.3); padding: 10px; border-radius: 5px;">
                                <div style="color: #fff; font-weight: bold;">Хиты</div>
                                <div style="color: #4CAF50; font-size: 18px;">${character.hit_points}</div>
                            </div>
                            <div style="text-align: center; background: rgba(0,0,0,0.3); padding: 10px; border-radius: 5px;">
                                <div style="color: #fff; font-weight: bold;">Класс брони</div>
                                <div style="color: #2196F3; font-size: 18px;">${character.armor_class}</div>
                            </div>
                            <div style="text-align: center; background: rgba(0,0,0,0.3); padding: 10px; border-radius: 5px;">
                                <div style="color: #fff; font-weight: bold;">Скорость</div>
                                <div style="color: #FF9800; font-size: 18px;">${character.speed} фт</div>
                            </div>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin: 15px 0;">
                            <div style="text-align: center; background: rgba(0,0,0,0.2); padding: 8px; border-radius: 5px;">
                                <div style="color: #ccc; font-size: 12px;">Сила</div>
                                <div style="color: #fff;">${character.abilities.str} (${character.modifiers.str >= 0 ? '+' : ''}${character.modifiers.str})</div>
                            </div>
                            <div style="text-align: center; background: rgba(0,0,0,0.2); padding: 8px; border-radius: 5px;">
                                <div style="color: #ccc; font-size: 12px;">Ловкость</div>
                                <div style="color: #fff;">${character.abilities.dex} (${character.modifiers.dex >= 0 ? '+' : ''}${character.modifiers.dex})</div>
                            </div>
                            <div style="text-align: center; background: rgba(0,0,0,0.2); padding: 8px; border-radius: 5px;">
                                <div style="color: #ccc; font-size: 12px;">Телосложение</div>
                                <div style="color: #fff;">${character.abilities.con} (${character.modifiers.con >= 0 ? '+' : ''}${character.modifiers.con})</div>
                            </div>
                            <div style="text-align: center; background: rgba(0,0,0,0.2); padding: 8px; border-radius: 5px;">
                                <div style="color: #ccc; font-size: 12px;">Интеллект</div>
                                <div style="color: #fff;">${character.abilities.int} (${character.modifiers.int >= 0 ? '+' : ''}${character.modifiers.int})</div>
                            </div>
                            <div style="text-align: center; background: rgba(0,0,0,0.2); padding: 8px; border-radius: 5px;">
                                <div style="color: #ccc; font-size: 12px;">Мудрость</div>
                                <div style="color: #fff;">${character.abilities.wis} (${character.modifiers.wis >= 0 ? '+' : ''}${character.modifiers.wis})</div>
                            </div>
                            <div style="text-align: center; background: rgba(0,0,0,0.2); padding: 8px; border-radius: 5px;">
                                <div style="color: #ccc; font-size: 12px;">Харизма</div>
                                <div style="color: #fff;">${character.abilities.cha} (${character.modifiers.cha >= 0 ? '+' : ''}${character.modifiers.cha})</div>
                            </div>
                        </div>
                        
                        <div style="margin-top: 20px; text-align: center;">
                            <button onclick="saveCharacterToNotes('${character.name}', '${character.race}', '${character.class}')" class="btn btn-success" style="margin-right: 10px;">
                                💾 Сохранить в заметки
                            </button>
                            <button onclick="generateSimpleCharacter()" class="btn btn-primary" style="margin-right: 10px;">
                                🔄 Сгенерировать заново
                            </button>
                            <button onclick="closeModal()" class="btn btn-secondary">
                                ❌ Закрыть
                            </button>
                        </div>
                    </div>
                `;

                    document.getElementById('char-result').innerHTML = resultHtml;

                    // Автоматически сохраняем персонажа
                    saveCharacterToNotes(character.name, character.race, character.class);

                } else {
                    document.getElementById('char-result').innerHTML = `
                    <div style="background: rgba(255,0,0,0.1); padding: 20px; border-radius: 10px; color: #ff6b6b; text-align: center;">
                        <h3>Ошибка генерации</h3>
                        <p>${data.message || 'Неизвестная ошибка'}</p>
                        <button onclick="generateSimpleCharacter()" class="btn btn-primary">Попробовать снова</button>
                    </div>
                `;
                }
            }, 500);
        })
        .catch(error => {
            console.error('Generation error:', error);
            clearInterval(interval);
            document.getElementById('char-progress').style.display = 'none';
            document.getElementById('char-result').style.display = 'block';
            document.getElementById('char-result').innerHTML = `
            <div style="background: rgba(255,0,0,0.1); padding: 20px; border-radius: 10px; color: #ff6b6b; text-align: center;">
                <h3>Ошибка сети</h3>
                <p>${error.message}</p>
                <button onclick="generateSimpleCharacter()" class="btn btn-primary">Попробовать снова</button>
            </div>
        `;
        });
}

// --- Функция сохранения персонажа в заметки ---
function saveCharacterToNotes(name, race, charClass) {
    const noteContent = `<div><strong>${name}</strong> - ${race} ${charClass}</div>`;

    fetch('api/save-note.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'content=' + encodeURIComponent(noteContent) + '&title=' + encodeURIComponent(name)
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                console.log('Персонаж сохранен в заметки!');
            }
        })
        .catch(error => {
            console.error('Ошибка сохранения:', error);
        });
}

console.log('Character generator functions defined');