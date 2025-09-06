<?php
// Диагностика мобильной версии
echo "=== ДИАГНОСТИКА МОБИЛЬНОЙ ВЕРСИИ ===\n\n";

// Тест 1: Проверяем загрузку mobile-api.php
echo "1. Тестируем загрузку mobile-api.php...\n";
try {
    // Симулируем POST запрос
    $_POST['action'] = 'ai_chat';
    $_POST['question'] = 'Тест';
    
    ob_start();
    include 'mobile-api.php';
    $output = ob_get_clean();
    
    echo "Ответ: " . $output . "\n";
} catch (Exception $e) {
    echo "ОШИБКА: " . $e->getMessage() . "\n";
}

echo "\n2. Тестируем генерацию персонажа...\n";
try {
    $_POST['action'] = 'generate_character';
    $_POST['race'] = 'human';
    $_POST['class'] = 'fighter';
    $_POST['level'] = 1;
    
    ob_start();
    include 'mobile-api.php';
    $output = ob_get_clean();
    
    echo "Ответ: " . $output . "\n";
} catch (Exception $e) {
    echo "ОШИБКА: " . $e->getMessage() . "\n";
}

echo "\n=== ДИАГНОСТИКА ЗАВЕРШЕНА ===\n";
?>
