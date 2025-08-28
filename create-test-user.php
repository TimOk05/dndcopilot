<?php
require_once 'users.php';

// Создаем тестового пользователя
$testUser = [
    'id' => uniqid(),
    'username' => 'testuser',
    'email' => 'test@example.com',
    'password_hash' => password_hash('TestPass123!', PASSWORD_DEFAULT),
    'role' => 'user',
    'created_at' => date('Y-m-d H:i:s'),
    'is_active' => true,
    'login_count' => 0
];

$users = loadUsers();
$users[] = $testUser;
saveUsers($users);

echo "Тестовый пользователь создан:\n";
echo "Username: testuser\n";
echo "Email: test@example.com\n";
echo "Password: TestPass123!\n";
echo "Файл users.json обновлен.\n";
?>
