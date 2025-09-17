<?php
// Простой тест PowerShell
echo "=== ПРОСТОЙ ТЕСТ POWERSHELL ===\n\n";

$url = 'https://www.dnd5eapi.co/api/monsters';
$temp_file = tempnam(sys_get_temp_dir(), 'ps_test_');

echo "URL: $url\n";
echo "Временный файл: $temp_file\n\n";

// Простая PowerShell команда
$ps_command = "powershell -Command \"Invoke-WebRequest -Uri '$url' -OutFile '$temp_file'\"";
echo "Команда: $ps_command\n\n";

$output = [];
$return_code = 0;
exec($ps_command, $output, $return_code);

echo "Код возврата: $return_code\n";
echo "Вывод: " . implode(' ', $output) . "\n\n";

if (file_exists($temp_file)) {
    echo "✅ Файл создан\n";
    $content = file_get_contents($temp_file);
    echo "Размер файла: " . strlen($content) . " байт\n";
    
    // Показываем первые 200 символов
    echo "Первые 200 символов:\n";
    echo substr($content, 0, 200) . "\n\n";
    
    // Пробуем декодировать JSON
    $data = json_decode($content, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "✅ JSON декодирован успешно\n";
        if (isset($data['count'])) {
            echo "Количество монстров: " . $data['count'] . "\n";
        }
    } else {
        echo "❌ Ошибка JSON: " . json_last_error_msg() . "\n";
    }
    
    unlink($temp_file);
} else {
    echo "❌ Файл не создан\n";
}
?>
