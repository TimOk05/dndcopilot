<?php
/**
 * Проверка путей к файлам данных
 */

echo "<h1>Проверка путей к файлам данных</h1>";

$basePath = __DIR__ . '/../';

$files = [
    'Расы' => 'data/персонажи/расы/races.json',
    'Классы (директория)' => 'data/персонажи/классы/',
    'Имена' => 'data/персонажи/имена/имена.json',
    'Снаряжение' => 'data/персонажи/снаряжение/equipment.json',
    'Заклинания' => 'data/заклинания/заклинания.json',
    'Зелья' => 'data/зелья/зелья.json'
];

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Файл/Директория</th><th>Путь</th><th>Существует</th><th>Размер</th></tr>";

foreach ($files as $name => $relativePath) {
    $fullPath = $basePath . $relativePath;
    $exists = file_exists($fullPath);
    $size = $exists ? filesize($fullPath) : 0;
    
    echo "<tr>";
    echo "<td>$name</td>";
    echo "<td>$fullPath</td>";
    echo "<td>" . ($exists ? "✅ Да" : "❌ Нет") . "</td>";
    echo "<td>" . ($exists ? number_format($size) . " байт" : "-") . "</td>";
    echo "</tr>";
}

echo "</table>";

// Проверяем структуру директории классов
echo "<h2>Содержимое директории классов:</h2>";
$classesDir = $basePath . 'data/персонажи/классы/';
if (is_dir($classesDir)) {
    $classFiles = glob($classesDir . '*/*.json');
    echo "<ul>";
    foreach ($classFiles as $file) {
        echo "<li>" . basename($file) . " (" . number_format(filesize($file)) . " байт)</li>";
    }
    echo "</ul>";
} else {
    echo "<p>❌ Директория классов не найдена</p>";
}

// Проверяем структуру директории имен
echo "<h2>Содержимое директории имен:</h2>";
$namesDir = $basePath . 'data/персонажи/имена/';
if (is_dir($namesDir)) {
    $nameFiles = glob($namesDir . '*/*.json');
    echo "<ul>";
    foreach ($nameFiles as $file) {
        echo "<li>" . basename($file) . " (" . number_format(filesize($file)) . " байт)</li>";
    }
    echo "</ul>";
} else {
    echo "<p>❌ Директория имен не найдена</p>";
}

echo "<p><a href='debug-api.php'>← Вернуться к отладке API</a></p>";
?>
