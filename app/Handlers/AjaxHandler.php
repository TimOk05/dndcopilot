<?php
/**
 * Обработчик AJAX запросов
 */

require_once __DIR__ . '/NotesHandler.php';
require_once __DIR__ . '/DiceHandler.php';
require_once __DIR__ . '/CharacterHandler.php';

class AjaxHandler {
    
    /**
     * Обрабатывает AJAX запросы
     */
    public static function handleRequest() {
        if (!isset($_POST['fast_action'])) {
            return ['success' => false, 'message' => 'Неизвестное действие'];
        }
        
        $action = $_POST['fast_action'];
        
        // Логируем запрос
        error_log('AJAX action: ' . $action);
        
        switch ($action) {
            case 'dice_result':
                return self::handleDiceRoll();
                
            case 'save_note':
                return self::handleSaveNote();
                
            case 'remove_note':
                return self::handleRemoveNote();
                
            case 'update_notes':
                return self::handleUpdateNotes();
                
            case 'generate_character':
                return self::handleGenerateCharacter();
                
            case 'generate_random_character':
                return self::handleGenerateRandomCharacter();
                
            case 'generate_quick_character':
                return self::handleGenerateQuickCharacter();
                
            default:
                return ['success' => false, 'message' => 'Неизвестное действие: ' . $action];
        }
    }
    
    /**
     * Обрабатывает бросок костей
     */
    private static function handleDiceRoll() {
        $dice = $_POST['dice'] ?? '1d20';
        $label = $_POST['label'] ?? '';
        
        $result = DiceHandler::rollDice($dice, $label);
        
        if ($result['success']) {
            echo nl2br(htmlspecialchars($result['message']));
        } else {
            echo $result['message'];
        }
        exit;
    }
    
    /**
     * Обрабатывает сохранение заметки
     */
    private static function handleSaveNote() {
        $content = $_POST['content'] ?? '';
        $title = $_POST['title'] ?? '';
        
        $result = NotesHandler::addNote($content, $title);
        echo $result['message'];
        exit;
    }
    
    /**
     * Обрабатывает удаление заметки
     */
    private static function handleRemoveNote() {
        $index = $_POST['remove_note'] ?? '';
        
        $result = NotesHandler::removeNote($index);
        echo $result['message'];
        exit;
    }
    
    /**
     * Обрабатывает обновление отображения заметок
     */
    private static function handleUpdateNotes() {
        $html = NotesHandler::updateNotesDisplay();
        echo $html;
        exit;
    }
    
    /**
     * Обрабатывает генерацию персонажа
     */
    private static function handleGenerateCharacter() {
        $handler = new CharacterHandler();
        
        $params = [
            'race' => $_POST['race'] ?? 'human',
            'class' => $_POST['class'] ?? 'fighter',
            'level' => (int)($_POST['level'] ?? 1),
            'gender' => $_POST['gender'] ?? 'random',
            'alignment' => $_POST['alignment'] ?? 'random'
        ];
        
        $result = $handler->generateCharacter($params);
        
        if ($result['success']) {
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
    
    /**
     * Обрабатывает генерацию случайного персонажа
     */
    private static function handleGenerateRandomCharacter() {
        $handler = new CharacterHandler();
        $result = $handler->generateRandomCharacter();
        
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Обрабатывает генерацию персонажа по шаблону
     */
    private static function handleGenerateQuickCharacter() {
        $template = $_POST['template'] ?? '';
        
        $handler = new CharacterHandler();
        $result = $handler->generateQuickCharacter($template);
        
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
?>
