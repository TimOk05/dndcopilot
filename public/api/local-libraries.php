<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/config.php';

// Helper to send JSON
function send_json($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

$type = $_GET['type'] ?? '';

try {
    if ($type === 'races') {
        $racesFile = __DIR__ . '/../../data/персонажи/расы/races.json';
        if (!file_exists($racesFile)) {
            send_json(['success' => false, 'message' => 'Файл races.json не найден']);
        }
        $raw = file_get_contents($racesFile);
        $data = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            send_json(['success' => false, 'message' => 'Ошибка JSON races.json: ' . json_last_error_msg()]);
        }

        $racesRoot = $data['races'] ?? [];

        // Если запрошена конкретная раса — вернуть подрасы
        if (isset($_GET['race'])) {
            $raceKey = $_GET['race'];
            $race = $racesRoot[$raceKey] ?? null;
            if (!$race) {
                // Попробуем найти по name/name_en в качестве совместимости
                foreach ($racesRoot as $key => $r) {
                    if (
                        (isset($r['name']) && mb_strtolower($r['name'], 'UTF-8') === mb_strtolower($raceKey, 'UTF-8')) ||
                        (isset($r['name_en']) && mb_strtolower($r['name_en'], 'UTF-8') === mb_strtolower($raceKey, 'UTF-8'))
                    ) {
                        $race = $r;
                        break;
                    }
                }
            }
            $subraces = [];
            if ($race && isset($race['subraces']) && is_array($race['subraces'])) {
                foreach ($race['subraces'] as $sub) {
                    $subraces[] = [
                        'index' => $sub['id'] ?? ($sub['name_en'] ?? ($sub['name'] ?? 'subrace')),
                        'name' => $sub['name'] ?? ($sub['name_en'] ?? 'Подраса')
                    ];
                }
            }
            send_json(['success' => true, 'subraces' => $subraces]);
        }

        // Иначе вернуть список рас
        $races = [];
        foreach ($racesRoot as $key => $race) {
            $races[] = [
                'index' => $key,
                'name' => $race['name'] ?? ($race['name_en'] ?? $key),
                'has_subraces' => isset($race['subraces']) && is_array($race['subraces']) && count($race['subraces']) > 0
            ];
        }
        send_json(['success' => true, 'races' => $races]);
    }

    if ($type === 'classes') {
        $classesDir = __DIR__ . '/../../data/персонажи/классы';
        if (!is_dir($classesDir)) {
            send_json(['success' => false, 'message' => 'Каталог классов не найден']);
        }

        $classes = [];
        $dirs = scandir($classesDir);
        foreach ($dirs as $dir) {
            if ($dir === '.' || $dir === '..') continue;
            $classPath = $classesDir . '/' . $dir;
            if (!is_dir($classPath)) continue;
            $file = $classPath . '/' . $dir . '.json';
            if (!file_exists($file)) continue;
            $raw = file_get_contents($file);
            $json = json_decode($raw, true);
            if (!$json) continue;
            $name = $json['class']['name']['ru'] ?? ($json['class']['name']['en'] ?? $dir);
            $classes[] = [
                'index' => $dir,
                'name' => $name
            ];
        }

        // Стабильная сортировка по русскому имени
        usort($classes, function($a, $b) {
            return strcoll($a['name'], $b['name']);
        });

        send_json(['success' => true, 'classes' => $classes]);
    }

    if ($type === 'backgrounds') {
        // Локальная библиотека происхождений пока не подключена
        // Возвращаем пустой список, чтобы фронтенд корректно отработал
        send_json(['success' => true, 'backgrounds' => []]);
    }

    send_json(['success' => false, 'message' => 'Неверный тип']);
} catch (Throwable $e) {
    send_json(['success' => false, 'message' => 'Ошибка: ' . $e->getMessage()]);
}
?>


