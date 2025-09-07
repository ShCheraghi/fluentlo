<?php
// فایل: debug-test.php در root پروژه

echo "=== Laravel Social Auth Debug ===\n\n";

// تست 1: کلاس‌های اصلی
echo "1. Testing core classes:\n";
try {
    require_once 'vendor/autoload.php';
    echo "✅ Autoload OK\n";
} catch (Exception $e) {
    echo "❌ Autoload failed: " . $e->getMessage() . "\n";
    exit;
}

// تست 2: Laravel bootstrap
echo "\n2. Testing Laravel bootstrap:\n";
try {
    $app = require_once 'bootstrap/app.php';
    echo "✅ Laravel bootstrap OK\n";
} catch (Exception $e) {
    echo "❌ Laravel bootstrap failed: " . $e->getMessage() . "\n";
}

// تست 3: Socialite
echo "\n3. Testing Socialite:\n";
try {
    $socialite = app('Laravel\Socialite\Contracts\Factory');
    echo "✅ Socialite factory OK\n";
} catch (Exception $e) {
    echo "❌ Socialite factory failed: " . $e->getMessage() . "\n";
}

// تست 4: Google driver
echo "\n4. Testing Google driver:\n";
try {
    $driver = $socialite->driver('google');
    echo "✅ Google driver OK\n";
} catch (Exception $e) {
    echo "❌ Google driver failed: " . $e->getMessage() . "\n";
}

// تست 5: Config values
echo "\n5. Testing config values:\n";
echo "GOOGLE_CLIENT_ID: " . (config('services.google.client_id') ? 'SET' : 'NOT SET') . "\n";
echo "GOOGLE_CLIENT_SECRET: " . (config('services.google.client_secret') ? 'SET' : 'NOT SET') . "\n";
echo "GOOGLE_REDIRECT: " . (config('services.google.redirect') ? config('services.google.redirect') : 'NOT SET') . "\n";

// تست 6: Database connection
echo "\n6. Testing database:\n";
try {
    DB::connection()->getPdo();
    echo "✅ Database connection OK\n";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
}

// تست 7: User model
echo "\n7. Testing User model:\n";
try {
    $userCount = App\Models\User::count();
    echo "✅ User model OK (count: $userCount)\n";
} catch (Exception $e) {
    echo "❌ User model failed: " . $e->getMessage() . "\n";
}

echo "\n=== Debug Complete ===\n";
