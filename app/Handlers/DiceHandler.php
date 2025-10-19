<?php
/**
 * Обработчик для работы с костями
 */

class DiceHandler {
    
    /**
     * Бросает кости и возвращает результат
     */
    public static function rollDice($dice, $label = '') {
        // Валидация формата костей (например, "1d20", "2d6", "3d4")
        if (!preg_match('/^(\d{1,2})d(\d{1,3})$/', $dice, $matches)) {
            return [
                'success' => false,
                'message' => 'Неверный формат костей! Используйте формат: количествоdстороны (например, 1d20, 2d6)'
            ];
        }
        
        $count = (int)$matches[1];
        $sides = (int)$matches[2];
        
        // Ограничения для безопасности
        if ($count > 20 || $sides > 100) {
            return [
                'success' => false,
                'message' => 'Слишком много костей или сторон! Максимум: 20 костей, 100 сторон'
            ];
        }
        
        // Бросаем кости
        $results = [];
        for ($i = 0; $i < $count; $i++) {
            $results[] = rand(1, $sides);
        }
        
        $sum = array_sum($results);
        
        // Формируем результат
        $output = "🎲 Бросок: $dice\n";
        
        if ($count == 1) {
            $output .= "📊 Результат: " . $results[0];
        } else {
            $output .= "📊 Результаты: " . implode(', ', $results) . "\n";
            $output .= "💎 Сумма: $sum";
        }
        
        if ($label) {
            $output .= "\n💬 Комментарий: $label";
        }
        
        return [
            'success' => true,
            'message' => $output,
            'results' => $results,
            'sum' => $sum,
            'dice' => $dice,
            'label' => $label
        ];
    }
    
    /**
     * Бросает кость с модификатором
     */
    public static function rollWithModifier($dice, $modifier = 0, $label = '') {
        $result = self::rollDice($dice, $label);
        
        if (!$result['success']) {
            return $result;
        }
        
        $total = $result['sum'] + $modifier;
        $result['message'] .= "\n🎯 Модификатор: " . ($modifier >= 0 ? '+' : '') . $modifier;
        $result['message'] .= "\n🏆 Итого: $total";
        $result['total'] = $total;
        $result['modifier'] = $modifier;
        
        return $result;
    }
    
    /**
     * Бросает кости для инициативы
     */
    public static function rollInitiative($modifier = 0, $label = '') {
        return self::rollWithModifier('1d20', $modifier, $label ?: 'Инициатива');
    }
    
    /**
     * Бросает кости для атаки
     */
    public static function rollAttack($modifier = 0, $label = '') {
        return self::rollWithModifier('1d20', $modifier, $label ?: 'Атака');
    }
    
    /**
     * Бросает кости для урона
     */
    public static function rollDamage($dice, $modifier = 0, $label = '') {
        return self::rollWithModifier($dice, $modifier, $label ?: 'Урон');
    }
}
?>
