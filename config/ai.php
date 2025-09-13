<?php
return [
    'default' => env('AI_DEFAULT_DRIVER', 'rapidapi'),

    'drivers' => [
        'rapidapi' => [
            'host' => env('RAPIDAPI_HOST', 'speech-to-text-ai.p.rapidapi.com'),
            'key' => env('RAPIDAPI_KEY'),
            'timeout' => 60,
            'chat_host' => 'cheapest-gpt-4-turbo-gpt-4-vision-chatgpt-openai-ai-api.p.rapidapi.com',
        ],

        'openai' => [
            'api_key' => env('OPENAI_API_KEY'),
            'timeout' => 60,
        ],
    ],
];
