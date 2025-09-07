<?php
header('Content-Type: text/plain; charset=utf-8');

// Обработка бросков костей
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['fast_action'] ?? '';
    
    if ($action === 'dice_result') {
        $dice = $_POST['dice'] ?? '1d20';
        $label = $_POST['label'] ?? '';
        
        if (preg_match('/^(\d{1,2})d(\d{1,3})([+-]\d+)?$/', $dice, $m)) {
            $count = (int)$m[1];
            $sides = (int)$m[2];
            $modifier = isset($m[3]) ? (int)$m[3] : 0;
            
            $results = [];
            for ($i = 0; $i < $count; $i++) {
                $results[] = rand(1, $sides);
            }
            
            $sum = array_sum($results) + $modifier;
            
            // Формируем результат в зависимости от количества костей
            if ($count == 1) {
                $output = "🎲 Бросок: $dice\n📊 Результат: " . $results[0];
                if ($modifier != 0) {
                    $output .= " + $modifier = $sum";
                }
            } else {
                $output = "🎲 Бросок: $dice\n📊 Результаты: " . implode(', ', $results);
                if ($modifier != 0) {
                    $output .= " + $modifier";
                }
                $output .= "\n💎 Сумма: $sum";
            }
            
            if ($label) {
                $output .= "\n💬 Комментарий: $label";
            }
            
            echo $output;
        } else {
            echo 'Неверный формат костей! Используйте формат: 1d20, 2d6+3, 3d8-1';
        }
    } else {
        echo 'Неизвестное действие';
    }
} else {
    echo 'Только POST запросы поддерживаются';
}
?>
