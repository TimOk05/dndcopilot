<?php
// Тест внешних сервисов
require_once 'config/config.php';
require_once 'app/Services/ExternalApiService.php';

echo "=== ТЕСТ ВНЕШНИХ СЕРВИСОВ ===\n\n";

$service = new ExternalApiService();

// Тест 1: Генерация имен
echo "--- Тест 1: Генерация имен ---\n";
$name_result = $service->generateCharacterNames('human', 'any', 5);
if ($name_result['success']) {
    echo "✅ Успешно сгенерировано " . $name_result['count'] . " имен:\n";
    foreach ($name_result['names'] as $name) {
        echo "  - $name\n";
    }
} else {
    echo "❌ Ошибка: " . $name_result['error'] . "\n";
}

echo "\n--- Тест 2: Генерация имен эльфов ---\n";
$elf_result = $service->generateCharacterNames('elf', 'female', 3);
if ($elf_result['success']) {
    echo "✅ Успешно сгенерировано " . $elf_result['count'] . " имен эльфов:\n";
    foreach ($elf_result['names'] as $name) {
        echo "  - $name\n";
    }
} else {
    echo "❌ Ошибка: " . $elf_result['error'] . "\n";
}

// Тест 2: Бросок костей
echo "\n--- Тест 3: Бросок костей ---\n";
$dice_tests = ['1d20', '2d6+3', '3d4-1', '1d100'];

foreach ($dice_tests as $dice) {
    $dice_result = $service->rollDice($dice);
    if ($dice_result['success']) {
        echo "✅ $dice: " . implode(', ', $dice_result['rolls']) . " = " . $dice_result['total'] . "\n";
    } else {
        echo "❌ $dice: " . $dice_result['error'] . "\n";
    }
}

// Тест 3: Погода (если настроен API ключ)
echo "\n--- Тест 4: Погода ---\n";
if (!empty(getApiKey('openweathermap'))) {
    $weather_result = $service->getWeather('Moscow');
    if ($weather_result['success']) {
        echo "✅ Погода в " . $weather_result['location'] . ":\n";
        echo "  Температура: " . $weather_result['temperature'] . "°C\n";
        echo "  Ощущается как: " . $weather_result['feels_like'] . "°C\n";
        echo "  Описание: " . $weather_result['description'] . "\n";
        echo "  Влажность: " . $weather_result['humidity'] . "%\n";
    } else {
        echo "❌ Ошибка погоды: " . $weather_result['error'] . "\n";
    }
} else {
    echo "⚠️ API ключ для погоды не настроен\n";
}

// Тест 4: Перевод (если настроен API ключ)
echo "\n--- Тест 5: Перевод ---\n";
if (!empty(getApiKey('translate'))) {
    $translate_result = $service->translateText('Hello, world!', 'ru', 'en');
    if ($translate_result['success']) {
        echo "✅ Перевод: '" . $translate_result['original_text'] . "' -> '" . $translate_result['translated_text'] . "'\n";
    } else {
        echo "❌ Ошибка перевода: " . $translate_result['error'] . "\n";
    }
} else {
    echo "⚠️ API ключ для переводов не настроен\n";
}

// Тест 5: Генерация изображений (если настроен API ключ)
echo "\n--- Тест 6: Генерация изображений ---\n";
if (!empty(getApiKey('image_generation'))) {
    $image_result = $service->generateImage('A fantasy warrior with a sword', '512x512', 1);
    if ($image_result['success']) {
        echo "✅ Изображение сгенерировано:\n";
        foreach ($image_result['images'] as $image_url) {
            echo "  URL: $image_url\n";
        }
    } else {
        echo "❌ Ошибка генерации изображения: " . $image_result['error'] . "\n";
    }
} else {
    echo "⚠️ API ключ для генерации изображений не настроен\n";
}

echo "\n--- Тест 7: Веб API ---\n";
echo "Тестируем веб API endpoint...\n";

// Тест веб API
$test_urls = [
    'http://localhost/dnd/public/api/external-services.php?action=generate_names&race=human&count=3',
    'http://localhost/dnd/public/api/external-services.php?action=roll_dice&dice=1d20',
    'http://localhost/dnd/public/api/external-services.php?action=test'
];

foreach ($test_urls as $url) {
    echo "Тестируем: $url\n";
    $response = @file_get_contents($url);
    if ($response !== false) {
        $data = json_decode($response, true);
        if ($data && $data['success']) {
            echo "✅ Веб API работает\n";
        } else {
            echo "❌ Веб API ошибка: " . ($data['error'] ?? 'Неизвестная ошибка') . "\n";
        }
    } else {
        echo "❌ Не удалось подключиться к веб API\n";
    }
    echo "\n";
}

echo "=== ТЕСТ ЗАВЕРШЕН ===\n";
?>
