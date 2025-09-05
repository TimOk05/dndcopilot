<?php
/**
 * Localization Service - Система локализации для D&D Copilot
 * Поддерживает русский и английский языки
 */

class LocalizationService {
    private static $instance = null;
    private $current_language = 'ru';
    private $translations = [];
    private $available_languages = ['ru', 'en'];
    private $default_language = 'ru';
    
    private function __construct() {
        $this->loadLanguage();
        $this->loadTranslations();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Загрузка текущего языка из сессии или параметров
     */
    private function loadLanguage() {
        // Проверяем параметр в URL
        if (isset($_GET['lang']) && in_array($_GET['lang'], $this->available_languages)) {
            $this->current_language = $_GET['lang'];
            $_SESSION['language'] = $this->current_language;
        }
        // Проверяем сессию
        elseif (isset($_SESSION['language']) && in_array($_SESSION['language'], $this->available_languages)) {
            $this->current_language = $_SESSION['language'];
        }
        // Проверяем куки
        elseif (isset($_COOKIE['language']) && in_array($_COOKIE['language'], $this->available_languages)) {
            $this->current_language = $_COOKIE['language'];
            $_SESSION['language'] = $this->current_language;
        }
        // Используем язык браузера
        else {
            $this->current_language = $this->detectBrowserLanguage();
            $_SESSION['language'] = $this->current_language;
        }
        
        // Устанавливаем куку на 30 дней
        setcookie('language', $this->current_language, time() + (30 * 24 * 60 * 60), '/');
    }
    
    /**
     * Определение языка браузера
     */
    private function detectBrowserLanguage() {
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $languages = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
            foreach ($languages as $lang) {
                $lang = strtolower(trim(explode(';', $lang)[0]));
                if (in_array($lang, $this->available_languages)) {
                    return $lang;
                }
                // Проверяем префикс языка
                $prefix = substr($lang, 0, 2);
                if (in_array($prefix, $this->available_languages)) {
                    return $prefix;
                }
            }
        }
        return $this->default_language;
    }
    
    /**
     * Загрузка переводов
     */
    private function loadTranslations() {
        $translation_file = __DIR__ . "/translations/{$this->current_language}.php";
        if (file_exists($translation_file)) {
            $this->translations = include $translation_file;
        } else {
            // Fallback на английский
            $fallback_file = __DIR__ . "/translations/en.php";
            if (file_exists($fallback_file)) {
                $this->translations = include $fallback_file;
            }
        }
    }
    
    /**
     * Получение перевода
     */
    public function t($key, $params = []) {
        $translation = $this->translations[$key] ?? $key;
        
        // Замена параметров
        if (!empty($params)) {
            foreach ($params as $param_key => $param_value) {
                $translation = str_replace("{{$param_key}}", $param_value, $translation);
            }
        }
        
        return $translation;
    }
    
    /**
     * Получение текущего языка
     */
    public function getCurrentLanguage() {
        return $this->current_language;
    }
    
    /**
     * Получение доступных языков
     */
    public function getAvailableLanguages() {
        return $this->available_languages;
    }
    
    /**
     * Смена языка
     */
    public function setLanguage($language) {
        if (in_array($language, $this->available_languages)) {
            $this->current_language = $language;
            $_SESSION['language'] = $language;
            setcookie('language', $language, time() + (30 * 24 * 60 * 60), '/');
            $this->loadTranslations();
            return true;
        }
        return false;
    }
    
    /**
     * Получение названия языка
     */
    public function getLanguageName($code = null) {
        $code = $code ?? $this->current_language;
        $names = [
            'ru' => 'Русский',
            'en' => 'English'
        ];
        return $names[$code] ?? $code;
    }
    
    /**
     * Проверка, является ли текущий язык русским
     */
    public function isRussian() {
        return $this->current_language === 'ru';
    }
    
    /**
     * Проверка, является ли текущий язык английским
     */
    public function isEnglish() {
        return $this->current_language === 'en';
    }
    
    /**
     * Получение направления текста
     */
    public function getTextDirection() {
        return $this->current_language === 'ar' ? 'rtl' : 'ltr';
    }
    
    /**
     * Получение формата даты для текущего языка
     */
    public function getDateFormat() {
        return $this->current_language === 'ru' ? 'd.m.Y' : 'Y-m-d';
    }
    
    /**
     * Получение формата времени для текущего языка
     */
    public function getTimeFormat() {
        return $this->current_language === 'ru' ? 'H:i' : 'g:i A';
    }
}

// Глобальная функция для удобства
function t($key, $params = []) {
    return LocalizationService::getInstance()->t($key, $params);
}

// Глобальная функция для получения текущего языка
function getCurrentLanguage() {
    return LocalizationService::getInstance()->getCurrentLanguage();
}
?>
