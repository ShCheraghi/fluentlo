<?php
declare(strict_types=1);

namespace App\Services\AI\Drivers;

use App\Services\AI\Contracts\AIDriverInterface;
use App\Services\AI\Exceptions\AIException;

class ChatGpt26Driver extends BaseDriver implements AIDriverInterface
{
    public function transcribe(array $data): array
    {
        throw new AIException('Use rapidapi_stt driver for transcription');
    }

    public function chat(array $data): array
    {
        // 1) URL دقیقاً ریشه مثل مثال RapidAPI
        $url = rtrim((string)($this->config['base_url'] ?? ''), '/') . '/';

        // 2) مدل واقعی/همون چیزی که توی پلن RapidAPI هست
        $model = $this->resolveModel($data['model'] ?? null);

        // 3) حالت strict: فقط آخرین پیام user، بدون system/history/پارامتر اضافه
        $strict = (bool)($this->config['strict_minimal'] ?? true);
        $messages = $strict
            ? $this->lastUserOnly($data['messages'] ?? [])
            : $this->sanitizeMessages($data['messages'] ?? []);

        if (empty($messages)) {
            $messages = [['role' => 'user', 'content' => 'Hello!']];
        }

        // 4) payload دقیقاً مثل مثال RapidAPI: raw body (نه گزینه json)
        $payload = json_encode([
            'model'    => $model,
            'messages' => $messages,
        ], JSON_UNESCAPED_UNICODE);

        if ($payload === false) {
            throw new AIException('Failed to encode JSON payload');
        }

        // 5) هدرها دقیقاً مینیمال
        $resp = $this->makeRequest('POST', $url, [
            'body'    => $payload, // ← مهم: raw body
            'headers' => [
                'Content-Type'    => 'application/json',
                'x-rapidapi-key'  => $this->config['key'],
                'x-rapidapi-host' => $this->config['host'],
            ],
        ]);

        return $this->normalize($resp);
    }

    private function resolveModel(?string $model): string
    {
        $m   = $model ?? ($this->config['default_model'] ?? 'GPT-5-mini');
        $map = (array)($this->config['model_map'] ?? []);
        return $map[$m] ?? $m;
    }

    // فقط آخرین پیام user را نگه می‌داریم (سازگاری با مثال RapidAPI)
// در ChatGpt26Driver جایگزین همین متد کن
    private function lastUserOnly(array $messages): array
    {
        $instruction = null;
        $lastUser = null;

        foreach ($messages as $m) {
            $role = (string)($m['role'] ?? '');
            $content = trim((string)($m['content'] ?? ''));

            // Instruction را پیدا کن: یا system، یا user که با "Instruction:" شروع شده
            if ($role === 'system' && $content !== '') {
                $instruction = "Instruction:\n" . $content;
            } elseif ($role === 'user' && strncasecmp($content, 'Instruction:', 12) === 0) {
                $instruction = $content;
            }
        }

        // آخرین پیام واقعی user برای پرسش کاربر
        foreach (array_reverse($messages) as $m) {
            $role = (string)($m['role'] ?? '');
            $content = trim((string)($m['content'] ?? ''));
            if ($role === 'user' && $content !== '' && strncasecmp($content, 'Instruction:', 12) !== 0) {
                $lastUser = $content;
                break;
            }
        }

        if ($lastUser === null && $instruction === null) {
            return [];
        }

        // اگر Instruction داریم، آن را به ابتدای پیام user بچسبان
        $merged = $instruction ? ($instruction . "\n\n" . ($lastUser ?? '')) : ($lastUser ?? '');

        return [['role' => 'user', 'content' => $merged]];
    }


    // در غیر strict، پیام‌ها را تمیز می‌کنیم (بدون نقش‌های عجیب)
    private function sanitizeMessages(array $messages): array
    {
        $allowSystem = (bool)($this->config['allow_system'] ?? false);
        $out = [];
        foreach ($messages as $m) {
            $role    = (string)($m['role'] ?? '');
            $content = trim((string)($m['content'] ?? ''));
            if ($role === '' || $content === '') continue;
            if (!$allowSystem && $role === 'system') {
                $role    = 'user';
                $content = 'System instruction: ' . $content;
            }
            if (!in_array($role, ['system','user','assistant'], true)) {
                $role = 'user';
            }
            $out[] = ['role' => $role, 'content' => $content];
        }
        return $out;
    }

    private function normalize(array $resp): array
    {
        if (isset($resp['choices'][0]['message']['content'])) {
            return $resp;
        }
        $text = $this->extractText($resp);
        return [
            'choices' => [[
                'message' => ['role' => 'assistant', 'content' => $text],
            ]],
        ];
    }

    private function extractText(array $resp): string
    {
        foreach ([
                     $resp['content']  ?? null,
                     $resp['result']   ?? null,
                     $resp['text']     ?? null,
                     $resp['message']  ?? null,
                     $resp['response'] ?? null,
                     $resp['output']   ?? null,
                 ] as $c) {
            if (is_string($c) && $c !== '') return $c;
        }
        return '';
    }
}
