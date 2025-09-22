<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/SpellService.php';
require_once __DIR__ . '/PotionService.php';
require_once __DIR__ . '/CacheService.php';
require_once __DIR__ . '/../../public/api/error-handler.php';

class CharacterService {
    private $classesDir;
    private $racesFile;
    private $equipmentFile;
    private $namesDir;
    private $cache;
    private $spellService;

    public function __construct() {
        $this->classesDir = __DIR__ . '/../../data/персонажи/классы';
        $this->racesFile = __DIR__ . '/../../data/персонажи/расы/races.json';
        $this->equipmentFile = __DIR__ . '/../../data/персонажи/снаряжение/снаряжение.json';
        $this->namesDir = __DIR__ . '/../../names';
        $this->cache = new CacheService(__DIR__ . '/../../data/cache', CACHE_DURATION);
        $this->spellService = new SpellService();
    }

    public function generateCharacter($params) {
        $validated = $this->validateParams($params);

        $cacheKey = 'character:' . md5(json_encode($validated, JSON_UNESCAPED_UNICODE));
        if (CACHE_ENABLED) {
            $cached = $this->cache->get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }

        $classData = $this->loadClassData($validated['class']);
        $raceData = $this->loadRaceData($validated['race']);
        $equipmentData = $this->loadEquipmentData();
        $nameSuggestions = $this->loadNameSuggestions($validated['race'], $validated['gender']);

        $spells = [];
        // Подбираем заклинания только для классов-кастеров
        try {
            $spells = $this->collectSpellsForClass($validated['class'], (int)$validated['level']);
        } catch (Exception $e) {
            logMessage('WARNING', 'Failed to collect spells for class', ['class' => $validated['class'], 'error' => $e->getMessage()]);
        }

        $aiPayload = [
            'class' => $classData,
            'race' => $raceData,
            'alignment' => $validated['alignment'],
            'level' => (int)$validated['level'],
            'gender' => $validated['gender'],
            'background' => $validated['background'],
            'name_suggestions' => $nameSuggestions,
            'equipment_library' => $equipmentData,
            'spells_library' => $spells,
            'requirements' => [
                'output_format' => 'json',
                'must_include' => [
                    'name', 'race', 'class', 'level', 'alignment', 'background',
                    'abilities', 'skills', 'features', 'equipment', 'spells', 'personality', 'backstory'
                ]
            ]
        ];

        $character = $this->callDeepSeek($aiPayload);

        if (CACHE_ENABLED && $character) {
            $this->cache->set($cacheKey, $character, CACHE_DURATION);
        }

        return $character;
    }

    private function validateParams($params) {
        $required = ['class','race','alignment','level','gender','background'];
        foreach ($required as $field) {
            if (!isset($params[$field]) || $params[$field] === '') {
                throw new Exception("Отсутствует обязательное поле: $field");
            }
        }
        $level = (int)$params['level'];
        if ($level < 1 || $level > 20) {
            throw new Exception('Уровень должен быть от 1 до 20');
        }
        return [
            'class' => $this->normalizeSlug($params['class']),
            'race' => $this->normalizeSlug($params['race']),
            'alignment' => trim($params['alignment']),
            'level' => $level,
            'gender' => trim($params['gender']),
            'background' => trim($params['background'])
        ];
    }

    private function normalizeSlug($value) {
        $value = trim($value);
        $value = mb_strtolower($value, 'UTF-8');
        return $value;
    }

    private function loadClassData($classSlug) {
        $dir = $this->classesDir . '/' . $classSlug;
        $file = $dir . '/' . $classSlug . '.json';
        if (!file_exists($file)) {
            throw new Exception("Данные класса не найдены: $classSlug");
        }
        $content = file_get_contents($file);
        $data = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Ошибка чтения данных класса: ' . json_last_error_msg());
        }
        return $data;
    }

    private function loadRaceData($raceKeyOrName) {
        if (!file_exists($this->racesFile)) {
            throw new Exception('Файл рас не найден');
        }
        $content = file_get_contents($this->racesFile);
        $data = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Ошибка чтения файла рас: ' . json_last_error_msg());
        }
        // Структура races.json: объект races с ключами как машинные имена (например, "aarakocra")
        if (isset($data['races']) && is_array($data['races'])) {
            $needle = $this->normalizeSlug($raceKeyOrName);
            // 1) Прямая попытка по ключу
            if (isset($data['races'][$needle]) && is_array($data['races'][$needle])) {
                return $data['races'][$needle];
            }
            // 2) Поиск по русскому названию name
            foreach ($data['races'] as $key => $race) {
                $nameRu = isset($race['name']) ? $this->normalizeSlug($race['name']) : '';
                if ($nameRu === $needle) {
                    return $race;
                }
            }
            // 3) Поиск по английскому названию name_en
            foreach ($data['races'] as $key => $race) {
                $nameEn = isset($race['name_en']) ? $this->normalizeSlug($race['name_en']) : '';
                if ($nameEn === $needle) {
                    return $race;
                }
            }
        }
        // Если не нашли, вернем минимальную структуру, чтобы ИИ мог сгенерировать по описанию
        return ['key' => $raceKeyOrName, 'name' => $raceKeyOrName];
    }

    private function loadEquipmentData() {
        if (!file_exists($this->equipmentFile)) {
            return [];
        }
        $content = file_get_contents($this->equipmentFile);
        $data = json_decode($content, true);
        return $data ?: [];
    }

    private function loadNameSuggestions($raceSlug, $gender) {
        $map = [
            'aarakocra' => 'aarakocra_names.json',
            'aasimar' => 'aasimar_names.json',
            'dragonborn' => 'dragonborn_names.json',
            'dwarf' => 'dwarf_names.json',
            'elf' => 'elf_names.json',
            'fairy' => 'fairy_names.json',
            'gnome' => 'gnome_names.json',
            'goblin' => 'goblin_names.json',
            'goliath' => 'goliath_names.json',
            'half-elf' => 'half-elf_names.json',
            'half-orc' => 'half-orc_names.json',
            'halfling' => 'halfling_names.json',
            'human' => 'human_names.json',
            'kenku' => 'kenku_names.json',
            'lizardfolk' => 'lizardfolk_names.json',
            'orc' => 'orc_names.json',
            'tabaxi' => 'tabaxi_names.json',
            'tiefling' => 'tiefling_names.json',
            'yuan-ti' => 'yuan-ti_names.json'
        ];
        $raceKey = $this->normalizeSlug($raceSlug);
        $file = $map[$raceKey] ?? null;
        if (!$file) return [];
        $path = $this->namesDir . '/' . $file;
        if (!file_exists($path)) return [];
        $content = file_get_contents($path);
        $data = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) return [];
        // Возвращаем ограниченный список имён, учитывая пол, если структура поддерживает
        $names = [];
        if (isset($data['male']) || isset($data['female'])) {
            if ($gender && isset($data[$gender])) {
                $names = $data[$gender];
            } else {
                $names = array_merge($data['male'] ?? [], $data['female'] ?? []);
            }
        } else if (isset($data['names'])) {
            $names = $data['names'];
        } else if (is_array($data)) {
            $names = $data;
        }
        return array_slice($names, 0, 30);
    }

    private function collectSpellsForClass($classSlug, $level) {
        // Собираем доступные уровни от 0 до min(9, максимальный для уровня персонажа)
        $result = [];
        $casterClasses = [
            'волшебник','жрец','друид','чародей','колдун','паладин','следопыт','бард','изобретатель','монах'
        ];
        if (!in_array($this->normalizeSlug($classSlug), $casterClasses, true)) {
            return $result;
        }
        $maxSpellLevel = min(9, $this->estimateMaxSpellLevel($classSlug, $level));
        for ($lvl = 0; $lvl <= $maxSpellLevel; $lvl++) {
            $spells = $this->spellService->getSpellsByLevelAndClass($lvl, $this->mapClassToEn($classSlug));
            if (!empty($spells)) {
                $result[$lvl] = array_values($spells);
            }
        }
        return $result;
    }

    private function estimateMaxSpellLevel($classSlug, $charLevel) {
        // Приблизительная оценка: каждое 2 уровня после 1 даёт новый уровень заклинаний, до 9
        return max(0, min(9, (int)floor(($charLevel - 1) / 2)));
    }

    private function mapClassToEn($classSlug) {
        // Простейшее сопоставление русских классов к англ названию в базе заклинаний
        $map = [
            'бард' => 'Bard',
            'варвар' => 'Barbarian',
            'воин' => 'Fighter',
            'волшебник' => 'Wizard',
            'друид' => 'Druid',
            'жрец' => 'Cleric',
            'изобретатель' => 'Artificer',
            'колдун' => 'Warlock',
            'монах' => 'Monk',
            'паладин' => 'Paladin',
            'плут' => 'Rogue',
            'следопыт' => 'Ranger',
            'чародей' => 'Sorcerer'
        ];
        $key = $this->normalizeSlug($classSlug);
        return $map[$key] ?? $classSlug;
    }

    private function callDeepSeek($payload) {
        $apiKey = getApiKey('deepseek');
        if (!$apiKey) {
            throw new Exception('API ключ DeepSeek не настроен');
        }

        $systemPrompt = 'Ты помощник-мастер D&D 5e. На основе предоставленных библиотек JSON создай полностью оформленного персонажа в формате JSON (UTF-8, без комментариев). Строго следуй правилам 5e, учитывай класс, расу, уровень, мировоззрение и происхождение. Включай вычисленные значения характеристик, модификаторы, владения, черты, заклинания, начальное снаряжение и краткую предысторию.';

        $userPrompt = [
            'instruction' => 'Сгенерируй персонажа D&D 5e. Верни ТОЛЬКО JSON указанной структуры.',
            'data' => $payload,
            'expected_schema' => [
                'name' => 'string',
                'race' => 'string',
                'class' => 'string',
                'level' => 'number',
                'alignment' => 'string',
                'background' => 'string',
                'abilities' => [
                    'STR' => 'number','DEX' => 'number','CON' => 'number','INT' => 'number','WIS' => 'number','CHA' => 'number'
                ],
                'skills' => 'array',
                'features' => 'array',
                'equipment' => 'array',
                'spells' => 'object',
                'personality' => 'object',
                'backstory' => 'string'
            ]
        ];

        $body = [
            'model' => 'deepseek-chat',
            'response_format' => ['type' => 'json_object'],
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => json_encode($userPrompt, JSON_UNESCAPED_UNICODE)]
            ]
        ];

        try {
            $response = ErrorHandler::safeHttpRequest(
                DEEPSEEK_API_URL,
                [
                    'method' => 'POST',
                    'timeout' => API_TIMEOUT,
                    'headers' => [
                        'Authorization: Bearer ' . $apiKey,
                        'Content-Type: application/json'
                    ],
                    'data' => json_encode($body, JSON_UNESCAPED_UNICODE)
                ]
            );
            $decoded = json_decode($response, true);
            if (isset($decoded['choices'][0]['message']['content'])) {
                $content = $decoded['choices'][0]['message']['content'];
                $asJson = json_decode($content, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $asJson;
                }
                // Если контент не JSON, попробуем вернуть как есть
                return ['raw' => $content];
            }
            return $decoded;
        } catch (Exception $e) {
            logMessage('ERROR', 'DeepSeek request failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}
?>


