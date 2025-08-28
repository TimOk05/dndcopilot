<?php
require_once 'users.php';

// Если пользователь уже авторизован, перенаправляем на главную
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$message = '';
$messageType = '';

// Обработка формы регистрации
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Проверяем CSRF токен
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $message = 'Ошибка безопасности. Обновите страницу.';
        $messageType = 'error';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';
        
        if (empty($username) || empty($password) || empty($password_confirm)) {
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
                $result = registerUser($username, $password);
                if ($result['success']) {
                    // Автоматически входим в систему после успешной регистрации
                    $_SESSION['user'] = $username;
                    $_SESSION['access_granted'] = true;
                    $_SESSION['login_time'] = time();
                    $_SESSION['ip'] = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                    
                    // Перенаправляем на главную страницу
                    header('Location: index.php?welcome=1');
                    exit;
                } else {
                    $message = $result['message'];
                    $messageType = 'error';
                }
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
    <title>Регистрация - DnD Copilot</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 500px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .form-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            text-align: center;
            color: #333;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            box-sizing: border-box;
        }
        input:focus {
            outline: none;
            border-color: #007cba;
            box-shadow: 0 0 5px rgba(0,124,186,0.3);
        }
        button {
            width: 100%;
            padding: 12px;
            background: #007cba;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        button:hover {
            background: #005a87;
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
        .links {
            text-align: center;
            margin-top: 20px;
        }
        .links a {
            color: #007cba;
            text-decoration: none;
        }
        .links a:hover {
            text-decoration: underline;
        }
        .loading {
            display: none;
            text-align: center;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h1>Регистрация в DnD Copilot</h1>
        
        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <form method="post" id="registerForm">
            <div class="form-group">
                <label for="username">Имя пользователя:</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required autocomplete="username">
            </div>
            
            <div class="form-group">
                <label for="password">Пароль:</label>
                <input type="password" id="password" name="password" required autocomplete="new-password">
                <small style="color: #666; font-size: 0.9em; margin-top: 5px; display: block;">
                    Пароль должен содержать минимум 8 символов, включая заглавные и строчные буквы, цифры и специальные символы
                </small>
            </div>
            
            <div class="form-group">
                <label for="password_confirm">Подтвердите пароль:</label>
                <input type="password" id="password_confirm" name="password_confirm" required autocomplete="new-password">
            </div>
            
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            <button type="submit" id="submitBtn">Зарегистрироваться</button>
            <div class="loading" id="loading">Регистрация...</div>
        </form>
        
        <div class="links">
            <p><a href="login.php">Уже есть аккаунт? Войти</a></p>
            
            <p><a href="index.php">Главная страница</a></p>
        </div>
    </div>

    <script>
        // Автофокус на первое поле при загрузке страницы
        document.addEventListener('DOMContentLoaded', function() {
            const usernameField = document.getElementById('username');
            if (usernameField && !usernameField.value) {
                usernameField.focus();
            }
        });

        // Показываем индикатор загрузки при отправке формы
        document.getElementById('registerForm').addEventListener('submit', function() {
            document.getElementById('submitBtn').style.display = 'none';
            document.getElementById('loading').style.display = 'block';
        });

        // Автоматический переход к следующему полю при нажатии Enter
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                const activeElement = document.activeElement;
                if (activeElement.tagName === 'INPUT') {
                    const inputs = Array.from(document.querySelectorAll('input[type="text"], input[type="password"]'));
                    const currentIndex = inputs.indexOf(activeElement);
                    if (currentIndex < inputs.length - 1) {
                        inputs[currentIndex + 1].focus();
                        e.preventDefault();
                    }
                }
            }
        });
    </script>
</body>
</html>
