<?php
require_once 'config.php';

echo "=== Тест fallback генерации персонажей ===\n\n";

// Создаем тестовые данные персонажа
$testCharacter = [
    'name' => 'Арагорн',
    'race' => 'human',
    'class' => 'fighter',
    'level' => 5,
    'gender' => 'male',
    'alignment' => 'lawful-good',
    'occupation' => 'Следопыт',
    'hit_points' => 45,
    'armor_class' => 16,
    'speed' => 30,
    'initiative' => 2,
    'proficiency_bonus' => 3,
    'damage' => '1d8+3',
    'abilities' => [
        'str' => 16,
        'dex' => 14,
        'con' => 14,
        'int' => 12,
        'wis' => 14,
        'cha' => 10
    ],
    'proficiencies' => ['Мечи', 'Луки', 'Доспехи'],
    'spells' => []
];

echo "Тестовый персонаж: " . $testCharacter['name'] . "\n";
echo "Раса: " . $testCharacter['race'] . "\n";
echo "Класс: " . $testCharacter['class'] . "\n";
echo "Пол: " . $testCharacter['gender'] . "\n\n";

// Тестируем fallback описания
echo "=== Тест fallback описания ===\n";

// Создаем экземпляр генератора
require_once 'api/generate-characters.php';

// Создаем экземпляр класса (только для тестирования)
$generator = new CharacterGenerator();

// Используем reflection для доступа к приватным методам
$reflection = new ReflectionClass($generator);
$generateDescriptionMethod = $reflection->getMethod('generateFallbackDescription');
$generateDescriptionMethod->setAccessible(true);

$generateBackgroundMethod = $reflection->getMethod('generateFallbackBackground');
$generateBackgroundMethod->setAccessible(true);

// Тестируем описание
echo "Описание персонажа:\n";
$description = $generateDescriptionMethod->invoke($generator, $testCharacter);
echo $description . "\n\n";

// Тестируем предысторию
echo "Предыстория персонажа:\n";
$background = $generateBackgroundMethod->invoke($generator, $testCharacter);
echo $background . "\n\n";

echo "=== Тест завершен ===\n";
?>
