<?php

namespace App\Services\AI;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SpeechToTextSimpleService
{
    private string $driver = 'rapidapi';
    private string $endpoint = 'transcribe';

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

        Log::info('STT: Generated temp URL', ['url' => $absoluteUrl]);

        return $this->callStt($absoluteUrl, cleanupAfter: true);
    }

    private function uploadTempAndGetAbsoluteUrl(UploadedFile $file): ?string
    {
        $filename = 'temp_audio_' . time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
        $relativeDir = 'temp/audio';

        $storedPath = Storage::disk('public')->putFileAs($relativeDir, $file, $filename);
        if (!$storedPath) {
            Log::error('STT: Failed to store file', ['filename' => $filename]);
            return null;
        }

        // مشکل اینجا بود! Storage::url() خودش URL کامل برمی‌گردونه
        $absoluteUrl = Storage::disk('public')->url($storedPath);

        // اگر URL کامل نیست، base URL اضافه کن
        if (!str_starts_with($absoluteUrl, 'http')) {
            $baseUrl = rtrim(config('services.stt.public_base_url', config('app.url')), '/');
            $absoluteUrl = $baseUrl . $absoluteUrl;
        }

        Log::info('STT: File uploaded', [
            'stored_path' => $storedPath,
            'absolute_url' => $absoluteUrl
        ]);

        return $absoluteUrl;
    }

    /** تماس به سرویس STT روی RapidAPI */
    private function callStt(string $url, bool $cleanupAfter): array
    {
        try {
            Log::info('STT: Calling API', [
                'driver' => $this->driver,
                'endpoint' => $this->endpoint,
                'url' => $url
            ]);

            $payload = ['url' => $url, 'task' => 'transcribe'];
            $resp = $this->ai->driver($this->driver)->postNoBody($this->endpoint, $payload);

            if ($cleanupAfter) {
                $this->cleanupByAbsoluteUrl($url);
            }

            if (!$resp->ok()) {
                Log::error('STT API failed', [
                    'endpoint' => $this->endpoint,
                    'status' => $resp->status ?? null,
                    'error' => $resp->error ?? null,
                    'url' => $url,
                    'response_data' => $resp->data ?? null
                ]);
                return ['success' => false, 'error' => $resp->error ?? 'STT API error'];
            }

            $text = (string)($resp->data['text'] ?? '');

            Log::info('STT: Success', [
                'text_length' => strlen($text),
                'language' => $resp->data['language'] ?? 'auto'
            ]);

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
            if ($cleanupAfter) {
                $this->cleanupByAbsoluteUrl($url);
            }

            Log::error('STT exception', [
                'message' => $e->getMessage(),
                'url' => $url,
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function cleanupByAbsoluteUrl(string $absoluteUrl): void
    {
        try {
            $path = parse_url($absoluteUrl, PHP_URL_PATH);
            $relative = ltrim(str_replace('/storage', '', $path), '/');

            if (str_starts_with($relative, 'temp/audio/')) {
                $deleted = Storage::disk('public')->delete($relative);
                Log::info('STT: Temp file cleanup', [
                    'file' => $relative,
                    'deleted' => $deleted
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('STT: Cleanup failed', [
                'error' => $e->getMessage(),
                'url' => $absoluteUrl
            ]);
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
        if ($this->isLocalhostUrl($audioUrl)) {
            return ['success' => false, 'error' => 'audio_url must be publicly reachable (not localhost/127.0.0.1).'];
        }

        // Preflight check
        try {
            $head = Http::timeout(10)->head($audioUrl);
            if (!$head->ok()) {
                Log::warning('STT: URL preflight failed', [
                    'status' => $head->status(),
                    'url' => $audioUrl
                ]);
            } else {
                Log::info('STT: URL preflight OK', [
                    'content_type' => $head->header('content-type'),
                    'content_length' => $head->header('content-length'),
                    'url' => $audioUrl
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('STT: Preflight exception', [
                'error' => $e->getMessage(),
                'url' => $audioUrl
            ]);
        }

        return $this->callStt($audioUrl, cleanupAfter: false);
    }

    private function isLocalhostUrl(string $url): bool
    {
        return (bool)preg_match('/^(https?:\/\/)(localhost|127\.0\.0\.1)(:\d+)?\//i', $url);
    }

    /**
     * بررسی فرمت فایل
     */
    private function isSupportedFormat(UploadedFile $file): bool
    {
        $supportedMimes = [
            'audio/wav', 'audio/wave', 'audio/x-wav',
            'audio/mpeg', 'audio/mp3',
            'audio/mp4', 'audio/m4a', 'audio/x-m4a',
            'audio/ogg', 'audio/vorbis',
            'audio/webm',
            'audio/flac', 'audio/x-flac'
        ];

        $supportedExtensions = ['wav', 'mp3', 'm4a', 'ogg', 'webm', 'flac'];

        $actualMime = $file->getMimeType();
        $extension = strtolower($file->getClientOriginalExtension());

        Log::info('STT: File format check', [
            'filename' => $file->getClientOriginalName(),
            'detected_mime' => $actualMime,
            'extension' => $extension,
            'size' => $file->getSize()
        ]);

        return in_array($actualMime, $supportedMimes) || in_array($extension, $supportedExtensions);
    }

    /**
     * تست دسترسی URL
     */
    private function testUrlAccessibility(string $url): bool
    {
        try {
            $response = Http::timeout(15)->head($url);

            Log::info('STT: URL accessibility test', [
                'url' => $url,
                'status' => $response->status(),
                'content_type' => $response->header('content-type'),
                'content_length' => $response->header('content-length'),
                'accessible' => $response->ok()
            ]);

            return $response->ok();
        } catch (\Throwable $e) {
            Log::error('STT: URL accessibility test failed', [
                'url' => $url,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
