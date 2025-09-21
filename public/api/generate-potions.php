<?php
/**
 * API для генерации зелий D&D 5e
 * Поддерживает фильтрацию по редкости и типу
 */

header('Content-Type: application/json');

// Подключаем конфигурацию
require_once __DIR__ . '/../../config/config.php';

// Подключаем сервис зелий
require_once __DIR__ . '/../../app/Services/PotionService.php';

// Обработка POST запросов
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Получаем данные из запроса
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            $input = $_POST;
        }
        
        // Валидация входных данных
        $rarity = $input['rarity'] ?? null;
        $count = isset($input['count']) ? (int)$input['count'] : 1;
        
        // Валидация
        $errors = [];
        
        if ($count < 1 || $count > 10) {
            $errors[] = 'Количество зелий должно быть от 1 до 10';
        }
        
        if (!empty($errors)) {
            echo json_encode([
                'success' => false,
                'message' => 'Ошибки валидации',
                'errors' => $errors
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // Создаем экземпляр сервиса
        $potionService = new PotionService();
        
        // Генерируем зелья (все зелья и масла)
        $potions = $potionService->generatePotions($rarity, 'potion', $count);
        
        // Добавляем локализованные названия
        foreach ($potions as &$potion) {
            $potion['rarity_localized'] = $potionService->getRarityLocalized($potion['rarity']);
            $potion['type_localized'] = $potionService->getTypeLocalized($potion['type']);
        }
        
        // Логируем успешную генерацию
        logMessage('INFO', 'Potions generated successfully', [
            'rarity' => $rarity,
            'count' => $count,
            'generated_count' => count($potions)
        ]);
        
        // Возвращаем результат
        echo json_encode([
            'success' => true,
            'potions' => $potions,
            'meta' => [
                'rarity' => $rarity,
                'requested_count' => $count,
                'generated_count' => count($potions)
            ]
        ], JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        // Логируем ошибку
        logMessage('ERROR', 'Potion generation failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        // Возвращаем ошибку
        echo json_encode([
            'success' => false,
            'message' => 'Ошибка при генерации зелий: ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
} else {
    // Если это не POST запрос, возвращаем ошибку
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Метод не разрешен. Используйте POST запрос.'
    ], JSON_UNESCAPED_UNICODE);
}
?>
