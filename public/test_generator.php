<?php
/**
 * Веб-тест для проверки улучшенного генератора персонажей
 */

require_once '../app/Services/CharacterService.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Тест генератора персонажей</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .character { border: 1px solid #ccc; margin: 10px 0; padding: 15px; background: #f9f9f9; }
        .character h3 { color: #333; margin-top: 0; }
        .section { margin: 10px 0; }
        .section h4 { color: #666; margin-bottom: 5px; }
        .json { background: #f0f0f0; padding: 10px; border-radius: 5px; font-family: monospace; white-space: pre-wrap; }
        .error { color: red; background: #ffe6e6; padding: 10px; border-radius: 5px; }
        .success { color: green; background: #e6ffe6; padding: 10px; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>Тест улучшенного генератора персонажей D&D 5e</h1>
    
    <?php
    try {
        $characterService = new CharacterService();
        
        // Тест 1: Генерация эльфа-волшебника
        echo "<div class='character'>";
        echo "<h3>1. Эльф-волшебник</h3>";
        $character1 = $characterService->generateCharacter([
            'race' => 'elf',
            'class' => 'wizard',
            'level' => 1,
            'gender' => 'male'
        ]);
        
        echo "<div class='section'><h4>Основная информация:</h4>";
        echo "Имя: " . htmlspecialchars($character1['name']) . "<br>";
        echo "Раса: " . htmlspecialchars($character1['race']) . "<br>";
        echo "Класс: " . htmlspecialchars($character1['class']) . "<br>";
        echo "Уровень: " . $character1['level'] . "<br>";
        echo "Мировоззрение: " . htmlspecialchars($character1['alignment']) . "<br>";
        echo "Предыстория: " . htmlspecialchars($character1['background']['name']) . " - " . htmlspecialchars($character1['background']['description']) . "<br>";
        echo "</div>";
        
        echo "<div class='section'><h4>Характеристики:</h4>";
        echo "<div class='json'>" . json_encode($character1['abilities'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "</div>";
        echo "</div>";
        
        echo "<div class='section'><h4>Снаряжение:</h4>";
        echo "<div class='json'>" . json_encode($character1['equipment'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "</div>";
        echo "</div>";
        
        echo "<div class='section'><h4>Заклинания:</h4>";
        echo "<div class='json'>" . json_encode($character1['spells'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "</div>";
        echo "</div>";
        
        echo "<div class='section'><h4>Зелья:</h4>";
        echo "<div class='json'>" . json_encode($character1['potions'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "</div>";
        echo "</div>";
        
        echo "<div class='section'><h4>Черты характера:</h4>";
        echo "<div class='json'>" . json_encode($character1['personality'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "</div>";
        echo "</div>";
        echo "</div>";
        
        // Тест 2: Генерация человека-воина
        echo "<div class='character'>";
        echo "<h3>2. Человек-воин</h3>";
        $character2 = $characterService->generateCharacter([
            'race' => 'human',
            'class' => 'fighter',
            'level' => 3,
            'gender' => 'female'
        ]);
        
        echo "<div class='section'><h4>Основная информация:</h4>";
        echo "Имя: " . htmlspecialchars($character2['name']) . "<br>";
        echo "Раса: " . htmlspecialchars($character2['race']) . "<br>";
        echo "Класс: " . htmlspecialchars($character2['class']) . "<br>";
        echo "Уровень: " . $character2['level'] . "<br>";
        echo "Мировоззрение: " . htmlspecialchars($character2['alignment']) . "<br>";
        echo "Предыстория: " . htmlspecialchars($character2['background']['name']) . " - " . htmlspecialchars($character2['background']['description']) . "<br>";
        echo "</div>";
        
        echo "<div class='section'><h4>Снаряжение:</h4>";
        echo "<div class='json'>" . json_encode($character2['equipment'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "</div>";
        echo "</div>";
        echo "</div>";
        
        // Тест 3: Проверка доступных рас и классов
        echo "<div class='character'>";
        echo "<h3>3. Доступные расы и классы</h3>";
        
        $races = $characterService->getRaces();
        echo "<div class='section'><h4>Доступные расы (" . count($races) . "):</h4>";
        echo "<ul>";
        foreach (array_slice($races, 0, 10) as $race) {
            echo "<li>" . htmlspecialchars($race['name'] ?? 'Неизвестная раса') . "</li>";
        }
        echo "</ul></div>";
        
        $classes = $characterService->getClasses();
        echo "<div class='section'><h4>Доступные классы (" . count($classes) . "):</h4>";
        echo "<ul>";
        foreach (array_slice($classes, 0, 10) as $class) {
            echo "<li>" . htmlspecialchars($class['name']['ru'] ?? $class['name']['en'] ?? 'Неизвестный класс') . "</li>";
        }
        echo "</ul></div>";
        echo "</div>";
        
        echo "<div class='success'>✅ Тестирование завершено успешно!</div>";
        
    } catch (Exception $e) {
        echo "<div class='error'>❌ Ошибка при тестировании: " . htmlspecialchars($e->getMessage()) . "</div>";
        echo "<div class='error'>Трассировка: <pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre></div>";
    }
    ?>
    
    <p><a href="index.php">← Вернуться к главной странице</a></p>
</body>
</html>
