<?php
header('Content-Type: application/json; charset=utf-8');
mb_internal_encoding('UTF-8');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../app/Middleware/auth.php';
require_once __DIR__ . '/../../app/Services/ai-service.php';

// Проверяем авторизацию (временно отключено для тестирования)
// if (!isLoggedIn()) {
//     http_response_code(401);
//     echo json_encode(['success' => false, 'error' => 'Не авторизован']);
//     exit;
// }

class AIChat {
    private $context = '';
    private $pdf_content = '';
    
    public function __construct() {
        if (!isset($_SESSION['ai_chat'])) {
            $_SESSION['ai_chat'] = [
                'messages' => [],
                'pdf_content' => '',
                'context' => $this->getDnDContext()
            ];
        }
        $this->context = $_SESSION['ai_chat']['context'];
        $this->pdf_content = $_SESSION['ai_chat']['pdf_content'];
    }
    
    /**
     * Получение базового контекста D&D
     */
    private function getDnDContext() {
        return "Ты - опытный мастер подземелий (DM) для настольной ролевой игры Dungeons & Dragons 5e. 
        
Твои основные задачи:
1. Помогать мастерам в проведении игр
2. Создавать интересные сценарии и квесты
3. Генерировать NPC, локации и события
4. Давать советы по правилам D&D 5e
5. Помогать с балансом и механиками игры

Ты знаешь:
- Все основные правила D&D 5e
- Механики боя, заклинаний, навыков
- Расы, классы, монстры
- Создание приключений и кампаний
- Управление игровым процессом

Всегда отвечай кратко, но информативно. Используй русский язык. 
Если пользователь загрузил PDF с ваншотом, используй эту информацию для создания приключения.";
    }
    
    /**
     * Обработка сообщения пользователя
     */
    public function processMessage($message, $pdf_file = null) {
        // Обрабатываем PDF файл если загружен
        if ($pdf_file && $pdf_file['error'] === UPLOAD_ERR_OK) {
            $this->processPDF($pdf_file);
        }
        
        // Формируем промпт с контекстом
        $prompt = $this->buildPrompt($message);
        
        // Отправляем запрос к AI
        $response = $this->callAI($prompt);
        
        if ($response) {
            // Сохраняем сообщения в сессии
            $_SESSION['ai_chat']['messages'][] = [
                'role' => 'user',
                'content' => $message,
                'timestamp' => time()
            ];
            
            $_SESSION['ai_chat']['messages'][] = [
                'role' => 'assistant',
                'content' => $response,
                'timestamp' => time()
            ];
            
            return [
                'success' => true,
                'response' => $response,
                'pdf_processed' => $pdf_file ? true : false
            ];
        } else {
            return [
                'success' => false,
                'error' => 'Не удалось получить ответ от AI'
            ];
        }
    }
    
    /**
     * Обработка PDF файла
     */
    private function processPDF($file) {
        $allowed_types = ['application/pdf'];
        $max_size = 10 * 1024 * 1024; // 10MB
        
        // Проверяем тип файла
        if (!in_array($file['type'], $allowed_types)) {
            throw new Exception('Поддерживаются только PDF файлы');
        }
        
        // Проверяем размер файла
        if ($file['size'] > $max_size) {
            throw new Exception('Файл слишком большой (максимум ' . ($max_size / 1024 / 1024) . 'MB)');
        }
        
        // Проверяем на ошибки загрузки
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Ошибка загрузки файла');
        }
        
        // Проверяем расширение файла
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($file_extension !== 'pdf') {
            throw new Exception('Файл должен иметь расширение .pdf');
        }
        
        // Простое извлечение текста из PDF (в реальном проекте лучше использовать библиотеку)
        $content = $this->extractTextFromPDF($file['tmp_name']);
        
        if ($content) {
            $this->pdf_content = $content;
            $_SESSION['ai_chat']['pdf_content'] = $content;
        }
    }
    
    /**
     * Простое извлечение текста из PDF
     */
    private function extractTextFromPDF($file_path) {
        // В реальном проекте здесь должна быть библиотека для работы с PDF
        // Например, pdfparser или аналогичная
        return "PDF файл загружен. Содержимое будет обработано для создания приключения.";
    }
    
    /**
     * Формирование промпта для AI
     */
    private function buildPrompt($message) {
        $prompt = $this->context . "\n\n";
        
        // Добавляем контекст из PDF если есть
        if ($this->pdf_content) {
            $prompt .= "Контекст из загруженного PDF:\n" . $this->pdf_content . "\n\n";
        }
        
        // Добавляем последние сообщения для контекста
        $recent_messages = array_slice($_SESSION['ai_chat']['messages'], -6); // Последние 3 пары сообщений
        if (!empty($recent_messages)) {
            $prompt .= "Предыдущие сообщения:\n";
            foreach ($recent_messages as $msg) {
                $role = $msg['role'] === 'user' ? 'Пользователь' : 'AI';
                $prompt .= "{$role}: {$msg['content']}\n";
            }
            $prompt .= "\n";
        }
        
        $prompt .= "Пользователь: {$message}\n\nAI:";
        
        return $prompt;
    }
    
    /**
     * Вызов DeepSeek API напрямую
     */
    private function callAI($prompt) {
        $api_key = getApiKey('deepseek');
        if (!$api_key) {
            return "Извините, AI временно недоступен. Проверьте настройки API.";
        }
        
        if (!function_exists('curl_init')) {
            // Fallback для случая когда cURL недоступен
            return "Привет! Я AI помощник для D&D. К сожалению, AI API временно недоступен (отсутствует поддержка cURL), но я могу помочь с базовыми вопросами по D&D. Что вас интересует?";
        }
        
        $data = [
            'model' => 'deepseek-chat',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'max_tokens' => 1000,
            'temperature' => 0.7
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.deepseek.com/v1/chat/completions');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_USERAGENT, 'DnD-Copilot/2.0');
        
        $headers = [
            'Content-Type: application/json',
            "Authorization: Bearer {$api_key}"
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($response === false) {
            logMessage('ERROR', "AI Chat cURL ошибка: {$curlError}");
            return "Извините, произошла ошибка при обращении к AI. Попробуйте позже.";
        }
        
        $result = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            logMessage('ERROR', "AI Chat ошибка декодирования JSON: " . json_last_error_msg());
            return "Извините, произошла ошибка при обработке ответа AI.";
        }
        
        if ($httpCode >= 400) {
            logMessage('ERROR', "AI Chat ошибка: HTTP {$httpCode}. Ответ: {$response}");
            return "Извините, AI временно недоступен. Попробуйте позже.";
        }
        
        if (isset($result['choices'][0]['message']['content'])) {
            return trim($result['choices'][0]['message']['content']);
        }
        
        logMessage('WARNING', 'AI Chat не вернул текстовый ответ');
        logMessage('DEBUG', 'AI Chat структура ответа: ' . json_encode($result, JSON_UNESCAPED_UNICODE));
        return "Извините, AI не смог сгенерировать ответ. Попробуйте позже.";
    }
    
    /**
     * Получение истории чата
     */
    public function getChatHistory() {
        return [
            'success' => true,
            'messages' => $_SESSION['ai_chat']['messages'] ?? [],
            'has_pdf' => !empty($this->pdf_content)
        ];
    }
    
    /**
     * Очистка истории чата
     */
    public function clearChat() {
        $_SESSION['ai_chat']['messages'] = [];
        $_SESSION['ai_chat']['pdf_content'] = '';
        $this->pdf_content = '';
        
        return [
            'success' => true,
            'message' => 'История чата очищена'
        ];
    }
    
    /**
     * Удаление PDF контекста
     */
    public function removePDF() {
        $_SESSION['ai_chat']['pdf_content'] = '';
        $this->pdf_content = '';
        
        return [
            'success' => true,
            'message' => 'PDF контекст удален'
        ];
    }
}

// Обработка запросов
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Проверяем CSRF токен (временно отключено для тестирования)
    // if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
    //     http_response_code(403);
    //     echo json_encode(['success' => false, 'error' => 'Неверный CSRF токен']);
    //     exit;
    // }
    
    $chat = new AIChat();
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'send_message':
                $message = sanitizeInput($_POST['message'] ?? '', 'string');
                $pdf_file = isset($_FILES['pdf']) ? $_FILES['pdf'] : null;
                
                if (empty($message)) {
                    throw new Exception('Сообщение не может быть пустым');
                }
                
                $result = $chat->processMessage($message, $pdf_file);
                break;
                
            case 'get_history':
                $result = $chat->getChatHistory();
                break;
                
            case 'clear_chat':
                $result = $chat->clearChat();
                break;
                
            case 'remove_pdf':
                $result = $chat->removePDF();
                break;
                
            default:
                throw new Exception('Неизвестное действие');
        }
    } catch (Exception $e) {
        $result = [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
    
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode(['success' => false, 'error' => 'Метод не поддерживается']);
}
?>
