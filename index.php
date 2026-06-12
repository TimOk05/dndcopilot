<?php
declare(strict_types=1);

$app_root = __DIR__;
$settings = load_settings($app_root);
$GLOBALS['app_root'] = $app_root;
$GLOBALS['settings'] = $settings;

prepare_runtime($settings);
enforce_https_for_public_hosts($settings);
start_user_session($settings);
send_security_headers();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = normalize_request_path(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');

if ($path === '/favicon.ico' || $path === '/favicon.svg') {
    serve_public_file(basename($path), false);
}

if (path_starts_with($path, '/public/')) {
    require_auth_for_static();
    serve_public_file(substr($path, strlen('/public/')), true);
}

if ($path === '/health') {
    send_json(['ok' => true, 'runtime' => 'php']);
}

if ($path === '/login') {
    if (is_authenticated()) {
        redirect('/');
    }
    render_login_page();
}

if ($path === '/auth/request-code') {
    require_method('POST');
    handle_request_code();
}

if ($path === '/auth/verify-code') {
    require_method('POST');
    handle_verify_code();
}

if ($path === '/auth/logout') {
    require_method('POST');
    handle_logout();
}

if ($path === '/api/me') {
    require_method('GET');
    require_auth_for_api();
    api_me();
}

if ($path === '/api/storage') {
    require_method('GET');
    require_auth_for_api();
    api_storage_index();
}

if (preg_match('#^/api/storage/([^/]+)$#', $path, $matches)) {
    require_auth_for_api();
    api_storage_item(rawurldecode($matches[1]), $method);
}

if ($path === '/api/ai/chat') {
    require_method('POST');
    require_auth_for_api();
    send_json(['error' => 'AI endpoint is not configured in the vanilla PHP build.'], 503);
}

if ($path === '/' || $path === '/app') {
    require_auth_for_page();
    render_app_page();
}

not_found();

function load_settings(string $app_root): array
{
    $defaults = [
        'site_name' => 'D&D Copilot',
        'timezone' => 'Europe/Minsk',
        'data_dir' => $app_root . DIRECTORY_SEPARATOR . '.data',
        'session_name' => 'dndcopilot_sid',
        'session_lifetime_days' => 14,
        'login_code_ttl_minutes' => 10,
        'login_code_cooldown_minutes' => 2,
        'login_code_ip_cooldown_minutes' => 1,
        'login_code_digits' => 6,
        'login_code_max_attempts' => 5,
        'mail_from' => 'D&D Copilot <no-reply@localhost>',
        'mail_subject' => 'Код входа в D&D Copilot',
        'allowed_emails' => [],
        'debug_show_login_code' => false,
        'local_login_code' => '111',
        'force_https_non_local' => true,
        'hsts_max_age' => 31536000,
    ];

    $config = [];
    $config_path = $app_root . DIRECTORY_SEPARATOR . 'config.php';
    if (is_file($config_path)) {
        $loaded = require $config_path;
        if (is_array($loaded)) {
            $config = $loaded;
        }
    }

    $local_path = $app_root . DIRECTORY_SEPARATOR . 'config.local.php';
    if (is_file($local_path)) {
        $loaded = require $local_path;
        if (is_array($loaded)) {
            $config = array_replace($config, $loaded);
        }
    }

    return array_replace($defaults, $config);
}

function prepare_runtime(array $settings): void
{
    date_default_timezone_set((string) $settings['timezone']);
    if (!is_dir((string) $settings['data_dir'])) {
        mkdir((string) $settings['data_dir'], 0775, true);
    }
}

function start_user_session(array $settings): void
{
    $lifetime = max(1, (int) $settings['session_lifetime_days']) * 86400;
    ini_set('session.gc_maxlifetime', (string) $lifetime);
    session_name((string) $settings['session_name']);
    session_set_cookie_params([
        'lifetime' => $lifetime,
        'path' => '/',
        'secure' => is_https_request(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function send_security_headers(): void
{
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: same-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    if (!is_local_auth_mode() && is_https_request()) {
        $max_age = max(0, (int) ($GLOBALS['settings']['hsts_max_age'] ?? 0));
        if ($max_age > 0) {
            header('Strict-Transport-Security: max-age=' . $max_age . '; includeSubDomains');
        }
    }
}

function normalize_request_path(string $path): string
{
    $path = '/' . ltrim($path, '/');
    if ($path !== '/') {
        $path = rtrim($path, '/');
    }
    return $path === '' ? '/' : $path;
}

function path_starts_with(string $value, string $prefix): bool
{
    return substr($value, 0, strlen($prefix)) === $prefix;
}

function is_https_request(): bool
{
    if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
        return true;
    }
    if ((string) ($_SERVER['SERVER_PORT'] ?? '') === '443') {
        return true;
    }
    if (strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https') {
        return true;
    }
    if (strtolower((string) ($_SERVER['HTTP_X_FORWARDED_SSL'] ?? '')) === 'on') {
        return true;
    }
    return strtolower((string) ($_SERVER['HTTP_FRONT_END_HTTPS'] ?? '')) === 'on';
}

function enforce_https_for_public_hosts(array $settings): void
{
    if (empty($settings['force_https_non_local']) || is_local_auth_mode() || is_https_request()) {
        return;
    }

    $host = (string) ($_SERVER['HTTP_HOST'] ?? '');
    $host = preg_replace('/[^A-Za-z0-9.\-:\[\]]/', '', $host);
    if ($host === '') {
        return;
    }

    $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
    $uri = str_replace(["\r", "\n"], '', $uri);
    if ($uri === '' || $uri[0] !== '/') {
        $uri = '/';
    }

    redirect('https://' . $host . $uri, 301);
}

function is_local_auth_mode(): bool
{
    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? ''));
    $host = trim($host);
    if (preg_match('/^\[([^\]]+)\](?::\d+)?$/', $host, $matches)) {
        $host = $matches[1];
    } else {
        $host = preg_replace('/:\d+$/', '', $host);
    }
    $local_hosts = ['localhost', '127.0.0.1', '::1', 'dndcopilot'];
    return in_array($host, $local_hosts, true);
}

function local_login_code(): string
{
    $code = preg_replace('/\D+/', '', (string) ($GLOBALS['settings']['local_login_code'] ?? '111'));
    return $code !== '' ? $code : '111';
}

function database_path(): string
{
    $settings = $GLOBALS['settings'];
    if (!empty($settings['database_path'])) {
        return (string) $settings['database_path'];
    }
    return rtrim((string) $settings['data_dir'], "/\\") . DIRECTORY_SEPARATOR . 'dnd-copilot.sqlite';
}

function legacy_json_database_path(): string
{
    $settings = $GLOBALS['settings'];
    return rtrim((string) $settings['data_dir'], "/\\") . DIRECTORY_SEPARATOR . 'dnd-copilot.json';
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    if (!extension_loaded('pdo_sqlite')) {
        http_response_code(500);
        render_simple_page('SQLite недоступен', 'В PHP должен быть включен модуль pdo_sqlite.');
        exit;
    }

    $path = database_path();
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }

    $pdo = new PDO('sqlite:' . $path);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON');
    $pdo->exec('PRAGMA busy_timeout = 5000');
    $pdo->exec('PRAGMA journal_mode = WAL');

    initialize_database_schema($pdo);
    migrate_legacy_json_database($pdo);
    return $pdo;
}

function initialize_database_schema(PDO $pdo): void
{
    $pdo->exec('CREATE TABLE IF NOT EXISTS users (
        id TEXT PRIMARY KEY,
        email TEXT NOT NULL UNIQUE COLLATE NOCASE,
        name TEXT NOT NULL,
        avatar_url TEXT,
        created_at TEXT NOT NULL,
        updated_at TEXT NOT NULL,
        last_login_at TEXT
    )');

    $pdo->exec('CREATE TABLE IF NOT EXISTS user_storage (
        user_id TEXT NOT NULL,
        storage_key TEXT NOT NULL,
        value_json TEXT NOT NULL,
        updated_at TEXT NOT NULL,
        PRIMARY KEY (user_id, storage_key),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )');

    $pdo->exec('CREATE TABLE IF NOT EXISTS auth_codes (
        email TEXT PRIMARY KEY COLLATE NOCASE,
        code_hash TEXT NOT NULL,
        expires_at INTEGER NOT NULL,
        cooldown_until INTEGER NOT NULL,
        attempts INTEGER NOT NULL DEFAULT 0,
        created_at TEXT NOT NULL
    )');

    $pdo->exec('CREATE TABLE IF NOT EXISTS request_locks (
        lock_key TEXT PRIMARY KEY,
        cooldown_until INTEGER NOT NULL,
        created_at TEXT NOT NULL
    )');

    $pdo->exec('CREATE TABLE IF NOT EXISTS migrations (
        migration_key TEXT PRIMARY KEY,
        applied_at TEXT NOT NULL
    )');

    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_user_storage_user ON user_storage(user_id)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_auth_codes_expires ON auth_codes(expires_at)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_request_locks_cooldown ON request_locks(cooldown_until)');
}

function migration_applied(PDO $pdo, string $key): bool
{
    $statement = $pdo->prepare('SELECT 1 FROM migrations WHERE migration_key = :key LIMIT 1');
    $statement->execute([':key' => $key]);
    return (bool) $statement->fetchColumn();
}

function mark_migration_applied(PDO $pdo, string $key): void
{
    $statement = $pdo->prepare('INSERT OR REPLACE INTO migrations (migration_key, applied_at) VALUES (:key, :applied_at)');
    $statement->execute([':key' => $key, ':applied_at' => now_iso()]);
}

function migrate_legacy_json_database(PDO $pdo): void
{
    $migration_key = 'legacy-json-dnd-copilot';
    $json_path = legacy_json_database_path();
    if (migration_applied($pdo, $migration_key) || !is_file($json_path)) {
        return;
    }

    $json = (string) file_get_contents($json_path);
    $json = preg_replace('/^\xEF\xBB\xBF/', '', $json);
    $decoded = json_decode($json, true);
    if (!is_array($decoded)) {
        return;
    }

    $pdo->beginTransaction();
    try {
        foreach (($decoded['users'] ?? []) as $user) {
            if (!is_array($user) || empty($user['id']) || empty($user['email'])) {
                continue;
            }
            upsert_user_row($pdo, [
                'id' => (string) $user['id'],
                'email' => normalize_email((string) $user['email']),
                'name' => (string) ($user['name'] ?? $user['email']),
                'avatar_url' => $user['avatar_url'] ?? null,
                'created_at' => (string) ($user['created_at'] ?? now_iso()),
                'updated_at' => (string) ($user['updated_at'] ?? now_iso()),
                'last_login_at' => $user['last_login_at'] ?? null,
            ]);
        }

        foreach (($decoded['userStorage'] ?? []) as $user_id => $bucket) {
            if (!is_array($bucket)) {
                continue;
            }
            foreach ($bucket as $key => $entry) {
                if (!validate_storage_key((string) $key) || !is_array($entry)) {
                    continue;
                }
                save_storage_value((string) $user_id, (string) $key, $entry['value'] ?? null, (string) ($entry['updated_at'] ?? now_iso()));
            }
        }

        foreach (($decoded['authCodes'] ?? []) as $email => $entry) {
            if (!is_valid_email((string) $email) || !is_array($entry) || empty($entry['code_hash'])) {
                continue;
            }
            save_auth_code(normalize_email((string) $email), [
                'code_hash' => (string) $entry['code_hash'],
                'expires_at' => (int) ($entry['expires_at'] ?? 0),
                'cooldown_until' => (int) ($entry['cooldown_until'] ?? 0),
                'attempts' => (int) ($entry['attempts'] ?? 0),
                'created_at' => (string) ($entry['created_at'] ?? now_iso()),
            ]);
        }

        foreach (($decoded['requestLocks'] ?? []) as $key => $entry) {
            if (!is_array($entry)) {
                continue;
            }
            save_request_lock((string) $key, [
                'cooldown_until' => (int) ($entry['cooldown_until'] ?? 0),
                'created_at' => (string) ($entry['created_at'] ?? now_iso()),
            ]);
        }

        mark_migration_applied($pdo, $migration_key);
        $pdo->commit();
    } catch (Throwable $error) {
        $pdo->rollBack();
        throw $error;
    }

    @rename($json_path, $json_path . '.migrated-' . date('YmdHis'));
}

function prune_expired_auth(): void
{
    $pdo = db();
    $now = time();
    $statement = $pdo->prepare('DELETE FROM auth_codes WHERE expires_at <= :now');
    $statement->execute([':now' => $now]);
    $statement = $pdo->prepare('DELETE FROM request_locks WHERE cooldown_until <= :now');
    $statement->execute([':now' => $now]);
}

function now_iso(): string
{
    return date('c');
}

function normalize_email(string $email): string
{
    return strtolower(trim($email));
}

function is_valid_email(string $email): bool
{
    return $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function is_email_allowed(string $email): bool
{
    if (is_local_auth_mode()) {
        return true;
    }

    $allowed = $GLOBALS['settings']['allowed_emails'];
    if (!is_array($allowed) || count($allowed) === 0) {
        return true;
    }

    $allowed = array_map('normalize_email', $allowed);
    return in_array(normalize_email($email), $allowed, true);
}

function find_user_by_email(string $email): ?array
{
    $statement = db()->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
    $statement->execute([':email' => normalize_email($email)]);
    $user = $statement->fetch();
    return is_array($user) ? $user : null;
}

function upsert_user_row(PDO $pdo, array $user): void
{
    $statement = $pdo->prepare('INSERT INTO users
        (id, email, name, avatar_url, created_at, updated_at, last_login_at)
        VALUES (:id, :email, :name, :avatar_url, :created_at, :updated_at, :last_login_at)
        ON CONFLICT(email) DO UPDATE SET
            name = excluded.name,
            avatar_url = excluded.avatar_url,
            updated_at = excluded.updated_at,
            last_login_at = excluded.last_login_at');

    $statement->execute([
        ':id' => $user['id'],
        ':email' => normalize_email((string) $user['email']),
        ':name' => (string) ($user['name'] ?? $user['email']),
        ':avatar_url' => $user['avatar_url'] ?? null,
        ':created_at' => (string) ($user['created_at'] ?? now_iso()),
        ':updated_at' => (string) ($user['updated_at'] ?? now_iso()),
        ':last_login_at' => $user['last_login_at'] ?? null,
    ]);
}

function upsert_user_by_email(string $email): array
{
    $email = normalize_email($email);
    $timestamp = now_iso();
    $existing = find_user_by_email($email);

    if ($existing) {
        $statement = db()->prepare('UPDATE users
            SET name = COALESCE(NULLIF(name, \'\'), :email), updated_at = :updated_at, last_login_at = :last_login_at
            WHERE email = :email');
        $statement->execute([
            ':email' => $email,
            ':updated_at' => $timestamp,
            ':last_login_at' => $timestamp,
        ]);
        return find_user_by_email($email) ?: $existing;
    }

    $user = [
        'id' => 'u_' . bin2hex(random_bytes(16)),
        'email' => $email,
        'name' => $email,
        'avatar_url' => null,
        'created_at' => $timestamp,
        'updated_at' => $timestamp,
        'last_login_at' => $timestamp,
    ];
    upsert_user_row(db(), $user);
    return $user;
}

function get_auth_code(string $email): ?array
{
    $statement = db()->prepare('SELECT * FROM auth_codes WHERE email = :email LIMIT 1');
    $statement->execute([':email' => normalize_email($email)]);
    $entry = $statement->fetch();
    return is_array($entry) ? $entry : null;
}

function save_auth_code(string $email, array $entry): void
{
    $statement = db()->prepare('INSERT INTO auth_codes
        (email, code_hash, expires_at, cooldown_until, attempts, created_at)
        VALUES (:email, :code_hash, :expires_at, :cooldown_until, :attempts, :created_at)
        ON CONFLICT(email) DO UPDATE SET
            code_hash = excluded.code_hash,
            expires_at = excluded.expires_at,
            cooldown_until = excluded.cooldown_until,
            attempts = excluded.attempts,
            created_at = excluded.created_at');

    $statement->execute([
        ':email' => normalize_email($email),
        ':code_hash' => (string) $entry['code_hash'],
        ':expires_at' => (int) $entry['expires_at'],
        ':cooldown_until' => (int) $entry['cooldown_until'],
        ':attempts' => (int) ($entry['attempts'] ?? 0),
        ':created_at' => (string) ($entry['created_at'] ?? now_iso()),
    ]);
}

function delete_auth_code(string $email): void
{
    $statement = db()->prepare('DELETE FROM auth_codes WHERE email = :email');
    $statement->execute([':email' => normalize_email($email)]);
}

function increment_auth_attempts(string $email): void
{
    $statement = db()->prepare('UPDATE auth_codes SET attempts = attempts + 1 WHERE email = :email');
    $statement->execute([':email' => normalize_email($email)]);
}

function get_request_lock(string $key): ?array
{
    $statement = db()->prepare('SELECT * FROM request_locks WHERE lock_key = :key LIMIT 1');
    $statement->execute([':key' => $key]);
    $entry = $statement->fetch();
    return is_array($entry) ? $entry : null;
}

function save_request_lock(string $key, array $entry): void
{
    $statement = db()->prepare('INSERT INTO request_locks
        (lock_key, cooldown_until, created_at)
        VALUES (:key, :cooldown_until, :created_at)
        ON CONFLICT(lock_key) DO UPDATE SET
            cooldown_until = excluded.cooldown_until,
            created_at = excluded.created_at');
    $statement->execute([
        ':key' => $key,
        ':cooldown_until' => (int) $entry['cooldown_until'],
        ':created_at' => (string) ($entry['created_at'] ?? now_iso()),
    ]);
}

function encode_storage_value($value): string
{
    $encoded = json_encode($value, JSON_UNESCAPED_UNICODE);
    return $encoded === false ? 'null' : $encoded;
}

function decode_storage_value(string $value)
{
    $decoded = json_decode($value, true);
    return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
}

function save_storage_value(string $user_id, string $key, $value, ?string $updated_at = null): void
{
    $statement = db()->prepare('INSERT INTO user_storage
        (user_id, storage_key, value_json, updated_at)
        VALUES (:user_id, :storage_key, :value_json, :updated_at)
        ON CONFLICT(user_id, storage_key) DO UPDATE SET
            value_json = excluded.value_json,
            updated_at = excluded.updated_at');

    $statement->execute([
        ':user_id' => $user_id,
        ':storage_key' => $key,
        ':value_json' => encode_storage_value($value),
        ':updated_at' => $updated_at ?: now_iso(),
    ]);
}

function current_user(): ?array
{
    if (empty($_SESSION['user_id']) || empty($_SESSION['user_email'])) {
        return null;
    }

    return [
        'id' => (string) $_SESSION['user_id'],
        'email' => (string) $_SESSION['user_email'],
        'name' => (string) ($_SESSION['user_name'] ?? $_SESSION['user_email']),
        'avatar_url' => null,
    ];
}

function is_authenticated(): bool
{
    return current_user() !== null;
}

function require_auth_for_page(): void
{
    if (!is_authenticated()) {
        redirect('/login');
    }
}

function require_auth_for_api(): void
{
    if (!is_authenticated()) {
        send_json(['error' => 'Authentication required.'], 401);
    }
}

function require_auth_for_static(): void
{
    if (!is_authenticated()) {
        http_response_code(401);
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'Authentication required.';
        exit;
    }
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return (string) $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    $expected = (string) ($_SESSION['csrf_token'] ?? '');
    $actual = (string) ($_POST['csrf_token'] ?? '');
    if ($expected === '' || $actual === '' || !hash_equals($expected, $actual)) {
        http_response_code(400);
        render_simple_page('Ошибка формы', 'Обнови страницу и попробуй еще раз.');
        exit;
    }
}

function add_flash(string $type, string $message): void
{
    $_SESSION['flashes'][] = ['type' => $type, 'message' => $message];
}

function consume_flashes(): array
{
    $flashes = $_SESSION['flashes'] ?? [];
    unset($_SESSION['flashes']);
    return is_array($flashes) ? $flashes : [];
}

function generate_login_code(int $digits): string
{
    $digits = max(4, min(10, $digits));
    $min = (int) pow(10, $digits - 1);
    $max = (int) pow(10, $digits) - 1;
    return (string) random_int($min, $max);
}

function client_ip_key(): string
{
    $ip = (string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown');
    $ip = trim(explode(',', $ip)[0]);
    return hash('sha256', $ip);
}

function send_login_mail(string $email, string $code): bool
{
    $settings = $GLOBALS['settings'];
    $ttl = (int) $settings['login_code_ttl_minutes'];
    $subject = '=?UTF-8?B?' . base64_encode((string) $settings['mail_subject']) . '?=';
    $from = trim(str_replace(["\r", "\n"], '', (string) $settings['mail_from']));
    $message = "Ваш код входа в D&D Copilot: {$code}\n\n";
    $message .= "Код действует {$ttl} минут.\n";
    $message .= "Если вы не запрашивали вход, просто проигнорируйте это письмо.\n";
    $headers = [
        'From: ' . $from,
        'Content-Type: text/plain; charset=UTF-8',
        'X-Mailer: PHP/' . phpversion(),
    ];

    return @mail($email, $subject, $message, implode("\r\n", $headers));
}

function handle_request_code(): void
{
    verify_csrf();

    $settings = $GLOBALS['settings'];
    $local_mode = is_local_auth_mode();
    $email = normalize_email((string) ($_POST['email'] ?? ''));
    if (!is_valid_email($email)) {
        add_flash('error', 'Введи корректный email.');
        redirect('/login');
    }

    if (!is_email_allowed($email)) {
        add_flash('error', 'Для этого email вход закрыт настройками сайта.');
        redirect('/login');
    }

    prune_expired_auth();
    $now = time();
    $entry = get_auth_code($email);
    if (!$local_mode && is_array($entry) && (int) ($entry['cooldown_until'] ?? 0) > $now) {
        $_SESSION['pending_email'] = $email;
        $left = max(1, (int) ceil(((int) $entry['cooldown_until'] - $now) / 60));
        add_flash('error', "Новый код для этого email можно запросить примерно через {$left} мин.");
        redirect('/login');
    }

    $ip_key = client_ip_key();
    $ip_entry = get_request_lock($ip_key);
    if (!$local_mode && is_array($ip_entry) && (int) ($ip_entry['cooldown_until'] ?? 0) > $now) {
        $left = max(1, (int) ceil(((int) $ip_entry['cooldown_until'] - $now) / 60));
        add_flash('error', "Слишком часто. Попробуй снова примерно через {$left} мин.");
        redirect('/login');
    }

    $code = $local_mode ? local_login_code() : generate_login_code((int) $settings['login_code_digits']);
    $debug = !empty($settings['debug_show_login_code']);
    $sent = $local_mode || send_login_mail($email, $code);
    if (!$sent && !$debug) {
        add_flash('error', 'Не удалось отправить письмо через PHP mail(). Проверь настройки почты на сервере.');
        redirect('/login');
    }

    save_auth_code($email, [
        'code_hash' => password_hash($code, PASSWORD_DEFAULT),
        'expires_at' => $now + max(1, (int) $settings['login_code_ttl_minutes']) * 60,
        'cooldown_until' => $local_mode ? $now : $now + max(0, (int) $settings['login_code_cooldown_minutes']) * 60,
        'attempts' => 0,
        'created_at' => now_iso(),
    ]);
    if (!$local_mode) {
        save_request_lock($ip_key, [
            'cooldown_until' => $now + max(0, (int) $settings['login_code_ip_cooldown_minutes']) * 60,
            'created_at' => now_iso(),
        ]);
    }

    $_SESSION['pending_email'] = $email;
    add_flash('success', $local_mode
        ? 'Локальный режим: письмо не отправлялось, код уже подставлен ниже.'
        : 'Код отправлен. Проверь почту и введи код ниже.'
    );
    if ($debug) {
        add_flash('success', "Локальный debug-код: {$code}");
    }
    redirect('/login');
}

function handle_verify_code(): void
{
    verify_csrf();

    $settings = $GLOBALS['settings'];
    $local_mode = is_local_auth_mode();
    $email = normalize_email((string) ($_POST['email'] ?? $_SESSION['pending_email'] ?? ''));
    $code = preg_replace('/\D+/', '', (string) ($_POST['code'] ?? ''));
    if (!is_valid_email($email) || $code === '') {
        add_flash('error', 'Укажи email и код из письма.');
        redirect('/login');
    }

    prune_expired_auth();
    if ($local_mode && hash_equals(local_login_code(), $code)) {
        $user = upsert_user_by_email($email);
        delete_auth_code($email);

        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['name'];
        unset($_SESSION['pending_email']);
        redirect('/');
    }

    $entry = get_auth_code($email);
    if (!is_array($entry)) {
        unset($_SESSION['pending_email']);
        add_flash('error', 'Код истек или еще не был запрошен. Отправь новый код.');
        redirect('/login');
    }

    $attempts = (int) ($entry['attempts'] ?? 0);
    $max_attempts = max(1, (int) $settings['login_code_max_attempts']);
    if ($attempts >= $max_attempts) {
        delete_auth_code($email);
        unset($_SESSION['pending_email']);
        add_flash('error', 'Слишком много неверных попыток. Запроси новый код.');
        redirect('/login');
    }

    if (!password_verify($code, (string) ($entry['code_hash'] ?? ''))) {
        increment_auth_attempts($email);
        $left = max(0, $max_attempts - $attempts - 1);
        add_flash('error', "Неверный код. Осталось попыток: {$left}.");
        $_SESSION['pending_email'] = $email;
        redirect('/login');
    }

    $user = upsert_user_by_email($email);
    delete_auth_code($email);

    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_name'] = $user['name'];
    unset($_SESSION['pending_email']);
    redirect('/');
}

function handle_logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', (bool) $params['secure'], (bool) $params['httponly']);
    }
    session_destroy();
    send_json(['ok' => true]);
}

function api_me(): void
{
    $user = current_user();
    send_json([
        'id' => $user['id'],
        'email' => $user['email'],
        'name' => $user['name'],
        'avatarUrl' => null,
    ]);
}

function validate_storage_key(string $key): bool
{
    return preg_match('/^dnd-[a-z0-9-]{1,80}$/i', $key) === 1;
}

function api_storage_index(): void
{
    $user = current_user();
    $statement = db()->prepare('SELECT storage_key, value_json, updated_at
        FROM user_storage
        WHERE user_id = :user_id
        ORDER BY storage_key COLLATE NOCASE ASC');
    $statement->execute([':user_id' => $user['id']]);

    $entries = [];
    foreach ($statement->fetchAll() as $entry) {
        $entries[] = [
            'key' => (string) $entry['storage_key'],
            'value' => decode_storage_value((string) $entry['value_json']),
            'updatedAt' => $entry['updated_at'],
        ];
    }
    send_json(['entries' => $entries]);
}

function api_storage_item(string $key, string $method): void
{
    if (!validate_storage_key($key)) {
        send_json(['error' => 'Invalid storage key.'], 400);
    }

    $user = current_user();

    if ($method === 'GET') {
        $statement = db()->prepare('SELECT value_json FROM user_storage
            WHERE user_id = :user_id AND storage_key = :storage_key
            LIMIT 1');
        $statement->execute([
            ':user_id' => $user['id'],
            ':storage_key' => $key,
        ]);
        $raw = $statement->fetchColumn();
        $value = is_string($raw) ? decode_storage_value($raw) : null;
        send_json(['key' => $key, 'value' => $value]);
    }

    if ($method === 'PUT') {
        $body = read_json_body();
        save_storage_value($user['id'], $key, $body['value'] ?? null);
        send_json(['ok' => true]);
    }

    if ($method === 'DELETE') {
        $statement = db()->prepare('DELETE FROM user_storage
            WHERE user_id = :user_id AND storage_key = :storage_key');
        $statement->execute([
            ':user_id' => $user['id'],
            ':storage_key' => $key,
        ]);
        send_json(['ok' => true]);
    }

    method_not_allowed(['GET', 'PUT', 'DELETE']);
}

function read_json_body(): array
{
    $raw = (string) file_get_contents('php://input');
    if ($raw === '') {
        return [];
    }
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        send_json(['error' => 'Invalid JSON body.'], 400);
    }
    return $decoded;
}

function render_login_page(): void
{
    $settings = $GLOBALS['settings'];
    $site = escape_html((string) $settings['site_name']);
    $pending_email = normalize_email((string) ($_SESSION['pending_email'] ?? ''));
    $flashes = consume_flashes();
    $csrf = escape_html(csrf_token());
    $email_value = escape_html($pending_email);
    $ttl = (int) $settings['login_code_ttl_minutes'];
    $cooldown = (int) $settings['login_code_cooldown_minutes'];
    $local_mode = is_local_auth_mode();
    $local_code_value = $local_mode ? ' value="' . escape_html(local_login_code()) . '"' : '';

    header('Content-Type: text/html; charset=UTF-8');
    echo '<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#2a1710">
  <title>' . $site . ' - вход</title>
  <style>
    :root { color-scheme: dark; font-family: Inter, "Segoe UI", Arial, sans-serif; }
    * { box-sizing: border-box; }
    body {
      margin: 0;
      min-height: 100vh;
      display: grid;
      place-items: center;
      padding: 24px;
      color: #fff7e8;
      background: radial-gradient(circle at 30% 20%, rgba(244, 172, 75, .26), transparent 30%), #120a12;
    }
    main {
      width: min(440px, 100%);
      padding: 28px;
      border: 1px solid rgba(242,184,106,.28);
      border-radius: 8px;
      background: rgba(35,20,13,.82);
      box-shadow: 0 24px 70px rgba(0,0,0,.48);
    }
    h1 { margin: 0 0 8px; font-size: 30px; letter-spacing: 0; }
    p { margin: 0 0 20px; color: #d7bea0; line-height: 1.5; }
    form { display: grid; gap: 14px; margin-top: 18px; }
    label { display: grid; gap: 7px; color: #f6ddb7; font-weight: 700; }
    input {
      width: 100%;
      min-height: 46px;
      border: 1px solid rgba(242,184,106,.34);
      border-radius: 8px;
      padding: 0 13px;
      color: #fff7e8;
      background: rgba(14, 9, 13, .76);
      font: inherit;
    }
    button {
      min-height: 46px;
      border: 0;
      border-radius: 8px;
      padding: 0 18px;
      color: #130b06;
      background: linear-gradient(180deg, #fff0bd, #d9964a);
      font: inherit;
      font-weight: 800;
      cursor: pointer;
    }
    .muted { margin-top: 16px; font-size: 14px; color: #bca78d; }
    .flash {
      margin: 12px 0;
      padding: 12px 13px;
      border-radius: 8px;
      line-height: 1.45;
      border: 1px solid rgba(255,255,255,.16);
      background: rgba(255,255,255,.08);
    }
    .flash.error { border-color: rgba(255,120,96,.46); color: #ffe2da; background: rgba(172,48,38,.24); }
    .flash.success { border-color: rgba(149,220,146,.4); color: #ddffd8; background: rgba(48,128,62,.2); }
    .secondary { color: #f9dfb5; background: rgba(255,255,255,.1); border: 1px solid rgba(242,184,106,.24); }
  </style>
</head>
<body>
  <main>
    <h1>' . $site . '</h1>
    <p>' . ($local_mode
        ? 'Локальный режим: можно указать любой email, письмо не отправляется, код входа подставляется автоматически.'
        : 'Вход и регистрация по email. Мы отправим одноразовый код, который действует ' . $ttl . ' мин.; повторная отправка доступна через ' . $cooldown . ' мин.'
    ) . '</p>';

    foreach ($flashes as $flash) {
        $type = escape_html((string) ($flash['type'] ?? ''));
        $message = escape_html((string) ($flash['message'] ?? ''));
        echo '<div class="flash ' . $type . '">' . $message . '</div>';
    }

    if ($pending_email !== '') {
        echo '<form method="post" action="/auth/verify-code">
      <input type="hidden" name="csrf_token" value="' . $csrf . '">
      <input type="hidden" name="email" value="' . $email_value . '">
      <label>
        Код из письма
        <input name="code" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]*" maxlength="10"' . $local_code_value . ' autofocus required>
      </label>
      <button type="submit">Войти</button>
    </form>
    <form method="post" action="/auth/request-code">
      <input type="hidden" name="csrf_token" value="' . $csrf . '">
      <label>
        Email
        <input type="email" name="email" value="' . $email_value . '" autocomplete="email" required>
      </label>
      <button class="secondary" type="submit">Отправить новый код</button>
    </form>';
    } else {
        echo '<form method="post" action="/auth/request-code">
      <input type="hidden" name="csrf_token" value="' . $csrf . '">
      <label>
        Email
        <input type="email" name="email" autocomplete="email" autofocus required>
      </label>
      <button type="submit">Получить код</button>
    </form>';
    }

    echo '<p class="muted">Письмо отправляется стандартной функцией PHP <code>mail()</code>, поэтому на хостинге должен быть настроен почтовый transport.</p>
  </main>
</body>
</html>';
    exit;
}

function render_app_page(): void
{
    $template = $GLOBALS['app_root'] . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'app.html';
    if (!is_file($template)) {
        http_response_code(500);
        render_simple_page('Ошибка', 'Шаблон приложения не найден.');
        exit;
    }

    $html = (string) file_get_contents($template);
    if (strpos($html, '<base ') === false) {
        $html = str_replace('<head>', '<head>' . "\n  " . '<base href="/public/">', $html);
    }
    header('Content-Type: text/html; charset=UTF-8');
    header('Cache-Control: no-store');
    echo $html;
    exit;
}

function render_simple_page(string $title, string $message): void
{
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>'
        . escape_html($title)
        . '</title><style>body{margin:0;min-height:100vh;display:grid;place-items:center;padding:24px;background:#120a12;color:#fff7e8;font-family:Arial,sans-serif}main{max-width:520px}a{color:#ffd086}</style></head><body><main><h1>'
        . escape_html($title)
        . '</h1><p>'
        . escape_html($message)
        . '</p><p><a href="/login">Вернуться ко входу</a></p></main></body></html>';
}

function serve_public_file(string $relative, bool $cache): void
{
    $relative = str_replace('\\', '/', ltrim($relative, '/'));
    if ($relative === '' || strpos($relative, '..') !== false) {
        not_found();
    }

    $public_dir = realpath($GLOBALS['app_root'] . DIRECTORY_SEPARATOR . 'public');
    $full_path = realpath($public_dir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative));
    if (!$public_dir || !$full_path || strpos($full_path, $public_dir . DIRECTORY_SEPARATOR) !== 0 || !is_file($full_path)) {
        not_found();
    }

    header('Content-Type: ' . content_type_for_file($full_path));
    header('Content-Length: ' . filesize($full_path));
    header('Cache-Control: ' . ($cache ? 'private, max-age=86400' : 'no-store'));
    readfile($full_path);
    exit;
}

function content_type_for_file(string $path): string
{
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $types = [
        'css' => 'text/css; charset=UTF-8',
        'js' => 'application/javascript; charset=UTF-8',
        'json' => 'application/json; charset=UTF-8',
        'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'webp' => 'image/webp',
        'mp3' => 'audio/mpeg',
        'html' => 'text/html; charset=UTF-8',
        'txt' => 'text/plain; charset=UTF-8',
    ];
    return $types[$ext] ?? 'application/octet-stream';
}

function require_method(string $expected): void
{
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if ($method !== $expected) {
        method_not_allowed([$expected]);
    }
}

function method_not_allowed(array $allowed): void
{
    header('Allow: ' . implode(', ', $allowed));
    if (path_starts_with(normalize_request_path(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/'), '/api/')) {
        send_json(['error' => 'Method not allowed.'], 405);
    }
    http_response_code(405);
    render_simple_page('Метод не поддерживается', 'Этот адрес не принимает такой HTTP-метод.');
    exit;
}

function not_found(): void
{
    $path = normalize_request_path(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
    if (path_starts_with($path, '/api/')) {
        send_json(['error' => 'Not found.'], 404);
    }
    http_response_code(404);
    render_simple_page('Страница не найдена', 'Такого адреса в приложении нет.');
    exit;
}

function send_json(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function redirect(string $target, int $status = 302): void
{
    header('Location: ' . str_replace(["\r", "\n"], '', $target), true, $status);
    exit;
}

function escape_html(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
