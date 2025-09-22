<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Подключаем конфигурацию
require_once __DIR__ . '/../../config/config.php';

class BackgroundGenerator {
    
    /**
     * Генерирует предысторию и описание персонажа с помощью AI
     */
    public function generateBackground($characterData) {
        try {
            // Подготавливаем данные персонажа для AI
            $characterInfo = $this->prepareCharacterInfo($characterData);
            
            // Создаем промпт для AI
            $prompt = $this->createPrompt($characterInfo);
            
            // Отправляем запрос к AI (здесь нужно будет интегрировать с вашим AI сервисом)
            $background = $this->callAI($prompt);
            
            return [
                'background' => $background,
                'generated_at' => date('Y-m-d H:i:s')
            ];
            
        } catch (Exception $e) {
            throw new Exception("Ошибка генерации предыстории: " . $e->getMessage());
        }
    }
    
    /**
     * Подготавливает информацию о персонаже для AI
     */
    private function prepareCharacterInfo($characterData) {
        $info = [
            'race' => $characterData['race']['name'],
            'subrace' => $characterData['subrace']['name'] ?? null,
            'class' => $characterData['class']['name'],
            'subclass' => $characterData['subclass']['name'] ?? null,
            'level' => $characterData['level'],
            'ability_scores' => $characterData['ability_scores'],
            'ability_modifiers' => $characterData['ability_modifiers'],
            'traits' => $characterData['traits'],
            'languages' => $characterData['languages']
        ];
        
        return $info;
    }
    
    /**
     * Создает промпт для AI
     */
    private function createPrompt($characterInfo) {
        $prompt = "Создай подробную предысторию и описание для персонажа D&D 5e со следующими характеристиками:\n\n";
        
        $prompt .= "РАСА: " . $characterInfo['race'];
        if ($characterInfo['subrace']) {
            $prompt .= " (" . $characterInfo['subrace'] . ")";
        }
        $prompt .= "\n";
        
        $prompt .= "КЛАСС: " . $characterInfo['class'];
        if ($characterInfo['subclass']) {
            $prompt .= " (" . $characterInfo['subclass'] . ")";
        }
        $prompt .= "\n";
        
        $prompt .= "УРОВЕНЬ: " . $characterInfo['level'] . "\n\n";
        
        $prompt .= "ХАРАКТЕРИСТИКИ:\n";
        $abilities = ['STR' => 'Сила', 'DEX' => 'Ловкость', 'CON' => 'Телосложение', 
                     'INT' => 'Интеллект', 'WIS' => 'Мудрость', 'CHA' => 'Харизма'];
        
        foreach ($abilities as $abbr => $name) {
            $score = $characterInfo['ability_scores'][$abbr];
            $modifier = $characterInfo['ability_modifiers'][$abbr];
            $modifierStr = $modifier >= 0 ? "+" . $modifier : (string)$modifier;
            $prompt .= "- " . $name . ": " . $score . " (" . $modifierStr . ")\n";
        }
        
        if (!empty($characterInfo['traits'])) {
            $prompt .= "\nРАСОВЫЕ ЧЕРТЫ:\n";
            foreach ($characterInfo['traits'] as $trait) {
                $prompt .= "- " . $trait['name'] . ": " . $trait['description'] . "\n";
            }
        }
        
        if (!empty($characterInfo['languages'])) {
            $prompt .= "\nЯЗЫКИ: " . implode(', ', $characterInfo['languages']) . "\n";
        }
        
        $prompt .= "\nПожалуйста, создай:\n";
        $prompt .= "1. Краткое описание внешности персонажа (2-3 предложения)\n";
        $prompt .= "2. Подробную предысторию, объясняющую как персонаж стал " . strtolower($characterInfo['class']) . "ом (3-4 абзаца)\n";
        $prompt .= "3. Мотивацию и цели персонажа (1-2 абзаца)\n";
        $prompt .= "4. Особенности характера и личности (1-2 абзаца)\n";
        $prompt .= "5. Отношения с другими (семья, друзья, враги) (1-2 абзаца)\n\n";
        
        $prompt .= "Предыстория должна быть логичной, интересной и соответствовать характеристикам персонажа. ";
        $prompt .= "Используй фэнтезийную атмосферу D&D. Пиши на русском языке.";
        
        return $prompt;
    }
    
    /**
     * Вызывает AI для генерации предыстории
     * TODO: Интегрировать с реальным AI сервисом
     */
    private function callAI($prompt) {
        // Временная заглушка - возвращаем пример предыстории
        // В реальной реализации здесь будет вызов к AI API (OpenAI, Claude, etc.)
        
        $sampleBackgrounds = [
            "Внешность: Высокий и стройный эльф с серебристыми волосами и пронзительными голубыми глазами. Его движения грациозны и точны, а на лице часто играет загадочная улыбка.\n\n" .
            "Предыстория: Родился в древнем эльфийском лесу, где с детства изучал магические искусства. Его семья принадлежала к знатному роду магов, и с ранних лет он проявлял необычайные способности к колдовству. После столетий обучения в эльфийских академиях, он решил отправиться в мир людей, чтобы изучить новые формы магии и найти свое место в изменяющемся мире.\n\n" .
            "Мотивация: Стремится открыть новые магические знания и защитить мир от темных сил. Его цель - стать величайшим магом своего времени и создать новые заклинания, которые помогут всем расам.\n\n" .
            "Характер: Любознательный и мудрый, но иногда высокомерный. Терпелив в обучении, но нетерпим к глупости. Любит интеллектуальные дискуссии и решение сложных задач.\n\n" .
            "Отношения: Поддерживает связь с семьей в эльфийском лесу. Имеет несколько друзей среди других магов и ученых. Некоторые консервативные эльфы считают его предателем за то, что он покинул традиционные пути.",
            
            "Внешность: Крепкий дварф с густой рыжей бородой и добрыми карими глазами. Его руки покрыты шрамами от кузнечного дела, а в голосе слышится теплота и мудрость.\n\n" .
            "Предыстория: Вырос в горных кланах дварфов, где с детства учился ремеслу кузнеца. Его семья была известна своими магическими артефактами, и он унаследовал не только навыки ремесла, но и способности к божественной магии. После того как его клан был атакован драконами, он поклялся защищать невинных и стал жрецом.\n\n" .
            "Мотивация: Желает восстановить честь своего клана и защитить всех, кто не может защитить себя. Мечтает создать легендарное оружие, которое поможет в борьбе со злом.\n\n" .
            "Характер: Честный и прямолинейный, всегда держит слово. Терпелив в работе, но может вспылить при виде несправедливости. Любит рассказывать истории и петь традиционные песни дварфов.\n\n" .
            "Отношения: Чтит память погибших членов клана. Имеет друзей среди других жрецов и кузнецов. Некоторые считают его слишком идеалистичным."
        ];
        
        // Возвращаем случайную предысторию
        return $sampleBackgrounds[array_rand($sampleBackgrounds)];
    }
}

// Обработка запросов
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['character'])) {
            throw new Exception('Неверные данные запроса');
        }
        
        $generator = new BackgroundGenerator();
        $response = $generator->generateBackground($input['character']);
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Метод не поддерживается'], JSON_UNESCAPED_UNICODE);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>
