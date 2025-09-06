<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/users.php';

// Проверяем авторизацию
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Не авторизован']);
    exit;
}

class CombatSystem {
    private $participants = [];
    private $current_turn = 0;
    private $round = 1;
    
    public function __construct() {
        if (!isset($_SESSION['combat'])) {
            $_SESSION['combat'] = [
                'participants' => [],
                'current_turn' => 0,
                'round' => 1
            ];
        }
        $this->participants = &$_SESSION['combat']['participants'];
        $this->current_turn = &$_SESSION['combat']['current_turn'];
        $this->round = &$_SESSION['combat']['round'];
    }
    
    /**
     * Добавление участника в бой
     */
    public function addParticipant($data) {
        $name = $data['name'] ?? 'Неизвестный';
        $initiative = $data['initiative'] ?? 0;
        $max_hp = $data['max_hp'] ?? 10;
        $current_hp = $data['current_hp'] ?? $max_hp;
        $type = $data['type'] ?? 'character'; // character или enemy
        $source = $data['source'] ?? 'manual'; // manual, notes, generated
        
        // Генерируем уникальный ID
        $id = uniqid($type . '_', true);
        
        $participant = [
            'id' => $id,
            'name' => $name,
            'initiative' => $initiative,
            'max_hp' => $max_hp,
            'current_hp' => $current_hp,
            'type' => $type,
            'source' => $source,
            'status' => 'active', // active, unconscious, dead
            'notes' => $data['notes'] ?? ''
        ];
        
        $this->participants[] = $participant;
        $this->sortParticipants();
        
        return [
            'success' => true,
            'participant' => $participant,
            'message' => "Участник {$name} добавлен в бой"
        ];
    }
    
    /**
     * Сортировка участников по инициативе
     */
    private function sortParticipants() {
        usort($this->participants, function($a, $b) {
            if ($a['initiative'] == $b['initiative']) {
                // При равной инициативе сортируем по имени
                return strcmp($a['name'], $b['name']);
            }
            return $b['initiative'] - $a['initiative']; // По убыванию
        });
    }
    
    /**
     * Получение списка участников
     */
    public function getParticipants() {
        return [
            'success' => true,
            'participants' => $this->participants,
            'current_turn' => $this->current_turn,
            'round' => $this->round,
            'current_participant' => isset($this->participants[$this->current_turn]) ? $this->participants[$this->current_turn] : null
        ];
    }
    
    /**
     * Следующий ход
     */
    public function nextTurn() {
        if (empty($this->participants)) {
            return ['success' => false, 'error' => 'Нет участников в бою'];
        }
        
        $this->current_turn++;
        
        // Если дошли до конца списка, начинаем новый раунд
        if ($this->current_turn >= count($this->participants)) {
            $this->current_turn = 0;
            $this->round++;
        }
        
        return [
            'success' => true,
            'current_turn' => $this->current_turn,
            'round' => $this->round,
            'current_participant' => $this->participants[$this->current_turn]
        ];
    }
    
    /**
     * Предыдущий ход
     */
    public function previousTurn() {
        if (empty($this->participants)) {
            return ['success' => false, 'error' => 'Нет участников в бою'];
        }
        
        $this->current_turn--;
        
        // Если ушли в минус, переходим к последнему участнику предыдущего раунда
        if ($this->current_turn < 0) {
            $this->current_turn = count($this->participants) - 1;
            $this->round = max(1, $this->round - 1);
        }
        
        return [
            'success' => true,
            'current_turn' => $this->current_turn,
            'round' => $this->round,
            'current_participant' => $this->participants[$this->current_turn]
        ];
    }
    
    /**
     * Изменение хитов участника
     */
    public function modifyHP($id, $change, $type = 'damage') {
        foreach ($this->participants as &$participant) {
            if ($participant['id'] === $id) {
                if ($type === 'heal') {
                    $participant['current_hp'] = min($participant['max_hp'], $participant['current_hp'] + $change);
                } else {
                    $participant['current_hp'] = max(0, $participant['current_hp'] - $change);
                }
                
                // Обновляем статус
                if ($participant['current_hp'] <= 0) {
                    $participant['status'] = 'unconscious';
                    if ($participant['current_hp'] <= -$participant['max_hp']) {
                        $participant['status'] = 'dead';
                    }
                } else {
                    $participant['status'] = 'active';
                }
                
                return [
                    'success' => true,
                    'participant' => $participant,
                    'message' => "Хиты {$participant['name']} изменены на " . ($type === 'heal' ? '+' : '-') . $change
                ];
            }
        }
        
        return ['success' => false, 'error' => 'Участник не найден'];
    }
    
    /**
     * Удаление участника из боя
     */
    public function removeParticipant($id) {
        foreach ($this->participants as $key => $participant) {
            if ($participant['id'] === $id) {
                $name = $participant['name'];
                unset($this->participants[$key]);
                $this->participants = array_values($this->participants); // Переиндексируем массив
                
                // Корректируем текущий ход
                if ($this->current_turn >= count($this->participants)) {
                    $this->current_turn = 0;
                }
                
                return [
                    'success' => true,
                    'message' => "Участник {$name} удален из боя"
                ];
            }
        }
        
        return ['success' => false, 'error' => 'Участник не найден'];
    }
    
    /**
     * Очистка боя
     */
    public function clearCombat() {
        $_SESSION['combat'] = [
            'participants' => [],
            'current_turn' => 0,
            'round' => 1
        ];
        
        return [
            'success' => true,
            'message' => 'Бой очищен'
        ];
    }
    
    /**
     * Завершение боя
     */
    public function endCombat() {
        // Сохраняем результаты в заметки
        $result = $this->saveCombatResult([
            'participants' => $this->participants,
            'current_turn' => $this->current_turn,
            'round' => $this->round,
            'end_reason' => 'combat_ended'
        ]);
        
        // Очищаем бой
        $this->clearCombat();
        
        return [
            'success' => true,
            'message' => 'Бой завершен и результаты сохранены'
        ];
    }
    
    /**
     * Сохранение результатов боя в заметки
     */
    public function saveCombatResult($data) {
        $participants = $data['participants'] ?? [];
        $round = $data['round'] ?? 1;
        
        // Формируем HTML для заметки
        $noteContent = '<div class="combat-result">';
        $noteContent .= '<div class="combat-result-title">Результаты боя</div>';
        $noteContent .= '<div class="combat-result-info">';
        $noteContent .= '<div><strong>Раунд:</strong> ' . $round . '</div>';
        $noteContent .= '<div><strong>Участники:</strong></div>';
        
        foreach ($participants as $participant) {
            $status = $this->getStatusText($participant['status']);
            $noteContent .= '<div class="combat-participant">';
            $noteContent .= '<strong>' . htmlspecialchars($participant['name']) . '</strong> ';
            $noteContent .= '(' . ($participant['type'] === 'character' ? 'Персонаж' : 'Противник') . ') - ';
            $noteContent .= 'Хиты: ' . $participant['current_hp'] . '/' . $participant['max_hp'] . ' ';
            $noteContent .= 'Статус: ' . $status;
            $noteContent .= '</div>';
        }
        
        $noteContent .= '</div></div>';
        
        // Сохраняем в заметки
        if (!isset($_SESSION['notes'])) {
            $_SESSION['notes'] = [];
        }
        
        $_SESSION['notes'][] = $noteContent;
        
        return [
            'success' => true,
            'message' => 'Результаты боя сохранены в заметки'
        ];
    }
    
    /**
     * Получение текста статуса
     */
    private function getStatusText($status) {
        switch ($status) {
            case 'active': return 'Активен';
            case 'unconscious': return 'Без сознания';
            case 'dead': return 'Мертв';
            default: return 'Неизвестно';
        }
    }
    
    /**
     * Бросок инициативы
     */
    public function rollInitiative($bonus = 0) {
        $roll = rand(1, 20) + $bonus;
        return [
            'success' => true,
            'roll' => $roll,
            'dice' => rand(1, 20),
            'bonus' => $bonus
        ];
    }
}

// Обработка запросов
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $combat = new CombatSystem();
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add_participant':
            $result = $combat->addParticipant($_POST);
            break;
            
        case 'get_participants':
            $result = $combat->getParticipants();
            break;
            
        case 'next_turn':
            $result = $combat->nextTurn();
            break;
            
        case 'previous_turn':
            $result = $combat->previousTurn();
            break;
            
        case 'modify_hp':
            $result = $combat->modifyHP($_POST['id'], $_POST['change'], $_POST['type'] ?? 'damage');
            break;
            
        case 'remove_participant':
            $result = $combat->removeParticipant($_POST['id']);
            break;
            
        case 'clear_combat':
            $result = $combat->clearCombat();
            break;
            
        case 'end_combat':
            $result = $combat->endCombat();
            break;
            
        case 'save_result':
            $data = json_decode($_POST['data'], true);
            $result = $combat->saveCombatResult($data);
            break;
            
        case 'roll_initiative':
            $result = $combat->rollInitiative($_POST['bonus'] ?? 0);
            break;
            
        default:
            $result = ['success' => false, 'error' => 'Неизвестное действие'];
    }
    
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode(['success' => false, 'error' => 'Метод не поддерживается']);
}
?>
