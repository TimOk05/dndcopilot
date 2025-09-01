<?php
/**
 * Упрощенный API для генерации зелий D&D
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Простая проверка
try {
    $action = $_GET['action'] ?? 'test';
    
    switch ($action) {
        case 'test':
            $result = ['message' => 'API работает'];
            break;
            
        case 'rarities':
            $result = ['common', 'uncommon', 'rare', 'very rare', 'legendary'];
            break;
            
        case 'types':
            $result = ['Восстановление', 'Усиление', 'Защита', 'Иллюзия', 'Трансмутация', 'Некромантия', 'Прорицание'];
            break;
            
        case 'stats':
            // Статистика по зельям
            $all_potions = [
                ['rarity' => 'Common', 'type' => 'Восстановление'],
                ['rarity' => 'Common', 'type' => 'Усиление'],
                ['rarity' => 'Common', 'type' => 'Некромантия'],
                ['rarity' => 'Uncommon', 'type' => 'Восстановление'],
                ['rarity' => 'Uncommon', 'type' => 'Усиление'],
                ['rarity' => 'Uncommon', 'type' => 'Защита'],
                ['rarity' => 'Uncommon', 'type' => 'Иллюзия'],
                ['rarity' => 'Rare', 'type' => 'Восстановление'],
                ['rarity' => 'Rare', 'type' => 'Усиление'],
                ['rarity' => 'Rare', 'type' => 'Защита'],
                ['rarity' => 'Rare', 'type' => 'Трансмутация'],
                ['rarity' => 'Rare', 'type' => 'Некромантия'],
                ['rarity' => 'Rare', 'type' => 'Прорицание'],
                ['rarity' => 'Very Rare', 'type' => 'Иллюзия'],
                ['rarity' => 'Very Rare', 'type' => 'Трансмутация'],
                ['rarity' => 'Very Rare', 'type' => 'Прорицание'],
                ['rarity' => 'Legendary', 'type' => 'Защита'],
                ['rarity' => 'Legendary', 'type' => 'Некромантия']
            ];
            
            $rarity_stats = [];
            $type_stats = [];
            
            foreach ($all_potions as $potion) {
                $rarity = $potion['rarity'];
                $type = $potion['type'];
                
                $rarity_stats[$rarity] = ($rarity_stats[$rarity] ?? 0) + 1;
                $type_stats[$type] = ($type_stats[$type] ?? 0) + 1;
            }
            
            $result = [
                'total_potions' => count($all_potions),
                'rarity_distribution' => $rarity_stats,
                'type_distribution' => $type_stats,
                'rarities' => array_keys($rarity_stats),
                'types' => array_keys($type_stats)
            ];
            break;
            
        case 'random':
            // Расширенная генерация зелий
            $potions = [
                // Зелья восстановления
                [
                    'name' => 'Зелье лечения',
                    'description' => 'Восстанавливает 2d4+2 очков здоровья. При употреблении вы чувствуете тепло, разливающееся по телу.',
                    'rarity' => 'Common',
                    'type' => 'Восстановление',
                    'icon' => '🩹',
                    'color' => '#9b9b9b',
                    'value' => '50 золотых',
                    'weight' => '0.5 фунта',
                    'properties' => ['Питье', 'Магическое', 'Common']
                ],
                [
                    'name' => 'Большое зелье лечения',
                    'description' => 'Восстанавливает 4d4+4 очков здоровья. Более мощная версия обычного зелья лечения.',
                    'rarity' => 'Uncommon',
                    'type' => 'Восстановление',
                    'icon' => '🩹',
                    'color' => '#4caf50',
                    'value' => '150 золотых',
                    'weight' => '0.5 фунта',
                    'properties' => ['Питье', 'Магическое', 'Uncommon']
                ],
                [
                    'name' => 'Верховное зелье лечения',
                    'description' => 'Восстанавливает 8d4+8 очков здоровья. Одно из самых мощных зелий восстановления.',
                    'rarity' => 'Rare',
                    'type' => 'Восстановление',
                    'icon' => '🩹',
                    'color' => '#2196f3',
                    'value' => '500 золотых',
                    'weight' => '0.5 фунта',
                    'properties' => ['Питье', 'Магическое', 'Rare']
                ],
                
                // Зелья усиления
                [
                    'name' => 'Зелье силы',
                    'description' => 'Увеличивает силу на 1d4+1 на 1 час. Ваши мышцы наполняются невероятной силой.',
                    'rarity' => 'Common',
                    'type' => 'Усиление',
                    'icon' => '💪',
                    'color' => '#9b9b9b',
                    'value' => '75 золотых',
                    'weight' => '0.5 фунта',
                    'properties' => ['Питье', 'Магическое', 'Common']
                ],
                [
                    'name' => 'Зелье ловкости',
                    'description' => 'Увеличивает ловкость на 1d4+1 на 1 час. Вы становитесь невероятно проворным.',
                    'rarity' => 'Common',
                    'type' => 'Усиление',
                    'icon' => '💪',
                    'color' => '#9b9b9b',
                    'value' => '75 золотых',
                    'weight' => '0.5 фунта',
                    'properties' => ['Питье', 'Магическое', 'Common']
                ],
                [
                    'name' => 'Зелье героизма',
                    'description' => 'Дает временные очки здоровья и преимущество на спасброски от страха.',
                    'rarity' => 'Rare',
                    'type' => 'Усиление',
                    'icon' => '💪',
                    'color' => '#2196f3',
                    'value' => '400 золотых',
                    'weight' => '0.5 фунта',
                    'properties' => ['Питье', 'Магическое', 'Rare']
                ],
                
                // Зелья защиты
                [
                    'name' => 'Зелье сопротивления',
                    'description' => 'Дает сопротивление к одному типу урона на 1 час. Выберите тип при употреблении.',
                    'rarity' => 'Uncommon',
                    'type' => 'Защита',
                    'icon' => '🛡️',
                    'color' => '#4caf50',
                    'value' => '200 золотых',
                    'weight' => '0.5 фунта',
                    'properties' => ['Питье', 'Магическое', 'Uncommon']
                ],
                [
                    'name' => 'Зелье неуязвимости',
                    'description' => 'Дает сопротивление ко всем типам урона на 1 минуту. Крайне мощное зелье.',
                    'rarity' => 'Legendary',
                    'type' => 'Защита',
                    'icon' => '🛡️',
                    'color' => '#ff9800',
                    'value' => '5000 золотых',
                    'weight' => '0.5 фунта',
                    'properties' => ['Питье', 'Магическое', 'Legendary']
                ],
                
                // Зелья иллюзии
                [
                    'name' => 'Зелье невидимости',
                    'description' => 'Делает вас невидимым на 1 час. Действие нарушает невидимость.',
                    'rarity' => 'Very Rare',
                    'type' => 'Иллюзия',
                    'icon' => '👁️',
                    'color' => '#9c27b0',
                    'value' => '1000 золотых',
                    'weight' => '0.5 фунта',
                    'properties' => ['Питье', 'Магическое', 'Very Rare']
                ],
                [
                    'name' => 'Зелье маскировки',
                    'description' => 'Позволяет вам выглядеть как другой человек на 1 час.',
                    'rarity' => 'Uncommon',
                    'type' => 'Иллюзия',
                    'icon' => '👁️',
                    'color' => '#4caf50',
                    'value' => '150 золотых',
                    'weight' => '0.5 фунта',
                    'properties' => ['Питье', 'Магическое', 'Uncommon']
                ],
                
                // Зелья трансмутации
                [
                    'name' => 'Зелье полета',
                    'description' => 'Позволяет летать со скоростью 60 футов на 1 час.',
                    'rarity' => 'Very Rare',
                    'type' => 'Трансмутация',
                    'icon' => '🔄',
                    'color' => '#9c27b0',
                    'value' => '800 золотых',
                    'weight' => '0.5 фунта',
                    'properties' => ['Питье', 'Магическое', 'Very Rare']
                ],
                [
                    'name' => 'Зелье изменения размера',
                    'description' => 'Увеличивает или уменьшает ваш размер на 1d4+1 на 1 час.',
                    'rarity' => 'Rare',
                    'type' => 'Трансмутация',
                    'icon' => '🔄',
                    'color' => '#2196f3',
                    'value' => '300 золотых',
                    'weight' => '0.5 фунта',
                    'properties' => ['Питье', 'Магическое', 'Rare']
                ],
                
                // Зелья некромантии
                [
                    'name' => 'Зелье яда',
                    'description' => 'При употреблении наносит 1d4 урона ядом. Используется для отравления оружия.',
                    'rarity' => 'Common',
                    'type' => 'Некромантия',
                    'icon' => '💀',
                    'color' => '#9b9b9b',
                    'value' => '100 золотых',
                    'weight' => '0.5 фунта',
                    'properties' => ['Яд', 'Магическое', 'Common']
                ],
                [
                    'name' => 'Зелье смерти',
                    'description' => 'Крайне опасное зелье. При употреблении требует спасбросок от смерти.',
                    'rarity' => 'Legendary',
                    'type' => 'Некромантия',
                    'icon' => '💀',
                    'color' => '#ff9800',
                    'value' => '10000 золотых',
                    'weight' => '0.5 фунта',
                    'properties' => ['Смертельный', 'Магическое', 'Legendary']
                ],
                
                // Зелья прорицания
                [
                    'name' => 'Зелье истинного зрения',
                    'description' => 'Позволяет видеть сквозь иллюзии и невидимость на 1 час.',
                    'rarity' => 'Rare',
                    'type' => 'Прорицание',
                    'icon' => '🔮',
                    'color' => '#2196f3',
                    'value' => '350 золотых',
                    'weight' => '0.5 фунта',
                    'properties' => ['Питье', 'Магическое', 'Rare']
                ],
                [
                    'name' => 'Зелье предвидения',
                    'description' => 'Дает преимущество на инициативу и спасброски на 1 час.',
                    'rarity' => 'Very Rare',
                    'type' => 'Прорицание',
                    'icon' => '🔮',
                    'color' => '#9c27b0',
                    'value' => '750 золотых',
                    'weight' => '0.5 фунта',
                    'properties' => ['Питье', 'Магическое', 'Very Rare']
                ]
            ];
            
            $count = min((int)($_GET['count'] ?? 1), count($potions));
            $rarity = $_GET['rarity'] ?? '';
            $type = $_GET['type'] ?? '';
            
            // Фильтрация по редкости
            if ($rarity && $rarity !== '') {
                $potions = array_filter($potions, function($potion) use ($rarity) {
                    return strtolower($potion['rarity']) === strtolower($rarity);
                });
            }
            
            // Фильтрация по типу
            if ($type && $type !== '') {
                $potions = array_filter($potions, function($potion) use ($type) {
                    return $potion['type'] === $type;
                });
            }
            
            // Переиндексируем массив
            $potions = array_values($potions);
            
            // Выбираем случайные зелья
            if (count($potions) <= $count) {
                $result = $potions;
            } else {
                $result = [];
                $available = array_values($potions);
                
                for ($i = 0; $i < $count; $i++) {
                    $index = array_rand($available);
                    $result[] = $available[$index];
                    unset($available[$index]);
                }
            }
            break;
            
        default:
            throw new Exception('Неизвестное действие');
    }
    
    echo json_encode([
        'success' => true,
        'data' => $result,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
}
?>
