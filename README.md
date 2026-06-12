# D&D Copilot

Vanilla PHP, HTML, CSS and JavaScript tabletop assistant.

No NodeJS runtime, no frameworks, no Composer packages, no Docker.

Start locally:

```bash
php -S 127.0.0.1:8000 index.php
```

Configuration lives in `config.php`; private overrides can go into
`config.local.php`.

See [docs/php-monolith.md](docs/php-monolith.md) for login and deployment notes.
