<?php

require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "SANCTUM_STATEFUL_DOMAINS: " . implode(',', config('sanctum.stateful', [])) . "\n";
echo "SESSION_DOMAIN: " . config('session.domain') . "\n";
echo "CORS_ALLOWED_ORIGINS: " . implode(',', config('cors.allowed_origins', [])) . "\n";
echo "CORS_SUPPORTS_CREDENTIALS: " . (config('cors.supports_credentials') ? 'true' : 'false') . "\n";
echo "APP_URL: " . config('app.url') . "\n";
