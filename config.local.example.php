<?php
return [
    // Copy this file to config.local.php for private server overrides.
    'database_path' => __DIR__ . '/.data/dnd-copilot.sqlite',
    'mail_from' => 'D&D Copilot <no-reply@your-domain.example>',
    'login_code_ttl_minutes' => 10,
    'login_code_cooldown_minutes' => 2,
    'allowed_emails' => [],
    'debug_show_login_code' => false,
    'local_login_code' => '111',
    'force_https_non_local' => true,
    'hsts_max_age' => 31536000,
];
