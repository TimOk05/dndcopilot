<?php
session_start();
require_once '../app/Middleware/auth.php';

// Language Service будет переписан для новой архитектуры

// Мобильная версия будет переписана позже

// Проверяем авторизацию
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Мобильная версия временно отключена

// Получаем имя текущего пользователя
$currentUser = $_SESSION['username'] ?? 'Пользователь';

// Язык по умолчанию - русский
$currentLanguage = 'ru';



// --- Заметки ---
if (!isset($_SESSION['notes'])) {
    $_SESSION['notes'] = [];
}
if (isset($_POST['add_note']) && isset($_POST['note_content'])) {
    $_SESSION['notes'][] = trim($_POST['note_content']);
    exit('OK');
}
if (isset($_POST['remove_note'])) {
    $idx = (int)$_POST['remove_note'];
    if (isset($_SESSION['notes'][$idx])) {
        array_splice($_SESSION['notes'], $idx, 1);
    }
    exit('OK');
}

// --- Быстрые генерации через AJAX ---
if (isset($_POST['fast_action'])) {
    $action = $_POST['fast_action'];
    error_log('fast_action called with action: ' . $action);
    error_log('Request URI: ' . $_SERVER['REQUEST_URI']);
    error_log('Script name: ' . $_SERVER['SCRIPT_NAME']);
    // --- Кости ---
    if ($action === 'dice_result') {
        $dice = $_POST['dice'] ?? '1d20';
        $label = $_POST['label'] ?? '';
        if (preg_match('/^(\d{1,2})d(\d{1,3})$/', $dice, $m)) {
            $count = (int)$m[1]; $sides = (int)$m[2];
            $results = [];
            for ($i = 0; $i < $count; $i++) $results[] = rand(1, $sides);
            $sum = array_sum($results);
            // Формируем результат в зависимости от количества костей
            if ($count == 1) {
                $out = "🎲 Бросок: $dice\n📊 Результат: " . $results[0];
            } else {
                $out = "🎲 Бросок: $dice\n📊 Результаты: " . implode(', ', $results) . "\n💎 Сумма: $sum";
            }
            if ($label) $out .= "\n💬 Комментарий: $label";
            echo nl2br(htmlspecialchars($out));
            exit;
        } else {
            echo 'Неверный формат кубов!';
            exit;
        }
    }
    // --- Сохранение заметки инициативы ---
    if ($action === 'save_note') {
        // Логируем запрос для отладки
        error_log('save_note called with content: ' . substr($_POST['content'] ?? '', 0, 100));
        error_log('save_note REQUEST_URI: ' . $_SERVER['REQUEST_URI']);
        error_log('save_note SCRIPT_NAME: ' . $_SERVER['SCRIPT_NAME']);
        
        $content = $_POST['content'] ?? '';
        $title = $_POST['title'] ?? '';
        
        // Инициализируем массив заметок, если его нет
        if (!isset($_SESSION['notes'])) {
            $_SESSION['notes'] = [];
        }
        
        if ($content) {
            // Если есть заголовок, добавляем его в начало заметки
            if ($title) {
                $content = "<h3>$title</h3>" . $content;
            }
            
            $_SESSION['notes'][] = $content;
            echo 'OK';
        } else {
            echo 'Ошибка: пустое содержимое';
        }
        exit;
    }
    // --- Обновление отображения заметок ---
    if ($action === 'update_notes') {
        $notes = $_SESSION['notes'] ?? [];
        $html = '';
        foreach ($notes as $i => $note) {
            $nameLine = '';
            
            // Ищем имя в заголовках персонажей и противников
            if (preg_match('/<div class="character-note-title">([^<]+)<\/div>/iu', $note, $matches)) {
                $nameLine = trim($matches[1]);
            } elseif (preg_match('/<div class="enemy-note-title">([^<]+)<\/div>/iu', $note, $matches)) {
                $nameLine = trim($matches[1]);
            } elseif (preg_match('/<div class="npc-name-header">([^<]+)<\/div>/iu', $note, $matches)) {
                $nameLine = trim($matches[1]);
            } elseif (preg_match('/<div class="npc-modern-header">([^<]+)<\/div>/iu', $note, $matches)) {
                $nameLine = trim($matches[1]);
            } elseif (preg_match('/<div class="dice-result-header">([^<]+)<\/div>/iu', $note, $matches)) {
                $nameLine = trim($matches[1]);
            } else {
                // Для старых заметок ищем строку с именем по разным вариантам
                $plain = strip_tags(str_replace(['<br>', "\n"], "\n", $note));
                $lines = array_filter(array_map('trim', explode("\n", $plain)));
                
                foreach ($lines as $line) {
                    if (preg_match('/^(Имя|Name|Имя NPC|Имя персонажа)\s*:/iu', $line)) {
                        $nameLine = $line;
                        break;
                    }
                }
                
                // Если нашли имя, извлекаем только имя без префикса
                if ($nameLine) {
                    if (preg_match('/^(Имя|Name|Имя NPC|Имя персонажа)\s*:\s*(.+)$/iu', $nameLine, $matches)) {
                        $nameLine = trim($matches[2]);
                    }
                }
                
                // Если это не NPC заметка, ищем первое значимое слово
                if (!$nameLine) {
                    foreach ($lines as $line) {
                        $line = trim($line);
                        if ($line && !preg_match('/^(описание|внешность|черты|способность|оружие|урон|хиты|класс|раса|уровень|профессия)/iu', $line)) {
                            $nameLine = $line;
                            break;
                        }
                    }
                }
            }
            
            // Очищаем имя - убираем лишние символы
            if ($nameLine) {
                $nameLine = preg_replace('/[^\wа-яё\s]/ui', '', $nameLine);
                $nameLine = trim($nameLine);
                
                // Если имя слишком длинное, обрезаем
                if (mb_strlen($nameLine) > 20) {
                    $nameLine = mb_substr($nameLine, 0, 20) . '…';
                }
            }
            
            $preview = $nameLine ?: '(нет данных)';
            // Проверяем, является ли заметка зельем
            $isPotionNote = strpos($note, 'potion-note-header') !== false || strpos($note, 'Зелье') !== false || strpos($note, '🧪') !== false;
            $editButton = $isPotionNote ? '' : '<button class="note-edit" onclick="event.stopPropagation();editNoteTitle(' . $i . ', \'' . htmlspecialchars($nameLine, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '\')">✏️</button>';
            
            $potionClass = $isPotionNote ? ' potion-note' : '';
            $html .= '<div class="note-item' . $potionClass . '" onclick="expandNote(' . $i . ')">' . htmlspecialchars($preview, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . $editButton . '<button class="note-remove" onclick="event.stopPropagation();removeNote(' . $i . ')">×</button></div>';
        }
        echo $html;
        exit;
    }
    // --- Редактирование заголовка заметки ---
    if ($action === 'edit_note_title') {
        $noteIndex = (int)($_POST['note_index'] ?? -1);
        $newTitle = trim($_POST['new_title'] ?? '');
        
        if ($noteIndex >= 0 && $noteIndex < count($_SESSION['notes']) && $newTitle !== '') {
            $note = $_SESSION['notes'][$noteIndex];
            
            // Заменяем заголовок в зависимости от типа заметки
            if (preg_match('/<div class="dice-result-header">[^<]+<\/div>/iu', $note)) {
                // Для результатов костей
                $note = preg_replace('/<div class="dice-result-header">[^<]+<\/div>/iu', '<div class="dice-result-header">' . htmlspecialchars($newTitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</div>', $note);
            } elseif (preg_match('/<div class="npc-name-header">[^<]+<\/div>/iu', $note)) {
                // Для NPC
                $note = preg_replace('/<div class="npc-name-header">[^<]+<\/div>/iu', '<div class="npc-name-header">' . htmlspecialchars($newTitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</div>', $note);
            } elseif (preg_match('/<div class="character-note-title">[^<]+<\/div>/iu', $note)) {
                // Для персонажей
                $note = preg_replace('/<div class="character-note-title">[^<]+<\/div>/iu', '<div class="character-note-title">' . htmlspecialchars($newTitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</div>', $note);
            } elseif (preg_match('/<div class="enemy-note-title">[^<]+<\/div>/iu', $note)) {
                // Для противников
                $note = preg_replace('/<div class="enemy-note-title">[^<]+<\/div>/iu', '<div class="enemy-note-title">' . htmlspecialchars($newTitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</div>', $note);
            }
            
            $_SESSION['notes'][$noteIndex] = $note;
            echo 'success';
        } else {
            echo 'error';
        }
        exit;
    }
    
    // --- Получение данных заметок ---
    if ($action === 'get_notes_data') {
        header('Content-Type: application/json');
        echo json_encode($_SESSION['notes'] ?? [], JSON_UNESCAPED_UNICODE);
        exit;
    }
    echo 'Неизвестное действие';
    exit;
}

// --- Чат ---
if (!isset($_SESSION['chat'])) {
    $_SESSION['chat'] = [];
}
if (isset($_GET['reset'])) {
    $_SESSION['chat'] = [];
    header("Location: index.php");
    exit;
}

// --- Новый systemInstruction с усиленными требованиями ---
$systemInstruction = "Ты — помощник мастера DnD. Твоя задача — сгенерировать NPC для быстрого и удобного вывода в игровом приложении. Каждый блок будет отображаться отдельно, поэтому не добавляй пояснений, не используй лишние слова, не пиши ничего кроме блоков.\nСтрого по шаблону, каждый блок с новой строки:\nИмя: ...\nКраткое описание: ...\nЧерта характера: ...\nСлабость: ...\nКороткая характеристика: Оружие: ... Урон: ... Хиты: ... Способность: ...\n\nВАЖНО: НЕ используй слово 'Описание' в начале блоков. Начинай блоки сразу с содержимого. НЕ дублируй информацию между блоками. Каждый блок должен содержать только релевантную информацию.

ВАЖНО: Способность — это конкретный навык персонажа в D&D, например: 'Двойная атака', 'Исцеление ран', 'Скрытность', 'Божественная кара', 'Ярость', 'Вдохновение', 'Магическая защита', 'Элементальная магия', 'Боевой стиль', 'Связь с природой', 'Боевые искусства', 'Скрытные способности', 'Магическое исследование', 'Общение с животными', 'Магическая обработка', 'Магическое красноречие'. НЕ пиши описания, только название способности. ОБЯЗАТЕЛЬНО указывай способность для каждого класса кроме 'Без класса'.\nТехнические параметры (Оружие, Урон, Хиты, Способность) обязательны и всегда идут первым блоком. Если не можешь заполнить какой-то параметр — напиши '-'. Не добавляй ничего лишнего.";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message']) && !isset($_POST['add_note']) && !isset($_POST['remove_note'])) {
    $userMessage = trim($_POST['message']);
    if ($userMessage !== '') {
        if (empty($_SESSION['chat']) || $_SESSION['chat'][0]['role'] !== 'system') {
            array_unshift($_SESSION['chat'], ['role' => 'system', 'content' => $systemInstruction]);
        }
        $_SESSION['chat'][] = ['role' => 'user', 'content' => $userMessage];
        $apiKey = 'sk-1e898ddba737411e948af435d767e893';
        $apiUrl = 'https://api.deepseek.com/v1/chat/completions';
        $messages = array_map(function($msg) {
            return ['role' => $msg['role'], 'content' => $msg['content']];
        }, $_SESSION['chat']);
        $data = [
            'model' => 'deepseek-chat',
            'messages' => $messages
        ];
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        $response = curl_exec($ch);
        curl_close($ch);
        $result = json_decode($response, true);
        $aiMessage = $result['choices'][0]['message']['content'] ?? '[Ошибка AI]';
        $aiMessage = preg_replace('/[*_`>#\-]+/', '', $aiMessage);
        $aiMessage = str_replace(['"', "'", '“', '”', '«', '»'], '', $aiMessage);
        $aiMessage = preg_replace('/\n{2,}/', "\n", $aiMessage);
        $aiMessage = preg_replace('/\s{3,}/', "\n", $aiMessage);
        $lines = explode("\n", $aiMessage);
        $formatted = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if (mb_strlen($line) > 90) {
                $formatted = array_merge($formatted, str_split($line, 80));
            } else {
                $formatted[] = $line;
            }
        }
        $aiMessage = implode("\n", $formatted);
        $_SESSION['chat'][] = ['role' => 'assistant', 'content' => $aiMessage];
    }
}

// --- Генерация быстрых кнопок ---
$fastBtns = '<div class="button-grid">';
$fastBtns .= '<button class="fast-btn btn btn-primary interactive" onclick="openDiceStep1()" data-tooltip="Бросить кости" aria-label="Открыть генератор бросков костей"><span class="svg-icon icon-dice" data-icon="dice"></span> Кости</button>';
$fastBtns .= '<button class="fast-btn btn btn-primary interactive" onclick="openCharacterModal()" data-tooltip="Создать персонажа" aria-label="Открыть генератор персонажей"><span class="svg-icon icon-hero" data-icon="hero"></span> Персонаж</button>';
$fastBtns .= '<button class="fast-btn btn btn-primary interactive" onclick="openEnemyModal()" data-tooltip="Создать противника" aria-label="Открыть генератор противников"><span class="svg-icon icon-enemy" data-icon="enemy"></span> Противники</button>';
$fastBtns .= '<button class="fast-btn btn btn-primary interactive" onclick="openPotionModalSimple()" data-tooltip="Создать зелье" aria-label="Открыть генератор зелий"><span class="svg-icon icon-potion" data-icon="potion"></span> Зелья</button>';
$fastBtns .= '<button class="fast-btn btn btn-primary interactive" onclick="openSpellModal()" data-tooltip="Создать заклинания" aria-label="Открыть генератор заклинаний"><span class="svg-icon icon-spell" data-icon="spell"></span> Заклинания</button>';
$fastBtns .= '<button class="fast-btn btn btn-primary interactive" onclick="openInitiativeModal()" data-tooltip="Управление инициативой" aria-label="Открыть управление инициативой"><span class="svg-icon icon-initiative" data-icon="initiative"></span> Инициатива</button>';
$fastBtns .= '</div>';

// --- Генерация сообщений чата (пропускаем system) ---
$chatMsgs = '';
foreach ($_SESSION['chat'] as $msg) {
    if ($msg['role'] === 'system') continue;
    $who = $msg['role'] === 'user' ? 'Вы' : 'AI';
    $class = $msg['role'];
    $chatMsgs .= '<div class="msg ' . $class . '"><b>' . $who . ':</b> ' . nl2br(htmlspecialchars($msg['content'])) . '</div>';
}

// --- Генерация блока заметок ---
$notesBlock = '';
foreach ($_SESSION['notes'] as $i => $note) {
    $nameLine = '';
    
    // Ищем имя в заголовках персонажей и противников
    if (preg_match('/<div class="character-note-title">([^<]+)<\/div>/iu', $note, $matches)) {
        $nameLine = trim($matches[1]);
    } elseif (preg_match('/<div class="enemy-note-title">([^<]+)<\/div>/iu', $note, $matches)) {
        $nameLine = trim($matches[1]);
    } elseif (preg_match('/<div class="npc-name-header">([^<]+)<\/div>/iu', $note, $matches)) {
        $nameLine = trim($matches[1]);
    } elseif (preg_match('/<div class="npc-modern-header">([^<]+)<\/div>/iu', $note, $matches)) {
        $nameLine = trim($matches[1]);
    } elseif (preg_match('/<div class="dice-result-header">([^<]+)<\/div>/iu', $note, $matches)) {
        $nameLine = trim($matches[1]);
    } else {
        // Для старых заметок ищем строку с именем по разным вариантам
        $plain = strip_tags(str_replace(['<br>', "\n"], "\n", $note));
        $lines = array_filter(array_map('trim', explode("\n", $plain)));
        
        foreach ($lines as $line) {
            if (preg_match('/^(Имя|Name|Имя NPC|Имя персонажа)\s*:/iu', $line)) {
                $nameLine = $line;
                break;
            }
        }
        
        // Если нашли имя, извлекаем только имя без префикса
        if ($nameLine) {
            if (preg_match('/^(Имя|Name|Имя NPC|Имя персонажа)\s*:\s*(.+)$/iu', $nameLine, $matches)) {
                $nameLine = trim($matches[2]);
            }
        }
        
        // Если это не NPC заметка, ищем первое значимое слово
        if (!$nameLine) {
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line && !preg_match('/^(описание|внешность|черты|способность|оружие|урон|хиты|класс|раса|уровень|профессия)/iu', $line)) {
                    $nameLine = $line;
                    break;
                }
            }
        }
    }
    
    // Очищаем имя - убираем лишние символы
    if ($nameLine) {
        $nameLine = preg_replace('/[^\wа-яё\s]/ui', '', $nameLine);
        $nameLine = trim($nameLine);
        
        // Если имя слишком длинное, обрезаем
        if (mb_strlen($nameLine) > 20) {
            $nameLine = mb_substr($nameLine, 0, 20) . '…';
        }
    }
    
    $preview = $nameLine ?: '(нет данных)';
    // Проверяем, является ли заметка зельем
    $isPotionNote = strpos($note, 'potion-note-header') !== false || strpos($note, 'Зелье') !== false || strpos($note, '🧪') !== false;
    $editButton = $isPotionNote ? '' : '<button class="note-edit" onclick="event.stopPropagation();editNoteTitle(' . $i . ', \'' . htmlspecialchars($nameLine, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '\')">✏️</button>';
    
    $potionClass = $isPotionNote ? ' potion-note' : '';
    $notesBlock .= '<div class="note-item' . $potionClass . '" onclick="expandNote(' . $i . ')">' . htmlspecialchars($preview, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . $editButton . '<button class="note-remove" onclick="event.stopPropagation();removeNote(' . $i . ')">×</button></div>';
}

// --- Загрузка шаблона и подстановка контента ---
$template = file_get_contents(__DIR__ . '/template.html');
$template = str_replace('{{fast_buttons}}', $fastBtns, $template);
$template = str_replace('{{chat_messages}}', $chatMsgs, $template);
$template = str_replace('{{notes_block}}', $notesBlock, $template);
echo $template;
?>


<script>
// --- Dice Modal Steps ---
function openDiceStep1() {
    showModal('<b class="mini-menu-title">Выберите тип кости:</b><div class="mini-menu-btns">' +
        ['d3','d4','d6','d8','d10','d12','d20','d100'].map(d => `<button onclick=\'openDiceStep2("${d}")\' class=\'fast-btn\'>${d}</button>`).join(' ') + '</div>'
    );
    document.getElementById('modal-save').style.display = 'none';
}
function openDiceStep2(dice) {
    showModal(`<b class="mini-menu-title">Сколько бросков ${dice}?</b><div class="npc-level-wrap"><input type=number id=dice-count value=1 min=1 max=20 style=\'width:60px\'></div><div class="npc-level-wrap"><input type=text id=dice-label placeholder=\'Комментарий (необязательно)\' style=\'margin-top:8px;width:90%\'></div><button class=\'fast-btn\' onclick=\'getDiceResult("${dice}")\'>Бросить</button>`);
    document.getElementById('modal-save').style.display = 'none';
    // Автофокус на поле количества
    setTimeout(() => document.getElementById('dice-count').focus(), 100);
}
function getDiceResult(dice) {
    let count = document.getElementById('dice-count').value;
    let label = document.getElementById('dice-label').value;
    let diceStr = count + dice;
    fetch('', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'fast_action=dice_result&dice=' + encodeURIComponent(diceStr) + '&label=' + encodeURIComponent(label)
    })
    .then(r => r.text())
    .then(txt => {
        // Добавляем кнопку редактирования комментария
        const editButton = `<button class="fast-btn" onclick="editDiceComment('${dice}', '${count}', '${label}')" style="margin-bottom: 10px;">✏️ Редактировать комментарий</button>`;
        
        document.getElementById('modal-content').innerHTML = editButton + formatResultSegments(txt, false);
        document.getElementById('modal-save').style.display = '';
        document.getElementById('modal-save').onclick = function() { saveDiceResultAsNote(txt, label); closeModal(); };
        
        // Автоматически сохраняем результат броска в заметки
        saveDiceResultAsNote(txt, label);
    });
}

// Функция для редактирования комментария
function editDiceComment(dice, count, currentLabel) {
    showModal(`<b class="mini-menu-title">Редактировать комментарий для ${count}${dice}</b>
        <div class="npc-level-wrap">
            <input type="text" id="edit-dice-label" placeholder="Комментарий (необязательно)" value="${currentLabel}" style="width:90%">
        </div>
        <button class="fast-btn" onclick="updateDiceComment(\'' + dice + '\', \'' + count + '\')">Обновить</button>`);
    document.getElementById('modal-save').style.display = 'none';
    setTimeout(() => document.getElementById('edit-dice-label').focus(), 100);
}

// Функция для обновления комментария
function updateDiceComment(dice, count) {
    let newLabel = document.getElementById('edit-dice-label').value;
    let diceStr = count + dice;
    fetch('', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'fast_action=dice_result&dice=' + encodeURIComponent(diceStr) + '&label=' + encodeURIComponent(newLabel)
    })
    .then(r => r.text())
    .then(txt => {
        // Добавляем кнопку редактирования комментария
        const editButton = `<button class="fast-btn" onclick="editDiceComment('${dice}', '${count}', '${newLabel}')" style="margin-bottom: 10px;">✏️ Редактировать комментарий</button>`;
        
        document.getElementById('modal-content').innerHTML = editButton + formatResultSegments(txt, false);
        document.getElementById('modal-save').style.display = '';
        document.getElementById('modal-save').onclick = function() { saveDiceResultAsNote(txt, newLabel); closeModal(); };
        
        // Автоматически сохраняем обновленный результат броска в заметки
        saveDiceResultAsNote(txt, newLabel);
    });
}
// --- Генерация персонажей и противников ---
// Статические данные удалены - теперь используются внешние API







// --- Функция открытия генерации персонажей ---
function openCharacterModal() {
    showModal(`
        <div class="character-generator">
            <div class="generator-header">
                <h2 style="color: var(--text-color, #e0e0e0); margin-bottom: 10px;">🎭 Генератор персонажей</h2>
                <p class="generator-subtitle" style="color: var(--text-color, #e0e0e0); opacity: 0.8; margin: 0;">Создайте уникального персонажа D&D 5e с помощью AI</p>
            </div>
            
            <div class="character-form-container">
                <form id="newCharacterForm" class="character-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="new-character-race">Раса</label>
                            <select id="new-character-race" name="race" required>
                                <option value="">Загрузка рас...</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="new-character-class">Класс</label>
                            <select id="new-character-class" name="class" required>
                                <option value="">Загрузка классов...</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="new-character-level">Уровень</label>
                            <select id="new-character-level" name="level" required>
                                <option value="1">1 уровень</option>
                                <option value="2">2 уровень</option>
                                <option value="3">3 уровень</option>
                                <option value="4">4 уровень</option>
                                <option value="5">5 уровень</option>
                                <option value="6">6 уровень</option>
                                <option value="7">7 уровень</option>
                                <option value="8">8 уровень</option>
                                <option value="9">9 уровень</option>
                                <option value="10">10 уровень</option>
                                <option value="11">11 уровень</option>
                                <option value="12">12 уровень</option>
                                <option value="13">13 уровень</option>
                                <option value="14">14 уровень</option>
                                <option value="15">15 уровень</option>
                                <option value="16">16 уровень</option>
                                <option value="17">17 уровень</option>
                                <option value="18">18 уровень</option>
                                <option value="19">19 уровень</option>
                                <option value="20">20 уровень</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="new-character-gender">Пол</label>
                            <select id="new-character-gender" name="gender">
                                <option value="random">Случайный</option>
                                <option value="male">Мужской</option>
                                <option value="female">Женский</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="new-character-alignment">Мировоззрение</label>
                            <select id="new-character-alignment" name="alignment">
                                <option value="random">Случайное</option>
                                <option value="lawful_good">Законопослушный добрый</option>
                                <option value="neutral_good">Нейтральный добрый</option>
                                <option value="chaotic_good">Хаотичный добрый</option>
                                <option value="lawful_neutral">Законопослушный нейтральный</option>
                                <option value="true_neutral">Истинно нейтральный</option>
                                <option value="chaotic_neutral">Хаотичный нейтральный</option>
                                <option value="lawful_evil">Законопослушный злой</option>
                                <option value="neutral_evil">Нейтральный злой</option>
                                <option value="chaotic_evil">Хаотичный злой</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="new-character-background">Предыстория</label>
                            <select id="new-character-background" name="background">
                                <option value="random">Случайная</option>
                                <option value="">Загрузка предысторий...</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group full-width">
                            <label for="new-character-ability-method">Метод генерации характеристик</label>
                            <select id="new-character-ability-method" name="ability_method">
                                <option value="standard_array">Стандартный массив (15,14,13,12,10,8)</option>
                                <option value="point_buy">Покупка очков (27 очков)</option>
                                <option value="roll_4d6">4d6 drop lowest</option>
                                <option value="roll_3d6">3d6 (классический)</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <span class="btn-icon">🎲</span>
                            Создать персонажа
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="closeModal()">
                            <span class="btn-icon">❌</span>
                            Отмена
                        </button>
                    </div>
                </form>
                
                <div id="characterProgress" class="character-progress" style="display: none;">
                    <div class="progress-container">
                        <div class="progress-bar">
                            <div class="progress-fill"></div>
                        </div>
                        <div class="progress-text" id="characterProgressText">Создание персонажа...</div>
                    </div>
                </div>
                
                <div id="characterResult" class="character-result" style="display: none;">
                    <!-- Результат будет вставлен сюда -->
                </div>
            </div>
        </div>
    `);
    
    document.getElementById('modal-save').style.display = 'none';
    
    // Загружаем данные
    loadNewCharacterData();
    
    // Обработчик формы
    document.getElementById('newCharacterForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const resultDiv = document.getElementById('characterResult');
        const progressDiv = document.getElementById('characterProgress');
        const formContainer = document.querySelector('.character-form-container');
        
        // Скрываем форму и показываем прогресс
        formContainer.style.display = 'none';
        progressDiv.style.display = 'block';
        
        // Анимация прогресса
        const progressFill = progressDiv.querySelector('.progress-fill');
        const progressText = progressDiv.querySelector('#characterProgressText');
        let progress = 0;
        const progressInterval = setInterval(() => {
            progress += Math.random() * 15;
            if (progress > 90) progress = 90;
            progressFill.style.width = progress + '%';
            
            if (progress < 30) {
                progressText.textContent = 'Загрузка данных персонажа...';
            } else if (progress < 60) {
                progressText.textContent = 'Генерация характеристик...';
            } else if (progress < 90) {
                progressText.textContent = 'Создание описания с помощью AI...';
            } else {
                progressText.textContent = 'Завершение генерации...';
            }
        }, 200);
        
        fetch('api/generate-characters.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('Generation response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('Generation response data:', JSON.stringify(data, null, 2));
            clearInterval(progressInterval);
            progressFill.style.width = '100%';
            
            setTimeout(() => {
                progressDiv.style.display = 'none';
                resultDiv.style.display = 'block';
                
                if (data.success) {
                    const character = data.character;
                    resultDiv.innerHTML = formatNewCharacter(character);
                    
                    // Добавляем кнопку сохранения
                    const saveButton = document.createElement('div');
                    saveButton.className = 'character-actions';
                    saveButton.innerHTML = `
                        <button onclick="saveNewCharacterToNotes()" class="btn btn-success">
                            <span class="btn-icon">💾</span>
                            Сохранить в заметки
                        </button>
                        <button onclick="regenerateCharacter()" class="btn btn-primary">
                            <span class="btn-icon">🔄</span>
                            Сгенерировать заново
                        </button>
                        <button onclick="closeModal()" class="btn btn-secondary">
                            <span class="btn-icon">❌</span>
                            Закрыть
                        </button>
                    `;
                    resultDiv.appendChild(saveButton);
                    
                    // Автоматически сохраняем персонажа
                    saveNewCharacterToNotes(character);
                } else {
                    resultDiv.innerHTML = '<div class="error">Ошибка: ' + (data.message || 'Неизвестная ошибка') + '</div>';
                }
            }, 500);
        })
        .catch(error => {
            clearInterval(progressInterval);
            progressDiv.style.display = 'none';
            formContainer.style.display = 'block';
            console.error('Generation error:', error);
            alert('Ошибка сети: ' + error.message);
        });
    });
}

// --- Вспомогательные функции для нового генератора персонажей ---
function loadNewCharacterData() {
    // Загружаем расы
    fetch('api/generate-characters.php?action=races')
        .then(response => {
            console.log('Races response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('Races data:', JSON.stringify(data, null, 2));
            if (data.success && data.races) {
                const raceSelect = document.getElementById('new-character-race');
                raceSelect.innerHTML = '<option value="">Выберите расу</option>';
                data.races.forEach(race => {
                    const option = document.createElement('option');
                    option.value = race.id;
                    option.textContent = race.name_ru || race.name;
                    raceSelect.appendChild(option);
                    console.log('Added race option:', race.id, race.name_ru || race.name);
                });
                console.log('Races loaded successfully:', data.races.length);
            } else {
                console.error('Failed to load races:', data);
            }
        })
        .catch(error => {
            console.error('Error loading races:', error);
        });
    
    // Загружаем классы
    fetch('api/generate-characters.php?action=classes')
        .then(response => {
            console.log('Classes response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('Classes data:', JSON.stringify(data, null, 2));
            if (data.success && data.classes) {
                const classSelect = document.getElementById('new-character-class');
                classSelect.innerHTML = '<option value="">Выберите класс</option>';
                data.classes.forEach(cls => {
                    const option = document.createElement('option');
                    option.value = cls.id;
                    option.textContent = cls.name_ru || cls.name;
                    classSelect.appendChild(option);
                    console.log('Added class option:', cls.id, cls.name_ru || cls.name);
                });
                console.log('Classes loaded successfully:', data.classes.length);
            } else {
                console.error('Failed to load classes:', data);
            }
        })
        .catch(error => {
            console.error('Error loading classes:', error);
        });
    
    // Предыстории пока статичные
    const backgroundSelect = document.getElementById('new-character-background');
    backgroundSelect.innerHTML = `
        <option value="random">Случайная</option>
        <option value="acolyte">Аколит</option>
        <option value="criminal">Преступник</option>
        <option value="folk_hero">Народный герой</option>
        <option value="noble">Дворянин</option>
        <option value="soldier">Солдат</option>
        <option value="sage">Мудрец</option>
        <option value="sailor">Матрос</option>
        <option value="guild_artisan">Гильдейский ремесленник</option>
        <option value="hermit">Отшельник</option>
        <option value="outlander">Бродяга</option>
    `;
}

function formatNewCharacter(character) {
    let html = `
        <div class="character-card">
            <div class="character-header">
                <h3>${character.name}</h3>
                <div class="character-subtitle">${character.race} - ${character.class} (${character.level} уровень)</div>
            </div>
            
            <div class="character-stats">
                <div class="stat-row">
                    <div class="stat-item"><strong>Пол:</strong> ${character.gender}</div>
                    <div class="stat-item"><strong>Мировоззрение:</strong> ${character.alignment}</div>
                    <div class="stat-item"><strong>Предыстория:</strong> ${character.background}</div>
                </div>
                
                <div class="stat-row">
                    <div class="stat-item"><strong>Хиты:</strong> ${character.hit_points}</div>
                    <div class="stat-item"><strong>КД:</strong> ${character.armor_class}</div>
                    <div class="stat-item"><strong>Скорость:</strong> ${character.speed} футов</div>
                </div>
                
                <div class="abilities-section">
                    <h4>Характеристики</h4>
                    <div class="abilities-grid">
                        <div class="ability-item">СИЛ: ${character.abilities.str} (${character.modifiers.str >= 0 ? '+' : ''}${character.modifiers.str})</div>
                        <div class="ability-item">ЛОВ: ${character.abilities.dex} (${character.modifiers.dex >= 0 ? '+' : ''}${character.modifiers.dex})</div>
                        <div class="ability-item">ТЕЛ: ${character.abilities.con} (${character.modifiers.con >= 0 ? '+' : ''}${character.modifiers.con})</div>
                        <div class="ability-item">ИНТ: ${character.abilities.int} (${character.modifiers.int >= 0 ? '+' : ''}${character.modifiers.int})</div>
                        <div class="ability-item">МДР: ${character.abilities.wis} (${character.modifiers.wis >= 0 ? '+' : ''}${character.modifiers.wis})</div>
                        <div class="ability-item">ХАР: ${character.abilities.cha} (${character.modifiers.cha >= 0 ? '+' : ''}${character.modifiers.cha})</div>
                    </div>
                </div>
                
                ${character.description ? `
                    <div class="character-description">
                        <h4>Описание</h4>
                        <p>${character.description}</p>
                    </div>
                ` : ''}
                
                ${character.background_story ? `
                    <div class="character-background">
                        <h4>Предыстория</h4>
                        <p>${character.background_story}</p>
                    </div>
                ` : ''}
                
                ${character.equipment ? `
                    <div class="character-equipment">
                        <h4>Снаряжение</h4>
                        ${character.equipment.weapons && character.equipment.weapons.length > 0 ? `
                            <div class="equipment-category">
                                <strong>Оружие:</strong>
                                <ul>${character.equipment.weapons.map(weapon => `<li>${weapon}</li>`).join('')}</ul>
                            </div>
                        ` : ''}
                        ${character.equipment.armor && character.equipment.armor.length > 0 ? `
                            <div class="equipment-category">
                                <strong>Броня:</strong>
                                <ul>${character.equipment.armor.map(armor => `<li>${armor}</li>`).join('')}</ul>
                            </div>
                        ` : ''}
                        ${character.equipment.items && character.equipment.items.length > 0 ? `
                            <div class="equipment-category">
                                <strong>Предметы:</strong>
                                <ul>${character.equipment.items.map(item => `<li>${item}</li>`).join('')}</ul>
                            </div>
                        ` : ''}
                        ${character.equipment.money ? `
                            <div class="equipment-category">
                                <strong>Деньги:</strong> ${character.equipment.money}
                            </div>
                        ` : ''}
                    </div>
                ` : ''}
                
                ${character.spells ? `
                    <div class="character-spells">
                        <h4>Заклинания</h4>
                        ${character.spells.cantrips && character.spells.cantrips.length > 0 ? `
                            <div class="spell-category">
                                <strong>Заговоры:</strong>
                                <ul>${character.spells.cantrips.map(spell => `<li>${spell}</li>`).join('')}</ul>
                            </div>
                        ` : ''}
                        ${character.spells.level_1 && character.spells.level_1.length > 0 ? `
                            <div class="spell-category">
                                <strong>1-й уровень:</strong>
                                <ul>${character.spells.level_1.map(spell => `<li>${spell}</li>`).join('')}</ul>
                            </div>
                        ` : ''}
                        ${character.spells.spellbook ? `
                            <div class="spell-info">
                                <strong>Книга заклинаний:</strong> ${character.spells.spellbook}
                            </div>
                        ` : ''}
                    </div>
                ` : ''}
                
                ${character.potions && character.potions.length > 0 ? `
                    <div class="character-potions">
                        <h4>Зелья</h4>
                        <ul>${character.potions.map(potion => `<li>${potion.name} - ${potion.effect}</li>`).join('')}</ul>
                    </div>
                ` : ''}
                
                ${character.personality ? `
                    <div class="character-personality">
                        <h4>Черты характера</h4>
                        ${character.personality.Идеал ? `
                            <div class="personality-trait">
                                <strong>Идеал:</strong> ${character.personality.Идеал.name} - ${character.personality.Идеал.description}
                            </div>
                        ` : ''}
                        ${character.personality.Привязанность ? `
                            <div class="personality-trait">
                                <strong>Привязанность:</strong> ${character.personality.Привязанность.name} - ${character.personality.Привязанность.description}
                            </div>
                        ` : ''}
                        ${character.personality.Недостаток ? `
                            <div class="personality-trait">
                                <strong>Недостаток:</strong> ${character.personality.Недостаток.name} - ${character.personality.Недостаток.description}
                            </div>
                        ` : ''}
                    </div>
                ` : ''}
            </div>
        </div>
    `;
    
    return html;
}

function saveNewCharacterToNotes(character) {
    const noteContent = `
        <div class="character-note">
            <div class="character-note-title">${character.name}</div>
            <div class="character-note-info">
                <div><strong>Раса:</strong> ${character.race}</div>
                <div><strong>Класс:</strong> ${character.class}</div>
                <div><strong>Уровень:</strong> ${character.level}</div>
                <div><strong>Пол:</strong> ${character.gender}</div>
                <div><strong>Мировоззрение:</strong> ${character.alignment}</div>
                <div><strong>Предыстория:</strong> ${character.background.name || character.background}</div>
                <div><strong>Хиты:</strong> ${character.hit_points}</div>
                <div><strong>КД:</strong> ${character.armor_class}</div>
                <div><strong>Скорость:</strong> ${character.speed} футов</div>
                <div><strong>Характеристики:</strong></div>
                <div style="margin-left: 20px;">
                    <div>СИЛ: ${character.abilities.str} (${character.modifiers.str >= 0 ? '+' : ''}${character.modifiers.str})</div>
                    <div>ЛОВ: ${character.abilities.dex} (${character.modifiers.dex >= 0 ? '+' : ''}${character.modifiers.dex})</div>
                    <div>ТЕЛ: ${character.abilities.con} (${character.modifiers.con >= 0 ? '+' : ''}${character.modifiers.con})</div>
                    <div>ИНТ: ${character.abilities.int} (${character.modifiers.int >= 0 ? '+' : ''}${character.modifiers.int})</div>
                    <div>МДР: ${character.abilities.wis} (${character.modifiers.wis >= 0 ? '+' : ''}${character.modifiers.wis})</div>
                    <div>ХАР: ${character.abilities.cha} (${character.modifiers.cha >= 0 ? '+' : ''}${character.modifiers.cha})</div>
                </div>
                ${character.description ? `<div><strong>Описание:</strong> ${character.description}</div>` : ''}
                ${character.background_story ? `<div><strong>Предыстория:</strong> ${character.background_story}</div>` : ''}
            </div>
        </div>
    `;
    
    fetch('api/save-note.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'content=' + encodeURIComponent(noteContent) + '&title=' + encodeURIComponent(character.name)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            console.log('Персонаж ' + character.name + ' сохранен в заметки!');
        } else {
            console.error('Ошибка сохранения:', data.message);
        }
    })
    .catch(error => {
        console.error('Ошибка сохранения:', error);
    });
}

function regenerateCharacter() {
    // Закрываем модальное окно и открываем заново
    closeModal();
    setTimeout(() => {
        openCharacterModal();
    }, 100);
}

// --- Функция открытия генерации противников ---
function openEnemyModal() {
    showModal(`
        <div class="enemy-generator">
            <div class="generator-header">
                <h2>Генератор противников</h2>
                <p class="generator-subtitle">Создайте подходящих противников для вашей группы</p>
            </div>
            
            <form id="enemyForm" class="enemy-form">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="enemy-threat">Уровень угрозы</label>
                        <select id="enemy-threat" name="threat_level" required>
                            <option value="">Выберите уровень угрозы</option>
                            <option value="easy">Легкий (CR 0-3)</option>
                            <option value="medium">Средний (CR 1-7)</option>
                            <option value="hard">Сложный (CR 5-12)</option>
                            <option value="deadly">Смертельный (CR 10-20)</option>
                            <option value="random">Случайный</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="enemy-count">Количество противников</label>
                        <input type="number" id="enemy-count" name="count" min="1" max="10" value="1" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="enemy-type">Тип противников</label>
                        <select id="enemy-type" name="enemy_type">
                            <option value="">Любой тип</option>
                            <option value="humanoid">Гуманоиды</option>
                            <option value="beast">Звери</option>
                            <option value="undead">Нежить</option>
                            <option value="giant">Великаны</option>
                            <option value="dragon">Драконы</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="enemy-environment">Среда обитания</label>
                        <select id="enemy-environment" name="environment">
                            <option value="">Любая среда</option>
                            <option value="arctic">Арктика</option>
                            <option value="coastal">Побережье</option>
                            <option value="desert">Пустыня</option>
                            <option value="forest">Лес</option>
                            <option value="grassland">Равнины</option>
                            <option value="hill">Холмы</option>
                            <option value="mountain">Горы</option>
                            <option value="swamp">Болота</option>
                            <option value="underdark">Подземелье</option>
                            <option value="urban">Город</option>
                        </select>
                    </div>
                    

                </div>
                

                
                <button type="submit" class="generate-btn">
                    <span class="btn-icon"><span class="svg-icon icon-enemy" data-icon="enemy"></span></span>
                    <span class="btn-text">Создать противников</span>
                </button>
            </form>
            
            <div id="enemyResult" class="result-container"></div>
        </div>
    `);
    
    document.getElementById('modal-save').style.display = 'none';
    
    // Динамическое обновление доступных типов и сред в зависимости от уровня сложности
    function updateAvailableOptions() {
        const threatLevel = document.getElementById('enemy-threat').value;
        const typeSelect = document.getElementById('enemy-type');
        const environmentSelect = document.getElementById('enemy-environment');
        
        // Сбрасываем выбор
        typeSelect.value = '';
        environmentSelect.value = '';
        
        // Определяем доступные типы для каждого уровня сложности
        const availableTypes = {
            'easy': ['humanoid', 'beast', 'undead'],
            'medium': ['humanoid', 'beast', 'undead', 'giant'],
            'hard': ['humanoid', 'beast', 'undead', 'giant', 'dragon'],
            'deadly': ['humanoid', 'undead', 'giant', 'dragon']
        };
        
        // Определяем доступные среды для каждого уровня сложности
        const availableEnvironments = {
            'easy': ['arctic', 'coastal', 'desert', 'forest', 'grassland', 'hill', 'urban'],
            'medium': ['arctic', 'coastal', 'desert', 'forest', 'grassland', 'hill', 'mountain', 'swamp', 'urban'],
            'hard': ['arctic', 'coastal', 'desert', 'forest', 'grassland', 'hill', 'mountain', 'swamp', 'underdark', 'urban'],
            'deadly': ['mountain', 'swamp', 'underdark', 'urban']
        };
        
        // Обновляем доступные типы
        Array.from(typeSelect.options).forEach(option => {
            if (option.value === '') return; // Пропускаем "Любой тип"
            
            if (threatLevel && availableTypes[threatLevel]) {
                option.disabled = !availableTypes[threatLevel].includes(option.value);
                if (option.disabled) {
                    option.style.display = 'none';
                } else {
                    option.style.display = 'block';
                }
            } else {
                option.disabled = false;
                option.style.display = 'block';
            }
        });
        
        // Обновляем доступные среды
        Array.from(environmentSelect.options).forEach(option => {
            if (option.value === '') return; // Пропускаем "Любая среда"
            
            if (threatLevel && availableEnvironments[threatLevel]) {
                option.disabled = !availableEnvironments[threatLevel].includes(option.value);
                if (option.disabled) {
                    option.style.display = 'none';
                } else {
                    option.style.display = 'block';
                }
            } else {
                option.disabled = false;
                option.style.display = 'block';
            }
        });
    }
    
    // Добавляем обработчики для динамического обновления
    document.getElementById('enemy-threat').addEventListener('change', updateAvailableOptions);
    
    // Инициализируем доступные опции
    updateAvailableOptions();
    
    // Добавляем обработчик формы
    document.getElementById('enemyForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        const resultDiv = document.getElementById('enemyResult');
        
        submitBtn.innerHTML = '<span class="btn-icon"><span class="svg-icon icon-loading" data-icon="loading"></span></span><span class="btn-text">Создание...</span>';
        submitBtn.disabled = true;
        resultDiv.innerHTML = '<div class="loading">Создание противников...</div>';
        

        
        fetch('api/generate-enemies.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('API Response:', data);
            if (data.success && data.enemies) {
                let resultHtml = formatEnemiesFromApi(data.enemies);
                
                // Убрано автоматическое сохранение в заметки - пользователь может сохранить вручную
                
                resultDiv.innerHTML = resultHtml;
                
                // Автоматическая прокрутка к результату
                setTimeout(() => {
                    resultDiv.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }, 100);
            } else {
                let errorMsg = data.error || 'Неизвестная ошибка';
                if (data.message) {
                    errorMsg = data.message;
                }
                resultDiv.innerHTML = '<div class="error">Ошибка: ' + errorMsg + '</div>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            let errorMessage = 'Ошибка сети. Попробуйте ещё раз.';
            
            if (error.message.includes('HTTP')) {
                errorMessage = `Ошибка сервера: ${error.message}`;
            } else if (error.name === 'TypeError' && error.message.includes('fetch')) {
                errorMessage = 'API недоступен. Проверьте подключение к интернету.';
            } else if (error.message.includes('Failed to fetch')) {
                errorMessage = 'Сервер недоступен. Проверьте, что сервер запущен.';
            }
            
            resultDiv.innerHTML = '<div class="error">' + errorMessage + '</div>';
        })
        .finally(() => {
            submitBtn.innerHTML = '<span class="btn-icon"><span class="svg-icon icon-enemy" data-icon="enemy"></span></span><span class="btn-text">Создать противников</span>';
            submitBtn.disabled = false;
        });
    });
}
function openNpcStepLevel(cls) {
    npcClass = cls;
    showModal(`
        <b class="mini-menu-title">Укажите уровень NPC (1-20):</b>
        <div class="npc-level-wrap">
            <input type=number id=npc-level value=1 min=1 max=20 style='width:60px'>
        </div>
        <div class="npc-advanced-settings" style="margin-top: 15px;">
            <button class='fast-btn' onclick='toggleAdvancedSettings()' style='background: var(--accent-info);'>⚙️ Расширенные настройки</button>
        </div>
        <div id="advanced-settings-panel" style="display: none; margin-top: 15px; padding: 15px; background: var(--bg-tertiary); border-radius: 8px; border: 1px solid var(--border-tertiary);">
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; color: var(--text-tertiary); font-weight: bold;">Пол:</label>
                <div class="advanced-options">
                    <label style="margin-right: 15px;"><input type="radio" name="gender" value="мужской" checked> Мужской</label>
                    <label style="margin-right: 15px;"><input type="radio" name="gender" value="женский"> Женский</label>
                    <label><input type="radio" name="gender" value="рандом"> Рандом</label>
                </div>
            </div>
            <div>
                <label style="display: block; margin-bottom: 5px; color: var(--text-tertiary); font-weight: bold;">Мировоззрение:</label>
                <div class="advanced-options">
                    <label style="margin-right: 15px;"><input type="radio" name="alignment" value="добрый" checked> Добрый</label>
                    <label style="margin-right: 15px;"><input type="radio" name="alignment" value="нейтральный"> Нейтральный</label>
                    <label style="margin-right: 15px;"><input type="radio" name="alignment" value="злой"> Злой</label>
                    <label><input type="radio" name="alignment" value="рандом"> Рандом</label>
                </div>
            </div>
            <div>
                <label style="display: block; margin-bottom: 5px; color: var(--text-tertiary); font-weight: bold;">Профессия:</label>
                <select id="npc-background" style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid var(--border-tertiary); background: var(--bg-primary); color: var(--text-primary);">
                    <option value="soldier">Солдат</option>
                    <option value="criminal">Преступник</option>
                    <option value="sage">Мудрец</option>
                    <option value="noble">Благородный</option>
                    <option value="merchant">Торговец</option>
                    <option value="artisan">Ремесленник</option>
                    <option value="farmer">Фермер</option>
                    <option value="hermit">Отшельник</option>
                    <option value="entertainer">Артист</option>
                    <option value="acolyte">Послушник</option>
                    <option value="outlander">Чужеземец</option>
                    <option value="urchin">Бродяга</option>
                </select>
            </div>
        </div>
        <div style="margin-top: 15px;">
            <button class='fast-btn' onclick='generateNpcWithLevel()'>Создать NPC</button>
        </div>
    `);
    document.getElementById('modal-save').style.display = 'none';
    // Автофокус на поле уровня
    setTimeout(() => document.getElementById('npc-level').focus(), 100);
}

// --- Функция переключения расширенных настроек ---
function toggleAdvancedSettings() {
    const panel = document.getElementById('advanced-settings-panel');
    const button = document.querySelector('.npc-advanced-settings .fast-btn');
    
    if (panel.style.display === 'none') {
        panel.style.display = 'block';
        button.innerHTML = '⚙️ Скрыть расширенные настройки';
        button.style.background = 'var(--accent-warning)';
    } else {
        panel.style.display = 'none';
        button.innerHTML = '⚙️ Расширенные настройки';
        button.style.background = 'var(--accent-info)';
    }
}
// --- Загрузка базы уникальных торговцев ---
window.uniqueTraders = {
  data: {
    traits: [
      'Любознательный и наблюдательный',
      'Осторожный и расчетливый',
      'Дружелюбный и общительный',
      'Гордый и независимый',
      'Мудрый и терпеливый'
    ],
    motivation: [
      'Поиск знаний и мудрости',
      'Защита близких и слабых',
      'Достижение власти и влияния',
      'Исследование мира и приключения',
      'Служение высшей цели'
    ],
    occupations: [
      { name_ru: 'Торговец' },
      { name_ru: 'Ремесленник' },
      { name_ru: 'Стражник' },
      { name_ru: 'Ученый' },
      { name_ru: 'Авантюрист' }
    ]
  }
};

// --- Загрузка механических параметров D&D ---
window.dndMechanics = {
  classes: {
    fighter: { casting_category: 'none', saving_throws: ['str', 'con'] },
    wizard: { casting_category: 'full', spellcasting_ability: 'int', saving_throws: ['int', 'wis'] },
    cleric: { casting_category: 'full', spellcasting_ability: 'wis', saving_throws: ['wis', 'cha'] },
    rogue: { casting_category: 'none', saving_throws: ['dex', 'int'] },
    barbarian: { casting_category: 'none', saving_throws: ['str', 'con'] },
    paladin: { casting_category: 'half', spellcasting_ability: 'cha', saving_throws: ['wis', 'cha'] },
    ranger: { casting_category: 'half', spellcasting_ability: 'wis', saving_throws: ['str', 'dex'] },
    bard: { casting_category: 'full', spellcasting_ability: 'cha', saving_throws: ['dex', 'cha'] },
    druid: { casting_category: 'full', spellcasting_ability: 'wis', saving_throws: ['int', 'wis'] },
    monk: { casting_category: 'none', saving_throws: ['str', 'dex'] },
    warlock: { casting_category: 'pact', spellcasting_ability: 'cha', saving_throws: ['wis', 'cha'] },
    sorcerer: { casting_category: 'full', spellcasting_ability: 'cha', saving_throws: ['con', 'cha'] },
    artificer: { casting_category: 'half', spellcasting_ability: 'int', saving_throws: ['con', 'int'] }
  },
  races: {
    human: { speed: { walk: 30 } },
    elf: { speed: { walk: 30 } },
    dwarf: { speed: { walk: 25 } },
    halfling: { speed: { walk: 25 } },
    gnome: { speed: { walk: 25 } },
    half_elf: { speed: { walk: 30 } },
    half_orc: { speed: { walk: 30 } },
    tiefling: { speed: { walk: 30 } },
    dragonborn: { speed: { walk: 30 } },
    goblin: { speed: { walk: 30 } },
    orc: { speed: { walk: 30 } },
    kobold: { speed: { walk: 30 } },
    lizardfolk: { speed: { walk: 30 } },
    hobbit: { speed: { walk: 25 } }
  },
  enums: {
    saving_throws: ['str', 'dex', 'con', 'int', 'wis', 'cha']
  },
  rules: {
    slot_tables: {
      full: {
        1: { 1: 2 },
        2: { 1: 3, 2: 2 },
        3: { 1: 4, 2: 2 },
        4: { 1: 4, 2: 3 },
        5: { 1: 4, 2: 3, 3: 2 }
      },
      half: {
        1: { 1: 0 },
        2: { 1: 2 },
        3: { 1: 3 },
        4: { 1: 3, 2: 1 },
        5: { 1: 4, 2: 2 }
      },
      pact: {
        1: { 1: 1 },
        2: { 1: 2 },
        3: { 1: 2, 2: 1 },
        4: { 1: 2, 2: 1 },
        5: { 1: 2, 2: 1, 3: 1 }
      }
    }
  }
};
console.log('D&D Mechanics loaded successfully');

// --- Функция генерации технических параметров NPC ---
function generateTechnicalParams(race, npcClass, level) {
    console.log('generateTechnicalParams called with:', { race, npcClass, level });
    console.log('window.dndMechanics:', window.dndMechanics);
    
    if (!window.dndMechanics) {
        console.error('D&D Mechanics not loaded');
        return "Технические параметры: Базовые (данные не загружены)";
    }
    
    const mechanics = window.dndMechanics;
    
    // Маппинги удалены - теперь используются прямые названия из API
    const classKey = npcClass.toLowerCase();
    const raceKey = race.toLowerCase();
    
    console.log('Processing with keys:', { classKey, raceKey });
    console.log('Available classes:', mechanics.classes ? Object.keys(mechanics.classes) : 'undefined');
    console.log('Available races:', mechanics.races ? Object.keys(mechanics.races) : 'undefined');
    
    // Генерируем случайные характеристики (10-18)
    const abilities = {
        str: Math.floor(Math.random() * 9) + 10,
        dex: Math.floor(Math.random() * 9) + 10,
        con: Math.floor(Math.random() * 9) + 10,
        int: Math.floor(Math.random() * 9) + 10,
        wis: Math.floor(Math.random() * 9) + 10,
        cha: Math.floor(Math.random() * 9) + 10
    };
    
    // Определяем бонус мастерства по уровню
    let proficiencyBonus = 2;
    if (level >= 5) proficiencyBonus = 3;
    if (level >= 9) proficiencyBonus = 4;
    if (level >= 13) proficiencyBonus = 5;
    if (level >= 17) proficiencyBonus = 6;
    
    // Получаем данные класса
    const classData = mechanics.classes[classKey] || mechanics.classes.fighter || {};
    const castingCategory = classData.casting_category || 'none';
    const spellcastingAbility = classData.spellcasting_ability || null;
    
    // Рассчитываем модификаторы
    const mods = {};
    for (let ability in abilities) {
        mods[ability] = Math.floor((abilities[ability] - 10) / 2);
    }
    
    // Рассчитываем КД
    let ac = 10 + mods.dex; // Базовая формула
    if (castingCategory !== 'none') {
        ac = 13 + mods.dex; // Mage Armor для заклинателей
    }
    
    // Рассчитываем инициативу
    const initiativeMod = mods.dex;
    
    // Рассчитываем скорость
    const raceData = mechanics.races[raceKey] || mechanics.races.human || { speed: { walk: 30 } };
    const speed = raceData.speed ? raceData.speed.walk : 30;
    
    // Рассчитываем спасброски
    const savingThrows = classData.saving_throws || ['str', 'con'];
    const savingThrowMods = {};
    
    for (let ability of mechanics.enums.saving_throws) {
        const isProficient = savingThrows.includes(ability);
        savingThrowMods[ability] = mods[ability] + (isProficient ? proficiencyBonus : 0);
    }
    
    // Рассчитываем параметры заклинаний
    let spellAttackBonus = 0;
    let spellSaveDC = 0;
    let spellSlots = {};
    
    if (castingCategory !== 'none' && spellcastingAbility) {
        spellAttackBonus = proficiencyBonus + mods[spellcastingAbility];
        spellSaveDC = 8 + proficiencyBonus + mods[spellcastingAbility];
        
        // Получаем слоты заклинаний
        if (mechanics.rules && mechanics.rules.slot_tables) {
            const slotTable = mechanics.rules.slot_tables[castingCategory];
            if (slotTable && slotTable[level]) {
                spellSlots = slotTable[level];
            }
        }
    }
    
    // Рассчитываем боевые параметры
    let extraAttacks = 0;
    if (classData.martial && classData.martial.extra_attacks) {
        extraAttacks = classData.martial.extra_attacks[level] || 0;
    }
    
    // Формируем результат
    let result = `\n\nТехнические параметры:\n`;
    result += `Класс доспеха: ${ac}\n`;
    result += `Инициатива: ${initiativeMod >= 0 ? '+' : ''}${initiativeMod}\n`;
    result += `Скорость: ${speed} футов\n`;
    result += `Уровень: ${level}\n`;
    result += `Бонус мастерства: +${proficiencyBonus}\n`;
    
    // Характеристики
    result += `\nХарактеристики:\n`;
    result += `СИЛ ${abilities.str} (${mods.str >= 0 ? '+' : ''}${mods.str})\n`;
    result += `ЛОВ ${abilities.dex} (${mods.dex >= 0 ? '+' : ''}${mods.dex})\n`;
    result += `ТЕЛ ${abilities.con} (${mods.con >= 0 ? '+' : ''}${mods.con})\n`;
    result += `ИНТ ${abilities.int} (${mods.int >= 0 ? '+' : ''}${mods.int})\n`;
    result += `МДР ${abilities.wis} (${mods.wis >= 0 ? '+' : ''}${mods.wis})\n`;
    result += `ХАР ${abilities.cha} (${mods.cha >= 0 ? '+' : ''}${mods.cha})\n`;
    
    // Спасброски
    result += `\nСпасброски:\n`;
    for (let ability of mechanics.enums.saving_throws) {
        const mod = savingThrowMods[ability];
        const proficient = savingThrows.includes(ability) ? ' (мастерство)' : '';
        result += `${ability.toUpperCase()} ${mod >= 0 ? '+' : ''}${mod}${proficient}\n`;
    }
    
    // Заклинания
    if (castingCategory !== 'none') {
        result += `\nЗаклинания:\n`;
        result += `Бонус атаки заклинаниями: +${spellAttackBonus}\n`;
        result += `Сложность спасбросков: ${spellSaveDC}\n`;
        if (Object.keys(spellSlots).length > 0) {
            result += `Слоты заклинаний: `;
            const slotList = [];
            for (let spellLevel in spellSlots) {
                slotList.push(`${spellLevel} уровень: ${spellSlots[spellLevel]}`);
            }
            result += slotList.join(', ') + '\n';
        }
    }
    
    // Боевые параметры
    if (extraAttacks > 0) {
        result += `\nБоевые параметры:\n`;
        result += `Дополнительные атаки: ${extraAttacks}\n`;
    }
    
    // Особые способности класса
    if (classData.martial && classData.martial.special_features) {
        result += `\nОсобые способности:\n`;
        for (let feature of classData.martial.special_features) {
            result += `- ${feature}\n`;
        }
    }
    
    console.log('Technical params result:', result);
    return result;
}

function fetchNpcFromAI(race, npcClass, background, level, advancedSettings = {}) {
    console.log('fetchNpcFromAI called with:', { race, npcClass, background, level, advancedSettings });
    showModal('🎲 Генерация NPC...<br><small>Это может занять до 30 секунд</small>');
    
    // Добавляем индикатор прогресса
    let progressDots = 0;
    const progressInterval = setInterval(() => {
        progressDots = (progressDots + 1) % 4;
        const dots = '.'.repeat(progressDots);
        document.getElementById('modal-content').innerHTML = `🎲 Генерация NPC${dots}<br><small>Это может занять до 30 секунд</small>`;
    }, 500);
    
        // Используем встроенные данные вместо загрузки JSON
    const json = window.uniqueTraders;
    console.log('Using embedded traders data:', json);
    console.log('JSON loaded successfully:', json);
          // 1. Имя по расе или случайное
        let name = '';
        // Имена теперь генерируются через внешние API - статические данные удалены
        name = 'Имя будет сгенерировано через AI';
        // 2. Черты, мотивация, профессия
        let trait = '';
        if (json.data && json.data.traits && Array.isArray(json.data.traits) && json.data.traits.length > 0) {
          trait = json.data.traits[Math.floor(Math.random() * json.data.traits.length)];
        }
        let motivation = '';
        if (json.data && json.data.motivation && Array.isArray(json.data.motivation) && json.data.motivation.length > 0) {
          motivation = json.data.motivation[Math.floor(Math.random() * json.data.motivation.length)];
        }
        let occ = '';
        if (json.data && json.data.occupations && Array.isArray(json.data.occupations) && json.data.occupations.length > 0) {
          occ = json.data.occupations[Math.floor(Math.random() * json.data.occupations.length)].name_ru;
        }
        // 3. Формируем контекст
        let contextBlock = '';
        if (name) contextBlock += `\nИмя: ${name} (используй это имя для NPC)`;
        if (trait) contextBlock += `\nЧерта: ${trait}`;
        if (motivation) contextBlock += `\nМотивация: ${motivation}`;
        if (occ) contextBlock += `\nПрофессия: ${occ}`;
        contextBlock += '\nИспользуй эти данные для вдохновения, но придумай цельного NPC.';
        
        // Генерируем технические параметры
        console.log('About to generate technical params for:', { race, npcClass, level });
        const technicalParams = generateTechnicalParams(race, npcClass, level);
        console.log('Technical params generated:', technicalParams);
        
        const systemInstruction = 'Создай уникального NPC для D&D. СТРОГО следуй этому формату:\n\nИмя и Профессия\n[только имя и профессия, например: "Торин Каменщик"]\n\nОписание\n[3-4 предложения о прошлом, мотивации, целях персонажа БЕЗ упоминания имени]\n\nВнешность\n[2-3 предложения о внешнем виде, одежде, особенностях]\n\nЧерты характера\n[1-2 предложения о личности, поведении, привычках]\n\nТехнические параметры\n[ИСПОЛЬЗУЙ ТОЛЬКО ПРЕДОСТАВЛЕННЫЕ ТЕХНИЧЕСКИЕ ПАРАМЕТРЫ, НЕ ИЗМЕНЯЙ ИХ]\n\nВАЖНО: Имя указывай ТОЛЬКО в блоке "Имя и Профессия". НЕ используй имя в других блоках. ОБЯЗАТЕЛЬНО учитывай указанный пол и мировоззрение в описании и чертах характера.';
        
        // Добавляем расширенные настройки в промпт
        let advancedPrompt = `Создай NPC для DnD. Раса: ${race}. Класс: ${npcClass}. Уровень: ${level}. Придумай подходящую профессию для этого персонажа.`;
        
        if (advancedSettings.gender) {
            advancedPrompt += ` Пол: ${advancedSettings.gender}.`;
        }
        if (advancedSettings.alignment) {
            advancedPrompt += ` Мировоззрение: ${advancedSettings.alignment}.`;
        }
        
        console.log('Advanced settings:', advancedSettings);
        console.log('Advanced prompt:', advancedPrompt);
        console.log('Context block:', contextBlock);
        console.log('Technical params length:', technicalParams.length);
        
        // Используем новый API для генерации NPC
        const formData = new FormData();
        formData.append('race', race);
        formData.append('class', npcClass);
        formData.append('level', level);
        formData.append('alignment', advancedSettings.alignment || 'neutral');
        formData.append('background', background || 'soldier');
        
        // Отладочная информация
        console.log('FormData debug:', {
            race: race,
            class: npcClass,
            level: level,
            alignment: advancedSettings.alignment || 'neutral',
            background: background || 'soldier',
            backgroundParam: background
        });
        
        fetch('api/generate-characters.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            clearInterval(progressInterval); // Останавливаем индикатор прогресса
            
            console.log('API Response:', data); // Отладочная информация
            console.log('NPC data:', data.npc); // Отладочная информация о NPC
            console.log('Background value:', data.npc?.background); // Отладочная информация о background
            
            if (data && data.success && data.npc) {
                const npc = data.npc;
                let html = `
                    <div class="npc-header">
                        <h3>${npc.name}</h3>
                        <div class="npc-subtitle">${npc.race} - ${npc.class} (уровень ${npc.level})</div>
                    </div>
                    
                    <div class="npc-section">
                        <h4>Основная информация</h4>
                        <div class="info-grid">
                            <div class="info-item">
                                <strong>Мировоззрение:</strong> ${npc.alignment}
                            </div>
                            <div class="info-item">
                                <strong>Профессия:</strong> ${npc.background}
                            </div>
                        </div>
                    </div>
                    
                    ${npc.description ? `
                        <div class="npc-section">
                            <h4>Описание</h4>
                            <p>${npc.description}</p>
                        </div>
                    ` : ''}
                    
                    ${npc.technical_params && npc.technical_params.length > 0 ? `
                        <div class="npc-section">
                            <h4>Технические параметры</h4>
                            <div class="technical-params">
                                ${npc.technical_params.map(param => `<div class="param-item">${param}</div>`).join('')}
                            </div>
                        </div>
                    ` : ''}
                `;
                
                document.getElementById('modal-content').innerHTML = html;
                document.getElementById('modal-save').style.display = '';
                document.getElementById('modal-save').onclick = function() { saveNoteAndUpdate(document.getElementById('modal-content').innerHTML); closeModal(); };
                
                // Удаляем старую кнопку повторной генерации, если она есть
                let oldRegenerateBtn = document.querySelector('.modal-regenerate');
                if (oldRegenerateBtn) {
                    oldRegenerateBtn.remove();
                }
                
                // Добавляем кнопку повторной генерации
                let regenerateBtn = document.createElement('button');
                regenerateBtn.className = 'modal-regenerate';
                regenerateBtn.textContent = '🔄 Повторить генерацию';
                regenerateBtn.onclick = regenerateNpc;
                document.getElementById('modal').appendChild(regenerateBtn);
            } else {
                let errorMsg = 'Неизвестная ошибка';
                if (data && data.error) {
                    errorMsg = data.error;
                }
                document.getElementById('modal-content').innerHTML = '<div class="result-segment error">❌ Ошибка генерации: ' + errorMsg + '<br><small>Попробуйте ещё раз или проверьте соединение</small></div>';
                document.getElementById('modal-save').style.display = 'none';
            }
        })
        .catch((e) => {
            clearInterval(progressInterval); // Останавливаем индикатор прогресса
            console.error('AI Response Error:', e);
            document.getElementById('modal-content').innerHTML = '<div class="result-segment error">❌ Ошибка AI<br><small>Ошибка: ' + e.message + '</small><br><small>Попробуйте ещё раз</small></div>';
            document.getElementById('modal-save').style.display = 'none';
        });
}



function generateNpcWithLevel() {
    npcLevel = document.getElementById('npc-level').value;
    
    console.log('generateNpcWithLevel called with level:', npcLevel);
    console.log('Current npcRace:', npcRace);
    console.log('Current npcClass:', npcClass);
    
    // Собираем расширенные настройки
    let advancedSettings = {};
    
    // Получаем выбранный пол
    const genderRadio = document.querySelector('input[name="gender"]:checked');
    console.log('Gender radio found:', genderRadio);
    if (genderRadio) {
        console.log('Gender radio value:', genderRadio.value);
        if (genderRadio.value !== 'рандом') {
            advancedSettings.gender = genderRadio.value;
        }
    }
    
    // Получаем выбранное мировоззрение
    const alignmentRadio = document.querySelector('input[name="alignment"]:checked');
    console.log('Alignment radio found:', alignmentRadio);
    if (alignmentRadio) {
        console.log('Alignment radio value:', alignmentRadio.value);
        if (alignmentRadio.value !== 'рандом') {
            advancedSettings.alignment = alignmentRadio.value;
        }
    }
    
    // Получаем выбранную профессию
    const backgroundSelect = document.getElementById('npc-background');
    console.log('Background select found:', backgroundSelect);
    let background = 'soldier'; // значение по умолчанию
    if (backgroundSelect) {
        background = backgroundSelect.value;
        console.log('Background value:', background);
    } else {
        console.log('Background select NOT found!');
    }
    
    console.log('Final background value:', background);
    
    console.log('Collected advanced settings:', advancedSettings);
    
    // Сохраняем параметры для повторной генерации
    lastGeneratedParams = {
        race: npcRace,
        class: npcClass,
        level: npcLevel,
        background: background,
        advancedSettings: advancedSettings
    };
    
    // Передаем выбранную профессию
    fetchNpcFromAI(npcRace, npcClass, background, npcLevel, advancedSettings);
}

function regenerateNpc() {
    if (lastGeneratedParams.race && lastGeneratedParams.class && lastGeneratedParams.level) {
        const advancedSettings = lastGeneratedParams.advancedSettings || {};
        const background = lastGeneratedParams.background || 'soldier';
        fetchNpcFromAI(lastGeneratedParams.race, lastGeneratedParams.class, background, lastGeneratedParams.level, advancedSettings);
    } else {
        alert('Нет сохраненных параметров для повторной генерации');
    }
}

// --- Зелья ---
function openPotionModalSimple() {
    showModal(`
        <div class="potion-generator">
            <div class="generator-header">
                <h2>Генератор зелий</h2>
                <p class="generator-subtitle">Создайте магические зелья различных типов и редкости</p>
            </div>
            
            <form id="potionForm" class="potion-form">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="potion-count">Количество зелий</label>
                        <input type="number" id="potion-count" name="count" min="1" max="10" value="1" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="potion-rarity">Редкость</label>
                        <select id="potion-rarity" name="rarity">
                            <option value="">Любая редкость</option>
                            <option value="uncommon">Необычное</option>
                            <option value="rare">Редкое</option>
                            <option value="very_rare">Очень редкое</option>
                            <option value="legendary">Легендарное</option>
                        </select>
                    </div>
                </div>
                
                <button type="submit" class="generate-btn">
                    <span class="btn-icon"><span class="svg-icon icon-potion" data-icon="potion"></span></span>
                    <span class="btn-text">Создать зелья</span>
                </button>
            </form>
            
            <div id="potionResult" class="result-container"></div>
        </div>
    `);
    
    document.getElementById('modal-save').style.display = 'none';
    
    // Добавляем обработчик формы
    document.getElementById('potionForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        const resultDiv = document.getElementById('potionResult');
        
        submitBtn.innerHTML = '<span class="btn-icon"><span class="svg-icon icon-loading" data-icon="loading"></span></span><span class="btn-text">Создание...</span>';
        submitBtn.disabled = true;
        resultDiv.innerHTML = '<div class="loading">Создание зелий...</div>';
        
        // Подготавливаем данные для API
        const requestData = {
            rarity: formData.get('rarity') || null,
            count: parseInt(formData.get('count'))
        };
        
        fetch('api/generate-potions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(requestData)
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Potion API Response:', data);
            if (data.success && data.potions) {
                let resultHtml = formatPotionsFromApi(data.potions);
                resultDiv.innerHTML = resultHtml;
                
                // Автоматическая прокрутка к результату
                setTimeout(() => {
                    resultDiv.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }, 100);
            } else {
                const errorMessage = data.message || 'Неизвестная ошибка при создании зелий';
                resultDiv.innerHTML = '<div class="error">' + errorMessage + '</div>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            let errorMessage = 'Ошибка сети. Попробуйте ещё раз.';
            
            if (error.message.includes('HTTP')) {
                errorMessage = `Ошибка сервера: ${error.message}`;
            } else if (error.name === 'TypeError' && error.message.includes('fetch')) {
                errorMessage = 'API недоступен. Проверьте подключение к интернету.';
            } else if (error.message.includes('Failed to fetch')) {
                errorMessage = 'Сервер недоступен. Проверьте, что сервер запущен.';
            }
            
            resultDiv.innerHTML = '<div class="error">' + errorMessage + '</div>';
        })
        .finally(() => {
            submitBtn.innerHTML = '<span class="btn-icon"><span class="svg-icon icon-potion" data-icon="potion"></span></span><span class="btn-text">Создать зелья</span>';
            submitBtn.disabled = false;
        });
    });
}

// --- Функция открытия генератора заклинаний ---
function openSpellModal() {
    showModal(`
        <div class="spell-generator">
            <div class="generator-header">
                <h2>Генератор заклинаний</h2>
                <p class="generator-subtitle">Создайте заклинания D&D 5e по уровню и классу</p>
            </div>
            
            <form id="spellForm" class="spell-form">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="spell-level">Уровень заклинания</label>
                        <select id="spell-level" name="level" required>
                            <option value="0">Заговоры (0 уровень)</option>
                            <option value="1">1 уровень</option>
                            <option value="2">2 уровень</option>
                            <option value="3">3 уровень</option>
                            <option value="4">4 уровень</option>
                            <option value="5">5 уровень</option>
                            <option value="6">6 уровень</option>
                            <option value="7">7 уровень</option>
                            <option value="8">8 уровень</option>
                            <option value="9">9 уровень</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="spell-class">Класс (опционально)</label>
                        <select id="spell-class" name="class">
                            <option value="">Любой класс</option>
                            <option value="bard">Бард</option>
                            <option value="cleric">Жрец</option>
                            <option value="druid">Друид</option>
                            <option value="paladin">Паладин</option>
                            <option value="ranger">Следопыт</option>
                            <option value="sorcerer">Чародей</option>
                            <option value="warlock">Колдун</option>
                            <option value="wizard">Волшебник</option>
                            <option value="artificer">Изобретатель</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="spell-count">Количество заклинаний</label>
                        <input type="number" id="spell-count" name="count" min="1" max="5" value="1" required>
                    </div>
                </div>
                
                <button type="submit" class="generate-btn">
                    <span class="btn-icon"><span class="svg-icon icon-spell" data-icon="spell"></span></span>
                    <span class="btn-text">Создать заклинания</span>
                </button>
            </form>
            
            <div id="spellResult" class="result-container"></div>
        </div>
    `);
    
    document.getElementById('modal-save').style.display = 'none';
    
    // Добавляем обработчик формы
    document.getElementById('spellForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        const resultDiv = document.getElementById('spellResult');
        
        submitBtn.innerHTML = '<span class="btn-icon"><span class="svg-icon icon-loading" data-icon="loading"></span></span><span class="btn-text">Создание...</span>';
        submitBtn.disabled = true;
        resultDiv.innerHTML = '<div class="loading">Создание заклинаний...</div>';
        
        // Подготавливаем данные для API
        const requestData = {
            level: parseInt(formData.get('level')),
            class: formData.get('class') || null,
            count: parseInt(formData.get('count'))
        };
        
        fetch('api/generate-spells.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(requestData)
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Spell API Response:', data);
            if (data.success && data.spells) {
                let resultHtml = formatSpellsFromApi(data.spells);
                
                // Автоматически сохраняем все заклинания в заметки
                data.spells.forEach(spell => {
                    saveSpellAsNote(spell);
                });
                
                resultDiv.innerHTML = resultHtml;
                
                // Автоматическая прокрутка к результату
                setTimeout(() => {
                    resultDiv.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }, 100);
            } else {
                const errorMessage = data.message || 'Неизвестная ошибка при создании заклинаний';
                resultDiv.innerHTML = '<div class="error">' + errorMessage + '</div>';
            }
        })
        .catch(error => {
            console.error('Spell generation error:', error);
            const errorMessage = error.message || 'Ошибка сети при создании заклинаний';
            resultDiv.innerHTML = '<div class="error">' + errorMessage + '</div>';
        })
        .finally(() => {
            submitBtn.innerHTML = '<span class="btn-icon"><span class="svg-icon icon-spell" data-icon="spell"></span></span><span class="btn-text">Создать заклинания</span>';
            submitBtn.disabled = false;
        });
    });
}

// Функция форматирования заклинаний из API
function formatSpellsFromApi(spells) {
    let html = '<div class="spells-grid">';
    
    spells.forEach((spell, index) => {
        const levelText = spell.level === 0 ? 'Заговор' : `${spell.level} уровень`;
        const schoolText = spell.school_ru || spell.school || 'Неизвестная школа';
        const classesText = spell.classes ? spell.classes.join(', ') : 'Неизвестные классы';
        
        html += `
            <div class="spell-card">
                <div class="spell-header">
                    <h3 class="spell-name">${spell.name}</h3>
                    <div class="spell-level">${levelText}</div>
                </div>
                <div class="spell-details">
                    <div class="spell-info">
                        <span class="spell-school">${schoolText}</span>
                        <span class="spell-classes">${classesText}</span>
                    </div>
                    <div class="spell-description">
                        <p><strong>Описание:</strong> ${spell.description || 'Описание недоступно'}</p>
                        ${spell.casting_time ? `<p><strong>Время накладывания:</strong> ${spell.casting_time}</p>` : ''}
                        ${spell.range ? `<p><strong>Дистанция:</strong> ${spell.range}</p>` : ''}
                        ${spell.duration ? `<p><strong>Длительность:</strong> ${spell.duration.text || spell.duration}</p>` : ''}
                        ${spell.components ? `<p><strong>Компоненты:</strong> ${formatSpellComponents(spell.components)}</p>` : ''}
                        ${spell.higher_level ? `<p><strong>На высоких уровнях:</strong> ${spell.higher_level}</p>` : ''}
                    </div>
                </div>
            </div>
        `;
    });
    
    html += '</div>';
    return html;
}

// Функция форматирования компонентов заклинания
function formatSpellComponents(components) {
    let parts = [];
    if (components.verbal) parts.push('В');
    if (components.somatic) parts.push('С');
    if (components.material) parts.push('М');
    return parts.join(', ') + (components.material_desc ? ` (${components.material_desc})` : '');
}

// Функция сохранения заклинания в заметки
function saveSpellAsNote(spell) {
    const levelText = spell.level === 0 ? 'Заговор' : `${spell.level} уровень`;
    const schoolText = spell.school_ru || spell.school || 'Неизвестная школа';
    const classesText = spell.classes ? spell.classes.join(', ') : 'Неизвестные классы';
    
    const noteContent = `
        <div class="spell-note">
            <div class="spell-note-header">
                <h3>${spell.name}</h3>
                <span class="spell-level-badge">${levelText}</span>
            </div>
            <div class="spell-note-details">
                <p><strong>Школа:</strong> ${schoolText}</p>
                <p><strong>Классы:</strong> ${classesText}</p>
                <p><strong>Описание:</strong> ${spell.description || 'Описание недоступно'}</p>
                ${spell.casting_time ? `<p><strong>Время накладывания:</strong> ${spell.casting_time}</p>` : ''}
                ${spell.range ? `<p><strong>Дистанция:</strong> ${spell.range}</p>` : ''}
                ${spell.duration ? `<p><strong>Длительность:</strong> ${spell.duration.text || spell.duration}</p>` : ''}
                ${spell.components ? `<p><strong>Компоненты:</strong> ${formatSpellComponents(spell.components)}</p>` : ''}
                ${spell.higher_level ? `<p><strong>На высоких уровнях:</strong> ${spell.higher_level}</p>` : ''}
            </div>
        </div>
    `;
    
    // Добавляем в заметки
    if (typeof addNote === 'function') {
        addNote(noteContent);
    } else {
        // Fallback - добавляем через сессию
        if (typeof $_SESSION !== 'undefined' && $_SESSION.notes) {
            $_SESSION.notes.push(noteContent);
        }
    }
}

// Функция форматирования зелий из API
function formatPotionsFromApi(potions) {
    let html = '<div class="potions-grid">';
    
    potions.forEach((potion, index) => {
        // Получаем данные зелья
        const displayName = potion.name || 'Неизвестное зелье';
        const displayRarity = potion.rarity_localized || potion.rarity || 'Неизвестная редкость';
        const displayType = potion.type_localized || potion.type || 'Неизвестный тип';
        const displayEffect = potion.effect || 'Эффект не описан';
        const displayDuration = potion.duration || 'Мгновенный';
        
        // Определяем цвет по редкости
        let rarityColor = '#666666';
        switch (potion.rarity) {
            case 'common': rarityColor = '#4CAF50'; break;
            case 'uncommon': rarityColor = '#2196F3'; break;
            case 'rare': rarityColor = '#9C27B0'; break;
            case 'very_rare': rarityColor = '#FF9800'; break;
            case 'legendary': rarityColor = '#F44336'; break;
        }
        
        // Определяем иконку по типу
        let typeIcon = '🧪';
        switch (potion.type) {
            case 'potion': typeIcon = '🧪'; break;
            case 'oil': typeIcon = '🧪'; break; // Масла теперь отображаются как зелья
            case 'ointment': typeIcon = '🧴'; break;
        }
        
        // Форматируем стоимость
        let costText = '';
        if (potion.cost) {
            const costValue = potion.cost.value;
            const costApprox = potion.cost.approx ? '~' : '';
            costText = `${costApprox}${costValue} зм`;
        }
        
        html += `
            <div class="potion-card" style="border-left: 4px solid ${rarityColor}">
                <div class="potion-header">
                    <span class="potion-icon">${typeIcon}</span>
                    <h3 class="potion-name">${displayName}</h3>
                    <span class="potion-rarity" style="color: ${rarityColor}">${displayRarity}</span>
                </div>
                <div class="potion-body">
                    <p class="potion-effect"><strong>Эффект:</strong> ${displayEffect}</p>
                    ${displayDuration !== 'Мгновенный' ? `<p class="potion-duration"><strong>Длительность:</strong> ${displayDuration}</p>` : ''}
                    <div class="potion-details">
                        <span class="potion-type">${typeIcon} ${displayType}</span>
                        ${costText ? `<span class="potion-cost">💰 ${costText}</span>` : ''}
                    </div>
                    <div class="potion-actions">
                        <button class="btn btn-sm btn-primary potion-save-btn" onclick="savePotionAsNote('${potion.id}', '${potion.name.replace(/'/g, "\\'")}', '${potion.rarity}', '${potion.type}', '${potion.effect.replace(/'/g, "\\'")}', '${potion.duration || 'Мгновенный'}', '${JSON.stringify(potion.cost || {}).replace(/"/g, '&quot;')}')">
                            💾 Сохранить в заметки
                        </button>
                    </div>
                </div>
            </div>
        `;
    });
    
    html += '</div>';
    return html;
}

// Функция сохранения зелья в заметки
function savePotionAsNote(id, name, rarity, type, effect, duration, costJson) {
    console.log('savePotionAsNote called with:', {id, name, rarity, type, effect, duration, costJson});
    
    // Находим кнопку сохранения и добавляем анимацию загрузки
    const saveBtn = event.target;
    const originalText = saveBtn.innerHTML;
    const originalDisabled = saveBtn.disabled;
    
    // Показываем состояние загрузки
    saveBtn.innerHTML = '⏳ Сохранение...';
    saveBtn.disabled = true;
    saveBtn.style.opacity = '0.7';
    
    const displayName = name || 'Неизвестное зелье';
    const displayRarity = rarity || 'Неизвестная редкость';
    const displayType = type || 'Неизвестный тип';
    const displayEffect = effect || 'Эффект не описан';
    const displayDuration = duration || 'Мгновенный';
    
    // Форматируем стоимость
    let costText = '';
    if (costJson && costJson !== '{}') {
        try {
            const cost = JSON.parse(costJson.replace(/&quot;/g, '"'));
            if (cost.value) {
                const costValue = cost.value;
                const costApprox = cost.approx ? '~' : '';
                costText = `${costApprox}${costValue} зм`;
            }
        } catch (e) {
            console.error('Error parsing cost:', e);
        }
    }
    
    // Определяем иконку по типу
    let typeIcon = '🧪';
    switch (type) {
        case 'potion': typeIcon = '🧪'; break;
        case 'oil': typeIcon = '🧪'; break; // Масла теперь отображаются как зелья
        case 'ointment': typeIcon = '🧴'; break;
    }
    
    const potionNote = `
        <div class="potion-note-header" style="background: var(--bg-tertiary); padding: var(--space-3); border-radius: var(--radius-md); margin-bottom: var(--space-3); border-left: 4px solid var(--accent-primary);">
            <h3 style="margin: 0; color: var(--text-primary);">${typeIcon} ${displayName}</h3>
            <div style="display: flex; gap: var(--space-2); margin-top: var(--space-2); flex-wrap: wrap;">
                <span style="background: var(--accent-primary); color: white; padding: var(--space-1) var(--space-2); border-radius: var(--radius-sm); font-size: var(--text-sm);">${displayRarity}</span>
                <span style="background: var(--bg-quaternary); color: var(--text-primary); padding: var(--space-1) var(--space-2); border-radius: var(--radius-sm); font-size: var(--text-sm);">${displayType}</span>
                ${costText ? `<span style="background: var(--bg-quaternary); color: var(--text-primary); padding: var(--space-1) var(--space-2); border-radius: var(--radius-sm); font-size: var(--text-sm);">💰 ${costText}</span>` : ''}
            </div>
        </div>
        <div style="margin-bottom: var(--space-3);">
            <strong>Эффект:</strong> ${displayEffect}
        </div>
        ${displayDuration !== 'Мгновенный' ? `<div style="margin-bottom: var(--space-3);"><strong>Длительность:</strong> ${displayDuration}</div>` : ''}
    `;
    
    // Сохраняем в заметки через AJAX
    console.log('Sending potion note to server:', potionNote);
    console.log('Current URL:', window.location.href);
    console.log('Current pathname:', window.location.pathname);
    const saveUrl = 'api/generate-potions.php';
    console.log('Fetching from:', saveUrl);
    
    fetch(saveUrl, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'fast_action=save_note&content=' + encodeURIComponent(potionNote)
    })
    .then(response => {
        console.log('Server response status:', response.status);
        return response.text();
    })
    .then(result => {
        console.log('Server response:', result);
        if (result === 'OK') {
            // Показываем уведомление об успешном сохранении
            showNotification('Зелье сохранено в заметки!', 'success');
            // Обновляем отображение заметок с анимацией
            updateNotesWithAnimation();
            
            // Показываем успешное состояние кнопки
            saveBtn.innerHTML = '✅ Сохранено!';
            saveBtn.style.opacity = '1';
            saveBtn.style.background = 'var(--success-color, #28a745)';
            
            // Через 2 секунды возвращаем кнопку в исходное состояние
            setTimeout(() => {
                saveBtn.innerHTML = originalText;
                saveBtn.disabled = originalDisabled;
                saveBtn.style.opacity = '';
                saveBtn.style.background = '';
            }, 2000);
        } else {
            console.error('Server returned error:', result);
            showNotification('Ошибка при сохранении зелья: ' + result, 'error');
            
            // Восстанавливаем кнопку при ошибке
            saveBtn.innerHTML = originalText;
            saveBtn.disabled = originalDisabled;
            saveBtn.style.opacity = '';
        }
    })
    .catch(error => {
        console.error('Error saving potion:', error);
        showNotification('Ошибка при сохранении зелья: ' + error.message, 'error');
        
        // Восстанавливаем кнопку при ошибке
        saveBtn.innerHTML = originalText;
        saveBtn.disabled = originalDisabled;
        saveBtn.style.opacity = '';
    });
}

// Функция показа уведомлений
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: var(--space-4);
        border-radius: var(--radius-md);
        color: white;
        font-weight: 600;
        z-index: 9999;
        max-width: 300px;
        box-shadow: var(--shadow-lg);
        transform: translateX(100%);
        transition: transform 0.3s ease;
    `;
    
    // Устанавливаем цвет в зависимости от типа
    if (type === 'success') {
        notification.style.background = 'var(--accent-success)';
    } else if (type === 'error') {
        notification.style.background = 'var(--accent-danger)';
    } else {
        notification.style.background = 'var(--accent-info)';
    }
    
    notification.textContent = message;
    document.body.appendChild(notification);
    
    // Показываем уведомление
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 100);
    
    // Скрываем через 3 секунды
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 3000);
}

// --- Инициатива ---
let initiativeList = [];
let currentInitiativeIndex = 0;
let currentRound = 1;

function openInitiativeModal() {
    showModal('<div class="initiative-container">' +
            '<div class="initiative-header">' +
                '<h3><span class="svg-icon icon-initiative" data-icon="initiative"></span> Инициатива</h3>' +
                '<div class="initiative-stats">' +
                    '<span class="stat-item">Участников: <strong id="initiative-count">0</strong></span>' +
                    '<span class="stat-item">Раунд: <strong id="initiative-round">1</strong></span>' +
                '</div>' +
            '</div>' +
            '<div class="initiative-current-turn" id="initiative-current-turn"></div>' +
            '<div class="initiative-list" id="initiative-list"></div>' +
            '<div class="initiative-controls">' +
                '<div class="control-group">' +
                    '<button class="initiative-btn player-btn" onclick="addInitiativeEntry(\'player\')">&#128100; Игрок</button>' +
                    '<button class="initiative-btn enemy-btn" onclick="addInitiativeEntry(\'enemy\')">&#128127; Противник</button>' +
                    '<button class="initiative-btn other-btn" onclick="addInitiativeEntry(\'other\')">&#9889; Ещё</button>' +
                '</div>' +
                '<div class="control-group">' +
                    '<button class="initiative-btn round-btn" onclick="nextRound()">🔄 Новый раунд</button>' +
                    '<button class="initiative-btn clear-btn" onclick="clearInitiative()">🗑️ Очистить</button>' +
                '</div>' +
            '</div>' +
        '</div>');
        document.getElementById('modal-save').style.display = '';
        document.getElementById('modal-save').onclick = function() { saveInitiativeNote(); closeModal(); };
        updateInitiativeDisplay();
}

function addInitiativeEntry(type) {
    let title = type === 'player' ? 'Добавить игрока' : 
                type === 'enemy' ? 'Добавить противника' : 'Добавить участника';
    let diceButton = type === 'enemy' || type === 'other' ? 
        '<button class="dice-btn" onclick="rollInitiativeDice()">🎲 d20</button>' : '';
    
    // Добавляем кнопку для добавления из заметок
    let notesButton = '<button class="notes-btn" onclick="addFromNotes(\'' + type + '\')">📝 Из заметок</button>';
    
    showModal('<div class="initiative-entry">' +
        '<div class="entry-title">' + title + '</div>' +
        '<input type="text" id="initiative-name" placeholder="Название (до 30 символов)" maxlength="30" class="initiative-input">' +
        '<input type="number" id="initiative-value" placeholder="Значение инициативы" class="initiative-input">' +
        diceButton +
        notesButton +
        '<div class="entry-buttons">' +
            '<button class="save-btn" onclick="saveInitiativeEntry(\'' + type + '\')">Сохранить</button>' +
            '<button class="cancel-btn" onclick="openInitiativeModal()">Отмена</button>' +
        '</div>' +
    '</div>');
    document.getElementById('modal-save').style.display = 'none';
    // Автофокус на поле имени
    setTimeout(() => document.getElementById('initiative-name').focus(), 100);
}

function rollInitiativeDice() {
    let result = Math.floor(Math.random() * 20) + 1;
    document.getElementById('initiative-value').value = result;
}

function saveInitiativeEntry(type) {
    let name = document.getElementById('initiative-name').value.trim();
    let value = parseInt(document.getElementById('initiative-value').value);
    
    if (!name || isNaN(value)) {
        alert('Заполните все поля!');
        return;
    }
    
    // Проверяем ограничения на название
    if (!/^[а-яё0-9\s]+$/i.test(name)) {
        alert('Используйте только кириллицу, цифры и пробелы!');
        return;
    }
    
    let entry = {
        id: Date.now(),
        name: name,
        value: value,
        type: type
    };
    
    initiativeList.push(entry);
    sortInitiativeList();
    openInitiativeModal();
}

function sortInitiativeList() {
    initiativeList.sort((a, b) => {
        if (b.value !== a.value) {
            return b.value - a.value; // По убыванию
        }
        return a.id - b.id; // При равных значениях - по времени добавления
    });
}

function updateInitiativeDisplay() {
    // Обновляем счетчик участников и раунд
    document.getElementById('initiative-count').textContent = initiativeList.length;
    document.getElementById('initiative-round').textContent = currentRound;
    
    // Показываем текущего участника
    if (initiativeList.length > 0) {
        let current = initiativeList[currentInitiativeIndex];
        let typeIcon = current.type === 'player' ? '&#128100;' :
current.type === 'enemy' ? '&#128127;' : '&#9889;';
        
        document.getElementById('initiative-current-turn').innerHTML = 
            '<div class="current-turn-display">' +
                '<div class="current-turn-icon">' + typeIcon + '</div>' +
                '<div class="current-turn-info">' +
                    '<div class="current-turn-name">' + current.name + '</div>' +
                    '<div class="current-turn-value">Инициатива: ' + current.value + '</div>' +
                '</div>' +
                '<div class="current-turn-actions">' +
                    '<button class="turn-btn prev-btn" onclick="prevInitiative()">◀</button>' +
                    '<button class="turn-btn next-btn" onclick="nextInitiative()">▶</button>' +
                '</div>' +
            '</div>';
    } else {
        document.getElementById('initiative-current-turn').innerHTML = 
            '<div class="no-initiative">Добавьте участников для начала боя</div>';
    }
    
    // Обновляем список участников
    let listHtml = '';
    initiativeList.forEach((entry, index) => {
        let isActive = index === currentInitiativeIndex;
        let typeClass = entry.type === 'player' ? 'player-entry' : 
                       entry.type === 'enemy' ? 'enemy-entry' : 'other-entry';
        let activeClass = isActive ? ' active' : '';
        let typeIcon = entry.type === 'player' ? '&#128100;' :
entry.type === 'enemy' ? '&#128127;' : '&#9889;';
        
        listHtml += '<div class="initiative-item ' + typeClass + activeClass + '" onclick="setActiveInitiative(' + index + ')">' +
            '<div class="initiative-item-content">' +
                '<div class="initiative-icon">' + typeIcon + '</div>' +
                '<div class="initiative-info">' +
                    '<div class="initiative-name">' + entry.name + '</div>' +
                    '<div class="initiative-value">' + entry.value + '</div>' +
                '</div>' +
            '</div>' +
            '<div class="initiative-actions">' +
                '<button class="edit-btn" onclick="event.stopPropagation(); editInitiativeEntry(' + entry.id + ')">✏️</button>' +
                '<button class="delete-btn" onclick="event.stopPropagation(); deleteInitiativeEntry(' + entry.id + ')">🗑️</button>' +
            '</div>' +
        '</div>';
    });
    
    document.getElementById('initiative-list').innerHTML = listHtml;
}

function setActiveInitiative(index) {
    currentInitiativeIndex = index;
    updateInitiativeDisplay();
}

function prevInitiative() {
    if (initiativeList.length > 0) {
        currentInitiativeIndex = (currentInitiativeIndex - 1 + initiativeList.length) % initiativeList.length;
        updateInitiativeDisplay();
    }
}



function clearInitiative() {
    if (confirm('Очистить всех участников инициативы?')) {
        initiativeList = [];
        currentInitiativeIndex = 0;
        currentRound = 1;
        updateInitiativeDisplay();
    }
}

function nextRound() {
    currentRound++;
    currentInitiativeIndex = 0;
    updateInitiativeDisplay();
}

function nextInitiative() {
    if (initiativeList.length > 0) {
        currentInitiativeIndex = (currentInitiativeIndex + 1) % initiativeList.length;
        // Если прошли полный круг, увеличиваем раунд
        if (currentInitiativeIndex === 0) {
            currentRound++;
        }
        updateInitiativeDisplay();
    }
}

function prevInitiative() {
    if (initiativeList.length > 0) {
        currentInitiativeIndex = (currentInitiativeIndex - 1 + initiativeList.length) % initiativeList.length;
        // Если пошли назад и достигли конца, уменьшаем раунд
        if (currentInitiativeIndex === initiativeList.length - 1 && currentRound > 1) {
            currentRound--;
        }
        updateInitiativeDisplay();
    }
}

function editInitiativeEntry(id) {
    let entry = initiativeList.find(e => e.id === id);
    if (!entry) return;
    
    let title = entry.type === 'player' ? 'Редактировать игрока' : 
                entry.type === 'enemy' ? 'Редактировать противника' : 'Редактировать участника';
    
    showModal('<div class="initiative-entry">' +
        '<div class="entry-title">' + title + '</div>' +
        '<input type="text" id="initiative-name" value="' + entry.name + '" maxlength="30" class="initiative-input">' +
        '<input type="number" id="initiative-value" value="' + entry.value + '" class="initiative-input">' +
        '<div class="entry-buttons">' +
            '<button class="save-btn" onclick="updateInitiativeEntry(' + entry.id + ')">Сохранить</button>' +
            '<button class="cancel-btn" onclick="openInitiativeModal()">Отмена</button>' +
        '</div>' +
    '</div>');
    document.getElementById('modal-save').style.display = 'none';
    // Автофокус на поле имени
    setTimeout(() => document.getElementById('initiative-name').focus(), 100);
}

function updateInitiativeEntry(id) {
    let name = document.getElementById('initiative-name').value.trim();
    let value = parseInt(document.getElementById('initiative-value').value);
    
    if (!name || isNaN(value)) {
        alert('Заполните все поля!');
        return;
    }
    
    if (!/^[а-яё0-9\s]+$/i.test(name)) {
        alert('Используйте только кириллицу, цифры и пробелы!');
        return;
    }
    
    let entry = initiativeList.find(e => e.id === id);
    if (entry) {
        entry.name = name;
        entry.value = value;
        sortInitiativeList();
        openInitiativeModal();
    }
}

function deleteInitiativeEntry(id) {
    if (confirm('Удалить участника?')) {
        initiativeList = initiativeList.filter(e => e.id !== id);
        if (currentInitiativeIndex >= initiativeList.length) {
            currentInitiativeIndex = Math.max(0, initiativeList.length - 1);
        }
        updateInitiativeDisplay();
    }
}

function saveInitiativeNote() {
    if (initiativeList.length === 0) {
        alert('Нет участников для сохранения!');
        return;
    }
    
    let noteContent = '<div class="initiative-note">' +
        '<div class="initiative-note-title">Инициатива</div>';
    
    initiativeList.forEach((entry, index) => {
        let typeClass = entry.type === 'player' ? 'player-entry' : 
                       entry.type === 'enemy' ? 'enemy-entry' : 'other-entry';
        let isActive = index === currentInitiativeIndex ? ' active' : '';
        
        noteContent += '<div class="initiative-item ' + typeClass + isActive + '">' +
            '<div class="initiative-name">' + entry.name + '</div>' +
            '<div class="initiative-value">' + entry.value + '</div>' +
        '</div>';
    });
    
    noteContent += '</div>';
    
    fetch('', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'fast_action=save_note&content=' + encodeURIComponent(noteContent)
    })
    .then(r => r.text())
    .then(() => {
        alert('Инициатива сохранена в заметки!');
        closeModal();
    });
}
// --- Форматирование результата NPC по смысловым блокам ---
function formatNpcBlocks(txt, forcedName = '') {
    // Проверяем, является ли это данными от рабочей API системы
    if (typeof txt === 'object' && txt.name && txt.race && txt.class) {
        return formatNpcFromWorkingApi(txt);
    }
    
    // Оригинальная логика для AI-генерации
    // Очищаем текст от лишних символов
    txt = txt.replace(/[\#\*`>\[\]]+/g, '');
    
    // Ищем блоки по заголовкам
    const blockTitles = [
        'Имя и Профессия', 'Описание', 'Внешность', 'Черты характера', 'Технические параметры'
    ];
    
    let blocks = [];
    let regex = /(Имя и Профессия|Описание|Внешность|Черты характера|Технические параметры)\s*[:\- ]/gi;
    let matches = [...txt.matchAll(regex)];
    
    if (matches.length > 0) {
        for (let i = 0; i < matches.length; i++) {
            let start = matches[i].index + matches[i][0].length;
            let end = (i + 1 < matches.length) ? matches[i + 1].index : txt.length;
            let title = matches[i][1];
            let content = txt.slice(start, end).replace(/^\s+|\s+$/g, '');
            if (content && content.length > 5) {
                blocks.push({ title, content });
            }
        }
    }
    let name = '', desc = '', appear = '', trait = '', techBlock = '';
    
    // Извлекаем данные из блоков
    for (let block of blocks) {
        if (block.title === 'Имя и Профессия') name = block.content;
        if (block.title === 'Описание') desc = block.content;
        if (block.title === 'Внешность') appear = block.content;
        if (block.title === 'Черты характера') trait = block.content;
        if (block.title === 'Технические параметры') techBlock = block.content;
    }
    
    // Если блоки не найдены, пытаемся извлечь данные из всего текста
    if (!name || !desc || !appear || !trait) {
        let lines = txt.split(/\n/).map(s => s.trim()).filter(Boolean);
        
            // Ищем имя в первой строке
    if (!name && lines.length > 0) {
        let firstLine = lines[0];
        if (firstLine.length < 50 && !firstLine.includes(':')) {
            name = firstLine;
        }
    }
    
    // Если имя не найдено, ищем его в описании (часто AI помещает имя туда)
    if (!name && desc) {
        let nameMatch = desc.match(/^([А-ЯЁ][а-яё]+(?:\s+[А-ЯЁ][а-яё]+)*)(?:\s*[,\-]\s*[а-яё\s]+)?/);
        if (nameMatch && nameMatch[1]) {
            name = nameMatch[1];
            // Убираем имя из описания
            desc = desc.replace(nameMatch[0], '').trim();
            desc = desc.replace(/^[,\s]+/, '').replace(/[,\s]+$/, '');
        }
    }
        
        // Ищем описание (обычно после имени)
        if (!desc && lines.length > 1) {
            for (let i = 1; i < Math.min(5, lines.length); i++) {
                let line = lines[i];
                if (line.length > 20 && line.length < 200 && 
                    !line.includes('Оружие:') && !line.includes('Урон:') && !line.includes('Хиты:')) {
                    desc = line;
                    break;
                }
            }
        }
        
        // Ищем внешность (описания внешнего вида)
        if (!appear) {
            for (let line of lines) {
                if (line.length > 15 && line.length < 150 &&
                    /высокий|низкий|стройный|полный|волосы|глаза|лицо|одежда|длинные|короткие|светлые|темные|крепкий|мужчина|плечи|руки|шрамы|фартук|хвост|серебристые|заплетённые|косы|ярко-голубые|проницательные|внешность|стройная|женщина|собранными|тёмными|пучок|форменном|платье|формария|глаза|следят|движения|точны|экономны|мускулистым|телосложением|покрытым|старыми|шрамами|доспехов|брони|зелёные|морской|волны|холодными|острыми|чертами|унаследованными|эльфийской|крови|внутренней|силой/i.test(line.toLowerCase()) &&
                    !line.includes('Оружие:') && !line.includes('Урон:') && !line.includes('Хиты:')) {
                    appear = line;
                    break;
                }
            }
        }
        
        // Ищем черты характера
        if (!trait) {
            for (let line of lines) {
                if (line.length > 10 && line.length < 100 &&
                    /харизматичный|проницательный|ответственный|надменный|артистичный|дипломатичный|преданный|терпеливый|внимательный|мечтательный|общительный|находчивый|рассеянный|хитрый|наблюдательный|амбициозный|осторожный|циничный|любознательный|обаятельный|нетерпеливый|наивный|агрессивный|мстительный|спокойный|вспыльчивый|добрый|злой|нейтральный/i.test(line.toLowerCase()) &&
                    !line.includes('Оружие:') && !line.includes('Урон:') && !line.includes('Хиты:')) {
                    trait = line;
                    break;
                }
            }
        }
    }
    
    // Если блоки не найдены, используем принудительное имя
    if (!name && forcedName) name = forcedName;
    
    // Очищаем блоки от лишних символов и форматирования
    if (name) name = name.replace(/[\[\]()]/g, '').trim();
    if (desc) desc = desc.replace(/[\[\]()]/g, '').trim();
    if (appear) appear = appear.replace(/[\[\]()]/g, '').trim();
    if (trait) trait = trait.replace(/[\[\]()]/g, '').trim();
    if (techBlock) techBlock = techBlock.replace(/[\[\]()]/g, '').trim();
    
    // Убираем имя из других блоков
    if (name) {
        let cleanName = name.split(/\s+/)[0].replace(/[^\wа-яё]/gi, '').trim();
        const nameRegex = new RegExp(cleanName + '\\s*', 'gi');
        
        if (trait && trait.includes(cleanName)) {
            trait = trait.replace(nameRegex, '').trim().replace(/^[,\s]+/, '').replace(/[,\s]+$/, '');
        }
        if (desc && desc.includes(cleanName)) {
            desc = desc.replace(nameRegex, '').trim().replace(/^[,\s]+/, '').replace(/[,\s]+$/, '');
        }
        if (appear && appear.includes(cleanName)) {
            appear = appear.replace(nameRegex, '').trim().replace(/^[,\s]+/, '').replace(/[,\s]+$/, '');
        }
    }
    
    // Убираем формальные ссылки на имя
    if (trait && trait.includes('Имя:')) {
        trait = trait.replace(/.*?Имя:\s*[^.]*\.?/i, '').trim();
    }
    if (desc && desc.includes('Имя:')) {
        desc = desc.replace(/.*?Имя:\s*[^.]*\.?/i, '').trim();
    }
    if (appear && appear.includes('Имя:')) {
        appear = appear.replace(/.*?Имя:\s*[^.]*\.?/i, '').trim();
    }
    
    // Если в описании черты характера - переносим
    if (desc && /черты характера|прямолинейный|наблюдательный|грубоватым|юмор|харизматичный|проницательный|ответственный|надменный|артистичный|дипломатичный|преданный|терпеливый|внимательный|мечтательный|общительный|находчивый|рассеянный|дикая|необузданная|натура|брала верх|духовное|воспитание|наставники|покинула|храм|найти путь|сочетая|ярость|варвара|глубокую связь|природой|дикая энергия|направлена|защиту|священных|поддержание|баланса|племенем|лесом/i.test(desc.toLowerCase())) {
        if (!trait || trait === '-') {
            trait = desc;
            desc = '';
        } else {
            // Если уже есть черты характера, объединяем
            trait = trait + '. ' + desc;
            desc = '';
        }
    }
    if (!name && forcedName) name = forcedName;
    // Извлечение технических параметров
    let summaryLines = [];
    let techParams = { weapon: '', damage: '', hp: '' };
    
    // Ищем технические параметры в блоке
    if (techBlock) {
        let lines = techBlock.split(/\n|\r/).map(s => s.trim()).filter(Boolean);
        for (let line of lines) {
            if (/оружие\s*:/i.test(line)) techParams.weapon = line;
            if (/урон\s*:/i.test(line)) techParams.damage = line;
            if (/хиты\s*:/i.test(line)) techParams.hp = line;
        }
        
        // Если технические параметры найдены, используем их полностью
        if (techBlock.length > 50) {
            techParams.fullBlock = techBlock;
        }
        
        // Проверяем, есть ли новые технические параметры (КД, характеристики и т.д.)
        if (techBlock.includes('Класс доспеха:') || techBlock.includes('Характеристики:') || techBlock.includes('Спасброски:')) {
            techParams.fullBlock = techBlock;
        }
    }
    // Проверяем наличие необходимых блоков
    if (!name) {
        return `<div class='npc-block-modern'><div class='npc-modern-header'>Ошибка генерации</div><div class='npc-modern-block'>AI не вернул имя персонажа. Попробуйте сгенерировать NPC ещё раз.</div></div>`;
    }
    
    // Если нет технических параметров, создаем подходящие для класса
    if (!techBlock || techBlock.length < 10) {
        let weapon, damage, hp;
        
        // Подбираем оружие и параметры в зависимости от класса
        switch(npcClass.toLowerCase()) {
            case 'воин':
            case 'варвар':
            case 'паладин':
                weapon = 'Меч';
                damage = '1d8 рубящий';
                hp = '15';
                break;
            case 'маг':
            case 'волшебник':
                weapon = 'Посох';
                damage = '1d6 дробящий';
                hp = '8';
                break;
            case 'лучник':
            case 'следопыт':
                weapon = 'Лук';
                damage = '1d8 колющий';
                hp = '12';
                break;
            case 'жрец':
            case 'друид':
                weapon = 'Булава';
                damage = '1d6 дробящий';
                hp = '10';
                break;
            case 'плут':
            case 'бард':
                weapon = 'Кинжал';
                damage = '1d4 колющий';
                hp = '8';
                break;
            default:
                weapon = 'Кулаки';
                damage = '1d4 дробящий';
                hp = '10';
        }
        
        techBlock = `Оружие: ${weapon}\nУрон: ${damage}\nХиты: ${hp}`;
    }
    
    // Формируем технические параметры
    if (techParams.weapon) summaryLines.push(techParams.weapon);
    if (techParams.damage) summaryLines.push(techParams.damage);
    if (techParams.hp) summaryLines.push(techParams.hp);
    
    // Проверяем наличие технических параметров
    const foundParams = [techParams.weapon, techParams.damage, techParams.hp].filter(p => p).length;
    if (foundParams < 2) {
        // Если параметров недостаточно, используем базовые
        if (!techParams.weapon) techParams.weapon = 'Оружие: Кулаки';
        if (!techParams.damage) techParams.damage = 'Урон: 1d4 дробящий';
        if (!techParams.hp) techParams.hp = 'Хиты: 10';
        summaryLines = [techParams.weapon, techParams.damage, techParams.hp];
    }
    
    function firstSentence(str) {
        if (!str || str === '-') return '';
        let m = str.match(/^[^.?!]+[.?!]?/);
        return m ? m[0].trim() : str.trim();
    }
    
    let out = '';
    out += `<div class='npc-block-modern'>`;
    
    // Очищаем имя и извлекаем только имя (без профессии)
    let cleanName = name;
    if (name.includes(',')) {
        cleanName = name.split(',')[0].trim();
    } else if (name.includes('-')) {
        cleanName = name.split('-')[0].trim();
    }
    cleanName = cleanName.split(/\s+/)[0].replace(/[^\wа-яё]/gi, '').trim();
    out += `<div class='npc-modern-header'>${cleanName || 'NPC'}</div>`;
    
    // Технические параметры (сворачиваемые)
    if (techParams.fullBlock) {
        // Используем полный блок технических параметров
        let techContent = techParams.fullBlock.replace(/\n/g, '<br>');
        out += `<div class='npc-col-block'>
            <div class='npc-collapsible-header collapsed' onclick='toggleTechnicalParams(this)'>
                <div><span style='font-size:1.2em;'>&#9876;&#65039;</span> <b>Технические параметры</b></div>
                <span class='toggle-icon'>▼</span>
            </div>
            <div class='npc-collapsible-content collapsed'>
                <div class='npc-content' style='font-family: monospace; font-size: 0.9em; white-space: pre-line; margin-top: 8px;'>${techContent}</div>
            </div>
        </div>`;
    } else if (summaryLines.length) {
        let listHtml = '<ul class="npc-modern-list">' + summaryLines.map(s => `<li>${s}</li>`).join('') + '</ul>';
        out += `<div class='npc-col-block'>
            <div class='npc-collapsible-header collapsed' onclick='toggleTechnicalParams(this)'>
                <div><span style='font-size:1.2em;'>&#9876;&#65039;</span> <b>Технические параметры</b></div>
                <span class='toggle-icon'>▼</span>
            </div>
            <div class='npc-collapsible-content collapsed'>
                <div style='margin-top: 8px;'>${listHtml}</div>
            </div>
        </div>`;
    }
    

    
    // Описание
    if (desc && desc.length > 10) {
        out += `<div class='npc-col-block'><span style='font-size:1.2em;'>📜</span> <b>Описание</b><div class='npc-content'>${firstSentence(desc)}</div></div>`;
    } else if (!desc || desc.length <= 10) {
        out += `<div class='npc-col-block'><span style='font-size:1.2em;'>📜</span> <b>Описание</b><div class='npc-content'>Описание недоступно</div></div>`;
    }
    
    // Черты характера
    if (trait && trait.length > 5) {
        out += `<div class='npc-col-block'><span style='font-size:1.2em;'>🧠</span> <b>Черты характера</b><div class='npc-content'>${firstSentence(trait)}</div></div>`;
    } else if (!trait || trait.length <= 5) {
        out += `<div class='npc-col-block'><span style='font-size:1.2em;'>🧠</span> <b>Черты характера</b><div class='npc-content'>Черты характера недоступны</div></div>`;
    }
    
    // Внешность
    if (appear && appear.length > 10) {
        out += `<div class='npc-col-block'><span style='font-size:1.2em;'>&#128100;</span> <b>Внешность</b><div class='npc-content'>${firstSentence(appear)}</div></div>`;
    } else if (!appear || appear.length <= 10) {
        out += `<div class='npc-col-block'><span style='font-size:1.2em;'>&#128100;</span> <b>Внешность</b><div class='npc-content'>Внешность недоступна</div></div>`;
    }
    out += `</div>`;
    setTimeout(() => {
      document.querySelectorAll('.npc-desc-toggle-btn').forEach(btn => {
        btn.onclick = function() {
          let block = this.nextElementSibling;
          block.style.display = block.style.display === 'block' ? 'none' : 'block';
        };
      });
    }, 100);
    return out;
}

// --- Перевод названий действий ---
    
    // Заголовок персонажа
    out += '<div class="character-header">';
    out += '<h3>' + safeText(character.name || 'Без имени') + '</h3>';
    out += '<div class="character-subtitle">' + safeText(character.race || 'Неизвестная раса') + ' - ' + safeText(character.class || 'Неизвестный класс') + ' (уровень ' + (character.level || '?') + ')</div>';
    out += '</div>';
    
    // Основная информация
    out += '<div class="character-section">';
    out += '<div class="section-title" onclick="toggleSection(this)">🏷️ Основная информация <span class="toggle-icon">▼</span></div>';
    out += '<div class="section-content">';
    out += '<div class="info-grid">';
    out += '<div class="info-item"><strong>Пол:</strong> ' + safeText(character.gender || 'Не определен') + '</div>';
    out += '<div class="info-item"><strong>Мировоззрение:</strong> ' + safeText(character.alignment || 'Не определено') + '</div>';
    out += '<div class="info-item"><strong>Профессия:</strong> ' + safeText(character.occupation || 'Не определена') + '</div>';
    out += '</div>';
    out += '</div></div>';
    
    // Боевые характеристики
    out += '<div class="character-section">';
    out += '<div class="section-title collapsed" onclick="toggleSection(this)">&#9876;&#65039; Боевые характеристики <span class="toggle-icon">▶</span></div>';
    out += '<div class="section-content collapsed">';
    out += '<div class="info-grid">';
    out += '<div class="info-item"><strong>Хиты:</strong> ' + (character.hit_points || 'Не определены') + '</div>';
    out += '<div class="info-item"><strong>Класс доспеха:</strong> ' + (character.armor_class || 'Не определен') + '</div>';
    out += '<div class="info-item"><strong>Скорость:</strong> ' + (character.speed || 'Не определена') + ' футов</div>';
    out += '<div class="info-item"><strong>Инициатива:</strong> ' + (character.initiative || 'Не определена') + '</div>';
    out += '<div class="info-item"><strong>Бонус мастерства:</strong> +' + (character.proficiency_bonus || 'Не определен') + '</div>';
    out += '<div class="info-item"><strong>Оружие:</strong> ' + (character.main_weapon || 'Не определено') + '</div>';
    out += '<div class="info-item"><strong>Попадание:</strong> ' + (character.attack_bonus || 'Не определено') + '</div>';
    out += '<div class="info-item"><strong>Урон:</strong> ' + (character.damage || 'Не определен') + '</div>';
    out += '</div>';
    out += '</div></div>';
    
    // Характеристики
    if (character.abilities && typeof character.abilities === 'object') {
        out += '<div class="character-section">';
        out += '<div class="section-title collapsed" onclick="toggleSection(this)">📊 Характеристики <span class="toggle-icon">▶</span></div>';
        out += '<div class="section-content collapsed">';
        out += '<div class="abilities-grid">';
        out += '<div class="ability-item">СИЛ: ' + (character.abilities.str || '?') + '</div>';
        out += '<div class="ability-item">ЛОВ: ' + (character.abilities.dex || '?') + '</div>';
        out += '<div class="ability-item">ТЕЛ: ' + (character.abilities.con || '?') + '</div>';
        out += '<div class="ability-item">ИНТ: ' + (character.abilities.int || '?') + '</div>';
        out += '<div class="ability-item">МДР: ' + (character.abilities.wis || '?') + '</div>';
        out += '<div class="ability-item">ХАР: ' + (character.abilities.cha || '?') + '</div>';
        out += '</div>';
        out += '</div></div>';
    }
    
    // Броски спасения
    if (character.saving_throws && Array.isArray(character.saving_throws) && character.saving_throws.length > 0) {
        out += '<div class="character-section">';
        out += '<div class="section-title collapsed" onclick="toggleSection(this)">🛡️ Броски спасения <span class="toggle-icon">▶</span></div>';
        out += '<div class="section-content saving-throws-content collapsed">';
        out += '<div class="info-grid">';
        character.saving_throws.forEach(throw_item => {
            if (throw_item && typeof throw_item === 'object') {
                out += '<div class="info-item"><strong>' + safeText(throw_item.name || 'Неизвестно') + ':</strong> ' + (throw_item.modifier >= 0 ? '+' : '') + (throw_item.modifier || '0') + '</div>';
            }
        });
        out += '</div>';
        out += '</div></div>';
    }
    
    // Владения
    if (character.proficiencies && Array.isArray(character.proficiencies) && character.proficiencies.length > 0) {
        out += '<div class="character-section">';
        out += '<div class="section-title collapsed" onclick="toggleSection(this)">&#9876;&#65039; Владения <span class="toggle-icon">▶</span></div>';
        out += '<div class="section-content collapsed">';
        out += '<div class="proficiencies-list">';
        character.proficiencies.forEach(prof => {
            if (prof && typeof prof === 'string') {
                out += '<span class="proficiency-tag">' + safeText(prof) + '</span>';
            }
        });
        out += '</div>';
        out += '</div></div>';
    }
    
    // Описание
    if (character.description && typeof character.description === 'string') {
        out += '<div class="character-section">';
        out += '<div class="section-title collapsed" onclick="toggleSection(this)">📝 Описание <span class="toggle-icon">▶</span></div>';
        out += '<div class="section-content collapsed">';
        out += '<p>' + safeText(character.description) + '</p>';
        out += '</div></div>';
    }
    
    // Предыстория
    if (character.background && typeof character.background === 'string') {
        out += '<div class="character-section">';
        out += '<div class="section-title collapsed" onclick="toggleSection(this)">📖 Предыстория <span class="toggle-icon">▶</span></div>';
        out += '<div class="section-content collapsed">';
        out += '<p>' + safeText(character.background) + '</p>';
        out += '</div></div>';
    }
    
    // Заклинания
    if (character.spells && (Array.isArray(character.spells) || typeof character.spells === 'object')) {
        out += '<div class="character-section">';
        out += '<div class="section-title collapsed" onclick="toggleSection(this)">🔮 Заклинания <span class="toggle-icon">▶</span></div>';
        out += '<div class="section-content collapsed">';
        
        // Если это новая структура заклинаний (объект)
        if (typeof character.spells === 'object' && !Array.isArray(character.spells)) {
            if (character.spells.spellcasting_ability) {
                out += '<div class="spell-info"><strong>Способность заклинаний:</strong> ' + safeText(character.spells.spellcasting_ability) + '</div>';
            }
            
            if (character.spells.spell_slots) {
                out += '<div class="spell-info"><strong>Слоты заклинаний:</strong> ' + safeText(JSON.stringify(character.spells.spell_slots)) + '</div>';
            }
            
            if (character.spells.known_spells && character.spells.known_spells.length > 0) {
                out += '<div class="spell-category"><strong>Известные заклинания:</strong></div>';
                out += '<div class="spell-list">';
                character.spells.known_spells.forEach(spell => {
                    out += '<div class="spell-item">';
                    out += '<span class="spell-name">' + safeText(spell.name) + '</span>';
                    out += '<span class="spell-level">' + safeText(spell.level) + ' уровень</span>';
                    out += '</div>';
                });
                out += '</div>';
            }
            
            if (character.spells.spells_by_level) {
                out += '<div class="spell-category"><strong>Доступные заклинания по уровням:</strong></div>';
                for (let level in character.spells.spells_by_level) {
                    if (character.spells.spells_by_level[level].length > 0) {
                        out += '<div class="spell-level-category"><strong>' + level + ' уровень:</strong></div>';
                        out += '<div class="spell-list">';
                        character.spells.spells_by_level[level].forEach(spell => {
                            out += '<div class="spell-item">';
                            out += '<span class="spell-name">' + safeText(spell) + '</span>';
                            out += '</div>';
                        });
                        out += '</div>';
                    }
                }
            }
        } else {
            // Старая структура (массив)
            out += '<div class="spell-list">';
            character.spells.forEach(spell => {
                if (typeof spell === 'object' && spell && spell.name) {
                    // Новый формат с детальной информацией
                    out += '<div class="spell-item">';
                    out += '<div class="spell-header" onclick="toggleSpellDetails(this)">';
                    out += '<span class="spell-name">' + safeText(spell.name) + '</span>';
                    out += '<span class="spell-level">' + (spell.level || '?') + ' уровень</span>';
                    out += '<span class="spell-school">' + safeText(spell.school || 'Неизвестно') + '</span>';
                    out += '<span class="spell-toggle">▼</span>';
                    out += '</div>';
                    out += '<div class="spell-details" style="display: none;">';
                    out += '<div class="spell-info">';
                    out += '<div><strong>Время накладывания:</strong> ' + safeText(spell.casting_time || 'Не указано') + '</div>';
                    out += '<div><strong>Дистанция:</strong> ' + safeText(spell.range || 'Не указана') + '</div>';
                    out += '<div><strong>Компоненты:</strong> ' + safeText(spell.components || 'Не указаны') + '</div>';
                    out += '<div><strong>Длительность:</strong> ' + safeText(spell.duration || 'Не указана') + '</div>';
                    if (spell.damage) {
                        out += '<div><strong>Урон:</strong> ' + safeText(spell.damage) + '</div>';
                    }
                    out += '</div>';
                    if (spell.description) {
                        out += '<div class="spell-description">' + safeText(spell.description) + '</div>';
                    }
                    out += '</div>';
                    out += '</div>';
                } else if (typeof spell === 'string' && spell) {
                    // Старый формат (просто строка)
                    out += '<div class="spell-item">';
                    out += '<div class="spell-name">' + safeText(spell) + '</div>';
                    out += '</div>';
                }
            });
            out += '</div>';
        }
        
        out += '</div></div>';
    }
    
    // Снаряжение
    if (character.equipment && (Array.isArray(character.equipment) || typeof character.equipment === 'object')) {
        out += '<div class="character-section">';
        out += '<div class="section-title collapsed" onclick="toggleSection(this)">🎒 Снаряжение <span class="toggle-icon">▶</span></div>';
        out += '<div class="section-content collapsed">';
        
        // Если это новая структура снаряжения (объект)
        if (typeof character.equipment === 'object' && !Array.isArray(character.equipment)) {
            if (character.equipment.weapons && character.equipment.weapons.length > 0) {
                out += '<div class="equipment-category"><strong>⚔️ Оружие:</strong><ul>';
                character.equipment.weapons.forEach(weapon => {
                    out += '<li>' + safeText(weapon) + '</li>';
                });
                out += '</ul></div>';
            }
            
            if (character.equipment.armor && character.equipment.armor.length > 0) {
                out += '<div class="equipment-category"><strong>🛡️ Броня:</strong><ul>';
                character.equipment.armor.forEach(armor => {
                    out += '<li>' + safeText(armor) + '</li>';
                });
                out += '</ul></div>';
            }
            
            if (character.equipment.shields && character.equipment.shields.length > 0) {
                out += '<div class="equipment-category"><strong>🛡️ Щиты:</strong><ul>';
                character.equipment.shields.forEach(shield => {
                    out += '<li>' + safeText(shield) + '</li>';
                });
                out += '</ul></div>';
            }
            
            if (character.equipment.tools && character.equipment.tools.length > 0) {
                out += '<div class="equipment-category"><strong>🔧 Инструменты:</strong><ul>';
                character.equipment.tools.forEach(tool => {
                    out += '<li>' + safeText(tool) + '</li>';
                });
                out += '</ul></div>';
            }
            
            if (character.equipment.items && character.equipment.items.length > 0) {
                out += '<div class="equipment-category"><strong>🎒 Базовое снаряжение:</strong><ul>';
                character.equipment.items.forEach(item => {
                    out += '<li>' + safeText(item) + '</li>';
                });
                out += '</ul></div>';
            }
            
            if (character.equipment.background_items && character.equipment.background_items.length > 0) {
                out += '<div class="equipment-category"><strong>📜 От происхождения:</strong><ul>';
                character.equipment.background_items.forEach(item => {
                    out += '<li>' + safeText(item) + '</li>';
                });
                out += '</ul></div>';
            }
            
            if (character.equipment.money) {
                out += '<div class="equipment-category"><strong>💰 Деньги:</strong> ' + safeText(character.equipment.money) + '</div>';
            }
        } else {
            // Старая структура (массив)
            out += '<ul class="equipment-list">';
            character.equipment.forEach(item => {
                if (item && typeof item === 'string') {
                    out += '<li>' + safeText(item) + '</li>';
                }
            });
            out += '</ul>';
        }
        
        out += '</div></div>';
    }
    
    out += '</div>';
    return out;
}

// Добавляем CSS стили для категорий снаряжения
const equipmentStyles = `
    <style>
        .equipment-category {
            margin: 15px 0;
            padding: 10px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            border-left: 4px solid #ff6b35;
        }
        
        .equipment-category ul {
            list-style: none;
            padding: 0;
            margin: 8px 0 0 0;
        }
        
        .equipment-category li {
            padding: 3px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .equipment-category strong {
            color: #ff6b35;
            display: block;
            margin-bottom: 8px;
        }
        
        .spell-info {
            margin: 10px 0;
            padding: 8px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 6px;
            border-left: 3px solid #4CAF50;
        }
        
        .spell-category {
            margin: 15px 0 10px 0;
            padding: 8px;
            background: rgba(76, 175, 80, 0.1);
            border-radius: 6px;
            border-left: 4px solid #4CAF50;
        }
        
        .spell-level-category {
            margin: 10px 0 5px 0;
            padding: 5px;
            background: rgba(76, 175, 80, 0.05);
            border-radius: 4px;
            border-left: 3px solid #4CAF50;
        }
        
        .spell-list {
            margin: 10px 0;
        }
        
        .spell-item {
            padding: 8px;
            margin: 5px 0;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 4px;
            border-left: 2px solid #4CAF50;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .spell-name {
            font-weight: bold;
            color: #4CAF50;
        }
        
        .spell-level {
            color: #ff6b35;
            font-size: 0.9em;
        }
        
        /* Стили для нового генератора персонажей */
        .character-generator {
            background: var(--modal-bg, rgba(255, 255, 255, 0.05));
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
            border: 1px solid var(--modal-border, rgba(255, 255, 255, 0.1));
            box-shadow: var(--modal-shadow, 0 4px 15px rgba(0, 0, 0, 0.1));
        }
        
        
        .character-form-container {
            margin-top: 20px;
        }
        
        .character-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .form-row {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .form-group {
            flex: 1;
            min-width: 200px;
        }
        
        .form-group.full-width {
            flex: 1 1 100%;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-color, #e0e0e0);
            font-size: 14px;
        }
        
        .form-group select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--input-border, rgba(255, 255, 255, 0.2));
            border-radius: 8px;
            background: var(--input-bg, rgba(255, 255, 255, 0.1));
            color: var(--text-color, #ffffff);
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .form-group select:focus {
            outline: none;
            border-color: var(--accent-color, #ff6b35);
            box-shadow: 0 0 0 3px var(--accent-shadow, rgba(255, 107, 53, 0.2));
        }
        
        .form-actions {
            display: flex;
            gap: 12px;
            justify-content: center;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        
        .character-progress {
            text-align: center;
            padding: 20px;
        }
        
        .progress-container {
            margin-bottom: 20px;
        }
        
        .progress-bar {
            width: 100%;
            height: 8px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 10px;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #ff6b35, #f7931e);
            border-radius: 4px;
            transition: width 0.3s ease;
            width: 0%;
        }
        
        .progress-text {
            color: var(--text-color, #e0e0e0);
            font-size: 14px;
        }
        
        .character-result {
            margin-top: 20px;
        }
        
        .character-card {
            background: var(--card-bg, rgba(255, 255, 255, 0.05));
            border: 1px solid var(--card-border, rgba(255, 255, 255, 0.1));
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .character-header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--accent-color, #ff6b35);
        }
        
        .character-header h3 {
            margin: 0 0 10px 0;
            color: var(--text-color, #ffffff);
            font-size: 24px;
        }
        
        .character-subtitle {
            color: var(--text-color, #e0e0e0);
            opacity: 0.8;
            font-size: 16px;
        }
        
        .character-stats {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .stat-row {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .stat-item {
            flex: 1;
            min-width: 150px;
            padding: 8px 12px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 6px;
            border-left: 3px solid var(--accent-color, #ff6b35);
        }
        
        .abilities-section {
            background: rgba(255, 255, 255, 0.03);
            padding: 15px;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .abilities-section h4 {
            margin: 0 0 15px 0;
            color: var(--accent-color, #ff6b35);
            font-size: 18px;
        }
        
        .abilities-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
        }
        
        .ability-item {
            padding: 8px 12px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 6px;
            text-align: center;
            font-weight: 600;
        }
        
        .character-description,
        .character-background,
        .character-equipment,
        .character-spells {
            background: rgba(255, 255, 255, 0.03);
            padding: 15px;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            margin-top: 15px;
        }
        
        .character-description h4,
        .character-background h4,
        .character-equipment h4,
        .character-spells h4 {
            margin: 0 0 10px 0;
            color: var(--accent-color, #ff6b35);
            font-size: 16px;
        }
        
        .equipment-category {
            margin: 10px 0;
        }
        
        .equipment-category strong {
            color: var(--accent-color, #ff6b35);
            display: block;
            margin-bottom: 5px;
        }
        
        .equipment-category ul {
            margin: 5px 0 0 20px;
            padding: 0;
        }
        
        .equipment-category li {
            margin: 3px 0;
        }
        
        .character-actions {
            display: flex;
            gap: 12px;
            justify-content: center;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        
        .character-note {
            background: var(--note-bg, rgba(255, 255, 255, 0.05));
            border: 1px solid var(--note-border, rgba(255, 255, 255, 0.1));
            border-radius: 8px;
            padding: 15px;
            margin: 10px 0;
        }
        
        .character-note-title {
            font-size: 18px;
            font-weight: bold;
            color: var(--accent-color, #ff6b35);
            margin-bottom: 10px;
            text-align: center;
        }
        
        .character-note-info {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .character-note-info div {
            padding: 4px 0;
        }
        
        .character-note-info strong {
            color: var(--text-color, #ffffff);
        }
        
        /* Стили для генератора заклинаний */
        .spell-generator {
            background: var(--spell-generator-bg, rgba(255, 255, 255, 0.05));
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
            border: 1px solid var(--spell-generator-border, rgba(255, 255, 255, 0.1));
            box-shadow: var(--spell-generator-shadow, 0 4px 15px rgba(0, 0, 0, 0.1));
        }
        
        .spell-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .spells-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .spell-card {
            background: var(--spell-card-bg, rgba(255, 255, 255, 0.1));
            border: 1px solid var(--spell-card-border, rgba(255, 255, 255, 0.2));
            border-radius: 8px;
            padding: 16px;
            transition: all 0.3s ease;
        }
        
        .spell-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px var(--spell-card-shadow, rgba(0, 0, 0, 0.2));
        }
        
        .spell-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 1px solid var(--spell-card-border, rgba(255, 255, 255, 0.2));
        }
        
        .spell-name {
            margin: 0;
            color: var(--spell-name-color, #ffffff);
            font-size: 18px;
            font-weight: 600;
        }
        
        .spell-level {
            background: var(--spell-level-bg, #ff6b35);
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .spell-info {
            display: flex;
            gap: 12px;
            margin-bottom: 12px;
            flex-wrap: wrap;
        }
        
        .spell-school, .spell-classes {
            background: var(--spell-info-bg, rgba(255, 255, 255, 0.1));
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            color: var(--spell-info-color, #e0e0e0);
        }
        
        .spell-description {
            color: var(--spell-description-color, #e0e0e0);
            font-size: 14px;
            line-height: 1.5;
        }
        
        .spell-description p {
            margin: 8px 0;
        }
        
        .spell-description strong {
            color: var(--spell-strong-color, #ffffff);
        }
        
        /* Темы для генератора заклинаний */
        .theme-dark .spell-generator {
            --spell-generator-bg: rgba(30, 30, 30, 0.8);
            --spell-generator-border: rgba(255, 255, 255, 0.15);
            --spell-generator-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
            --spell-card-bg: rgba(40, 40, 40, 0.8);
            --spell-card-border: rgba(255, 255, 255, 0.2);
            --spell-card-shadow: rgba(0, 0, 0, 0.4);
            --spell-name-color: #e0e0e0;
            --spell-level-bg: #ff6b35;
            --spell-info-bg: rgba(255, 255, 255, 0.1);
            --spell-info-color: #e0e0e0;
            --spell-description-color: #e0e0e0;
            --spell-strong-color: #ffffff;
        }
        
        .theme-light .spell-generator {
            --spell-generator-bg: rgba(255, 255, 255, 0.9);
            --spell-generator-border: rgba(0, 0, 0, 0.1);
            --spell-generator-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            --spell-card-bg: rgba(248, 248, 248, 0.9);
            --spell-card-border: rgba(0, 0, 0, 0.1);
            --spell-card-shadow: rgba(0, 0, 0, 0.1);
            --spell-name-color: #333333;
            --spell-level-bg: #2196F3;
            --spell-info-bg: rgba(0, 0, 0, 0.05);
            --spell-info-color: #666666;
            --spell-description-color: #333333;
            --spell-strong-color: #000000;
        }
        
        .theme-mystic .spell-generator {
            --spell-generator-bg: rgba(75, 0, 130, 0.8);
            --spell-generator-border: rgba(138, 43, 226, 0.3);
            --spell-generator-shadow: 0 4px 15px rgba(75, 0, 130, 0.3);
            --spell-card-bg: rgba(75, 0, 130, 0.9);
            --spell-card-border: rgba(138, 43, 226, 0.4);
            --spell-card-shadow: rgba(75, 0, 130, 0.4);
            --spell-name-color: #e6d7ff;
            --spell-level-bg: #9c27b0;
            --spell-info-bg: rgba(138, 43, 226, 0.2);
            --spell-info-color: #e6d7ff;
            --spell-description-color: #e6d7ff;
            --spell-strong-color: #ffffff;
        }
        
        .theme-orange .spell-generator {
            --spell-generator-bg: rgba(255, 152, 0, 0.1);
            --spell-generator-border: rgba(255, 152, 0, 0.3);
            --spell-generator-shadow: 0 4px 15px rgba(255, 152, 0, 0.2);
            --spell-card-bg: rgba(255, 152, 0, 0.1);
            --spell-card-border: rgba(255, 152, 0, 0.3);
            --spell-card-shadow: rgba(255, 152, 0, 0.2);
            --spell-name-color: #fff3e0;
            --spell-level-bg: #ff9800;
            --spell-info-bg: rgba(255, 152, 0, 0.2);
            --spell-info-color: #fff3e0;
            --spell-description-color: #fff3e0;
            --spell-strong-color: #ffffff;
        }
        
        /* Стили для генератора зелий */
        .potion-generator {
            background: var(--potion-generator-bg, rgba(255, 255, 255, 0.05));
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
            border: 1px solid var(--potion-generator-border, rgba(255, 255, 255, 0.1));
            box-shadow: var(--potion-generator-shadow, 0 4px 15px rgba(0, 0, 0, 0.1));
        }
        
        .potion-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .potions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .potion-card {
            background: var(--potion-card-bg, rgba(255, 255, 255, 0.1));
            border: 1px solid var(--potion-card-border, rgba(255, 255, 255, 0.2));
            border-radius: 8px;
            padding: 16px;
            transition: all 0.3s ease;
        }
        
        .potion-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px var(--potion-card-shadow, rgba(0, 0, 0, 0.2));
        }
        
        .potion-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 1px solid var(--potion-card-border, rgba(255, 255, 255, 0.2));
        }
        
        .potion-name {
            margin: 0;
            color: var(--potion-name-color, #ffffff);
            font-size: 18px;
            font-weight: 600;
        }
        
        .potion-rarity {
            background: var(--potion-rarity-bg, #ff6b35);
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .potion-body {
            color: var(--potion-body-color, #e0e0e0);
            font-size: 14px;
            line-height: 1.5;
        }
        
        .potion-effect {
            font-size: 16px !important;
            font-weight: 500;
            margin: 12px 0 !important;
            color: var(--potion-effect-color, #ffffff) !important;
        }
        
        .potion-duration {
            margin: 8px 0;
        }
        
        .potion-details {
            display: flex;
            gap: 12px;
            margin-top: 12px;
            flex-wrap: wrap;
        }
        
        .potion-type {
            background: var(--potion-info-bg, rgba(255, 255, 255, 0.1));
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            color: var(--potion-info-color, #e0e0e0);
        }
        
.potion-cost {
    background: var(--potion-cost-bg, rgba(255, 215, 0, 0.2));
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    color: var(--potion-cost-color, #ffd700);
    font-weight: 600;
}

.potion-actions {
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid var(--border-color, #ddd);
}

.potion-actions .btn {
    font-size: 12px;
    padding: 6px 12px;
}

/* Стили для заметок зелий */
.note-item.potion-note {
    border-left: 3px solid var(--accent-primary, #007bff);
    background: linear-gradient(90deg, rgba(0, 123, 255, 0.05) 0%, transparent 100%);
}

.note-item.potion-note:hover {
    background: linear-gradient(90deg, rgba(0, 123, 255, 0.1) 0%, transparent 100%);
}

.note-item.potion-note::before {
    content: "🧪";
    margin-right: 8px;
    opacity: 0.7;
}
        
        /* Темы для генератора зелий */
        .theme-dark .potion-generator {
            --potion-generator-bg: rgba(30, 30, 30, 0.8);
            --potion-generator-border: rgba(255, 255, 255, 0.15);
            --potion-generator-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
            --potion-card-bg: rgba(40, 40, 40, 0.8);
            --potion-card-border: rgba(255, 255, 255, 0.2);
            --potion-card-shadow: rgba(0, 0, 0, 0.4);
            --potion-name-color: #e0e0e0;
            --potion-rarity-bg: #ff6b35;
            --potion-info-bg: rgba(255, 255, 255, 0.1);
            --potion-info-color: #e0e0e0;
            --potion-body-color: #e0e0e0;
            --potion-effect-color: #ffffff;
            --potion-cost-bg: rgba(255, 215, 0, 0.2);
            --potion-cost-color: #ffd700;
        }
        
        .theme-light .potion-generator {
            --potion-generator-bg: rgba(255, 255, 255, 0.9);
            --potion-generator-border: rgba(0, 0, 0, 0.1);
            --potion-generator-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            --potion-card-bg: rgba(248, 248, 248, 0.9);
            --potion-card-border: rgba(0, 0, 0, 0.1);
            --potion-card-shadow: rgba(0, 0, 0, 0.1);
            --potion-name-color: #333333;
            --potion-rarity-bg: #2196F3;
            --potion-info-bg: rgba(0, 0, 0, 0.05);
            --potion-info-color: #666666;
            --potion-body-color: #333333;
            --potion-effect-color: #000000;
            --potion-cost-bg: rgba(255, 215, 0, 0.3);
            --potion-cost-color: #b8860b;
        }
        
        .theme-mystic .potion-generator {
            --potion-generator-bg: rgba(75, 0, 130, 0.8);
            --potion-generator-border: rgba(138, 43, 226, 0.3);
            --potion-generator-shadow: 0 4px 15px rgba(75, 0, 130, 0.3);
            --potion-card-bg: rgba(75, 0, 130, 0.9);
            --potion-card-border: rgba(138, 43, 226, 0.4);
            --potion-card-shadow: rgba(75, 0, 130, 0.4);
            --potion-name-color: #e6d7ff;
            --potion-rarity-bg: #9c27b0;
            --potion-info-bg: rgba(138, 43, 226, 0.2);
            --potion-info-color: #e6d7ff;
            --potion-body-color: #e6d7ff;
            --potion-effect-color: #ffffff;
            --potion-cost-bg: rgba(255, 215, 0, 0.3);
            --potion-cost-color: #ffd700;
        }
        
        .theme-orange .potion-generator {
            --potion-generator-bg: rgba(255, 152, 0, 0.1);
            --potion-generator-border: rgba(255, 152, 0, 0.3);
            --potion-generator-shadow: 0 4px 15px rgba(255, 152, 0, 0.2);
            --potion-card-bg: rgba(255, 152, 0, 0.1);
            --potion-card-border: rgba(255, 152, 0, 0.3);
            --potion-card-shadow: rgba(255, 152, 0, 0.2);
            --potion-name-color: #fff3e0;
            --potion-rarity-bg: #ff9800;
            --potion-info-bg: rgba(255, 152, 0, 0.2);
            --potion-info-color: #fff3e0;
            --potion-body-color: #fff3e0;
            --potion-effect-color: #ffffff;
            --potion-cost-bg: rgba(255, 215, 0, 0.3);
            --potion-cost-color: #ffd700;
        }
        
        .character-form-new {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .form-row {
            display: flex;
            gap: 20px;
            align-items: end;
        }
        
        .form-row .form-group {
            flex: 1;
        }
        
        .form-row .form-group.full-width {
            flex: 1;
        }
        
        
        .select-wrapper {
            position: relative;
        }
        
        .subrace-tooltip {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: rgba(0, 0, 0, 0.9);
            border: 1px solid #ff6b35;
            border-radius: 8px;
            padding: 12px;
            margin-top: 5px;
            z-index: 1000;
            display: none;
            color: #ffffff;
            font-size: 12px;
        }
        
        .subrace-tooltip.show {
            display: block;
        }
        
        .subrace-tooltip h4 {
            margin: 0 0 8px 0;
            color: #ff6b35;
            font-size: 14px;
        }
        
        .subrace-tooltip ul {
            margin: 0;
            padding-left: 16px;
        }
        
        .subrace-tooltip li {
            margin: 4px 0;
        }
        
        
        .generate-btn:active {
            transform: translateY(0);
        }
        
        /* Кнопки управления результатами */
        .result-actions {
            display: flex;
            gap: 12px;
            justify-content: center;
            margin: 20px 0;
            flex-wrap: wrap;
        }
        
        .result-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .result-btn.regenerate {
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
        }
        
        .result-btn.new {
            background: linear-gradient(135deg, #2196F3, #1976D2);
            color: white;
        }
        
        .result-btn.save {
            background: linear-gradient(135deg, #9C27B0, #7B1FA2);
            color: white;
        }
        
        .result-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }
        
        /* Адаптивность */
        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
                gap: 15px;
            }
            
            .result-actions {
                flex-direction: column;
                align-items: center;
            }
            
            .result-btn {
                width: 200px;
                justify-content: center;
            }
        }
    </style>
`;

// Добавляем стили в head документа
if (!document.getElementById('equipment-styles')) {
    const styleElement = document.createElement('div');
    styleElement.id = 'equipment-styles';
    styleElement.innerHTML = equipmentStyles;
    document.head.appendChild(styleElement);
}

// --- Перевод названий действий ---
function translateActionName(actionName) {
    const translations = {
        'Bite': 'Укус',
        'Claw': 'Коготь',
        'Tail': 'Хвост',
        'Gore': 'Рог',
        'Slam': 'Удар',
        'Tentacle': 'Щупальце',
        'Sting': 'Жало',
        'Spit': 'Плевок',
        'Breath': 'Дыхание',
        'Wing': 'Крыло',
        'Hoof': 'Копыто',
        'Punch': 'Кулак',
        'Kick': 'Пинок',
        'Headbutt': 'Удар головой',
        'Charge': 'Рывок',
        'Trample': 'Топтание',
        'Swallow': 'Проглатывание',
        'Constrict': 'Сжатие',
        'Grapple': 'Захват',
        'Shove': 'Толчок',
        'Dash': 'Рывок',
        'Disengage': 'Отход',
        'Dodge': 'Уклонение',
        'Help': 'Помощь',
        'Hide': 'Скрытие',
        'Ready': 'Подготовка',
        'Search': 'Поиск',
        'Use an Object': 'Использование предмета',
        'Teleport': 'Телепортация',
        'Invisibility': 'Невидимость',
        'Polymorph': 'Превращение',
        'Charm': 'Очарование',
        'Fear': 'Страх',
        'Sleep': 'Сон',
        'Confusion': 'Смятение',
        'Paralysis': 'Паралич',
        'Poison': 'Яд',
        'Disease': 'Болезнь',
        'Curse': 'Проклятие',
        'Blessing': 'Благословение',
        'Healing': 'Исцеление',
        'Regeneration': 'Регенерация',
        'Summon': 'Призыв',
        'Banish': 'Изгнание',
        'Plane Shift': 'Сдвиг плана',
        'Time Stop': 'Остановка времени',
        'Wish': 'Желание',
        'Meteor Swarm': 'Метеоритный дождь',
        'Power Word Kill': 'Слово силы: смерть',
        'Power Word Stun': 'Слово силы: оглушение',
        'Power Word Heal': 'Слово силы: исцеление'
    };
    
    return translations[actionName] || actionName;
}

// --- Форматирование противников от API системы ---
function formatEnemiesFromApi(enemies) {
    console.log('formatEnemiesFromApi called with:', enemies);
    
    // Проверяем, что enemies является массивом
    if (!enemies || !Array.isArray(enemies)) {
        console.error('Invalid enemies data:', enemies);
        return '<div class="error">Ошибка: Некорректные данные противников</div>';
    }
    
    let out = '<div class="enemies-container">';
    enemies.forEach((enemy, index) => {
        // Проверяем, что enemy является валидным объектом
        if (!enemy || typeof enemy !== 'object') {
            out += '<div class="error">Ошибка: Некорректные данные противника #' + (index + 1) + '</div>';
            return;
        }
        
        out += '<div class="enemy-block">';
        
        // Заголовок противника
        out += '<div class="enemy-header">';
        out += '<h3>' + (enemy.name || 'Без имени') + '</h3>';
        out += '<div class="enemy-cr">CR ' + (enemy.challenge_rating || enemy.cr || '?') + '</div>';
        
        // Показываем информацию о группе если это группа
        if (enemy.is_group && enemy.count > 1) {
            out += '<div class="enemy-group-info">Группа из ' + enemy.count + ' существ</div>';
        }
        out += '</div>';
        
        // Добавляем информацию об уровне угрозы если доступна
        if (enemy.threat_level_display) {
            out += '<div class="enemy-threat-level">' + enemy.threat_level_display + '</div>';
        }
        
        // Основная информация
        out += '<div class="enemy-section">';
        out += '<div class="section-title" onclick="toggleSection(this)">🏷️ Основная информация <span class="toggle-icon">▼</span></div>';
        out += '<div class="section-content">';
        out += '<div class="info-grid">';
        out += '<div class="info-item"><strong>Тип:</strong> ' + (enemy.type || 'Не определен') + '</div>';
        // Определяем отображаемую среду
        let displayEnvironment = enemy.environment || 'Любая среда';
        if (displayEnvironment === 'Любая среда') {
            // Если у монстра нет environment, показываем выбранную пользователем среду
            const selectedEnvironment = document.getElementById('enemy-environment')?.value;
            if (selectedEnvironment) {
                const environmentNames = {
                    'forest': 'Лес',
                    'mountain': 'Горы', 
                    'desert': 'Пустыня',
                    'swamp': 'Болота',
                    'underdark': 'Подземелье',
                    'urban': 'Город',
                    'coastal': 'Побережье'
                };
                displayEnvironment = environmentNames[selectedEnvironment] || selectedEnvironment;
            }
        }
        out += '<div class="info-item"><strong>Среда:</strong> ' + displayEnvironment + '</div>';
        out += '</div>';
        out += '</div></div>';
        
        // Боевые характеристики
        out += '<div class="enemy-section">';
        out += '<div class="section-title collapsed" onclick="toggleSection(this)">&#9876;&#65039; Боевые характеристики <span class="toggle-icon">▶</span></div>';
        out += '<div class="section-content collapsed">';
        out += '<div class="info-grid">';
        out += '<div class="info-item"><strong>Хиты:</strong> ' + (enemy.hit_points || enemy.hp || enemy.hit_points || 'Не определены') + '</div>';
        out += '<div class="info-item"><strong>Класс доспеха:</strong> ' + (enemy.armor_class || enemy.ac || 'Не определен') + '</div>';
        out += '<div class="info-item"><strong>Скорость:</strong> ' + (enemy.speed || 'Не определена') + '</div>';
        if (enemy.cr_numeric !== undefined) {
            out += '<div class="info-item"><strong>CR числовой:</strong> ' + enemy.cr_numeric + '</div>';
        }
        out += '</div>';
        out += '</div></div>';
        
        // Характеристики
        if (enemy.abilities && typeof enemy.abilities === 'object') {
            out += '<div class="enemy-section">';
            out += '<div class="section-title collapsed" onclick="toggleSection(this)">📊 Характеристики <span class="toggle-icon">▶</span></div>';
            out += '<div class="section-content collapsed">';
            out += '<div class="abilities-grid">';
            
            // Проверяем, есть ли переведенные названия характеристик
            if (enemy.abilities.СИЛ) {
                out += '<div class="ability-item"><strong>СИЛ:</strong> ' + enemy.abilities.СИЛ.value + ' (' + (enemy.abilities.СИЛ.modifier >= 0 ? '+' : '') + enemy.abilities.СИЛ.modifier + ')</div>';
            } else if (enemy.abilities.str || enemy.abilities.strength) {
                const strValue = enemy.abilities.str || enemy.abilities.strength;
                const strMod = Math.floor((strValue - 10) / 2);
                out += '<div class="ability-item"><strong>СИЛ:</strong> ' + strValue + ' (' + (strMod >= 0 ? '+' : '') + strMod + ')</div>';
            }
            
            if (enemy.abilities.ЛОВ) {
                out += '<div class="ability-item"><strong>ЛОВ:</strong> ' + enemy.abilities.ЛОВ.value + ' (' + (enemy.abilities.ЛОВ.modifier >= 0 ? '+' : '') + enemy.abilities.ЛОВ.modifier + ')</div>';
            } else if (enemy.abilities.dex || enemy.abilities.dexterity) {
                const dexValue = enemy.abilities.dex || enemy.abilities.dexterity;
                const dexMod = Math.floor((dexValue - 10) / 2);
                out += '<div class="ability-item"><strong>ЛОВ:</strong> ' + dexValue + ' (' + (dexMod >= 0 ? '+' : '') + dexMod + ')</div>';
            }
            
            if (enemy.abilities.ТЕЛ) {
                out += '<div class="ability-item"><strong>ТЕЛ:</strong> ' + enemy.abilities.ТЕЛ.value + ' (' + (enemy.abilities.ТЕЛ.modifier >= 0 ? '+' : '') + enemy.abilities.ТЕЛ.modifier + ')</div>';
            } else if (enemy.abilities.con || enemy.abilities.constitution) {
                const conValue = enemy.abilities.con || enemy.abilities.constitution;
                const conMod = Math.floor((conValue - 10) / 2);
                out += '<div class="ability-item"><strong>ТЕЛ:</strong> ' + conValue + ' (' + (conMod >= 0 ? '+' : '') + conMod + ')</div>';
            }
            
            if (enemy.abilities.ИНТ) {
                out += '<div class="ability-item"><strong>ИНТ:</strong> ' + enemy.abilities.ИНТ.value + ' (' + (enemy.abilities.ИНТ.modifier >= 0 ? '+' : '') + enemy.abilities.ИНТ.modifier + ')</div>';
            } else if (enemy.abilities.int || enemy.abilities.intelligence) {
                const intValue = enemy.abilities.int || enemy.abilities.intelligence;
                const intMod = Math.floor((intValue - 10) / 2);
                out += '<div class="ability-item"><strong>ИНТ:</strong> ' + intValue + ' (' + (intMod >= 0 ? '+' : '') + intMod + ')</div>';
            }
            
            if (enemy.abilities.МДР) {
                out += '<div class="ability-item"><strong>МДР:</strong> ' + enemy.abilities.МДР.value + ' (' + (enemy.abilities.МДР.modifier >= 0 ? '+' : '') + enemy.abilities.МДР.modifier + ')</div>';
            } else if (enemy.abilities.wis || enemy.abilities.wisdom) {
                const wisValue = enemy.abilities.wis || enemy.abilities.wisdom;
                const wisMod = Math.floor((wisValue - 10) / 2);
                out += '<div class="ability-item"><strong>МДР:</strong> ' + wisValue + ' (' + (wisMod >= 0 ? '+' : '') + wisMod + ')</div>';
            }
            
            if (enemy.abilities.ХАР) {
                out += '<div class="ability-item"><strong>ХАР:</strong> ' + enemy.abilities.ХАР.value + ' (' + (enemy.abilities.ХАР.modifier >= 0 ? '+' : '') + enemy.abilities.ХАР.modifier + ')</div>';
            } else if (enemy.abilities.cha || enemy.abilities.charisma) {
                const chaValue = enemy.abilities.cha || enemy.abilities.charisma;
                const chaMod = Math.floor((chaValue - 10) / 2);
                out += '<div class="ability-item"><strong>ХАР:</strong> ' + chaValue + ' (' + (chaMod >= 0 ? '+' : '') + chaMod + ')</div>';
            }
            
            out += '</div>';
            out += '</div></div>';
        }
        
        // Действия
        if (enemy.actions && Array.isArray(enemy.actions) && enemy.actions.length > 0) {
            out += '<div class="enemy-section">';
            out += '<div class="section-title collapsed" onclick="toggleSection(this)">&#9876;&#65039; Действия <span class="toggle-icon">▶</span></div>';
            out += '<div class="section-content collapsed">';
            out += '<ul class="actions-list">';
            enemy.actions.forEach(action => {
                if (action && typeof action === 'object') {
                    const actionName = translateActionName(action.name || 'Неизвестное действие');
                    out += '<li><strong>' + actionName + '</strong>';
                    if (action.description) {
                        out += ': ' + action.description;
                    }
                    out += '</li>';
                } else if (typeof action === 'string') {
                    const actionName = translateActionName(action);
                    out += '<li>' + actionName + '</li>';
                }
            });
            out += '</ul>';
            out += '</div></div>';
        }
        
        // Особые способности
        if (enemy.special_abilities && Array.isArray(enemy.special_abilities) && enemy.special_abilities.length > 0) {
            out += '<div class="enemy-section">';
            out += '<div class="section-title collapsed" onclick="toggleSection(this)">🌟 Особые способности <span class="toggle-icon">▶</span></div>';
            out += '<div class="section-content collapsed">';
            out += '<ul class="abilities-list">';
            enemy.special_abilities.forEach(ability => {
                if (ability && typeof ability === 'object') {
                    const abilityName = translateActionName(ability.name || 'Неизвестная способность');
                    out += '<li><strong>' + abilityName + '</strong>';
                    if (ability.description) {
                        out += ': ' + ability.description;
                    }
                    out += '</li>';
                } else if (typeof ability === 'string') {
                    const abilityName = translateActionName(ability);
                    out += '<li>' + abilityName + '</li>';
                }
            });
            out += '</ul>';
            out += '</div></div>';
        }
        
        // Описание
        if (enemy.description && typeof enemy.description === 'string') {
            out += '<div class="enemy-section">';
            out += '<div class="section-title collapsed" onclick="toggleSection(this)">📝 Описание <span class="toggle-icon">▶</span></div>';
            out += '<div class="section-content collapsed">';
            out += '<p>' + enemy.description + '</p>';
            out += '</div></div>';
        }
        
        // Тактика
        if (enemy.tactics && typeof enemy.tactics === 'string') {
            out += '<div class="enemy-section">';
            out += '<div class="section-title collapsed" onclick="toggleSection(this)">🎯 Тактика <span class="toggle-icon">▶</span></div>';
            out += '<div class="section-content collapsed">';
            out += '<p>' + enemy.tactics + '</p>';
            out += '</div></div>';
        }
        
        // Добавляем кнопку сохранения в заметки
        // Убираем индивидуальные кнопки сохранения - будет общая кнопка внизу
        
        out += '</div>';
        
        if (index < enemies.length - 1) {
            out += '<hr class="enemy-separator">';
        }
    });
    
    // Добавляем кнопку сохранения всех противников в заметки
    out += `
        <div class="save-enemies-section">
            <button class="save-enemies-btn" onclick="saveAllEnemiesToNotes(${JSON.stringify(enemies)})">
                💾 Сохранить всех в заметки
            </button>
        </div>
    `;
    
    out += '</div>';
    return out;
}

// --- Форматирование результата бросков ---
function formatResultSegments(txt, isNpc) {
    if (isNpc) {
        return formatNpcBlocks(txt);
    } else {
        // Для бросков: красивое форматирование с эмодзи
        const lines = txt.split(/<br>|\n/).map(l => l.trim()).filter(Boolean);
        let out = '<div class="dice-result-container">';
        
        lines.forEach((line, index) => {
            let className = 'dice-result-line';
            if (line.includes('🎲')) {
                className += ' dice-header';
            } else if (line.includes('📊')) {
                className += ' dice-results';
            } else if (line.includes('💎')) {
                className += ' dice-sum';
            } else if (line.includes('💬')) {
                className += ' dice-comment';
            }
            
            out += `<div class="${className}">${line}</div>`;
        });
        
        out += '</div>';
        return out;
    }
}
// --- Modal & Notes ---
function showModal(content) {
    document.getElementById('modal-content').innerHTML = content;
    document.getElementById('modal-bg').classList.add('active');
}
function closeModal() {
    document.getElementById('modal-bg').classList.remove('active');
    // Удаляем кнопку повторной генерации при закрытии
    let regenerateBtn = document.querySelector('.modal-regenerate');
    if (regenerateBtn) {
        regenerateBtn.remove();
    }
}
document.getElementById('modal-close').onclick = closeModal;
document.getElementById('modal-bg').onclick = function(e) { if (e.target === this) closeModal(); };

// Функция для сохранения результата костей с комментарием
function saveDiceResultAsNote(content, comment) {
    // Если comment не передан, используем "Бросок костей"
    if (!comment || comment.trim() === '') {
        comment = 'Бросок костей';
    }
    
    // Добавляем комментарий в начало заметки для идентификации
    var noteWithComment = '<div class="dice-result-header">' + comment + '</div>' + content;
    
    fetch('', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'add_note=1&note_content=' + encodeURIComponent(noteWithComment)
    }).then(() => {
        // Мгновенно обновляем заметки без перезагрузки
        updateNotesInstantly();
    });
}

// Новая функция для сохранения заметки с мгновенным обновлением
function saveNoteAndUpdate(content) {
    // Если content не передан, берем из модального окна
    if (!content) {
        content = document.getElementById('modal-content').innerHTML;
    }
    
    // Извлекаем имя NPC из заголовка
    var headerElement = document.querySelector('.npc-modern-header');
    var npcName = headerElement ? headerElement.textContent.trim() : 'NPC';
    
    // Если имя пустое или "NPC", пытаемся найти имя в содержимом
    if (!npcName || npcName === 'NPC') {
        // Ищем имя в тексте содержимого
        var plainText = content.replace(/<[^>]+>/g, '\n');
        var lines = plainText.split(/\n/).map(l => l.trim()).filter(Boolean);
        
        for (var i = 0; i < lines.length; i++) {
            var line = lines[i];
            if (line && line.length > 2 && line.length < 30 && 
                !/^(описание|внешность|черты|способность|оружие|урон|хиты|класс|раса|уровень|профессия|технические)/i.test(line) &&
                !line.includes(':') && !line.includes('—')) {
                npcName = line;
                break;
            }
        }
    }
    
    // Очищаем имя от лишних слов (только первое слово с большой буквы)
    if (npcName && npcName !== 'NPC') {
        var words = npcName.split(/\s+/);
        if (words.length > 1) {
            // Берем только первое слово как имя
            npcName = words[0];
        }
        // Убираем лишние символы
        npcName = npcName.replace(/[^\wа-яё]/gi, '').trim();
    }
    
    // Добавляем имя в начало заметки для лучшей идентификации
    var noteWithName = '<div class="npc-name-header">' + npcName + '</div>' + content;
    
    fetch('', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'add_note=1&note_content=' + encodeURIComponent(noteWithName)
    }).then(() => {
        // Мгновенно обновляем заметки без перезагрузки
        updateNotesInstantly();
    });
}

function saveNote(content) {
    // Сохраняем HTML содержимого модального окна
    var content = document.getElementById('modal-content').innerHTML;
    
    // Извлекаем имя NPC из заголовка
    var headerElement = document.querySelector('.npc-modern-header');
    var npcName = headerElement ? headerElement.textContent.trim() : 'NPC';
    
    // Если имя пустое или "NPC", пытаемся найти имя в содержимом
    if (!npcName || npcName === 'NPC') {
        // Ищем имя в тексте содержимого
        var plainText = content.replace(/<[^>]+>/g, '\n');
        var lines = plainText.split(/\n/).map(l => l.trim()).filter(Boolean);
        
        for (var i = 0; i < lines.length; i++) {
            var line = lines[i];
            if (line && line.length > 2 && line.length < 30 && 
                !/^(описание|внешность|черты|способность|оружие|урон|хиты|класс|раса|уровень|профессия|технические)/i.test(line) &&
                !line.includes(':') && !line.includes('—')) {
                npcName = line;
                break;
            }
        }
    }
    
    // Очищаем имя от лишних слов (только первое слово с большой буквы)
    if (npcName && npcName !== 'NPC') {
        var words = npcName.split(/\s+/);
        if (words.length > 1) {
            // Берем только первое слово как имя
            npcName = words[0];
        }
        // Убираем лишние символы
        npcName = npcName.replace(/[^\wа-яё]/gi, '').trim();
    }
    
    // Добавляем имя в начало заметки для лучшей идентификации
    var noteWithName = '<div class="npc-name-header">' + npcName + '</div>' + content;
    
    fetch('', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'add_note=1&note_content=' + encodeURIComponent(noteWithName)
    }).then(() => {
        // Обновляем только блок заметок без перезагрузки страницы
        updateNotesDisplay();
    });
}
function removeNote(idx) {
    fetch('', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'remove_note=' + encodeURIComponent(idx)
    }).then(() => {
        // Мгновенно обновляем заметки без перезагрузки
        updateNotesInstantly();
    });
}
function expandNote(idx) {
    if (window.allNotes && window.allNotes[idx]) {
        var content = window.allNotes[idx];
        if (content && content.trim()) {
            // Убираем дублирующий заголовок имени из начала заметки
            var cleanContent = content;
            var nameHeaderMatch = content.match(/<div class="npc-name-header">([^<]+)<\/div>/i);
            if (nameHeaderMatch) {
                // Убираем заголовок имени из начала
                cleanContent = content.replace(/<div class="npc-name-header">[^<]+<\/div>/i, '');
                // Убираем лишние пробелы в начале
                cleanContent = cleanContent.replace(/^\s+/, '');
            }
            
            document.getElementById('modal-content').innerHTML = cleanContent;
            document.getElementById('modal-bg').classList.add('active');
            document.getElementById('modal-save').style.display = 'none';
        }
    }
}
// Передаём все заметки в JS
window.allNotes = <?php echo json_encode($_SESSION['notes'], JSON_UNESCAPED_UNICODE); ?>;

// === ЗВУКОВАЯ СИСТЕМА ===
class SoundManager {
    constructor() {
        this.sounds = {};
        this.backgroundMusic = null;
        this.isMusicEnabled = true;
        this.isSoundEnabled = true;
        this.currentTheme = 'medium';
        this.init();
    }
    
    init() {
        console.log('SoundManager init started');
        
        // Загружаем настройки из localStorage
        this.loadSettings();
        
        // Устанавливаем текущую тему
        this.detectTheme();
        
        // Загружаем звуки
        this.loadSounds();
        
        // Не запускаем музыку автоматически - только по взаимодействию пользователя
        console.log('SoundManager initialized, waiting for user interaction');
    }
    
    detectTheme() {
        if (document.body) {
            // Проверяем data-theme атрибут
            const dataTheme = document.documentElement.getAttribute('data-theme');
            if (dataTheme) {
                this.currentTheme = dataTheme === 'medium' ? 'orange' : dataTheme;
            } else {
                // Fallback - проверяем localStorage
                const savedTheme = localStorage.getItem('theme') || 'medium';
                this.currentTheme = savedTheme === 'medium' ? 'orange' : savedTheme;
            }
        } else {
            // Fallback - проверяем localStorage
            const savedTheme = localStorage.getItem('theme') || 'medium';
            this.currentTheme = savedTheme === 'medium' ? 'orange' : savedTheme;
        }
        console.log('Detected theme:', this.currentTheme);
    }
    
    loadSounds() {
        try {
            console.log('Loading sounds...');
            
            // Звук клика
            this.sounds.click = new Audio('sound/click.mp3');
            this.sounds.click.volume = 0.6;
            console.log('Click sound loaded');
            
            // Фоновая музыка для разных тем (с версионированием для обхода кэша)
            const timestamp = Date.now();
            this.sounds.bgDark = new Audio(`sound/bg music dark.mp3?v=${timestamp}`);
            this.sounds.bgMystic = new Audio(`sound/bg music mystic.mp3?v=${timestamp}`);
            this.sounds.bgOrange = new Audio(`sound/bg music orange.mp3?v=${timestamp}`);
            this.sounds.bgIce = new Audio(`sound/bg music ice.mp3?v=${timestamp}`);
            console.log('Background music files loaded');
            
            // Настраиваем фоновую музыку
            Object.values(this.sounds).forEach(sound => {
                if (sound !== this.sounds.click) {
                    sound.loop = true;
                    sound.volume = 0.2;
                    sound.preload = 'auto';
                }
            });
            
            // Добавляем обработчики событий для отладки
            this.sounds.bgDark.addEventListener('canplaythrough', () => {
                console.log('Dark theme music ready to play');
            });
            this.sounds.bgMystic.addEventListener('canplaythrough', () => {
                console.log('Mystic theme music ready to play');
            });
            this.sounds.bgOrange.addEventListener('canplaythrough', () => {
                console.log('Orange theme music ready to play');
            });
            this.sounds.bgIce.addEventListener('canplaythrough', () => {
                console.log('Ice theme music ready to play');
            });
            
            console.log('All sounds loaded successfully');
        } catch (error) {
            console.log('Error loading sounds:', error);
            this.isSoundEnabled = false;
            this.isMusicEnabled = false;
        }
    }
    
    loadSettings() {
        this.isMusicEnabled = localStorage.getItem('musicEnabled') !== 'false';
        this.isSoundEnabled = localStorage.getItem('soundEnabled') !== 'false';
    }
    
    saveSettings() {
        localStorage.setItem('musicEnabled', this.isMusicEnabled);
        localStorage.setItem('soundEnabled', this.isSoundEnabled);
    }
    
    playClick() {
        if (this.isSoundEnabled && this.sounds.click) {
            try {
                this.sounds.click.currentTime = 0;
                this.sounds.click.play().catch(e => console.log('Click sound failed:', e));
            } catch (error) {
                console.log('Error playing click sound:', error);
            }
        }
    }
    
    startBackgroundMusic() {
        if (!this.isMusicEnabled) return;
        
        // Сначала останавливаем все фоновые аудиофайлы для предотвращения наложения
        Object.values(this.sounds).forEach(sound => {
            if (sound !== this.sounds.click) {
                sound.pause();
                sound.currentTime = 0;
            }
        });
        
        let musicFile = null;
        switch (this.currentTheme) {
            case 'dark':
                musicFile = this.sounds.bgDark;
                break;
            case 'mystic':
                musicFile = this.sounds.bgMystic;
                break;
            case 'orange':
                musicFile = this.sounds.bgOrange;
                break;
            case 'ice':
                musicFile = this.sounds.bgIce;
                break;
            default:
                return; // Тема без музыки
        }
        
        if (musicFile) {
            this.backgroundMusic = musicFile;
            console.log('Starting background music for theme:', this.currentTheme);
            this.backgroundMusic.play().then(() => {
                console.log('Background music started successfully');
            }).catch(e => {
                console.log('Background music failed:', e);
                // Попробуем еще раз через 2 секунды
                setTimeout(() => {
                    this.backgroundMusic.play().catch(e2 => console.log('Retry failed:', e2));
                }, 2000);
            });
        } else {
            console.log('No music file found for theme:', this.currentTheme);
        }
    }
    
    stopBackgroundMusic() {
        // Останавливаем текущую фоновую музыку
        if (this.backgroundMusic) {
            this.backgroundMusic.pause();
            this.backgroundMusic.currentTime = 0;
        }
        
        // Останавливаем ВСЕ фоновые аудиофайлы для предотвращения наложения
        Object.values(this.sounds).forEach(sound => {
            if (sound !== this.sounds.click) {
                sound.pause();
                sound.currentTime = 0;
            }
        });
        
        console.log('All background music stopped');
    }
    
    changeTheme(newTheme) {
        this.currentTheme = newTheme;
        this.stopBackgroundMusic();
        
        // Принудительно перезагружаем аудиофайлы для обхода кэша
        this.reloadAudioFiles();
        
        // Небольшая задержка перед запуском новой музыки
        setTimeout(() => {
            this.startBackgroundMusic();
        }, 500);
    }
    
    reloadAudioFiles() {
        try {
            const timestamp = Date.now();
            console.log('Reloading audio files to bypass cache...');
            
            // Сначала останавливаем все старые аудиофайлы
            Object.values(this.sounds).forEach(sound => {
                if (sound !== this.sounds.click) {
                    sound.pause();
                    sound.currentTime = 0;
                }
            });
            
            // Перезагружаем только фоновую музыку
            this.sounds.bgDark = new Audio(`sound/bg music dark.mp3?v=${timestamp}`);
            this.sounds.bgMystic = new Audio(`sound/bg music mystic.mp3?v=${timestamp}`);
            this.sounds.bgOrange = new Audio(`sound/bg music orange.mp3?v=${timestamp}`);
            this.sounds.bgIce = new Audio(`sound/bg music ice.mp3?v=${timestamp}`);
            
            // Настраиваем фоновую музыку
            Object.values(this.sounds).forEach(sound => {
                if (sound !== this.sounds.click) {
                    sound.loop = true;
                    sound.volume = 0.2;
                    sound.preload = 'auto';
                }
            });
            
            console.log('Audio files reloaded successfully');
        } catch (error) {
            console.log('Error reloading audio files:', error);
        }
    }
    
    toggleMusic() {
        this.isMusicEnabled = !this.isMusicEnabled;
        this.saveSettings();
        
        if (this.isMusicEnabled) {
            this.startBackgroundMusic();
        } else {
            this.stopBackgroundMusic();
        }
    }
    
    toggleSound() {
        this.isSoundEnabled = !this.isSoundEnabled;
        this.saveSettings();
    }
}

// Функция для добавления звука клика к кнопкам
function addClickSound() {
    // Добавляем звук ко всем кнопкам
    document.addEventListener('click', function(e) {
        if (e.target.tagName === 'BUTTON' || e.target.closest('button')) {
            console.log('Button clicked, playing sound...');
            if (window.soundManager) {
                window.soundManager.playClick();
            } else {
                console.log('SoundManager not available');
            }
        }
    });
    
    // Добавляем звук к ссылкам, которые ведут себя как кнопки
    document.addEventListener('click', function(e) {
        if (e.target.tagName === 'A' && e.target.classList.contains('btn')) {
            console.log('Link button clicked, playing sound...');
            if (window.soundManager) {
                window.soundManager.playClick();
            } else {
                console.log('SoundManager not available');
            }
        }
    });
}

// Инициализируем звуки после загрузки DOM
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, initializing SoundManager...');
    
    // Создаем глобальный экземпляр звукового менеджера
    window.soundManager = new SoundManager();
    
    // Обновляем тему после инициализации
    window.soundManager.detectTheme();
    
    // Дополнительная проверка для оранжевой темы при загрузке
    if (window.soundManager.currentTheme === 'orange') {
        setTimeout(() => {
            window.soundManager.startBackgroundMusic();
        }, 500);
    }
    
    addClickSound();
    
    // Принудительно запускаем музыку при первом взаимодействии пользователя
    function enableMusicOnInteraction() {
        console.log('User interaction detected, enabling music...');
        if (window.soundManager && window.soundManager.isMusicEnabled) {
            window.soundManager.startBackgroundMusic();
        }
        // Удаляем обработчики после первого взаимодействия
        document.removeEventListener('click', enableMusicOnInteraction);
        document.removeEventListener('keydown', enableMusicOnInteraction);
        document.removeEventListener('touchstart', enableMusicOnInteraction);
    }
    
    // Добавляем обработчики для принудительного запуска музыки
    document.addEventListener('click', enableMusicOnInteraction);
    document.addEventListener('keydown', enableMusicOnInteraction);
    document.addEventListener('touchstart', enableMusicOnInteraction);
});

// Функция для обновления отображения заметок без перезагрузки страницы
function updateNotesDisplay() {
    // Используем ту же логику, что и для мгновенного обновления
    updateNotesInstantly();
}

// Функция для обновления заметок с анимацией
function updateNotesWithAnimation() {
    console.log('updateNotesWithAnimation called');
    
    // Получаем блок заметок
    const notesBlock = document.getElementById('notes-block');
    if (!notesBlock) {
        console.log('notes-block not found');
        return;
    }
    
    // Добавляем анимацию загрузки
    const originalContent = notesBlock.innerHTML;
    notesBlock.innerHTML = `
        <div class="notes-loading" style="
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            color: var(--text-secondary, #666);
        ">
            <div class="loading-spinner" style="
                width: 20px;
                height: 20px;
                border: 2px solid var(--border-color, #ddd);
                border-top: 2px solid var(--accent-primary, #007bff);
                border-radius: 50%;
                animation: spin 1s linear infinite;
                margin-right: 10px;
            "></div>
            Обновление заметок...
        </div>
    `;
    
    // Добавляем CSS для анимации
    if (!document.getElementById('loading-spinner-css')) {
        const style = document.createElement('style');
        style.id = 'loading-spinner-css';
        style.textContent = `
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
            @keyframes fadeInUp {
                from {
                    opacity: 0;
                    transform: translateY(20px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            .note-item-animate {
                animation: fadeInUp 0.5s ease-out;
            }
        `;
        document.head.appendChild(style);
    }
    
    // Запрашиваем обновленные заметки
    fetch('', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'fast_action=update_notes'
    })
    .then(response => response.text())
    .then(html => {
        // Плавно заменяем содержимое
        notesBlock.innerHTML = html;
        
        // Добавляем анимацию появления для новых заметок
        const noteItems = notesBlock.querySelectorAll('.note-item');
        noteItems.forEach((item, index) => {
            item.classList.add('note-item-animate');
            item.style.animationDelay = `${index * 0.1}s`;
        });
        
        console.log('Notes updated with animation');
    })
    .catch(error => {
        console.error('Error updating notes:', error);
        // Восстанавливаем оригинальное содержимое при ошибке
        notesBlock.innerHTML = originalContent;
    });
}

// Функция для редактирования заголовка заметки
function editNoteTitle(noteIndex, currentTitle) {
    // Создаем модальное окно для редактирования
    const modalContent = `
        <div class="edit-note-modal">
            <h3>✏️ Редактировать название заметки</h3>
            <div class="edit-note-form">
                <label for="edit-note-input">Новое название:</label>
                <input type="text" id="edit-note-input" value="${currentTitle}" placeholder="Введите новое название" maxlength="50">
                <div class="edit-note-buttons">
                    <button class="edit-note-save" onclick="saveNoteTitle(${noteIndex})">💾 Сохранить</button>
                    <button class="edit-note-cancel" onclick="closeEditModal()">❌ Отмена</button>
                </div>
            </div>
        </div>
    `;
    
    showModal(modalContent);
    
    // Фокус на поле ввода
    setTimeout(() => {
        const input = document.getElementById('edit-note-input');
        if (input) {
            input.focus();
            input.select();
        }
    }, 100);
    
    // Обработка Enter для сохранения
    document.getElementById('edit-note-input').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            saveNoteTitle(noteIndex);
        } else if (e.key === 'Escape') {
            closeEditModal();
        }
    });
}

// Функция для сохранения нового названия заметки
function saveNoteTitle(noteIndex) {
    const newTitle = document.getElementById('edit-note-input').value.trim();
    
    if (newTitle === '') {
        alert('Название не может быть пустым');
        return;
    }
    
    fetch('', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'fast_action=edit_note_title&note_index=' + noteIndex + '&new_title=' + encodeURIComponent(newTitle)
    })
    .then(r => r.text())
    .then(response => {
        if (response === 'success') {
            closeEditModal();
            updateNotesInstantly();
        } else {
            alert('Ошибка при обновлении названия заметки');
        }
    })
    .catch(error => {
        console.error('Ошибка:', error);
        alert('Ошибка при обновлении названия заметки');
    });
}

// Функция для закрытия модального окна редактирования
function closeEditModal() {
    closeModal();
}

// Функция для мгновенного обновления заметок без перезагрузки
function updateNotesInstantly() {
    console.log('updateNotesInstantly called');
    
    // Получаем блок заметок
    const notesBlock = document.getElementById('notes-block');
    if (!notesBlock) {
        console.log('notes-block not found');
        return;
    }
    
    console.log('Fetching updated notes...');
    
    // Запрашиваем обновленные заметки
    fetch('', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'fast_action=update_notes'
    })
    .then(r => {
        console.log('Response status:', r.status);
        return r.text();
    })
    .then(html => {
        console.log('Received HTML:', html);
        
        // Удаляем старые заметки
        const oldNotes = notesBlock.querySelectorAll('.note-item');
        console.log('Removing', oldNotes.length, 'old notes');
        oldNotes.forEach(item => item.remove());
        
        // Создаем временный div для парсинга HTML
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = html;
        const newNoteItems = tempDiv.querySelectorAll('.note-item');
        console.log('Found', newNoteItems.length, 'new notes');
        
        // Добавляем новые заметки
        newNoteItems.forEach((item) => {
            const clonedItem = item.cloneNode(true);
            notesBlock.appendChild(clonedItem);
        });
        
        console.log('Notes updated successfully');
        
        // Обновляем данные в памяти
        fetch('', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'fast_action=get_notes_data'
        })
        .then(r => r.json())
        .then(data => {
            window.allNotes = data;
            console.log('Notes data updated in memory');
        });
    })
    .catch(error => {
        console.error('Error updating notes:', error);
    });
}

// Debug: выводим первую строку каждой заметки в консоль
if (window.allNotes) {
    window.allNotes.forEach((n, i) => {
        let plain = n.replace(/<[^>]+>/g, '\n');
        let lines = plain.split(/\n/).map(l => l.trim()).filter(Boolean);
        
        // Ищем имя NPC в специальном заголовке
        let nameMatch = n.match(/<div class="npc-name-header">([^<]+)<\/div>/i);
        let headerMatch = n.match(/<div class="npc-modern-header">([^<]+)<\/div>/i);
        let nameLine = lines.find(l => /^(Имя|Name|Имя NPC|Имя персонажа)\s*:/i.test(l));
        
        let preview = '';
        if (nameMatch) {
            preview = nameMatch[1].trim();
        } else if (headerMatch) {
            preview = headerMatch[1].trim();
        } else if (nameLine) {
            let match = nameLine.match(/^(Имя|Name|Имя NPC|Имя персонажа)\s*:\s*(.+)$/i);
            preview = match ? match[2].trim() : nameLine;
        } else {
            // Ищем первое значимое слово
            for (let line of lines) {
                if (line && !/^(описание|внешность|черты|способность|оружие|урон|хиты|класс|раса|уровень|профессия)/i.test(line)) {
                    preview = line;
                    break;
                }
            }
            if (!preview && lines.length) {
                preview = lines[0];
            }
        }
        
        // Очищаем превью от лишних слов
        preview = preview.replace(/^описание\s+/i, '').replace(/^\s*—\s*/, '').replace(/^npc\s+/i, '');
        
        // Очищаем превью - берем только первое слово если это имя
        if (nameMatch || headerMatch) {
            let words = preview.split(/\s+/);
            if (words.length > 1) {
                preview = words[0];
            }
        }
        
        console.log('Заметка', i, 'превью:', preview || '(нет данных)');
    });
}
// --- Чат: отправка сообщения ---
document.querySelector('form').onsubmit = function(e) {
    e.preventDefault();
    var msg = this.message.value.trim();
    if (!msg) return false;
    fetch('ai.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'prompt=' + encodeURIComponent(msg) + '&type=chat'
    })
    .then(r => r.json())
    .then(data => {
        if (data && data.result) {
            // Добавить сообщение в чат (можно обновить страницу или динамически)
            location.reload();
        } else {
            alert(data.error || 'Ошибка AI');
        }
    });
    return false;
};

        // Показываем приветственное сообщение для новых пользователей
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('welcome') === '1') {
            showWelcomeMessage();
        }

        // Горячие клавиши для быстрого доступа
        document.addEventListener('keydown', function(e) {
            // Ctrl+Enter для отправки сообщения
            if (e.ctrlKey && e.key === 'Enter') {
                e.preventDefault();
                // Используем новую функцию AI чата вместо старой формы
                if (typeof sendAIMessage === 'function') {
                    sendAIMessage();
                } else {
                    document.getElementById('chatForm').submit();
                }
            }
            
            // F1 для броска костей
            if (e.key === 'F1') {
                e.preventDefault();
                openDiceStep1();
            }
            
            // F2 для генерации персонажей
            if (e.key === 'F2') {
                e.preventDefault();
                openCharacterModal();
            }
            
            // F4 для генерации противников
            if (e.key === 'F4') {
                e.preventDefault();
                openEnemyModal();
            }
            
            // F3 для инициативы
            if (e.key === 'F3') {
                e.preventDefault();
                openInitiativeModal();
            }
            
            // Escape для закрытия модального окна
            if (e.key === 'Escape') {
                const modal = document.getElementById('modal-bg');
                if (modal && modal.classList.contains('active')) {
                    closeModal();
                }
            }
        });
        
        // --- Функция для переключения сворачиваемых секций ---
        function toggleSection(headerElement) {
            const contentElement = headerElement.nextElementSibling;
            const isCollapsed = headerElement.classList.contains('collapsed');
            
            if (isCollapsed) {
                // Разворачиваем
                headerElement.classList.remove('collapsed');
                contentElement.classList.remove('collapsed');
            } else {
                // Сворачиваем
                headerElement.classList.add('collapsed');
                contentElement.classList.add('collapsed');
            }
        }
        
        // --- Функция для переключения сворачиваемых технических параметров (для обратной совместимости) ---
        function toggleTechnicalParams(headerElement) {
            toggleSection(headerElement);
        }
        

        // --- Функция сохранения противника в заметки ---
        function saveEnemyToNotes(enemyData) {
            // Создаем полное содержимое заметки с именем противника как заголовком
            const noteContent = `
                <div class="enemy-note">
                    <div class="enemy-note-title">${enemyData.name}</div>
                    <div class="enemy-note-info">
                        <div><strong>Тип:</strong> ${enemyData.type || 'Не указан'}</div>

                        <div><strong>CR:</strong> ${enemyData.challenge_rating || 'Не указан'}</div>
                        <div><strong>Хиты:</strong> ${enemyData.hit_points || 'Не указаны'}</div>
                        <div><strong>КД:</strong> ${enemyData.armor_class || 'Не указан'}</div>
                                                <div><strong>Скорость:</strong> ${enemyData.speed || 'Не указана'}</div>
                        ${enemyData.environment ? `<div><strong>Среда:</strong> ${enemyData.environment}</div>` : ''}
                        <div><strong>Характеристики:</strong></div>
                        <div style="margin-left: 20px;">
                            <div>СИЛ: ${enemyData.abilities?.str || '0'}</div>
                            <div>ЛОВ: ${enemyData.abilities?.dex || '0'}</div>
                            <div>ТЕЛ: ${enemyData.abilities?.con || '0'}</div>
                            <div>ИНТ: ${enemyData.abilities?.int || '0'}</div>
                            <div>МДР: ${enemyData.abilities?.wis || '0'}</div>
                            <div>ХАР: ${enemyData.abilities?.cha || '0'}</div>
                        </div>
                        ${enemyData.actions && enemyData.actions.length > 0 ? `<div><strong>Действия:</strong> ${enemyData.actions.map(action => typeof action === 'string' ? action : (action.name || 'Неизвестное действие')).join(', ')}</div>` : ''}
                        ${enemyData.special_abilities && enemyData.special_abilities.length > 0 ? `<div><strong>Особые способности:</strong> ${enemyData.special_abilities.map(ability => typeof ability === 'string' ? ability : (ability.name || 'Неизвестная способность')).join(', ')}</div>` : ''}
                        ${enemyData.description ? `<div><strong>Описание:</strong> ${enemyData.description}</div>` : ''}
                        ${enemyData.tactics ? `<div><strong>Тактика:</strong> ${enemyData.tactics}</div>` : ''}
                    </div>
                </div>
            `;
            
            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'fast_action=save_note&content=' + encodeURIComponent(noteContent) + '&title=' + encodeURIComponent(enemyData.name)
            })
            .then(r => r.text())
            .then(() => {
                alert('Противник ' + enemyData.name + ' сохранен в заметки!');
            })
            .catch(error => {
                alert('Ошибка сохранения: ' + error.message);
            });
        }

        // --- Функция сохранения группы противников в заметки ---
        function saveEnemyGroupToNotes(groupData) {
            // Создаем отдельные заметки для каждого противника в группе
            const promises = [];
            
            groupData.group_info.individual_enemies.forEach((enemy, index) => {
                const noteContent = `
                    <div class="enemy-note">
                        <div class="enemy-note-title">${enemy.name}</div>
                        <div class="enemy-note-info">
                            <div><strong>Тип:</strong> ${enemy.type || 'Не указан'}</div>
                            <div><strong>CR:</strong> ${enemy.challenge_rating || 'Не указан'}</div>
                            <div><strong>Хиты:</strong> ${enemy.hit_points || 'Не указаны'}</div>
                            <div><strong>КД:</strong> ${enemy.armor_class || 'Не указан'}</div>
                            <div><strong>Скорость:</strong> ${enemy.speed || 'Не указана'}</div>
                            ${enemy.environment ? `<div><strong>Среда:</strong> ${enemy.environment}</div>` : ''}
                            <div><strong>Характеристики:</strong></div>
                            <div style="margin-left: 20px;">
                                <div>СИЛ: ${enemy.abilities?.str || '0'}</div>
                                <div>ЛОВ: ${enemy.abilities?.dex || '0'}</div>
                                <div>ТЕЛ: ${enemy.abilities?.con || '0'}</div>
                                <div>ИНТ: ${enemy.abilities?.int || '0'}</div>
                                <div>МДР: ${enemy.abilities?.wis || '0'}</div>
                                <div>ХАР: ${enemy.abilities?.cha || '0'}</div>
                            </div>
                            ${enemy.actions && enemy.actions.length > 0 ? `<div><strong>Действия:</strong> ${enemy.actions.map(action => typeof action === 'string' ? action : (action.name || 'Неизвестное действие')).join(', ')}</div>` : ''}
                            ${enemy.special_abilities && enemy.special_abilities.length > 0 ? `<div><strong>Особые способности:</strong> ${enemy.special_abilities.map(ability => typeof ability === 'string' ? ability : (ability.name || 'Неизвестная способность')).join(', ')}</div>` : ''}
                            ${enemy.description ? `<div><strong>Описание:</strong> ${enemy.description}</div>` : ''}
                            ${enemy.tactics ? `<div><strong>Тактика:</strong> ${enemy.tactics}</div>` : ''}
                        </div>
                    </div>
                `;
                
                const promise = fetch('', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'fast_action=save_note&content=' + encodeURIComponent(noteContent) + '&title=' + encodeURIComponent(enemy.name)
                }).then(r => r.text());
                
                promises.push(promise);
            });
            
            // Ждем сохранения всех заметок
            Promise.all(promises)
                .then(() => {
                    alert(`Группа из ${groupData.count} противников "${groupData.base_name}" сохранена в заметки!`);
                })
                .catch(error => {
                    alert('Ошибка сохранения группы: ' + error.message);
                });
        }

        // --- Функция сохранения всех противников в заметки ---
        function saveAllEnemiesToNotes(enemies) {
            const promises = [];
            
            enemies.forEach(enemy => {
                if (enemy.is_group && enemy.count > 1) {
                    // Для группы создаем отдельные заметки
                    enemy.group_info.individual_enemies.forEach(individualEnemy => {
                        const noteContent = `
                            <div class="enemy-note">
                                <div class="enemy-note-title">${individualEnemy.name}</div>
                                <div class="enemy-note-info">
                                    <div><strong>Тип:</strong> ${individualEnemy.type || 'Не указан'}</div>
                                    <div><strong>CR:</strong> ${individualEnemy.challenge_rating || 'Не указан'}</div>
                                    <div><strong>Хиты:</strong> ${individualEnemy.hit_points || 'Не указаны'}</div>
                                    <div><strong>КД:</strong> ${individualEnemy.armor_class || 'Не указан'}</div>
                                    <div><strong>Скорость:</strong> ${individualEnemy.speed || 'Не указана'}</div>
                                    ${individualEnemy.environment ? `<div><strong>Среда:</strong> ${individualEnemy.environment}</div>` : ''}
                                    <div><strong>Характеристики:</strong></div>
                                    <div style="margin-left: 20px;">
                                        <div>СИЛ: ${individualEnemy.abilities?.str || '0'}</div>
                                        <div>ЛОВ: ${individualEnemy.abilities?.dex || '0'}</div>
                                        <div>ТЕЛ: ${individualEnemy.abilities?.con || '0'}</div>
                                        <div>ИНТ: ${individualEnemy.abilities?.int || '0'}</div>
                                        <div>МДР: ${individualEnemy.abilities?.wis || '0'}</div>
                                        <div>ХАР: ${individualEnemy.abilities?.cha || '0'}</div>
                                    </div>
                                    ${individualEnemy.actions && individualEnemy.actions.length > 0 ? `<div><strong>Действия:</strong> ${individualEnemy.actions.map(action => typeof action === 'string' ? action : (action.name || 'Неизвестное действие')).join(', ')}</div>` : ''}
                                    ${individualEnemy.special_abilities && individualEnemy.special_abilities.length > 0 ? `<div><strong>Особые способности:</strong> ${individualEnemy.special_abilities.map(ability => typeof ability === 'string' ? ability : (ability.name || 'Неизвестная способность')).join(', ')}</div>` : ''}
                                    ${individualEnemy.description ? `<div><strong>Описание:</strong> ${individualEnemy.description}</div>` : ''}
                                    ${individualEnemy.tactics ? `<div><strong>Тактика:</strong> ${individualEnemy.tactics}</div>` : ''}
                                </div>
                            </div>
                        `;
                        
                        const promise = fetch('', {
                            method: 'POST',
                            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                            body: 'fast_action=save_note&content=' + encodeURIComponent(noteContent) + '&title=' + encodeURIComponent(individualEnemy.name)
                        }).then(r => r.text());
                        
                        promises.push(promise);
                    });
                } else {
                    // Для одиночного противника
                    const noteContent = `
                        <div class="enemy-note">
                            <div class="enemy-note-title">${enemy.name}</div>
                            <div class="enemy-note-info">
                                <div><strong>Тип:</strong> ${enemy.type || 'Не указан'}</div>
                                <div><strong>CR:</strong> ${enemy.challenge_rating || 'Не указан'}</div>
                                <div><strong>Хиты:</strong> ${enemy.hit_points || 'Не указаны'}</div>
                                <div><strong>КД:</strong> ${enemy.armor_class || 'Не указан'}</div>
                                <div><strong>Скорость:</strong> ${enemy.speed || 'Не указана'}</div>
                                ${enemy.environment ? `<div><strong>Среда:</strong> ${enemy.environment}</div>` : ''}
                                <div><strong>Характеристики:</strong></div>
                                <div style="margin-left: 20px;">
                                    <div>СИЛ: ${enemy.abilities?.str || '0'}</div>
                                    <div>ЛОВ: ${enemy.abilities?.dex || '0'}</div>
                                    <div>ТЕЛ: ${enemy.abilities?.con || '0'}</div>
                                    <div>ИНТ: ${enemy.abilities?.int || '0'}</div>
                                    <div>МДР: ${enemy.abilities?.wis || '0'}</div>
                                    <div>ХАР: ${enemy.abilities?.cha || '0'}</div>
                                </div>
                                ${enemy.actions && enemy.actions.length > 0 ? `<div><strong>Действия:</strong> ${enemy.actions.map(action => typeof action === 'string' ? action : (action.name || 'Неизвестное действие')).join(', ')}</div>` : ''}
                                ${enemy.special_abilities && enemy.special_abilities.length > 0 ? `<div><strong>Особые способности:</strong> ${enemy.special_abilities.map(ability => typeof ability === 'string' ? ability : (ability.name || 'Неизвестная способность')).join(', ')}</div>` : ''}
                                ${enemy.description ? `<div><strong>Описание:</strong> ${enemy.description}</div>` : ''}
                                ${enemy.tactics ? `<div><strong>Тактика:</strong> ${enemy.tactics}</div>` : ''}
                            </div>
                        </div>
                    `;
                    
                    const promise = fetch('', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: 'fast_action=save_note&content=' + encodeURIComponent(noteContent) + '&title=' + encodeURIComponent(enemy.name)
                    }).then(r => r.text());
                    
                    promises.push(promise);
                }
            });
            
            // Ждем сохранения всех заметок
            Promise.all(promises)
                .then(() => {
                    alert(`Все противники (${enemies.length} групп) сохранены в заметки!`);
                })
                .catch(error => {
                    alert('Ошибка сохранения: ' + error.message);
                });
        }

// --- Функция добавления из заметок ---
function addFromNotes(type) {
    // Получаем заметки из сессии
    const notes = <?php echo json_encode($_SESSION['notes'] ?? []); ?>;
    const characterNotes = [];
    const enemyNotes = [];
    
    notes.forEach((note, index) => {
        if (note.includes('character-note-title')) {
            // Извлекаем информацию о персонаже из нового формата
            const nameMatch = note.match(/<div class="character-note-title">([^<]+)<\/div>/);
            const raceMatch = note.match(/Раса:\s*([^<]+)/);
            const classMatch = note.match(/Класс:\s*([^<]+)/);
            const levelMatch = note.match(/Уровень:\s*(\d+)/);
            const initiativeMatch = note.match(/Инициатива:\s*([^<]+)/);
            
            if (nameMatch) {
                characterNotes.push({
                    index: index,
                    name: nameMatch[1].trim(),
                    race: raceMatch ? raceMatch[1].trim() : '',
                    class: classMatch ? classMatch[1].trim() : '',
                    level: levelMatch ? levelMatch[1] : '',
                    initiative: initiativeMatch ? initiativeMatch[1].trim() : '0'
                });
            }
        } else if (note.includes('enemy-note-title')) {
            // Извлекаем информацию о противнике из нового формата
            const nameMatch = note.match(/<div class="enemy-note-title">([^<]+)<\/div>/);
            const typeMatch = note.match(/Тип:\s*([^<]+)/);
            const crMatch = note.match(/CR:\s*([^<]+)/);
            const initiativeMatch = note.match(/Инициатива:\s*([^<]+)/);
            
            if (nameMatch) {
                enemyNotes.push({
                    index: index,
                    name: nameMatch[1].trim(),
                    type: typeMatch ? typeMatch[1].trim() : '',
                    cr: crMatch ? crMatch[1].trim() : '',
                    initiative: initiativeMatch ? initiativeMatch[1].trim() : '0'
                });
            }
        }
    });
    
    const notesToShow = type === 'player' ? characterNotes : enemyNotes;
    
    if (notesToShow.length === 0) {
        alert('В заметках нет ' + (type === 'player' ? 'персонажей' : 'противников') + ' для добавления');
        return;
    }
    
    let notesHtml = '<div class="notes-selection">';
    notesHtml += '<div class="notes-title">Выберите ' + (type === 'player' ? 'персонажа' : 'противника') + ' из заметок:</div>';
    notesHtml += '<div class="notes-list">';
    
    notesToShow.forEach(note => {
        const displayName = type === 'player' ? 
            `${note.name} (${note.race} ${note.class} ${note.level} ур.)` :
            `${note.name} (${note.type} CR ${note.cr})`;
        
        notesHtml += `
            <div class="note-item" onclick="selectFromNotes('${note.name.replace(/'/g, "\\'")}', '${note.initiative}', '${type}')">
                <div class="note-name">${displayName}</div>
                <div class="note-initiative">Инициатива: ${note.initiative}</div>
            </div>
        `;
    });
    
    notesHtml += '</div>';
    notesHtml += '<div class="notes-buttons">';
    notesHtml += '<button class="cancel-btn" onclick="openInitiativeModal()">Отмена</button>';
    notesHtml += '</div>';
    notesHtml += '</div>';
    
    showModal(notesHtml);
    document.getElementById('modal-save').style.display = 'none';
}

// --- Функция выбора из заметок ---
function selectFromNotes(name, initiative, type) {
    // Проверяем, существует ли форма инициативы
    const nameField = document.getElementById('initiative-name');
    const valueField = document.getElementById('initiative-value');
    
    if (nameField && valueField) {
        nameField.value = name;
        valueField.value = initiative;
        
        // Возвращаемся к форме добавления инициативы
        addInitiativeEntry(type);
    } else {
        // Если форма не открыта, открываем её и заполняем
        addInitiativeEntry(type);
        
        // Небольшая задержка для открытия формы
        setTimeout(() => {
            const nameField = document.getElementById('initiative-name');
            const valueField = document.getElementById('initiative-value');
            if (nameField && valueField) {
                nameField.value = name;
                valueField.value = initiative;
            }
        }, 100);
    }
}

// --- Функция переключения деталей заклинания ---
function toggleSpellDetails(header) {
    const details = header.nextElementSibling;
    const toggle = header.querySelector('.spell-toggle');
    
    if (details.style.display === 'none') {
        details.style.display = 'block';
        toggle.textContent = '▲';
    } else {
        details.style.display = 'none';
        toggle.textContent = '▼';
    }
}

// --- Функция переключения бросков спасения ---
function toggleSavingThrows(header) {
    const content = header.nextElementSibling;
    const toggle = header.querySelector('.toggle-icon');
    
    if (content.style.display === 'none') {
        content.style.display = 'block';
        toggle.textContent = '▲';
    } else {
        content.style.display = 'none';
        toggle.textContent = '▼';
    }
}

// --- Функция сохранения всех противников в заметки ---
function saveAllEnemiesToNotes(enemies) {
    if (!enemies || enemies.length === 0) {
        alert('Нет противников для сохранения');
        return;
    }
    
    let savedCount = 0;
    const totalCount = enemies.length;
    
    enemies.forEach((enemy, index) => {
        // Используем новую функцию saveEnemyToNotes для каждого противника
        saveEnemyToNotes(enemy);
        savedCount++;
        
        if (savedCount === totalCount) {
            setTimeout(() => {
                alert(`Сохранено ${savedCount} противников в заметки!`);
            }, 100);
        }
    });
}
</script>

<script>
// Надежная система загрузки SVG иконок
(function() {
    'use strict';
    
    // Встроенные SVG иконки из папки icons
    const icons = {
        dice: `<svg viewBox="0 0 512 512" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
  <path d="M248 20.3L72 132.6l176-3.8V20.3zm16 0v108.5l175.7 3.8L264 20.3zm43.1 49.97c2.8.06 5.8.75 9.2 2.08 2.3.91 4.1 1.91 5.6 3.07 1.5 1.15 2.8 2.5 3.7 3.79 1.5 2.06 2.2 4.04 2.6 6.25 2.4-1.77 5.2-2.98 8.2-3.84 3.4-.73 7.2-.35 11.1 1.23 4.6 1.82 8.1 4.19 10.3 7.11 2.2 2.93 3.5 5.97 4 9.13.3 1.71.3 3.41.1 5.01-.1 1.7-.6 3.3-1.2 4.7-.5 1.5-1.2 3-2.3 4.3-1 1.3-2.1 2.6-3.5 3.6-2.5 2-5.5 3.3-9.1 3.9-3.6.6-7.7 0-12.3-1.8-4-1.6-7-3.8-9-6.7-1.6-2.6-2.9-5.5-3.4-8.4-2 1.5-4.2 2.5-6.9 2.9-3 .6-6.5 0-10.7-1.6-2.4-1-4.5-2.1-6.2-3.3-1.8-1.3-3.2-2.61-4.3-4.08-2.1-2.58-3.2-5.37-3.5-8.35-.2-2.9.2-5.65 1.1-8.15.5-1.29 1.3-2.57 2.1-3.92 1-1.1 2-2.21 3.1-3.26 2.4-1.77 5.2-2.97 8.5-3.53.9-.12 1.8-.17 2.8-.14zM208 75.56c4.8.05 10.9 3.57 9 10.04-4 6.9-10.3 12.17-18 14.8-7.4 2.5-15 4.4-22 1.9-3-2.3-13-9.4-15-3.4-1.2 15.3 1 13-11 17.8V92.3c10-3.9 21-4.5 31 1.3 8 4.2 19 1.5 24-5.8 1-6.5-8-4.5-12-3.3-3-8.3 7.8-8.43 13-8.9.3-.03.6-.04 1-.04zm100.5 4.46c-.9.01-1.8.14-2.8.36-2.4.61-4.2 2.17-5.1 4.67-1 2.42-.8 4.74.6 6.88 1.4 2.22 3.3 3.73 6 4.78 2.9 1.15 5.4 1.41 8 .73 2.5-.56 4.3-2.12 5.2-4.54 1-2.5.8-4.82-.7-7.01-1.4-2.14-3.5-3.77-6.4-4.92-1.6-.66-3.2-.96-4.8-.95zm28.9 10.15c-1.1.05-2.2.27-3.2.65-2.7 1.17-4.5 2.96-5.4 5.39-1 2.5-.9 5.09.4 7.59 1.2 2.6 3.6 4.6 7.2 6.1 2.9 1.1 5.8 1.3 8.6.6 2.8-.8 4.7-2.7 5.8-5.6 1.1-2.9 1.1-5.53-.5-8.01-1.5-2.47-3.7-4.37-6.6-5.51-2.3-.9-4.4-1.29-6.3-1.21zM242 144.9L55 149l72 192.9 115-197zm28 0l115.4 197L456.6 149 270 144.9zm-14 7.5L139 352.6h234.1L256 152.4zm116.6 16.4l19.2 42.5 7.2-3.3 4.1 9.2-7.1 3.2 6.3 14-10.4 4.7-6.3-14-30.2 13.6-3.9-8.7c1.4-9.2 4.4-27.8 8.9-55.7l1.8-.8.8-.3 3.1-1.5 6.5-2.9zm-225.9 12.1h1.3c2.9 0 5.5.5 7.8 1.6 6.9 3.2 10.7 8.4 11.7 15.3.9 6.9-1 15.3-5.7 25.1-4.7 9.7-10 16.5-15.8 20.3-6 3.8-12.3 4.1-19.1 1-5.9-2.8-9.4-6.7-10.6-11.9-1.2-5.3-.9-9.7.9-13.5l9.6 4.4c-.9 1.7-1.1 3.8-.8 6.3.3 2.6 1.9 4.5 5 6 3.1 1.4 6.1 1.3 9.2-.2 3.1-1.4 6.3-5.2 9.7-11.3.5-1 1.1-2.1 1.7-3.3-1.8 1.2-3.6 2-5.5 2.6-3.2.9-6.6.5-10.3-1.2-4.3-2-7.5-5.5-9.5-10.6-2.1-5-1.5-10.7 1.6-17.2l.1-.1c1.1-2.3 2.4-4.4 4-6 1.4-1.7 3.1-3.1 4.8-4.2 3.1-1.9 6.4-3 9.9-3.1zM52 186v173.2l62-5.7L52 186zm408 0l-61.9 167.5 61.9 5.7V186zm-91.9.6c-1.6 9.7-3.6 22.5-6.2 38.2l19.6-8.8-8.2-17.9-5.2-11.5zm-219.7 4.1c-1.5.1-2.9.4-4.3 1.1-3 1.4-5.1 3.5-6.5 6.5-1.6 3.4-2.1 6.5-1.2 9.6.9 3 2.7 5.1 5.4 6.4 2.8 1.3 5.7 1.3 8.5 0s5.1-3.6 6.8-7c1.4-2.9 1.7-5.9 1-9-.8-3.1-2.6-5.3-5.4-6.6-1.4-.7-2.9-1-4.3-1zm103.2 47.7h15.6v84.2h-15.6v-70.2c-8.8 5.8-15.3 9.6-19.4 11.2l-6.3 2.8v-14l6.3-2.8c4.1-1.8 10.6-5.4 19.4-11.2zm201.7 6.2h.5c3.6.3 5.7 7 4.7 11.1-.1 18.6 1.1 39.2-9.7 55.3-.9 1.2-2.2 1.9-3.7 2.5-5.8-4.1-3-11.3 1.2-15.5 1 7.3 5.5-2.9 6.6-5.6 1.3-3.2 3.6-17.7-1-10.2.7 4-6.8 13.1-9.3 8.1-5-14.4 0-30.5 7-43.5 1.3-1.4 2.5-2.1 3.7-2.2zm-393.3.9c1 .1 1 1 2 3.6v61.1c-7-7-3-17.4-4-26.4-1-7.6 2-16.3-1-23.2-5-1.7-6-17-3-12.7 4 4.8 4-2.7 6-2.4zm390.9 10.6c-1 0-2 1-2.8 3.7-1.6 5.9-3.3 13.4-.7 19.3 5.1-2 5.4-9.6 6.6-14.5 1.2-3.3-.9-8.4-3.1-8.5zM75 268.2c4-.5 7 7.2 9 10.8 3.28 12.7 4.21 13.9 3 16.8-5-3.7-4.87-7.4-5.36-8.9-1-3-1.64-5.3-3.64-8.4-3.34 2.8-3 9.1-3 13.4 0-1.6 1-2.3 4-.7 7 12.6 12 29.1 7 43.5l-2 1.1c-11-5.8-12-19.4-14-30-1-12.3-1-24.7 2-36.7 1-.6 2-.9 3-.9zm358.2 4.8c4.5.3.8 35.2.8 55l-4.4 6.7v-42.3c-4.6 7.5-9.1 9.1-6.1-.9 4.9-13.4 7.9-18.6 9.7-18.5zM77 299.2c-4 4.7-2 12.8-1 18.4 2 5.5 7 10.2 6 1.6 0-5.7 1-11.8-3-16.4 0-.6-1-1.9-2-3.6zm66 69.4l113 123.1 112.8-123.1H143zm-21 .3l-54 4.9 64 41.1c-2-2.7-5-5.7-7-8.8-5-6.9-10-13.6-19-16.6-9-6.5-4-5.3 3-2.6-1-1.8-1-2.6 0-2.6 2-.2 9 4.2 10 6.3l25 31.6 65 41.7-87-95zm268.2 0l-42.4 46.3c6.4-3.1 11.3-8.5 17-12.4 2.4-1.4 3.7-1.9 4.3-1.9 2.1 0-5.4 7.1-7.7 10.3-9.4 9.8-16 23-28.6 29.1l18.9-24.5c-2.3 1.3-6 3.2-8.2 4.1l-40.3 44 74.5-47.6c5.4-6.7 1.9-5.6-5.7-.9l-11.4 6c11.4-13.7 26.8-23.6 40-35.6 3.2-1.5 9.5-5.6 11-5.7.8-.1.2 1-2.8 4.2l-12.6 16c10-7.6.9 3.9-4.5 5.5-.7 1-1.4 2-2.2 2.9l54.5-34.9-53.8-4.9zm-158.3 16.7c1.4 0 2.7.1 4.1.2v43.4h-13v-30c-5-1.4-11 1.7-16-.3-4-2.9 1-6.8 5-5.9 3-.1 7 .2 9-3.2 3.4-3.1 7-4.2 10.9-4.2zm33.1.7s1 .1 1 .2c4 .8 7 .3 10 .4h25.6c1.5 3 .8 7.8-3.3 7.9-3.9.5-7.8-.4-11.7.2-4.7.2-9.6-1.8-14.6.4-3 1.7-4 8.5 1 6.1 4-1.1 7.3-1.8 10.8-.9 7 1.1 15 2.9 19.1 9.2 2.1 3.1 2.7 7.3.7 10.7-3.6 6.5-11.6 8.4-18.3 9.7-2.4.4-4.7 1.4-7.3 1.2-7-.6-15-1.1-20-7.1-3-2.5-3-7.1 2-6.7 3-.1 8-.4 10 3.5 3 3.7 9 3 13 2 3.6-.5 7.5-2.6 7.6-6.7.6-4.2-3.1-7.2-6.9-7.8-5.7-2.3-11.7 1.4-17.7 1.8-3 1.1-9 .5-9-4.4 1-4.2 3-8.1 3-12.5 0-3 2-7 5-7.2zm133.5 5c-.2-.2-7 5.8-9.9 8.1l-15.8 13.1c8.6-4.4 16.5-9.6 22.3-17.4 2.6-2.6 3.5-3.7 3.4-3.8zM151 405.5c3 0 8 4.6 10 7l26 31.1c-8-2.1-13-7.1-18-13.7-6-7.3-11-16.6-21-19.6-9-5-5-6.4 2-2.2 0-1.9 0-2.6 1-2.6z"/>
</svg>`,
        hero: `<svg viewBox="0 0 512 512" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
  <path d="M387.366,317.875c0,0,0.014-0.014,0.027-0.014l-0.04,0.014H387.366z"/>
  <path d="M401.674,301.43c0,0,0,0.027-0.013,0.034l0.027-0.054C401.688,301.417,401.688,301.423,401.674,301.43z"/>
  <path d="M395.683,246.881l0.23,0.237l-0.257-0.271C395.669,246.861,395.683,246.867,395.683,246.881z"/>
  <path d="M383.565,264.219c-0.771-1.095-1.677-1.941-3.002-2.684c-1.745-0.98-4.382-1.758-8.317-1.941v-0.007c-7.763-0.054-15.133-2.272-22.22-5.632c-7.14-3.415-14.065-7.993-21.057-12.963c-13.956-9.96-28.238-21.503-44.169-28.996c-10.616-5.004-21.882-8.236-34.432-8.236c-18.771,0-34.838,7.29-49.85,16.872c-15.011,9.554-28.684,21.327-42.708,29.549c-9.291,5.41-18.907,9.332-29.307,9.406v0.007c-2.934,0.142-5.166,0.608-6.829,1.251c-1.664,0.649-2.759,1.426-3.651,2.36c-1.758,1.819-2.881,4.788-3.422,9.189c-0.352,2.908-0.446,6.316-0.446,10.056c-0.014,5.477,0.446,9.812,1.595,12.868c1.19,3.064,2.732,5.031,6.114,6.938c1.46,0.805,3.353,1.528,5.626,2.164l2.299-0.805c0.176,0.501,0.379,0.988,0.555,1.481l1.271,0.271c11.793,2.143,35.013,4.233,58.383,5.741c23.397,1.514,47.158,2.482,60.371,2.482c13.227,0,36.988-0.967,60.385-2.482c23.383-1.508,46.59-3.598,58.396-5.741c0.46-0.082,0.825-0.197,1.271-0.285c0.176-0.493,0.379-0.974,0.542-1.467l2.326,0.818c4.03-1.122,6.694-2.57,8.426-4.145c2.259-2.063,3.462-4.524,4.22-8.452c0.473-2.59,0.663-5.741,0.663-9.386c0-4.206-0.108-8-0.596-11.117C385.54,268.202,384.701,265.848,383.565,264.219z M131.761,290.063c-4.666,0-8.452-3.787-8.452-8.46c0-4.679,3.787-8.466,8.452-8.466c4.68,0,8.466,3.786,8.466,8.466C140.227,286.277,136.441,290.063,131.761,290.063z M172.536,296.352c-6.6-0.203-12.888-0.507-18.636-0.913v-29.591c6.451-2.846,12.591-6.504,18.636-10.562V296.352z M216.097,297.217c-5.896-0.047-12.144-0.108-18.582-0.257v-59.594c6.032-4.267,12.186-8.324,18.582-11.779V297.217z M259.699,297.319h-18.636v-80.617c3.056-0.508,6.154-0.758,9.291-0.758c3.205,0,6.302,0.25,9.345,0.758V297.319z M303.259,296.961c-6.45,0.149-12.739,0.21-18.636,0.257v-71.63c6.397,3.401,12.591,7.512,18.636,11.779V296.961z M346.82,295.439c-5.747,0.406-12.036,0.71-18.582,0.913v-41.066c6.032,4.058,12.186,7.716,18.582,10.562V295.439z M368.987,290.063c-4.666,0-8.453-3.787-8.453-8.46c0-4.679,3.787-8.466,8.453-8.466s8.452,3.786,8.452,8.466C377.439,286.277,373.652,290.063,368.987,290.063z"/>
  <path d="M189.333,325.401c-20.746-1.352-41.154-3.103-54.461-5.064c5.342,11.637,11.942,22.47,18.609,32.89c8.993,14.092,18.135,27.42,24.167,41.356l-9.318,4.024c-2.489-5.782-5.707-11.651-9.318-17.67c-0.284,5.937-0.893,12.28-2.015,18.562c-1.65,9.108-4.274,18-8.723,25.114c-0.69,1.095-1.447,2.13-2.232,3.144c2.036,2.408,6.316,6.174,12.293,10.17c5.992,4.023,13.714,8.364,22.843,12.354c18.257,7.972,42.114,14.512,69.175,14.512h0.852c40.586,0.02,74.017-14.762,92.018-26.866c5.653-3.767,9.71-7.296,11.861-9.717c-0.933-1.136-1.812-2.34-2.61-3.598c-2.975-4.74-5.112-10.278-6.708-16.128c-2.435-8.973-3.611-18.724-4.044-27.534c-3.597,6.011-6.816,11.881-9.304,17.656l-9.318-4.024c6.031-13.937,15.174-27.264,24.167-41.356c6.667-10.42,13.267-21.253,18.609-32.89c-13.294,1.961-33.716,3.719-54.475,5.064c-20.895,1.352-41.924,2.259-55.949,2.448v113.582h-10.157V327.849C231.271,327.66,210.241,326.754,189.333,325.401z"/>
  <path d="M364.551,439.713c-0.582-0.244-1.082-0.588-1.623-0.872c-3.056,3.469-7.709,7.418-14.052,11.679c-6.518,4.374-14.754,8.993-24.424,13.226c-19.367,8.446-44.535,15.35-73.247,15.357h-0.852c-43.088-0.027-78.128-15.485-97.671-28.583c-6.505-4.376-11.279-8.433-14.322-11.976c-0.704,0.412-1.393,0.844-2.151,1.169c-5.869,2.482-10.13,5.389-12.739,8.284c-2.624,2.928-3.665,5.639-3.692,8.568c0,3.245,1.447,7.262,5.355,11.935c3.882,4.652,10.144,9.784,18.974,14.951c14.024,8.25,34.635,15.506,54.692,20.563c20.029,5.078,39.707,8,51.567,7.986c11.874,0.013,31.538-2.908,51.581-7.986c20.056-5.058,40.666-12.314,54.691-20.563c8.831-5.166,15.093-10.306,18.988-14.951c3.895-4.68,5.342-8.69,5.342-11.935c-0.027-2.928-1.068-5.64-3.679-8.568C374.667,445.103,370.407,442.195,364.551,439.713z"/>
  <path d="M196.299,96.535c-1.623-1.535-3.462-3.428-5.315-5.504c-1.042,2.204-1.934,5.355-1.921,8.749c0,3.023,0.65,6.208,2.408,9.42c1.217,2.204,3.029,4.45,5.599,6.647c14.606-6.836,30.808-10.968,48.226-11.637V20.306h10.157v83.903c17.419,0.67,33.621,4.801,48.227,11.637c2.556-2.197,4.382-4.443,5.599-6.647c1.758-3.212,2.407-6.397,2.407-9.42c0.027-3.36-0.879-6.518-1.893-8.716c-1.867,2.096-3.719,3.922-5.342,5.471c-2.069,1.968-3.8,3.59-4.49,4.429l-7.804-6.492c1.501-1.771,3.286-3.367,5.288-5.281c1.988-1.886,4.125-4.016,6.086-6.39c3.922-4.808,6.951-10.36,6.951-17.203c-0.014-4.382-1.285-9.602-4.841-15.803c-1.488,2.813-3.408,5.558-5.829,8.189l-7.506-6.83c5.003-5.545,6.775-10.643,6.802-15.539c0-4.233-1.42-8.52-4.125-12.706s-6.681-8.189-11.441-11.624C274.034,4.402,261.376-0.034,250.367,0c-8.236-0.014-17.432,2.448-25.52,6.62c-8.088,4.146-15.039,10.014-19.083,16.29c-2.704,4.186-4.125,8.472-4.125,12.706c0.014,4.896,1.799,9.994,6.803,15.539l-7.52,6.83c-2.394-2.63-4.341-5.376-5.815-8.189c-3.557,6.201-4.842,11.421-4.842,15.803c0,5.159,1.704,9.514,4.219,13.457c2.502,3.928,5.842,7.303,8.818,10.136c1.988,1.914,3.786,3.51,5.287,5.281l-7.803,6.492C200.099,100.125,198.367,98.503,196.299,96.535z"/>
  <path d="M128.164,245.285h0.108c5.883,0,11.821-1.677,18.082-4.659c6.248-2.969,12.739-7.222,19.528-12.064c13.619-9.677,28.346-21.692,45.725-29.909c11.59-5.463,24.438-9.21,38.76-9.21c21.517,0,39.572,8.412,55.327,18.467c15.756,10.076,29.496,21.862,42.37,29.34c8.574,5.018,16.594,8.047,24.425,8.034h0.108l0.122,0.007c0.77,0.034,1.433,0.176,2.164,0.25c-2.083-31.707-14.795-62.583-35.311-85.884c-21.99-24.966-52.676-41.269-89.205-41.282c-36.515,0.013-67.214,16.316-89.204,41.282c-20.488,23.282-33.188,54.143-35.284,85.83c0.717-0.068,1.407-0.162,2.151-0.196L128.164,245.285z"/>
</svg>`,
        enemy: `<svg viewBox="0 0 32 32" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
  <path d="M20.624 5.42c-0.707-0.58-1.541-1.011-2.519-1.267-0.187-0.231-0.411-0.43-0.663-0.588-0.623-1.288-1.424-2.596-1.424-2.596s-0.8 1.307-1.423 2.595c-0.215 0.135-0.41 0.299-0.578 0.487-1.162 0.232-2.132 0.695-2.939 1.351-6.017-0.988-11.433 5.057-9.516 11.428 0.771 2.562 4.546 4.208 6.506 3.134-3.694 0.006-4.792-4.007-3.65-6.791 0.73-1.779 2.011-2.784 3.783-2.794-0.587 2.2-0.797 4.817-0.797 7.625h4.405c-0.105 0.75-0.151 1.563-0.157 2.413-0.102 0.919 0.559 4.765 0.559 4.765s1.887-3.121 2.23-4.136c0.384 1.498 1.424 4.285 1.424 4.285s1.030-2.76 1.418-4.263c0.362 1.037 2.222 4.114 2.222 4.114s0.682-3.963 0.554-4.805c-0.007-0.836-0.053-1.635-0.157-2.374h4.372c0-2.808-0.21-5.425-0.796-7.624 1.841-0.041 3.168 0.969 3.917 2.793 1.142 2.784 0.045 6.797-3.65 6.791 1.96 1.073 5.735-0.572 6.506-3.134 1.929-6.41-3.566-12.49-9.627-11.409zM11.99 11.264c0.555 0.59 1.343 1.012 2.444 1.201 0 0.002 0 0.003 0 0.005 0 0.839-0.68 1.519-1.519 1.519s-1.519-0.68-1.519-1.519c0-0.491 0.233-0.928 0.595-1.205zM20.553 12.469c-0 0.839-0.68 1.519-1.519 1.519s-1.519-0.68-1.519-1.519c0-0.019 0.001-0.038 0.001-0.057 1.026-0.222 1.759-0.663 2.274-1.26 0.11 0.063 0.212 0.14 0.302 0.228l0.001 0.003c0.001-0 0.001-0.001 0.002-0.001 0.283 0.276 0.459 0.661 0.459 1.087zM7.131 28.033c-0.737 2.75 3.066 3.588 3.758 1.007 0.278-1.038-0.252-6.577-0.252-6.577s-3.228 4.532-3.506 5.57zM20.954 22.463c0 0-0.529 5.539-0.252 6.577 0.692 2.581 4.495 1.743 3.758-1.007-0.278-1.038-3.506-5.57-3.506-5.57zM17.467 23.943c0 0-1.025 3.982-0.942 4.772 0.207 1.964 3.080 1.79 2.859-0.302-0.083-0.79-1.917-4.47-1.917-4.47zM12.318 28.413c-0.221 2.092 2.652 2.266 2.859 0.302 0.083-0.79-0.942-4.772-0.942-4.772s-1.834 3.68-1.917 4.47z"/>
</svg>`,
        potion: `<svg viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
  <path fill-rule="evenodd" clip-rule="evenodd" d="M10 9V6.82446C10.2515 6.93762 10.5295 7 10.8198 7H13.1802C13.4705 7 13.7485 6.93762 14 6.82446V9C14 9.55228 14.4477 10 15 10C18.3137 10 21 12.6863 21 16C21 19.3137 18.3137 22 15 22H9C5.68629 22 3 19.3137 3 16C3 12.6863 5.68629 10 9 10C9.55228 10 10 9.55228 10 9ZM16 2H15.7809C15.7832 0.921269 14.9075 0 13.7802 0H10.2198C9.09245 0 8.21684 0.92127 8.21913 2H8C7.44772 2 7 2.44772 7 3C7 3.55228 7.44772 4 8 4V8.06189C4.05369 8.55399 1 11.9204 1 16C1 20.4183 4.58172 24 9 24H15C19.4183 24 23 20.4183 23 16C23 11.9204 19.9463 8.55399 16 8.06189V4C16.5523 4 17 3.55228 17 3C17 2.44772 16.5523 2 16 2ZM10.2198 2L10.8198 5L13.1802 5L13.7802 2H10.2198ZM15.5 14.4999C14.4117 14.5668 13.3536 14.1606 12.2932 13.7535C11.371 13.3995 10.4471 13.0448 9.5 13C7.89583 12.9239 6.20559 13.609 5.17036 14.129C4.51271 14.4593 4.03027 15.0786 4.00339 15.814C4.00114 15.8757 4 15.9377 4 15.9999C4 18.7613 6.23858 20.9999 9 20.9999H15C17.7614 20.9999 20 18.7613 20 15.9999C20 15.6347 19.9608 15.2786 19.8865 14.9357C19.6683 13.9292 18.4938 13.6409 17.5283 13.9993C16.8742 14.2421 16.1303 14.4612 15.5 14.4999ZM9 16.5C9 17.3284 8.32843 18 7.5 18C6.67157 18 6 17.3284 6 16.5C6 15.6715 6.67157 15 7.5 15C8.32843 15 9 15.6715 9 16.5ZM12.5 19.75C13.1904 19.75 13.75 19.1903 13.75 18.5C13.75 17.8096 13.1904 17.25 12.5 17.25C11.8096 17.25 11.25 17.8096 11.25 18.5C11.25 19.1903 11.8096 19.75 12.5 19.75ZM17 17C17 17.5522 16.5523 18 16 18C15.4477 18 15 17.5522 15 17C15 16.4477 15.4477 16 16 16C16.5523 16 17 16.4477 17 17Z"/>
</svg>`,
        initiative: `<svg viewBox="-24.5 0 155 155" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
  <path d="M29.9801 99.6718C25.9694 111.25 21.7567 122.977 17.6831 134.318C16.1595 138.559 14.6381 142.8 13.1188 147.041C12.2587 149.449 11.5771 152.176 14.4031 154.081C15.1805 154.645 16.112 154.958 17.0735 154.98C18.8423 154.98 20.3793 153.811 21.708 152.537C24.2358 150.122 26.8472 147.704 29.375 145.368C35.0139 140.152 40.8474 134.759 46.1123 129.006C55.2428 119.03 64.1887 108.588 72.8407 98.4899C76.2831 94.4728 79.7325 90.4616 83.1865 86.4567C88.7696 80.0075 94.4195 73.4296 99.8834 67.0675L103.327 63.0589C105.009 61.1014 106.399 58.8998 105.19 56.0614C104.011 53.2953 101.56 52.8568 99.0901 52.7948C93.9814 52.6682 88.874 52.5313 83.7666 52.3841C80.2342 52.285 76.7018 52.188 73.17 52.0933C72.2276 52.0681 71.2943 52.0747 70.2164 52.0812L69.4146 52.0856C69.5118 51.8027 69.6039 51.5303 69.6933 51.2687C70.0368 50.2567 70.3344 49.3827 70.6656 48.5348C72.2146 44.55 73.7663 40.5662 75.3218 36.5831C78.3681 28.7685 81.522 20.6897 84.5852 12.7285C85.466 10.4306 86.1712 8.06956 86.6943 5.66569C86.8472 5.13061 86.8861 4.56956 86.8077 4.01871C86.7293 3.46786 86.5368 2.93951 86.2412 2.46759C85.9249 2.05423 85.523 1.71279 85.0642 1.46612C84.6046 1.21945 84.0978 1.07315 83.5773 1.03701C82.9454 0.953699 82.3141 0.855545 81.6828 0.757379C80.0157 0.454569 78.3286 0.280517 76.635 0.236816H76.5799C68.8579 0.262649 61.0078 0.350502 53.4173 0.434461C48.9399 0.483974 44.4627 0.529837 39.9853 0.572031C33.905 0.623052 33.0728 1.22759 31.0745 7.03881C30.8723 7.62845 30.6489 8.25484 30.3903 8.84707L25.4058 20.2558C20.183 32.2244 14.7824 44.5992 9.42547 56.7499C8.38455 59.1143 7.24732 61.4897 6.14741 63.7863C4.90102 66.3894 3.61237 69.0819 2.45284 71.7808C0.864876 75.4705 0.631518 77.8743 1.69772 79.5773C2.76392 81.2804 5.01261 82.1167 9.00586 82.3169L13.8929 82.5585C17.0247 82.7122 20.1563 82.8704 23.2873 83.0332C25.909 83.1733 28.529 83.3316 31.3011 83.4982L34.4773 83.6881C34.043 85.2781 33.6348 86.8591 33.2329 88.3942C32.2114 92.317 31.2453 96.0222 29.9801 99.6718ZM60.2298 53.2249C59.7923 55.4137 59.9792 56.9261 60.8134 57.986C61.6475 59.0458 63.0477 59.567 65.2359 59.6574C71.3792 59.91 77.6254 60.0623 83.6668 60.2102C86.0623 60.2683 88.4579 60.3288 90.8534 60.3917H91.0096C91.3441 60.3919 91.6772 60.4168 92.0071 60.466C85.4544 68.0221 79.0234 75.8554 72.798 83.4413C59.2444 99.9572 45.2476 117.012 29.3466 131.918C29.974 130.027 30.6064 128.137 31.2441 126.249C33.3182 120.083 35.4576 113.708 37.3028 107.355C38.8616 101.984 40.1034 96.4342 41.3044 91.0673C41.771 88.9832 42.2374 86.8998 42.7216 84.8215C42.9358 83.8011 43.041 82.7606 43.0349 81.7176C43.0997 76.6155 41.4011 74.866 36.2989 74.7904C30.7592 74.7084 25.1277 74.6832 19.6813 74.6612C17.4543 74.6516 15.2275 74.6406 13.0005 74.6283C12.55 74.6283 12.1006 74.6251 11.6029 74.6251H9.61791C11.0944 71.4779 12.5731 68.3463 14.0531 65.2301C23.3488 45.5924 32.1322 27.0376 38.3966 7.27964H78.3053C78.2515 7.45078 78.2009 7.61224 78.1536 7.76466C77.9397 8.45376 77.7855 8.9517 77.5897 9.42639C75.8656 13.6213 74.1383 17.8147 72.4084 22.0066C69.1029 30.0285 65.6813 38.323 62.3502 46.496C61.4296 48.6664 60.7194 50.9197 60.2298 53.2249Z"/>
</svg>`,
        loading: `<svg viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
  <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" opacity="0.25"/>
  <path d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" fill="currentColor"/>
</svg>`,
        logout: `<svg viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
  <path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.59L17 17l5-5-5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/>
</svg>`,
        settings: `<svg viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
  <path d="M19.14,12.94c0.04-0.3,0.06-0.61,0.06-0.94c0-0.32-0.02-0.64-0.07-0.94l2.03-1.58c0.18-0.14,0.23-0.41,0.12-0.61 l-1.92-3.32c-0.12-0.22-0.37-0.29-0.59-0.22l-2.39,0.96c-0.5-0.38-1.03-0.7-1.62-0.94L14.4,2.81c-0.04-0.24-0.24-0.41-0.48-0.41 h-3.84c-0.24,0-0.43,0.17-0.47,0.41L9.25,5.35C8.66,5.59,8.12,5.92,7.63,6.29L5.24,5.33c-0.22-0.08-0.47,0-0.59,0.22L2.74,8.87 C2.62,9.08,2.66,9.34,2.86,9.48l2.03,1.58C4.84,11.36,4.82,11.69,4.82,12s0.02,0.64,0.07,0.94l-2.03,1.58 c-0.18,0.14-0.23,0.41-0.12,0.61l1.92,3.32c0.12,0.22,0.37,0.29,0.59,0.22l2.39-0.96c0.5,0.38,1.03,0.7,1.62,0.94l0.36,2.54 c0.05,0.24,0.24,0.41,0.48,0.41h3.84c0.24,0,0.44-0.17,0.47-0.41l0.36-2.54c0.59-0.24,1.13-0.56,1.62-0.94l2.39,0.96 c0.22,0.08,0.47,0,0.59-0.22l1.92-3.32c0.12-0.22,0.07-0.47-0.12-0.61L19.14,12.94z M12,15.6c-1.98,0-3.6-1.62-3.6-3.6 s1.62-3.6,3.6-3.6s3.6,1.62,3.6,3.6S13.98,15.6,12,15.6z"/>
</svg>`,
        stats: `<svg viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
  <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/>
</svg>`,
        skull: `<svg viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
  <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-1-13h2v6h-2zm0 8h2v2h-2z"/>
</svg>`,
        'spell': `<svg viewBox="0 0 32 32" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
  <path d="M30.527 5.467c-0.147-0.118-0.33-0.196-0.531-0.216l-0.004-0-3.242 0.033v-3.283c-0-0.208-0.085-0.395-0.221-0.531v0c-0.134-0.135-0.319-0.219-0.524-0.219-0.003 0-0.007 0-0.010 0h0c-0.296-0.024-0.64-0.038-0.988-0.038-3.501 0-6.678 1.386-9.012 3.64l0.004-0.004c-2.33-2.25-5.507-3.637-9.008-3.637-0.348 0-0.693 0.014-1.034 0.041l0.045-0.003c-0.206 0.006-0.393 0.088-0.533 0.219l0-0c-0.136 0.136-0.221 0.323-0.221 0.531 0 0 0 0 0 0v-0 3.283l-3.242-0.033c-0.003-0-0.007-0-0.011-0-0.205 0-0.39 0.083-0.524 0.217v0c-0.137 0.136-0.223 0.324-0.223 0.533 0 0 0 0 0 0v0 24c0 0.414 0.336 0.749 0.75 0.749 0.13 0 0.252-0.033 0.359-0.091l-0.004 0.002c1.904-1.129 4.196-1.797 6.645-1.797 2.443 0 4.731 0.665 6.693 1.823l-0.061-0.034c0.058 0.034 0.125 0.061 0.196 0.077l0.005 0.001c0.028 0.007 0.060 0.013 0.094 0.016l0.002 0h0.004l0.069 0.004 0.013-0.004c0.13-0.001 0.253-0.036 0.359-0.096l-0.004 0.002c1.901-1.124 4.189-1.788 6.632-1.788 2.449 0 4.742 0.667 6.707 1.83l-0.061-0.034c0.102 0.058 0.224 0.092 0.354 0.092 0.142 0 0.275-0.041 0.387-0.111l-0.003 0.002c0.22-0.133 0.365-0.371 0.365-0.642 0-0 0-0 0-0v0-24c0-0 0-0 0-0 0-0.208-0.085-0.397-0.223-0.533l-0-0zM6.75 2.766c0.166-0.009 0.36-0.014 0.556-0.014 3.142 0 5.969 1.346 7.937 3.494l0.007 0.008v21.837c-2.233-1.764-5.090-2.83-8.195-2.83-0.107 0-0.214 0.001-0.321 0.004l0.016-0zM2.75 28.793v-22.035l2.5 0.025v19.217c0 0.413 0.335 0.749 0.748 0.75h0c0.088-0.002 0.192-0.003 0.296-0.003 1.834 0 3.596 0.308 5.238 0.876l-0.112-0.034c-0.729-0.13-1.569-0.204-2.426-0.204-2.269 0-4.416 0.519-6.33 1.445l0.087-0.038zM16.75 6.253c1.975-2.155 4.803-3.502 7.945-3.502 0.195 0 0.39 0.005 0.582 0.015l-0.027-0.001v22.498c-0.091-0.002-0.198-0.003-0.305-0.003-3.105 0-5.962 1.066-8.223 2.851l0.028-0.021zM29.25 28.793c-1.831-0.877-3.978-1.397-6.244-1.414l-0.006-0c-0.855 0-1.691 0.077-2.503 0.224l0.085-0.013c1.529-0.534 3.291-0.843 5.126-0.843 0.103 0 0.207 0.001 0.309 0.003l-0.015-0c0.413-0.001 0.748-0.336 0.748-0.75 0-0 0-0 0-0v0-19.217l2.5-0.025z"/>
</svg>`,
        description: `<svg viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
  <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z"/>
</svg>`
    };
    
    // Делаем объект icons глобальным для использования в icons.js
    window.icons = icons;
    
    // Обработчики для новых генераторов
    document.addEventListener('DOMContentLoaded', function() {
        // Генератор квестов
        const questForm = document.getElementById('questForm');
        if (questForm) {
            questForm.addEventListener('submit', function(e) {
                e.preventDefault();
                generateQuest();
            });
        }
        
        // Генератор лора
        const loreForm = document.getElementById('loreForm');
        if (loreForm) {
            loreForm.addEventListener('submit', function(e) {
                e.preventDefault();
                generateLore();
            });
        }
        
        // Генератор заклинаний
        const spellForm = document.getElementById('spellForm');
        if (spellForm) {
            spellForm.addEventListener('submit', function(e) {
                e.preventDefault();
                generateSpell();
            });
        }
        
        // Генератор монстров
        const monsterForm = document.getElementById('monsterForm');
        if (monsterForm) {
            monsterForm.addEventListener('submit', function(e) {
                e.preventDefault();
                generateMonster();
            });
        }
    });
    
    // Функция генерации квеста
    function generateQuest() {
        const form = document.getElementById('questForm');
        const formData = new FormData(form);
        const resultDiv = document.getElementById('questResult');
        const submitBtn = form.querySelector('button[type="submit"]');
        
        submitBtn.innerHTML = '<span class="svg-icon" data-icon="loading" style="width: 20px; height: 20px;"></span> Создание...';
        submitBtn.disabled = true;
        resultDiv.innerHTML = '<div class="loading">Создание квеста...</div>';
        
        const params = new URLSearchParams();
        params.append('action', 'generate_quest');
        params.append('type', formData.get('quest_type'));
        params.append('difficulty', formData.get('difficulty'));
        params.append('theme', formData.get('theme'));
        
        fetch('api/external-services.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: params.toString()
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.quest) {
                const quest = data.quest;
                resultDiv.innerHTML = `
                    <div class="quest-result" style="background: var(--bg-primary); border-radius: var(--radius-lg); padding: var(--space-6); border-left: 4px solid var(--accent-primary);">
                        <div class="quest-header" style="display: flex; align-items: center; margin-bottom: var(--space-4);">
                            <span class="svg-icon" data-icon="dice" style="width: 32px; height: 32px; margin-right: var(--space-3); color: var(--accent-primary);"></span>
                            <h3 style="margin: 0; color: var(--text-primary);">${quest.type} квест</h3>
                            <span style="margin-left: auto; background: var(--accent-primary); color: white; padding: var(--space-1) var(--space-3); border-radius: var(--radius-sm); font-size: var(--text-sm);">${quest.difficulty}</span>
                        </div>
                        <div class="quest-content" style="color: var(--text-primary); line-height: var(--line-height-relaxed);">
                            ${quest.description.replace(/\n/g, '<br>')}
                        </div>
                        <div class="quest-meta" style="margin-top: var(--space-4); padding-top: var(--space-4); border-top: 1px solid var(--border-primary); font-size: var(--text-sm); color: var(--text-secondary);">
                            <strong>Тема:</strong> ${quest.theme} | <strong>Сложность:</strong> ${quest.difficulty} | <strong>Источник:</strong> ${data.source}
                        </div>
                    </div>
                `;
            } else {
                resultDiv.innerHTML = `<div class="error">Ошибка: ${data.error || 'Неизвестная ошибка'}</div>`;
            }
        })
        .catch(error => {
            console.error('Quest generation error:', error);
            resultDiv.innerHTML = '<div class="error">Ошибка при создании квеста</div>';
        })
        .finally(() => {
            submitBtn.innerHTML = '<span class="svg-icon" data-icon="dice" style="width: 20px; height: 20px;"></span> Создать квест';
            submitBtn.disabled = false;
        });
    }
    
    // Функция генерации лора
    function generateLore() {
        const form = document.getElementById('loreForm');
        const formData = new FormData(form);
        const resultDiv = document.getElementById('loreResult');
        const submitBtn = form.querySelector('button[type="submit"]');
        
        submitBtn.innerHTML = '<span class="svg-icon" data-icon="loading" style="width: 20px; height: 20px;"></span> Создание...';
        submitBtn.disabled = true;
        resultDiv.innerHTML = '<div class="loading">Создание лора...</div>';
        
        const params = new URLSearchParams();
        params.append('action', 'generate_lore');
        params.append('type', formData.get('lore_type'));
        params.append('setting', formData.get('setting'));
        params.append('mood', formData.get('mood'));
        
        fetch('api/external-services.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: params.toString()
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.lore) {
                const lore = data.lore;
                resultDiv.innerHTML = `
                    <div class="lore-result" style="background: var(--bg-primary); border-radius: var(--radius-lg); padding: var(--space-6); border-left: 4px solid var(--accent-primary);">
                        <div class="lore-header" style="display: flex; align-items: center; margin-bottom: var(--space-4);">
                            <span class="svg-icon" data-icon="description" style="width: 32px; height: 32px; margin-right: var(--space-3); color: var(--accent-primary);"></span>
                            <h3 style="margin: 0; color: var(--text-primary);">${lore.type}</h3>
                            <span style="margin-left: auto; background: var(--accent-primary); color: white; padding: var(--space-1) var(--space-3); border-radius: var(--radius-sm); font-size: var(--text-sm);">${lore.setting}</span>
                        </div>
                        <div class="lore-content" style="color: var(--text-primary); line-height: var(--line-height-relaxed);">
                            ${lore.description.replace(/\n/g, '<br>')}
                        </div>
                        <div class="lore-meta" style="margin-top: var(--space-4); padding-top: var(--space-4); border-top: 1px solid var(--border-primary); font-size: var(--text-sm); color: var(--text-secondary);">
                            <strong>Тип:</strong> ${lore.type} | <strong>Сеттинг:</strong> ${lore.setting} | <strong>Настроение:</strong> ${lore.mood} | <strong>Источник:</strong> ${data.source}
                        </div>
                    </div>
                `;
            } else {
                resultDiv.innerHTML = `<div class="error">Ошибка: ${data.error || 'Неизвестная ошибка'}</div>`;
            }
        })
        .catch(error => {
            console.error('Lore generation error:', error);
            resultDiv.innerHTML = '<div class="error">Ошибка при создании лора</div>';
        })
        .finally(() => {
            submitBtn.innerHTML = '<span class="svg-icon" data-icon="description" style="width: 20px; height: 20px;"></span> Создать лор';
            submitBtn.disabled = false;
        });
    }
    
    // Функция поиска заклинания
    function generateSpell() {
        const form = document.getElementById('spellForm');
        const formData = new FormData(form);
        const spellName = formData.get('spell_name').trim();
        const resultDiv = document.getElementById('spellResult');
        const submitBtn = form.querySelector('button[type="submit"]');
        
        if (!spellName) {
            alert('Введите название заклинания');
            return;
        }
        
        submitBtn.innerHTML = '<span class="svg-icon" data-icon="loading" style="width: 20px; height: 20px;"></span> Поиск...';
        submitBtn.disabled = true;
        resultDiv.innerHTML = '<div class="loading">Поиск заклинания...</div>';
        
        const params = new URLSearchParams();
        params.append('action', 'get_comprehensive_spell');
        params.append('spell', spellName);
        
        fetch('api/dnd-libraries.php?' + params.toString())
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                let html = '<div class="spell-results">';
                
                Object.keys(data.data).forEach(source => {
                    const spellData = data.data[source];
                    html += `
                        <div class="spell-source" style="background: var(--bg-primary); border-radius: var(--radius-lg); padding: var(--space-6); margin-bottom: var(--space-4); border-left: 4px solid var(--accent-primary);">
                            <div class="spell-header" style="display: flex; align-items: center; margin-bottom: var(--space-4);">
                                <span class="svg-icon" data-icon="crystal-ball" style="width: 32px; height: 32px; margin-right: var(--space-3); color: var(--accent-primary);"></span>
                                <h3 style="margin: 0; color: var(--text-primary);">${spellName}</h3>
                                <span style="margin-left: auto; background: var(--accent-primary); color: white; padding: var(--space-1) var(--space-3); border-radius: var(--radius-sm); font-size: var(--text-sm);">${source}</span>
                            </div>
                            <div class="spell-content" style="color: var(--text-primary); line-height: var(--line-height-relaxed);">
                                ${typeof spellData === 'string' ? spellData.replace(/\n/g, '<br>') : JSON.stringify(spellData, null, 2).replace(/\n/g, '<br>')}
                            </div>
                        </div>
                    `;
                });
                
                html += '</div>';
                html += `<div style="margin-top: var(--space-4); padding-top: var(--space-4); border-top: 1px solid var(--border-primary); font-size: var(--text-sm); color: var(--text-secondary);">
                    <strong>Найдено источников:</strong> ${data.total_sources} | <strong>Источники:</strong> ${data.sources_used.join(', ')}
                </div>`;
                
                resultDiv.innerHTML = html;
            } else {
                resultDiv.innerHTML = `<div class="error">Ошибка: ${data.error || 'Заклинание не найдено'}</div>`;
            }
        })
        .catch(error => {
            console.error('Spell search error:', error);
            resultDiv.innerHTML = '<div class="error">Ошибка при поиске заклинания</div>';
        })
        .finally(() => {
            submitBtn.innerHTML = '<span class="svg-icon" data-icon="crystal-ball" style="width: 20px; height: 20px;"></span> Найти заклинание';
            submitBtn.disabled = false;
        });
    }
    
    // Функция поиска монстра
    function generateMonster() {
        const form = document.getElementById('monsterForm');
        const formData = new FormData(form);
        const monsterName = formData.get('monster_name').trim();
        const resultDiv = document.getElementById('monsterResult');
        const submitBtn = form.querySelector('button[type="submit"]');
        
        if (!monsterName) {
            alert('Введите название монстра');
            return;
        }
        
        submitBtn.innerHTML = '<span class="svg-icon" data-icon="loading" style="width: 20px; height: 20px;"></span> Поиск...';
        submitBtn.disabled = true;
        resultDiv.innerHTML = '<div class="loading">Поиск монстра...</div>';
        
        const params = new URLSearchParams();
        params.append('action', 'get_comprehensive_monster');
        params.append('monster', monsterName);
        
        fetch('api/dnd-libraries.php?' + params.toString())
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                let html = '<div class="monster-results">';
                
                Object.keys(data.data).forEach(source => {
                    const monsterData = data.data[source];
                    html += `
                        <div class="monster-source" style="background: var(--bg-primary); border-radius: var(--radius-lg); padding: var(--space-6); margin-bottom: var(--space-4); border-left: 4px solid var(--accent-primary);">
                            <div class="monster-header" style="display: flex; align-items: center; margin-bottom: var(--space-4);">
                                <span class="svg-icon" data-icon="skull" style="width: 32px; height: 32px; margin-right: var(--space-3); color: var(--accent-primary);"></span>
                                <h3 style="margin: 0; color: var(--text-primary);">${monsterName}</h3>
                                <span style="margin-left: auto; background: var(--accent-primary); color: white; padding: var(--space-1) var(--space-3); border-radius: var(--radius-sm); font-size: var(--text-sm);">${source}</span>
                            </div>
                            <div class="monster-content" style="color: var(--text-primary); line-height: var(--line-height-relaxed);">
                                ${typeof monsterData === 'string' ? monsterData.replace(/\n/g, '<br>') : JSON.stringify(monsterData, null, 2).replace(/\n/g, '<br>')}
                            </div>
                        </div>
                    `;
                });
                
                html += '</div>';
                html += `<div style="margin-top: var(--space-4); padding-top: var(--space-4); border-top: 1px solid var(--border-primary); font-size: var(--text-sm); color: var(--text-secondary);">
                    <strong>Найдено источников:</strong> ${data.total_sources} | <strong>Источники:</strong> ${data.sources_used.join(', ')}
                </div>`;
                
                resultDiv.innerHTML = html;
            } else {
                resultDiv.innerHTML = `<div class="error">Ошибка: ${data.error || 'Монстр не найден'}</div>`;
            }
        })
        .catch(error => {
            console.error('Monster search error:', error);
            resultDiv.innerHTML = '<div class="error">Ошибка при поиске монстра</div>';
        })
        .finally(() => {
            submitBtn.innerHTML = '<span class="svg-icon" data-icon="skull" style="width: 20px; height: 20px;"></span> Найти монстра';
            submitBtn.disabled = false;
        });
    }
    
    // Внешние сервисы
    window.externalServices = {
        // Генерация имен персонажей
        generateNames: async function(race = 'human', gender = 'any', count = 1) {
            try {
                const response = await fetch('api/external-services.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=generate_names&race=${race}&gender=${gender}&count=${count}`
                });
                
                const result = await response.json();
                
                if (result.success) {
                    return result.names;
                } else {
                    console.error('Ошибка генерации имен:', result.error);
                    return [];
                }
            } catch (error) {
                console.error('Ошибка запроса генерации имен:', error);
                return [];
            }
        },
        
        // Бросок костей
        rollDice: async function(diceString = '1d20') {
            try {
                const response = await fetch('api/external-services.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=roll_dice&dice=${diceString}`
                });
                
                const result = await response.json();
                
                if (result.success) {
                    return result;
                } else {
                    console.error('Ошибка броска костей:', result.error);
                    return null;
                }
            } catch (error) {
                console.error('Ошибка запроса броска костей:', error);
                return null;
            }
        },
        
        // Получение погоды
        getWeather: async function(location = 'Moscow') {
            try {
                const response = await fetch('api/external-services.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=get_weather&location=${location}`
                });
                
                const result = await response.json();
                
                if (result.success) {
                    return result;
                } else {
                    console.error('Ошибка получения погоды:', result.error);
                    return null;
                }
            } catch (error) {
                console.error('Ошибка запроса погоды:', error);
                return null;
            }
        }
    };
    
    function replaceIcons() {
        const elements = document.querySelectorAll('[data-icon]');
        console.log(`Found ${elements.length} elements with data-icon`);
        
        elements.forEach(element => {
            const iconName = element.getAttribute('data-icon');
            if (icons[iconName]) {
                element.innerHTML = icons[iconName];
                element.removeAttribute('data-icon');
                console.log(`✓ Loaded icon: ${iconName}`);
            } else {
                console.warn(`✗ Icon not found: ${iconName}`);
            }
        });
    }
    
    // Запускаем загрузку иконок сразу
    replaceIcons();
    
    // Дополнительная попытка через небольшую задержку
    setTimeout(replaceIcons, 500);
    
    console.log('Icon loading completed');
})();
</script>

