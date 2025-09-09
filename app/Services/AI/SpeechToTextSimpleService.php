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

    /** فایل → مستقیم به RapidAPI */
    public function transcribe(UploadedFile $file): array
    {
        Log::info('STT: Processing file upload', [
            'filename' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'mime' => $file->getMimeType(),
            'extension' => $file->getClientOriginalExtension()
        ]);

        // بجای آپلود موقتی، مستقیماً فایل را به RapidAPI ارسال می‌کنیم
        return $this->transcribeFileDirectly($file);
    }

    /**
     * ارسال مستقیم فایل به RapidAPI
     */
    private function transcribeFileDirectly(UploadedFile $file): array
    {
        try {
            // خواندن محتوای فایل
            $fileContent = file_get_contents($file->getRealPath());
            $filename = $file->getClientOriginalName();
            $mimeType = $file->getMimeType();

            Log::info('STT: Sending file directly to RapidAPI', [
                'filename' => $filename,
                'content_length' => strlen($fileContent),
                'mime_type' => $mimeType
            ]);

            // ارسال درخواست به RapidAPI با فایل
            $response = Http::withHeaders([
                'x-rapidapi-host' => 'speech-to-text-ai.p.rapidapi.com',
                'x-rapidapi-key' => config('services.rapidapi.key'), // کلید از config
            ])
                ->timeout(60)
                ->attach('file', $fileContent, $filename)
                ->post('https://speech-to-text-ai.p.rapidapi.com/transcribe', [
                    'task' => 'transcribe',
                    'lang' => 'auto' // یا en, fa, etc.
                ]);

            if (!$response->ok()) {
                Log::error('STT: RapidAPI direct upload failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'headers' => $response->headers()
                ]);

                return [
                    'success' => false,
                    'error' => 'STT service returned: ' . $response->body()
                ];
            }

            $data = $response->json();
            $text = $data['text'] ?? '';

            Log::info('STT: Direct upload success', [
                'text_length' => strlen($text),
                'response_keys' => array_keys($data)
            ]);

            return [
                'success' => true,
                'data' => [
                    'text' => $text,
                    'language' => $data['language'] ?? 'auto',
                    'confidence' => (float)($data['confidence'] ?? 0.0),
                    'duration' => $data['duration'] ?? null,
                    'word_count' => $this->wordCount($text),
                    'provider' => 'rapidapi-direct',
                    'processed_at' => now()->toISOString(),
                ],
            ];

        } catch (\Throwable $e) {
            Log::error('STT: Direct upload exception', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function wordCount(string $s): int
    {
        preg_match_all('/[\p{L}\p{N}]+/u', $s, $m);
        return count($m[0]);
    }

    /** برای URL های عمومی - روش قبلی */
    public function transcribeFromPublicUrl(string $audioUrl): array
    {
        if ($this->isLocalhostUrl($audioUrl)) {
            return ['success' => false, 'error' => 'audio_url must be publicly reachable.'];
        }

        Log::info('STT: Processing public URL', ['url' => $audioUrl]);

        try {
            // ارسال URL به RapidAPI
            $response = Http::withHeaders([
                'x-rapidapi-host' => 'speech-to-text-ai.p.rapidapi.com',
                'x-rapidapi-key' => config('services.rapidapi.key'),
            ])
                ->timeout(60)
                ->asForm()
                ->post('https://speech-to-text-ai.p.rapidapi.com/transcribe', [
                    'url' => $audioUrl,
                    'task' => 'transcribe',
                    'lang' => 'auto'
                ]);

            if (!$response->ok()) {
                Log::error('STT: RapidAPI URL failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'url' => $audioUrl
                ]);

                return [
                    'success' => false,
                    'error' => 'STT service error: ' . $response->body()
                ];
            }

            $data = $response->json();
            $text = $data['text'] ?? '';

            Log::info('STT: URL transcription success', [
                'text_length' => strlen($text),
                'url' => $audioUrl
            ]);

            return [
                'success' => true,
                'data' => [
                    'text' => $text,
                    'language' => $data['language'] ?? 'auto',
                    'confidence' => (float)($data['confidence'] ?? 0.0),
                    'duration' => $data['duration'] ?? null,
                    'word_count' => $this->wordCount($text),
                    'provider' => 'rapidapi-url',
                    'processed_at' => now()->toISOString(),
                ],
            ];

        } catch (\Throwable $e) {
            Log::error('STT: URL transcription exception', [
                'message' => $e->getMessage(),
                'url' => $audioUrl
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function isLocalhostUrl(string $url): bool
    {
        return (bool)preg_match('/^(https?:\/\/)(localhost|127\.0\.0\.1)(:\d+)?\//i', $url);
    }
}
