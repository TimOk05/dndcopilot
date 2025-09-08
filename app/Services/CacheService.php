<?php
require_once __DIR__ . '/../../config/config.php';

class CacheService {
    private $cacheDir;
    private $defaultTtl;
    
    public function __construct($cacheDir = null, $defaultTtl = 3600) {
        $this->cacheDir = $cacheDir ?: __DIR__ . '/../../data/cache';
        $this->defaultTtl = $defaultTtl;
        
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    /**
     * Получение данных из кэша
     */
    public function get($key) {
        $filename = $this->getCacheFilename($key);
        
        if (!file_exists($filename)) {
            return null;
        }
        
        $data = file_get_contents($filename);
        $cached = json_decode($data, true);
        
        if (!$cached || !isset($cached['expires']) || !isset($cached['data'])) {
            return null;
        }
        
        // Проверяем срок действия
        if (time() > $cached['expires']) {
            unlink($filename);
            return null;
        }
        
        logMessage('Cache hit', 'DEBUG', ['key' => $key]);
        return $cached['data'];
    }
    
    /**
     * Сохранение данных в кэш
     */
    public function set($key, $data, $ttl = null) {
        $ttl = $ttl ?: $this->defaultTtl;
        $filename = $this->getCacheFilename($key);
        
        $cached = [
            'expires' => time() + $ttl,
            'data' => $data,
            'created' => time()
        ];
        
        $result = file_put_contents($filename, json_encode($cached), LOCK_EX);
        
        if ($result !== false) {
            logMessage('Cache set', 'DEBUG', ['key' => $key, 'ttl' => $ttl]);
            return true;
        }
        
        logMessage('Cache set failed', 'WARNING', ['key' => $key]);
        return false;
    }
    
    /**
     * Удаление данных из кэша
     */
    public function delete($key) {
        $filename = $this->getCacheFilename($key);
        
        if (file_exists($filename)) {
            $result = unlink($filename);
            logMessage('Cache deleted', 'DEBUG', ['key' => $key, 'success' => $result]);
            return $result;
        }
        
        return true;
    }
    
    /**
     * Очистка всего кэша
     */
    public function clear() {
        $files = glob($this->cacheDir . '/*.cache');
        $deleted = 0;
        
        foreach ($files as $file) {
            if (unlink($file)) {
                $deleted++;
            }
        }
        
        logMessage('Cache cleared', 'INFO', ['deleted_files' => $deleted]);
        return $deleted;
    }
    
    /**
     * Очистка устаревших записей
     */
    public function cleanup() {
        $files = glob($this->cacheDir . '/*.cache');
        $deleted = 0;
        
        foreach ($files as $file) {
            $data = file_get_contents($file);
            $cached = json_decode($data, true);
            
            if ($cached && isset($cached['expires']) && time() > $cached['expires']) {
                if (unlink($file)) {
                    $deleted++;
                }
            }
        }
        
        if ($deleted > 0) {
            logMessage('Cache cleanup completed', 'INFO', ['deleted_files' => $deleted]);
        }
        
        return $deleted;
    }
    
    /**
     * Получение статистики кэша
     */
    public function getStats() {
        $files = glob($this->cacheDir . '/*.cache');
        $totalSize = 0;
        $expiredCount = 0;
        $validCount = 0;
        
        foreach ($files as $file) {
            $totalSize += filesize($file);
            $data = file_get_contents($file);
            $cached = json_decode($data, true);
            
            if ($cached && isset($cached['expires'])) {
                if (time() > $cached['expires']) {
                    $expiredCount++;
                } else {
                    $validCount++;
                }
            }
        }
        
        return [
            'total_files' => count($files),
            'valid_files' => $validCount,
            'expired_files' => $expiredCount,
            'total_size' => $totalSize,
            'cache_dir' => $this->cacheDir
        ];
    }
    
    /**
     * Генерация имени файла кэша
     */
    private function getCacheFilename($key) {
        $hash = md5($key);
        return $this->cacheDir . '/' . $hash . '.cache';
    }
    
    /**
     * Проверка существования ключа в кэше
     */
    public function exists($key) {
        $filename = $this->getCacheFilename($key);
        
        if (!file_exists($filename)) {
            return false;
        }
        
        $data = file_get_contents($filename);
        $cached = json_decode($data, true);
        
        return $cached && isset($cached['expires']) && time() <= $cached['expires'];
    }
    
    /**
     * Получение времени истечения кэша
     */
    public function getExpiration($key) {
        $filename = $this->getCacheFilename($key);
        
        if (!file_exists($filename)) {
            return null;
        }
        
        $data = file_get_contents($filename);
        $cached = json_decode($data, true);
        
        return $cached['expires'] ?? null;
    }
    
    /**
     * Увеличение времени жизни кэша
     */
    public function extend($key, $additionalTtl) {
        $data = $this->get($key);
        
        if ($data !== null) {
            $currentExpiration = $this->getExpiration($key);
            $newTtl = ($currentExpiration - time()) + $additionalTtl;
            
            return $this->set($key, $data, $newTtl);
        }
        
        return false;
    }
    
    /**
     * Получение времени последней очистки кэша
     */
    public function getLastCleanup() {
        $cleanupFile = $this->cacheDir . '/.last_cleanup';
        if (file_exists($cleanupFile)) {
            return filemtime($cleanupFile);
        }
        return null;
    }
    
    /**
     * Установка времени последней очистки
     */
    private function setLastCleanup() {
        $cleanupFile = $this->cacheDir . '/.last_cleanup';
        touch($cleanupFile);
    }
    
    /**
     * Автоматическая очистка устаревших записей
     */
    public function autoCleanup() {
        $deleted = $this->cleanup();
        if ($deleted > 0) {
            $this->setLastCleanup();
        }
        return $deleted;
    }
    
    /**
     * Получение размера кэша в байтах
     */
    public function getCacheSize() {
        $files = glob($this->cacheDir . '/*.cache');
        $totalSize = 0;
        
        foreach ($files as $file) {
            $totalSize += filesize($file);
        }
        
        return $totalSize;
    }
    
    /**
     * Получение размера кэша в человекочитаемом формате
     */
    public function getCacheSizeFormatted() {
        $size = $this->getCacheSize();
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;
        
        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }
        
        return round($size, 2) . ' ' . $units[$unitIndex];
    }
    
    /**
     * Проверка здоровья кэша
     */
    public function getHealthStatus() {
        $stats = $this->getStats();
        $health = [
            'status' => 'healthy',
            'issues' => []
        ];
        
        // Проверяем доступность директории
        if (!is_dir($this->cacheDir)) {
            $health['status'] = 'unhealthy';
            $health['issues'][] = 'Cache directory not accessible';
        }
        
        // Проверяем права на запись
        if (!is_writable($this->cacheDir)) {
            $health['status'] = 'unhealthy';
            $health['issues'][] = 'Cache directory not writable';
        }
        
        // Проверяем количество устаревших файлов
        if ($stats['expired_files'] > $stats['valid_files'] * 0.5) {
            $health['status'] = 'warning';
            $health['issues'][] = 'Too many expired cache files';
        }
        
        // Проверяем размер кэша
        $cacheSize = $this->getCacheSize();
        if ($cacheSize > 100 * 1024 * 1024) { // 100MB
            $health['status'] = 'warning';
            $health['issues'][] = 'Cache size too large: ' . $this->getCacheSizeFormatted();
        }
        
        return $health;
    }
    
    /**
     * Получение информации о кэш файле
     */
    public function getCacheInfo($key) {
        $filename = $this->getCacheFilename($key);
        
        if (!file_exists($filename)) {
            return null;
        }
        
        $data = file_get_contents($filename);
        $cached = json_decode($data, true);
        
        if (!$cached) {
            return null;
        }
        
        return [
            'key' => $key,
            'filename' => $filename,
            'created' => $cached['created'] ?? null,
            'expires' => $cached['expires'] ?? null,
            'size' => filesize($filename),
            'is_expired' => isset($cached['expires']) && time() > $cached['expires']
        ];
    }
    
    /**
     * Массовое удаление ключей
     */
    public function deleteMultiple($keys) {
        $deleted = 0;
        foreach ($keys as $key) {
            if ($this->delete($key)) {
                $deleted++;
            }
        }
        return $deleted;
    }
    
    /**
     * Получение всех ключей кэша
     */
    public function getAllKeys() {
        $files = glob($this->cacheDir . '/*.cache');
        $keys = [];
        
        foreach ($files as $file) {
            $filename = basename($file, '.cache');
            // Восстанавливаем оригинальный ключ (это приблизительно)
            $keys[] = $filename;
        }
        
        return $keys;
    }
}
?>
