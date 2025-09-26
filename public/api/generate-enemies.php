<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../../config/config.php';

// Простая генерация противников без AI
function generateEnemies($threatLevel, $count, $enemyType = '', $environment = '') {
    $enemies = [];
    
    // Базовые данные противников по уровню угрозы
    $enemyData = [
        'easy' => [
            ['name' => 'Гоблин', 'cr' => '1/4', 'hp' => '7', 'ac' => '15', 'speed' => '30', 'str' => '8', 'dex' => '14', 'con' => '10', 'int' => '10', 'wis' => '8', 'cha' => '8'],
            ['name' => 'Кобольд', 'cr' => '1/8', 'hp' => '5', 'ac' => '12', 'speed' => '30', 'str' => '7', 'dex' => '15', 'con' => '9', 'int' => '8', 'wis' => '7', 'cha' => '8'],
            ['name' => 'Волк', 'cr' => '1/4', 'hp' => '11', 'ac' => '13', 'speed' => '40', 'str' => '12', 'dex' => '15', 'con' => '12', 'int' => '3', 'wis' => '12', 'cha' => '6'],
            ['name' => 'Скелет', 'cr' => '1/4', 'hp' => '13', 'ac' => '13', 'speed' => '30', 'str' => '10', 'dex' => '14', 'con' => '15', 'int' => '6', 'wis' => '8', 'cha' => '5']
        ],
        'medium' => [
            ['name' => 'Орк', 'cr' => '1/2', 'hp' => '15', 'ac' => '13', 'speed' => '30', 'str' => '16', 'dex' => '12', 'con' => '16', 'int' => '7', 'wis' => '11', 'cha' => '10'],
            ['name' => 'Хобгоблин', 'cr' => '1/2', 'hp' => '11', 'ac' => '18', 'speed' => '30', 'str' => '13', 'dex' => '12', 'con' => '12', 'int' => '10', 'wis' => '10', 'cha' => '9'],
            ['name' => 'Медведь', 'cr' => '1', 'hp' => '34', 'ac' => '11', 'speed' => '40', 'str' => '19', 'dex' => '10', 'con' => '16', 'int' => '2', 'wis' => '13', 'cha' => '7'],
            ['name' => 'Зомби', 'cr' => '1/4', 'hp' => '22', 'ac' => '8', 'speed' => '20', 'str' => '13', 'dex' => '6', 'con' => '16', 'int' => '3', 'wis' => '6', 'cha' => '5']
        ],
        'hard' => [
            ['name' => 'Огр', 'cr' => '2', 'hp' => '59', 'ac' => '11', 'speed' => '40', 'str' => '19', 'dex' => '8', 'con' => '16', 'int' => '5', 'wis' => '7', 'cha' => '7'],
            ['name' => 'Тролль', 'cr' => '5', 'hp' => '84', 'ac' => '15', 'speed' => '30', 'str' => '18', 'dex' => '13', 'con' => '20', 'int' => '7', 'wis' => '9', 'cha' => '7'],
            ['name' => 'Вампир-спаун', 'cr' => '4', 'hp' => '82', 'ac' => '15', 'speed' => '30', 'str' => '16', 'dex' => '16', 'con' => '15', 'int' => '11', 'wis' => '10', 'cha' => '10'],
            ['name' => 'Молодой дракон', 'cr' => '6', 'hp' => '75', 'ac' => '17', 'speed' => '40', 'str' => '19', 'dex' => '10', 'con' => '17', 'int' => '12', 'wis' => '11', 'cha' => '15']
        ],
        'deadly' => [
            ['name' => 'Взрослый дракон', 'cr' => '13', 'hp' => '200', 'ac' => '18', 'speed' => '40', 'str' => '23', 'dex' => '10', 'con' => '21', 'int' => '14', 'wis' => '13', 'cha' => '17'],
            ['name' => 'Лич', 'cr' => '21', 'hp' => '135', 'ac' => '17', 'speed' => '30', 'str' => '11', 'dex' => '16', 'con' => '16', 'int' => '20', 'wis' => '14', 'cha' => '16'],
            ['name' => 'Древний дракон', 'cr' => '24', 'hp' => '546', 'ac' => '22', 'speed' => '40', 'str' => '30', 'dex' => '10', 'con' => '29', 'int' => '18', 'wis' => '15', 'cha' => '23'],
            ['name' => 'Тарраск', 'cr' => '30', 'hp' => '676', 'ac' => '25', 'speed' => '40', 'str' => '30', 'dex' => '11', 'con' => '30', 'int' => '3', 'wis' => '11', 'cha' => '11']
        ]
    ];
    
    // Выбираем случайных противников
    $availableEnemies = $enemyData[$threatLevel] ?? $enemyData['easy'];
    
    for ($i = 0; $i < $count; $i++) {
        $enemy = $availableEnemies[array_rand($availableEnemies)];
        $enemies[] = $enemy;
    }
    
    return $enemies;
}

// Обработка POST запроса
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $threatLevel = $_POST['threat_level'] ?? 'easy';
        $count = intval($_POST['count'] ?? 1);
        $enemyType = $_POST['enemy_type'] ?? '';
        $environment = $_POST['environment'] ?? '';
        
        // Ограничиваем количество противников
        $count = max(1, min(10, $count));
        
        $enemies = generateEnemies($threatLevel, $count, $enemyType, $environment);
        
        echo json_encode([
            'success' => true,
            'enemies' => $enemies,
            'count' => count($enemies),
            'threat_level' => $threatLevel
        ]);
        
    } catch (Exception $e) {
        logMessage('Enemy generation error: ' . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => 'Ошибка генерации противников: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Метод не поддерживается'
    ]);
}
?>
