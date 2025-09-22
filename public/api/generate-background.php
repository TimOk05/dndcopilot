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
    public function generateBackground($characterData, $type = 'background') {
        try {
            // Подготавливаем данные персонажа для AI
            $characterInfo = $this->prepareCharacterInfo($characterData);
            
            // Создаем промпт для AI
            $prompt = $this->createPrompt($characterInfo, $type);
            
            // Отправляем запрос к AI
            $content = $this->callAI($prompt, $type);
            
            return [
                'content' => $content,
                'type' => $type,
                'generated_at' => date('Y-m-d H:i:s')
            ];
            
        } catch (Exception $e) {
            throw new Exception("Ошибка генерации: " . $e->getMessage());
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
    private function createPrompt($characterInfo, $type = 'background') {
        $prompt = "Создай ";
        
        if ($type === 'description') {
            $prompt .= "краткое описание внешности и характера для персонажа D&D 5e";
        } else {
            $prompt .= "подробную предысторию для персонажа D&D 5e";
        }
        
        $prompt .= " со следующими характеристиками:\n\n";
        
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
        
        if ($type === 'description') {
            $prompt .= "\nСоздай краткое описание (2-3 абзаца):\n";
            $prompt .= "1. Внешность персонажа (рост, телосложение, цвет волос/глаз, отличительные черты)\n";
            $prompt .= "2. Особенности характера и поведения\n";
            $prompt .= "3. Манера речи и привычки\n\n";
            $prompt .= "Описание должно соответствовать расе, классу и характеристикам персонажа. Используй фэнтезийную атмосферу D&D. Пиши на русском языке.";
        } else {
            $prompt .= "\nСоздай подробную предысторию:\n";
            $prompt .= "1. Детство и происхождение (1-2 абзаца)\n";
            $prompt .= "2. Как персонаж стал " . strtolower($characterInfo['class']) . "ом (2-3 абзаца)\n";
            $prompt .= "3. Важные события в жизни (1-2 абзаца)\n";
            $prompt .= "4. Мотивация и цели (1-2 абзаца)\n";
            $prompt .= "5. Отношения с другими (семья, друзья, враги) (1-2 абзаца)\n\n";
            $prompt .= "Предыстория должна быть логичной, интересной и соответствовать характеристикам персонажа. Используй фэнтезийную атмосферу D&D. Пиши на русском языке.";
        }
        
        return $prompt;
    }
    
    /**
     * Вызывает AI для генерации предыстории
     */
    private function callAI($prompt, $type = 'background') {
        $apiKey = getApiKey('deepseek');
        
        if (empty($apiKey)) {
            // Если нет API ключа, возвращаем заглушку
            return $this->getFallbackContent($type);
        }
        
        try {
            $data = [
                'model' => 'deepseek-chat',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Ты - опытный мастер D&D, который создает интересные и детальные предыстории для персонажей. Пиши на русском языке, используй фэнтезийную атмосферу D&D.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => 1500,
                'temperature' => 0.8,
                'stream' => false
            ];
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, DEEPSEEK_API_URL);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, API_TIMEOUT);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                throw new Exception("Ошибка cURL: " . $error);
            }
            
            if ($httpCode !== 200) {
                throw new Exception("HTTP ошибка: " . $httpCode);
            }
            
            $result = json_decode($response, true);
            
            if (isset($result['choices'][0]['message']['content'])) {
                return trim($result['choices'][0]['message']['content']);
            } else {
                throw new Exception("Неверный формат ответа от AI");
            }
            
        } catch (Exception $e) {
            logMessage('ERROR', 'AI generation failed', ['error' => $e->getMessage()]);
            return $this->getFallbackContent($type);
        }
    }
    
    /**
     * Возвращает заглушку если AI недоступен
     */
    private function getFallbackContent($type) {
        if ($type === 'description') {
            return "Описание персонажа будет сгенерировано AI. В данный момент AI сервис недоступен, но вы можете добавить описание вручную.";
        }
        
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
        
        $type = $input['type'] ?? 'background';
        
        $generator = new BackgroundGenerator();
        $response = $generator->generateBackground($input['character'], $type);
        
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
