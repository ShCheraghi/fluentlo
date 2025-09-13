<?php
namespace App\Services\AI\Drivers;

use App\Exceptions\AIException;
use App\Services\AI\Contracts\AIDriverInterface;

class OpenAiDriver extends BaseDriver implements AIDriverInterface
{
    protected function getHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->config['api_key'],
            'Content-Type' => 'application/json',
        ];
    }

    public function transcribe(array $data): array
    {
        $url = 'https://api.openai.com/v1/audio/transcriptions';

        $multipart = [
            [
                'name' => 'file',
                'contents' => fopen($data['file'], 'r'),
                'filename' => basename($data['file'])
            ],
            [
                'name' => 'model',
                'contents' => $data['model'] ?? 'whisper-1'
            ]
        ];

        if (isset($data['language'])) {
            $multipart[] = [
                'name' => 'language',
                'contents' => $data['language']
            ];
        }

        return $this->makeRequest('POST', $url, [
            'multipart' => $multipart,
            'headers' => array_merge($this->getHeaders(), [
                'Content-Type' => 'multipart/form-data'
            ])
        ]);
    }

    public function chat(array $data): array
    {
        $url = 'https://api.openai.com/v1/chat/completions';

        return $this->makeRequest('POST', $url, [
            'json' => [
                'model' => $data['model'] ?? 'gpt-3.5-turbo',
                'messages' => $data['messages'],
                'temperature' => $data['temperature'] ?? 0.7,
            ],
            'headers' => $this->getHeaders()
        ]);
    }
}
