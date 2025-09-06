<?php
/**
 * Тест настроек логирования PHP
 */

echo "<h1>🔍 Тест настроек логирования PHP</h1>";

// Проверяем настройки логирования
echo "<h2>📋 Настройки логирования</h2>";
echo "<p><strong>log_errors:</strong> " . (ini_get('log_errors') ? 'Включено' : 'Отключено') . "</p>";
echo "<p><strong>error_log:</strong> " . (ini_get('error_log') ?: 'Не установлен') . "</p>";
echo "<p><strong>display_errors:</strong> " . (ini_get('display_errors') ? 'Включено' : 'Отключено') . "</p>";

// Пытаемся записать в лог
echo "<h2>✍️ Тест записи в лог</h2>";

// Тест 1: error_log
echo "<p>Тестируем error_log()...</p>";
$test_message = "Тест логирования: " . date('Y-m-d H:i:s');
if (error_log($test_message)) {
    echo "<p style='color: green;'>✅ error_log() выполнен успешно</p>";
} else {
    echo "<p style='color: red;'>❌ error_log() не выполнен</p>";
}

// Тест 2: Прямая запись в файл
echo "<p>Тестируем прямую запись в файл...</p>";
$log_file = __DIR__ . '/logs/test-php.log';
$test_message2 = "[" . date('Y-m-d H:i:s') . "] Прямая запись в файл\n";

if (file_put_contents($log_file, $test_message2, FILE_APPEND | LOCK_EX) !== false) {
    echo "<p style='color: green;'>✅ Прямая запись в файл успешна</p>";
    echo "<p><strong>Файл:</strong> $log_file</p>";
} else {
    echo "<p style='color: red;'>❌ Прямая запись в файл не удалась</p>";
}

// Тест 3: Проверяем права на запись
echo "<h2>🔐 Проверка прав доступа</h2>";
$logs_dir = __DIR__ . '/logs';
echo "<p><strong>Директория логов:</strong> $logs_dir</p>";
echo "<p><strong>Существует:</strong> " . (is_dir($logs_dir) ? 'Да' : 'Нет') . "</p>";
echo "<p><strong>Доступна для записи:</strong> " . (is_writable($logs_dir) ? 'Да' : 'Нет') . "</p>";

if (file_exists($log_file)) {
    echo "<p><strong>Файл лога существует:</strong> Да</p>";
    echo "<p><strong>Размер файла:</strong> " . filesize($log_file) . " байт</p>";
    echo "<p><strong>Доступен для записи:</strong> " . (is_writable($log_file) ? 'Да' : 'Нет') . "</p>";
} else {
    echo "<p><strong>Файл лога существует:</strong> Нет</p>";
}

echo "<p><strong>Тест завершен:</strong> " . date('Y-m-d H:i:s') . "</p>";
?>
