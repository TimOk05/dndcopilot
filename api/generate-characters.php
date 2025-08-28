<?php
// Убираем заголовки для использования в тестах
if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/json');
}

// Проверяем метод запроса только если это не CLI
if (php_sapi_name() !== 'cli' && (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST')) {
    echo json_encode(['success' => false, 'error' => 'Метод не поддерживается']);
    exit;
}
require_once __DIR__ . '/../config.php';

class CharacterGenerator {
    private $dnd5e_api_url = 'https://www.dnd5eapi.co/api';
    private $deepseek_api_key;
    private $occupations = [];
    
    public function __construct() {
        $this->deepseek_api_key = getApiKey('deepseek');
        $this->loadOccupations();
    }
    
    /**
     * Загрузка профессий из JSON файла
     */
    private function loadOccupations() {
        $jsonFile = __DIR__ . '/../pdf/d100_unique_traders.json';
        if (file_exists($jsonFile)) {
            $jsonData = json_decode(file_get_contents($jsonFile), true);
            if (isset($jsonData['data']['occupations'])) {
                $this->occupations = $jsonData['data']['occupations'];
            }
        }
    }
    
    /**
     * База имён для разных рас из JSON файла
     */
    private function getNamesByRace($race, $gender = 'random') {
        static $raceNames = null;
        
        // Загружаем имена из JSON файла только один раз
        if ($raceNames === null) {
            $jsonFile = __DIR__ . '/../pdf/dnd_race_names_ru_v2.json';
            if (file_exists($jsonFile)) {
                $jsonData = json_decode(file_get_contents($jsonFile), true);
                if (isset($jsonData['data'])) {
                    $raceNames = [];
                    foreach ($jsonData['data'] as $raceData) {
                        $raceKey = strtolower($raceData['race']);
                        $raceNames[$raceKey] = $raceData;
                    }
                }
            }
        }
        
        $race = strtolower($race);
        
        // Если есть данные для этой расы
        if (isset($raceNames[$race])) {
            $raceData = $raceNames[$race];
            
            // Определяем пол если выбран случайный
            if ($gender === 'random') {
                $gender = rand(0, 1) ? 'male' : 'female';
            }
            
            // Выбираем имя в зависимости от пола
            $nameList = [];
            
            if ($gender === 'male' && !empty($raceData['male'])) {
                $nameList = $raceData['male'];
            } elseif ($gender === 'female' && !empty($raceData['female'])) {
                $nameList = $raceData['female'];
            }
            
            // Если нет имен для выбранного пола, используем унисекс имена
            if (empty($nameList) && !empty($raceData['unisex'])) {
                $nameList = $raceData['unisex'];
            }
            
            // Если все еще нет имен, используем имена другого пола
            if (empty($nameList)) {
                if ($gender === 'male' && !empty($raceData['female'])) {
                    $nameList = $raceData['female'];
                } elseif ($gender === 'female' && !empty($raceData['male'])) {
                    $nameList = $raceData['male'];
                }
            }
            
            // Возвращаем случайное имя из списка
            if (!empty($nameList)) {
                return $nameList[array_rand($nameList)];
            }
        }
        
        // Fallback имена для случаев, когда JSON файл недоступен или раса не найдена
        $fallbackNames = [
            'male' => ['Алексей', 'Дмитрий', 'Иван', 'Михаил', 'Сергей', 'Андрей', 'Владимир', 'Николай', 'Петр', 'Александр'],
            'female' => ['Анна', 'Елена', 'Мария', 'Ольга', 'Татьяна', 'Ирина', 'Наталья', 'Светлана', 'Екатерина', 'Юлия']
        ];
        
        if ($gender === 'random') {
            $gender = rand(0, 1) ? 'male' : 'female';
        }
        
        return $fallbackNames[$gender][array_rand($fallbackNames[$gender])];
    }
    
    /**
     * Получение случайной профессии
     */
    private function getRandomOccupation() {
        if (empty($this->occupations)) {
            return 'Странник';
        }
        
        $occupation = $this->occupations[array_rand($this->occupations)];
        $name = $occupation['name_ru'] ?? 'Странник';
        
        // Очищаем от лишних символов и исправляем слипание слов
        $name = preg_replace('/[^\p{L}\p{N}\s\-]/u', '', $name); // Убираем спецсимволы
        $name = preg_replace('/\s+/', ' ', $name); // Убираем множественные пробелы
        $name = preg_replace('/\d+/', '', $name); // Убираем цифры
        $name = preg_replace('/\s+([А-ЯЁ])/u', ' $1', $name); // Добавляем пробелы перед заглавными буквами
        $name = trim($name);
        
        // Убираем дублирующиеся слова
        $words = explode(' ', $name);
        $uniqueWords = [];
        foreach ($words as $word) {
            $word = trim($word);
            if (!empty($word) && !in_array(strtolower($word), array_map('strtolower', $uniqueWords))) {
                $uniqueWords[] = $word;
            }
        }
        $name = implode(' ', $uniqueWords);
        
        // Если после очистки осталась пустая строка, возвращаем fallback
        if (empty($name) || strlen($name) < 2) {
            return 'Странник';
        }
        
        return $name;
    }
    
    /**
     * Генерация персонажа
     */
    public function generateCharacter($params) {
        $race = $params['race'] ?? 'human';
        $class = $params['class'] ?? 'fighter';
        $level = (int)($params['level'] ?? 1);
        $alignment = $params['alignment'] ?? 'neutral';
        $gender = $params['gender'] ?? 'random';
        $use_ai = isset($params['use_ai']) && $params['use_ai'] === 'on';
        
        // Валидация параметров
        if ($level < 1 || $level > 20) {
            throw new Exception('Уровень персонажа должен быть от 1 до 20');
        }
        
        $valid_races = ['human', 'elf', 'dwarf', 'halfling', 'orc', 'tiefling', 'dragonborn', 'gnome', 'half-elf', 'half-orc', 'tabaxi', 'aarakocra', 'goblin', 'kenku', 'lizardfolk', 'triton', 'yuan-ti', 'goliath', 'firbolg', 'bugbear', 'hobgoblin', 'kobold'];
        if (!in_array($race, $valid_races)) {
            throw new Exception('Неверная раса персонажа');
        }
        
        $valid_classes = ['fighter', 'wizard', 'rogue', 'cleric', 'ranger', 'barbarian', 'bard', 'druid', 'monk', 'paladin', 'sorcerer', 'warlock', 'artificer'];
        if (!in_array($class, $valid_classes)) {
            throw new Exception('Неверный класс персонажа');
        }
        
        // Генерируем случайное мировоззрение если выбрано
        if ($alignment === 'random') {
            $alignments = ['lawful-good', 'neutral-good', 'chaotic-good', 'lawful-neutral', 'neutral', 'chaotic-neutral', 'lawful-evil', 'neutral-evil', 'chaotic-evil'];
            $alignment = $alignments[array_rand($alignments)];
        }
        
        try {
            // Получаем данные расы
            $race_data = $this->getRaceData($race);
            if (!$race_data) {
                throw new Exception('Не удалось получить данные расы');
            }
            
            // Получаем данные класса
            $class_data = $this->getClassData($class);
            if (!$class_data) {
                throw new Exception('Не удалось получить данные класса');
            }
            
            // Генерируем характеристики
            $abilities = $this->generateAbilities($race_data, $level);
            
            // Проверяем корректность характеристик
            if (!$this->validateAbilities($abilities)) {
                throw new Exception('Ошибка генерации характеристик персонажа');
            }
            
            // Рассчитываем параметры
            $character = [
                'name' => $this->generateName($race, $gender),
                'race' => $race_data['name'],
                'class' => $class_data['name'],
                'level' => $level,
                'alignment' => $this->getAlignmentText($alignment),
                'gender' => $this->getGenderText($gender),
                'occupation' => $this->getRandomOccupation(),
                'abilities' => $abilities,
                'hit_points' => $this->calculateHP($class_data, $abilities['con'], $level),
                'armor_class' => $this->calculateAC($class_data, $abilities['dex']),
                'speed' => $this->getSpeed($race_data),
                'initiative' => $this->calculateInitiative($abilities['dex']),
                'proficiency_bonus' => $this->calculateProficiencyBonus($level),
                'attack_bonus' => $this->calculateAttackBonus($class_data, $abilities, $level),
                'damage' => $this->calculateDamage($class_data, $abilities, $level),
                'main_weapon' => $this->getMainWeapon($class_data),
                'proficiencies' => $this->getProficiencies($class_data),
                'spells' => $this->getSpells($class_data, $level, $abilities['int'], $abilities['wis'], $abilities['cha']),
                'features' => $this->getFeatures($class_data, $level),
                'equipment' => $this->getEquipment($class_data),
                'saving_throws' => $this->getSavingThrows($class_data, $abilities)
            ];
            
            // Добавляем описание (AI или fallback)
            $character['use_ai'] = $use_ai ? 'on' : 'off';
            $character['description'] = $this->generateDescription($character);
            $character['background'] = $this->generateBackground($character);
            
            return [
                'success' => true,
                'npc' => $character
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Получение данных расы
     */
    private function getRaceData($race_index) {
        $fallback_races = [
            'human' => [
                'name' => 'Человек',
                'ability_bonuses' => ['str' => 1, 'dex' => 1, 'con' => 1, 'int' => 1, 'wis' => 1, 'cha' => 1],
                'traits' => ['Универсальность', 'Дополнительное владение навыком']
            ],
            'elf' => [
                'name' => 'Эльф',
                'ability_bonuses' => ['dex' => 2],
                'traits' => ['Темное зрение', 'Келебрас', 'Иммунитет к усыплению', 'Транс']
            ],
            'dwarf' => [
                'name' => 'Дварф',
                'ability_bonuses' => ['con' => 2],
                'traits' => ['Темное зрение', 'Устойчивость к яду', 'Владение боевым топором']
            ],
            'halfling' => [
                'name' => 'Полурослик',
                'ability_bonuses' => ['dex' => 2],
                'traits' => ['Удача', 'Смелость', 'Ловкость полурослика']
            ],
            'orc' => [
                'name' => 'Орк',
                'ability_bonuses' => ['str' => 2, 'con' => 1],
                'traits' => ['Темное зрение', 'Угрожающий', 'Мощная атака']
            ],
            'tiefling' => [
                'name' => 'Тифлинг',
                'ability_bonuses' => ['cha' => 2, 'int' => 1],
                'traits' => ['Темное зрение', 'Устойчивость к огню', 'Адское наследие']
            ],
            'dragonborn' => [
                'name' => 'Драконорожденный',
                'ability_bonuses' => ['str' => 2, 'cha' => 1],
                'traits' => ['Дыхание дракона', 'Устойчивость к урону', 'Драконье наследие']
            ],
            'gnome' => [
                'name' => 'Гном',
                'ability_bonuses' => ['int' => 2],
                'traits' => ['Темное зрение', 'Гномья хитрость', 'Иллюзии']
            ],
            'half-elf' => [
                'name' => 'Полуэльф',
                'ability_bonuses' => ['cha' => 2, 'dex' => 1, 'int' => 1],
                'traits' => ['Темное зрение', 'Универсальность', 'Эльфийское наследие']
            ],
            'half-orc' => [
                'name' => 'Полуорк',
                'ability_bonuses' => ['str' => 2, 'con' => 1],
                'traits' => ['Темное зрение', 'Угрожающий', 'Мощная атака']
            ],
            'tabaxi' => [
                'name' => 'Табакси',
                'ability_bonuses' => ['dex' => 2, 'cha' => 1],
                'traits' => ['Темное зрение', 'Кошачья ловкость', 'Кошачьи когти']
            ],
            'aarakocra' => [
                'name' => 'Ааракокра',
                'ability_bonuses' => ['dex' => 2, 'wis' => 1],
                'traits' => ['Полет', 'Клюв и когти', 'Ветряной голос']
            ],
            'goblin' => [
                'name' => 'Гоблин',
                'ability_bonuses' => ['dex' => 2, 'con' => 1],
                'traits' => ['Темное зрение', 'Гоблинская ловкость', 'Бегство']
            ],
            'kenku' => [
                'name' => 'Кенку',
                'ability_bonuses' => ['dex' => 2, 'wis' => 1],
                'traits' => ['Темное зрение', 'Подражание', 'Экспертная подделка']
            ],
            'lizardfolk' => [
                'name' => 'Ящеролюд',
                'ability_bonuses' => ['con' => 2, 'wis' => 1],
                'traits' => ['Темное зрение', 'Укус', 'Держаться на плаву']
            ],
            'triton' => [
                'name' => 'Тритон',
                'ability_bonuses' => ['str' => 1, 'con' => 1, 'cha' => 1],
                'traits' => ['Амфибия', 'Дыхание', 'Управление призыванием']
            ],
            'yuan-ti' => [
                'name' => 'Юань-ти',
                'ability_bonuses' => ['cha' => 2, 'int' => 1],
                'traits' => ['Темное зрение', 'Устойчивость к яду', 'Магическое сопротивление']
            ],
            'goliath' => [
                'name' => 'Голиаф',
                'ability_bonuses' => ['str' => 2, 'con' => 1],
                'traits' => ['Мощное телосложение', 'Каменная выносливость', 'Атлетик']
            ],
            'firbolg' => [
                'name' => 'Фирболг',
                'ability_bonuses' => ['wis' => 2, 'str' => 1],
                'traits' => ['Темное зрение', 'Скрытность', 'Речь зверей и растений']
            ],
            'bugbear' => [
                'name' => 'Багбир',
                'ability_bonuses' => ['str' => 2, 'dex' => 1],
                'traits' => ['Темное зрение', 'Длиннорукий', 'Скрытность']
            ],
            'hobgoblin' => [
                'name' => 'Хобгоблин',
                'ability_bonuses' => ['con' => 2, 'int' => 1],
                'traits' => ['Темное зрение', 'Военная подготовка', 'Спасительная милость']
            ],
            'kobold' => [
                'name' => 'Кобольд',
                'ability_bonuses' => ['dex' => 2],
                'traits' => ['Темное зрение', 'Солнечная чувствительность', 'Упакованная тактика']
            ]
        ];
        
        return $fallback_races[$race_index] ?? $fallback_races['human'];
    }
    
    /**
     * Получение данных класса
     */
    private function getClassData($class_index) {
        $fallback_classes = [
            'fighter' => [
                'name' => 'Воин',
                'hit_die' => 10,
                'proficiencies' => ['Все доспехи', 'Щиты', 'Простое оружие', 'Воинское оружие'],
                'features' => ['Боевой стиль', 'Second Wind'],
                'spellcasting' => false
            ],
            'wizard' => [
                'name' => 'Волшебник',
                'hit_die' => 6,
                'proficiencies' => ['Кинжалы', 'Посохи', 'Арбалеты'],
                'features' => ['Заклинания', 'Восстановление заклинаний'],
                'spellcasting' => true,
                'spellcasting_ability' => 'int'
            ],
            'rogue' => [
                'name' => 'Плут',
                'hit_die' => 8,
                'proficiencies' => ['Легкие доспехи', 'Простое оружие', 'Короткие мечи', 'Длинные мечи'],
                'features' => ['Скрытность', 'Sneak Attack'],
                'spellcasting' => false
            ],
            'cleric' => [
                'name' => 'Жрец',
                'hit_die' => 8,
                'proficiencies' => ['Легкие доспехи', 'Средние доспехи', 'Щиты', 'Простое оружие'],
                'features' => ['Заклинания', 'Божественный домен'],
                'spellcasting' => true,
                'spellcasting_ability' => 'wis'
            ],
            'ranger' => [
                'name' => 'Следопыт',
                'hit_die' => 10,
                'proficiencies' => ['Легкие доспехи', 'Средние доспехи', 'Щиты', 'Простое оружие', 'Воинское оружие'],
                'features' => ['Любимый враг', 'Естественный исследователь'],
                'spellcasting' => true,
                'spellcasting_ability' => 'wis'
            ],
            'barbarian' => [
                'name' => 'Варвар',
                'hit_die' => 12,
                'proficiencies' => ['Легкие доспехи', 'Средние доспехи', 'Щиты', 'Простое оружие', 'Воинское оружие'],
                'features' => ['Ярость', 'Защита без доспехов'],
                'spellcasting' => false
            ],
            'bard' => [
                'name' => 'Бард',
                'hit_die' => 8,
                'proficiencies' => ['Легкие доспехи', 'Простое оружие', 'Ручные арбалеты', 'Длинные мечи'],
                'features' => ['Вдохновение барда', 'Песнь отдыха'],
                'spellcasting' => true,
                'spellcasting_ability' => 'cha'
            ],
            'druid' => [
                'name' => 'Друид',
                'hit_die' => 8,
                'proficiencies' => ['Легкие доспехи', 'Средние доспехи', 'Щиты', 'Простое оружие'],
                'features' => ['Дикий облик', 'Друидский'],
                'spellcasting' => true,
                'spellcasting_ability' => 'wis'
            ],
            'monk' => [
                'name' => 'Монах',
                'hit_die' => 8,
                'proficiencies' => ['Простое оружие', 'Короткие мечи'],
                'features' => ['Безоружная защита', 'Боевые искусства'],
                'spellcasting' => false
            ],
            'paladin' => [
                'name' => 'Паладин',
                'hit_die' => 10,
                'proficiencies' => ['Все доспехи', 'Щиты', 'Простое оружие', 'Воинское оружие'],
                'features' => ['Божественное чувство', 'Божественное здоровье'],
                'spellcasting' => true,
                'spellcasting_ability' => 'cha'
            ],
            'sorcerer' => [
                'name' => 'Чародей',
                'hit_die' => 6,
                'proficiencies' => ['Кинжалы', 'Посохи', 'Арбалеты'],
                'features' => ['Магическое происхождение', 'Метамагия'],
                'spellcasting' => true,
                'spellcasting_ability' => 'cha'
            ],
            'warlock' => [
                'name' => 'Колдун',
                'hit_die' => 8,
                'proficiencies' => ['Легкие доспехи', 'Простое оружие'],
                'features' => ['Пакт с покровителем', 'Мистические арканумы'],
                'spellcasting' => true,
                'spellcasting_ability' => 'cha'
            ],
            'artificer' => [
                'name' => 'Изобретатель',
                'hit_die' => 8,
                'proficiencies' => ['Легкие доспехи', 'Средние доспехи', 'Щиты', 'Простое оружие'],
                'features' => ['Магическое изобретение', 'Инфузия'],
                'spellcasting' => true,
                'spellcasting_ability' => 'int'
            ]
        ];
        
        return $fallback_classes[$class_index] ?? $fallback_classes['fighter'];
    }
    
    /**
     * Генерация характеристик
     */
    private function generateAbilities($race_data, $level = 1) {
        $abilities = [
            'str' => $this->rollAbilityScore(),
            'dex' => $this->rollAbilityScore(),
            'con' => $this->rollAbilityScore(),
            'int' => $this->rollAbilityScore(),
            'wis' => $this->rollAbilityScore(),
            'cha' => $this->rollAbilityScore()
        ];
        
        // Применяем бонусы расы
        if (isset($race_data['ability_bonuses'])) {
            foreach ($race_data['ability_bonuses'] as $ability => $bonus) {
                if (isset($abilities[$ability])) {
                    $abilities[$ability] += $bonus;
                    // Ограничиваем максимальное значение 20
                    $abilities[$ability] = min(20, $abilities[$ability]);
                }
            }
        }
        
        // Улучшение характеристик с уровнем (каждые 4 уровня)
        $ability_improvements = floor(($level - 1) / 4);
        if ($ability_improvements > 0) {
            // Улучшаем случайные характеристики
            $ability_names = ['str', 'dex', 'con', 'int', 'wis', 'cha'];
            for ($i = 0; $i < $ability_improvements; $i++) {
                $ability = $ability_names[array_rand($ability_names)];
                $abilities[$ability] += 2;
                $abilities[$ability] = min(20, $abilities[$ability]);
            }
        }
        
        // Логируем для отладки
        error_log("Generated abilities: " . json_encode($abilities));
        
        return $abilities;
    }
    
    /**
     * Бросок характеристики (4d6, убираем минимальный)
     */
    private function rollAbilityScore() {
        $rolls = [];
        for ($i = 0; $i < 4; $i++) {
            $rolls[] = rand(1, 6);
        }
        sort($rolls);
        array_shift($rolls); // Убираем минимальный
        return array_sum($rolls);
    }
    
    /**
     * Проверка корректности характеристик
     */
    private function validateAbilities($abilities) {
        $required_abilities = ['str', 'dex', 'con', 'int', 'wis', 'cha'];
        
        // Проверяем наличие всех характеристик
        foreach ($required_abilities as $ability) {
            if (!isset($abilities[$ability])) {
                error_log("Missing ability: $ability");
                return false;
            }
        }
        
        // Проверяем, что все характеристики находятся в разумном диапазоне 3-20
        foreach ($abilities as $ability_name => $ability_value) {
            if ($ability_value < 3 || $ability_value > 20) {
                error_log("Invalid ability value for $ability_name: $ability_value");
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Расчет хитов
     */
    private function calculateHP($class_data, $con_modifier, $level) {
        $con_bonus = floor(($con_modifier - 10) / 2);
        $base_hp = $class_data['hit_die'] + $con_bonus;
        $additional_hp = 0;
        
        for ($i = 2; $i <= $level; $i++) {
            $additional_hp += rand(1, $class_data['hit_die']) + $con_bonus;
        }
        
        return max(1, $base_hp + $additional_hp);
    }
    
    /**
     * Расчет урона
     */
    private function calculateDamage($class_data, $abilities, $level = 1) {
        $damage_die = $class_data['hit_die'];
        $damage_bonus = floor(($abilities['str'] - 10) / 2);
        
        // Определяем количество кубиков и их размер
        $dice_count = 1;
        $dice_size = $damage_die;
        
        // Улучшение урона с уровнем (дополнительные атаки)
        if ($level >= 5) {
            $dice_count = 2; // Дополнительная атака
        }
        if ($level >= 11) {
            $dice_count = 3; // Улучшенная дополнительная атака
        }
        if ($level >= 20) {
            $dice_count = 4; // Превосходная дополнительная атака
        }
        
        // Формируем формулу урона в формате "XdY + Z"
        $damage_formula = $dice_count . 'd' . $dice_size;
        
        // Добавляем бонус только если он положительный
        if ($damage_bonus > 0) {
            $damage_formula .= ' + ' . $damage_bonus;
        } elseif ($damage_bonus < 0) {
            // Для отрицательных бонусов показываем как "XdY - Z"
            $damage_formula .= ' - ' . abs($damage_bonus);
        }
        
        return $damage_formula;
    }
    
    /**
     * Расчет попадания (атаки)
     */
    private function calculateAttackBonus($class_data, $abilities, $level = 1) {
        $proficiency_bonus = $this->calculateProficiencyBonus($level);
        $str_bonus = floor(($abilities['str'] - 10) / 2);
        
        // Базовый бонус атаки = бонус мастерства + модификатор силы
        $attack_bonus = $proficiency_bonus + $str_bonus;
        
        // Формируем строку в формате "+X" или "-X"
        if ($attack_bonus >= 0) {
            return '+' . $attack_bonus;
        } else {
            return $attack_bonus; // Уже будет со знаком минус
        }
    }
    
    /**
     * Получение основного оружия персонажа
     */
    private function getMainWeapon($class_data) {
        $weapons = [];
        
        // Определяем оружие на основе владений класса
        if (in_array('Воинское оружие', $class_data['proficiencies'])) {
            $weapons = ['Длинный меч', 'Боевой топор', 'Молот', 'Копье', 'Алебарда', 'Меч-рапира'];
        } elseif (in_array('Простое оружие', $class_data['proficiencies'])) {
            $weapons = ['Булава', 'Короткий меч', 'Кинжал', 'Дубина', 'Копье', 'Топор'];
        }
        
        // Добавляем специальное оружие для определенных классов
        if (in_array('Кинжалы', $class_data['proficiencies'])) {
            $weapons[] = 'Кинжал';
        }
        if (in_array('Посохи', $class_data['proficiencies'])) {
            $weapons[] = 'Магический посох';
        }
        if (in_array('Арбалеты', $class_data['proficiencies'])) {
            $weapons[] = 'Легкий арбалет';
        }
        if (in_array('Короткие мечи', $class_data['proficiencies'])) {
            $weapons[] = 'Короткий меч';
        }
        if (in_array('Длинные мечи', $class_data['proficiencies'])) {
            $weapons[] = 'Длинный меч';
        }
        
        // Если нет оружия, возвращаем базовое
        if (empty($weapons)) {
            $weapons = ['Кинжал', 'Дубина', 'Копье'];
        }
        
        return $weapons[array_rand($weapons)];
    }
    
    /**
     * Расчет класса доспеха
     */
    private function calculateAC($class_data, $dex_modifier) {
        $dex_bonus = floor(($dex_modifier - 10) / 2);
        
        if (in_array('Все доспехи', $class_data['proficiencies'])) {
            return 16 + min(2, $dex_bonus); // Кольчуга
        } elseif (in_array('Средние доспехи', $class_data['proficiencies'])) {
            return 14 + min(2, $dex_bonus); // Кожаный доспех
        } else {
            return 10 + $dex_bonus; // Без доспеха
        }
    }
    
    /**
     * Получение скорости
     */
    private function getSpeed($race_data) {
        $speed = 30; // Базовая скорость
        if (isset($race_data['traits']) && in_array('Транс', $race_data['traits'])) {
            $speed = 60; // Транс
        }
        return $speed;
    }

    /**
     * Расчет инициативы
     */
    private function calculateInitiative($dex_modifier) {
        return floor(($dex_modifier - 10) / 2);
    }

    /**
     * Расчет бонуса мастерства
     */
    private function calculateProficiencyBonus($level) {
        return floor(($level - 1) / 4) + 2;
    }
    
    /**
     * Получение владений
     */
    private function getProficiencies($class_data) {
        return $class_data['proficiencies'];
    }
    
    /**
     * Получение заклинаний
     */
    private function getSpells($class_data, $level, $int, $wis, $cha) {
        if (!$class_data['spellcasting']) {
            return [];
        }
        
        $spellcasting_ability = $class_data['spellcasting_ability'] ?? 'int';
        $ability_score = $$spellcasting_ability;
        $ability_modifier = floor(($ability_score - 10) / 2);
        
        $spells = [];
        
        // Заклинания 1 уровня
        if ($level >= 1) {
            $level1_spells = [
                [
                    'name' => 'Свет',
                    'level' => 1,
                    'school' => 'Воплощение',
                    'casting_time' => '1 действие',
                    'range' => 'Касание',
                    'components' => 'V, M (светлячок или светящийся мох)',
                    'duration' => '1 час',
                    'description' => 'Вы касаетесь объекта размером не больше 10 футов в любом измерении. Пока заклинание активно, объект испускает яркий свет в радиусе 20 футов и тусклый свет еще на 20 футов.',
                    'damage' => null
                ],
                [
                    'name' => 'Магическая стрела',
                    'level' => 1,
                    'school' => 'Воплощение',
                    'casting_time' => '1 действие',
                    'range' => '120 футов',
                    'components' => 'V, S',
                    'duration' => 'Мгновенно',
                    'description' => 'Вы создаете три светящихся дротика магической энергии. Каждый дротик поражает цель по вашему выбору, которую вы можете видеть в пределах дистанции.',
                    'damage' => '1d4 + ' . $ability_modifier . ' урона силовым полем за дротик'
                ],
                [
                    'name' => 'Лечение ран',
                    'level' => 1,
                    'school' => 'Воплощение',
                    'casting_time' => '1 действие',
                    'range' => 'Касание',
                    'components' => 'V, S',
                    'duration' => 'Мгновенно',
                    'description' => 'Существо, которого вы касаетесь, восстанавливает количество хитов, равное 1d8 + модификатор вашей характеристики заклинаний.',
                    'damage' => '1d8 + ' . $ability_modifier . ' лечения'
                ],
                [
                    'name' => 'Щит',
                    'level' => 1,
                    'school' => 'Воплощение',
                    'casting_time' => '1 реакция',
                    'range' => 'На себя',
                    'components' => 'V, S',
                    'duration' => '1 раунд',
                    'description' => 'Невидимый барьер магической силы появляется и защищает вас, давая +5 к КД до начала вашего следующего хода.',
                    'damage' => null
                ]
            ];
            
            // Выбираем случайные заклинания 1 уровня
            $spell_count = min(2, count($level1_spells));
            $selected_spells = array_rand($level1_spells, $spell_count);
            if (!is_array($selected_spells)) {
                $selected_spells = [$selected_spells];
            }
            
            foreach ($selected_spells as $index) {
                $spells[] = $level1_spells[$index];
            }
        }
        
        // Заклинания 2 уровня
        if ($level >= 3) {
            $level2_spells = [
                [
                    'name' => 'Огненный шар',
                    'level' => 3,
                    'school' => 'Воплощение',
                    'casting_time' => '1 действие',
                    'range' => '150 футов',
                    'components' => 'V, S, M (маленький шарик из гуано летучей мыши и серы)',
                    'duration' => 'Мгновенно',
                    'description' => 'Яркий светящийся шар огня летит к выбранной точке в пределах дистанции и взрывается в яркой вспышке.',
                    'damage' => '8d6 урона огнем'
                ],
                [
                    'name' => 'Невидимость',
                    'level' => 2,
                    'school' => 'Иллюзия',
                    'casting_time' => '1 действие',
                    'range' => 'Касание',
                    'components' => 'V, S, M (ресница, завернутая в кусочек смолы)',
                    'duration' => 'Концентрация, до 1 часа',
                    'description' => 'Существо, которого вы касаетесь, и все, что оно носит или несет, становятся невидимыми до тех пор, пока заклинание не закончится.',
                    'damage' => null
                ]
            ];
            
            $spells[] = $level2_spells[array_rand($level2_spells)];
        }
        
        // Заклинания 3 уровня
        if ($level >= 5) {
            $level3_spells = [
                [
                    'name' => 'Молния',
                    'level' => 3,
                    'school' => 'Воплощение',
                    'casting_time' => '1 действие',
                    'range' => 'На себя (100-футовая линия)',
                    'components' => 'V, S, M (кусочек меха и стержень из янтаря, кристалла или стекла)',
                    'duration' => 'Мгновенно',
                    'description' => 'Молния формируется в линию длиной 100 футов и шириной 5 футов, исходящую от вас в выбранном направлении.',
                    'damage' => '8d6 урона электричеством'
                ],
                [
                    'name' => 'Полет',
                    'level' => 3,
                    'school' => 'Преобразование',
                    'casting_time' => '1 действие',
                    'range' => 'Касание',
                    'components' => 'V, S, M (перо любой птицы)',
                    'duration' => 'Концентрация, до 10 минут',
                    'description' => 'Вы касаетесь согласного существа. Цель получает скорость полета 60 футов на время действия заклинания.',
                    'damage' => null
                ]
            ];
            
            $spells[] = $level3_spells[array_rand($level3_spells)];
        }
        
        return $spells;
    }
    
    /**
     * Получение способностей
     */
    private function getFeatures($class_data, $level) {
        $features = $class_data['features'];
        
        if ($level >= 2) {
            $features[] = 'Дополнительная атака';
        }
        if ($level >= 5) {
            $features[] = 'Улучшенная критическая атака';
        }
        
        return $features;
    }
    
    /**
     * Получение снаряжения
     */
    private function getEquipment($class_data) {
        $equipment = [];
        
        // Доспехи
        if (in_array('Все доспехи', $class_data['proficiencies'])) {
            $armors = ['Кольчуга', 'Кожаный доспех', 'Кожаная броня', 'Стеганый доспех'];
            $equipment[] = $armors[array_rand($armors)];
        } elseif (in_array('Средние доспехи', $class_data['proficiencies'])) {
            $armors = ['Кожаный доспех', 'Кожаная броня', 'Стеганый доспех'];
            $equipment[] = $armors[array_rand($armors)];
        } elseif (in_array('Легкие доспехи', $class_data['proficiencies'])) {
            $armors = ['Кожаная броня', 'Стеганый доспех'];
            $equipment[] = $armors[array_rand($armors)];
        }
        
        // Щиты
        if (in_array('Щиты', $class_data['proficiencies'])) {
            $equipment[] = 'Деревянный щит';
        }
        
        // Оружие
        if (in_array('Воинское оружие', $class_data['proficiencies'])) {
            $weapons = ['Длинный меч', 'Боевой топор', 'Молот', 'Копье', 'Алебарда'];
            $equipment[] = $weapons[array_rand($weapons)];
        } elseif (in_array('Простое оружие', $class_data['proficiencies'])) {
            $weapons = ['Булава', 'Короткий меч', 'Кинжал', 'Дубина', 'Копье'];
            $equipment[] = $weapons[array_rand($weapons)];
        }
        
        // Дополнительное оружие
        if (in_array('Кинжалы', $class_data['proficiencies'])) {
            $equipment[] = 'Кинжал';
        }
        if (in_array('Посохи', $class_data['proficiencies'])) {
            $equipment[] = 'Магический посох';
        }
        if (in_array('Арбалеты', $class_data['proficiencies'])) {
            $equipment[] = 'Легкий арбалет';
        }
        
        // Базовое снаряжение
        $equipment[] = 'Рюкзак исследователя';
        $equipment[] = 'Веревка (50 футов)';
        $equipment[] = 'Факел';
        $equipment[] = 'Трутница';
        
        // Зелья и магические предметы
        $potions = ['Зелье лечения', 'Зелье невидимости', 'Зелье прыгучести', 'Зелье сопротивления огню'];
        $equipment[] = $potions[array_rand($potions)];
        
        // Бытовые предметы
        $tools = ['Набор для выживания', 'Инструменты кузнеца', 'Инструменты плотника', 'Инструменты кожевника'];
        $equipment[] = $tools[array_rand($tools)];
        
        // Дополнительные предметы
        $items = ['Компас', 'Карта местности', 'Свисток', 'Зеркало', 'Мыло', 'Полотенце'];
        $equipment[] = $items[array_rand($items)];
        
        // Деньги
        $gold = rand(5, 25);
        $equipment[] = "{$gold} золотых монет";
        
        return $equipment;
    }

    /**
     * Получение бросков способностей
     */
    private function getSavingThrows($class_data, $abilities) {
        $saving_throws = [];
        
        if (isset($class_data['spellcasting']) && $class_data['spellcasting']) {
            $spellcasting_ability = $class_data['spellcasting_ability'] ?? 'int';
            $spellcasting_ability_score = $abilities[$spellcasting_ability] ?? 10;
            $spellcasting_ability_modifier = floor(($spellcasting_ability_score - 10) / 2);
            $saving_throws[] = ['name' => 'Заклинания', 'modifier' => $spellcasting_ability_modifier];
        }

        $saving_throws[] = ['name' => 'Сила', 'modifier' => floor(($abilities['str'] - 10) / 2)];
        $saving_throws[] = ['name' => 'Ловкость', 'modifier' => floor(($abilities['dex'] - 10) / 2)];
        $saving_throws[] = ['name' => 'Телосложение', 'modifier' => floor(($abilities['con'] - 10) / 2)];
        $saving_throws[] = ['name' => 'Интеллект', 'modifier' => floor(($abilities['int'] - 10) / 2)];
        $saving_throws[] = ['name' => 'Мудрость', 'modifier' => floor(($abilities['wis'] - 10) / 2)];
        $saving_throws[] = ['name' => 'Харизма', 'modifier' => floor(($abilities['cha'] - 10) / 2)];

        return $saving_throws;
    }
    
    /**
     * Генерация имени персонажа
     */
    private function generateName($race, $gender) {
        if ($gender === 'random') {
            $gender = rand(0, 1) ? 'male' : 'female';
        }
        return $this->getNamesByRace($race, $gender);
    }

    /**
     * Получение текста пола
     */
    private function getGenderText($gender) {
        return $gender === 'male' ? 'Мужчина' : 'Женщина';
    }
    
    /**
     * Получение текста мировоззрения
     */
    private function getAlignmentText($alignment) {
        $alignments = [
            'lawful-good' => 'Законно-добрый',
            'neutral-good' => 'Нейтрально-добрый',
            'chaotic-good' => 'Хаотично-добрый',
            'lawful-neutral' => 'Законно-нейтральный',
            'neutral' => 'Нейтральный',
            'chaotic-neutral' => 'Хаотично-нейтральный',
            'lawful-evil' => 'Законно-злой',
            'neutral-evil' => 'Нейтрально-злой',
            'chaotic-evil' => 'Хаотично-злой'
        ];
        
        return $alignments[$alignment] ?? 'Нейтральный';
    }
    
    /**
     * Генерация описания с помощью AI
     */
    private function generateDescription($character) {
        // AI всегда включен для генерации описаний
        
        // Формируем полную информацию о персонаже для AI
        $characterInfo = "Персонаж: {$character['name']}, {$character['race']} {$character['class']} {$character['level']} уровня.\n";
        $characterInfo .= "Профессия: {$character['occupation']}\n";
        $characterInfo .= "Пол: {$character['gender']}\n";
        $characterInfo .= "Мировоззрение: {$character['alignment']}\n";
        $characterInfo .= "Характеристики: СИЛ {$character['abilities']['str']}, ЛОВ {$character['abilities']['dex']}, ТЕЛ {$character['abilities']['con']}, ИНТ {$character['abilities']['int']}, МДР {$character['abilities']['wis']}, ХАР {$character['abilities']['cha']}\n";
        $characterInfo .= "Боевые параметры: Хиты {$character['hit_points']}, КД {$character['armor_class']}, Скорость {$character['speed']} футов, Инициатива {$character['initiative']}, Бонус мастерства +{$character['proficiency_bonus']}\n";
        $characterInfo .= "Урон: {$character['damage']}\n";
        
        if (!empty($character['proficiencies'])) {
            $characterInfo .= "Владения: " . implode(', ', $character['proficiencies']) . "\n";
        }
        
        if (!empty($character['spells'])) {
            $characterInfo .= "Заклинания: " . implode(', ', array_column($character['spells'], 'name')) . "\n";
        }
        
        $prompt = "Опиши внешность и характер персонажа на основе его полных данных:\n\n" . $characterInfo . "\n" .
                 "Включи детали внешности, особенности поведения и характерные черты, связанные с его расой, классом, профессией и характеристиками. " .
                 "Ответ должен быть кратким (2-3 предложения) и атмосферным.";
        
        try {
            $response = $this->callDeepSeek($prompt);
            return $response ?: $this->generateFallbackDescription($character);
        } catch (Exception $e) {
            error_log("AI description generation failed: " . $e->getMessage());
            return $this->generateFallbackDescription($character);
        }
    }
    
    /**
     * Генерация предыстории с помощью AI
     */
    private function generateBackground($character) {
        // AI всегда включен для генерации предысторий
        
        // Формируем полную информацию о персонаже для AI
        $characterInfo = "Персонаж: {$character['name']}, {$character['race']} {$character['class']} {$character['level']} уровня.\n";
        $characterInfo .= "Профессия: {$character['occupation']}\n";
        $characterInfo .= "Пол: {$character['gender']}\n";
        $characterInfo .= "Мировоззрение: {$character['alignment']}\n";
        $characterInfo .= "Характеристики: СИЛ {$character['abilities']['str']}, ЛОВ {$character['abilities']['dex']}, ТЕЛ {$character['abilities']['con']}, ИНТ {$character['abilities']['int']}, МДР {$character['abilities']['wis']}, ХАР {$character['abilities']['cha']}\n";
        $characterInfo .= "Боевые параметры: Хиты {$character['hit_points']}, КД {$character['armor_class']}, Скорость {$character['speed']} футов, Инициатива {$character['initiative']}, Бонус мастерства +{$character['proficiency_bonus']}\n";
        $characterInfo .= "Урон: {$character['damage']}\n";
        
        if (!empty($character['proficiencies'])) {
            $characterInfo .= "Владения: " . implode(', ', $character['proficiencies']) . "\n";
        }
        
        if (!empty($character['spells'])) {
            $characterInfo .= "Заклинания: " . implode(', ', array_column($character['spells'], 'name')) . "\n";
        }
        
        $prompt = "Создай краткую предысторию персонажа на основе его полных данных:\n\n" . $characterInfo . "\n" .
                 "Включи мотивацию, ключевое событие из прошлого и цель персонажа, связанные с его расой, классом, профессией и характеристиками. " .
                 "Ответ должен быть кратким (2-3 предложения) и интересным.";
        
        try {
            $response = $this->callDeepSeek($prompt);
            return $response ?: $this->generateFallbackBackground($character);
        } catch (Exception $e) {
            error_log("AI background generation failed: " . $e->getMessage());
            return $this->generateFallbackBackground($character);
        }
    }
    
    /**
     * Генерация fallback описания без AI
     */
    private function generateFallbackDescription($character) {
        $race = $character['race'];
        $gender = $character['gender'];
        $name = $character['name'];
        
        $descriptions = [
            'human' => [
                'male' => "{$name} - крепкий мужчина средних лет с решительным взглядом. Его руки покрыты мозолями от долгих лет тренировок, а в глазах читается опыт и мудрость.",
                'female' => "{$name} - стройная женщина с уверенной осанкой и острым взглядом. Её движения точны и выверены, а в голосе звучит непоколебимая уверенность."
            ],
            'elf' => [
                'male' => "{$name} - высокий эльф с благородными чертами лица и длинными светлыми волосами. Его движения грациозны, а взгляд проницателен и полон древней мудрости.",
                'female' => "{$name} - изящная эльфийка с тонкими чертами лица и серебристыми волосами. Её походка легка как ветер, а глаза сияют внутренним светом."
            ],
            'dwarf' => [
                'male' => "{$name} - коренастый дварф с густой бородой и крепкими руками. Его взгляд прямой и честный, а голос звучит как гром среди гор.",
                'female' => "{$name} - крепкая дварфийка с заплетёнными в косы волосами и решительным выражением лица. Её руки умелые, а характер твёрд как камень."
            ],
            'halfling' => [
                'male' => "{$name} - жизнерадостный полурослик с кудрявыми волосами и весёлыми глазами. Его движения быстры и ловки, а улыбка заразительна.",
                'female' => "{$name} - очаровательная полуросличка с милым личиком и звонким голосом. Её глаза полны любопытства, а характер дружелюбен и открыт."
            ],
            'tabaxi' => [
                'male' => "{$name} - грациозный табакси с ярким мехом и острыми когтями. Его движения бесшумны и точны, а глаза светятся любопытством и хитростью.",
                'female' => "{$name} - изящная табакси с мягким мехом и гибким телом. Её походка легка и бесшумна, а взгляд полон загадочности и мудрости."
            ],
            'dragonborn' => [
                'male' => "{$name} - величественный драконорожденный с чешуйчатой кожей и внушительной осанкой. Его глаза горят внутренним огнем, а голос звучит как гром.",
                'female' => "{$name} - гордая драконорожденная с благородными чертами и чешуйчатой кожей. Её движения полны достоинства, а взгляд проницателен и мудр."
            ],
            'tiefling' => [
                'male' => "{$name} - загадочный тифлинг с рогами и хвостом, чьи глаза светятся адским огнем. Его присутствие внушает трепет, а голос звучит как шепот демонов.",
                'female' => "{$name} - очаровательная тифлинг с изящными рогами и хвостом, чьи глаза мерцают таинственным светом. Её движения грациозны, а характер загадочен."
            ]
        ];
        
        if (isset($descriptions[$race][$gender])) {
            return $descriptions[$race][$gender];
        }
        
        // Fallback для других рас
        return "{$name} - представитель расы {$race}, чей внешний вид отражает особенности его народа. Готов к приключениям и полон решимости.";
    }
    
    /**
     * Генерация fallback предыстории без AI
     */
    private function generateFallbackBackground($character) {
        $race = $character['race'];
        $class = $character['class'];
        $occupation = $character['occupation'];
        $name = $character['name'];
        
        $backgrounds = [
            'human' => "{$name} вырос в мире людей, где научился ценить силу и упорство. Работая {$occupation}, он развил навыки, которые привели его к пути {$class}.",
            'elf' => "{$name} провёл долгие годы в изучении древних знаний своего народа. Его интерес к {$occupation} и природная склонность к магии привели к становлению {$class}.",
            'dwarf' => "{$name} родился среди гор и камня, где почитается мастерство и честь. Работая {$occupation}, он развил качества, необходимые для пути {$class}.",
            'halfling' => "{$name} вырос в уютном мире полуросликов, где ценится дружба и смекалка. Его опыт в {$occupation} помог развить навыки, полезные для {$class}."
        ];
        
        if (isset($backgrounds[$race])) {
            return $backgrounds[$race];
        }
        
        // Fallback для других рас
        return "{$name} прошёл непростой путь от {$occupation} до {$class}. Его опыт и навыки делают его ценным союзником в любом приключении.";
    }
    
    /**
     * Вызов DeepSeek API
     */
    private function callDeepSeek($prompt) {
        if (!$this->deepseek_api_key) {
            return null;
        }
        
        // Проверяем доступность cURL
        if (!function_exists('curl_init')) {
            return null;
        }
        
        $data = [
            'model' => 'deepseek-chat',
            'messages' => [
                ['role' => 'system', 'content' => 'Ты помощник мастера D&D. Создавай интересных и атмосферных персонажей.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'max_tokens' => 200,
            'temperature' => 0.8
        ];
        
        $ch = curl_init('https://api.deepseek.com/v1/chat/completions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->deepseek_api_key
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($response === false || $httpCode !== 200) {
            return null;
        }
        
        $result = json_decode($response, true);
        
        if (isset($result['choices'][0]['message']['content'])) {
            return trim($result['choices'][0]['message']['content']);
        }
        
        return null;
    }
}

// Обработка запроса
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $generator = new CharacterGenerator();
    $result = $generator->generateCharacter($_POST);
    
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Метод не поддерживается'
    ]);
}
?>
