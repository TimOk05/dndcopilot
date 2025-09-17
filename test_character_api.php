<?php
header('Content-Type: application/json');

try {
    // Проверяем доступность файлов
    $files = [
        'config' => __DIR__ . '/config/config.php',
        'dnd_api' => __DIR__ . '/app/Services/dnd-api-service.php',
        'ai_service' => __DIR__ . '/app/Services/ai-service.php',
        'language_service' => __DIR__ . '/app/Services/language-service.php'
    ];
    
    $missing_files = [];
    foreach ($files as $name => $path) {
        if (!file_exists($path)) {
            $missing_files[] = $name . ': ' . $path;
        }
    }
    
    if (!empty($missing_files)) {
        throw new Exception("Missing files: " . implode(', ', $missing_files));
    }
    
    // Подключаем файлы
    require_once $files['config'];
    require_once $files['dnd_api'];
    require_once $files['ai_service'];
    require_once $files['language_service'];
    
    // Тестируем создание сервисов
    $dnd_api = new DndApiService();
    $ai_service = new AiService();
    $language_service = new LanguageService();
    
    echo json_encode([
        'success' => true,
        'message' => 'All services created successfully',
        'services' => [
            'dnd_api' => get_class($dnd_api),
            'ai_service' => get_class($ai_service),
            'language_service' => get_class($language_service)
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>
