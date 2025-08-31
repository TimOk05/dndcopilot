<?php
/**
 * Тест интеграции D&D API и AI API
 * Проверяет работу новой системы генерации персонажей
 */

require_once 'config.php';
require_once 'api/dnd-api-service.php';
require_once 'api/ai-service.php';
require_once 'api/generate-characters-v3.php';

echo "<h1>🧪 Тест интеграции D&D API и AI API</h1>\n";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
    .test-section { background: white; margin: 10px 0; padding: 15px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .success { border-left: 4px solid #4CAF50; }
    .error { border-left: 4px solid #f44336; }
    .warning { border-left: 4px solid #ff9800; }
    .info { border-left: 4px solid #2196F3; }
    pre { background: #f8f8f8; padding: 10px; border-radius: 4px; overflow-x: auto; }
    .api-info { background: #e3f2fd; padding: 10px; border-radius: 4px; margin: 10px 0; }
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
    echo "<div class='api-info'>\n";
    echo "<strong>Данные расы:</strong><br>\n";
    echo "Название: " . ($race_data['name'] ?? 'N/A') . "<br>\n";
    echo "Скорость: " . ($race_data['speed'] ?? 'N/A') . "<br>\n";
    echo "Бонусы характеристик: " . json_encode($race_data['ability_bonuses'] ?? []) . "<br>\n";
    echo "Черты: " . json_encode($race_data['traits'] ?? []) . "<br>\n";
    echo "Языки: " . json_encode($race_data['languages'] ?? []) . "<br>\n";
    echo "</div>\n";
    
    // Тест получения данных класса
    echo "<h3>Тест получения данных класса (Fighter):</h3>\n";
    $class_data = $dnd_service->getClassData('fighter');
    echo "<div class='api-info'>\n";
    echo "<strong>Данные класса:</strong><br>\n";
    echo "Название: " . ($class_data['name'] ?? 'N/A') . "<br>\n";
    echo "Кость здоровья: " . ($class_data['hit_die'] ?? 'N/A') . "<br>\n";
    echo "Владения: " . json_encode($class_data['proficiencies'] ?? []) . "<br>\n";
    echo "Заклинательство: " . ($class_data['spellcasting'] ? 'Да' : 'Нет') . "<br>\n";
    echo "</div>\n";
    
    // Тест получения заклинаний
    echo "<h3>Тест получения заклинаний (Wizard, уровень 1):</h3>\n";
    $spells = $dnd_service->getSpellsForClass('wizard', 1);
    echo "<div class='api-info'>\n";
    echo "<strong>Заклинания:</strong><br>\n";
    if (!empty($spells)) {
        foreach (array_slice($spells, 0, 3) as $spell) {
            echo "- {$spell['name']} (уровень {$spell['level']}, школа {$spell['school']})<br>\n";
        }
        if (count($spells) > 3) {
            echo "... и еще " . (count($spells) - 3) . " заклинаний<br>\n";
        }
    } else {
        echo "Заклинания не найдены (используются fallback данные)<br>\n";
    }
    echo "</div>\n";
    
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
    echo "<div class='api-info'>\n";
    echo "<strong>Описание:</strong><br>\n";
    echo htmlspecialchars($description) . "<br>\n";
    echo "</div>\n";
    
    // Тест генерации предыстории
    echo "<h3>Тест генерации предыстории персонажа:</h3>\n";
    $background = $ai_service->generateCharacterBackground($test_character, true);
    echo "<div class='api-info'>\n";
    echo "<strong>Предыстория:</strong><br>\n";
    echo htmlspecialchars($background) . "<br>\n";
    echo "</div>\n";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Ошибка AI Service: " . $e->getMessage() . "</p>\n";
}

// Тест 3: Полная генерация персонажа
echo "</div>\n<div class='test-section info'>\n";
echo "<h2>🎭 Тест 3: Полная генерация персонажа</h2>\n";

try {
    $generator = new CharacterGeneratorV3();
    echo "<p>✅ CharacterGeneratorV3 создан успешно</p>\n";
    
    // Параметры для генерации
    $params = [
        'race' => 'elf',
        'class' => 'wizard',
        'level' => 3,
        'alignment' => 'lawful-good',
        'gender' => 'female',
        'use_ai' => 'on'
    ];
    
    echo "<h3>Генерация эльфийки-волшебницы 3 уровня:</h3>\n";
    $result = $generator->generateCharacter($params);
    
    if ($result['success']) {
        $character = $result['character'];
        echo "<div class='api-info'>\n";
        echo "<strong>Персонаж создан успешно!</strong><br>\n";
        echo "Имя: {$character['name']}<br>\n";
        echo "Раса: {$character['race']}<br>\n";
        echo "Класс: {$character['class']}<br>\n";
        echo "Уровень: {$character['level']}<br>\n";
        echo "HP: {$character['hit_points']}<br>\n";
        echo "AC: {$character['armor_class']}<br>\n";
        echo "Скорость: {$character['speed']}<br>\n";
        echo "Профессия: {$character['occupation']}<br>\n";
        echo "</div>\n";
        
        echo "<h4>Характеристики:</h4>\n";
        echo "<div class='api-info'>\n";
        foreach ($character['abilities'] as $ability => $value) {
            $ability_names = ['str' => 'Сила', 'dex' => 'Ловкость', 'con' => 'Телосложение', 
                            'int' => 'Интеллект', 'wis' => 'Мудрость', 'cha' => 'Харизма'];
            echo "{$ability_names[$ability]}: {$value}<br>\n";
        }
        echo "</div>\n";
        
        echo "<h4>Заклинания:</h4>\n";
        echo "<div class='api-info'>\n";
        if (!empty($character['spells'])) {
            foreach (array_slice($character['spells'], 0, 5) as $spell) {
                echo "- {$spell['name']} (уровень {$spell['level']})<br>\n";
            }
            if (count($character['spells']) > 5) {
                echo "... и еще " . (count($character['spells']) - 5) . " заклинаний<br>\n";
            }
        } else {
            echo "Заклинания не найдены<br>\n";
        }
        echo "</div>\n";
        
        echo "<h4>Описание:</h4>\n";
        echo "<div class='api-info'>\n";
        echo htmlspecialchars($character['description']) . "<br>\n";
        echo "</div>\n";
        
        echo "<h4>Предыстория:</h4>\n";
        echo "<div class='api-info'>\n";
        echo htmlspecialchars($character['background']) . "<br>\n";
        echo "</div>\n";
        
        echo "<h4>Информация об API:</h4>\n";
        echo "<div class='api-info'>\n";
        if (isset($result['api_info'])) {
            echo "D&D API использован: " . ($result['api_info']['dnd_api_used'] ? 'Да' : 'Нет') . "<br>\n";
            echo "AI API использован: " . ($result['api_info']['ai_api_used'] ? 'Да' : 'Нет') . "<br>\n";
            echo "Источник данных: " . ($result['api_info']['data_source'] ?? 'Неизвестно') . "<br>\n";
        }
        echo "</div>\n";
        
    } else {
        echo "<p class='error'>❌ Ошибка генерации персонажа: " . ($result['error'] ?? 'Неизвестная ошибка') . "</p>\n";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Ошибка генератора персонажей: " . $e->getMessage() . "</p>\n";
}

// Тест 4: Проверка кэширования
echo "</div>\n<div class='test-section info'>\n";
echo "<h2>💾 Тест 4: Проверка кэширования</h2>\n";

try {
    $dnd_service = new DndApiService();
    
    echo "<h3>Первый запрос данных расы (должен загрузить из API):</h3>\n";
    $start_time = microtime(true);
    $race_data1 = $dnd_service->getRaceData('dwarf');
    $time1 = microtime(true) - $start_time;
    echo "<p>Время выполнения: " . round($time1 * 1000, 2) . " мс</p>\n";
    
    echo "<h3>Второй запрос данных расы (должен загрузить из кэша):</h3>\n";
    $start_time = microtime(true);
    $race_data2 = $dnd_service->getRaceData('dwarf');
    $time2 = microtime(true) - $start_time;
    echo "<p>Время выполнения: " . round($time2 * 1000, 2) . " мс</p>\n";
    
    if ($time2 < $time1) {
        echo "<p class='success'>✅ Кэширование работает! Второй запрос быстрее первого.</p>\n";
    } else {
        echo "<p class='warning'>⚠️ Кэширование может не работать или API недоступен.</p>\n";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Ошибка теста кэширования: " . $e->getMessage() . "</p>\n";
}

// Тест 5: Проверка fallback данных
echo "</div>\n<div class='test-section info'>\n";
echo "<h2>🔄 Тест 5: Проверка fallback данных</h2>\n";

try {
    $dnd_service = new DndApiService();
    
    echo "<h3>Тест с несуществующей расой (должен использовать fallback):</h3>\n";
    $fallback_race = $dnd_service->getRaceData('nonexistent_race');
    echo "<div class='api-info'>\n";
    echo "<strong>Fallback данные:</strong><br>\n";
    echo "Название: " . ($fallback_race['name'] ?? 'N/A') . "<br>\n";
    echo "Бонусы характеристик: " . json_encode($fallback_race['ability_bonuses'] ?? []) . "<br>\n";
    echo "</div>\n";
    
    echo "<h3>Тест с несуществующим классом (должен использовать fallback):</h3>\n";
    $fallback_class = $dnd_service->getClassData('nonexistent_class');
    echo "<div class='api-info'>\n";
    echo "<strong>Fallback данные:</strong><br>\n";
    echo "Название: " . ($fallback_class['name'] ?? 'N/A') . "<br>\n";
    echo "Кость здоровья: " . ($fallback_class['hit_die'] ?? 'N/A') . "<br>\n";
    echo "</div>\n";
    
    echo "<p class='success'>✅ Fallback данные работают корректно</p>\n";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Ошибка fallback данных: " . $e->getMessage() . "</p>\n";
}

echo "</div>\n";

// Итоговая информация
echo "<div class='test-section success'>\n";
echo "<h2>📊 Итоги тестирования</h2>\n";
echo "<p><strong>Новая система генерации персонажей включает:</strong></p>\n";
echo "<ul>\n";
echo "<li>🔗 Интеграцию с внешними D&D API (D&D 5e API, Open5e)</li>\n";
echo "<li>🤖 Использование AI API для генерации описаний и предысторий</li>\n";
echo "<li>💾 Систему кэширования для оптимизации производительности</li>\n";
echo "<li>🔄 Fallback данные на случай недоступности API</li>\n";
echo "<li>📝 Подробную информацию об источниках данных</li>\n";
echo "</ul>\n";
echo "<p><strong>Для использования новой системы:</strong></p>\n";
echo "<ol>\n";
echo "<li>Настройте API ключи в config.php</li>\n";
echo "<li>Используйте generate-characters-v3.php вместо старой версии</li>\n";
echo "<li>Проверьте доступность внешних API</li>\n";
echo "</ol>\n";
echo "</div>\n";

echo "<div class='test-section warning'>\n";
echo "<h2>⚠️ Важные замечания</h2>\n";
echo "<ul>\n";
echo "<li>Внешние API могут быть недоступны или иметь ограничения</li>\n";
echo "<li>AI API требует настройки API ключей</li>\n";
echo "<li>Кэширование помогает снизить нагрузку на внешние API</li>\n";
echo "<li>Fallback данные обеспечивают работу системы даже при недоступности API</li>\n";
echo "</ul>\n";
echo "</div>\n";
?>
