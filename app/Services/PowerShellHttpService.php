<?php
// Сервис для HTTP запросов через PowerShell
class PowerShellHttpService {
    private $temp_dir;
    
    public function __construct() {
        $this->temp_dir = sys_get_temp_dir();
    }
    
    /**
     * Выполнение HTTP GET запроса через PowerShell
     */
    public function get($url, $timeout = 30) {
        if (!function_exists('exec')) {
            throw new Exception('exec функция недоступна');
        }
        
        if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
            throw new Exception('PowerShell доступен только на Windows');
        }
        
        $temp_file = tempnam($this->temp_dir, 'ps_http_');
        
        try {
            // Создаем PowerShell команду для загрузки данных
            $ps_command = sprintf(
                'powershell -Command "Invoke-WebRequest -Uri \'%s\' -OutFile \'%s\'"',
                $url,
                $temp_file
            );
            
            $output = [];
            $return_code = 0;
            exec($ps_command, $output, $return_code);
            
            if ($return_code !== 0) {
                throw new Exception('PowerShell команда завершилась с ошибкой: ' . implode(' ', $output));
            }
            
            if (!file_exists($temp_file)) {
                throw new Exception('PowerShell не создал файл ответа');
            }
            
            $content = file_get_contents($temp_file);
            if ($content === false) {
                throw new Exception('Не удалось прочитать файл ответа');
            }
            
            // Парсим JSON
            $data = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Ошибка парсинга JSON: ' . json_last_error_msg());
            }
            
            return $data;
            
        } finally {
            // Удаляем временный файл
            if (file_exists($temp_file)) {
                unlink($temp_file);
            }
        }
    }
    
    /**
     * Проверка доступности PowerShell
     */
    public function isAvailable() {
        if (!function_exists('exec')) {
            return false;
        }
        
        if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
            return false;
        }
        
        $output = [];
        $return_code = 0;
        exec('powershell -Command "Write-Output \'TEST\'"', $output, $return_code);
        
        return $return_code === 0 && !empty($output) && $output[0] === 'TEST';
    }
    
    /**
     * Тест подключения к API
     */
    public function testConnection($url = 'https://www.dnd5eapi.co/api/monsters') {
        try {
            $data = $this->get($url);
            return [
                'success' => true,
                'message' => 'PowerShell HTTP работает корректно',
                'data' => $data
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'PowerShell HTTP не работает: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ];
        }
    }
}
?>
