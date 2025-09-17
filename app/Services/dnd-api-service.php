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
    
    private $cache_duration = 7200; // 2 часа - увеличен для лучшей производительности
    private $cache_dir;
    private $powershell_service;
    
    public function __construct() {
        $this->cache_dir = __DIR__ . '/../../data/cache/dnd_api/';
        if (!is_dir($this->cache_dir)) {
            mkdir($this->cache_dir, 0755, true);
        }
        
        // Подключаем PowerShell сервис для локального тестирования
        if (!class_exists('PowerShellHttpService')) {
            require_once __DIR__ . '/PowerShellHttpService.php';
        }
        $this->powershell_service = new PowerShellHttpService();
        
        // Проверяем доступность интернета (временно отключено для диагностики)
        // $this->checkInternetConnection();
    }
    
    /**
     * Проверка доступности интернета
     */
    private function checkInternetConnection() {
        $test_url = 'https://www.google.com';
        
        // Пробуем cURL
        if (function_exists('curl_init')) {
            $ch = curl_init($test_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($result !== false && $httpCode === 200) {
                logMessage('INFO', 'Интернет соединение доступно (cURL)');
                return;
            }
        }
        
        // Пробуем file_get_contents
        $context = stream_context_create([
            'http' => [
                'method' => 'HEAD',
                'timeout' => 5
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ]);
        
        $result = @file_get_contents($test_url, false, $context);
        if ($result !== false) {
            logMessage('INFO', 'Интернет соединение доступно (file_get_contents)');
        } else {
            logMessage('WARNING', 'Интернет соединение недоступно или ограничено');
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
        
        logMessage('INFO', "Запрашиваем данные расы: {$race_name}");
        
        // Список альтернативных названий для некоторых рас
        $race_alternatives = [
            'tabaxi' => ['tabaxi', 'tabax'],
            'tiefling' => ['tiefling', 'tieflings'],
            'half-elf' => ['half-elf', 'half-elves', 'halfelf'],
            'half-orc' => ['half-orc', 'half-orcs', 'halforc'],
            'high-elf' => ['high-elf', 'high-elves', 'highelf'],
            'wood-elf' => ['wood-elf', 'wood-elves', 'woodelf']
        ];
        
        $race_variants = $race_alternatives[strtolower($race_name)] ?? [$race_name];
        
        foreach ($race_variants as $variant) {
            // Пробуем D&D 5e API
            $dnd5e_url = "/races/" . strtolower($variant);
            logMessage('INFO', "Запрашиваем D&D 5e API: {$dnd5e_url}");
            $data = $this->fetchFromDnd5eApi($dnd5e_url);
            if ($data && isset($data['name'])) {
                logMessage('INFO', "Получены данные расы {$race_name} из D&D 5e API (вариант: {$variant})");
                $processed_data = $this->processRaceData($data);
                $this->cacheData($cache_key, $processed_data);
                return $processed_data;
            } elseif ($data) {
                logMessage('WARNING', "Получены некорректные данные от D&D 5e API для расы {$race_name} (вариант: {$variant})");
            }
            
            // Пробуем Open5e API
            $open5e_url = "/races/{$variant}/";
            logMessage('INFO', "Запрашиваем Open5e API: {$open5e_url}");
            $data = $this->fetchFromOpen5e($open5e_url);
            if ($data && isset($data['name'])) {
                logMessage('INFO', "Получены данные расы {$race_name} из Open5e API (вариант: {$variant})");
                $processed_data = $this->processRaceData($data);
                $this->cacheData($cache_key, $processed_data);
                return $processed_data;
            } elseif ($data) {
                logMessage('WARNING', "Получены некорректные данные от Open5e API для расы {$race_name} (вариант: {$variant})");
            }
        }
        
        // API недоступен - возвращаем ошибку
        logMessage('ERROR', "Не удалось получить данные расы '{$race_name}' ни из одного API");
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
     * Получение списка всех рас
     */
    public function getAllRaces() {
        $cache_key = "all_races";
        $cached = $this->getCachedData($cache_key);
        if ($cached) {
            return $cached;
        }
        
        // Получаем список рас
        $races = $this->fetchFromDnd5eApi("/races");
        if (!$races) {
            $races = $this->fetchFromOpen5e("/races/");
        }
        
        if ($races) {
            $processed_races = $this->processRacesListData($races);
            $this->cacheData($cache_key, $processed_races);
            return $processed_races;
        }
        
        // API недоступен - возвращаем ошибку
        return [
            'error' => 'API недоступен',
            'message' => "Не удалось получить список рас из внешних API"
        ];
    }
    
    /**
     * Получение списка всех классов
     */
    public function getAllClasses() {
        $cache_key = "all_classes";
        $cached = $this->getCachedData($cache_key);
        if ($cached) {
            return $cached;
        }
        
        // Получаем список классов
        $classes = $this->fetchFromDnd5eApi("/classes");
        if (!$classes) {
            $classes = $this->fetchFromOpen5e("/classes/");
        }
        
        if ($classes) {
            $processed_classes = $this->processClassesListData($classes);
            $this->cacheData($cache_key, $processed_classes);
            return $processed_classes;
        }
        
        // API недоступен - возвращаем ошибку
        return [
            'error' => 'API недоступен',
            'message' => "Не удалось получить список классов из внешних API"
        ];
    }
    
    /**
     * Получение списка всех заклинаний
     */
    public function getAllSpells() {
        $cache_key = "all_spells";
        $cached = $this->getCachedData($cache_key);
        if ($cached) {
            return $cached;
        }
        
        // Получаем список заклинаний
        $spells = $this->fetchFromDnd5eApi("/spells");
        if (!$spells) {
            $spells = $this->fetchFromOpen5e("/spells/");
        }
        
        if ($spells) {
            $processed_spells = $this->processSpellsListData($spells);
            $this->cacheData($cache_key, $processed_spells);
            return $processed_spells;
        }
        
        // API недоступен - возвращаем ошибку
        return [
            'error' => 'API недоступен',
            'message' => "Не удалось получить список заклинаний из внешних API"
        ];
    }
    
    /**
     * Получение списка всего снаряжения
     */
    public function getAllEquipment() {
        $cache_key = "all_equipment";
        $cached = $this->getCachedData($cache_key);
        if ($cached) {
            return $cached;
        }
        
        // Получаем список снаряжения
        $equipment = $this->fetchFromDnd5eApi("/equipment");
        if (!$equipment) {
            $equipment = $this->fetchFromOpen5e("/equipment/");
        }
        
        if ($equipment) {
            $processed_equipment = $this->processEquipmentListData($equipment);
            $this->cacheData($cache_key, $processed_equipment);
            return $processed_equipment;
        }
        
        // API недоступен - возвращаем ошибку
        return [
            'error' => 'API недоступен',
            'message' => "Не удалось получить список снаряжения из внешних API"
        ];
    }
    
    /**
     * Получение детальной информации о заклинании
     */
    public function getSpellDetails($spell_name) {
        $cache_key = "spell_details_{$spell_name}";
        $cached = $this->getCachedData($cache_key);
        if ($cached) {
            return $cached;
        }
        
        // Получаем детали заклинания
        $spell = $this->fetchFromDnd5eApi("/spells/" . strtolower(str_replace(' ', '-', $spell_name)));
        if (!$spell) {
            $spell = $this->fetchFromOpen5e("/spells/{$spell_name}/");
        }
        
        if ($spell) {
            $processed_spell = $this->processSpellDetailsData($spell);
            $this->cacheData($cache_key, $processed_spell);
            return $processed_spell;
        }
        
        // API недоступен - возвращаем ошибку
        return [
            'error' => 'API недоступен',
            'message' => "Не удалось получить детали заклинания '{$spell_name}' из внешних API"
        ];
    }
    
    /**
     * Получение детальной информации о снаряжении
     */
    public function getEquipmentDetails($equipment_name) {
        $cache_key = "equipment_details_{$equipment_name}";
        $cached = $this->getCachedData($cache_key);
        if ($cached) {
            return $cached;
        }
        
        // Получаем детали снаряжения
        $equipment = $this->fetchFromDnd5eApi("/equipment/" . strtolower(str_replace(' ', '-', $equipment_name)));
        if (!$equipment) {
            $equipment = $this->fetchFromOpen5e("/equipment/{$equipment_name}/");
        }
        
        if ($equipment) {
            $processed_equipment = $this->processEquipmentDetailsData($equipment);
            $this->cacheData($cache_key, $processed_equipment);
            return $processed_equipment;
        }
        
        // API недоступен - возвращаем ошибку
        return [
            'error' => 'API недоступен',
            'message' => "Не удалось получить детали снаряжения '{$equipment_name}' из внешних API"
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
        logMessage('INFO', "Начинаем API запрос к: {$url}");
        
        // Пробуем cURL сначала
        if (function_exists('curl_init')) {
            $result = $this->makeCurlRequest($url);
            if ($result !== null) {
                return $result;
            }
        }
        
        // Fallback на file_get_contents
        logMessage('INFO', "cURL недоступен, используем file_get_contents");
        $result = $this->makeFileGetContentsRequest($url);
        if ($result !== null) {
            return $result;
        }
        
        // Fallback на PowerShell (для локального тестирования)
        logMessage('INFO', "file_get_contents недоступен, пробуем PowerShell");
        $result = $this->makePowerShellRequest($url);
        if ($result !== null) {
            return $result;
        }
        
        logMessage('ERROR', "Все методы HTTP запросов недоступны для URL: {$url}");
        return null;
    }
    
    /**
     * Запрос через cURL
     */
    private function makeCurlRequest($url) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, 'DnD-Copilot/2.0');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        
        logMessage('INFO', "cURL запрос завершен: {$url}, HTTP: {$httpCode}, Время: {$info['total_time']}s");
        
        if ($response === false) {
            logMessage('ERROR', "cURL error: {$error} for URL: {$url}");
            return null;
        }
        
        if ($httpCode !== 200) {
            logMessage('WARNING', "cURL request failed: {$url}, HTTP: {$httpCode}, Response: " . substr($response, 0, 200));
            return null;
        }
        
        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            logMessage('ERROR', "JSON decode error: " . json_last_error_msg() . " for URL: {$url}");
            return null;
        }
        
        logMessage('INFO', "cURL request successful: {$url}, Data keys: " . implode(', ', array_keys($data)));
        return $data;
    }
    
    /**
     * Запрос через file_get_contents
     */
    private function makeFileGetContentsRequest($url) {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => [
                    'User-Agent: DnD-Copilot/2.0',
                    'Accept: application/json',
                    'Content-Type: application/json'
                ],
                'timeout' => 15
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ]);
        
        $start_time = microtime(true);
        $response = @file_get_contents($url, false, $context);
        $end_time = microtime(true);
        $duration = round($end_time - $start_time, 2);
        
        if ($response === false) {
            logMessage('ERROR', "file_get_contents failed for URL: {$url}");
            return null;
        }
        
        // Получаем HTTP код из заголовков
        $httpCode = 200;
        if (isset($http_response_header)) {
            foreach ($http_response_header as $header) {
                if (preg_match('/HTTP\/\d\.\d\s+(\d+)/', $header, $matches)) {
                    $httpCode = (int)$matches[1];
                    break;
                }
            }
        }
        
        logMessage('INFO', "file_get_contents запрос завершен: {$url}, HTTP: {$httpCode}, Время: {$duration}s");
        
        if ($httpCode !== 200) {
            logMessage('WARNING', "file_get_contents request failed: {$url}, HTTP: {$httpCode}");
            return null;
        }
        
        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            logMessage('ERROR', "JSON decode error: " . json_last_error_msg() . " for URL: {$url}");
            return null;
        }
        
        logMessage('INFO', "file_get_contents request successful: {$url}, Data keys: " . implode(', ', array_keys($data)));
        return $data;
    }
    
    /**
     * Запрос через PowerShell
     */
    private function makePowerShellRequest($url) {
        if (!$this->powershell_service->isAvailable()) {
            logMessage('WARNING', "PowerShell HTTP сервис недоступен");
            return null;
        }
        
        try {
            logMessage('INFO', "Выполняем PowerShell запрос к: {$url}");
            $result = $this->powershell_service->get($url);
            
            if ($result) {
                logMessage('INFO', "PowerShell request successful: {$url}, Data keys: " . implode(', ', array_keys($result)));
                return $result;
            }
            
        } catch (Exception $e) {
            logMessage('WARNING', "PowerShell request failed: {$url} - " . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * Обработка данных расы
     */
    private function processRaceData($data) {
        logMessage('INFO', "Обрабатываем данные расы: " . ($data['name'] ?? 'Unknown'));
        
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
        
        logMessage('INFO', "Обработаны данные расы: {$race_data['name']}");
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
     * Обработка списка рас
     */
    private function processRacesListData($data) {
        $races = [];
        
        if (isset($data['results'])) {
            foreach ($data['results'] as $race) {
                $races[] = [
                    'name' => $race['name'] ?? 'Unknown race',
                    'index' => $race['index'] ?? '',
                    'url' => $race['url'] ?? ''
                ];
            }
        }
        
        return $races;
    }
    
    /**
     * Обработка списка классов
     */
    private function processClassesListData($data) {
        $classes = [];
        
        if (isset($data['results'])) {
            foreach ($data['results'] as $class) {
                $classes[] = [
                    'name' => $class['name'] ?? 'Unknown class',
                    'index' => $class['index'] ?? '',
                    'url' => $class['url'] ?? ''
                ];
            }
        }
        
        return $classes;
    }
    
    /**
     * Обработка списка заклинаний
     */
    private function processSpellsListData($data) {
        $spells = [];
        
        if (isset($data['results'])) {
            foreach ($data['results'] as $spell) {
                $spells[] = [
                    'name' => $spell['name'] ?? 'Unknown spell',
                    'index' => $spell['index'] ?? '',
                    'url' => $spell['url'] ?? ''
                ];
            }
        }
        
        return $spells;
    }
    
    /**
     * Обработка списка снаряжения
     */
    private function processEquipmentListData($data) {
        $equipment = [];
        
        if (isset($data['results'])) {
            foreach ($data['results'] as $item) {
                $equipment[] = [
                    'name' => $item['name'] ?? 'Unknown item',
                    'index' => $item['index'] ?? '',
                    'url' => $item['url'] ?? ''
                ];
            }
        }
        
        return $equipment;
    }
    
    /**
     * Обработка деталей заклинания
     */
    private function processSpellDetailsData($data) {
        return [
            'name' => $data['name'] ?? 'Unknown spell',
            'level' => $data['level'] ?? 0,
            'school' => $data['school']['name'] ?? 'Unknown',
            'casting_time' => $data['casting_time'] ?? 'Unknown',
            'range' => $data['range'] ?? 'Unknown',
            'components' => $data['components'] ?? [],
            'duration' => $data['duration'] ?? 'Unknown',
            'description' => $data['desc'] ?? 'No description',
            'higher_levels' => $data['higher_level'] ?? '',
            'material' => $data['material'] ?? '',
            'ritual' => $data['ritual'] ?? false,
            'concentration' => $data['concentration'] ?? false
        ];
    }
    
    /**
     * Обработка деталей снаряжения
     */
    private function processEquipmentDetailsData($data) {
        return [
            'name' => $data['name'] ?? 'Unknown item',
            'type' => $data['equipment_category']['name'] ?? 'Unknown',
            'cost' => $data['cost'] ?? [],
            'weight' => $data['weight'] ?? 0,
            'description' => $data['desc'] ?? 'No description',
            'properties' => $data['properties'] ?? [],
            'armor_class' => $data['armor_class'] ?? null,
            'damage' => $data['damage'] ?? null,
            'range' => $data['range'] ?? null
        ];
    }
    
    /**
     * Кэширование данных с улучшенной обработкой
     */
    private function cacheData($key, $data) {
        try {
            $filename = $this->cache_dir . md5($key) . '.json';
            $cache_data = [
                'timestamp' => time(),
                'data' => $data,
                'checksum' => md5(json_encode($data, JSON_UNESCAPED_UNICODE))
            ];
            
            // Создаем временный файл для атомарной записи
            $temp_filename = $filename . '.tmp';
            $result = file_put_contents($temp_filename, json_encode($cache_data, JSON_UNESCAPED_UNICODE));
            
            if ($result !== false) {
                // Атомарно переименовываем временный файл
                if (rename($temp_filename, $filename)) {
                    logMessage('INFO', "Данные успешно закэшированы: {$key}");
                    return true;
                }
            }
            
            // Очищаем временный файл при ошибке
            if (file_exists($temp_filename)) {
                unlink($temp_filename);
            }
            
            logMessage('ERROR', "Ошибка кэширования данных: {$key}");
            return false;
            
        } catch (Exception $e) {
            logMessage('ERROR', "Исключение при кэшировании: {$key} - " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Получение кэшированных данных с улучшенной валидацией
     */
    private function getCachedData($key) {
        try {
            $filename = $this->cache_dir . md5($key) . '.json';
            
            if (!file_exists($filename)) {
                return null;
            }
            
            $file_content = file_get_contents($filename);
            if ($file_content === false) {
                logMessage('WARNING', "Не удалось прочитать кэш файл: {$key}");
                return null;
            }
            
            $cache_data = json_decode($file_content, true);
            if (!$cache_data || !isset($cache_data['timestamp']) || !isset($cache_data['data'])) {
                logMessage('WARNING', "Некорректные данные кэша: {$key}");
                $this->clearCacheFile($filename);
                return null;
            }
            
            // Проверяем срок действия кэша
            if (time() - $cache_data['timestamp'] > $this->cache_duration) {
                logMessage('INFO', "Кэш устарел: {$key}");
                $this->clearCacheFile($filename);
                return null;
            }
            
            // Проверяем целостность данных
            if (isset($cache_data['checksum'])) {
                $current_checksum = md5(json_encode($cache_data['data'], JSON_UNESCAPED_UNICODE));
                if ($cache_data['checksum'] !== $current_checksum) {
                    logMessage('WARNING', "Нарушена целостность кэша: {$key}");
                    $this->clearCacheFile($filename);
                    return null;
                }
            }
            
            logMessage('INFO', "Данные получены из кэша: {$key}");
            return $cache_data['data'];
            
        } catch (Exception $e) {
            logMessage('ERROR', "Исключение при чтении кэша: {$key} - " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Очистка кэш файла
     */
    private function clearCacheFile($filename) {
        try {
            if (file_exists($filename)) {
                unlink($filename);
                logMessage('INFO', "Кэш файл очищен: " . basename($filename));
            }
        } catch (Exception $e) {
            logMessage('ERROR', "Ошибка очистки кэш файла: " . basename($filename) . " - " . $e->getMessage());
        }
    }
    
    /**
     * Очистка всего кэша
     */
    public function clearAllCache() {
        try {
            $files = glob($this->cache_dir . '*.json');
            $count = 0;
            foreach ($files as $file) {
                if (unlink($file)) {
                    $count++;
                }
            }
            logMessage('INFO', "Очищено кэш файлов: {$count}");
            return $count;
        } catch (Exception $e) {
            logMessage('ERROR', "Ошибка очистки кэша: " . $e->getMessage());
            return 0;
        }
    }
}
?>
