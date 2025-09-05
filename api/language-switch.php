<?php
/**
 * Language Switch API - API для переключения языков
 * Поддерживает английский (по умолчанию) и русский языки
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/language-service.php';

// Обработка OPTIONS запроса для CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    $language_service = LanguageService::getInstance();
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Получение текущего языка
        $current_language = $language_service->getCurrentLanguage();
        $supported_languages = $language_service->getSupportedLanguages();
        
        echo json_encode([
            'success' => true,
            'current_language' => $current_language,
            'language_name' => $language_service->getLanguageName($current_language),
            'supported_languages' => $supported_languages,
            'language_names' => [
                'en' => $language_service->getLanguageName('en'),
                'ru' => $language_service->getLanguageName('ru')
            ]
        ], JSON_UNESCAPED_UNICODE);
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Переключение языка
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['language'])) {
            throw new Exception('Language parameter is required');
        }
        
        $new_language = $input['language'];
        
        if ($language_service->setLanguage($new_language)) {
            echo json_encode([
                'success' => true,
                'message' => 'Language switched successfully',
                'current_language' => $new_language,
                'language_name' => $language_service->getLanguageName($new_language)
            ], JSON_UNESCAPED_UNICODE);
        } else {
            throw new Exception('Invalid language specified');
        }
        
    } else {
        throw new Exception('Method not allowed');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
