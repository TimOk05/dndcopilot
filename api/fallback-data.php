<?php
/**
 * Fallback данные для работы без внешних API
 */

class FallbackData {
    
    /**
     * Fallback данные для рас
     */
    public static function getRaces() {
        return [
            'human' => [
                'name' => 'Человек',
                'ability_bonuses' => ['str' => 1, 'dex' => 1, 'con' => 1, 'int' => 1, 'wis' => 1, 'cha' => 1],
                'traits' => ['Универсальность', 'Дополнительное владение навыком'],
                'speed' => 30
            ],
            'elf' => [
                'name' => 'Эльф',
                'ability_bonuses' => ['dex' => 2],
                'traits' => ['Темное зрение', 'Келебрас', 'Иммунитет к усыплению', 'Транс'],
                'speed' => 30
            ],
            'dwarf' => [
                'name' => 'Дварф',
                'ability_bonuses' => ['con' => 2],
                'traits' => ['Темное зрение', 'Устойчивость к яду', 'Владение боевым топором'],
                'speed' => 25
            ],
            'halfling' => [
                'name' => 'Полурослик',
                'ability_bonuses' => ['dex' => 2],
                'traits' => ['Удача', 'Смелость', 'Ловкость полурослика'],
                'speed' => 25
            ],
            'orc' => [
                'name' => 'Орк',
                'ability_bonuses' => ['str' => 2, 'con' => 1],
                'traits' => ['Темное зрение', 'Агрессивность', 'Мощное телосложение'],
                'speed' => 30
            ],
            'tiefling' => [
                'name' => 'Тифлинг',
                'ability_bonuses' => ['int' => 1, 'cha' => 2],
                'traits' => ['Темное зрение', 'Устойчивость к огню', 'Адское наследие'],
                'speed' => 30
            ],
            'dragonborn' => [
                'name' => 'Драконорожденный',
                'ability_bonuses' => ['str' => 2, 'cha' => 1],
                'traits' => ['Дыхание дракона', 'Устойчивость к урону', 'Драконье наследие'],
                'speed' => 30
            ],
            'gnome' => [
                'name' => 'Гном',
                'ability_bonuses' => ['int' => 2],
                'traits' => ['Темное зрение', 'Гномья хитрость', 'Естественная иллюзия'],
                'speed' => 25
            ],
            'half-elf' => [
                'name' => 'Полуэльф',
                'ability_bonuses' => ['cha' => 2, 'dex' => 1, 'int' => 1],
                'traits' => ['Темное зрение', 'Универсальность', 'Эльфийское наследие'],
                'speed' => 30
            ],
            'half-orc' => [
                'name' => 'Полуорк',
                'ability_bonuses' => ['str' => 2, 'con' => 1],
                'traits' => ['Темное зрение', 'Угрожающий', 'Устойчивость к урону'],
                'speed' => 30
            ]
        ];
    }
    
    /**
     * Fallback данные для классов
     */
    public static function getClasses() {
        return [
            'fighter' => [
                'name' => 'Воин',
                'hit_die' => 10,
                'primary_ability' => 'str',
                'saving_throw_proficiencies' => ['str', 'con'],
                'armor_proficiencies' => ['light', 'medium', 'heavy', 'shields'],
                'weapon_proficiencies' => ['simple', 'martial']
            ],
            'wizard' => [
                'name' => 'Волшебник',
                'hit_die' => 6,
                'primary_ability' => 'int',
                'saving_throw_proficiencies' => ['int', 'wis'],
                'armor_proficiencies' => [],
                'weapon_proficiencies' => ['daggers', 'quarterstaffs', 'light_crossbows']
            ],
            'rogue' => [
                'name' => 'Плут',
                'hit_die' => 8,
                'primary_ability' => 'dex',
                'saving_throw_proficiencies' => ['dex', 'int'],
                'armor_proficiencies' => ['light'],
                'weapon_proficiencies' => ['simple', 'hand_crossbows', 'longswords', 'rapiers', 'shortswords']
            ],
            'cleric' => [
                'name' => 'Жрец',
                'hit_die' => 8,
                'primary_ability' => 'wis',
                'saving_throw_proficiencies' => ['wis', 'cha'],
                'armor_proficiencies' => ['light', 'medium', 'shields'],
                'weapon_proficiencies' => ['simple']
            ],
            'ranger' => [
                'name' => 'Следопыт',
                'hit_die' => 10,
                'primary_ability' => 'dex',
                'saving_throw_proficiencies' => ['str', 'dex'],
                'armor_proficiencies' => ['light', 'medium', 'shields'],
                'weapon_proficiencies' => ['simple', 'martial']
            ],
            'barbarian' => [
                'name' => 'Варвар',
                'hit_die' => 12,
                'primary_ability' => 'str',
                'saving_throw_proficiencies' => ['str', 'con'],
                'armor_proficiencies' => ['light', 'medium', 'shields'],
                'weapon_proficiencies' => ['simple', 'martial']
            ],
            'bard' => [
                'name' => 'Бард',
                'hit_die' => 8,
                'primary_ability' => 'cha',
                'saving_throw_proficiencies' => ['dex', 'cha'],
                'armor_proficiencies' => ['light'],
                'weapon_proficiencies' => ['simple', 'hand_crossbows', 'longswords', 'rapiers', 'shortswords']
            ],
            'druid' => [
                'name' => 'Друид',
                'hit_die' => 8,
                'primary_ability' => 'wis',
                'saving_throw_proficiencies' => ['int', 'wis'],
                'armor_proficiencies' => ['light', 'medium', 'shields'],
                'weapon_proficiencies' => ['clubs', 'daggers', 'javelins', 'maces', 'quarterstaffs', 'scimitars', 'sickles', 'slings', 'spears']
            ],
            'monk' => [
                'name' => 'Монах',
                'hit_die' => 8,
                'primary_ability' => 'dex',
                'saving_throw_proficiencies' => ['str', 'dex'],
                'armor_proficiencies' => [],
                'weapon_proficiencies' => ['simple', 'shortswords']
            ],
            'paladin' => [
                'name' => 'Паладин',
                'hit_die' => 10,
                'primary_ability' => 'str',
                'saving_throw_proficiencies' => ['wis', 'cha'],
                'armor_proficiencies' => ['light', 'medium', 'heavy', 'shields'],
                'weapon_proficiencies' => ['simple', 'martial']
            ],
            'sorcerer' => [
                'name' => 'Сорсерер',
                'hit_die' => 6,
                'primary_ability' => 'cha',
                'saving_throw_proficiencies' => ['con', 'cha'],
                'armor_proficiencies' => [],
                'weapon_proficiencies' => ['daggers', 'quarterstaffs', 'light_crossbows']
            ],
            'warlock' => [
                'name' => 'Колдун',
                'hit_die' => 8,
                'primary_ability' => 'cha',
                'saving_throw_proficiencies' => ['wis', 'cha'],
                'armor_proficiencies' => ['light'],
                'weapon_proficiencies' => ['simple']
            ]
        ];
    }
    
    /**
     * Fallback данные для монстров
     */
    public static function getMonsters() {
        return [
            // Легкие противники (CR 1/8 - 1/2)
            [
                'index' => 'goblin',
                'name' => 'Гоблин',
                'type' => 'гуманоид',
                'size' => 'маленький',
                'alignment' => 'нейтрально-злой',
                'challenge_rating' => '1/4',
                'hit_points' => 7,
                'armor_class' => 15,
                'speed' => '30 футов',
                'abilities' => [
                    'str' => 8, 'dex' => 14, 'con' => 10, 'int' => 10, 'wis' => 8, 'cha' => 8
                ],
                'actions' => [
                    'scimitar' => 'Рубящая атака: +4 к попаданию, 5 (1d6+2) рубящего урона',
                    'shortbow' => 'Дальняя атака: +4 к попаданию, 5 (1d6+2) колющего урона'
                ]
            ],
            [
                'index' => 'kobold',
                'name' => 'Кобольд',
                'type' => 'гуманоид',
                'size' => 'маленький',
                'alignment' => 'законно-злой',
                'challenge_rating' => '1/8',
                'hit_points' => 5,
                'armor_class' => 12,
                'speed' => '30 футов',
                'abilities' => [
                    'str' => 7, 'dex' => 15, 'con' => 9, 'int' => 8, 'wis' => 7, 'cha' => 8
                ],
                'actions' => [
                    'dagger' => 'Колющая атака: +4 к попаданию, 4 (1d4+2) колющего урона',
                    'sling' => 'Дальняя атака: +4 к попаданию, 4 (1d4+2) дробящего урона'
                ]
            ],
            [
                'index' => 'bandit',
                'name' => 'Бандит',
                'type' => 'гуманоид',
                'size' => 'средний',
                'alignment' => 'любое не-законное',
                'challenge_rating' => '1/8',
                'hit_points' => 11,
                'armor_class' => 12,
                'speed' => '30 футов',
                'abilities' => [
                    'str' => 12, 'dex' => 12, 'con' => 12, 'int' => 10, 'wis' => 10, 'cha' => 10
                ],
                'actions' => [
                    'scimitar' => 'Рубящая атака: +3 к попаданию, 4 (1d6+1) рубящего урона',
                    'light_crossbow' => 'Дальняя атака: +3 к попаданию, 5 (1d8+1) колющего урона'
                ]
            ],
            [
                'index' => 'cultist',
                'name' => 'Культист',
                'type' => 'гуманоид',
                'size' => 'средний',
                'alignment' => 'любое не-доброе',
                'challenge_rating' => '1/8',
                'hit_points' => 9,
                'armor_class' => 12,
                'speed' => '30 футов',
                'abilities' => [
                    'str' => 11, 'dex' => 12, 'con' => 10, 'int' => 10, 'wis' => 11, 'cha' => 10
                ],
                'actions' => [
                    'scimitar' => 'Рубящая атака: +3 к попаданию, 4 (1d6+1) рубящего урона',
                    'darkness' => 'Заклинание: Создает область магической тьмы'
                ]
            ],
            [
                'index' => 'giant_rat',
                'name' => 'Гигантская крыса',
                'type' => 'зверь',
                'size' => 'маленький',
                'alignment' => 'неопределенный',
                'challenge_rating' => '1/8',
                'hit_points' => 7,
                'armor_class' => 12,
                'speed' => '30 футов',
                'abilities' => [
                    'str' => 7, 'dex' => 15, 'con' => 11, 'int' => 2, 'wis' => 10, 'cha' => 4
                ],
                'actions' => [
                    'bite' => 'Колющая атака: +4 к попаданию, 4 (1d4+2) колющего урона'
                ]
            ],
            [
                'index' => 'giant_spider',
                'name' => 'Гигантский паук',
                'type' => 'зверь',
                'size' => 'большой',
                'alignment' => 'неопределенный',
                'challenge_rating' => '1/4',
                'hit_points' => 26,
                'armor_class' => 14,
                'speed' => '30 футов, лазание 30 футов',
                'abilities' => [
                    'str' => 14, 'dex' => 16, 'con' => 12, 'int' => 2, 'wis' => 11, 'cha' => 4
                ],
                'actions' => [
                    'bite' => 'Колющая атака: +5 к попаданию, 6 (1d8+2) колющего урона + яд'
                ]
            ],
            [
                'index' => 'wolf',
                'name' => 'Волк',
                'type' => 'зверь',
                'size' => 'средний',
                'alignment' => 'неопределенный',
                'challenge_rating' => '1/4',
                'hit_points' => 11,
                'armor_class' => 13,
                'speed' => '40 футов',
                'abilities' => [
                    'str' => 12, 'dex' => 15, 'con' => 12, 'int' => 3, 'wis' => 12, 'cha' => 6
                ],
                'actions' => [
                    'bite' => 'Колющая атака: +4 к попаданию, 7 (2d4+2) колющего урона'
                ]
            ],
            [
                'index' => 'zombie',
                'name' => 'Зомби',
                'type' => 'нежить',
                'size' => 'средний',
                'alignment' => 'нейтрально-злой',
                'challenge_rating' => '1/4',
                'hit_points' => 22,
                'armor_class' => 8,
                'speed' => '20 футов',
                'abilities' => [
                    'str' => 13, 'dex' => 6, 'con' => 16, 'int' => 3, 'wis' => 6, 'cha' => 5
                ],
                'actions' => [
                    'slam' => 'Дробящая атака: +3 к попаданию, 3 (1d6) дробящего урона'
                ]
            ],
            [
                'index' => 'skeleton',
                'name' => 'Скелет',
                'type' => 'нежить',
                'size' => 'средний',
                'alignment' => 'законно-злой',
                'challenge_rating' => '1/4',
                'hit_points' => 13,
                'armor_class' => 13,
                'speed' => '30 футов',
                'abilities' => [
                    'str' => 10, 'dex' => 14, 'con' => 15, 'int' => 6, 'wis' => 8, 'cha' => 5
                ],
                'actions' => [
                    'shortsword' => 'Колющая атака: +4 к попаданию, 5 (1d6+2) колющего урона',
                    'shortbow' => 'Дальняя атака: +4 к попаданию, 5 (1d6+2) колющего урона'
                ]
            ],
            
            // Средние противники (CR 1/2 - 3)
            [
                'index' => 'orc',
                'name' => 'Орк',
                'type' => 'гуманоид',
                'size' => 'средний',
                'alignment' => 'хаотично-злой',
                'challenge_rating' => '1/2',
                'hit_points' => 15,
                'armor_class' => 13,
                'speed' => '30 футов',
                'abilities' => [
                    'str' => 16, 'dex' => 12, 'con' => 16, 'int' => 7, 'wis' => 11, 'cha' => 10
                ],
                'actions' => [
                    'greataxe' => 'Рубящая атака: +5 к попаданию, 9 (1d12+3) рубящего урона',
                    'javelin' => 'Дальняя атака: +5 к попаданию, 6 (1d6+3) колющего урона'
                ]
            ],
            [
                'index' => 'hobgoblin',
                'name' => 'Хобгоблин',
                'type' => 'гуманоид',
                'size' => 'средний',
                'alignment' => 'законно-злой',
                'challenge_rating' => '1/2',
                'hit_points' => 11,
                'armor_class' => 18,
                'speed' => '30 футов',
                'abilities' => [
                    'str' => 13, 'dex' => 12, 'con' => 12, 'int' => 10, 'wis' => 10, 'cha' => 9
                ],
                'actions' => [
                    'longsword' => 'Рубящая атака: +3 к попаданию, 5 (1d8+1) рубящего урона',
                    'longbow' => 'Дальняя атака: +3 к попаданию, 5 (1d8+1) колющего урона'
                ]
            ],
            [
                'index' => 'bugbear',
                'name' => 'Багбир',
                'type' => 'гуманоид',
                'size' => 'средний',
                'alignment' => 'хаотично-злой',
                'challenge_rating' => '1',
                'hit_points' => 27,
                'armor_class' => 16,
                'speed' => '30 футов',
                'abilities' => [
                    'str' => 15, 'dex' => 14, 'con' => 13, 'int' => 8, 'wis' => 11, 'cha' => 9
                ],
                'actions' => [
                    'morningstar' => 'Колющая атака: +4 к попаданию, 11 (2d8+2) колющего урона',
                    'javelin' => 'Дальняя атака: +4 к попаданию, 9 (2d6+2) колющего урона'
                ]
            ],
            [
                'index' => 'ogre',
                'name' => 'Огр',
                'type' => 'великан',
                'size' => 'большой',
                'alignment' => 'хаотично-злой',
                'challenge_rating' => '2',
                'hit_points' => 59,
                'armor_class' => 11,
                'speed' => '40 футов',
                'abilities' => [
                    'str' => 19, 'dex' => 8, 'con' => 16, 'int' => 5, 'wis' => 7, 'cha' => 7
                ],
                'actions' => [
                    'greatclub' => 'Дробящая атака: +6 к попаданию, 13 (2d8+4) дробящего урона',
                    'javelin' => 'Дальняя атака: +6 к попаданию, 11 (2d6+4) колющего урона'
                ]
            ],
            [
                'index' => 'minotaur',
                'name' => 'Минотавр',
                'type' => 'чудовище',
                'size' => 'большой',
                'alignment' => 'хаотично-злой',
                'challenge_rating' => '3',
                'hit_points' => 76,
                'armor_class' => 14,
                'speed' => '40 футов',
                'abilities' => [
                    'str' => 18, 'dex' => 11, 'con' => 16, 'int' => 6, 'wis' => 16, 'cha' => 9
                ],
                'actions' => [
                    'greataxe' => 'Рубящая атака: +6 к попаданию, 17 (2d12+4) рубящего урона',
                    'gore' => 'Колющая атака: +6 к попаданию, 13 (2d8+4) колющего урона'
                ]
            ],
            
            // Сложные противники (CR 5-10)
            [
                'index' => 'troll',
                'name' => 'Тролль',
                'type' => 'великан',
                'size' => 'большой',
                'alignment' => 'хаотично-злой',
                'challenge_rating' => '5',
                'hit_points' => 84,
                'armor_class' => 15,
                'speed' => '30 футов',
                'abilities' => [
                    'str' => 18, 'dex' => 13, 'con' => 20, 'int' => 7, 'wis' => 9, 'cha' => 7
                ],
                'actions' => [
                    'multiattack' => 'Тролль совершает три атаки: одну когтями и две кулаками',
                    'claws' => 'Рубящая атака: +7 к попаданию, 11 (2d6+4) рубящего урона',
                    'bite' => 'Колющая атака: +7 к попаданию, 11 (2d6+4) колющего урона'
                ],
                'special_abilities' => [
                    'regeneration' => 'Тролль восстанавливает 10 хитов в начале своего хода'
                ]
            ],
            [
                'index' => 'young_dragon',
                'name' => 'Молодой красный дракон',
                'type' => 'дракон',
                'size' => 'большой',
                'alignment' => 'хаотично-злой',
                'challenge_rating' => '7',
                'hit_points' => 178,
                'armor_class' => 18,
                'speed' => '40 футов, полет 80 футов',
                'abilities' => [
                    'str' => 23, 'dex' => 10, 'con' => 21, 'int' => 14, 'wis' => 11, 'cha' => 19
                ],
                'actions' => [
                    'bite' => 'Колющая атака: +10 к попаданию, 17 (2d10+6) колющего урона',
                    'claw' => 'Рубящая атака: +10 к попаданию, 13 (2d6+6) рубящего урона',
                    'fire_breath' => 'Конус огня: 45 (10d8) огненного урона'
                ]
            ],
            [
                'index' => 'mind_flayer',
                'name' => 'Умыслитель',
                'type' => 'аберрация',
                'size' => 'средний',
                'alignment' => 'законно-злой',
                'challenge_rating' => '7',
                'hit_points' => 71,
                'armor_class' => 15,
                'speed' => '30 футов',
                'abilities' => [
                    'str' => 11, 'dex' => 12, 'con' => 12, 'int' => 19, 'wis' => 17, 'cha' => 17
                ],
                'actions' => [
                    'tentacles' => 'Колющая атака: +7 к попаданию, 15 (2d10+4) колющего урона',
                    'extract_brain' => 'Специальная атака: мгновенная смерть при успехе'
                ],
                'special_abilities' => [
                    'mind_blast' => 'Конус психической энергии: 22 (5d8) психического урона'
                ]
            ],
            
            // Очень сложные противники (CR 13+)
            [
                'index' => 'vampire',
                'name' => 'Вампир',
                'type' => 'нежить',
                'size' => 'средний',
                'alignment' => 'законно-злой',
                'challenge_rating' => '13',
                'hit_points' => 144,
                'armor_class' => 16,
                'speed' => '30 футов',
                'abilities' => [
                    'str' => 18, 'dex' => 18, 'con' => 18, 'int' => 17, 'wis' => 15, 'cha' => 18
                ],
                'actions' => [
                    'unarmed_strike' => 'Дробящая атака: +9 к попаданию, 8 (1d8+4) дробящего урона',
                    'bite' => 'Колющая атака: +9 к попаданию, 8 (1d8+4) колющего урона + кровососание'
                ],
                'special_abilities' => [
                    'shapechanger' => 'Может превращаться в летучую мышь или туман',
                    'regeneration' => 'Восстанавливает 20 хитов в начале хода'
                ]
            ],
            [
                'index' => 'beholder',
                'name' => 'Бехолдер',
                'type' => 'аберрация',
                'size' => 'большой',
                'alignment' => 'законно-злой',
                'challenge_rating' => '13',
                'hit_points' => 180,
                'armor_class' => 18,
                'speed' => '0 футов, полет 20 футов',
                'abilities' => [
                    'str' => 10, 'dex' => 14, 'con' => 18, 'int' => 17, 'wis' => 15, 'cha' => 17
                ],
                'actions' => [
                    'eye_rays' => '10 различных лучей с разными эффектами',
                    'central_eye' => 'Луч антимагии: подавляет магию в конусе'
                ],
                'special_abilities' => [
                    'levitate' => 'Постоянно левитирует',
                    'all_around_vision' => 'Не может быть застигнут врасплох'
                ]
            ]
        ];
    }
    
    /**
     * Fallback данные для оружия
     */
    public static function getWeapons() {
        return [
            'sword' => ['name' => 'Меч', 'damage' => '1d8', 'type' => 'slashing'],
            'axe' => ['name' => 'Топор', 'damage' => '1d8', 'type' => 'slashing'],
            'bow' => ['name' => 'Лук', 'damage' => '1d8', 'type' => 'piercing'],
            'dagger' => ['name' => 'Кинжал', 'damage' => '1d4', 'type' => 'piercing'],
            'staff' => ['name' => 'Посох', 'damage' => '1d6', 'type' => 'bludgeoning'],
            'mace' => ['name' => 'Булава', 'damage' => '1d6', 'type' => 'bludgeoning']
        ];
    }
    
    /**
     * Fallback данные для заклинаний
     */
    public static function getSpells() {
        return [
            'fireball' => [
                'name' => 'Огненный шар',
                'level' => 3,
                'school' => 'evocation',
                'casting_time' => '1 действие',
                'range' => '150 футов',
                'components' => ['V', 'S', 'M'],
                'duration' => 'Мгновенно',
                'description' => 'Яркий светящийся шар огня летит к выбранной точке в пределах дистанции и взрывается'
            ],
            'magic_missile' => [
                'name' => 'Волшебная стрела',
                'level' => 1,
                'school' => 'evocation',
                'casting_time' => '1 действие',
                'range' => '120 футов',
                'components' => ['V', 'S'],
                'duration' => 'Мгновенно',
                'description' => 'Вы создаете три светящихся дротика магической силы'
            ],
            'cure_wounds' => [
                'name' => 'Лечение ран',
                'level' => 1,
                'school' => 'evocation',
                'casting_time' => '1 действие',
                'range' => 'Касание',
                'components' => ['V', 'S'],
                'duration' => 'Мгновенно',
                'description' => 'Существо, которого вы касаетесь, восстанавливает количество хитов'
            ]
        ];
    }
}
?>
