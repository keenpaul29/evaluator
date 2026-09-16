#!/usr/bin/env php
<?php

$port = getenv('PORT') ?: '8080';

@touch(__DIR__ . '/database/database.sqlite');

passthru('php artisan migrate --force 2>/dev/null');
passthru('php artisan config:cache 2>/dev/null');

$publicDir = __DIR__ . '/public';

exec("exec php -S 0.0.0.0:{$port} -t " . escapeshellarg($publicDir));
