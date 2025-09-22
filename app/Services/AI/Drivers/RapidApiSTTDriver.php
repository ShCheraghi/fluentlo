<?php

namespace App\Services\AI\Drivers;

use App\Services\AI\Contracts\AIDriverInterface;
use App\Services\AI\Exceptions\AIException;

class RapidApiSTTDriver extends BaseDriver implements AIDriverInterface
{
    public function transcribe(array $data): array
    {
        $url = 'https://' . $this->config['host'] . '/transcribe';
        $query = http_build_query([
            'lang' => $data['lang'] ?? 'en',
            'task' => $data['task'] ?? 'transcribe',
        ]);

        // multipart مشترک
        $multipart = [];

        if (isset($data['file'])) {
            $path = $data['file'];
            if (!is_readable($path)) {
                throw new AIException('فایل قابل خواندن نیست');
            }
            $multipart[] = [
                'name' => 'file',
                'contents' => fopen($path, 'r'),
                'filename' => basename($path),
            ];
        }

        if (isset($data['url'])) {
            // اغلب سرویس‌ها همین فیلد را می‌پذیرند؛ بدون دانلود فایل
            $multipart[] = ['name' => 'url', 'contents' => (string)$data['url']];
        }

        if (empty($multipart)) {
            throw new AIException('فایل یا آدرس URL الزامی است');
        }

        $raw = $this->makeRequest('POST', $url . '?' . $query, [
            'multipart' => $multipart,
            'headers' => [
                'x-rapidapi-key' => $this->config['key'],
                'x-rapidapi-host' => $this->config['host'],
            ],
        ]);

        return $this->normalize($raw);
    }

    private function normalize(array $resp): array
    {
        $text = $resp['text']
            ?? $resp['result']
            ?? ($resp['data']['text'] ?? null)
            ?? $resp['transcript']
            ?? null;

        return ['text' => (string)($text ?? '')];
    }

    public function chat(array $data): array
    {
        throw new AIException('Use chatgpt26 driver for chat');
    }
}
