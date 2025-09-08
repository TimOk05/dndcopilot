<?php

require_once __DIR__ . '/ImprovedAiService.php';
require_once __DIR__ . '/CacheService.php';

/**
 * AI чат для D&D Copilot
 */
class AIChat {
    private $aiService;
    private $cacheService;
    private $conversationHistory = [];
    private $maxHistoryLength = 10;
    
    public function __construct() {
        $this->aiService = new ImprovedAiService();
        $this->cacheService = new CacheService();
        
        logMessage('INFO', 'AIChat: Инициализирован');
    }
    
    /**
     * Отправка сообщения в AI чат
     */
    public function sendMessage($message, $useCache = true) {
        try {
            if (empty($message)) {
                return [
                    'success' => false,
                    'error' => 'Сообщение не может быть пустым'
                ];
            }
            
            // Добавляем сообщение в историю
            $this->conversationHistory[] = [
                'role' => 'user',
                'content' => $message
            ];
            
            // Ограничиваем длину истории
            if (count($this->conversationHistory) > $this->maxHistoryLength) {
                $this->conversationHistory = array_slice($this->conversationHistory, -$this->maxHistoryLength);
            }
            
            // Проверяем кэш
            $cacheKey = 'ai_chat_' . md5($message . serialize($this->conversationHistory));
            if ($useCache) {
                $cached = $this->cacheService->get($cacheKey);
                if ($cached) {
                    logMessage('DEBUG', 'AIChat: Используем кэшированный ответ');
                    return [
                        'success' => true,
                        'response' => $cached,
                        'cached' => true
                    ];
                }
            }
            
            // Строим промпт с контекстом
            $prompt = $this->buildPrompt($message);
            
            // Отправляем запрос к AI
            $result = $this->aiService->generateCharacterDescription($prompt[1]['content'], false);
            
            if ($result && !isset($result['error'])) {
                $response = $this->aiService->cleanAiResponse($result);
                
                // Добавляем ответ в историю
                $this->conversationHistory[] = [
                    'role' => 'assistant',
                    'content' => $response
                ];
                
                // Сохраняем в кэш
                if ($useCache) {
                    $this->cacheService->set($cacheKey, $response, 1800); // 30 минут
                }
                
                return [
                    'success' => true,
                    'response' => $response,
                    'cached' => false
                ];
            }
            
            return [
                'success' => false,
                'error' => 'AI API недоступен'
            ];
            
        } catch (Exception $e) {
            logMessage('ERROR', "AIChat: Ошибка отправки сообщения: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Ошибка: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Построение промпта с контекстом
     */
    private function buildPrompt($message) {
        $systemPrompt = "Ты помощник мастера D&D 5e. Отвечай на русском языке. Помогай с правилами, созданием персонажей, приключениями, локациями и всем, что связано с D&D. Будь дружелюбным и полезным.";
        
        $prompt = [
            ['role' => 'system', 'content' => $systemPrompt]
        ];
        
        // Добавляем историю разговора
        foreach ($this->conversationHistory as $entry) {
            $prompt[] = $entry;
        }
        
        return $prompt;
    }
    
    /**
     * Добавление PDF контента в контекст
     */
    public function addPDF($pdfContent) {
        try {
            if (empty($pdfContent)) {
                return false;
            }
            
            // Ограничиваем размер PDF контента
            $maxLength = 2000;
            if (strlen($pdfContent) > $maxLength) {
                $pdfContent = substr($pdfContent, 0, $maxLength) . '...';
            }
            
            // Добавляем PDF контент в историю как системное сообщение
            $this->conversationHistory[] = [
                'role' => 'system',
                'content' => "PDF контент для анализа: " . $pdfContent
            ];
            
            logMessage('INFO', 'AIChat: PDF контент добавлен в контекст');
            return true;
            
        } catch (Exception $e) {
            logMessage('ERROR', "AIChat: Ошибка добавления PDF: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Очистка истории разговора
     */
    public function clearHistory() {
        $this->conversationHistory = [];
        logMessage('INFO', 'AIChat: История разговора очищена');
    }
    
    /**
     * Получение истории разговора
     */
    public function getHistory() {
        return $this->conversationHistory;
    }
    
    /**
     * Установка максимальной длины истории
     */
    public function setMaxHistoryLength($length) {
        $this->maxHistoryLength = max(1, min(20, $length));
        logMessage('INFO', "AIChat: Максимальная длина истории установлена: {$this->maxHistoryLength}");
    }
    
    /**
     * Получение статистики чата
     */
    public function getStats() {
        return [
            'history_length' => count($this->conversationHistory),
            'max_history_length' => $this->maxHistoryLength,
            'user_messages' => count(array_filter($this->conversationHistory, function($msg) {
                return $msg['role'] === 'user';
            })),
            'assistant_messages' => count(array_filter($this->conversationHistory, function($msg) {
                return $msg['role'] === 'assistant';
            }))
        ];
    }
}
?>
