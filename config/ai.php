<?php
return [
    'default_driver' => env('AI_DEFAULT_DRIVER', 'chatgpt26'),

    'drivers' => [

        'rapidapi_stt' => [
            'key'          => env('RAPIDAPI_STT_KEY', env('RAPIDAPI_KEY')),
            'host'         => env('RAPIDAPI_STT_HOST', 'speech-to-text-ai.p.rapidapi.com'),
            'base_url'     => env('RAPIDAPI_STT_BASE', 'https://speech-to-text-ai.p.rapidapi.com'),
            'endpoint'     => env('RAPIDAPI_STT_ENDPOINT', '/transcribe'),
            'default_lang' => env('AI_STT_LANG', 'en'),
            'timeout'      => 60,
        ],

        'chatgpt26' => [
            'key'               => env('RAPIDAPI_CHAT_KEY', env('RAPIDAPI_KEY')),
            'host'              => env('RAPIDAPI_CHAT_HOST', 'chat-gpt26.p.rapidapi.com'),
            'base_url'          => env('RAPIDAPI_CHAT_URL', 'https://chat-gpt26.p.rapidapi.com'),

            // مهم: endpoint خاص chat-completions
            'endpoint_messages' => env('RAPIDAPI_CHAT_ENDPOINT', '/'),

            // مدل پیش‌فرض واقعی و نگاشت نام‌های فیک به واقعی
            'default_model'     => env('RAPIDAPI_CHAT_AI_MODEL', 'gpt-3.5-turbo'),
            'model_map'         => [
                'GPT-5-mini'   => 'gpt-3.5-turbo',
                'gpt-5-mini'   => 'gpt-3.5-turbo',
                'GPT-4o-mini'  => 'gpt-4o-mini',
                'gpt-4o-mini'  => 'gpt-4o-mini',
            ],

            // بعضی Providerها با system مشکل دارن
            'allow_system'      => (bool) env('AI_CHAT_ALLOW_SYSTEM', false),

            // اگر Provider content parts می‌خواهد (معمولاً لازم نیست)
            'messages_as_parts' => (bool) env('AI_CHAT_MESSAGES_AS_PARTS', false),

            'temperature'       => (float) env('AI_CHAT_TEMPERATURE', 0.7),
            'max_tokens'        => (int) env('AI_CHAT_MAX_TOKENS', 1000),
            'timeout'           => 30,
        ],

    ],
];
