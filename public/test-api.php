<?php
// Простой тест API
header('Content-Type: application/json; charset=utf-8');

try {
    require_once '../app/Services/CharacterService.php';
    
    $characterService = new CharacterService();
    
    // Тестируем загрузку данных
    $races = $characterService->getRaces();
    $classes = $characterService->getClasses();
    
    echo json_encode([
        'success' => true,
        'races_count' => count($races),
        'classes_count' => count($classes),
        'first_race' => $races[0] ?? null,
        'first_class' => $classes[0] ?? null
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_UNESCAPED_UNICODE);
}
?>
