<?php
header('Content-Type: application/json');

try {
    echo "Testing Aarakocra API data...\n";
    
    // Подключаем необходимые файлы
    require_once __DIR__ . '/config/config.php';
    require_once __DIR__ . '/app/Services/dnd-api-service.php';
    
    echo "✓ Files loaded\n";
    
    // Создаем D&D API сервис
    $dnd_api = new DndApiService();
    echo "✓ D&D API Service created\n";
    
    // Тестируем получение данных расы ааракокра
    echo "Testing aarakocra race data...\n";
    $race_data = $dnd_api->getRaceData('aarakocra');
    
    echo json_encode([
        'success' => !isset($race_data['error']),
        'error' => $race_data['error'] ?? null,
        'message' => $race_data['message'] ?? null,
        'race_name' => $race_data['name'] ?? null,
        'race_size' => $race_data['size'] ?? null,
        'race_speed' => $race_data['speed'] ?? null,
        'has_traits' => !empty($race_data['traits'] ?? []),
        'traits_count' => count($race_data['traits'] ?? [])
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>
