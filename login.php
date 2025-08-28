<?php
require_once 'users.php';

// Если пользователь уже авторизован, перенаправляем на главную
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

// Генерируем CSRF токен
$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход в DnD Copilot</title>
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
        
        .login-container {
            background: var(--bg-primary) url('https://www.transparenttextures.com/patterns/old-mathematics.png');
            border: 8px solid var(--border-primary);
            border-radius: 24px;
            box-shadow: 0 8px 32px var(--shadow-secondary), 0 0 0 12px rgba(210, 180, 140, 0.3);
            max-width: 500px;
            width: 100%;
            padding: 40px 30px;
            position: relative;
        }
        
        .login-container:before,
        .login-container:after {
            content: '';
            position: absolute;
            width: 54px;
            height: 54px;
            background: url('https://cdn-icons-png.flaticon.com/512/616/616494.png') no-repeat center/contain;
            opacity: 0.12;
        }
        
        .login-container:before {
            left: -30px;
            top: -30px;
        }
        
        .login-container:after {
            right: -30px;
            bottom: -30px;
            transform: scaleX(-1);
        }
        
        h1 {
            font-family: 'UnifrakturCook', cursive;
            font-size: 2.2em;
            text-align: center;
            margin-bottom: 30px;
            color: var(--text-tertiary);
            letter-spacing: 2px;
            text-shadow: 0 2px 0 rgba(255, 255, 255, 0.5), 0 0 8px rgba(166, 124, 82, 0.7);
        }
        
        .form-tabs {
            display: flex;
            margin-bottom: 30px;
            border-bottom: 2px solid var(--border-primary);
        }
        
        .tab-btn {
            flex: 1;
            padding: 15px;
            background: none;
            border: none;
            font-family: inherit;
            font-size: 1.1em;
            color: var(--text-secondary);
            cursor: pointer;
            transition: all 0.3s ease;
            border-bottom: 3px solid transparent;
        }
        
        .tab-btn.active {
            color: var(--text-tertiary);
            border-bottom-color: var(--accent-primary);
            font-weight: bold;
        }
        
        .tab-btn:hover {
            background: var(--bg-tertiary);
        }
        
        .form-content {
            display: none;
        }
        
        .form-content.active {
            display: block;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-secondary);
            font-weight: bold;
            font-size: 1.1em;
        }
        
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--border-primary);
            border-radius: 10px;
            font-size: 1.1em;
            background: var(--bg-secondary);
            color: var(--text-primary);
            font-family: inherit;
            box-sizing: border-box;
            transition: all 0.3s ease;
        }
        
        input[type="text"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: var(--accent-secondary);
            background: var(--bg-tertiary);
            box-shadow: 0 0 15px rgba(166, 124, 82, 0.3);
        }
        
        .submit-btn {
            width: 100%;
            padding: 15px;
            background: var(--accent-primary);
            color: var(--bg-secondary);
            border: 2px solid var(--accent-secondary);
            border-radius: 10px;
            font-size: 1.2em;
            font-family: inherit;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }
        
        .submit-btn:hover {
            background: var(--accent-secondary);
            color: var(--bg-secondary);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px var(--shadow-secondary);
        }
        
        .message {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: bold;
            text-align: center;
        }
        
        .message.success {
            background: var(--accent-success);
            color: var(--bg-secondary);
        }
        
        .message.error {
            background: var(--accent-danger);
            color: var(--bg-secondary);
        }
        
        .loading {
            display: none;
            text-align: center;
            margin-top: 10px;
        }
        
        .spinner {
            border: 3px solid var(--bg-tertiary);
            border-top: 3px solid var(--accent-primary);
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .divider {
            text-align: center;
            margin: 20px 0;
            position: relative;
        }
        
        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: var(--border-primary);
        }
        
        .divider span {
            background: var(--bg-primary);
            padding: 0 15px;
            color: var(--text-secondary);
            font-size: 0.9em;
        }
        
        .google-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding: 12px;
            background: white;
            color: #333;
            border: 2px solid var(--border-primary);
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .google-btn:hover {
            background: #f8f9fa;
            border-color: var(--accent-primary);
            transform: translateY(-1px);
        }
        
        @media (max-width: 600px) {
            .login-container {
                padding: 30px 20px;
                margin: 10px;
            }
            
            h1 {
                font-size: 1.8em;
            }
            
            .tab-btn {
                padding: 12px;
                font-size: 1em;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>DnD Copilot</h1>
        
        <div class="form-tabs">
            <button class="tab-btn active" onclick="switchTab('login')">Вход</button>
            <button class="tab-btn" onclick="switchTab('register')">Регистрация</button>
        </div>
        
        <div id="message"></div>
        
        <!-- Форма входа -->
        <div id="login-form" class="form-content active">
            <form id="loginForm">
                <div class="form-group">
                    <label for="login-username">Имя пользователя:</label>
                    <input type="text" id="login-username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="login-password">Пароль:</label>
                    <input type="password" id="login-password" name="password" required>
                </div>
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <button type="submit" class="submit-btn">Войти</button>
            </form>
            
            <div class="divider">
                <span>или</span>
            </div>
            
            <a href="google-auth.php" class="google-btn">
                <img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTgiIGhlaWdodD0iMTgiIHZpZXdCb3g9IjAgMCAxOCAxOCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTE3LjY0IDkuMjA0NTVDMTcuNjQgOC41NjY0IDE3LjU4MjcgNy45NTI3MyAxNy40NzI3IDcuMzYzNjRIMTlWMTBIMTcuNjRWOS4yMDQ1NVoiIGZpbGw9IiNGQjQwMzEiLz4KPHBhdGggZD0iTTkgMTguMDAwMUMxMS40MyAxOC4wMDAxIDEzLjQ2NzMgMTcuMTk0NSAxNC45NjM2IDE1Ljc4MzZMMTIuNzA5MSAxNC4wNjM2QzExLjk3MjcgMTQuNzYzNiAxMC44NzI3IDE1LjIyNzMgOS41IDE1LjIyNzNDNy4xNDU0NSAxNS4yMjczIDUuMjcyNzMgMTMuNjM2NCA0LjU0NTQ1IDExLjU0NTVIMi4wOTA5MVYxMy44MTgySDQuNTQ1NDVDNC44ODE4MiAxNC42MzY0IDUuNjM2MzYgMTUuMjcyNyA2LjU5MDkxIDE1LjI3MjdDNi44MTgxOCAxNS4yNzI3IDcuMDMxODIgMTUuMjMxOCA3LjIzMTgyIDE1LjE1NDVDNy40MzE4MiAxNS4wNzcyIDcuNjE4MTggMTQuOTY4MiA3Ljc4MTgyIDE0LjgyNzNDNy45NDU0NSAxNC42ODY0IDguMDgxODIgMTQuNTE4MiA4LjE5MDkxIDE0LjMyNzNDOC4zIDE0LjEzNjQgOC4zNjM2NCAxMy45MjcyIDguMzkwOTEgMTMuNzA5MUM4LjQxODE4IDEzLjQ5MDkgOC40MTgxOCAxMy4yNjM2IDguMzkwOTEgMTMuMDQ1NUg4LjM2MzY0SDQuNTQ1NDVDNC41NDU0NSAxMi45NTQ1IDQuNTQ1NDUgMTIuODYzNiA0LjU0NTQ1IDEyLjc3MjdDNC41NDU0NSAxMi42ODE4IDQuNTQ1NDUgMTIuNTkwOSA0LjU0NTQ1IDEyLjVIMTlWMTQuNUg4LjM5MDkxQzguMzYzNjQgMTQuMjgxOCA4LjMgMTQuMDcyNyA4LjE5MDkxIDEzLjg4MThDOC4wODE4MiAxMy42OTA5IDcuOTQ1NDUgMTMuNTIyNyA3Ljc4MTgyIDEzLjM4MThDNy42MTgxOCAxMy4yNDA5IDcuNDMxODIgMTMuMTMxOCA3LjIzMTgyIDEzLjA1NDVDNy4wMzE4MiAxMi45NzcyIDYuODE4MTggMTIuOTM2NCA2LjU5MDkxIDEyLjkzNjRDNi4zNjM2NCAxMi45MzY0IDYuMTUwOTEgMTIuOTc3MiA1Ljk1MDkxIDEzLjA1NDVDNS43NTA5MSAxMy4xMzE4IDUuNTY4MTggMTMuMjQwOSA1LjQwNDU1IDEzLjM4MThDNS4yNDA5MSAxMy41MjI3IDUuMTA0NTUgMTMuNjkwOSA0Ljk5NTQ1IDEzLjg4MThDNC44ODYzNiAxNC4wNzI3IDQuODIyNzMgMTQuMjgxOCA0Ljc5NTQ1IDE0LjVIMi4wOTA5MVYxNi43NzI3SDQuNTQ1NDVDNS4yNzI3MyAxNC42MzY0IDcuMTQ1NDUgMTMuMDQ1NSA5LjUgMTMuMDQ1NUMxMC44NzI3IDEzLjA0NTUgMTEuOTcyNyAxMy41MDkxIDEyLjcwOTEgMTQuMjA5MUwxNC45NjM2IDEyLjQ4OTFDMTMuNDY3MyAxMS4wNzgxIDExLjQzIDEwLjI3MjcgOSAxMC4yNzI3QzYuNTY5MDkgMTAuMjcyNyA0LjUzMTgyIDExLjA3ODEgMy4wMzYzNiAxMi40ODkxQzEuNTQwOTEgMTMuODk5MSAwLjc3MjcyNyAxNS44MzE4IDAuNzcyNzI3IDE4SDBWMTYuNzI3M0MwIDE0LjQ1NDUgMC43NzI3MjcgMTIuNTIyNyAyLjI2ODE4IDEwLjk5MDlDMy43NjM2NCA5LjQ1OTA5IDUuNzY5MDkgOC42ODE4MiA4LjI4MTgyIDguNjgxODJDMTAuNzk0NSA4LjY4MTgyIDEyLjgwMTggOS40NTkwOSAxNC4yOTczIDEwLjk5MDlDMTUuNzkyNyAxMi41MjI3IDE2LjU0NTUgMTQuNDU0NSAxNi41NDU1IDE2Ljc3MjNWMThIMTlWMTYuNzI3M0MxOSAxNC40NTQ1IDE4LjIyNzMgMTIuNTIyNyAxNi43MzE4IDEwLjk5MDlDMTUuMjM2NCA5LjQ1OTA5IDEzLjIzMDkgOC42ODE4MiAxMC43MTgyIDguNjgxODJaIiBmaWxsPSIjRkZDMTA3Ii8+CjxwYXRoIGQ9Ik0xNy42NCA5LjIwNDU1QzE3LjY0IDguNTY2NCAxNy41ODI3IDcuOTUyNzMgMTcuNDcyNyA3LjM2MzY0SDE5VjEwSDE3LjY0VjkuMjA0NTVaIiBmaWxsPSIjRkI0MDMxIi8+CjxwYXRoIGQ9Ik0xNy42NCA5LjIwNDU1QzE3LjY0IDguNTY2NCAxNy41ODI3IDcuOTUyNzMgMTcuNDcyNyA3LjM2MzY0SDE5VjEwSDE3LjY0VjkuMjA0NTVaIiBmaWxsPSIjRkI0MDMxIi8+Cjwvc3ZnPgo=" alt="Google" width="18" height="18">
                Войти через Google
            </a>
        </div>
        
        <!-- Форма регистрации -->
        <div id="register-form" class="form-content">
            <form id="registerForm">
                <div class="form-group">
                    <label for="register-username">Имя пользователя:</label>
                    <input type="text" id="register-username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="register-password">Пароль:</label>
                    <input type="password" id="register-password" name="password" required>
                    <small style="color: #666; font-size: 0.9em; margin-top: 5px; display: block;">
                        Минимум 8 символов: заглавные, строчные, цифры, спецсимволы
                    </small>
                </div>
                <div class="form-group">
                    <label for="register-password-confirm">Подтвердите пароль:</label>
                    <input type="password" id="register-password-confirm" name="password_confirm" required>
                </div>
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <button type="submit" class="submit-btn">Зарегистрироваться</button>
            </form>
            
            <div class="divider">
                <span>или</span>
            </div>
            
            <a href="google-auth.php" class="google-btn">
                <img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTgiIGhlaWdodD0iMTgiIHZpZXdCb3g9IjAgMCAxOCAxOCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTE3LjY0IDkuMjA0NTVDMTcuNjQgOC41NjY0IDE3LjU4MjcgNy45NTI3MyAxNy40NzI3IDcuMzYzNjRIMTlWMTBIMTcuNjRWOS4yMDQ1NVoiIGZpbGw9IiNGQjQwMzEiLz4KPHBhdGggZD0iTTkgMTguMDAwMUMxMS40MyAxOC4wMDAxIDEzLjQ2NzMgMTcuMTk0NSAxNC45NjM2IDE1Ljc4MzZMMTIuNzA5MSAxNC4wNjM2QzExLjk3MjcgMTQuNzYzNiAxMC44NzI3IDE1LjIyNzMgOS41IDE1LjIyNzNDNy4xNDU0NSAxNS4yMjczIDUuMjcyNzMgMTMuNjM2NCA0LjU0NTQ1IDExLjU0NTVIMi4wOTA5MVYxMy44MTgySDQuNTQ1NDVDNC44ODE4MiAxNC42MzY0IDUuNjM2MzYgMTUuMjcyNyA2LjU5MDkxIDE1LjI3MjdDNi44MTgxOCAxNS4yNzI3IDcuMDMxODIgMTUuMjMxOCA3LjIzMTgyIDE1LjE1NDVDNy40MzE4MiAxNS4wNzcyIDcuNjE4MTggMTQuOTY4MiA3Ljc4MTgyIDE0LjgyNzNDNy45NDU0NSAxNC42ODY0IDguMDgxODIgMTQuNTE4MiA4LjE5MDkxIDE0LjMyNzNDOC4zIDE0LjEzNjQgOC4zNjM2NCAxMy45MjcyIDguMzkwOTEgMTMuNzA5MUM4LjQxODE4IDEzLjQ5MDkgOC40MTgxOCAxMy4yNjM2IDguMzkwOTEgMTMuMDQ1NUg4LjM2MzY0SDQuNTQ1NDVDNC41NDU0NSAxMi45NTQ1IDQuNTQ1NDUgMTIuODYzNiA0LjU0NTQ1IDEyLjc3MjdDNC41NDU0NSAxMi42ODE4IDQuNTQ1NDUgMTIuNTkwOSA0LjU0NTQ1IDEyLjVIMTlWMTQuNUg4LjM5MDkxQzguMzYzNjQgMTQuMjgxOCA4LjMgMTQuMDcyNyA4LjE5MDkxIDEzLjg4MThDOC4wODE4MiAxMy42OTA5IDcuOTQ1NDUgMTMuNTIyNyA3Ljc4MTgyIDEzLjM4MThDNy42MTgxOCAxMy4yNDA5IDcuNDMxODIgMTMuMTMxOCA3LjIzMTgyIDEzLjA1NDVDNy4wMzE4MiAxMi45NzcyIDYuODE4MTggMTIuOTM2NCA2LjU5MDkxIDEyLjkzNjRDNi4zNjM2NCAxMi45MzY0IDYuMTUwOTEgMTIuOTc3MiA1Ljk1MDkxIDEzLjA1NDVDNS43NTA5MSAxMy4xMzE4IDUuNTY4MTggMTMuMjQwOSA1LjQwNDU1IDEzLjM4MThDNS4yNDA5MSAxMy41MjI3IDUuMTA0NTUgMTMuNjkwOSA0Ljk5NTQ1IDEzLjg4MThDNC44ODYzNiAxNC4wNzI3IDQuODIyNzMgMTQuMjgxOCA0Ljc5NTQ1IDE0LjVIMi4wOTA5MVYxNi43NzI3SDQuNTQ1NDVDNS4yNzI3MyAxNC42MzY0IDcuMTQ1NDUgMTMuMDQ1NSA5LjUgMTMuMDQ1NUMxMC44NzI3IDEzLjA0NTUgMTEuOTcyNyAxMy41MDkxIDEyLjcwOTEgMTQuMjA5MUwxNC45NjM2IDEyLjQ4OTFDMTMuNDY3MyAxMS4wNzgxIDExLjQzIDEwLjI3MjcgOSAxMC4yNzI3QzYuNTY5MDkgMTAuMjcyNyA0LjUzMTgyIDExLjA3ODEgMy4wMzYzNiAxMi40ODkxQzEuNTQwOTEgMTMuODk5MSAwLjc3MjcyNyAxNS44MzE4IDAuNzcyNzI3IDE4SDBWMTYuNzI3M0MwIDE0LjQ1NDUgMC43NzI3MjcgMTIuNTIyNyAyLjI2ODE4IDEwLjk5MDlDMy43NjM2NCA5LjQ1OTA5IDUuNzY5MDkgOC42ODE4MiA4LjI4MTgyIDguNjgxODJDMTAuNzk0NSA4LjY4MTgyIDEyLjgwMTggOS40NTkwOSAxNC4yOTczIDEwLjk5MDlDMTUuNzkyNyAxMi41MjI3IDE2LjU0NTUgMTQuNDU0NSAxNi41NDU1IDE2Ljc3MjNWMThIMTlWMTYuNzI3M0MxOSAxNC40NTQ1IDE4LjIyNzMgMTIuNTIyNyAxNi43MzE4IDEwLjk5MDlDMTUuMjM2NCA5LjQ1OTA5IDEzLjIzMDkgOC42ODE4MiAxMC43MTgyIDguNjgxODJaIiBmaWxsPSIjRkZDMTA3Ii8+CjxwYXRoIGQ9Ik0xNy42NCA5LjIwNDU1QzE3LjY0IDguNTY2NCAxNy41ODI3IDcuOTUyNzMgMTcuNDcyNyA3LjM2MzY0SDE5VjEwSDE3LjY0VjkuMjA0NTVaIiBmaWxsPSIjRkI0MDMxIi8+CjxwYXRoIGQ9Ik0xNy42NCA5LjIwNDU1QzE3LjY0IDguNTY2NCAxNy41ODI3IDcuOTUyNzMgMTcuNDcyNyA3LjM2MzY0SDE5VjEwSDE3LjY0VjkuMjA0NTVaIiBmaWxsPSIjRkI0MDMxIi8+Cjwvc3ZnPgo=" alt="Google" width="18" height="18">
                Зарегистрироваться через Google
            </a>
        </div>
        
        <div class="loading" id="loading">
            <div class="spinner"></div>
        </div>
    </div>

    <script>
        function switchTab(tab) {
            // Убираем активный класс у всех кнопок и форм
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.form-content').forEach(form => form.classList.remove('active'));
            
            // Добавляем активный класс к выбранной вкладке
            if (tab === 'login') {
                document.querySelector('.tab-btn:first-child').classList.add('active');
                document.getElementById('login-form').classList.add('active');
                // Фокус на поле ввода имени пользователя
                setTimeout(() => document.getElementById('login-username').focus(), 100);
            } else {
                document.querySelector('.tab-btn:last-child').classList.add('active');
                document.getElementById('register-form').classList.add('active');
                // Фокус на поле ввода имени пользователя
                setTimeout(() => document.getElementById('register-username').focus(), 100);
            }
            
            // Очищаем сообщения
            document.getElementById('message').innerHTML = '';
        }
        
        function showMessage(text, type) {
            const messageDiv = document.getElementById('message');
            messageDiv.innerHTML = `<div class="message ${type}">${text}</div>`;
        }
        
        function showLoading(show) {
            document.getElementById('loading').style.display = show ? 'block' : 'none';
        }
        
        // Автофокус при загрузке страницы
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('login-username').focus();
        });
        
        // Автоматический переход к следующему полю при нажатии Enter
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                const activeElement = document.activeElement;
                if (activeElement.tagName === 'INPUT') {
                    const currentForm = activeElement.closest('.form-content');
                    if (currentForm) {
                        const inputs = Array.from(currentForm.querySelectorAll('input[type="text"], input[type="password"]'));
                        const currentIndex = inputs.indexOf(activeElement);
                        if (currentIndex < inputs.length - 1) {
                            inputs[currentIndex + 1].focus();
                            e.preventDefault();
                        }
                    }
                }
            }
        });
        
        // Обработка формы входа
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const username = document.getElementById('login-username').value.trim();
            const password = document.getElementById('login-password').value;
            const csrfToken = document.querySelector('#loginForm input[name="csrf_token"]').value;
            
            if (!username || !password) {
                showMessage('Заполните все поля', 'error');
                return;
            }
            
            showLoading(true);
            
            const formData = new FormData();
            formData.append('action', 'login');
            formData.append('username', username);
            formData.append('password', password);
            formData.append('csrf_token', csrfToken);
            
            fetch('users.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                showLoading(false);
                if (data.success) {
                    showMessage(data.message, 'success');
                    setTimeout(() => {
                        window.location.href = 'index.php';
                    }, 1000);
                } else {
                    showMessage(data.message, 'error');
                    // Фокус на поле пароля при ошибке
                    document.getElementById('login-password').focus();
                }
            })
            .catch(error => {
                showLoading(false);
                showMessage('Ошибка соединения', 'error');
            });
        });
        
        // Обработка формы регистрации
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const username = document.getElementById('register-username').value.trim();
            const password = document.getElementById('register-password').value;
            const passwordConfirm = document.getElementById('register-password-confirm').value;
            const csrfToken = document.querySelector('#registerForm input[name="csrf_token"]').value;
            
            if (!username || !password || !passwordConfirm) {
                showMessage('Заполните все поля', 'error');
                return;
            }
            
            if (password !== passwordConfirm) {
                showMessage('Пароли не совпадают', 'error');
                document.getElementById('register-password-confirm').focus();
                return;
            }
            
            // Проверяем сложность пароля на клиенте
            if (password.length < 8) {
                showMessage('Пароль должен быть не менее 8 символов', 'error');
                document.getElementById('register-password').focus();
                return;
            }
            
            if (!/[A-Z]/.test(password)) {
                showMessage('Пароль должен содержать хотя бы одну заглавную букву', 'error');
                document.getElementById('register-password').focus();
                return;
            }
            
            if (!/[a-z]/.test(password)) {
                showMessage('Пароль должен содержать хотя бы одну строчную букву', 'error');
                document.getElementById('register-password').focus();
                return;
            }
            
            if (!/[0-9]/.test(password)) {
                showMessage('Пароль должен содержать хотя бы одну цифру', 'error');
                document.getElementById('register-password').focus();
                return;
            }
            
            if (!/[^A-Za-z0-9]/.test(password)) {
                showMessage('Пароль должен содержать хотя бы один специальный символ', 'error');
                document.getElementById('register-password').focus();
                return;
            }
            
            showLoading(true);
            
            const formData = new FormData();
            formData.append('action', 'register');
            formData.append('username', username);
            formData.append('password', password);
            formData.append('csrf_token', csrfToken);
            
            fetch('users.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                showLoading(false);
                if (data.success) {
                    showMessage('Регистрация успешна! Выполняется вход...', 'success');
                    // Автоматически входим в систему после регистрации
                    setTimeout(() => {
                        const loginData = new FormData();
                        loginData.append('action', 'login');
                        loginData.append('username', username);
                        loginData.append('password', password);
                        loginData.append('csrf_token', csrfToken);
                        
                        fetch('users.php', {
                            method: 'POST',
                            body: loginData
                        })
                        .then(response => response.json())
                        .then(loginResult => {
                            if (loginResult.success) {
                                window.location.href = 'index.php?welcome=1';
                            } else {
                                // Если автоматический вход не удался, переключаемся на вкладку входа
                                switchTab('login');
                                document.getElementById('login-username').value = username;
                                showMessage('Регистрация успешна! Теперь войдите в систему.', 'success');
                            }
                        });
                    }, 1500);
                } else {
                    showMessage(data.message, 'error');
                    // Фокус на поле, где произошла ошибка
                    if (data.message.includes('имя пользователя')) {
                        document.getElementById('register-username').focus();
                    } else if (data.message.includes('пароль')) {
                        document.getElementById('register-password').focus();
                    }
                }
            })
            .catch(error => {
                showLoading(false);
                showMessage('Ошибка соединения', 'error');
            });
        });
    </script>
</body>
</html>
