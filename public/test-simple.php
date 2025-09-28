<?php
// Простой тест генерации персонажа
header('Content-Type: application/json; charset=utf-8');

try {
    require_once '../app/Services/CharacterService.php';
    
    $characterService = new CharacterService();
    
    // Генерируем простого персонажа
    $character = $characterService->generateCharacter([
        'race' => 'human',
        'class' => 'fighter',
        'level' => 1,
        'gender' => 'male',
        'alignment' => 'lawful_good'
    ]);
    
    echo json_encode([
        'success' => true,
        'character' => $character
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
