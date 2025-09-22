<?php
declare(strict_types=1);

namespace App\Services\AI\Drivers;

use App\Services\AI\Contracts\AIDriverInterface;
use App\Services\AI\Exceptions\AIException;

class ChatGpt26Driver extends BaseDriver implements AIDriverInterface
{
    /** @inheritDoc */
    public function transcribe(array $data): array
    {
        throw new AIException('Use rapidapi_stt driver for transcription');
    }

    /**
     * Try messages payload first; on 404/500 fall back to prompt.
     */
    public function chat(array $data): array
    {
        $url   = $this->buildUrl();
        $model = $data['model'] ?? ($this->config['default_model'] ?? 'GPT-5-mini');
        $msgs  = $data['messages'] ?? [];

        $allowed = ['temperature','max_tokens','top_p','stop','presence_penalty','frequency_penalty','stream'];
        $extra   = array_intersect_key($data, array_flip($allowed));

        $payloadMessages = ['model' => $model, 'messages' => $msgs] + $extra;

        try {
            $resp = $this->makeRequest('POST', $url, [
                'json'    => $payloadMessages,
                'headers' => $this->headers(),
            ]);
            return $this->normalize($resp);
        } catch (AIException $e) {
            $fallbackStatuses = (array)($this->config['fallback_statuses'] ?? [404, 500]);
            if (!in_array($e->getCode(), $fallbackStatuses, true)) {
                throw $e;
            }
            \Log::warning('Chat provider rejected messages payload; trying prompt...', [
                'status' => $e->getCode(),
                'msg'    => $e->getMessage(),
            ]);

            $payloadPrompt = ['model' => $model, 'prompt' => $this->messagesToPrompt($msgs)] + $extra;

            $resp = $this->makeRequest('POST', $url, [
                'json'    => $payloadPrompt,
                'headers' => $this->headers(),
            ]);

            if (isset($resp['choices'][0]['message']['content'])) {
                return $resp;
            }
            return [
                'choices' => [[
                    'message' => [
                        'role'    => 'assistant',
                        'content' => $this->extractText($resp),
                    ],
                ]],
            ];
        }
    }


    /** هدرهای مشترک RapidAPI */
    protected function headers(): array
    {
        return [
            'x-rapidapi-key'  => $this->config['key'],
            'x-rapidapi-host' => $this->config['host'],
            'Content-Type'    => 'application/json',
        ];
    }

    /** ساخت URL بر اساس base_url + endpoint (ریشه پیش‌فرض) */
    private function buildUrl(): string
    {
        $base     = rtrim((string)($this->config['base_url'] ?? ''), '/');
        $endpoint = (string)($this->config['endpoint'] ?? '/');
        return $base . '/' . ltrim($endpoint, '/');
    }

    /** نرمال‌سازی پاسخ به فرم OpenAI-مانند */
    private function normalize(array $resp): array
    {
        $text = $resp['text'] ??
            $resp['result'] ??
            ($resp['data']['text'] ?? null) ??
            $resp['transcript'] ?? null;

        return ['text' => (string)($text ?? '')];
    }

    /** استخراج متن از پاسخ‌های متنوع Provider */
    private function extractText(array $resp): string
    {
        $candidates = [
            $resp['content']  ?? null,
            $resp['result']   ?? null,
            $resp['text']     ?? null,
            $resp['message']  ?? null,
            $resp['response'] ?? null,
        ];
        foreach ($candidates as $c) {
            if (is_string($c) && $c !== '') {
                return $c;
            }
        }
        return 'خطا در دریافت پاسخ';
    }

    /**
     * تبدیل messages به یک prompt متنی ساده:
     * - آخرین system در بالا، سپس دیالوگ user/assistant به صورت خطی.
     */
    private function messagesToPrompt(array $messages): string
    {
        $system = '';
        $lines  = [];

        foreach ($messages as $m) {
            $role    = (string)($m['role'] ?? 'user');
            $content = (string)($m['content'] ?? '');

            if ($role === 'system') {
                $system = $content; // آخرین system برنده است
                continue;
            }

            $lines[] = strtoupper($role) . ': ' . $content;
        }

        return ($system !== '' ? "SYSTEM: {$system}\n" : '') . implode("\n", $lines);
    }
}
