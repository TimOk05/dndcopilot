<?php
/**
 * Сервис для работы с AI (DeepSeek)
 * Обеспечивает генерацию контента с помощью AI
 */

class AIService {
    private $apiKey;
    private $apiUrl;
    private $timeout;
    
    public function __construct() {
        $this->apiKey = getApiKey('deepseek');
        $this->apiUrl = DEEPSEEK_API_URL;
        $this->timeout = API_TIMEOUT;
    }
    
    /**
     * Генерирует описание персонажа с помощью AI
     */
    public function generateCharacterDescription($character) {
        $prompt = $this->buildCharacterPrompt($character);
        return $this->callAI($prompt);
    }
    
    /**
     * Генерирует предысторию персонажа с помощью AI
     */
    public function generateCharacterBackground($character) {
        $prompt = $this->buildBackgroundPrompt($character);
        return $this->callAI($prompt);
    }
    
    /**
     * Генерирует детальное описание персонажа с помощью AI
     */
    public function generateDetailedCharacter($character) {
        $prompt = $this->buildDetailedCharacterPrompt($character);
        return $this->callAI($prompt);
    }
    
    /**
     * Строит промпт для генерации описания персонажа
     */
    private function buildCharacterPrompt($character) {
        return "Создай краткое описание персонажа D&D 5e:\n" .
               "Имя: {$character['name']}\n" .
               "Раса: {$character['race']}\n" .
               "Класс: {$character['class']}\n" .
               "Уровень: {$character['level']}\n" .
               "Пол: {$character['gender']}\n" .
               "Мировоззрение: {$character['alignment']}\n" .
               "Характеристики: СИЛ {$character['abilities']['str']}, ЛОВ {$character['abilities']['dex']}, ТЕЛ {$character['abilities']['con']}, ИНТ {$character['abilities']['int']}, МДР {$character['abilities']['wis']}, ХАР {$character['abilities']['cha']}\n\n" .
               "Создай краткое описание внешности и характера персонажа (2-3 предложения).";
    }
    
    /**
     * Строит промпт для генерации предыстории персонажа
     */
    private function buildBackgroundPrompt($character) {
        return "Создай предысторию персонажа D&D 5e:\n" .
               "Имя: {$character['name']}\n" .
               "Раса: {$character['race']}\n" .
               "Класс: {$character['class']}\n" .
               "Предыстория: {$character['background']}\n" .
               "Мировоззрение: {$character['alignment']}\n\n" .
               "Создай интересную предысторию персонажа (3-4 предложения), объясняющую как он стал {$character['class']} и что привело его к приключениям.";
    }
    
    /**
     * Строит промпт для генерации детального описания персонажа
     */
    private function buildDetailedCharacterPrompt($character) {
        return "Создай детальное описание персонажа D&D 5e:\n" .
               "Имя: {$character['name']}\n" .
               "Раса: {$character['race']}\n" .
               "Класс: {$character['class']}\n" .
               "Уровень: {$character['level']}\n" .
               "Пол: {$character['gender']}\n" .
               "Мировоззрение: {$character['alignment']}\n" .
               "Предыстория: {$character['background']}\n" .
               "Характеристики: СИЛ {$character['abilities']['str']}, ЛОВ {$character['abilities']['dex']}, ТЕЛ {$character['abilities']['con']}, ИНТ {$character['abilities']['int']}, МДР {$character['abilities']['wis']}, ХАР {$character['abilities']['cha']}\n" .
               "Хиты: {$character['hit_points']}\n" .
               "КД: {$character['armor_class']}\n" .
               "Скорость: {$character['speed']} футов\n\n" .
               "Создай полное описание персонажа, включая:\n" .
               "1. Внешность (2-3 предложения)\n" .
               "2. Характер и личность (2-3 предложения)\n" .
               "3. Предыстория и мотивация (3-4 предложения)\n" .
               "4. Особые способности или таланты (1-2 предложения)";
    }
    
    /**
     * Вызывает AI API
     */
    private function callAI($prompt) {
        if (empty($this->apiKey)) {
            logMessage('WARNING', 'AI API key not configured');
            return $this->getFallbackResponse($prompt);
        }
        
        if (!OPENSSL_AVAILABLE) {
            logMessage('WARNING', 'OpenSSL not available for AI requests');
            return $this->getFallbackResponse($prompt);
        }
        
        $messages = [
            [
                'role' => 'system',
                'content' => 'Ты - помощник мастера D&D 5e. Создавай интересные и детальные описания персонажей на русском языке.'
            ],
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ];
        
        $data = [
            'model' => 'deepseek-chat',
            'messages' => $messages,
            'max_tokens' => 1000,
            'temperature' => 0.8
        ];
        
        $ch = curl_init($this->apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            logMessage('ERROR', 'AI API request failed', [
                'error' => $error,
                'http_code' => $httpCode
            ]);
            return $this->getFallbackResponse($prompt);
        }
        
        if ($httpCode !== 200) {
            logMessage('ERROR', 'AI API returned error', [
                'http_code' => $httpCode,
                'response' => $response
            ]);
            return $this->getFallbackResponse($prompt);
        }
        
        $result = json_decode($response, true);
        
        if (!$result || !isset($result['choices'][0]['message']['content'])) {
            logMessage('ERROR', 'AI API response parsing failed', [
                'response' => $response
            ]);
            return $this->getFallbackResponse($prompt);
        }
        
        $aiResponse = $result['choices'][0]['message']['content'];
        
        // Очищаем ответ от лишних символов
        $aiResponse = $this->cleanAIResponse($aiResponse);
        
        logMessage('INFO', 'AI response generated successfully', [
            'prompt_length' => strlen($prompt),
            'response_length' => strlen($aiResponse)
        ]);
        
        return $aiResponse;
    }
    
    /**
     * Очищает ответ AI от лишних символов
     */
    private function cleanAIResponse($response) {
        // Убираем лишние символы форматирования
        $response = preg_replace('/[*_`>#\-]+/', '', $response);
        $response = str_replace(['"', "'", '"', '"', '«', '»'], '', $response);
        $response = preg_replace('/\n{2,}/', "\n", $response);
        $response = preg_replace('/\s{3,}/', "\n", $response);
        
        // Разбиваем длинные строки
        $lines = explode("\n", $response);
        $formatted = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if (mb_strlen($line) > 90) {
                $formatted = array_merge($formatted, str_split($line, 80));
            } else {
                $formatted[] = $line;
            }
        }
        
        return implode("\n", $formatted);
    }
    
    /**
     * Возвращает fallback ответ, если AI недоступен
     */
    private function getFallbackResponse($prompt) {
        // Извлекаем данные персонажа из промпта
        $characterData = $this->extractCharacterDataFromPrompt($prompt);
        
        if (strpos($prompt, 'описание персонажа') !== false) {
            return $this->generateDescriptionFromData($characterData);
        }
        
        if (strpos($prompt, 'предыстория') !== false) {
            return $this->generateBackgroundFromData($characterData);
        }
        
        return "Персонаж готов к приключениям и имеет все необходимые навыки для успешного путешествия.";
    }
    
    /**
     * Извлекает данные персонажа из промпта
     */
    private function extractCharacterDataFromPrompt($prompt) {
        $data = [];
        
        // Извлекаем имя
        if (preg_match('/Имя: ([^\n]+)/', $prompt, $matches)) {
            $data['name'] = trim($matches[1]);
        }
        
        // Извлекаем расу
        if (preg_match('/Раса: ([^\n]+)/', $prompt, $matches)) {
            $data['race'] = trim($matches[1]);
        }
        
        // Извлекаем класс
        if (preg_match('/Класс: ([^\n]+)/', $prompt, $matches)) {
            $data['class'] = trim($matches[1]);
        }
        
        // Извлекаем уровень
        if (preg_match('/Уровень: (\d+)/', $prompt, $matches)) {
            $data['level'] = (int)$matches[1];
        }
        
        // Извлекаем мировоззрение
        if (preg_match('/Мировоззрение: ([^\n]+)/', $prompt, $matches)) {
            $data['alignment'] = trim($matches[1]);
        }
        
        // Извлекаем характеристики
        if (preg_match('/Характеристики: СИЛ (\d+), ЛОВ (\d+), ТЕЛ (\d+), ИНТ (\d+), МДР (\d+), ХАР (\d+)/', $prompt, $matches)) {
            $data['abilities'] = [
                'str' => (int)$matches[1],
                'dex' => (int)$matches[2],
                'con' => (int)$matches[3],
                'int' => (int)$matches[4],
                'wis' => (int)$matches[5],
                'cha' => (int)$matches[6]
            ];
        }
        
        return $data;
    }
    
    /**
     * Генерирует описание персонажа на основе данных
     */
    private function generateDescriptionFromData($data) {
        $description = [];
        
        // Внешность на основе расы и характеристик
        if (isset($data['race']) && isset($data['abilities'])) {
            $description[] = $this->generateAppearance($data['race'], $data['abilities']);
        }
        
        // Характер на основе класса и мировоззрения
        if (isset($data['class']) && isset($data['alignment'])) {
            $description[] = $this->generatePersonality($data['class'], $data['alignment']);
        }
        
        // Особые способности на основе расы и класса
        if (isset($data['race']) && isset($data['class'])) {
            $description[] = $this->generateAbilities($data['race'], $data['class']);
        }
        
        return implode("\n\n", array_filter($description));
    }
    
    /**
     * Генерирует предысторию персонажа
     */
    private function generateBackgroundFromData($data) {
        $background = [];
        
        if (isset($data['race']) && isset($data['class'])) {
            $background[] = $this->getRaceBackground($data['race']);
            $background[] = $this->getClassBackground($data['class'], $data['level'] ?? 1);
        }
        
        if (isset($data['alignment'])) {
            $background[] = $this->getAlignmentMotivation($data['alignment']);
        }
        
        return implode("\n\n", array_filter($background));
    }
    
    /**
     * Генерирует описание внешности
     */
    private function generateAppearance($race, $abilities) {
        $raceAppearance = [
            'человек' => 'Среднего роста с типичными человеческими чертами лица.',
            'эльф' => 'Высокий и грациозный, с заостренными ушами и выразительными глазами.',
            'дварф' => 'Крепкого телосложения, с густой бородой и решительным взглядом.',
            'халфлинг' => 'Невысокого роста, с веселыми глазами и дружелюбной улыбкой.',
            'гном' => 'Маленького роста, с любопытными глазами и живой мимикой.',
            'полуорк' => 'Крупного телосложения, с выдающимися клыками и сильными чертами лица.',
            'тифлинг' => 'С рогами, хвостом и необычным цветом кожи, что выдает его инфернальное происхождение.',
            'драконорожденный' => 'С чешуйчатой кожей и драконьими чертами, отражающими его предков.',
            'аасимар' => 'С небесным сиянием в глазах и благородными чертами лица.'
        ];
        
        $baseAppearance = $raceAppearance[strtolower($race)] ?? 'С типичными чертами своей расы.';
        
        // Добавляем детали на основе характеристик
        $details = [];
        if ($abilities['str'] >= 16) $details[] = 'мощного телосложения';
        if ($abilities['dex'] >= 16) $details[] = 'ловкий и подвижный';
        if ($abilities['con'] >= 16) $details[] = 'здоровый и выносливый';
        if ($abilities['int'] >= 16) $details[] = 'с умным взглядом';
        if ($abilities['wis'] >= 16) $details[] = 'с проницательными глазами';
        if ($abilities['cha'] >= 16) $details[] = 'с харизматичной внешностью';
        
        if (!empty($details)) {
            $baseAppearance .= ' ' . ucfirst(implode(', ', $details)) . '.';
        }
        
        return "Внешность: " . $baseAppearance;
    }
    
    /**
     * Генерирует описание личности
     */
    private function generatePersonality($class, $alignment) {
        $classTraits = [
            'воин' => 'дисциплинированный и храбрый',
            'варвар' => 'яростный и свободолюбивый',
            'паладин' => 'благородный и праведный',
            'рейнджер' => 'осторожный и наблюдательный',
            'следопыт' => 'мудрый и терпеливый',
            'маг' => 'любознательный и методичный',
            'волшебник' => 'аналитичный и упорный',
            'колдун' => 'амбициозный и хитрый',
            'чародей' => 'эмоциональный и импульсивный',
            'жрец' => 'набожный и сострадательный',
            'друид' => 'связанный с природой и мудрый',
            'бард' => 'артистичный и общительный',
            'плут' => 'хитрый и находчивый'
        ];
        
        $alignmentTraits = [
            'законно-добрый' => 'следует правилам и помогает другим',
            'нейтрально-добрый' => 'делает добро без привязанности к законам',
            'хаотично-добрый' => 'свободолюбивый и помогающий другим',
            'законно-нейтральный' => 'следует порядку и традициям',
            'нейтральный' => 'балансирует между разными подходами',
            'хаотично-нейтральный' => 'ценит личную свободу выше всего',
            'законно-злой' => 'использует систему для личной выгоды',
            'нейтрально-злой' => 'преследует собственные интересы',
            'хаотично-злой' => 'действует импульсивно и эгоистично'
        ];
        
        $classTrait = $classTraits[strtolower($class)] ?? 'уникальный';
        $alignmentTrait = $alignmentTraits[strtolower($alignment)] ?? 'сбалансированный';
        
        return "Характер: " . ucfirst($classTrait) . ", " . $alignmentTrait . ".";
    }
    
    /**
     * Генерирует описание способностей
     */
    private function generateAbilities($race, $class) {
        $abilities = [];
        
        // Расовые способности
        $raceAbilities = [
            'человек' => 'универсальность и адаптивность',
            'эльф' => 'острое зрение и грация',
            'дварф' => 'выносливость и устойчивость к ядам',
            'халфлинг' => 'удача и смелость',
            'гном' => 'остроумие и магические способности',
            'полуорк' => 'ярость и выносливость',
            'тифлинг' => 'магические способности и сопротивление огню',
            'драконорожденный' => 'дыхание дракона и сопротивление',
            'аасимар' => 'небесные способности и исцеление'
        ];
        
        // Классовые способности
        $classAbilities = [
            'воин' => 'мастерство в бою и второе дыхание',
            'варвар' => 'ярость и неистовство',
            'паладин' => 'божественные заклинания и аура',
            'рейнджер' => 'связь с природой и следопытство',
            'следопыт' => 'заклинания природы и животные-спутники',
            'маг' => 'широкий спектр заклинаний',
            'волшебник' => 'знания о магии и ритуалы',
            'колдун' => 'пактовая магия и мистические арканумы',
            'чародей' => 'врожденная магия и метамагия',
            'жрец' => 'божественные заклинания и каналы',
            'друид' => 'дикая форма и заклинания природы',
            'бард' => 'магическая музыка и вдохновение',
            'плут' => 'ловкость рук и скрытность'
        ];
        
        $raceAbility = $raceAbilities[strtolower($race)] ?? 'уникальные расовые черты';
        $classAbility = $classAbilities[strtolower($class)] ?? 'классовые навыки';
        
        return "Способности: Обладает " . $raceAbility . ", а также " . $classAbility . ".";
    }
    
    /**
     * Получает предысторию расы
     */
    private function getRaceBackground($race) {
        $raceBackgrounds = [
            'человек' => 'Вырос в человеческом обществе, где научились ценить разнообразие и адаптивность.',
            'эльф' => 'Провел долгие годы в эльфийских лесах, изучая древние традиции и магию.',
            'дварф' => 'Вырос в горных крепостях, где почитаются мастерство, честь и семейные узы.',
            'халфлинг' => 'Провел детство в уютных деревнях, где ценится покой, дружба и хорошая еда.',
            'гном' => 'Изучал древние секреты и изобретения в гномьих мастерских.',
            'полуорк' => 'Жил между двумя мирами, научившись выживать в суровых условиях.',
            'тифлинг' => 'Столкнулся с предрассудками из-за своего происхождения, что закалило характер.',
            'драконорожденный' => 'Воспитывался в традициях драконьей чести и силы.',
            'аасимар' => 'Получил божественное благословение и особую миссию.'
        ];
        
        return $raceBackgrounds[strtolower($race)] ?? 'Происходит из своей родной культуры.';
    }
    
    /**
     * Получает предысторию класса
     */
    private function getClassBackground($class, $level) {
        $classBackgrounds = [
            'воин' => 'Прошел военную подготовку и участвовал в сражениях.',
            'варвар' => 'Жил в диких землях, где выживание зависело от силы и ярости.',
            'паладин' => 'Принял священную клятву и посвятил себя служению высшей цели.',
            'рейнджер' => 'Патрулировал границы цивилизации, защищая от угроз.',
            'следопыт' => 'Изучал древние знания и общался с силами природы.',
            'маг' => 'Обучался в академии магии, изучая тайны заклинаний.',
            'волшебник' => 'Провел годы в библиотеках, постигая магические теории.',
            'колдун' => 'Заключил договор с могущественным существом.',
            'чародей' => 'Обнаружил в себе врожденные магические способности.',
            'жрец' => 'Служил в храме, получая благословения божества.',
            'друид' => 'Прошел инициацию в круге друидов.',
            'бард' => 'Путешествовал по миру, собирая истории и песни.',
            'плут' => 'Жил на улицах, изучая искусство обмана и воровства.'
        ];
        
        $baseBackground = $classBackgrounds[strtolower($class)] ?? 'Приобрел свои навыки через обучение и опыт.';
        
        if ($level > 1) {
            $baseBackground .= " За это время накопил значительный опыт приключений.";
        }
        
        return $baseBackground;
    }
    
    /**
     * Получает мотивацию по мировоззрению
     */
    private function getAlignmentMotivation($alignment) {
        $motivations = [
            'законно-добрый' => 'Стремится создать справедливое общество через закон и порядок.',
            'нейтрально-добрый' => 'Помогает другим, не привязываясь к строгим правилам.',
            'хаотично-добрый' => 'Борется за свободу и справедливость для всех.',
            'законно-нейтральный' => 'Следует традициям и поддерживает стабильность.',
            'нейтральный' => 'Ищет баланс и избегает крайностей.',
            'хаотично-нейтральный' => 'Ценит личную свободу и независимость.',
            'законно-злой' => 'Использует систему для достижения своих целей.',
            'нейтрально-злой' => 'Преследует собственные интересы любой ценой.',
            'хаотично-злой' => 'Действует импульсивно, не считаясь с последствиями.'
        ];
        
        return $motivations[strtolower($alignment)] ?? 'Имеет собственные принципы и цели.';
    }
}
?>
