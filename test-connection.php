<?php
// Тестовый файл для проверки соединения
header('Content-Type: application/json');

// Проверяем, что файлы существуют и доступны для записи
$users_file = 'users.json';
$login_attempts_file = 'login_attempts.json';

$result = [
    'success' => true,
    'message' => 'Тест соединения',
    'files' => [
        'users.json' => [
            'exists' => file_exists($users_file),
            'readable' => is_readable($users_file),
            'writable' => is_writable($users_file),
            'size' => file_exists($users_file) ? filesize($users_file) : 0,
            'content' => file_exists($users_file) ? file_get_contents($users_file) : 'file not found'
        ],
        'login_attempts.json' => [
            'exists' => file_exists($login_attempts_file),
            'readable' => is_readable($login_attempts_file),
            'writable' => is_writable($login_attempts_file),
            'size' => file_exists($login_attempts_file) ? filesize($login_attempts_file) : 0,
            'content' => file_exists($login_attempts_file) ? file_get_contents($login_attempts_file) : 'file not found'
        ]
    ],
    'php_errors' => error_get_last(),
    'session_status' => session_status(),
    'current_dir' => getcwd(),
    'server_info' => [
        'php_version' => PHP_VERSION,
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown'
    ]
];

echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
