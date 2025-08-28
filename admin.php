<?php
require_once 'config.php';
require_once 'users.php';

// Запускаем сессию
configureSession();

// Проверяем авторизацию
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Получаем данные пользователей
$users = loadUsers();
$currentUser = getCurrentUser();

// Проверяем права администратора
if (!isAdmin()) {
    // Если не администратор, показываем форму ввода пароля
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $csrf_token = generateCSRFToken();
        ?>
        <!DOCTYPE html>
        <html lang="ru">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Доступ администратора - DnD Copilot</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    margin: 0;
                    padding: 20px;
                    background: #f5f5f5;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    min-height: 100vh;
                }
                .admin-login-container {
                    max-width: 400px;
                    width: 100%;
                    background: white;
                    border-radius: 10px;
                    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                    overflow: hidden;
                }
                .admin-login-header {
                    background: #2c3e50;
                    color: white;
                    padding: 20px;
                    text-align: center;
                }
                .admin-login-header h1 {
                    margin: 0;
                    font-size: 1.5em;
                }
                .admin-login-content {
                    padding: 30px;
                }
                .form-group {
                    margin-bottom: 20px;
                }
                label {
                    display: block;
                    margin-bottom: 5px;
                    font-weight: bold;
                    color: #2c3e50;
                }
                input[type="password"] {
                    width: 100%;
                    padding: 12px;
                    border: 2px solid #ddd;
                    border-radius: 5px;
                    font-size: 16px;
                    box-sizing: border-box;
                }
                input[type="password"]:focus {
                    outline: none;
                    border-color: #3498db;
                    box-shadow: 0 0 5px rgba(52,152,219,0.3);
                }
                .admin-login-btn {
                    width: 100%;
                    padding: 12px;
                    background: #e74c3c;
                    color: white;
                    border: none;
                    border-radius: 5px;
                    font-size: 16px;
                    cursor: pointer;
                    transition: background 0.3s ease;
                }
                .admin-login-btn:hover {
                    background: #c0392b;
                }
                .back-link {
                    display: block;
                    text-align: center;
                    margin-top: 20px;
                    color: #3498db;
                    text-decoration: none;
                }
                .back-link:hover {
                    text-decoration: underline;
                }
                .message {
                    padding: 10px;
                    border-radius: 5px;
                    margin-bottom: 20px;
                    text-align: center;
                }
                .message.error {
                    background: #f8d7da;
                    color: #721c24;
                    border: 1px solid #f5c6cb;
                }
            </style>
        </head>
        <body>
            <div class="admin-login-container">
                <div class="admin-login-header">
                    <h1>🔒 Доступ администратора</h1>
                    <p>Введите пароль для доступа к админ панели</p>
                </div>
                
                <div class="admin-login-content">
                    <form method="post" id="adminLoginForm">
                        <div class="form-group">
                            <label for="admin_password">Пароль администратора:</label>
                            <input type="password" id="admin_password" name="admin_password" required autocomplete="current-password">
                        </div>
                        
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                        <button type="submit" class="admin-login-btn">Войти как администратор</button>
                    </form>
                    
                    <a href="index.php" class="back-link">← Вернуться к приложению</a>
                </div>
            </div>

            <script>
                // Автофокус на поле пароля
                document.addEventListener('DOMContentLoaded', function() {
                    document.getElementById('admin_password').focus();
                });

                // Обработка формы
                document.getElementById('adminLoginForm').addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const password = document.getElementById('admin_password').value;
                    const csrfToken = document.querySelector('input[name="csrf_token"]').value;
                    
                    if (!password) {
                        alert('Введите пароль');
                        return;
                    }
                    
                    const formData = new FormData();
                    formData.append('action', 'admin_login');
                    formData.append('password', password);
                    formData.append('csrf_token', csrfToken);
                    
                    fetch('users.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            window.location.reload();
                        } else {
                            alert(data.message);
                            document.getElementById('admin_password').focus();
                        }
                    })
                    .catch(error => {
                        alert('Ошибка соединения');
                    });
                });
            </script>
        </body>
        </html>
        <?php
        exit;
    } else {
        // Обработка POST запроса для входа администратора
        if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $password = $_POST['admin_password'] ?? '';
            if (checkAdminPassword($password)) {
                $_SESSION['is_admin'] = true;
                logActivity('ADMIN_LOGIN', 'admin', $_SERVER['REMOTE_ADDR'] ?? 'unknown', true);
                header('Location: admin.php');
                exit;
            } else {
                logActivity('ADMIN_LOGIN_FAILED', 'admin', $_SERVER['REMOTE_ADDR'] ?? 'unknown', false);
                header('Location: admin.php?error=1');
                exit;
            }
        }
    }
}

// Обработка действий администратора
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'clear_logs' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        // Очистка логов безопасности
        if (file_exists('security.log')) {
            file_put_contents('security.log', '');
        }
        if (file_exists('login_attempts.json')) {
            file_put_contents('login_attempts.json', '[]');
        }
        $message = 'Логи очищены';
        $messageType = 'success';
    }
    
    if ($action === 'reset_attempts' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        // Сброс попыток входа
        if (file_exists('login_attempts.json')) {
            file_put_contents('login_attempts.json', '[]');
        }
        $message = 'Попытки входа сброшены';
        $messageType = 'success';
    }
    
    if ($action === 'delete_user' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $username = trim($_POST['username'] ?? '');
        if (!empty($username) && !hash_equals($username, $currentUser)) {
            $users = array_filter($users, function($user) use ($username) {
                return !hash_equals($user['username'], $username);
            });
            saveUsers(array_values($users));
            $message = "Пользователь $username удален";
            $messageType = 'success';
            $users = loadUsers(); // Перезагружаем список
        }
    }
    
    if ($action === 'admin_logout' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        unset($_SESSION['is_admin']);
        logActivity('ADMIN_LOGOUT', 'admin', $_SERVER['REMOTE_ADDR'] ?? 'unknown', true);
        header('Location: index.php');
        exit;
    }
}

// Читаем логи безопасности
$securityLogs = [];
if (file_exists('security.log')) {
    $logContent = file_get_contents('security.log');
    $logLines = array_filter(explode("\n", $logContent));
    $securityLogs = array_slice(array_reverse($logLines), 0, 50); // Последние 50 записей
}

// Читаем попытки входа
$loginAttempts = [];
if (file_exists('login_attempts.json')) {
    $loginAttempts = json_decode(file_get_contents('login_attempts.json'), true) ?: [];
}

// Статистика
$totalUsers = count($users);
$activeUsers = 0;
$totalLogins = 0;
$recentActivity = 0;

foreach ($users as $user) {
    if (isset($user['login_count'])) {
        $totalLogins += $user['login_count'];
    }
    if (isset($user['last_login'])) {
        $lastLogin = strtotime($user['last_login']);
        if ($lastLogin > (time() - 86400)) { // Активны за последние 24 часа
            $activeUsers++;
        }
        if ($lastLogin > (time() - 3600)) { // Активны за последний час
            $recentActivity++;
        }
    }
}

// Генерируем CSRF токен
$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ панель - DnD Copilot</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }
        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .admin-header {
            background: #2c3e50;
            color: white;
            padding: 20px;
            text-align: center;
        }
        .admin-header h1 {
            margin: 0;
            font-size: 2em;
        }
        .admin-content {
            padding: 20px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: #ecf0f1;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        .stat-label {
            color: #7f8c8d;
            font-size: 1.1em;
        }
        .section {
            margin-bottom: 30px;
        }
        .section h2 {
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .users-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .users-table th,
        .users-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .users-table th {
            background: #3498db;
            color: white;
        }
        .users-table tr:hover {
            background: #f8f9fa;
        }
        .delete-btn {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
        }
        .delete-btn:hover {
            background: #c0392b;
        }
        .log-entry {
            background: #f8f9fa;
            padding: 10px;
            margin-bottom: 5px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 0.9em;
        }
        .log-success {
            border-left: 4px solid #27ae60;
        }
        .log-failed {
            border-left: 4px solid #e74c3c;
        }
        .admin-actions {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .admin-btn {
            background: #3498db;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
        }
        .admin-btn:hover {
            background: #2980b9;
        }
        .admin-btn.danger {
            background: #e74c3c;
        }
        .admin-btn.danger:hover {
            background: #c0392b;
        }
        .message {
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #3498db;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        .attempts-table {
            width: 100%;
            border-collapse: collapse;
        }
        .attempts-table th,
        .attempts-table td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .attempts-table th {
            background: #95a5a6;
            color: white;
        }
        .blocked {
            color: #e74c3c;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1>🔧 Админ панель</h1>
            <p>Управление пользователями и мониторинг безопасности</p>
        </div>
        
        <div class="admin-content">
            <a href="index.php" class="back-link">← Вернуться к приложению</a>
            
            <div class="message success" style="margin-bottom: 20px;">
                <strong>🔧 Режим администратора активен</strong><br>
                Пользователь: <?php echo htmlspecialchars($currentUser); ?> | 
                Время входа: <?php echo date('Y-m-d H:i:s'); ?>
            </div>
            
            <?php if (isset($message)): ?>
                <div class="message <?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <!-- Статистика -->
            <div class="section">
                <h2>📊 Общая статистика</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $totalUsers; ?></div>
                        <div class="stat-label">Всего пользователей</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $activeUsers; ?></div>
                        <div class="stat-label">Активных за 24 часа</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $recentActivity; ?></div>
                        <div class="stat-label">Активных за час</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $totalLogins; ?></div>
                        <div class="stat-label">Всего входов</div>
                    </div>
                </div>
            </div>
            
            <!-- Действия администратора -->
            <div class="section">
                <h2>⚙️ Действия администратора</h2>
                <div class="admin-actions">
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="action" value="clear_logs">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                        <button type="submit" class="admin-btn danger" onclick="return confirm('Очистить все логи безопасности?')">
                            🗑️ Очистить логи
                        </button>
                    </form>
                    
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="action" value="reset_attempts">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                        <button type="submit" class="admin-btn" onclick="return confirm('Сбросить все попытки входа?')">
                            🔄 Сбросить попытки входа
                        </button>
                    </form>
                    
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="action" value="admin_logout">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                        <button type="submit" class="admin-btn danger" onclick="return confirm('Выйти из режима администратора?')">
                            🚪 Выйти из админ панели
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Список пользователей -->
            <div class="section">
                <h2>👥 Пользователи</h2>
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>Имя пользователя</th>
                            <th>Дата регистрации</th>
                            <th>Последний вход</th>
                            <th>Количество входов</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['created_at'] ?? 'Неизвестно'); ?></td>
                                <td><?php echo htmlspecialchars($user['last_login'] ?? 'Никогда'); ?></td>
                                <td><?php echo htmlspecialchars($user['login_count'] ?? 0); ?></td>
                                <td>
                                    <?php if (!hash_equals($user['username'], $currentUser)): ?>
                                        <form method="post" style="display: inline;">
                                            <input type="hidden" name="action" value="delete_user">
                                            <input type="hidden" name="username" value="<?php echo htmlspecialchars($user['username']); ?>">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                            <button type="submit" class="delete-btn" onclick="return confirm('Удалить пользователя <?php echo htmlspecialchars($user['username']); ?>?')">
                                                Удалить
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span style="color: #7f8c8d;">Текущий пользователь</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Попытки входа -->
            <div class="section">
                <h2>🔐 Попытки входа</h2>
                <?php if (!empty($loginAttempts)): ?>
                    <table class="attempts-table">
                        <thead>
                            <tr>
                                <th>IP адрес</th>
                                <th>Количество попыток</th>
                                <th>Последняя попытка</th>
                                <th>Статус</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($loginAttempts as $ip => $data): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($ip); ?></td>
                                    <td><?php echo htmlspecialchars($data['count']); ?></td>
                                    <td><?php echo date('Y-m-d H:i:s', $data['last_attempt']); ?></td>
                                    <td>
                                        <?php if ($data['count'] >= 5): ?>
                                            <span class="blocked">Заблокирован</span>
                                        <?php else: ?>
                                            <span>Активен</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>Нет записей о попытках входа</p>
                <?php endif; ?>
            </div>
            
            <!-- Логи безопасности -->
            <div class="section">
                <h2>📝 Логи безопасности (последние 50 записей)</h2>
                <?php if (!empty($securityLogs)): ?>
                    <?php foreach ($securityLogs as $log): ?>
                        <div class="log-entry <?php echo strpos($log, 'SUCCESS') !== false ? 'log-success' : 'log-failed'; ?>">
                            <?php echo htmlspecialchars($log); ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>Логи безопасности пусты</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
