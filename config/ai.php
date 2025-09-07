<?php

return [
    'default' => env('AI_DEFAULT_DRIVER', 'openai'),

    'drivers' => [
        'openai' => [
            'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
            'api_key'  => env('OPENAI_API_KEY'),
            'timeout'  => (int) env('AI_HTTP_TIMEOUT', 30),
            'retry'    => ['times' => 2, 'sleep' => 200], // میلی‌ثانیه
            'headers'  => [
                'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
                'Content-Type'  => 'application/json',
            ],
        ],

        'azure' => [
            'endpoint'      => env('AZURE_ENDPOINT'),       // بدون / انتهایی
            'deployment_id' => env('AZURE_DEPLOYMENT_ID'),
            'api_version'   => env('AZURE_API_VERSION', '2023-12-01-preview'),
            'api_key'       => env('AZURE_API_KEY'),
            'timeout'       => (int) env('AI_HTTP_TIMEOUT', 30),
            'retry'         => ['times' => 2, 'sleep' => 200],
            'headers'       => [
                'api-key'      => env('AZURE_API_KEY'),
                'Content-Type' => 'application/json',
            ],
        ],

        'rapidapi' => [
            'host'    => env('RAPIDAPI_HOST'),
            'key'     => env('RAPIDAPI_KEY'),
            'timeout' => (int) env('AI_HTTP_TIMEOUT', 30),
            'retry'   => ['times' => 2, 'sleep' => 200],
            'headers' => [
                'X-RapidAPI-Key'  => env('RAPIDAPI_KEY'),
                'X-RapidAPI-Host' => env('RAPIDAPI_HOST'),
                // این سرویس نمونه‌ی RapidAPI فرم‌-انکد می‌خواد:
                'Content-Type'    => 'application/x-www-form-urlencoded',
            ],
        ],
    ],
];
