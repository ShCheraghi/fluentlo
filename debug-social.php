<?php
require_once 'vendor/autoload.php';

echo "Testing autoload...\n";

try {
    $logger = new \Monolog\Logger('test');
    echo "✅ Monolog\\Logger found\n";
} catch (Error $e) {
    echo "❌ Monolog\\Logger not found: " . $e->getMessage() . "\n";
}

try {
    $socialite = new \Laravel\Socialite\SocialiteManager(app());
    echo "✅ Socialite found\n";
} catch (Error $e) {
    echo "❌ Socialite not found: " . $e->getMessage() . "\n";
}

echo "Composer autoload files:\n";
print_r(get_included_files());
