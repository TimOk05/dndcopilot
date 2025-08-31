<?php
/**
 * D&D API Service - Сервис для работы с внешними D&D API
 * Получает достоверную информацию о расах, классах, заклинаниях и других игровых механиках
 */

class DndApiService {
    private $api_endpoints = [
        'open5e' => 'https://api.open5e.com',
        'dnd5eapi' => 'https://www.dnd5eapi.co/api'
    ];
    
    private $cache_duration = 3600; // 1 час
    private $cache_dir;
    
    public function __construct() {
        $this->cache_dir = __DIR__ . '/../cache/dnd_api/';
        if (!is_dir($this->cache_dir)) {
            mkdir($this->cache_dir, 0755, true);
        }
    }
    
    /**
     * Получение данных расы из API
     */
    public function getRaceData($race_name) {
        $cache_key = "race_{$race_name}";
        $cached = $this->getCachedData($cache_key);
        if ($cached) {
            return $cached;
        }
        
        // Пробуем разные API
        $data = $this->fetchFromDnd5eApi("/races/" . strtolower($race_name));
        if (!$data) {
            $data = $this->fetchFromOpen5e("/races/{$race_name}/");
        }
        
        if ($data) {
            $processed_data = $this->processRaceData($data);
            $this->cacheData($cache_key, $processed_data);
            return $processed_data;
        }
        
        // API недоступен - возвращаем ошибку
        return [
            'error' => 'API недоступен',
            'message' => "Не удалось получить данные расы '{$race_name}' из внешних API"
        ];
    }
    
    /**
     * Получение данных класса из API
     */
    public function getClassData($class_name) {
        $cache_key = "class_{$class_name}";
        $cached = $this->getCachedData($cache_key);
        if ($cached) {
            return $cached;
        }
        
        // Пробуем разные API
        $data = $this->fetchFromDnd5eApi("/classes/" . strtolower($class_name));
        if (!$data) {
            $data = $this->fetchFromOpen5e("/classes/{$class_name}/");
        }
        
        if ($data) {
            $processed_data = $this->processClassData($data);
            $this->cacheData($cache_key, $processed_data);
            return $processed_data;
        }
        
        // API недоступен - возвращаем ошибку
        return [
            'error' => 'API недоступен',
            'message' => "Не удалось получить данные класса '{$class_name}' из внешних API"
        ];
    }
    
    /**
     * Получение заклинаний для класса
     */
    public function getSpellsForClass($class_name, $level = 1) {
        $cache_key = "spells_{$class_name}_{$level}";
        $cached = $this->getCachedData($cache_key);
        if ($cached) {
            return $cached;
        }
        
        // Получаем список заклинаний для класса
        $spells = $this->fetchFromDnd5eApi("/classes/" . strtolower($class_name) . "/spells");
        if (!$spells) {
            $spells = $this->fetchFromOpen5e("/spells/?classes={$class_name}&level={$level}");
        }
        
        if ($spells) {
            $processed_spells = $this->processSpellsData($spells, $level);
            $this->cacheData($cache_key, $processed_spells);
            return $processed_spells;
        }
        
        // API недоступен - возвращаем ошибку
        return [
            'error' => 'API недоступен',
            'message' => "Не удалось получить заклинания для класса '{$class_name}' уровня {$level} из внешних API"
        ];
    }
    
    /**
     * Получение снаряжения для класса
     */
    public function getEquipmentForClass($class_name) {
        $cache_key = "equipment_{$class_name}";
        $cached = $this->getCachedData($cache_key);
        if ($cached) {
            return $cached;
        }
        
        // Получаем снаряжение для класса
        $equipment = $this->fetchFromDnd5eApi("/classes/" . strtolower($class_name) . "/starting-equipment");
        if (!$equipment) {
            $equipment = $this->fetchFromOpen5e("/equipment/?class={$class_name}");
        }
        
        if ($equipment) {
            $processed_equipment = $this->processEquipmentData($equipment);
            $this->cacheData($cache_key, $processed_equipment);
            return $processed_equipment;
        }
        
        // API недоступен - возвращаем ошибку
        return [
            'error' => 'API недоступен',
            'message' => "Не удалось получить снаряжение для класса '{$class_name}' из внешних API"
        ];
    }
    
    /**
     * Получение способностей класса по уровню
     */
    public function getClassFeatures($class_name, $level = 1) {
        $cache_key = "features_{$class_name}_{$level}";
        $cached = $this->getCachedData($cache_key);
        if ($cached) {
            return $cached;
        }
        
        // Получаем способности класса
        $features = $this->fetchFromDnd5eApi("/classes/" . strtolower($class_name) . "/levels/{$level}");
        if (!$features) {
            $features = $this->fetchFromOpen5e("/class-features/?class={$class_name}&level={$level}");
        }
        
        if ($features) {
            $processed_features = $this->processFeaturesData($features);
            $this->cacheData($cache_key, $processed_features);
            return $processed_features;
        }
        
        // API недоступен - возвращаем ошибку
        return [
            'error' => 'API недоступен',
            'message' => "Не удалось получить способности класса '{$class_name}' уровня {$level} из внешних API"
        ];
    }
    
    /**
     * Запрос к D&D 5e API
     */
    private function fetchFromDnd5eApi($endpoint) {
        $url = $this->api_endpoints['dnd5eapi'] . $endpoint;
        return $this->makeApiRequest($url);
    }
    
    /**
     * Запрос к Open5e API
     */
    private function fetchFromOpen5e($endpoint) {
        $url = $this->api_endpoints['open5e'] . $endpoint;
        return $this->makeApiRequest($url);
    }
    
    /**
     * Выполнение API запроса
     */
    private function makeApiRequest($url) {
        if (!function_exists('curl_init')) {
            return null;
        }
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, 'DnD-Copilot/2.0');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'Content-Type: application/json'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($response === false || $httpCode !== 200) {
            logMessage('WARNING', "API request failed: {$url}, HTTP: {$httpCode}");
            return null;
        }
        
        $data = json_decode($response, true);
        return $data;
    }
    
    /**
     * Обработка данных расы
     */
    private function processRaceData($data) {
        $race_data = [
            'name' => $data['name'] ?? 'Unknown',
            'speed' => $data['speed'] ?? 30,
            'ability_bonuses' => [],
            'traits' => [],
            'languages' => [],
            'subraces' => []
        ];
        
        // Обработка бонусов характеристик
        if (isset($data['ability_bonuses'])) {
            foreach ($data['ability_bonuses'] as $bonus) {
                $ability = strtolower($bonus['ability_score']['name'] ?? 'str');
                $race_data['ability_bonuses'][$ability] = $bonus['bonus'] ?? 1;
            }
        }
        
        // Обработка черт
        if (isset($data['traits'])) {
            foreach ($data['traits'] as $trait) {
                $race_data['traits'][] = $trait['name'] ?? 'Unknown trait';
            }
        }
        
        // Обработка языков
        if (isset($data['languages'])) {
            foreach ($data['languages'] as $language) {
                $race_data['languages'][] = $language['name'] ?? 'Common';
            }
        }
        
        // Обработка подрас
        if (isset($data['subraces'])) {
            foreach ($data['subraces'] as $subrace) {
                $race_data['subraces'][] = $subrace['name'] ?? 'Unknown subrace';
            }
        }
        
        return $race_data;
    }
    
    /**
     * Обработка данных класса
     */
    private function processClassData($data) {
        $class_data = [
            'name' => $data['name'] ?? 'Unknown',
            'hit_die' => $data['hit_die'] ?? 8,
            'proficiencies' => [],
            'proficiency_choices' => [],
            'spellcasting' => false,
            'spellcasting_ability' => null,
            'subclasses' => []
        ];
        
        // Обработка владений
        if (isset($data['proficiencies'])) {
            foreach ($data['proficiencies'] as $prof) {
                $class_data['proficiencies'][] = $prof['name'] ?? 'Unknown proficiency';
            }
        }
        
        // Обработка выбора владений
        if (isset($data['proficiency_choices'])) {
            foreach ($data['proficiency_choices'] as $choice) {
                $options = [];
                if (isset($choice['from']['options'])) {
                    foreach ($choice['from']['options'] as $option) {
                        $options[] = $option['item']['name'] ?? 'Unknown option';
                    }
                }
                $class_data['proficiency_choices'][] = [
                    'choose' => $choice['choose'] ?? 1,
                    'options' => $options
                ];
            }
        }
        
        // Обработка заклинательства
        if (isset($data['spellcasting'])) {
            $class_data['spellcasting'] = true;
            $class_data['spellcasting_ability'] = strtolower($data['spellcasting']['spellcasting_ability']['name'] ?? 'int');
        }
        
        // Обработка подклассов
        if (isset($data['subclasses'])) {
            foreach ($data['subclasses'] as $subclass) {
                $class_data['subclasses'][] = $subclass['name'] ?? 'Unknown subclass';
            }
        }
        
        return $class_data;
    }
    
    /**
     * Обработка данных заклинаний
     */
    private function processSpellsData($data, $level) {
        $spells = [];
        
        if (isset($data['results'])) {
            foreach ($data['results'] as $spell) {
                if (isset($spell['level']) && $spell['level'] <= $level) {
                    $spells[] = [
                        'name' => $spell['name'] ?? 'Unknown spell',
                        'level' => $spell['level'] ?? 0,
                        'school' => $spell['school']['name'] ?? 'Unknown',
                        'casting_time' => $spell['casting_time'] ?? 'Unknown',
                        'range' => $spell['range'] ?? 'Unknown',
                        'components' => $spell['components'] ?? [],
                        'duration' => $spell['duration'] ?? 'Unknown',
                        'description' => $spell['desc'] ?? 'No description'
                    ];
                }
            }
        }
        
        return $spells;
    }
    
    /**
     * Обработка данных снаряжения
     */
    private function processEquipmentData($data) {
        $equipment = [
            'choices' => [],
            'default' => []
        ];
        
        if (isset($data['choice'])) {
            foreach ($data['choice'] as $choice) {
                $options = [];
                if (isset($choice['from']['equipment_category']['equipment'])) {
                    foreach ($choice['from']['equipment_category']['equipment'] as $item) {
                        $options[] = $item['name'] ?? 'Unknown item';
                    }
                }
                $equipment['choices'][] = [
                    'choose' => $choice['choose'] ?? 1,
                    'options' => $options
                ];
            }
        }
        
        if (isset($data['starting_equipment'])) {
            foreach ($data['starting_equipment'] as $item) {
                $equipment['default'][] = $item['equipment']['name'] ?? 'Unknown item';
            }
        }
        
        return $equipment;
    }
    
    /**
     * Обработка данных способностей
     */
    private function processFeaturesData($data) {
        $features = [];
        
        if (isset($data['features'])) {
            foreach ($data['features'] as $feature) {
                $features[] = [
                    'name' => $feature['name'] ?? 'Unknown feature',
                    'description' => $feature['desc'] ?? 'No description',
                    'level' => $feature['level'] ?? 1
                ];
            }
        }
        
        return $features;
    }
    
    /**
     * Кэширование данных
     */
    private function cacheData($key, $data) {
        $filename = $this->cache_dir . md5($key) . '.json';
        $cache_data = [
            'timestamp' => time(),
            'data' => $data
        ];
        file_put_contents($filename, json_encode($cache_data, JSON_UNESCAPED_UNICODE));
    }
    
    /**
     * Получение кэшированных данных
     */
    private function getCachedData($key) {
        $filename = $this->cache_dir . md5($key) . '.json';
        
        if (!file_exists($filename)) {
            return null;
        }
        
        $cache_data = json_decode(file_get_contents($filename), true);
        if (!$cache_data) {
            return null;
        }
        
        // Проверяем срок действия кэша
        if (time() - $cache_data['timestamp'] > $this->cache_duration) {
            unlink($filename);
            return null;
        }
        
        return $cache_data['data'];
    }
}
?>
