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
        // Debug info برای شناسایی مشکل
        Log::info('STT DEBUG: Starting transcription', [
            'original_filename' => $file->getClientOriginalName(),
            'detected_mime' => $file->getMimeType(),
            'file_extension' => $file->getClientOriginalExtension(),
            'file_size' => $file->getSize(),
            'real_mime' => mime_content_type($file->getRealPath()),
            'timestamp' => now()->toISOString()
        ]);

        $absoluteUrl = $this->uploadTempAndGetAbsoluteUrl($file);
        if (!$absoluteUrl) {
            return ['success' => false, 'error' => 'Temporary file upload failed.'];
        }

        // تست manual URL قبل از ارسال
        $this->manualUrlTest($absoluteUrl);

        return $this->callStt($absoluteUrl, cleanupAfter: true);
    }

    private function uploadTempAndGetAbsoluteUrl(UploadedFile $file): ?string
    {
        $filename = 'temp_audio_' . time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
        $relativeDir = 'temp/audio';

        $storedPath = Storage::disk('public')->putFileAs($relativeDir, $file, $filename);
        if (!$storedPath) {
            Log::error('STT DEBUG: File store failed', ['filename' => $filename]);
            return null;
        }

        $absoluteUrl = Storage::disk('public')->url($storedPath);

        if (!str_starts_with($absoluteUrl, 'http')) {
            $baseUrl = rtrim(config('services.stt.public_base_url', config('app.url')), '/');
            $absoluteUrl = $baseUrl . $absoluteUrl;
        }

        Log::info('STT DEBUG: File uploaded successfully', [
            'stored_path' => $storedPath,
            'absolute_url' => $absoluteUrl,
            'file_exists' => Storage::disk('public')->exists($storedPath),
            'file_size' => Storage::disk('public')->size($storedPath)
        ]);

        return $absoluteUrl;
    }

    /**
     * تست manual URL - بررسی دقیق
     */
    private function manualUrlTest(string $url): void
    {
        try {
            Log::info('STT DEBUG: Testing URL accessibility', ['url' => $url]);

            // تست 1: HEAD request
            $headResponse = Http::timeout(10)->head($url);
            Log::info('STT DEBUG: HEAD test', [
                'status' => $headResponse->status(),
                'headers' => $headResponse->headers(),
                'ok' => $headResponse->ok()
            ]);

            // تست 2: GET request (چند بایت اول)
            $getResponse = Http::timeout(10)->get($url, [], [
                'Range' => 'bytes=0-1023' // فقط 1KB اول
            ]);

            Log::info('STT DEBUG: GET test (first 1KB)', [
                'status' => $getResponse->status(),
                'content_length' => strlen($getResponse->body()),
                'content_type' => $getResponse->header('content-type'),
                'first_bytes' => bin2hex(substr($getResponse->body(), 0, 16))
            ]);

            // تست 3: cURL از خارج (شبیه‌سازی RapidAPI)
            $curlTest = $this->simulateExternalAccess($url);
            Log::info('STT DEBUG: External access simulation', $curlTest);

        } catch (\Throwable $e) {
            Log::error('STT DEBUG: URL test failed', [
                'url' => $url,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }
    }

    /**
     * شبیه‌سازی دسترسی خارجی
     */
    private function simulateExternalAccess(string $url): array
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'RapidAPI-STT-Service/1.0',
                'Accept' => 'audio/*,*/*',
            ])->timeout(15)->head($url);

            return [
                'accessible' => $response->ok(),
                'status' => $response->status(),
                'headers' => $response->headers(),
                'error' => $response->ok() ? null : 'HTTP ' . $response->status()
            ];
        } catch (\Throwable $e) {
            return [
                'accessible' => false,
                'error' => $e->getMessage(),
                'type' => get_class($e)
            ];
        }
    }

    /** تماس به سرویس STT روی RapidAPI */
    private function callStt(string $url, bool $cleanupAfter): array
    {
        try {
            Log::info('STT DEBUG: Calling RapidAPI', [
                'driver' => $this->driver,
                'endpoint' => $this->endpoint,
                'url' => $url,
                'timestamp' => now()->toISOString()
            ]);

            $payload = ['url' => $url, 'task' => 'transcribe'];
            $resp = $this->ai->driver($this->driver)->postNoBody($this->endpoint, $payload);

            if ($cleanupAfter) {
                $this->cleanupByAbsoluteUrl($url);
            }

            if (!$resp->ok()) {
                Log::error('STT DEBUG: RapidAPI failed', [
                    'endpoint' => $this->endpoint,
                    'status' => $resp->status ?? null,
                    'error' => $resp->error ?? null,
                    'url' => $url,
                    'response_data' => $resp->data ?? null,
                    'response_raw' => method_exists($resp, 'raw') ? $resp->raw() : null
                ]);
                return ['success' => false, 'error' => $resp->error ?? 'STT API error'];
            }

            $text = (string)($resp->data['text'] ?? '');

            Log::info('STT DEBUG: Success', [
                'text_length' => strlen($text),
                'text_preview' => substr($text, 0, 100),
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

            Log::error('STT DEBUG: Exception in callStt', [
                'message' => $e->getMessage(),
                'url' => $url,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => array_slice($e->getTrace(), 0, 5)
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
                Log::info('STT DEBUG: Cleanup', [
                    'file' => $relative,
                    'deleted' => $deleted,
                    'url' => $absoluteUrl
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('STT DEBUG: Cleanup failed', [
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

        Log::info('STT DEBUG: Direct URL transcription', ['url' => $audioUrl]);

        // تست URL قبل از ارسال
        $this->manualUrlTest($audioUrl);

        return $this->callStt($audioUrl, cleanupAfter: false);
    }

    private function isLocalhostUrl(string $url): bool
    {
        return (bool)preg_match('/^(https?:\/\/)(localhost|127\.0\.0\.1)(:\d+)?\//i', $url);
    }
}
