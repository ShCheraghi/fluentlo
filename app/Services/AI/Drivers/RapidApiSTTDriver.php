<?php
declare(strict_types=1);

namespace App\Services\AI\Drivers;

use App\Services\AI\Contracts\AIDriverInterface;
use App\Services\AI\Exceptions\AIException;

class RapidApiSTTDriver extends BaseDriver implements AIDriverInterface
{
    public function transcribe(array $data): array
    {
        $base     = rtrim((string)($this->config['base_url'] ?? ''), '/');
        $endpoint = (string)($this->config['endpoint'] ?? '/transcribe');
        $url      = $base . '/' . ltrim($endpoint, '/');

        $query = http_build_query([
            'lang' => $data['lang'] ?? ($this->config['default_lang'] ?? 'en'),
            'task' => $data['task'] ?? 'transcribe',
        ]);

        $multipart = [];

        if (isset($data['file'])) {
            $path = (string)$data['file'];
            if (!is_readable($path)) {
                throw new AIException('فایل قابل خواندن نیست');
            }
            $multipart[] = [
                'name'     => 'file',
                'contents' => fopen($path, 'r'),
                'filename' => basename($path),
            ];
        }

        if (isset($data['url'])) {
            $multipart[] = ['name' => 'url', 'contents' => (string)$data['url']];
        }

        if (empty($multipart)) {
            throw new AIException('فایل یا آدرس URL الزامی است');
        }

        $raw = $this->makeRequest('POST', $url . '?' . $query, [
            'multipart' => $multipart,
            'headers'   => [
                'x-rapidapi-key'  => $this->config['key'],
                'x-rapidapi-host' => $this->config['host'],
            ],
        ]);

        return $this->normalize($raw);
    }

    private function normalize(array $resp): array
    {
        return [
            'text'       => (string)($resp['text'] ?? $resp['result'] ?? ($resp['data']['text'] ?? $resp['transcript'] ?? '')),
            'confidence' => $resp['confidence'] ?? ($resp['data']['confidence'] ?? null),
            'duration'   => $resp['duration'] ?? ($resp['data']['duration'] ?? null),
        ];
    }

    public function chat(array $data): array
    {
        throw new AIException('Use chatgpt26 driver for chat');
    }
}
