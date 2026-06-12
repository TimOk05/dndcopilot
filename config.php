<?php
return [
    'site_name' => 'D&D Copilot',
    'timezone' => 'Europe/Minsk',

    // Runtime data is stored as a SQLite file here. Keep this directory outside
    // public web access when you deploy without the provided .htaccess.
    'data_dir' => __DIR__ . '/.data',
    'database_path' => __DIR__ . '/.data/dnd-copilot.sqlite',

    'session_name' => 'dndcopilot_sid',
    'session_lifetime_days' => 14,

    // Email login settings.
    'login_code_ttl_minutes' => 10,
    'login_code_cooldown_minutes' => 2,
    'login_code_ip_cooldown_minutes' => 1,
    'login_code_digits' => 6,
    'login_code_max_attempts' => 5,

    // PHP mail() settings. Use a domain mailbox on production hosting.
    'mail_from' => 'D&D Copilot <no-reply@example.com>',
    'mail_subject' => 'Код входа в D&D Copilot',

    // Leave empty to allow any email, or list exact addresses.
    'allowed_emails' => [
        // 'dm@example.com',
    ],

    // Local-only helper when mail() is not configured. Keep false in production.
    'debug_show_login_code' => false,
    'local_login_code' => '111',

    // Redirect every non-local HTTP request to HTTPS.
    'force_https_non_local' => true,
    'hsts_max_age' => 31536000,
];
