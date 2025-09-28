<?php
/**
 * Простой тестовый API для проверки генератора персонажей
 */

header('Content-Type: application/json; charset=utf-8');

try {
    // Подключаем CharacterService
    require_once __DIR__ . '/../../app/Services/CharacterService.php';
    
    $characterService = new CharacterService();
    
    // Генерируем простого персонажа
    $character = $characterService->generateCharacter([
        'race' => 'human',
        'class' => 'fighter',
        'level' => 1,
        'gender' => 'male'
    ]);
    
    echo json_encode([
        'success' => true,
        'character' => $character,
        'message' => 'Персонаж успешно сгенерирован'
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_UNESCAPED_UNICODE);
}
?>
