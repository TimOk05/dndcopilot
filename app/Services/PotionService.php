<?php
/**
 * Сервис для работы с зельями D&D 5e
 * Обеспечивает загрузку, фильтрацию и генерацию зелий
 */

class PotionService {
    private $potionsData = null;
    private $potionsFile;
    
    public function __construct() {
        $this->potionsFile = __DIR__ . '/../../data/зелья/зелья.json';
    }
    
    /**
     * Загружает данные о зельях из JSON файла
     */
    private function loadPotionsData() {
        if ($this->potionsData !== null) {
            return $this->potionsData;
        }
        
        if (!file_exists($this->potionsFile)) {
            throw new Exception('Файл с зельями не найден');
        }
        
        $jsonContent = file_get_contents($this->potionsFile);
        $this->potionsData = json_decode($jsonContent, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Ошибка при чтении файла зелий: ' . json_last_error_msg());
        }
        
        return $this->potionsData;
    }
    
    /**
     * Получает все зелья определенной редкости
     */
    public function getPotionsByRarity($rarity) {
        $data = $this->loadPotionsData();
        $potions = $data['items'] ?? [];
        
        return array_filter($potions, function($potion) use ($rarity) {
            return $potion['rarity'] === $rarity;
        });
    }
    
    /**
     * Получает зелья определенного типа
     */
    public function getPotionsByType($type) {
        $data = $this->loadPotionsData();
        $potions = $data['items'] ?? [];
        
        return array_filter($potions, function($potion) use ($type) {
            // Объединяем зелья и масла в один тип
            if ($type === 'potion') {
                return $potion['type'] === 'potion' || $potion['type'] === 'oil';
            }
            return $potion['type'] === $type;
        });
    }
    
    /**
     * Получает зелья по редкости и типу
     */
    public function getPotionsByRarityAndType($rarity = null, $type = null) {
        $data = $this->loadPotionsData();
        $potions = $data['items'] ?? [];
        
        if ($rarity !== null) {
            $potions = array_filter($potions, function($potion) use ($rarity) {
                return $potion['rarity'] === $rarity;
            });
        }
        
        if ($type !== null) {
            $potions = array_filter($potions, function($potion) use ($type) {
                return $potion['type'] === $type;
            });
        }
        
        return $potions;
    }
    
    /**
     * Генерирует случайные зелья
     */
    public function generatePotions($rarity = null, $type = null, $count = 1) {
        // Получаем подходящие зелья
        $availablePotions = $this->getPotionsByRarityAndType($rarity, $type);
        
        if (empty($availablePotions)) {
            throw new Exception('Не найдено зелий для указанных параметров');
        }
        
        // Преобразуем в индексированный массив
        $availablePotions = array_values($availablePotions);
        
        // Ограничиваем количество запрашиваемых зелий
        $count = min($count, 10);
        $count = min($count, count($availablePotions));
        
        // Выбираем случайные зелья
        $selectedPotions = [];
        $usedIndices = [];
        
        for ($i = 0; $i < $count; $i++) {
            do {
                $randomIndex = array_rand($availablePotions);
            } while (in_array($randomIndex, $usedIndices));
            
            $usedIndices[] = $randomIndex;
            $selectedPotions[] = $availablePotions[$randomIndex];
        }
        
        return $selectedPotions;
    }
    
    /**
     * Получает список доступных редкостей
     */
    public function getAvailableRarities() {
        $data = $this->loadPotionsData();
        $potions = $data['items'] ?? [];
        $rarities = [];
        
        foreach ($potions as $potion) {
            $rarities[] = $potion['rarity'];
        }
        
        return array_unique($rarities);
    }
    
    /**
     * Получает список доступных типов
     */
    public function getAvailableTypes() {
        // Теперь у нас только один тип - зелья (включая масла)
        return ['potion'];
    }
    
    /**
     * Получает информацию о зелье по ID
     */
    public function getPotionById($id) {
        $data = $this->loadPotionsData();
        $potions = $data['items'] ?? [];
        
        foreach ($potions as $potion) {
            if ($potion['id'] === $id) {
                return $potion;
            }
        }
        
        return null;
    }
    
    /**
     * Поиск зелий по названию
     */
    public function searchPotions($query) {
        $data = $this->loadPotionsData();
        $potions = $data['items'] ?? [];
        $query = mb_strtolower($query, 'UTF-8');
        
        return array_filter($potions, function($potion) use ($query) {
            $name = mb_strtolower($potion['name'], 'UTF-8');
            return strpos($name, $query) !== false;
        });
    }
    
    /**
     * Получает локализованные названия редкостей
     */
    public function getRarityLocalized($rarity) {
        $rarityMap = [
            'common' => 'Обычное',
            'uncommon' => 'Необычное',
            'rare' => 'Редкое',
            'very_rare' => 'Очень редкое',
            'legendary' => 'Легендарное'
        ];
        
        return $rarityMap[$rarity] ?? $rarity;
    }
    
    /**
     * Получает локализованные названия типов
     */
    public function getTypeLocalized($type) {
        // Теперь все типы отображаются как зелья
        return 'Зелье';
    }
}
?>
