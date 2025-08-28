<?php
require_once 'users.php';
require_once 'google-auth.php';

// Проверяем, есть ли данные от Google
if (!isset($_SESSION['google_user_data'])) {
    header('Location: login.php');
    exit;
}

$googleUserData = $_SESSION['google_user_data'];
$message = '';
$messageType = '';

// Обработка формы завершения регистрации
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Проверяем CSRF токен
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $message = 'Ошибка безопасности. Обновите страницу.';
        $messageType = 'error';
    } else {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';
        
        if (empty($username) || empty($email) || empty($password) || empty($password_confirm)) {
            $message = 'Заполните все поля';
            $messageType = 'error';
        } elseif ($password !== $password_confirm) {
            $message = 'Пароли не совпадают';
            $messageType = 'error';
        } else {
            // Проверяем сложность пароля
            $password_errors = validatePassword($password);
            if (!empty($password_errors)) {
                $message = implode('. ', $password_errors);
                $messageType = 'error';
            } else {
                // Создаем пользователя через Google
                $googleAuth = new GoogleAuth();
                $newUser = $googleAuth->createUserFromGoogle($googleUserData);
                
                // Устанавливаем сессию
                $_SESSION['user_id'] = $newUser['id'];
                $_SESSION['username'] = $newUser['username'];
                $_SESSION['role'] = $newUser['role'];
                $_SESSION['login_time'] = time();
                $_SESSION['auth_method'] = 'google';
                
                // Очищаем данные Google
                unset($_SESSION['google_user_data']);
                
                // Перенаправляем на главную страницу
                header('Location: index.php?welcome=1');
                exit;
            }
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
    <title>Завершение регистрации - DnD Copilot</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&family=UnifrakturCook:wght@700&family=IM+Fell+English+SC&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-primary: #f8ecd0;
            --bg-secondary: #fffbe6;
            --bg-tertiary: #f3e1b6;
            --text-primary: #2d1b00;
            --text-secondary: #3d2a0a;
            --text-tertiary: #7c4a02;
            --border-primary: #a67c52;
            --border-secondary: #7c4a02;
            --accent-primary: #a67c52;
            --accent-secondary: #7c4a02;
            --accent-success: #2bb07b;
            --accent-danger: #b71c1c;
            --shadow-primary: #0002;
            --shadow-secondary: #0006;
        }
        
        body {
            background: url('https://images.unsplash.com/photo-1506744038136-46273834b3fb?auto=format&fit=crop&w=1500&q=80') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            margin: 0;
            font-family: 'Roboto', 'IM Fell English SC', serif;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .registration-container {
            background: var(--bg-primary) url('https://www.transparenttextures.com/patterns/old-mathematics.png');
            border: 8px solid var(--border-primary);
            border-radius: 24px;
            box-shadow: 0 8px 32px var(--shadow-secondary), 0 0 0 12px rgba(210, 180, 140, 0.3);
            max-width: 600px;
            width: 100%;
            padding: 40px 30px;
            position: relative;
        }
        
        .registration-container:before,
        .registration-container:after {
            content: '';
            position: absolute;
            width: 54px;
            height: 54px;
            background: url('https://cdn-icons-png.flaticon.com/512/616/616494.png') no-repeat center/contain;
            opacity: 0.12;
        }
        
        .registration-container:before {
            left: -30px;
            top: -30px;
        }
        
        .registration-container:after {
            right: -30px;
            bottom: -30px;
            transform: scaleX(-1);
        }
        
        h1 {
            font-family: 'UnifrakturCook', cursive;
            font-size: 2.2em;
            text-align: center;
            margin-bottom: 20px;
            color: var(--text-tertiary);
            letter-spacing: 2px;
            text-shadow: 0 2px 0 rgba(255, 255, 255, 0.5), 0 0 8px rgba(166, 124, 82, 0.7);
        }
        
        .subtitle {
            text-align: center;
            color: var(--text-secondary);
            margin-bottom: 30px;
            font-size: 1.1em;
        }
        
        .google-info {
            background: var(--bg-secondary);
            border: 2px solid var(--border-primary);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .google-info h3 {
            color: var(--accent-primary);
            margin-bottom: 10px;
        }
        
        .google-info p {
            color: var(--text-secondary);
            margin: 5px 0;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 700;
            color: var(--text-secondary);
            font-size: 1.1em;
        }
        
        input[type="text"], 
        input[type="email"], 
        input[type="password"] {
            width: 100%;
            padding: 12px 16px;
            border: 3px solid var(--border-primary);
            border-radius: 12px;
            font-size: 16px;
            background: var(--bg-secondary);
            color: var(--text-primary);
            box-sizing: border-box;
            transition: all 0.3s ease;
        }
        
        input:focus {
            outline: none;
            border-color: var(--accent-secondary);
            box-shadow: 0 0 0 3px rgba(166, 124, 82, 0.2);
        }
        
        .btn {
            width: 100%;
            padding: 14px 24px;
            border: none;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary));
            color: white;
            box-shadow: 0 4px 15px rgba(166, 124, 82, 0.4);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(166, 124, 82, 0.6);
        }
        
        .message {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .message.error {
            background: rgba(183, 28, 28, 0.1);
            border: 2px solid var(--accent-danger);
            color: var(--accent-danger);
        }
        
        .message.success {
            background: rgba(43, 176, 123, 0.1);
            border: 2px solid var(--accent-success);
            color: var(--accent-success);
        }
        
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-link a {
            color: var(--accent-primary);
            text-decoration: none;
            font-weight: 600;
        }
        
        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="registration-container">
        <h1>Завершение регистрации</h1>
        <p class="subtitle">Данные получены от Google. Заполните дополнительные поля:</p>
        
        <div class="google-info">
            <h3>✅ Данные от Google</h3>
            <p><strong>Имя:</strong> <?php echo htmlspecialchars($googleUserData['name'] ?? 'Не указано'); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($googleUserData['email'] ?? 'Не указан'); ?></p>
        </div>
        
        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            
            <div class="form-group">
                <label for="username">Имя пользователя:</label>
                <input type="text" id="username" name="username" 
                       value="<?php echo htmlspecialchars($googleUserData['name'] ?? ''); ?>" 
                       required>
            </div>
            
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" 
                       value="<?php echo htmlspecialchars($googleUserData['email'] ?? ''); ?>" 
                       required readonly>
            </div>
            
            <div class="form-group">
                <label for="password">Пароль:</label>
                <input type="password" id="password" name="password" 
                       placeholder="Создайте пароль для входа" required>
            </div>
            
            <div class="form-group">
                <label for="password_confirm">Подтвердите пароль:</label>
                <input type="password" id="password_confirm" name="password_confirm" 
                       placeholder="Повторите пароль" required>
            </div>
            
            <button type="submit" class="btn btn-primary">
                Завершить регистрацию
            </button>
        </form>
        
        <div class="back-link">
            <a href="login.php">← Вернуться к входу</a>
        </div>
    </div>
</body>
</html>
