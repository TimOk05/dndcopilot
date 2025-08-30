<?php
// Тестовый файл для проверки улучшенного качества генерации персонажей
require_once 'config.php';

// Имитируем POST данные для разных персонажей
$testCases = [
    [
        'race' => 'elf',
        'class' => 'wizard',
        'level' => '3',
        'alignment' => 'neutral-good',
        'gender' => 'female',
        'use_ai' => 'off'
    ],
    [
        'race' => 'human',
        'class' => 'fighter',
        'level' => '5',
        'alignment' => 'lawful-good',
        'gender' => 'male',
        'use_ai' => 'off'
    ],
    [
        'race' => 'dwarf',
        'class' => 'fighter',
        'level' => '2',
        'alignment' => 'neutral',
        'gender' => 'male',
        'use_ai' => 'off'
    ]
];

// Имитируем метод запроса
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SERVER_NAME'] = 'localhost';

echo "Тестируем улучшенное качество генерации персонажей...\n\n";

foreach ($testCases as $i => $testData) {
    echo "=== ТЕСТ " . ($i + 1) . " ===\n";
    echo "Параметры: " . $testData['race'] . " " . $testData['class'] . " (" . $testData['gender'] . ")\n\n";
    
    // Устанавливаем POST данные
    $_POST = $testData;
    
    // Подключаем генератор
    ob_start();
    require_once 'api/generate-characters.php';
    $output = ob_get_clean();
    
    // Парсим JSON ответ
    $result = json_decode($output, true);
    
    if ($result && $result['success']) {
        $character = $result['npc'];
        echo "Имя: " . $character['name'] . "\n";
        echo "Раса: " . $character['race'] . "\n";
        echo "Класс: " . $character['class'] . "\n";
        echo "Профессия: " . $character['occupation'] . "\n\n";
        
        echo "ОПИСАНИЕ:\n";
        echo $character['description'] . "\n\n";
        
        echo "ПРЕДЫСТОРИЯ:\n";
        echo $character['background'] . "\n\n";
        
        echo "Характеристики: СИЛ " . $character['abilities']['str'] . 
             ", ЛОВ " . $character['abilities']['dex'] . 
             ", ТЕЛ " . $character['abilities']['con'] . 
             ", ИНТ " . $character['abilities']['int'] . 
             ", МДР " . $character['abilities']['wis'] . 
             ", ХАР " . $character['abilities']['cha'] . "\n";
        
        echo "Хиты: " . $character['hit_points'] . 
             ", КД: " . $character['armor_class'] . 
             ", Урон: " . $character['damage'] . "\n\n";
    } else {
        echo "Ошибка: " . ($result['error'] ?? 'Неизвестная ошибка') . "\n\n";
    }
    
    echo "================================\n\n";
}

echo "Тест завершен!\n";
?>
