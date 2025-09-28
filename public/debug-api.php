<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Отладка API генератора</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { border: 1px solid #ccc; margin: 10px 0; padding: 15px; }
        .result { background: #f0f0f0; padding: 10px; margin: 10px 0; }
        .error { background: #ffe6e6; color: red; }
        .success { background: #e6ffe6; color: green; }
        button { padding: 10px 20px; margin: 5px; cursor: pointer; }
    </style>
</head>
<body>
    <h1>Отладка API генератора персонажей</h1>
    
    <div class="test-section">
        <h2>Тест 1: Простой API</h2>
        <button onclick="testSimpleAPI()">Тест простого API</button>
        <div id="simple-result" class="result"></div>
    </div>
    
    <div class="test-section">
        <h2>Тест 2: Основной API</h2>
        <button onclick="testMainAPI()">Тест основного API</button>
        <div id="main-result" class="result"></div>
    </div>
    
    <div class="test-section">
        <h2>Тест 3: Получение рас и классов</h2>
        <button onclick="testRacesAndClasses()">Тест рас и классов</button>
        <div id="races-result" class="result"></div>
    </div>

    <script>
        async function testSimpleAPI() {
            const resultDiv = document.getElementById('simple-result');
            resultDiv.innerHTML = 'Загрузка...';
            
            try {
                const response = await fetch('api/test-character.php');
                const data = await response.json();
                
                if (data.success) {
                    resultDiv.className = 'result success';
                    resultDiv.innerHTML = '<h3>✅ Успешно!</h3><pre>' + JSON.stringify(data, null, 2) + '</pre>';
                } else {
                    resultDiv.className = 'result error';
                    resultDiv.innerHTML = '<h3>❌ Ошибка!</h3><pre>' + JSON.stringify(data, null, 2) + '</pre>';
                }
            } catch (error) {
                resultDiv.className = 'result error';
                resultDiv.innerHTML = '<h3>❌ Ошибка сети!</h3><pre>' + error.message + '</pre>';
            }
        }
        
        async function testMainAPI() {
            const resultDiv = document.getElementById('main-result');
            resultDiv.innerHTML = 'Загрузка...';
            
            try {
                const formData = new FormData();
                formData.append('race', 'human');
                formData.append('class', 'fighter');
                formData.append('level', '1');
                formData.append('gender', 'male');
                
                const response = await fetch('api/generate-characters.php', {
                    method: 'POST',
                    body: formData
                });
                
                const text = await response.text();
                resultDiv.innerHTML = '<h3>Ответ сервера:</h3><pre>' + text + '</pre>';
                
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        resultDiv.className = 'result success';
                        resultDiv.innerHTML = '<h3>✅ Успешно!</h3><pre>' + JSON.stringify(data, null, 2) + '</pre>';
                    } else {
                        resultDiv.className = 'result error';
                        resultDiv.innerHTML = '<h3>❌ Ошибка!</h3><pre>' + JSON.stringify(data, null, 2) + '</pre>';
                    }
                } catch (parseError) {
                    resultDiv.className = 'result error';
                    resultDiv.innerHTML = '<h3>❌ Не JSON ответ!</h3><pre>' + text + '</pre>';
                }
            } catch (error) {
                resultDiv.className = 'result error';
                resultDiv.innerHTML = '<h3>❌ Ошибка сети!</h3><pre>' + error.message + '</pre>';
            }
        }
        
        async function testRacesAndClasses() {
            const resultDiv = document.getElementById('races-result');
            resultDiv.innerHTML = 'Загрузка...';
            
            try {
                const [racesResponse, classesResponse] = await Promise.all([
                    fetch('api/generate-characters.php?action=races'),
                    fetch('api/generate-characters.php?action=classes')
                ]);
                
                const racesData = await racesResponse.json();
                const classesData = await classesResponse.json();
                
                resultDiv.className = 'result success';
                resultDiv.innerHTML = '<h3>✅ Расы и классы загружены!</h3>' +
                    '<h4>Расы:</h4><pre>' + JSON.stringify(racesData, null, 2) + '</pre>' +
                    '<h4>Классы:</h4><pre>' + JSON.stringify(classesData, null, 2) + '</pre>';
            } catch (error) {
                resultDiv.className = 'result error';
                resultDiv.innerHTML = '<h3>❌ Ошибка!</h3><pre>' + error.message + '</pre>';
            }
        }
    </script>
</body>
</html>
