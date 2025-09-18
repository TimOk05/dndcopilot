<?php
/**
 * Тест полноценной генерации персонажей
 * Проверяет работу нового FullCharacterService
 */

require_once 'config/config.php';
require_once 'app/Services/FullCharacterService.php';

echo "🎲 Тест полноценной генерации персонажей\n";
echo "=====================================\n\n";

try {
    // Создаем сервис
    echo "1. Инициализация FullCharacterService...\n";
    $service = new FullCharacterService();
    echo "✅ Сервис создан успешно\n\n";
    
    // Тестовые параметры
    $testParams = [
        'race' => 'human',
        'class' => 'fighter',
        'level' => 1,
        'gender' => 'random',
        'background' => 'random',
        'alignment' => 'random',
        'ability_method' => 'standard_array'
    ];
    
    echo "2. Тестовые параметры:\n";
    foreach ($testParams as $key => $value) {
        echo "   - {$key}: {$value}\n";
    }
    echo "\n";
    
    echo "3. Запуск генерации персонажа...\n";
    $startTime = microtime(true);
    
    $result = $service->generateFullCharacter($testParams);
    
    $endTime = microtime(true);
    $duration = round($endTime - $startTime, 2);
    
    echo "4. Результат генерации (время: {$duration}с):\n";
    echo "=====================================\n";
    
    if ($result['success']) {
        echo "✅ Генерация успешна!\n\n";
        
        $character = $result['character'];
        echo "📋 Информация о персонаже:\n";
        echo "   - Имя: " . ($character['name'] ?? 'Не указано') . "\n";
        echo "   - Раса: " . ($character['race'] ?? 'Не указана') . "\n";
        echo "   - Класс: " . ($character['class'] ?? 'Не указан') . "\n";
        echo "   - Уровень: " . ($character['level'] ?? 'Не указан') . "\n";
        echo "   - Мировоззрение: " . ($character['alignment'] ?? 'Не указано') . "\n";
        echo "   - Пол: " . ($character['gender'] ?? 'Не указан') . "\n";
        echo "   - Происхождение: " . ($character['background'] ?? 'Не указано') . "\n";
        
        if (isset($character['abilities'])) {
            echo "\n💪 Характеристики:\n";
            foreach ($character['abilities'] as $ability => $score) {
                echo "   - " . strtoupper($ability) . ": {$score}\n";
            }
        }
        
        if (isset($character['hit_points'])) {
            echo "\n❤️ Боевые характеристики:\n";
            echo "   - Хиты: " . ($character['hit_points'] ?? 'Не указано') . "\n";
            echo "   - Класс брони: " . ($character['armor_class'] ?? 'Не указан') . "\n";
            echo "   - Скорость: " . ($character['speed'] ?? 'Не указана') . "\n";
            echo "   - Инициатива: " . ($character['initiative'] ?? 'Не указана') . "\n";
        }
        
        if (isset($character['description'])) {
            echo "\n📖 Описание:\n";
            echo "   " . substr($character['description'], 0, 200) . "...\n";
        }
        
        if (isset($character['background_story'])) {
            echo "\n📚 Предыстория:\n";
            echo "   " . substr($character['background_story'], 0, 200) . "...\n";
        }
        
        if (isset($result['sources'])) {
            echo "\n🔗 Источники данных:\n";
            foreach ($result['sources'] as $source => $description) {
                echo "   - {$source}: {$description}\n";
            }
        }
        
    } else {
        echo "❌ Ошибка генерации:\n";
        echo "   - Тип ошибки: " . ($result['error'] ?? 'Неизвестная ошибка') . "\n";
        echo "   - Сообщение: " . ($result['message'] ?? 'Нет сообщения') . "\n";
        echo "   - Детали: " . ($result['details'] ?? 'Нет деталей') . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Критическая ошибка: " . $e->getMessage() . "\n";
    echo "   Файл: " . $e->getFile() . "\n";
    echo "   Строка: " . $e->getLine() . "\n";
}

echo "\n🏁 Тест завершен\n";
?>
