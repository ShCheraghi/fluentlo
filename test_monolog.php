<?php
// test_monolog.php
require_once 'vendor/autoload.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// ایجاد یک لاگر
$log = new Logger('test');
$log->pushHandler(new StreamHandler('test.log', Logger::DEBUG));

// نوشتن یک لاگ تست
$log->info('Monolog test message');
echo "Log written to test.log";
