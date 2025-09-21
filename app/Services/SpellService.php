<?php
/**
 * Сервис для работы с заклинаниями D&D 5e
 * Обеспечивает загрузку, фильтрацию и генерацию заклинаний
 */

class SpellService {
    private $spellsData = null;
    private $spellsFile;
    
    public function __construct() {
        $this->spellsFile = __DIR__ . '/../../data/персонажи/заклинания/заклинания.json';
    }
    
    /**
     * Загружает данные о заклинаниях из JSON файла
     */
    private function loadSpellsData() {
        if ($this->spellsData !== null) {
            return $this->spellsData;
        }
        
        if (!file_exists($this->spellsFile)) {
            throw new Exception('Файл с заклинаниями не найден');
        }
        
        $jsonContent = file_get_contents($this->spellsFile);
        $this->spellsData = json_decode($jsonContent, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Ошибка при чтении файла заклинаний: ' . json_last_error_msg());
        }
        
        return $this->spellsData;
    }
    
    /**
     * Получает все заклинания определенного уровня
     */
    public function getSpellsByLevel($level) {
        $spells = $this->loadSpellsData();
        return array_filter($spells, function($spell) use ($level) {
            return $spell['level'] == $level;
        });
    }
    
    /**
     * Получает заклинания для определенного класса
     */
    public function getSpellsByClass($class) {
        $spells = $this->loadSpellsData();
        return array_filter($spells, function($spell) use ($class) {
            // Проверяем основные классы
            if (in_array($class, $spell['classes'])) {
                return true;
            }
            
            // Проверяем подклассы
            if (isset($spell['subclasses'])) {
                foreach ($spell['subclasses'] as $subclass) {
                    if ($subclass['class'] === $class) {
                        return true;
                    }
                }
            }
            
            return false;
        });
    }
    
    /**
     * Получает заклинания по уровню и классу
     */
    public function getSpellsByLevelAndClass($level, $class = null) {
        $spells = $this->getSpellsByLevel($level);
        
        if ($class !== null) {
            $spells = array_filter($spells, function($spell) use ($class) {
                // Проверяем основные классы
                if (in_array($class, $spell['classes'])) {
                    return true;
                }
                
                // Проверяем подклассы
                if (isset($spell['subclasses'])) {
                    foreach ($spell['subclasses'] as $subclass) {
                        if ($subclass['class'] === $class) {
                            return true;
                        }
                    }
                }
                
                return false;
            });
        }
        
        return $spells;
    }
    
    /**
     * Генерирует случайные заклинания
     */
    public function generateSpells($level, $class = null, $count = 1) {
        // Получаем подходящие заклинания
        $availableSpells = $this->getSpellsByLevelAndClass($level, $class);
        
        if (empty($availableSpells)) {
            throw new Exception('Не найдено заклинаний для указанных параметров');
        }
        
        // Преобразуем в индексированный массив
        $availableSpells = array_values($availableSpells);
        
        // Ограничиваем количество запрашиваемых заклинаний
        $count = min($count, 5);
        $count = min($count, count($availableSpells));
        
        // Выбираем случайные заклинания
        $selectedSpells = [];
        $usedIndices = [];
        
        for ($i = 0; $i < $count; $i++) {
            do {
                $randomIndex = array_rand($availableSpells);
            } while (in_array($randomIndex, $usedIndices));
            
            $usedIndices[] = $randomIndex;
            $selectedSpells[] = $availableSpells[$randomIndex];
        }
        
        return $selectedSpells;
    }
    
    /**
     * Получает список доступных классов для заклинаний
     */
    public function getAvailableClasses() {
        $spells = $this->loadSpellsData();
        $classes = [];
        
        foreach ($spells as $spell) {
            $classes = array_merge($classes, $spell['classes']);
            
            if (isset($spell['subclasses'])) {
                foreach ($spell['subclasses'] as $subclass) {
                    $classes[] = $subclass['class'];
                }
            }
        }
        
        return array_unique($classes);
    }
    
    /**
     * Получает список доступных уровней заклинаний
     */
    public function getAvailableLevels() {
        $spells = $this->loadSpellsData();
        $levels = [];
        
        foreach ($spells as $spell) {
            $levels[] = $spell['level'];
        }
        
        return array_unique($levels);
    }
    
    /**
     * Получает информацию о заклинании по ID
     */
    public function getSpellById($id) {
        $spells = $this->loadSpellsData();
        
        foreach ($spells as $spell) {
            if ($spell['id'] === $id) {
                return $spell;
            }
        }
        
        return null;
    }
    
    /**
     * Поиск заклинаний по названию
     */
    public function searchSpells($query) {
        $spells = $this->loadSpellsData();
        $query = mb_strtolower($query, 'UTF-8');
        
        return array_filter($spells, function($spell) use ($query) {
            $nameRu = mb_strtolower($spell['name'], 'UTF-8');
            $nameEn = mb_strtolower($spell['name_en'], 'UTF-8');
            
            return strpos($nameRu, $query) !== false || strpos($nameEn, $query) !== false;
        });
    }
}
?>
