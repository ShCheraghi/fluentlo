<?php
namespace App\Services\AI\Drivers;

use App\Exceptions\AIException;
use App\Services\AI\Contracts\AIDriverInterface;

class RapidApiDriver extends BaseDriver implements AIDriverInterface
{
    protected function getHeaders(): array
    {
        return [
            'x-rapidapi-host' => $this->config['host'],
            'x-rapidapi-key' => $this->config['key'],
        ];
    }

    public function transcribe(array $data): array
    {
        if (isset($data['url'])) {
            return $this->transcribeFromUrl($data);
        }

        if (isset($data['file'])) {
            return $this->transcribeFromFile($data);
        }

        throw new AIException('Either url or file must be provided');
    }

    protected function transcribeFromUrl(array $data): array
    {
        $url = 'https://speech-to-text-ai.p.rapidapi.com/transcribe';

        $query = http_build_query([
            'url' => $data['url'],
            'lang' => $data['lang'] ?? 'en',
            'task' => $data['task'] ?? 'transcribe'
        ]);

        return $this->makeRequest('GET', $url . '?' . $query, [
            'headers' => $this->getHeaders()
        ]);
    }

    protected function transcribeFromFile(array $data): array
    {
        $url = 'https://speech-to-text-ai.p.rapidapi.com/transcribe';

        $query = http_build_query([
            'lang' => $data['lang'] ?? 'en',
            'task' => $data['task'] ?? 'transcribe'
        ]);

        $multipart = [
            [
                'name' => 'file',
                'contents' => fopen($data['file'], 'r'),
                'filename' => basename($data['file'])
            ]
        ];

        return $this->makeRequest('POST', $url . '?' . $query, [
            'multipart' => $multipart,
            'headers' => $this->getHeaders()
        ]);
    }

    public function chat(array $data): array
    {
        throw new AIException('Chat not implemented for RapidAPI driver');
    }
}
