<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Обработка preflight запросов
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../../config/config.php';
require_once 'generate-enemies.php';

try {
    // Проверяем метод запроса
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Только POST запросы разрешены');
    }
    
    // Получаем параметры
    $threat_level = $_POST['threat_level'] ?? 'medium';
    $count = (int)($_POST['count'] ?? 1);
    $enemy_type = $_POST['enemy_type'] ?? '';
    $environment = $_POST['environment'] ?? '';
    $use_ai = isset($_POST['use_ai']) ? ($_POST['use_ai'] === 'on') : true;
    
    // Валидация
    if ($count < 1 || $count > 5) {
        throw new Exception('Количество противников должно быть от 1 до 5');
    }
    
    $valid_levels = ['easy', 'medium', 'hard', 'deadly'];
    if (!in_array($threat_level, $valid_levels)) {
        throw new Exception('Недопустимый уровень сложности');
    }
    
    // Создаем генератор
    $generator = new EnemyGenerator();
    
    // Подготавливаем параметры для генератора
    $params = [
        'threat_level' => $threat_level,
        'count' => $count,
        'enemy_type' => $enemy_type,
        'environment' => $environment,
        'use_ai' => $use_ai ? 'on' : 'off'
    ];
    
    // Генерируем противников
    $result = $generator->generateEnemies($params);
    
    // Возвращаем результат
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    // Логируем ошибку
    logMessage('ERROR', 'Test enemies API error: ' . $e->getMessage());
    
    // Возвращаем ошибку
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
}
?>
