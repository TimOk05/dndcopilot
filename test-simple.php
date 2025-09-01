<?php
// Простой тест
echo "Тест запущен\n";

// Проверяем, что файл config.php существует
if (file_exists('config.php')) {
    echo "config.php найден\n";
    require_once 'config.php';
    echo "config.php загружен\n";
} else {
    echo "config.php не найден\n";
}

// Проверяем, что файл generate-enemies.php существует
if (file_exists('api/generate-enemies.php')) {
    echo "generate-enemies.php найден\n";
} else {
    echo "generate-enemies.php не найден\n";
}

// Проверяем подключение к интернету
$test_url = 'https://www.dnd5eapi.co/api/monsters';
echo "Тестируем подключение к: $test_url\n";

$ch = curl_init($test_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "CURL ошибка: $error\n";
} else {
    echo "HTTP код: $http_code\n";
    if ($http_code === 200) {
        echo "Подключение успешно\n";
        $data = json_decode($response, true);
        if ($data && isset($data['results'])) {
            echo "Найдено монстров: " . count($data['results']) . "\n";
        }
    } else {
        echo "HTTP ошибка\n";
    }
}
?>
