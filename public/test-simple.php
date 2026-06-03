<?php
/**
 * Простой тест генератора персонажей
 */

// Подключаем конфигурацию
require_once __DIR__ . '/../../config/config.php';

// Подключаем сервисы
require_once __DIR__ . '/../../app/Controllers/EnhancedController.php';
require_once __DIR__ . '/../../app/Services/EnhancedCharacterService.php';

echo "<h1>🎲 Тест генератора персонажей D&D</h1>";

try {
    // Создаем контроллер
    $controller = new EnhancedController();
    
    echo "<h2>✅ Контроллер создан успешно</h2>";
    
    // Тестируем загрузку рас
    $races = $controller->getRaces();
    echo "<h3>📊 Расы загружены: " . count($races) . "</h3>";
    
    // Тестируем загрузку классов
    $classes = $controller->getClasses();
    echo "<h3>⚔️ Классы загружены: " . count($classes) . "</h3>";
    
    // Генерируем тестового персонажа
    echo "<h3>🎲 Генерация тестового персонажа:</h3>";
    
    $character = $controller->generateCharacter([
        'race' => 'human',
        'class' => 'fighter',
        'level' => 1,
        'gender' => 'random',
        'alignment' => 'random'
    ]);
    
    echo "<div style='background: #f0f0f0; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h4>👤 " . htmlspecialchars($character['name']) . "</h4>";
    echo "<p><strong>Раса:</strong> " . htmlspecialchars($character['race']) . "</p>";
    echo "<p><strong>Класс:</strong> " . htmlspecialchars($character['class']) . " (уровень " . $character['level'] . ")</p>";
    echo "<p><strong>Мировоззрение:</strong> " . htmlspecialchars($character['alignment']) . "</p>";
    echo "<p><strong>Хиты:</strong> " . $character['hit_points'] . "</p>";
    echo "<p><strong>КД:</strong> " . $character['armor_class'] . "</p>";
    echo "<p><strong>Скорость:</strong> " . $character['speed'] . " футов</p>";
    echo "</div>";
    
    // Показываем характеристики
    echo "<h4>📊 Характеристики:</h4>";
    echo "<div style='display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin: 10px 0;'>";
    $abilities = ['str', 'dex', 'con', 'int', 'wis', 'cha'];
    $abilityNames = ['СИЛ', 'ЛОВ', 'ТЕЛ', 'ИНТ', 'МДР', 'ХАР'];
    
    foreach ($abilities as $ability) {
        $value = $character['abilities'][$ability];
        $modifier = $character['modifiers'][$ability];
        $name = $abilityNames[array_search($ability, $abilities)];
        
        echo "<div style='background: #e3f2fd; padding: 10px; border-radius: 5px; text-align: center;'>";
        echo "<div style='font-weight: bold; color: #1976d2;'>$name</div>";
        echo "<div style='font-size: 18px; font-weight: bold;'>$value ($modifier)</div>";
        echo "</div>";
    }
    echo "</div>";
    
    // Показываем снаряжение
    if (isset($character['equipment']['items'])) {
        echo "<h4>🎒 Снаряжение:</h4>";
        echo "<ul>";
        foreach ($character['equipment']['items'] as $item) {
            echo "<li>" . htmlspecialchars($item) . "</li>";
        }
        echo "</ul>";
    }
    
    // Показываем заклинания
    if (isset($character['spells']) && !empty($character['spells'])) {
        echo "<h4>🔮 Заклинания:</h4>";
        foreach ($character['spells'] as $level => $spells) {
            if (!empty($spells)) {
                $levelName = ($level === 'cantrips') ? 'Заговоры' : 'Уровень ' . str_replace('level_', '', $level);
                echo "<p><strong>$levelName:</strong> " . implode(', ', $spells) . "</p>";
            }
        }
    }
    
    // Показываем зелья
    if (isset($character['potions']) && !empty($character['potions'])) {
        echo "<h4>🧪 Зелья:</h4>";
        echo "<ul>";
        foreach ($character['potions'] as $potion) {
            echo "<li><strong>" . htmlspecialchars($potion['name']) . ":</strong> " . htmlspecialchars($potion['effect'] ?? $potion['description'] ?? '') . "</li>";
        }
        echo "</ul>";
    }
    
    echo "<h2>✅ Генератор работает корректно!</h2>";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ Ошибка: " . htmlspecialchars($e->getMessage()) . "</h2>";
    echo "<p>Файл: " . $e->getFile() . "</p>";
    echo "<p>Строка: " . $e->getLine() . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "<hr>";
echo "<p><a href='simple-test.html'>🔗 Открыть веб-интерфейс</a></p>";
echo "<p><a href='test-enhanced.html'>🔗 Открыть полный тест</a></p>";
?>
