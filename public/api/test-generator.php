<?php
/**
 * Тестовый endpoint для проверки генератора персонажей
 * Позволяет быстро протестировать работу без frontend
 */

header('Content-Type: application/json');

// Подключаем конфигурацию
require_once __DIR__ . '/../../config/config.php';

// Подключаем сервисы
require_once __DIR__ . '/../../app/Controllers/CharacterController.php';
require_once __DIR__ . '/../../app/Services/ImprovedCharacterService.php';

try {
    // Создаем контроллер
    $characterController = new CharacterController();
    
    // Тестовые параметры
    $testParams = [
        'race' => 'human',
        'class' => 'fighter', 
        'level' => 1,
        'gender' => 'random',
        'alignment' => 'random'
    ];
    
    // Генерируем персонажа
    $character = $characterController->generateCharacter($testParams);
    
    // Возвращаем результат
    echo json_encode([
        'success' => true,
        'message' => 'Генератор работает корректно!',
        'character' => $character,
        'test_params' => $testParams,
        'generated_at' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    // Логируем ошибку
    logMessage('ERROR', 'Test generator failed', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    
    // Возвращаем ошибку
    echo json_encode([
        'success' => false,
        'message' => 'Ошибка при тестировании генератора: ' . $e->getMessage(),
        'error_details' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
?>
