<?php
// Получение информации о генерации противников с сервера
echo "=== МОНИТОРИНГ ГЕНЕРАТОРА ПРОТИВНИКОВ ===\n\n";

// URL вашего сервера (замените на реальный)
$server_url = 'http://localhost/dnd/public/api/enemy-monitor.php';

function makeRequest($url, $params = []) {
    $query_string = http_build_query($params);
    $full_url = $url . '?' . $query_string;
    
    echo "Запрос: $full_url\n";
    
    $response = @file_get_contents($full_url);
    if ($response === false) {
        echo "❌ Ошибка запроса к серверу\n";
        return null;
    }
    
    $data = json_decode($response, true);
    if (!$data) {
        echo "❌ Ошибка декодирования JSON: " . json_last_error_msg() . "\n";
        return null;
    }
    
    return $data;
}

// Получаем статистику
echo "--- Статистика генерации ---\n";
$stats = makeRequest($server_url, ['action' => 'stats']);
if ($stats && $stats['success']) {
    $s = $stats['stats'];
    echo "Всего запросов: " . $s['total_requests'] . "\n";
    echo "Успешных генераций: " . $s['successful_generations'] . "\n";
    echo "Неудачных генераций: " . $s['failed_generations'] . "\n";
    
    if (!empty($s['threat_levels'])) {
        echo "По уровням угрозы:\n";
        foreach ($s['threat_levels'] as $level => $count) {
            echo "  $level: $count\n";
        }
    }
    
    if (!empty($s['errors'])) {
        echo "Ошибки:\n";
        foreach ($s['errors'] as $error => $count) {
            echo "  $error: $count\n";
        }
    }
} else {
    echo "❌ Не удалось получить статистику\n";
}

echo "\n";

// Получаем последние логи
echo "--- Последние логи ---\n";
$logs = makeRequest($server_url, ['action' => 'logs', 'limit' => 20]);
if ($logs && $logs['success']) {
    foreach ($logs['logs'] as $log) {
        echo "$log\n";
    }
} else {
    echo "❌ Не удалось получить логи\n";
}

echo "\n";

// Получаем детали последней генерации
echo "--- Детали последней генерации ---\n";
$details = makeRequest($server_url, ['action' => 'last']);
if ($details && $details['success'] && $details['details']) {
    $d = $details['details'];
    if (isset($d['start'])) {
        echo "Начало: " . $d['start'] . "\n";
    }
    if (isset($d['success'])) {
        echo "Успех: " . $d['success'] . "\n";
    }
    if (isset($d['error'])) {
        echo "Ошибка: " . $d['error'] . "\n";
    }
    if (isset($d['logs'])) {
        echo "Логи:\n";
        foreach ($d['logs'] as $log) {
            echo "  $log\n";
        }
    }
} else {
    echo "❌ Нет данных о последней генерации\n";
}

echo "\n=== МОНИТОРИНГ ЗАВЕРШЕН ===\n";
?>
