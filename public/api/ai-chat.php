<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../app/Services/ai-service.php';

// AI чат согласно политике NO_FALLBACK
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'send_message':
            $message = $_POST['message'] ?? '';
            $language = $_POST['language'] ?? 'ru';
            
            if (empty($message)) {
                echo json_encode(['success' => false, 'error' => 'Сообщение не может быть пустым'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            // Используем AiService для реального AI API
            $aiService = new AiService();
            
            // Получаем заметки пользователя
            $userNotes = $_SESSION['notes'] ?? [];
            $notesContext = '';
            
            if (!empty($userNotes)) {
                $notesContext = "\n\nЗАМЕТКИ DM:\n";
                foreach ($userNotes as $note) {
                    // Очищаем HTML теги из заметок для AI
                    $cleanNote = strip_tags($note);
                    $notesContext .= "- " . $cleanNote . "\n";
                }
                $notesContext .= "\nИспользуй эти заметки для контекста. Если пользователь спрашивает о персонажах, противниках или других элементах из заметок, ссылайся на них.";
            }
            
            // Формируем промпт для D&D контекста с заметками
            $prompt = "Ты - опытный мастер подземелий (DM) для настольной ролевой игры Dungeons & Dragons 5e. Отвечай кратко и информативно на русском языке." . $notesContext . "\n\nПользователь написал: " . $message;
            
            $aiResponse = $aiService->generateText($prompt);
            
            // Проверяем результат согласно NO_FALLBACK политике
            if (is_array($aiResponse) && isset($aiResponse['error'])) {
                // AI API недоступен - показываем ошибку
                echo json_encode([
                    'success' => false,
                    'error' => 'AI API недоступен',
                    'message' => $aiResponse['message'],
                    'details' => $aiResponse['details'] ?? 'Проверьте подключение к интернету и настройки API'
                ], JSON_UNESCAPED_UNICODE);
            } else if ($aiResponse && is_string($aiResponse)) {
                // AI API работает - возвращаем ответ
                echo json_encode([
                    'success' => true,
                    'response' => $aiResponse
                ], JSON_UNESCAPED_UNICODE);
            } else {
                // Неожиданная ошибка
                echo json_encode([
                    'success' => false,
                    'error' => 'Неожиданная ошибка AI API',
                    'message' => 'AI API вернул неожиданный ответ',
                    'details' => 'Проверьте логи приложения'
                ], JSON_UNESCAPED_UNICODE);
            }
            break;
            
        case 'get_history':
            echo json_encode([
                'success' => true,
                'history' => []
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'get_notes':
            // Возвращаем заметки пользователя для AI чата
            $userNotes = $_SESSION['notes'] ?? [];
            $cleanNotes = [];
            
            foreach ($userNotes as $note) {
                $cleanNotes[] = strip_tags($note);
            }
            
            echo json_encode([
                'success' => true,
                'notes' => $cleanNotes
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Неизвестное действие'], JSON_UNESCAPED_UNICODE);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Метод не поддерживается'], JSON_UNESCAPED_UNICODE);
}
?>
