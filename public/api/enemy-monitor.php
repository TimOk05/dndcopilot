<?php
// Мониторинг генерации противников
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/config.php';

class EnemyMonitor {
    private $log_file;
    
    public function __construct() {
        $this->log_file = __DIR__ . '/../../data/logs/enemy_monitor.log';
    }
    
    /**
     * Получение последних записей мониторинга
     */
    public function getRecentLogs($limit = 50) {
        if (!file_exists($this->log_file)) {
            return [];
        }
        
        $lines = file($this->log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        return array_slice($lines, -$limit);
    }
    
    /**
     * Получение статистики генерации
     */
    public function getGenerationStats() {
        $stats = [
            'total_requests' => 0,
            'successful_generations' => 0,
            'failed_generations' => 0,
            'threat_levels' => [],
            'errors' => []
        ];
        
        if (!file_exists($this->log_file)) {
            return $stats;
        }
        
        $lines = file($this->log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            if (strpos($line, 'GENERATION_START') !== false) {
                $stats['total_requests']++;
                
                // Извлекаем уровень угрозы
                if (preg_match('/threat_level: ([^,]+)/', $line, $matches)) {
                    $threat_level = $matches[1];
                    $stats['threat_levels'][$threat_level] = ($stats['threat_levels'][$threat_level] ?? 0) + 1;
                }
            } elseif (strpos($line, 'GENERATION_SUCCESS') !== false) {
                $stats['successful_generations']++;
            } elseif (strpos($line, 'GENERATION_ERROR') !== false) {
                $stats['failed_generations']++;
                
                // Извлекаем ошибку
                if (preg_match('/ERROR: (.+)/', $line, $matches)) {
                    $error = $matches[1];
                    $stats['errors'][$error] = ($stats['errors'][$error] ?? 0) + 1;
                }
            }
        }
        
        return $stats;
    }
    
    /**
     * Получение детальной информации о последней генерации
     */
    public function getLastGenerationDetails() {
        if (!file_exists($this->log_file)) {
            return null;
        }
        
        $lines = file($this->log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $details = [];
        
        // Ищем последнюю генерацию
        $last_generation_start = null;
        for ($i = count($lines) - 1; $i >= 0; $i--) {
            if (strpos($lines[$i], 'GENERATION_START') !== false) {
                $last_generation_start = $i;
                break;
            }
        }
        
        if ($last_generation_start === null) {
            return null;
        }
        
        // Собираем все записи для этой генерации
        for ($i = $last_generation_start; $i < count($lines); $i++) {
            $line = $lines[$i];
            
            if (strpos($line, 'GENERATION_START') !== false) {
                $details['start'] = $line;
            } elseif (strpos($line, 'GENERATION_SUCCESS') !== false) {
                $details['success'] = $line;
                break;
            } elseif (strpos($line, 'GENERATION_ERROR') !== false) {
                $details['error'] = $line;
                break;
            } else {
                $details['logs'][] = $line;
            }
        }
        
        return $details;
    }
    
    /**
     * Тестирование генератора с разными уровнями сложности
     */
    public function runTestSuite() {
        require_once __DIR__ . '/generate-enemies.php';
        
        $test_results = [];
        $test_cases = [
            ['name' => 'Легкий уровень (CR 0-3)', 'threat_level' => 'easy'],
            ['name' => 'Средний уровень (CR 1-4)', 'threat_level' => 'medium'],
            ['name' => 'Сложный уровень (CR 2-6)', 'threat_level' => 'hard'],
            ['name' => 'Смертельный уровень (CR 5-12)', 'threat_level' => 'deadly'],
            ['name' => 'Конкретный CR 1', 'threat_level' => '1'],
            ['name' => 'Конкретный CR 5', 'threat_level' => '5'],
            ['name' => 'Конкретный CR 10', 'threat_level' => '10'],
        ];
        
        foreach ($test_cases as $test_case) {
            $this->log("TEST_START: {$test_case['name']} (threat_level: {$test_case['threat_level']})");
            
            $params = [
                'threat_level' => $test_case['threat_level'],
                'count' => 1,
                'enemy_type' => '',
                'environment' => '',
                'use_ai' => 'off' // Отключаем AI для чистого тестирования API
            ];
            
            try {
                $generator = new EnemyGenerator();
                $result = $generator->generateEnemies($params);
                
                if ($result['success']) {
                    $this->log("TEST_SUCCESS: {$test_case['name']} - найдено " . count($result['enemies']) . " противников");
                    $test_results[] = [
                        'test' => $test_case['name'],
                        'success' => true,
                        'enemies_count' => count($result['enemies']),
                        'threat_level_display' => $result['threat_level_display'],
                        'cr_range' => $result['cr_range']
                    ];
                } else {
                    $this->log("TEST_ERROR: {$test_case['name']} - {$result['error']}");
                    $test_results[] = [
                        'test' => $test_case['name'],
                        'success' => false,
                        'error' => $result['error']
                    ];
                }
                
            } catch (Exception $e) {
                $this->log("TEST_EXCEPTION: {$test_case['name']} - " . $e->getMessage());
                $test_results[] = [
                    'test' => $test_case['name'],
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
            
            // Небольшая пауза между тестами
            sleep(1);
        }
        
        return $test_results;
    }
    
    /**
     * Логирование сообщения
     */
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $log_entry = "[$timestamp] $message\n";
        file_put_contents($this->log_file, $log_entry, FILE_APPEND | LOCK_EX);
    }
}

// Обработка запросов
$monitor = new EnemyMonitor();
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'logs':
        $limit = (int)($_GET['limit'] ?? 50);
        $logs = $monitor->getRecentLogs($limit);
        echo json_encode([
            'success' => true,
            'logs' => $logs
        ], JSON_UNESCAPED_UNICODE);
        break;
        
    case 'stats':
        $stats = $monitor->getGenerationStats();
        echo json_encode([
            'success' => true,
            'stats' => $stats
        ], JSON_UNESCAPED_UNICODE);
        break;
        
    case 'last':
        $details = $monitor->getLastGenerationDetails();
        echo json_encode([
            'success' => true,
            'details' => $details
        ], JSON_UNESCAPED_UNICODE);
        break;
        
    case 'test':
        $test_results = $monitor->runTestSuite();
        echo json_encode([
            'success' => true,
            'test_results' => $test_results
        ], JSON_UNESCAPED_UNICODE);
        break;
        
    default:
        echo json_encode([
            'success' => false,
            'error' => 'Неизвестное действие. Доступные: logs, stats, last, test'
        ], JSON_UNESCAPED_UNICODE);
}
?>
