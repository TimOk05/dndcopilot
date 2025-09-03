<?php
// Скрипт для поиска всех файлов php.ini в системе
echo "<h1>🔍 Поиск файлов php.ini</h1>";

// Проверяем текущий php.ini
echo "<h2>1. Текущий php.ini</h2>";
$current_ini = php_ini_loaded_file();
if ($current_ini) {
    echo "✅ Загруженный php.ini: <strong>$current_ini</strong><br>";
} else {
    echo "❌ Не найден загруженный php.ini<br>";
}

// Проверяем дополнительные ini файлы
echo "<h2>2. Дополнительные ini файлы</h2>";
$additional_inis = php_ini_scanned_files();
if ($additional_inis) {
    echo "✅ Дополнительные ini файлы:<br>";
    $files = explode(',', $additional_inis);
    foreach ($files as $file) {
        $file = trim($file);
        if ($file) {
            echo "- $file<br>";
        }
    }
} else {
    echo "❌ Дополнительные ini файлы не найдены<br>";
}

// Проверяем возможные места
echo "<h2>3. Возможные места расположения</h2>";
$possible_paths = [
    'C:\\Windows\\php.ini',
    'C:\\Windows\\System32\\php.ini',
    'C:\\php\\php.ini',
    'C:\\xampp\\php\\php.ini',
    'C:\\wamp\\bin\\php\\php8.1.0\\php.ini',
    'C:\\wamp64\\bin\\php\\php8.1.0\\php.ini',
    'C:\\laragon\\bin\\php\\php8.1.0\\php.ini',
    'C:\\Program Files\\PHP\\php.ini',
    'C:\\Program Files (x86)\\PHP\\php.ini'
];

foreach ($possible_paths as $path) {
    if (file_exists($path)) {
        echo "✅ Найден: <strong>$path</strong><br>";
    } else {
        echo "❌ Не найден: $path<br>";
    }
}

// Проверяем переменные окружения
echo "<h2>4. Переменные окружения</h2>";
$env_vars = [
    'PHP_INI_SCAN_DIR',
    'PHPRC',
    'PHP_INI_DIR'
];

foreach ($env_vars as $var) {
    $value = getenv($var);
    if ($value) {
        echo "✅ $var = <strong>$value</strong><br>";
    } else {
        echo "❌ $var не установлена<br>";
    }
}

// Проверяем версию PHP
echo "<h2>5. Информация о PHP</h2>";
echo "Версия PHP: <strong>" . PHP_VERSION . "</strong><br>";
echo "Путь к PHP: <strong>" . PHP_BINARY . "</strong><br>";
echo "Путь к модулям: <strong>" . PHP_EXTENSION_DIR . "</strong><br>";

// Проверяем OpenSSL
echo "<h2>6. Статус OpenSSL</h2>";
if (extension_loaded('openssl')) {
    echo "✅ OpenSSL включен<br>";
    echo "Версия: " . OPENSSL_VERSION_TEXT . "<br>";
} else {
    echo "❌ OpenSSL НЕ включен<br>";
    
    // Проверяем доступные модули
    echo "<h3>Доступные модули:</h3>";
    $modules = get_loaded_extensions();
    $ssl_modules = array_filter($modules, function($module) {
        return stripos($module, 'ssl') !== false || stripos($module, 'curl') !== false;
    });
    
    if (!empty($ssl_modules)) {
        echo "Найдены SSL-связанные модули:<br>";
        foreach ($ssl_modules as $module) {
            echo "- $module<br>";
        }
    } else {
        echo "SSL-связанные модули не найдены<br>";
    }
}

// Проверяем cURL
echo "<h2>7. Статус cURL</h2>";
if (function_exists('curl_init')) {
    echo "✅ cURL доступен<br>";
    $curl_version = curl_version();
    echo "Версия: " . $curl_version['version'] . "<br>";
    echo "SSL версия: " . $curl_version['ssl_version'] . "<br>";
    
    // Тестируем HTTPS
    echo "<h3>Тест HTTPS соединения:</h3>";
    $ch = curl_init('https://www.google.com');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($result !== false && $httpCode === 200) {
        echo "✅ HTTPS работает через cURL!<br>";
    } else {
        echo "❌ HTTPS не работает через cURL: $error<br>";
    }
} else {
    echo "❌ cURL недоступен<br>";
}

echo "<h2>8. Рекомендации</h2>";
if (!extension_loaded('openssl')) {
    echo "<strong>🔴 КРИТИЧЕСКАЯ ПРОБЛЕМА:</strong><br>";
    echo "OpenSSL не включен. Варианты решения:<br>";
    echo "<br>";
    echo "<strong>Вариант A: Изменить php.ini</strong><br>";
    echo "1. Найдите правильный файл php.ini (см. выше)<br>";
    echo "2. Откройте его в блокноте<br>";
    echo "3. Найдите строку: ;extension=openssl<br>";
    echo "4. Уберите точку с запятой: extension=openssl<br>";
    echo "5. Сохраните и перезапустите веб-сервер<br>";
    echo "<br>";
    echo "<strong>Вариант B: Использовать XAMPP/WAMP</strong><br>";
    echo "1. Скачайте XAMPP или WAMP<br>";
    echo "2. Установите - они уже настроены с OpenSSL<br>";
    echo "<br>";
    echo "<strong>Вариант C: Переустановить PHP</strong><br>";
    echo "1. Скачайте PHP с официального сайта<br>";
    echo "2. Выберите версию с включенными расширениями<br>";
    echo "<br>";
} else {
    echo "<strong>✅ OpenSSL работает!</strong><br>";
    echo "AI должен работать корректно!<br>";
}
?>
