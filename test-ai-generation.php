<?php
require_once 'config.php';
require_once 'api/generate-characters.php';

echo "=== Тест AI генерации персонажа ===\n\n";

// Создаем тестовые данные
$testData = [
    'race' => 'human',
    'class' => 'fighter',
    'level' => 1,
    'gender' => 'male',
    'alignment' => 'lawful-good'
];

echo "Тестовые данные: " . json_encode($testData, JSON_UNESCAPED_UNICODE) . "\n\n";

try {
    // Создаем генератор
    $generator = new CharacterGenerator();
    
    // Генерируем персонажа
    echo "Генерируем персонажа...\n";
    $result = $generator->generateCharacter($testData);
    
    if ($result['success']) {
        $character = $result['character'];
        echo "\n✅ Персонаж сгенерирован успешно!\n";
        echo "Имя: " . $character['name'] . "\n";
        echo "Раса: " . $character['race'] . "\n";
        echo "Класс: " . $character['class'] . "\n";
        echo "Уровень: " . $character['level'] . "\n\n";
        
        echo "=== ОПИСАНИЕ ===\n";
        echo $character['description'] . "\n\n";
        
        echo "=== ПРЕДЫСТОРИЯ ===\n";
        echo $character['background'] . "\n\n";
        
        // Проверяем, откуда взялись описания
        echo "=== АНАЛИЗ ===\n";
        if (strpos($character['description'], 'крепкий мужчина') !== false) {
            echo "❌ Описание взято из FALLBACK (шаблонное)\n";
        } else {
            echo "✅ Описание сгенерировано AI\n";
        }
        
        if (strpos($character['background'], 'вырос в семье') !== false) {
            echo "❌ Предыстория взята из FALLBACK (шаблонная)\n";
        } else {
            echo "✅ Предыстория сгенерирована AI\n";
        }
        
    } else {
        echo "❌ Ошибка генерации: " . $result['error'] . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Исключение: " . $e->getMessage() . "\n";
}

echo "\n=== Тест завершен ===\n";
?>
