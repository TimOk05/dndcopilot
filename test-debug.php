<?php
// Отладочный файл для проверки работы генерации описаний
require_once 'config.php';

// Имитируем POST данные
$_POST = [
    'race' => 'elf',
    'class' => 'wizard',
    'level' => '3',
    'alignment' => 'neutral-good',
    'gender' => 'female',
    'use_ai' => 'off'
];

// Имитируем метод запроса
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SERVER_NAME'] = 'localhost';

echo "Отладка генерации описаний...\n\n";

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
    echo "Пол: " . $character['gender'] . "\n";
    echo "Профессия: " . $character['occupation'] . "\n\n";
    
    echo "ОПИСАНИЕ:\n";
    echo $character['description'] . "\n\n";
    
    echo "ПРЕДЫСТОРИЯ:\n";
    echo $character['background'] . "\n\n";
    
    // Проверяем маппинг рас
    $raceKey = strtolower($character['race']);
    $raceMapping = [
        'человек' => 'human',
        'эльф' => 'elf',
        'дварф' => 'dwarf',
        'полурослик' => 'halfling',
        'тифлинг' => 'tiefling',
        'драконорожденный' => 'dragonborn',
        'табакси' => 'tabaxi'
    ];
    
    $mappedRace = $raceMapping[$raceKey] ?? $raceKey;
    echo "Отладка маппинга рас:\n";
    echo "Исходная раса: '{$character['race']}'\n";
    echo "Ключ расы: '{$raceKey}'\n";
    echo "Маппинг: '{$mappedRace}'\n";
    
} else {
    echo "Ошибка: " . ($result['error'] ?? 'Неизвестная ошибка') . "\n";
}
?>
