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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#a67c52">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="DnD Copilot">
    <title>DnD Copilot - Вход</title>
    <link rel="icon" type="image/svg+xml" href="./favicon.svg">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&family=UnifrakturCook:wght@700&family=IM+Fell+English+SC&display=swap" rel="stylesheet">
    <style>
        :root {
            /* Цветовая схема */
            --bg-primary: #f8ecd0;
            --bg-secondary: #fffbe6;
            --bg-tertiary: #f3e1b6;
            --bg-overlay: rgba(248, 236, 208, 0.95);
            --text-primary: #2d1b00;
            --text-secondary: #3d2a0a;
            --text-tertiary: #7c4a02;
            --border-primary: #a67c52;
            --border-secondary: #7c4a02;
            --accent-primary: #a67c52;
            --accent-secondary: #7c4a02;
            --accent-success: #2bb07b;
            --accent-danger: #b71c1c;
            --accent-warning: #ffd700;
            --shadow-primary: rgba(0, 0, 0, 0.1);
            --shadow-secondary: rgba(0, 0, 0, 0.2);
            --shadow-tertiary: rgba(0, 0, 0, 0.05);
            
            /* Размеры для мобильных */
            --header-height: 60px;
            --input-height: 48px;
            --button-height: 52px;
            --border-radius: 12px;
            --border-radius-large: 16px;
            --spacing-xs: 8px;
            --spacing-sm: 12px;
            --spacing-md: 16px;
            --spacing-lg: 24px;
            --spacing-xl: 32px;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Roboto', system-ui, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: var(--spacing-md);
            position: relative;
            overflow-x: hidden;
        }
        
        /* Анимированный фон */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 20% 80%, rgba(120, 119, 198, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 119, 198, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(120, 219, 255, 0.3) 0%, transparent 50%);
            animation: backgroundShift 20s ease-in-out infinite;
            z-index: -1;
        }
        
        @keyframes backgroundShift {
            0%, 100% { transform: scale(1) rotate(0deg); }
            50% { transform: scale(1.1) rotate(1deg); }
        }
        
        .login-container {
            background: var(--bg-overlay);
            backdrop-filter: blur(20px);
            border: 2px solid var(--border-primary);
            border-radius: var(--border-radius-large);
            box-shadow: 
                0 20px 40px var(--shadow-secondary),
                0 0 0 1px rgba(255, 255, 255, 0.1);
            width: 100%;
            max-width: 400px;
            padding: var(--spacing-xl);
            position: relative;
            overflow: hidden;
        }
        
        /* Декоративные элементы */
        .login-container::before,
        .login-container::after {
            content: '';
            position: absolute;
            width: 100px;
            height: 100px;
            background: linear-gradient(45deg, var(--accent-primary), var(--accent-secondary));
            border-radius: 50%;
            opacity: 0.1;
            z-index: -1;
        }
        
        .login-container::before {
            top: -50px;
            left: -50px;
            animation: float 6s ease-in-out infinite;
        }
        
        .login-container::after {
            bottom: -50px;
            right: -50px;
            animation: float 6s ease-in-out infinite reverse;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }
        
        .header {
            text-align: center;
            margin-bottom: var(--spacing-xl);
        }
        
        .logo {
            font-family: 'UnifrakturCook', cursive;
            font-size: 2.5rem;
            color: var(--text-tertiary);
            margin-bottom: var(--spacing-sm);
            text-shadow: 
                0 2px 4px var(--shadow-primary),
                0 0 20px rgba(166, 124, 82, 0.3);
            letter-spacing: 2px;
        }
        
        .subtitle {
            color: var(--text-secondary);
            font-size: 1rem;
            font-weight: 500;
            opacity: 0.8;
        }
        
        .form-tabs {
            display: flex;
            background: var(--bg-tertiary);
            border-radius: var(--border-radius);
            padding: 4px;
            margin-bottom: var(--spacing-xl);
            position: relative;
        }
        
        .tab-btn {
            flex: 1;
            padding: var(--spacing-sm) var(--spacing-md);
            background: none;
            border: none;
            font-family: inherit;
            font-size: 1rem;
            font-weight: 500;
            color: var(--text-secondary);
            cursor: pointer;
            border-radius: calc(var(--border-radius) - 4px);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            z-index: 2;
        }
        
        .tab-btn.active {
            background: var(--bg-primary);
            color: var(--text-tertiary);
            box-shadow: 0 2px 8px var(--shadow-primary);
            transform: translateY(-1px);
        }
        
        .tab-btn:not(.active):hover {
            color: var(--text-tertiary);
            background: rgba(166, 124, 82, 0.1);
        }
        
        .form-content {
            display: none;
            animation: fadeInUp 0.3s ease-out;
        }
        
        .form-content.active {
            display: block;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .form-group {
            margin-bottom: var(--spacing-lg);
        }
        
        .form-group:last-child {
            margin-bottom: var(--spacing-md);
        }
        
        label {
            display: block;
            margin-bottom: var(--spacing-xs);
            color: var(--text-secondary);
            font-weight: 500;
            font-size: 0.95rem;
        }
        
        .input-wrapper {
            position: relative;
        }
        
        input[type="text"],
        input[type="password"] {
            width: 100%;
            height: var(--input-height);
            padding: 0 var(--spacing-md);
            border: 2px solid var(--border-primary);
            border-radius: var(--border-radius);
            font-size: 1rem;
            background: var(--bg-secondary);
            color: var(--text-primary);
            font-family: inherit;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }
        
        input[type="text"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: var(--accent-secondary);
            background: var(--bg-primary);
            box-shadow: 
                0 0 0 4px rgba(166, 124, 82, 0.1),
                0 4px 12px var(--shadow-primary);
            transform: translateY(-1px);
        }
        
        .input-icon {
            position: absolute;
            right: var(--spacing-md);
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-tertiary);
            font-size: 1.2rem;
            pointer-events: none;
            transition: all 0.3s ease;
        }
        
        input:focus + .input-icon {
            color: var(--accent-secondary);
            transform: translateY(-50%) scale(1.1);
        }
        
        .submit-btn {
            width: 100%;
            height: var(--button-height);
            background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary));
            color: var(--bg-secondary);
            border: none;
            border-radius: var(--border-radius);
            font-size: 1.1rem;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            margin-top: var(--spacing-md);
        }
        
        .submit-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }
        
        .submit-btn:hover::before {
            left: 100%;
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 
                0 8px 25px var(--shadow-secondary),
                0 0 0 1px rgba(255, 255, 255, 0.1);
        }
        
        .submit-btn:active {
            transform: translateY(0);
        }
        
        .submit-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .divider {
            text-align: center;
            margin: var(--spacing-xl) 0;
            position: relative;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        
        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: var(--border-primary);
            opacity: 0.3;
        }
        
        .divider span {
            background: var(--bg-overlay);
            padding: 0 var(--spacing-md);
            position: relative;
            z-index: 1;
        }
        
        .google-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: var(--spacing-sm);
            width: 100%;
            height: var(--button-height);
            background: white;
            color: #333;
            border: 2px solid var(--border-primary);
            border-radius: var(--border-radius);
            text-decoration: none;
            font-weight: 500;
            font-size: 1rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        
        .google-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(0, 0, 0, 0.05), transparent);
            transition: left 0.5s;
        }
        
        .google-btn:hover::before {
            left: 100%;
        }
        
        .google-btn:hover {
            border-color: var(--accent-primary);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px var(--shadow-primary);
        }
        
        .google-btn:active {
            transform: translateY(0);
        }
        
        .google-icon {
            width: 20px;
            height: 20px;
        }
        
        .message {
            padding: var(--spacing-md);
            border-radius: var(--border-radius);
            margin-bottom: var(--spacing-lg);
            font-weight: 500;
            text-align: center;
            animation: slideIn 0.3s ease-out;
            border: 1px solid transparent;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .message.success {
            background: rgba(43, 176, 123, 0.1);
            color: var(--accent-success);
            border-color: rgba(43, 176, 123, 0.3);
        }
        
        .message.error {
            background: rgba(183, 28, 28, 0.1);
            color: var(--accent-danger);
            border-color: rgba(183, 28, 28, 0.3);
        }
        
        .loading {
            display: none;
            text-align: center;
            margin-top: var(--spacing-md);
        }
        
        .spinner {
            display: inline-block;
            width: 24px;
            height: 24px;
            border: 3px solid var(--bg-tertiary);
            border-top: 3px solid var(--accent-primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .benefits {
            margin: var(--spacing-lg) 0;
        }
        
        .benefit-item {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            padding: var(--spacing-sm);
            background: var(--bg-secondary);
            border-radius: var(--border-radius);
            margin-bottom: var(--spacing-sm);
            border: 1px solid var(--border-primary);
            transition: all 0.3s ease;
        }
        
        .benefit-item:hover {
            transform: translateX(4px);
            box-shadow: 0 4px 12px var(--shadow-primary);
        }
        
        .benefit-icon {
            font-size: 1.2rem;
            width: 24px;
            text-align: center;
        }
        
        .benefit-text {
            color: var(--text-secondary);
            font-weight: 500;
            font-size: 0.95rem;
        }
        
        /* Стили для новой формы регистрации */
        .register-info {
            text-align: center;
            margin-bottom: var(--spacing-xl);
        }
        
        .register-info h3 {
            color: var(--text-tertiary);
            font-size: 1.5rem;
            margin-bottom: var(--spacing-md);
            font-weight: 700;
        }
        
        .register-info p {
            color: var(--text-secondary);
            font-size: 1rem;
            line-height: 1.6;
            margin-bottom: var(--spacing-lg);
        }
        
        .register-steps {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-md);
            margin-bottom: var(--spacing-xl);
        }
        
        .step {
            display: flex;
            align-items: flex-start;
            gap: var(--spacing-md);
            padding: var(--spacing-md);
            background: var(--bg-secondary);
            border: 2px solid var(--border-primary);
            border-radius: var(--border-radius);
            transition: all 0.3s ease;
        }
        
        .step:hover {
            transform: translateX(4px);
            box-shadow: 0 4px 12px var(--shadow-primary);
        }
        
        .step-number {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary));
            color: var(--bg-secondary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.1rem;
            flex-shrink: 0;
        }
        
        .step-content h4 {
            color: var(--text-tertiary);
            font-size: 1.1rem;
            margin-bottom: var(--spacing-xs);
            font-weight: 600;
        }
        
        .step-content p {
            color: var(--text-secondary);
            font-size: 0.95rem;
            margin: 0;
            line-height: 1.5;
        }
        
        .register-google-btn {
            margin-top: var(--spacing-lg);
            font-size: 1.1rem;
            padding: var(--spacing-md) var(--spacing-lg);
        }
        
        .password-toggle {
            position: absolute;
            right: var(--spacing-md);
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-tertiary);
            cursor: pointer;
            font-size: 1.2rem;
            padding: 4px;
            border-radius: 4px;
            transition: all 0.3s ease;
        }
        
        .password-toggle:hover {
            color: var(--accent-secondary);
            background: rgba(166, 124, 82, 0.1);
        }
        
        .form-footer {
            text-align: center;
            margin-top: var(--spacing-xl);
            padding-top: var(--spacing-lg);
            border-top: 1px solid var(--border-primary);
            opacity: 0.3;
        }
        
        .form-footer a {
            color: var(--text-tertiary);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .form-footer a:hover {
            color: var(--accent-secondary);
            text-decoration: underline;
        }
        
        /* Мобильные адаптации */
        @media (max-width: 480px) {
            body {
                padding: var(--spacing-sm);
            }
            
            .login-container {
                padding: var(--spacing-lg);
                margin: 0;
            }
            
            .logo {
                font-size: 2rem;
            }
            
            .tab-btn {
                padding: var(--spacing-xs) var(--spacing-sm);
                font-size: 0.9rem;
            }
            
            input[type="text"],
            input[type="password"] {
                font-size: 16px; /* Предотвращает зум на iOS */
            }
        }
        
        /* Анимации для улучшения UX */
        .shake {
            animation: shake 0.5s ease-in-out;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        
        .pulse {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        /* Улучшенная доступность */
        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }
        
        /* Фокус для клавиатурной навигации */
        .tab-btn:focus,
        input:focus,
        .submit-btn:focus,
        .google-btn:focus {
            outline: 2px solid var(--accent-secondary);
            outline-offset: 2px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="header">
            <div class="logo">DnD Copilot</div>
            <div class="subtitle">Мастер подземелий в вашем кармане</div>
        </div>
        
        <div class="form-tabs">
            <button class="tab-btn active" onclick="switchTab('login')" aria-label="Переключиться на форму входа">
                Вход
            </button>
            <button class="tab-btn" onclick="switchTab('register')" aria-label="Переключиться на форму регистрации">
                Регистрация
            </button>
        </div>
        
        <div id="message"></div>
        
        <!-- Форма входа -->
        <div id="login-form" class="form-content active">
            <form id="loginForm" novalidate>
                <div class="form-group">
                    <label for="login-username">Имя пользователя</label>
                    <div class="input-wrapper">
                        <input type="text" id="login-username" name="username" required autocomplete="username" placeholder="Введите имя пользователя">
                        <span class="input-icon">👤</span>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="login-password">Пароль</label>
                    <div class="input-wrapper">
                        <input type="password" id="login-password" name="password" required autocomplete="current-password" placeholder="Введите пароль">
                        <button type="button" class="password-toggle" onclick="togglePassword('login-password')" aria-label="Показать/скрыть пароль">
                            👁️
                        </button>
                    </div>
                </div>
                
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <button type="submit" class="submit-btn" id="loginSubmit">
                    <span class="btn-text">Войти</span>
                    <span class="btn-loading" style="display: none;">
                        <div class="spinner"></div>
                    </span>
                </button>
            </form>
            
            <div class="divider">
                <span>или</span>
            </div>
            
            <a href="google-auth.php" class="google-btn" aria-label="Войти через Google">
                <svg class="google-icon" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                </svg>
                Войти через Google
            </a>
        </div>
        
        <!-- Форма регистрации -->
        <div id="register-form" class="form-content">
            <form id="registerForm" novalidate>
                <div class="form-group">
                    <label for="register-username">Имя пользователя</label>
                    <div class="input-wrapper">
                        <input type="text" id="register-username" name="username" required autocomplete="username" placeholder="Придумайте имя пользователя">
                        <span class="input-icon">👤</span>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="register-email">Email</label>
                    <div class="input-wrapper">
                        <input type="email" id="register-email" name="email" required autocomplete="email" placeholder="Введите ваш email">
                        <span class="input-icon">📧</span>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="register-password">Пароль</label>
                    <div class="input-wrapper">
                        <input type="password" id="register-password" name="password" required autocomplete="new-password" placeholder="Придумайте пароль">
                        <button type="button" class="password-toggle" onclick="togglePassword('register-password')" aria-label="Показать/скрыть пароль">
                            👁️
                        </button>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="register-password-confirm">Подтвердите пароль</label>
                    <div class="input-wrapper">
                        <input type="password" id="register-password-confirm" name="password_confirm" required autocomplete="new-password" placeholder="Повторите пароль">
                        <button type="button" class="password-toggle" onclick="togglePassword('register-password-confirm')" aria-label="Показать/скрыть пароль">
                            👁️
                        </button>
                    </div>
                </div>
                
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <button type="submit" class="submit-btn" id="registerSubmit">
                    <span class="btn-text">Зарегистрироваться</span>
                    <span class="btn-loading" style="display: none;">
                        <div class="spinner"></div>
                    </span>
                </button>
            </form>
            
            <div class="divider">
                <span>или</span>
            </div>
            
            <a href="google-auth.php" class="google-btn" aria-label="Зарегистрироваться через Google">
                <svg class="google-icon" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                </svg>
                Зарегистрироваться через Google
            </a>
        </div>
        
        <div class="loading" id="loading">
            <div class="spinner"></div>
        </div>
        
        <div class="form-footer">
            <a href="index.php">← Вернуться на главную</a>
        </div>
    </div>

    <script>
        // Переключение между вкладками
        function switchTab(tab) {
            // Убираем активный класс у всех кнопок и форм
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.form-content').forEach(form => form.classList.remove('active'));
            
            // Добавляем активный класс к выбранной вкладке
            if (tab === 'login') {
                document.querySelector('.tab-btn:first-child').classList.add('active');
                document.getElementById('login-form').classList.add('active');
                setTimeout(() => document.getElementById('login-username').focus(), 100);
            } else {
                document.querySelector('.tab-btn:last-child').classList.add('active');
                document.getElementById('register-form').classList.add('active');
                setTimeout(() => document.getElementById('register-username').focus(), 100);
            }
            
            // Очищаем сообщения
            document.getElementById('message').innerHTML = '';
        }
        
        // Показать/скрыть пароль
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const toggle = input.nextElementSibling;
            
            if (input.type === 'password') {
                input.type = 'text';
                toggle.textContent = '🙈';
            } else {
                input.type = 'password';
                toggle.textContent = '👁️';
            }
        }
        
        // Показать сообщение
        function showMessage(text, type) {
            const messageDiv = document.getElementById('message');
            messageDiv.innerHTML = `<div class="message ${type}">${text}</div>`;
            
            // Автоматически скрыть через 5 секунд
            setTimeout(() => {
                messageDiv.innerHTML = '';
            }, 5000);
        }
        
        // Показать/скрыть загрузку
        function showLoading(buttonId, show) {
            const button = document.getElementById(buttonId);
            const btnText = button.querySelector('.btn-text');
            const btnLoading = button.querySelector('.btn-loading');
            
            if (show) {
                button.disabled = true;
                btnText.style.display = 'none';
                btnLoading.style.display = 'inline-flex';
            } else {
                button.disabled = false;
                btnText.style.display = 'inline';
                btnLoading.style.display = 'none';
            }
        }
        
        // Валидация пароля
        function validatePassword(password) {
            const errors = [];
            if (password.length < 8) errors.push('Минимум 8 символов');
            if (!/[A-Z]/.test(password)) errors.push('Нужна заглавная буква');
            if (!/[a-z]/.test(password)) errors.push('Нужна строчная буква');
            if (!/\d/.test(password)) errors.push('Нужна цифра');
            return errors;
        }
        
        // Автофокус при загрузке страницы
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('login-username').focus();
            
            // Добавляем haptic feedback для мобильных устройств
            if ('vibrate' in navigator) {
                document.querySelectorAll('button, .google-btn').forEach(element => {
                    element.addEventListener('click', () => {
                        navigator.vibrate(50);
                    });
                });
            }
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
                document.getElementById('loginForm').classList.add('shake');
                setTimeout(() => document.getElementById('loginForm').classList.remove('shake'), 500);
                return;
            }
            
            showLoading('loginSubmit', true);
            
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
                showLoading('loginSubmit', false);
                if (data.success) {
                    showMessage(data.message, 'success');
                    setTimeout(() => {
                        window.location.href = 'index.php';
                    }, 1000);
                } else {
                    showMessage(data.message, 'error');
                    document.getElementById('login-password').focus();
                }
            })
            .catch(error => {
                showLoading('loginSubmit', false);
                showMessage('Ошибка соединения. Проверьте интернет.', 'error');
            });
        });
        
        // Обработка формы регистрации
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const username = document.getElementById('register-username').value.trim();
            const email = document.getElementById('register-email').value.trim();
            const password = document.getElementById('register-password').value;
            const passwordConfirm = document.getElementById('register-password-confirm').value;
            const csrfToken = document.querySelector('#registerForm input[name="csrf_token"]').value;
            
            if (!username || !email || !password || !passwordConfirm) {
                showMessage('Заполните все поля', 'error');
                document.getElementById('registerForm').classList.add('shake');
                setTimeout(() => document.getElementById('registerForm').classList.remove('shake'), 500);
                return;
            }
            
            if (password !== passwordConfirm) {
                showMessage('Пароли не совпадают', 'error');
                document.getElementById('register-password-confirm').focus();
                return;
            }
            
            const passwordErrors = validatePassword(password);
            if (passwordErrors.length > 0) {
                showMessage('Пароль не соответствует требованиям: ' + passwordErrors.join(', '), 'error');
                return;
            }
            
            showLoading('registerSubmit', true);
            
            const formData = new FormData();
            formData.append('action', 'register');
            formData.append('username', username);
            formData.append('email', email);
            formData.append('password', password);
            formData.append('csrf_token', csrfToken);
            
            fetch('users.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                showLoading('registerSubmit', false);
                if (data.success) {
                    showMessage(data.message, 'success');
                    setTimeout(() => {
                        window.location.href = 'index.php';
                    }, 1000);
                } else {
                    showMessage(data.message, 'error');
                }
            })
            .catch(error => {
                showLoading('registerSubmit', false);
                showMessage('Ошибка соединения. Проверьте интернет.', 'error');
            });
        });
        

        
        // Предотвращение двойного клика
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                const submitBtn = this.querySelector('.submit-btn');
                if (submitBtn.disabled) {
                    return false;
                }
            });
        });
        
        // Улучшенная обработка ошибок сети
        window.addEventListener('online', function() {
            showMessage('Соединение восстановлено', 'success');
        });
        
        window.addEventListener('offline', function() {
            showMessage('Нет соединения с интернетом', 'error');
        });
    </script>
</body>
</html>
