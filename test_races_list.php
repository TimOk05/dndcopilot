<?php
header('Content-Type: application/json');

try {
    echo "Testing available races in D&D API...\n";
    
    // Подключаем необходимые файлы
    require_once __DIR__ . '/config/config.php';
    require_once __DIR__ . '/app/Services/dnd-api-service.php';
    
    echo "✓ Files loaded\n";
    
    // Создаем D&D API сервис
    $dnd_api = new DndApiService();
    echo "✓ D&D API Service created\n";
    
    // Тестируем получение списка рас
    echo "Testing races list...\n";
    $races_list = $dnd_api->getAllRaces();
    
    if (isset($races_list['error'])) {
        echo json_encode([
            'success' => false,
            'error' => $races_list['error'],
            'message' => $races_list['message'] ?? null
        ], JSON_UNESCAPED_UNICODE);
    } else {
        $races = $races_list['data'] ?? [];
        $race_names = array_column($races, 'name');
        
        echo json_encode([
            'success' => true,
            'total_races' => count($races),
            'race_names' => $race_names,
            'has_aarakocra' => in_array('Aarakocra', $race_names) || in_array('aarakocra', $race_names),
            'sample_races' => array_slice($race_names, 0, 10)
        ], JSON_UNESCAPED_UNICODE);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>
