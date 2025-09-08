<?php

require_once __DIR__ . '/ImprovedAiService.php';
require_once __DIR__ . '/TranslationService.php';
require_once __DIR__ . '/CacheService.php';

/**
 * Генератор зелий для D&D 5e
 */
class PotionGenerator {
    private $aiService;
    private $translationService;
    private $cacheService;
    
    public function __construct() {
        $this->aiService = new ImprovedAiService();
        $this->translationService = TranslationService::getInstance();
        $this->cacheService = new CacheService();
        
        logMessage('INFO', 'PotionGenerator: Инициализирован');
    }
    
    /**
     * Генерация зелий
     */
    public function generatePotions($params) {
        try {
            $rarity = $params['rarity'] ?? 'common';
            $type = $params['type'] ?? 'healing';
            $language = $params['language'] ?? 'ru';
            $use_ai = isset($params['use_ai']) ? ($params['use_ai'] === 'on') : true;
            
            logMessage('INFO', "PotionGenerator: Генерация зелий. Редкость: $rarity, Тип: $type, AI: " . ($use_ai ? 'Вкл' : 'Выкл'));
            
            // Генерируем зелья
            $potions = $this->generatePotionData($rarity, $type, $use_ai);
            
            if (empty($potions)) {
                throw new Exception('Не удалось сгенерировать зелья');
            }
            
            return [
                'success' => true,
                'potions' => $potions,
                'count' => count($potions),
                'filters' => [
                    'rarity' => $rarity,
                    'type' => $type
                ]
            ];
            
        } catch (Exception $e) {
            logMessage('ERROR', "PotionGenerator: Ошибка генерации: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Генерация данных о зельях
     */
    private function generatePotionData($rarity, $type, $use_ai) {
        $potions = [];
        
        // Базовые зелья по типу
        $basePotions = $this->getBasePotions($type);
        
        foreach ($basePotions as $basePotion) {
            $potion = [
                'name' => $basePotion['name'],
                'name_ru' => $basePotion['name'],
                'rarity' => $rarity,
                'type' => $type,
                'description' => $basePotion['description'],
                'description_ru' => $basePotion['description'],
                'price' => $this->calculatePrice($rarity),
                'weight' => $this->calculateWeight($type),
                'duration' => $this->getDuration($type),
                'effect' => $basePotion['effect']
            ];
            
            // Добавляем AI-генерированные детали если включено
            if ($use_ai) {
                $aiDetails = $this->generateAiDetails($potion);
                if ($aiDetails) {
                    $potion = array_merge($potion, $aiDetails);
                }
            }
            
            $potions[] = $potion;
        }
        
        return $potions;
    }
    
    /**
     * Получение базовых зелий по типу
     */
    private function getBasePotions($type) {
        $potions = [
            'healing' => [
                [
                    'name' => 'Potion of Healing',
                    'description' => 'A magical potion that restores hit points when consumed.',
                    'effect' => 'Restores 2d4+2 hit points'
                ],
                [
                    'name' => 'Greater Healing Potion',
                    'description' => 'A more powerful healing potion.',
                    'effect' => 'Restores 4d4+4 hit points'
                ],
                [
                    'name' => 'Superior Healing Potion',
                    'description' => 'An extremely powerful healing potion.',
                    'effect' => 'Restores 8d4+8 hit points'
                ]
            ],
            'mana' => [
                [
                    'name' => 'Potion of Mana',
                    'description' => 'A magical potion that restores spell slots.',
                    'effect' => 'Restores 1 spell slot of 3rd level or lower'
                ],
                [
                    'name' => 'Greater Mana Potion',
                    'description' => 'A more powerful mana potion.',
                    'effect' => 'Restores 1 spell slot of 5th level or lower'
                ]
            ],
            'strength' => [
                [
                    'name' => 'Potion of Strength',
                    'description' => 'A magical potion that enhances physical strength.',
                    'effect' => 'Increases Strength by 2 for 1 hour'
                ],
                [
                    'name' => 'Potion of Giant Strength',
                    'description' => 'A powerful potion that grants immense strength.',
                    'effect' => 'Increases Strength to 21 for 1 hour'
                ]
            ],
            'speed' => [
                [
                    'name' => 'Potion of Speed',
                    'description' => 'A magical potion that enhances movement speed.',
                    'effect' => 'Doubles movement speed for 1 hour'
                ]
            ],
            'invisibility' => [
                [
                    'name' => 'Potion of Invisibility',
                    'description' => 'A magical potion that makes the drinker invisible.',
                    'effect' => 'Makes the drinker invisible for 1 hour'
                ]
            ]
        ];
        
        return $potions[$type] ?? $potions['healing'];
    }
    
    /**
     * Расчет цены зелья
     */
    private function calculatePrice($rarity) {
        $prices = [
            'common' => '50-100 gp',
            'uncommon' => '101-500 gp',
            'rare' => '501-5000 gp',
            'very_rare' => '5001-50000 gp',
            'legendary' => '50001+ gp'
        ];
        
        return $prices[$rarity] ?? $prices['common'];
    }
    
    /**
     * Расчет веса зелья
     */
    private function calculateWeight($type) {
        $weights = [
            'healing' => '0.5 lb',
            'mana' => '0.5 lb',
            'strength' => '1 lb',
            'speed' => '0.5 lb',
            'invisibility' => '0.5 lb'
        ];
        
        return $weights[$type] ?? '0.5 lb';
    }
    
    /**
     * Получение длительности эффекта
     */
    private function getDuration($type) {
        $durations = [
            'healing' => 'Instant',
            'mana' => 'Instant',
            'strength' => '1 hour',
            'speed' => '1 hour',
            'invisibility' => '1 hour'
        ];
        
        return $durations[$type] ?? 'Instant';
    }
    
    /**
     * Генерация AI-деталей для зелья
     */
    private function generateAiDetails($potion) {
        try {
            $cacheKey = 'potion_ai_' . md5($potion['name'] . $potion['type']);
            $cached = $this->cacheService->get($cacheKey);
            
            if ($cached) {
                return $cached;
            }
            
            $prompt = "Создай детальное описание зелья для D&D 5e на русском языке.
            
ЗЕЛЬЕ: {$potion['name_ru']}
ТИП: {$potion['type']}
РЕДКОСТЬ: {$potion['rarity']}
ЭФФЕКТ: {$potion['effect']}

Создай описание (200-300 слов) включающее:
- Внешний вид и цвет зелья
- Запах и вкус
- Способ приготовления
- Историю создания
- Интересные детали использования
- Возможные побочные эффекты

Используй живой, описательный стиль.";
            
            $result = $this->aiService->generateCharacterDescription($prompt, false);
            
            if ($result && !isset($result['error'])) {
                $aiDetails = [
                    'ai_description' => $this->aiService->cleanAiResponse($result),
                    'ai_generated' => true
                ];
                
                $this->cacheService->set($cacheKey, $aiDetails, 3600);
                return $aiDetails;
            }
            
            return null;
            
        } catch (Exception $e) {
            logMessage('ERROR', "PotionGenerator: Ошибка AI генерации: " . $e->getMessage());
            return null;
        }
    }
}
?>
