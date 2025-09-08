<?php
// debug-socialite.php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';

echo "=== Socialite Debug ===\n\n";

// چک کردن نصب Socialite
echo "1. Checking Socialite installation:\n";
if (class_exists('Laravel\Socialite\SocialiteServiceProvider')) {
    echo "✅ SocialiteServiceProvider class exists\n";
} else {
    echo "❌ SocialiteServiceProvider class not found\n";
}

// چک کردن providers
echo "\n2. Checking registered providers:\n";
$providers = $app->getLoadedProviders();
if (isset($providers['Laravel\Socialite\SocialiteServiceProvider'])) {
    echo "✅ SocialiteServiceProvider is registered\n";
} else {
    echo "❌ SocialiteServiceProvider is NOT registered\n";
    echo "Registered providers: " . implode(', ', array_keys($providers)) . "\n";
}

// تست binding
echo "\n3. Testing binding:\n";
try {
    $factory = $app->make('Laravel\Socialite\Contracts\Factory');
    echo "✅ Factory binding works\n";
} catch (Exception $e) {
    echo "❌ Factory binding failed: " . $e->getMessage() . "\n";
}

// تست facade
echo "\n4. Testing facade:\n";
try {
    $driver = \Laravel\Socialite\Facades\Socialite::driver('google');
    echo "✅ Facade works\n";
} catch (Exception $e) {
    echo "❌ Facade failed: " . $e->getMessage() . "\n";
}

echo "\n=== Debug Complete ===\n";
