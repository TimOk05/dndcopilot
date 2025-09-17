<?php
// Простой тест для диагностики проблемы с API
header('Content-Type: application/json');

try {
    echo json_encode([
        'success' => true,
        'message' => 'Basic PHP test works',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>