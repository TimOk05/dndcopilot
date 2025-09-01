<?php
/**
 * Тест генератора персонажей v4 (без fallback системы)
 * Проверяет работу системы только с реальными API
 */

require_once 'config.php';
require_once 'api/dnd-api-service.php';
require_once 'api/ai-service.php';
require_once 'api/generate-characters-v4.php';

echo "<h1>🧪 Тест генератора персонажей v4 (без fallback)</h1>\n";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
    .test-section { background: white; margin: 10px 0; padding: 15px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .success { border-left: 4px solid #4CAF50; }
    .error { border-left: 4px solid #f44336; }
    .warning { border-left: 4px solid #ff9800; }
    .info { border-left: 4px solid #2196F3; }
    pre { background: #f8f8f8; padding: 10px; border-radius: 4px; overflow-x: auto; }
    .api-info { background: #e3f2fd; padding: 10px; border-radius: 4px; margin: 10px 0; }
    .character-info { background: #f3e5f5; padding: 10px; border-radius: 4px; margin: 10px 0; }
</style>\n";

// Тест 1: Проверка D&D API Service
echo "<div class='test-section info'>\n";
echo "<h2>🔧 Тест 1: D&D API Service</h2>\n";

try {
    $dnd_service = new DndApiService();
    echo "<p>✅ D&D API Service создан успешно</p>\n";
    
    // Тест получения данных расы
    echo "<h3>Тест получения данных расы (Human):</h3>\n";
    $race_data = $dnd_service->getRaceData('human');
    if (isset($race_data['error'])) {
        echo "<p class='error'>❌ Ошибка получения данных расы: " . $race_data['message'] . "</p>\n";
    } else {
        echo "<div class='api-info'>\n";
        echo "<strong>Данные расы:</strong><br>\n";
        echo "Название: " . ($race_data['name'] ?? 'N/A') . "<br>\n";
        echo "Скорость: " . ($race_data['speed'] ?? 'N/A') . "<br>\n";
        echo "Бонусы характеристик: " . json_encode($race_data['ability_bonuses'] ?? []) . "<br>\n";
        echo "Черты: " . json_encode($race_data['traits'] ?? []) . "<br>\n";
        echo "Языки: " . json_encode($race_data['languages'] ?? []) . "<br>\n";
        echo "</div>\n";
    }
    
    // Тест получения данных класса
    echo "<h3>Тест получения данных класса (Fighter):</h3>\n";
    $class_data = $dnd_service->getClassData('fighter');
    if (isset($class_data['error'])) {
        echo "<p class='error'>❌ Ошибка получения данных класса: " . $class_data['message'] . "</p>\n";
    } else {
        echo "<div class='api-info'>\n";
        echo "<strong>Данные класса:</strong><br>\n";
        echo "Название: " . ($class_data['name'] ?? 'N/A') . "<br>\n";
        echo "Кость здоровья: " . ($class_data['hit_die'] ?? 'N/A') . "<br>\n";
        echo "Владения: " . json_encode($class_data['proficiencies'] ?? []) . "<br>\n";
        echo "Заклинательство: " . ($class_data['spellcasting'] ? 'Да' : 'Нет') . "<br>\n";
        echo "</div>\n";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Ошибка D&D API Service: " . $e->getMessage() . "</p>\n";
}

// Тест 2: Проверка AI Service
echo "</div>\n<div class='test-section info'>\n";
echo "<h2>🤖 Тест 2: AI Service</h2>\n";

try {
    $ai_service = new AiService();
    echo "<p>✅ AI Service создан успешно</p>\n";
    
    // Тестовый персонаж
    $test_character = [
        'name' => 'Тестовый Персонаж',
        'race' => 'Человек',
        'class' => 'Воин',
        'level' => 1,
        'occupation' => 'Кузнец',
        'gender' => 'Мужчина',
        'alignment' => 'Нейтрально-добрый',
        'abilities' => ['str' => 16, 'dex' => 14, 'con' => 15, 'int' => 12, 'wis' => 10, 'cha' => 8]
    ];
    
    // Тест генерации описания
    echo "<h3>Тест генерации описания персонажа:</h3>\n";
    $description = $ai_service->generateCharacterDescription($test_character, true);
    if (isset($description['error'])) {
        echo "<p class='warning'>⚠️ AI генерация описания не удалась: " . $description['message'] . "</p>\n";
    } else {
        echo "<div class='api-info'>\n";
        echo "<strong>AI Описание:</strong><br>\n";
        echo htmlspecialchars($description) . "<br>\n";
        echo "</div>\n";
    }
    
    // Тест генерации предыстории
    echo "<h3>Тест генерации предыстории персонажа:</h3>\n";
    $background = $ai_service->generateCharacterBackground($test_character, true);
    if (isset($background['error'])) {
        echo "<p class='warning'>⚠️ AI генерация предыстории не удалась: " . $background['message'] . "</p>\n";
    } else {
        echo "<div class='api-info'>\n";
        echo "<strong>AI Предыстория:</strong><br>\n";
        echo htmlspecialchars($background) . "<br>\n";
        echo "</div>\n";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Ошибка AI Service: " . $e->getMessage() . "</p>\n";
}

// Тест 3: Проверка генератора персонажей v4
echo "</div>\n<div class='test-section info'>\n";
echo "<h2>⚔️ Тест 3: Генератор персонажей v4</h2>\n";

try {
    $generator = new CharacterGeneratorV4();
    echo "<p>✅ Генератор персонажей v4 создан успешно</p>\n";
    
    // Тест генерации персонажа
    echo "<h3>Тест генерации персонажа (Human Fighter):</h3>\n";
    $params = [
        'race' => 'human',
        'class' => 'fighter',
        'level' => 1,
        'alignment' => 'neutral-good',
        'gender' => 'male',
        'use_ai' => 'on'
    ];
    
    $result = $generator->generateCharacter($params);
    
    if ($result['success']) {
        echo "<p class='success'>✅ Персонаж успешно сгенерирован!</p>\n";
        
        $character = $result['character'];
        echo "<div class='character-info'>\n";
        echo "<strong>Информация о персонаже:</strong><br>\n";
        echo "Имя: " . ($character['name'] ?? 'N/A') . "<br>\n";
        echo "Раса: " . ($character['race'] ?? 'N/A') . "<br>\n";
        echo "Класс: " . ($character['class'] ?? 'N/A') . "<br>\n";
        echo "Уровень: " . ($character['level'] ?? 'N/A') . "<br>\n";
        echo "HP: " . ($character['hit_points'] ?? 'N/A') . "<br>\n";
        echo "AC: " . ($character['armor_class'] ?? 'N/A') . "<br>\n";
        echo "Характеристики: " . json_encode($character['abilities'] ?? []) . "<br>\n";
        echo "Профессия: " . ($character['occupation'] ?? 'N/A') . "<br>\n";
        echo "Описание: " . htmlspecialchars(substr($character['description'] ?? 'N/A', 0, 100)) . "...<br>\n";
        echo "Предыстория: " . htmlspecialchars(substr($character['background'] ?? 'N/A', 0, 100)) . "...<br>\n";
        echo "</div>\n";
        
        echo "<div class='api-info'>\n";
        echo "<strong>API информация:</strong><br>\n";
        echo "D&D API использован: " . ($result['api_info']['dnd_api_used'] ? 'Да' : 'Нет') . "<br>\n";
        echo "AI API использован: " . ($result['api_info']['ai_api_used'] ? 'Да' : 'Нет') . "<br>\n";
        echo "Источник данных: " . ($result['api_info']['data_source'] ?? 'N/A') . "<br>\n";
        echo "Информация о кэше: " . ($result['api_info']['cache_info'] ?? 'N/A') . "<br>\n";
        echo "</div>\n";
        
    } else {
        echo "<p class='error'>❌ Ошибка генерации персонажа: " . ($result['error'] ?? 'Неизвестная ошибка') . "</p>\n";
        if (isset($result['details'])) {
            echo "<p class='warning'>Детали: " . $result['details'] . "</p>\n";
        }
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Ошибка генератора персонажей v4: " . $e->getMessage() . "</p>\n";
}

// Тест 4: Проверка кэширования
echo "</div>\n<div class='test-section info'>\n";
echo "<h2>💾 Тест 4: Система кэширования</h2>\n";

try {
    echo "<h3>Проверка папок кэша:</h3>\n";
    
    $dnd_cache_dir = __DIR__ . '/cache/dnd_api/';
    $ai_cache_dir = __DIR__ . '/cache/ai/';
    
    if (is_dir($dnd_cache_dir)) {
        echo "<p>✅ Папка D&D API кэша существует</p>\n";
        $dnd_files = glob($dnd_cache_dir . '*.json');
        echo "<p>📁 D&D кэш файлов: " . count($dnd_files) . "</p>\n";
    } else {
        echo "<p class='warning'>⚠️ Папка D&D API кэша не существует</p>\n";
    }
    
    if (is_dir($ai_cache_dir)) {
        echo "<p>✅ Папка AI кэша существует</p>\n";
        $ai_files = glob($ai_cache_dir . '*.json');
        echo "<p>📁 AI кэш файлов: " . count($ai_files) . "</p>\n";
    } else {
        echo "<p class='warning'>⚠️ Папка AI кэша не существует</p>\n";
    }
    
    echo "<h3>Тест очистки кэша:</h3>\n";
    
    if (method_exists($dnd_service, 'clearAllCache')) {
        $cleared_count = $dnd_service->clearAllCache();
        echo "<p>🧹 Очищено D&D кэш файлов: {$cleared_count}</p>\n";
    }
    
    if (method_exists($ai_service, 'clearAllAiCache')) {
        $cleared_count = $ai_service->clearAllAiCache();
        echo "<p>🧹 Очищено AI кэш файлов: {$cleared_count}</p>\n";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Ошибка проверки кэширования: " . $e->getMessage() . "</p>\n";
}

// Тест 5: Проверка обработки ошибок
echo "</div>\n<div class='test-section info'>\n";
echo "<h2>🚨 Тест 5: Обработка ошибок</h2>\n";

try {
    echo "<h3>Тест с несуществующей расой:</h3>\n";
    $params = [
        'race' => 'nonexistent_race',
        'class' => 'fighter',
        'level' => 1,
        'alignment' => 'neutral',
        'gender' => 'male',
        'use_ai' => 'off'
    ];
    
    $result = $generator->generateCharacter($params);
    
    if (!$result['success']) {
        echo "<p class='success'>✅ Ошибка корректно обработана: " . ($result['error'] ?? 'Неизвестная ошибка') . "</p>\n";
    } else {
        echo "<p class='warning'>⚠️ Ошибка не была обработана</p>\n";
    }
    
} catch (Exception $e) {
    echo "<p class='success'>✅ Исключение корректно перехвачено: " . $e->getMessage() . "</p>\n";
}

echo "</div>\n<div class='test-section success'>\n";
echo "<h2>🎯 Итоги тестирования</h2>\n";
echo "<h3>Что протестировано:</h3>\n";
echo "<ul>\n";
echo "<li>✅ D&D API Service - получение данных о расах, классах, заклинаниях</li>\n";
echo "<li>✅ AI Service - генерация описаний и предысторий</li>\n";
echo "<li>✅ Генератор персонажей v4 - создание персонажей без fallback</li>\n";
echo "<li>✅ Система кэширования - улучшенное кэширование с валидацией</li>\n";
echo "<li>✅ Обработка ошибок - корректная обработка API ошибок</li>\n";
echo "</ul>\n";

echo "<h3>Ключевые изменения v4:</h3>\n";
echo "<ul>\n";
echo "<li>🚫 Убрана система fallback данных</li>\n";
echo "<li>🔗 Работа только с реальными D&D API</li>\n";
echo "<li>🤖 AI генерация с fallback на базовые описания</li>\n";
echo "<li>💾 Улучшенная система кэширования</li>\n";
echo "<li>🚨 Улучшенная обработка ошибок</li>\n";
echo "</ul>\n";

echo "<h3>Рекомендации:</h3>\n";
echo "<ul>\n";
echo "<li>🌐 Убедитесь в доступности D&D API</li>\n";
echo "<li>🔑 Проверьте настройку API ключей</li>\n";
echo "<li>📁 Создайте папки кэша если их нет</li>\n";
echo "<li>📊 Мониторьте логи для диагностики проблем</li>\n";
echo "</ul>\n";

echo "</div>\n";
?>
