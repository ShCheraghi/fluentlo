<?php

namespace App\Services\AI;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SpeechToTextSimpleService
{
    private string $driver = 'rapidapi';   // در آینده عوضش کنی فقط همین‌جا
    private string $endpoint = 'transcribe'; // مسیر driver در AIManager

    public function __construct(private AIManager $ai)
    {
    }

    /** فایل → آپلود موقت → URL مطلق → STT */
    public function transcribe(UploadedFile $file): array
    {
        $absoluteUrl = $this->uploadTempAndGetAbsoluteUrl($file);
        if (!$absoluteUrl) {
            return ['success' => false, 'error' => 'Temporary file upload failed.'];
        }

        $res = $this->callStt($absoluteUrl, cleanupAfter: true);
        return $res;
    }

    private function uploadTempAndGetAbsoluteUrl(UploadedFile $file): ?string
    {
        $filename = 'temp_audio_' . time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
        $relativeDir = 'temp/audio';
        $storedPath = Storage::disk('public')->putFileAs($relativeDir, $file, $filename);
        if (!$storedPath) return null;

        $publicPath = Storage::disk('public')->url($storedPath); // "/storage/temp/audio/xxx.ext"
        $base = rtrim(config('services.stt.public_base_url', config('app.url')), '/'); // از env
        return $base . $publicPath; // "https://domain.tld/storage/temp/audio/xxx.ext"
    }

    /** تماس به سرویس STT روی RapidAPI */
    private function callStt(string $url, bool $cleanupAfter): array
    {
        try {
            $payload = ['url' => $url, 'task' => 'transcribe']; // lang=auto by provider
            $resp = $this->ai->driver($this->driver)->postNoBody($this->endpoint, $payload);

            if ($cleanupAfter) $this->cleanupByAbsoluteUrl($url);

            if (!$resp->ok()) {
                Log::warning('STT failed', [
                    'endpoint' => $this->endpoint,
                    'status' => $resp->status ?? null,
                    'error' => $resp->error ?? null,
                    'url' => $url,
                ]);
                return ['success' => false, 'error' => $resp->error ?? 'STT error'];
            }

            $text = (string)($resp->data['text'] ?? '');
            return [
                'success' => true,
                'data' => [
                    'text' => $text,
                    'language' => (string)($resp->data['language'] ?? 'auto'),
                    'confidence' => (float)($resp->data['confidence'] ?? 0.0),
                    'duration' => $resp->data['duration'] ?? null,
                    'word_count' => $this->wordCount($text),
                    'provider' => $this->driver,
                    'processed_at' => now()->toISOString(),
                ],
            ];
        } catch (\Throwable $e) {
            if ($cleanupAfter) $this->cleanupByAbsoluteUrl($url);
            Log::error('STT transcribe exception', ['e' => $e->getMessage(), 'url' => $url]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function cleanupByAbsoluteUrl(string $absoluteUrl): void
    {
        try {
            $path = parse_url($absoluteUrl, PHP_URL_PATH);                // "/storage/temp/audio/xxx.ext"
            $relative = ltrim(str_replace('/storage', '', $path), '/');   // "temp/audio/xxx.ext"
            if (str_starts_with($relative, 'temp/audio/')) {
                Storage::disk('public')->delete($relative);
            }
        } catch (\Throwable $e) {
            Log::warning('STT temp cleanup failed', ['e' => $e->getMessage(), 'url' => $absoluteUrl]);
        }
    }

    private function wordCount(string $s): int
    {
        preg_match_all('/[\p{L}\p{N}]+/u', $s, $m);
        return count($m[0]);
    }

    /** مستقیم از URL عمومی */
    public function transcribeFromPublicUrl(string $audioUrl): array
    {
        // رد کردن localhost/127.*
        if ($this->isLocalhostUrl($audioUrl)) {
            return ['success' => false, 'error' => 'audio_url must be publicly reachable (not localhost/127.0.0.1).'];
        }

        // Preflight (اختیاری ولی مفید)
        try {
            $head = Http::timeout(6)->head($audioUrl);
            if (!$head->ok()) {
                Log::warning('STT preflight failed', ['status' => $head->status(), 'url' => $audioUrl]);
            }
        } catch (\Throwable $e) {
            Log::warning('STT preflight exception', ['e' => $e->getMessage(), 'url' => $audioUrl]);
        }

        return $this->callStt($audioUrl, cleanupAfter: false);
    }

    private function isLocalhostUrl(string $url): bool
    {
        return (bool)preg_match('/^(https?:\/\/)(localhost|127\.0\.0\.1)(:\d+)?\//i', $url);
    }
}
