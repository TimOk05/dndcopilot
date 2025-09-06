<?php
require_once '../app/Middleware/auth.php';

// Если пользователь уже авторизован, перенаправляем
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DnD Copilot - Вход</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 400px;
        }
        .tabs {
            display: flex;
            margin-bottom: 30px;
        }
        .tab {
            flex: 1;
            padding: 15px;
            text-align: center;
            background: #f5f5f5;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }
        .tab.active {
            background: #667eea;
            color: white;
        }
        .form {
            display: none;
        }
        .form.active {
            display: block;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            box-sizing: border-box;
        }
        input:focus {
            border-color: #667eea;
            outline: none;
        }
        button {
            width: 100%;
            padding: 15px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 10px;
        }
        button:hover {
            background: #5a6fd8;
        }
        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
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
    </style>
</head>
<body>
    <div class="container">
        <h1 style="text-align: center; margin-bottom: 30px;">DnD Copilot</h1>
        
        <div class="tabs">
            <button class="tab active" onclick="switchTab('login')">Вход</button>
            <button class="tab" onclick="switchTab('register')">Регистрация</button>
        </div>
        
        <div id="message"></div>
        
        <!-- Форма входа -->
        <form id="loginForm" class="form active">
                <div class="form-group">
                <label>Имя пользователя или Email:</label>
                <input type="text" name="username" required>
                </div>
                <div class="form-group">
                <label>Пароль:</label>
                <input type="password" name="password" required>
                    </div>
            <button type="submit">Войти</button>
            </form>
        
        <!-- Форма регистрации -->
        <form id="registerForm" class="form">
                <div class="form-group">
                <label>Имя пользователя:</label>
                <input type="text" name="username" required>
                </div>
                <div class="form-group">
                <label>Email:</label>
                <input type="email" name="email" required>
                </div>
                <div class="form-group">
                <label>Пароль:</label>
                <input type="password" name="password" required>
                    </div>
            <button type="submit">Зарегистрироваться</button>
            </form>
    </div>

    <script>
        function switchTab(tab) {
            // Убираем активный класс у всех вкладок и форм
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.form').forEach(f => f.classList.remove('active'));
            
            // Добавляем активный класс к выбранной вкладке
            if (tab === 'login') {
                document.querySelector('.tab:first-child').classList.add('active');
                document.getElementById('loginForm').classList.add('active');
            } else {
                document.querySelector('.tab:last-child').classList.add('active');
                document.getElementById('registerForm').classList.add('active');
            }
            
            // Очищаем сообщения
            document.getElementById('message').innerHTML = '';
        }
        
        function showMessage(text, type) {
            const messageDiv = document.getElementById('message');
            messageDiv.innerHTML = `<div class="message ${type}">${text}</div>`;
            
            setTimeout(() => {
                messageDiv.innerHTML = '';
            }, 5000);
        }
        
        // Обработка формы входа
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'login');
            
            fetch('api/users.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
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
                showMessage('Ошибка соединения', 'error');
            });
        });
        
        // Обработка формы регистрации
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'register');
            
            fetch('api/users.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage(data.message, 'success');
                    setTimeout(() => {
                        switchTab('login');
                    }, 1000);
                } else {
                    showMessage(data.message, 'error');
                }
            })
            .catch(error => {
                showMessage('Ошибка соединения', 'error');
            });
        });
    </script>
</body>
</html>
