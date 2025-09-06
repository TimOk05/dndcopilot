<?php
/**
 * Простой тест Language Service
 */

echo "<h1>🧪 Тест Language Service</h1>\n";

try {
    require_once 'api/language-service.php';
    echo "✅ Language Service загружен успешно<br>\n";
    
    $lang_service = new LanguageService();
    echo "✅ Language Service инициализирован<br>\n";
    
    echo "Текущий язык: " . $lang_service->getCurrentLanguage() . "<br>\n";
    echo "Поддерживаемые языки: " . implode(', ', $lang_service->getSupportedLanguages()) . "<br>\n";
    
    echo "✅ Все тесты прошли успешно!<br>\n";
    
} catch (Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "<br>\n";
    echo "Стек вызовов:<br>\n";
    echo "<pre>" . $e->getTraceAsString() . "</pre>\n";
}
?>
