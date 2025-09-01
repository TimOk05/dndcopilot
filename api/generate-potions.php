<?php
/**
 * API для генерации зелий D&D
 * Генерирует зелья различных типов и редкости
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

class PotionGenerator {
    private $potions = [
        'common' => [
            'Зелье лечения' => [
                'description' => 'Восстанавливает 2d4+2 хитов',
                'rarity' => 'Обычное',
                'type' => 'Восстановление',
                'value' => '50 золотых',
                'weight' => '0.5 фунта',
                'properties' => ['Питье', 'Магическое']
            ],
            'Зелье прыгучести' => [
                'description' => 'Увеличивает прыжок в 3 раза на 1 минуту',
                'rarity' => 'Обычное',
                'type' => 'Усиление',
                'value' => '50 золотых',
                'weight' => '0.5 фунта',
                'properties' => ['Питье', 'Магическое']
            ],
            'Зелье сопротивления огню' => [
                'description' => 'Дает сопротивление к огненному урону на 1 час',
                'rarity' => 'Обычное',
                'type' => 'Защита',
                'value' => '50 золотых',
                'weight' => '0.5 фунта',
                'properties' => ['Питье', 'Магическое']
            ],
            'Зелье сопротивления холоду' => [
                'description' => 'Дает сопротивление к холодному урону на 1 час',
                'rarity' => 'Обычное',
                'type' => 'Защита',
                'value' => '50 золотых',
                'weight' => '0.5 фунта',
                'properties' => ['Питье', 'Магическое']
            ],
            'Зелье сопротивления электричеству' => [
                'description' => 'Дает сопротивление к электрическому урону на 1 час',
                'rarity' => 'Обычное',
                'type' => 'Защита',
                'value' => '50 золотых',
                'weight' => '0.5 фунта',
                'properties' => ['Питье', 'Магическое']
            ],
            'Зелье ясновидения' => [
                'description' => 'Позволяет видеть в темноте на 60 футов на 1 час',
                'rarity' => 'Обычное',
                'type' => 'Прорицание',
                'value' => '50 золотых',
                'weight' => '0.5 фунта',
                'properties' => ['Питье', 'Магическое']
            ],
            'Зелье выносливости' => [
                'description' => 'Увеличивает выносливость на 1d4+1 на 1 час',
                'rarity' => 'Обычное',
                'type' => 'Усиление',
                'value' => '50 золотых',
                'weight' => '0.5 фунта',
                'properties' => ['Питье', 'Магическое']
            ]
        ],
        'uncommon' => [
            'Зелье невидимости' => [
                'description' => 'Делает невидимым на 1 час или до атаки',
                'rarity' => 'Необычное',
                'type' => 'Иллюзия',
                'value' => '100 золотых',
                'weight' => '0.5 фунта',
                'properties' => ['Питье', 'Магическое']
            ],
            'Зелье полета' => [
                'description' => 'Позволяет летать со скоростью 60 футов на 1 час',
                'rarity' => 'Необычное',
                'type' => 'Трансмутация',
                'value' => '100 золотых',
                'weight' => '0.5 фунта',
                'properties' => ['Питье', 'Магическое']
            ],
            'Зелье гигантской силы' => [
                'description' => 'Увеличивает силу до 21 на 1 час',
                'rarity' => 'Необычное',
                'type' => 'Усиление',
                'value' => '100 золотых',
                'weight' => '0.5 фунта',
                'properties' => ['Питье', 'Магическое']
            ],
            'Зелье сопротивления яду' => [
                'description' => 'Дает сопротивление к ядовитому урону на 1 час',
                'rarity' => 'Необычное',
                'type' => 'Защита',
                'value' => '100 золотых',
                'weight' => '0.5 фунта',
                'properties' => ['Питье', 'Магическое']
            ],
            'Зелье скорости' => [
                'description' => 'Увеличивает скорость на 10 футов на 1 минуту',
                'rarity' => 'Необычное',
                'type' => 'Усиление',
                'value' => '100 золотых',
                'weight' => '0.5 фунта',
                'properties' => ['Питье', 'Магическое']
            ],
            'Зелье понимания языков' => [
                'description' => 'Позволяет понимать все разговорные языки на 1 час',
                'rarity' => 'Необычное',
                'type' => 'Прорицание',
                'value' => '100 золотых',
                'weight' => '0.5 фунта',
                'properties' => ['Питье', 'Магическое']
            ],
            'Зелье изменения размера' => [
                'description' => 'Уменьшает или увеличивает размер в 2 раза на 1 час',
                'rarity' => 'Необычное',
                'type' => 'Трансмутация',
                'value' => '100 золотых',
                'weight' => '0.5 фунта',
                'properties' => ['Питье', 'Магическое']
            ]
        ],
        'rare' => [
            'Зелье великого лечения' => [
                'description' => 'Восстанавливает 8d4+8 хитов',
                'rarity' => 'Редкое',
                'type' => 'Восстановление',
                'value' => '500 золотых',
                'weight' => '0.5 фунта',
                'properties' => ['Питье', 'Магическое']
            ],
            'Зелье долголетия' => [
                'description' => 'Увеличивает возраст на 1d6+6 лет',
                'rarity' => 'Редкое',
                'type' => 'Трансмутация',
                'value' => '500 золотых',
                'weight' => '0.5 фунта',
                'properties' => ['Питье', 'Магическое']
            ],
            'Зелье сопротивления магии' => [
                'description' => 'Дает преимущество к спасброскам от заклинаний на 1 час',
                'rarity' => 'Редкое',
                'type' => 'Защита',
                'value' => '500 золотых',
                'weight' => '0.5 фунта',
                'properties' => ['Питье', 'Магическое']
            ],
            'Зелье неуязвимости' => [
                'description' => 'Дает сопротивление ко всем видам урона на 1 минуту',
                'rarity' => 'Редкое',
                'type' => 'Защита',
                'value' => '500 золотых',
                'weight' => '0.5 фунта',
                'properties' => ['Питье', 'Магическое']
            ],
            'Зелье героизма' => [
                'description' => 'Дает иммунитет к страху и временные хиты на 1 час',
                'rarity' => 'Редкое',
                'type' => 'Усиление',
                'value' => '500 золотых',
                'weight' => '0.5 фунта',
                'properties' => ['Питье', 'Магическое']
            ]
        ],
        'very_rare' => [
            'Зелье истинного воскрешения' => [
                'description' => 'Воскрешает мертвого существа с полным восстановлением',
                'rarity' => 'Очень редкое',
                'type' => 'Некромантия',
                'value' => '50000 золотых',
                'weight' => '0.5 фунта',
                'properties' => ['Питье', 'Магическое', 'Легендарное']
            ],
            'Зелье бессмертия' => [
                'description' => 'Делает существо неуязвимым к старению на 100 лет',
                'rarity' => 'Очень редкое',
                'type' => 'Трансмутация',
                'value' => '50000 золотых',
                'weight' => '0.5 фунта',
                'properties' => ['Питье', 'Магическое', 'Легендарное']
            ],
            'Зелье абсолютной защиты' => [
                'description' => 'Дает иммунитет ко всем видам урона на 1 час',
                'rarity' => 'Очень редкое',
                'type' => 'Защита',
                'value' => '50000 золотых',
                'weight' => '0.5 фунта',
                'properties' => ['Питье', 'Магическое', 'Легендарное']
            ],
            'Зелье всеведения' => [
                'description' => 'Дает знание всех языков и понимание всех существ на 1 час',
                'rarity' => 'Очень редкое',
                'type' => 'Прорицание',
                'value' => '50000 золотых',
                'weight' => '0.5 фунта',
                'properties' => ['Питье', 'Магическое', 'Легендарное']
            ],
            'Зелье божественной силы' => [
                'description' => 'Увеличивает все характеристики до 24 на 1 час',
                'rarity' => 'Очень редкое',
                'type' => 'Усиление',
                'value' => '50000 золотых',
                'weight' => '0.5 фунта',
                'properties' => ['Питье', 'Магическое', 'Легендарное']
            ]
        ],
        'legendary' => [
            'Зелье вечной жизни' => [
                'description' => 'Делает существо бессмертным (не стареет, не умирает от старости)',
                'rarity' => 'Легендарное',
                'type' => 'Трансмутация',
                'value' => '100000 золотых',
                'weight' => '0.5 фунта',
                'properties' => ['Питье', 'Магическое', 'Легендарное', 'Уникальное']
            ],
            'Зелье абсолютного могущества' => [
                'description' => 'Дает 20 уровень во всех классах на 24 часа',
                'rarity' => 'Легендарное',
                'type' => 'Усиление',
                'value' => '100000 золотых',
                'weight' => '0.5 фунта',
                'properties' => ['Питье', 'Магическое', 'Легендарное', 'Уникальное']
            ],
            'Зелье творения' => [
                'description' => 'Позволяет создать любой неживой предмет размером до 10x10x10 футов',
                'rarity' => 'Легендарное',
                'type' => 'Трансмутация',
                'value' => '100000 золотых',
                'weight' => '0.5 фунта',
                'properties' => ['Питье', 'Магическое', 'Легендарное', 'Уникальное']
            ],
            'Зелье времени' => [
                'description' => 'Позволяет путешествовать во времени на 1d100 лет назад или вперед',
                'rarity' => 'Легендарное',
                'type' => 'Прорицание',
                'value' => '100000 золотых',
                'weight' => '0.5 фунта',
                'properties' => ['Питье', 'Магическое', 'Легендарное', 'Уникальное']
            ],
            'Зелье судьбы' => [
                'description' => 'Позволяет изменить результат любого броска костей в прошлом или будущем',
                'rarity' => 'Легендарное',
                'type' => 'Прорицание',
                'value' => '100000 золотых',
                'weight' => '0.5 фунта',
                'properties' => ['Питье', 'Магическое', 'Легендарное', 'Уникальное']
            ]
        ]
    ];

    private $potionTypes = [
        'Восстановление' => '🩹',
        'Усиление' => '💪',
        'Защита' => '🛡️',
        'Иллюзия' => '👁️',
        'Трансмутация' => '🔄',
        'Некромантия' => '💀',
        'Прорицание' => '🔮'
    ];

    private $rarityColors = [
        'Обычное' => '#9b9b9b',
        'Необычное' => '#4caf50',
        'Редкое' => '#2196f3',
        'Очень редкое' => '#9c27b0',
        'Легендарное' => '#ff9800'
    ];

    public function generateRandomPotion($rarity = null, $type = null) {
        $availablePotions = [];
        
        // Если указана редкость, фильтруем по ней
        if ($rarity && isset($this->potions[$rarity])) {
            $availablePotions = $this->potions[$rarity];
        } else {
            // Собираем все зелья
            foreach ($this->potions as $rarityPotions) {
                $availablePotions = array_merge($availablePotions, $rarityPotions);
            }
        }
        
        // Если указан тип, фильтруем по нему
        if ($type) {
            $filteredPotions = [];
            foreach ($availablePotions as $name => $data) {
                if ($data['type'] === $type) {
                    $filteredPotions[$name] = $data;
                }
            }
            $availablePotions = $filteredPotions;
        }
        
        // Если нет доступных зелий после фильтрации, возвращаем случайное
        if (empty($availablePotions)) {
            $rarityKey = array_rand($this->potions);
            $availablePotions = $this->potions[$rarityKey];
        }
        
        $potionName = array_rand($availablePotions);
        $potionData = $availablePotions[$potionName];
        
        return [
            'name' => $potionName,
            'description' => $potionData['description'],
            'rarity' => $potionData['rarity'],
            'type' => $potionData['type'],
            'value' => $potionData['value'],
            'weight' => $potionData['weight'],
            'properties' => $potionData['properties'],
            'icon' => $this->potionTypes[$potionData['type']] ?? '🧪',
            'color' => $this->rarityColors[$potionData['rarity']] ?? '#9b9b9b'
        ];
    }

    public function generateMultiplePotions($count = 1, $rarity = null, $type = null) {
        $potions = [];
        for ($i = 0; $i < $count; $i++) {
            $potions[] = $this->generateRandomPotion($rarity, $type);
        }
        return $potions;
    }

    public function getPotionByType($type) {
        $foundPotions = [];
        foreach ($this->potions as $rarity => $rarityPotions) {
            foreach ($rarityPotions as $name => $data) {
                if ($data['type'] === $type) {
                    $foundPotions[] = [
                        'name' => $name,
                        'description' => $data['description'],
                        'rarity' => $data['rarity'],
                        'type' => $data['type'],
                        'value' => $data['value'],
                        'weight' => $data['weight'],
                        'properties' => $data['properties'],
                        'icon' => $this->potionTypes[$data['type']] ?? '🧪',
                        'color' => $this->rarityColors[$data['rarity']] ?? '#9b9b9b'
                    ];
                }
            }
        }
        return $foundPotions;
    }

    public function getAvailableRarities() {
        return array_keys($this->potions);
    }

    public function getAvailableTypes() {
        return array_keys($this->potionTypes);
    }
}

// Обработка запросов
$generator = new PotionGenerator();

$action = $_GET['action'] ?? 'random';
$count = (int)($_GET['count'] ?? 1);
$rarity = $_GET['rarity'] ?? null;
$type = $_GET['type'] ?? null;

try {
    switch ($action) {
        case 'random':
            if ($count > 10) $count = 10; // Ограничиваем количество
            $result = $generator->generateMultiplePotions($count, $rarity, $type);
            break;
            
        case 'by_type':
            if (!$type) {
                throw new Exception('Тип зелья не указан');
            }
            $result = $generator->getPotionByType($type);
            break;
            
        case 'rarities':
            $result = $generator->getAvailableRarities();
            break;
            
        case 'types':
            $result = $generator->getAvailableTypes();
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
