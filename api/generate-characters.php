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
            
            // Если все еще нет имен, используем унисекс имена
            if (empty($nameList) && !empty($raceData['unisex'])) {
                $nameList = $raceData['unisex'];
            }
            
            // Только в крайнем случае используем имена другого пола
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
            'male' => ['Торин', 'Гимли', 'Леголас', 'Арагорн', 'Боромир', 'Гэндальф', 'Фродо', 'Сэм', 'Мерри', 'Пиппин'],
            'female' => ['Арвен', 'Галадриэль', 'Эовин', 'Розмари', 'Лютиэн', 'Идриль', 'Анкалиме', 'Нимродэль', 'Элвинг', 'Аэрин'],
            'unisex' => ['Ривен', 'Скай', 'Тейлор', 'Морган', 'Кейси', 'Джордан', 'Алексис', 'Дрю', 'Ким', 'Пэт']
        ];
        
        if ($gender === 'random') {
            $gender = rand(0, 1) ? 'male' : 'female';
        }
        
        // Сначала пробуем имена для конкретного пола, потом унисекс
        if (isset($fallbackNames[$gender]) && !empty($fallbackNames[$gender])) {
            return $fallbackNames[$gender][array_rand($fallbackNames[$gender])];
        } elseif (isset($fallbackNames['unisex']) && !empty($fallbackNames['unisex'])) {
            return $fallbackNames['unisex'][array_rand($fallbackNames['unisex'])];
        } else {
            // Крайний случай
            return $gender === 'male' ? 'Торин' : 'Арвен';
        }
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
                'character' => $character
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
     * Генерация описания с помощью AI или улучшенных шаблонов
     */
    private function generateDescription($character) {
        // Пытаемся использовать AI, если доступен
        if ($this->deepseek_api_key) {
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
                if ($response) {
                    return $response;
                }
            } catch (Exception $e) {
                error_log("AI description generation failed: " . $e->getMessage());
            }
        }
        
        // Если AI недоступен или не сработал, используем улучшенные шаблоны
        return $this->generateFallbackDescription($character);
    }
    
    /**
     * Генерация предыстории с помощью AI или улучшенных шаблонов
     */
    private function generateBackground($character) {
        // Пытаемся использовать AI, если доступен
        if ($this->deepseek_api_key) {
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
                if ($response) {
                    return $response;
                }
            } catch (Exception $e) {
                error_log("AI background generation failed: " . $e->getMessage());
            }
        }
        
        // Если AI недоступен или не сработал, используем улучшенные шаблоны
        return $this->generateFallbackBackground($character);
    }
    
    /**
     * Генерация улучшенного fallback описания без AI
     */
    private function generateFallbackDescription($character) {
        $race = $character['race'];
        $gender = $character['gender'];
        $name = $character['name'];
        $class = $character['class'];
        $occupation = $character['occupation'];
        $abilities = $character['abilities'];
        
        // Определяем доминирующую характеристику
        $maxAbility = max($abilities);
        $dominantAbility = array_search($maxAbility, $abilities);
        
        // Живые и атмосферные описания с учетом расы, класса и пола
        $descriptions = [
            'human' => [
                'male' => [
                    'Воин' => "{$name} - закаленный в боях мужчина с шрамами на руках и решительным взглядом. Его опыт как {$occupation} научил его читать людей, а годы тренировок сделали каждое движение смертоносным. В глазах горит огонь воина, готового к любому вызову.",
                    'Волшебник' => "{$name} - худощавый мужчина с задумчивым выражением лица и руками, покрытыми следами магических экспериментов. Его работа {$occupation} развила аналитический ум, а постоянное изучение магии оставило след в каждом жесте. В глазах мерцает древняя мудрость.",
                    'Плут' => "{$name} - ловкий мужчина с быстрыми движениями и острым взглядом, который ничего не упускает. Опыт {$occupation} научил его читать намерения людей, а природная ловкость позволяет исчезать в тенях. В улыбке скрывается тысяча секретов.",
                    'Жрец' => "{$name} - степенный мужчина с добрыми глазами и уверенной походкой. Его работа {$occupation} научила его понимать нужды людей, а вера придает каждому слову особую силу. В голосе звучит непоколебимая уверенность в высшей справедливости.",
                    'default' => "{$name} - уверенный в себе мужчина с проницательным взглядом и опытными руками. Его работа {$occupation} наложила отпечаток на характер, сделав его одновременно практичным и мечтательным. В каждом движении чувствуется внутренняя сила."
                ],
                'female' => [
                    'Волшебник' => "{$name} - элегантная женщина с загадочным взглядом и грациозными движениями. Её опыт как {$occupation} развил острый ум, а магические исследования оставили след в каждом жесте. В глазах мерцает таинственный свет древних знаний.",
                    'Бард' => "{$name} - очаровательная женщина с музыкальным голосом и выразительными жестами. Её работа {$occupation} научила её понимать человеческую природу, а природная харизма завораживает всех вокруг. В улыбке скрывается тысяча историй.",
                    'Плут' => "{$name} - ловкая женщина с кошачьей грацией и острым умом. Опыт {$occupation} развил в ней наблюдательность, а природная ловкость позволяет исчезать в нужный момент. В глазах читается хитрость и находчивость.",
                    'default' => "{$name} - уверенная женщина с умным взглядом и грациозными движениями. Её работа {$occupation} научила её быть практичной, а природная интуиция помогает принимать верные решения. В голосе звучит внутренняя сила."
                ]
            ],
            'elf' => [
                'male' => [
                    'Волшебник' => "{$name} - высокий эльф с серебристыми волосами и глазами, полными древней мудрости. Его работа {$occupation} добавила к эльфийской элегантности практический опыт, а магические исследования оставили след в каждом движении. В голосе звучит мелодия веков.",
                    'Следопыт' => "{$name} - стройный эльф с острым взглядом и бесшумной походкой. Опыт {$occupation} научил его читать знаки природы, а эльфийская грация сочетается с охотничьими инстинктами. В движениях чувствуется связь с древними лесами.",
                    'default' => "{$name} - благородный эльф с изящными чертами лица и проницательным взглядом. Его работа {$occupation} добавила к врожденной мудрости практический опыт, а эльфийская элегантность проявляется в каждом жесте."
                ],
                'female' => [
                    'Волшебник' => "{$name} - изящная эльфийка с светящимися глазами и грациозными движениями. Её опыт {$occupation} развил аналитический ум, а магические способности озаряют каждое действие таинственным светом. В голосе звучит древняя мудрость.",
                    'Бард' => "{$name} - очаровательная эльфийка с музыкальным голосом и выразительными жестами. Её работа {$occupation} научила её понимать красоту во всех её проявлениях, а эльфийская грация завораживает всех вокруг.",
                    'default' => "{$name} - элегантная эльфийка с тонкими чертами лица и мудрым взглядом. Её работа {$occupation} добавила к врожденной элегантности практический опыт, а эльфийская мудрость проявляется в каждом слове."
                ]
            ],
            'dwarf' => [
                'male' => [
                    'Воин' => "{$name} - коренастый дварф с густой бородой и крепкими руками, покрытыми мозолями от оружия. Его работа {$occupation} развила мастерство, а дварфийская стойкость делает его непоколебимым в бою. В глазах горит огонь воина.",
                    'default' => "{$name} - крепкий дварф с честным взглядом и надежными руками мастера. Его работа {$occupation} развила в нем упорство и мастерство, а дварфийская честность проявляется в каждом слове и деле."
                ],
                'female' => [
                    'default' => "{$name} - крепкая дварфийка с заплетёнными в сложные косы волосами и решительным выражением лица. Её работа {$occupation} развила практичность и надежность, а дварфийская стойкость проявляется в каждом движении."
                ]
            ],
            'halfling' => [
                'male' => [
                    'Плут' => "{$name} - жизнерадостный полурослик с озорными глазами и быстрыми движениями. Его работа {$occupation} научила его находить выход из любой ситуации, а природная ловкость позволяет исчезать в нужный момент. В улыбке скрывается тысяча проказ.",
                    'default' => "{$name} - добродушный полурослик с кудрявыми волосами и звонким голосом. Его работа {$occupation} развила общительность и находчивость, а природный оптимизм помогает находить радость в любой ситуации."
                ],
                'female' => [
                    'default' => "{$name} - очаровательная полуросличка с милым личиком и музыкальным голосом. Её работа {$occupation} развила общительность и находчивость, а природная грация и оптимизм завораживают всех вокруг."
                ]
            ],
            'tiefling' => [
                'male' => [
                    'Колдун' => "{$name} - загадочный тифлинг с рогами, изящно изогнутыми над головой, и глазами, мерцающими адским огнем. Его работа {$occupation} научила его скрывать истинную природу, а врожденная магия оставляет след в каждом движении. В голосе звучит таинственная мелодия.",
                    'default' => "{$name} - харизматичный тифлинг с изящными рогами и хвостом, чьи глаза светятся внутренним огнем. Его работа {$occupation} развила способность манипулировать ситуациями, а врожденная харизма завораживает окружающих."
                ],
                'female' => [
                    'default' => "{$name} - очаровательная тифлинг с изящными рогами и грациозными движениями, чьи глаза мерцают таинственным светом. Её работа {$occupation} развила интуицию и способность читать людей, а врожденная харизма притягивает взгляды."
                ]
            ]
        ];
        
        // Пытаемся найти подходящее описание
        // Маппинг русских названий рас на английские ключи
        $raceMapping = [
            'Человек' => 'human',
            'Эльф' => 'elf',
            'Дварф' => 'dwarf',
            'Полурослик' => 'halfling',
            'Тифлинг' => 'tiefling',
            'Драконорожденный' => 'dragonborn',
            'Табакси' => 'tabaxi'
        ];
        
        // Маппинг пола
        $genderMapping = [
            'Мужчина' => 'male',
            'Женщина' => 'female'
        ];
        
        $raceKey = $raceMapping[$race] ?? 'human';
        $genderKey = $genderMapping[$gender] ?? 'male';
        
        // Отладочная информация
        error_log("DEBUG: race='{$race}', raceKey='{$raceKey}', gender='{$gender}', genderKey='{$genderKey}', class='{$class}'");
        error_log("DEBUG: Available classes for {$raceKey} {$genderKey}: " . (isset($descriptions[$raceKey][$genderKey]) ? implode(', ', array_keys($descriptions[$raceKey][$genderKey])) : 'NONE'));
        error_log("DEBUG: descriptions[{$raceKey}][{$genderKey}][{$class}] exists: " . (isset($descriptions[$raceKey][$genderKey][$class]) ? 'YES' : 'NO'));
        
        if (isset($descriptions[$raceKey][$genderKey][$class])) {
            error_log("DEBUG: Using specific description for {$raceKey} {$genderKey} {$class}");
            return $descriptions[$raceKey][$genderKey][$class];
        } elseif (isset($descriptions[$raceKey][$genderKey]['default'])) {
            error_log("DEBUG: Using default description for {$raceKey} {$genderKey}");
            return $descriptions[$raceKey][$genderKey]['default'];
        }
        
        error_log("DEBUG: Using fallback description");
        
        // Fallback для других рас
        $abilityDescriptions = [
            'str' => 'физически сильный',
            'dex' => 'ловкий и быстрый',
            'con' => 'выносливый и крепкий',
            'int' => 'умный и сообразительный',
            'wis' => 'мудрый и проницательный',
            'cha' => 'харизматичный и обаятельный'
        ];
        
        $abilityDesc = $abilityDescriptions[$dominantAbility] ?? 'опытный';
        $genderPronoun = ($gender === 'Женщина') ? 'её' : 'его';
        $genderEnding = ($gender === 'Женщина') ? 'а' : '';
        
        return "{$name} - {$abilityDesc} представитель{$genderEnding} расы {$race}, чей характер отражает опыт работы {$occupation}. В каждом движении чувствуется внутренняя сила и готовность к приключениям.";
    }
    
    /**
     * Генерация улучшенной fallback предыстории без AI
     */
    private function generateFallbackBackground($character) {
        $race = $character['race'];
        $class = $character['class'];
        $occupation = $character['occupation'];
        $name = $character['name'];
        $alignment = $character['alignment'];
        $abilities = $character['abilities'];
        $gender = $character['gender'];
        
        // Определяем доминирующую характеристику
        $maxAbility = max($abilities);
        $dominantAbility = array_search($maxAbility, $abilities);
        
        // Живые и атмосферные предыстории с учетом расы, класса и пола
        $backgrounds = [
            'human' => [
                'male' => [
                    'Воин' => "{$name} вырос в суровом мире, где каждый день был борьбой за выживание. Работая {$occupation}, он научился читать намерения людей и предугадывать их действия. Однажды, когда бандиты напали на его деревню, он взял в руки оружие и защитил своих близких. Этот момент изменил его жизнь навсегда, приведя к пути {$class}. Теперь он путешествует по миру, защищая слабых и сражаясь за справедливость.",
                    'Волшебник' => "{$name} всегда чувствовал, что в мире есть нечто большее, чем то, что видят обычные люди. Работая {$occupation}, он развил аналитический ум и любознательность. Однажды он нашел древний гримуар в заброшенной башне, и с тех пор его жизнь изменилась навсегда. Магия открыла перед ним новые горизонты, и теперь как {$class} он стремится постичь все тайны вселенной.",
                    'Плут' => "{$name} вырос на улицах большого города, где каждый день был уроком выживания. Работая {$occupation}, он научился читать людей и находить слабые места в любой системе. Однажды он спас жизнь местному вору, и тот научил его искусству скрытности. С тех пор {$name} использует свои навыки для помощи тем, кто не может защитить себя сам.",
                    'default' => "{$name} вырос в мире, где каждый должен найти свое место. Работая {$occupation}, он развил практический ум и упорство. Однажды судьба привела его к важному выбору, который изменил его жизнь навсегда. Теперь как {$class} он путешествует по миру, ища свое предназначение и помогая тем, кто в этом нуждается."
                ],
                'female' => [
                    'Волшебник' => "{$name} всегда была мечтательницей, чьи мысли уносились далеко за пределы обыденности. Работая {$occupation}, она развила острый ум и любознательность. Однажды она обнаружила в себе магические способности, когда случайно зажгла свечу одним взглядом. С тех пор она посвятила себя изучению магии, стремясь постичь все тайны вселенной.",
                    'Бард' => "{$name} выросла в семье музыкантов, где музыка была не просто развлечением, а способом рассказывать истории. Работая {$occupation}, она научилась понимать человеческую природу и находить красоту в самых простых вещах. Однажды она поняла, что может влиять на людей через музыку и слова. Теперь она путешествует по миру, собирая истории и вдохновляя других.",
                    'default' => "{$name} выросла в мире, где женщины должны были быть сильными и независимыми. Работая {$occupation}, она развила практический ум и упорство. Однажды она поняла, что может изменить мир к лучшему, и выбрала путь {$class}. Теперь она путешествует, помогая тем, кто в этом нуждается."
                ]
            ],
            'elf' => [
                'male' => [
                    'Волшебник' => "{$name} провел столетия в изучении древних знаний своего народа. Его работа {$occupation} добавила к эльфийской мудрости практический опыт. Однажды он обнаружил в себе необычайные магические способности, когда случайно оживил увядший цветок. С тех пор он посвятил себя изучению магии, стремясь постичь все тайны вселенной и сохранить древние знания для будущих поколений.",
                    'Следопыт' => "{$name} вырос в древних лесах, где каждый звук и каждое движение имели значение. Его работа {$occupation} научила его читать знаки природы и понимать язык зверей. Однажды он спас раненого волка, и тот стал его верным спутником. С тех пор он защищает дикую природу от тех, кто хочет её уничтожить.",
                    'default' => "{$name} провел долгие годы в изучении древних традиций своего народа. Его работа {$occupation} добавила к эльфийской мудрости практический опыт. Однажды он понял, что должен покинуть родные леса и отправиться в мир людей, чтобы помочь им понять красоту и мудрость природы."
                ],
                'female' => [
                    'Волшебник' => "{$name} всегда чувствовала связь с древней магией, которая текла в её жилах. Её работа {$occupation} развила аналитический ум, а эльфийская интуиция помогала ей понимать суть вещей. Однажды она обнаружила в себе необычайные магические способности и решила посвятить себя изучению древних знаний.",
                    'Бард' => "{$name} выросла среди древних деревьев, где каждая песня рассказывала историю веков. Её работа {$occupation} научила её понимать красоту во всех её проявлениях. Однажды она поняла, что может передавать мудрость предков через музыку и слова. Теперь она путешествует по миру, сохраняя древние истории и вдохновляя других.",
                    'default' => "{$name} провела долгие годы в изучении древних традиций своего народа. Её работа {$occupation} добавила к эльфийской мудрости практический опыт. Однажды она поняла, что должна поделиться своей мудростью с миром людей."
                ]
            ],
            'dwarf' => [
                'male' => [
                    'Воин' => "{$name} вырос среди гор и камня, где каждый день был уроком выносливости и мастерства. Его работа {$occupation} развила в нем упорство и внимание к деталям. Однажды, когда орки напали на его клан, он взял в руки оружие и защитил свой народ. С тех пор он посвятил себя защите слабых и сохранению традиций своего народа.",
                    'default' => "{$name} родился среди гор и камня, где почитается мастерство и честь. Его работа {$occupation} развила в нем упорство и внимание к деталям. Однажды он понял, что должен покинуть родные горы и отправиться в мир, чтобы защищать то, что дорого его сердцу."
                ],
                'female' => [
                    'default' => "{$name} выросла среди гор и камня, где женщины должны были быть такими же сильными, как и мужчины. Её работа {$occupation} развила в ней упорство и практичность. Однажды она поняла, что может изменить мир к лучшему, используя свою силу и мудрость."
                ]
            ],
            'halfling' => [
                'male' => [
                    'Плут' => "{$name} вырос в уютной деревне полуросликов, где каждый знал друг друга. Его работа {$occupation} развила в нем общительность и находчивость. Однажды он случайно подслушал разговор бандитов, планирующих ограбить его деревню. Используя свою ловкость и хитрость, он сумел предупредить жителей и спасти их от беды. С тех пор он использует свои навыки для помощи другим.",
                    'default' => "{$name} вырос в уютном мире полуросликов, где ценится дружба и смекалка. Его работа {$occupation} развила в нем общительность и находчивость. Однажды он понял, что даже маленький может совершить большие дела, и отправился в мир, чтобы доказать это."
                ],
                'female' => [
                    'default' => "{$name} выросла в уютном мире полуросликов, где ценится дружба и смекалка. Её работа {$occupation} развила в ней общительность и находчивость. Однажды она поняла, что может принести радость и помощь в мир, который часто бывает суровым."
                ]
            ],
            'tiefling' => [
                'male' => [
                    'Колдун' => "{$name} родился с адским наследием, что всегда делало его особенным и вызывало страх у окружающих. Его работа {$occupation} научила его скрывать свою истинную природу и находить общий язык с людьми. Однажды он обнаружил в себе темную магию и понял, что может использовать её не только для разрушения, но и для защиты. Теперь он путешествует по миру, пытаясь доказать, что даже адское наследие может служить добру.",
                    'default' => "{$name} родился с адским наследием, что всегда делало его особенным. Его работа {$occupation} научила его находить общий язык с людьми и скрывать свою истинную природу. Однажды он понял, что может использовать свои способности для помощи другим, а не для причинения вреда."
                ],
                'female' => [
                    'default' => "{$name} родилась с адским наследием, что всегда делало её особенной. Её работа {$occupation} научила её понимать человеческую природу и находить красоту даже в самых темных уголках души. Однажды она поняла, что может использовать свои способности для создания красоты, а не разрушения."
                ]
            ]
        ];
        
        // Пытаемся найти подходящую предысторию
        // Маппинг русских названий рас на английские ключи
        $raceMapping = [
            'Человек' => 'human',
            'Эльф' => 'elf',
            'Дварф' => 'dwarf',
            'Полурослик' => 'halfling',
            'Тифлинг' => 'tiefling',
            'Драконорожденный' => 'dragonborn',
            'Табакси' => 'tabaxi'
        ];
        
        // Маппинг пола
        $genderMapping = [
            'Мужчина' => 'male',
            'Женщина' => 'female'
        ];
        
        $raceKey = $raceMapping[$race] ?? 'human';
        $genderKey = $genderMapping[$gender] ?? 'male';
        
        if (isset($backgrounds[$raceKey][$genderKey][$class])) {
            return $backgrounds[$raceKey][$genderKey][$class];
        } elseif (isset($backgrounds[$raceKey][$genderKey]['default'])) {
            return $backgrounds[$raceKey][$genderKey]['default'];
        }
        
        // Fallback для других рас
        $genderPronoun = ($gender === 'Женщина') ? 'её' : 'его';
        $genderVerb = ($gender === 'Женщина') ? 'стремится' : 'стремится';
        
        return "{$name} прошёл непростой путь от {$occupation} до {$class}. Опыт работы научил {$genderPronoun} ценить упорство и мастерство. Теперь {$genderPronoun} {$genderVerb} найти свое место в мире и использовать свои навыки для помощи другим.";
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
