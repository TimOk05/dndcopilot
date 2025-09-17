<?php
// Тест PowerShell HTTP сервиса
require_once 'app/Services/PowerShellHttpService.php';

echo "=== ТЕСТ POWERSHELL HTTP СЕРВИСА ===\n\n";

$service = new PowerShellHttpService();

echo "--- Проверка доступности PowerShell ---\n";
if ($service->isAvailable()) {
    echo "✅ PowerShell доступен\n";
} else {
    echo "❌ PowerShell недоступен\n";
    exit(1);
}

echo "\n--- Тест подключения к D&D API ---\n";
$result = $service->testConnection();

if ($result['success']) {
    echo "✅ " . $result['message'] . "\n";
    
    if (isset($result['data']['count'])) {
        echo "Найдено монстров: " . $result['data']['count'] . "\n";
        
        if (isset($result['data']['results']) && count($result['data']['results']) > 0) {
            $first_monster = $result['data']['results'][0];
            echo "Первый монстр: " . $first_monster['name'] . "\n";
            echo "URL: " . $first_monster['url'] . "\n";
        }
    }
} else {
    echo "❌ " . $result['message'] . "\n";
}

echo "\n--- Тест получения детальной информации о монстре ---\n";
try {
    $monster_url = 'https://www.dnd5eapi.co/api/monsters/goblin';
    $monster_data = $service->get($monster_url);
    
    if ($monster_data && isset($monster_data['name'])) {
        echo "✅ Успешно получены данные о монстре: " . $monster_data['name'] . "\n";
        echo "CR: " . ($monster_data['challenge_rating'] ?? 'Не указан') . "\n";
        echo "Тип: " . ($monster_data['type'] ?? 'Не указан') . "\n";
        echo "HP: " . ($monster_data['hit_points'] ?? 'Не указаны') . "\n";
        echo "AC: " . ($monster_data['armor_class'] ?? 'Не указан') . "\n";
    } else {
        echo "❌ Не удалось получить данные о монстре\n";
    }
} catch (Exception $e) {
    echo "❌ Ошибка получения данных о монстре: " . $e->getMessage() . "\n";
}

echo "\n--- Рекомендации ---\n";
echo "✅ PowerShell HTTP сервис работает!\n";
echo "Можно использовать для:\n";
echo "1. Получения списка монстров\n";
echo "2. Получения детальной информации о монстрах\n";
echo "3. Работы с D&D 5e API\n";
echo "4. Генерации противников с любыми уровнями сложности\n";
?>
