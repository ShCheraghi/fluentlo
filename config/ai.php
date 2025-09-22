<?php
return [
    'default_driver' => env('AI_DEFAULT_DRIVER', 'chatgpt26'),

    'drivers' => [
        'rapidapi_stt' => [
            'key'          => env('RAPIDAPI_STT_KEY', env('RAPIDAPI_KEY')), // امکان استفاده از یک کلید
            'host'         => env('RAPIDAPI_STT_HOST', 'speech-to-text-ai.p.rapidapi.com'),
            'base_url'     => env('RAPIDAPI_STT_BASE', 'https://speech-to-text-ai.p.rapidapi.com'),
            'endpoint'     => env('RAPIDAPI_STT_ENDPOINT', '/transcribe'),
            'default_lang' => env('AI_STT_LANG', 'en'),
            'timeout'      => 60,
        ],

        'chatgpt26' => [
            'key'           => env('RAPIDAPI_CHAT_KEY', env('RAPIDAPI_KEY')),
            'host'          => env('RAPIDAPI_CHAT_HOST', 'chat-gpt26.p.rapidapi.com'),
            'base_url'      => env('RAPIDAPI_CHAT_URL', 'https://chat-gpt26.p.rapidapi.com'),
            'endpoint'      => env('RAPIDAPI_CHAT_ENDPOINT', '/'), // مهم: ریشه
            'default_model' => env('RAPIDAPI_CHAT_AI_MODEL', 'GPT-5-mini'),
            'timeout'       => 30,
            'temperature'   => 0.7,
            'max_tokens'    => 1000,
        ],
    ],
];
