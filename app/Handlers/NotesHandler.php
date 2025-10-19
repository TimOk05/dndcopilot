<?php
/**
 * Обработчик для работы с заметками
 */

class NotesHandler {
    
    /**
     * Инициализирует заметки в сессии
     */
    public static function initNotes() {
        if (!isset($_SESSION['notes'])) {
            $_SESSION['notes'] = [];
        }
    }
    
    /**
     * Добавляет новую заметку
     */
    public static function addNote($content, $title = '') {
        self::initNotes();
        
        if (empty($content)) {
            return ['success' => false, 'message' => 'Ошибка: пустое содержимое'];
        }
        
        // Если есть заголовок, добавляем его в начало заметки
        if ($title) {
            $content = "<h3>$title</h3>" . $content;
        }
        
        $_SESSION['notes'][] = $content;
        return ['success' => true, 'message' => 'OK'];
    }
    
    /**
     * Удаляет заметку по индексу
     */
    public static function removeNote($index) {
        self::initNotes();
        
        $index = (int)$index;
        if (isset($_SESSION['notes'][$index])) {
            array_splice($_SESSION['notes'], $index, 1);
            return ['success' => true, 'message' => 'OK'];
        }
        
        return ['success' => false, 'message' => 'Заметка не найдена'];
    }
    
    /**
     * Получает все заметки
     */
    public static function getNotes() {
        self::initNotes();
        return $_SESSION['notes'] ?? [];
    }
    
    /**
     * Обновляет отображение заметок
     */
    public static function updateNotesDisplay() {
        $notes = self::getNotes();
        $html = '';
        
        if (empty($notes)) {
            $html = '<div class="note-item empty">Нет заметок</div>';
        } else {
            foreach ($notes as $index => $note) {
                $html .= '<div class="note-item" data-index="' . $index . '">';
                $html .= '<div class="note-content">' . $note . '</div>';
                $html .= '<button class="remove-note" data-index="' . $index . '">Удалить</button>';
                $html .= '</div>';
            }
        }
        
        return $html;
    }
}
?>
