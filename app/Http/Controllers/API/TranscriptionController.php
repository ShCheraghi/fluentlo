<?php

namespace App\Http\Controllers\API;

use App\Facades\AI;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

#[OA\Tag(
    name: 'transcription',
    description: 'Audio transcription endpoints (URL and file)'
)]
class TranscriptionController extends BaseController
{
    #[OA\Post(
        path: '/v1/app/ai/transcription/url',
        operationId: 'transcribe_from_url',
        summary: 'Transcribe audio from URL',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['url'],
                properties: [
                    new OA\Property(property: 'url', type: 'string', format: 'uri', example: 'https://example.com/audio.mp3'),
                    new OA\Property(property: 'lang', type: 'string', example: 'en', description: 'Language code (default: en)'),
                    new OA\Property(property: 'task', type: 'string', example: 'transcribe', description: 'Task type (default: transcribe)'),
                ],
                type: 'object'
            )
        ),
        tags: ['transcription'],
        responses: [
            new OA\Response(response: 200, description: 'Transcription successful'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 500, description: 'Server error')
        ]
    )]
    public function transcribeUrl(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'url' => ['required', 'url', 'max:2048'],
            'lang' => ['nullable', 'string', 'max:5'],
            'task' => ['nullable', 'string', 'in:transcribe,translate'],
        ], [], [
            'url' => __('validation.attributes.url'),
            'lang' => __('validation.attributes.language'),
            'task' => __('validation.attributes.task'),
        ]);

        if ($validator->fails()) {
            return $this->sendValidationError($validator->errors()->toArray());
        }

        try {
            $driver = 'rapidapi';
            $t0 = microtime(true);
            $startedAt = CarbonImmutable::createFromTimestamp((int)$t0)->toISOString();

            $result = AI::driver($driver)->transcribe([
                'url' => $request->input('url'),
                'lang' => $request->input('lang', 'en'),
                'task' => $request->input('task', 'transcribe'),
            ]);

            $elapsedMs = (int)round((microtime(true) - $t0) * 1000);
            $meta = [
                'driver' => $driver,
                'elapsed_ms' => $elapsedMs,            // کل زمان اجرای اکشن (میلی‌ثانیه)
                'started_at' => $startedAt,            // ISO8601
                'finished_at' => now()->toISOString(),  // ISO8601
                // اگر RapidAPI یا سرویس متن، duration خود فایل را برگرداند:
                'audio_duration_s' => $result['duration'] ?? null,
                'language' => $result['language'] ?? $request->input('lang', 'en'),
            ];

            return $this->sendResponse([
                'result' => $result,
                'meta' => $meta,
            ], 'transcription.completed');
        } catch (\Exception $e) {
            return $this->sendError(
                'transcription.failed',
                ['error' => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[OA\Post(
        path: '/v1/app/ai/transcription/file',
        operationId: 'transcribe_from_file',
        summary: 'Transcribe audio from uploaded file',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['audio'],
                    properties: [
                        new OA\Property(property: 'audio', type: 'string', format: 'binary', description: 'Audio file (mp3, wav, ogg, etc.)'),
                        new OA\Property(property: 'lang', type: 'string', example: 'en', description: 'Language code (default: en)'),
                        new OA\Property(property: 'task', type: 'string', example: 'transcribe', description: 'Task type (default: transcribe)'),
                    ]
                )
            )
        ),
        tags: ['transcription'],
        responses: [
            new OA\Response(response: 200, description: 'Transcription successful'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 500, description: 'Server error')
        ]
    )]
    public function transcribeFile(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'audio' => ['required', 'file', 'max:102400', 'mimes:mp3,wav,ogg,m4a,flac'], // Max 100MB
            'lang' => ['nullable', 'string', 'max:5'],
            'task' => ['nullable', 'string', 'in:transcribe,translate'],
        ], [],
            [
            'audio' => __('validation.attributes.audio_file'),
            'lang' => __('validation.attributes.language'),
            'task' => __('validation.attributes.task'),
        ]);

        if ($validator->fails()) {
            return $this->sendValidationError($validator->errors()->toArray());
        }

        try {
            $file = $request->file('audio');

            if (!$file->isValid()) {
                return $this->sendError(
                    'transcription.invalid_file',
                    ['error' => 'Uploaded file is not valid'],
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }

            $driver = 'rapidapi';
            $result = AI::driver($driver)->transcribe([
                'file' => $file->getRealPath(),
                'lang' => $request->input('lang', 'en'),
                'task' => $request->input('task', 'transcribe'),
            ]);

            $meta = [
                'driver' => $driver,
                'file_size' => $file->getSize(),
                'file_type' => $file->getMimeType(),
                'audio_duration_s' => $result['duration'] ?? null,
                'language' => $result['language'] ?? $request->input('lang', 'en'),
            ];

            return $this->sendResponse([
                'result' => $result,
                'meta' => $meta,
            ], 'transcription.completed');
        } catch (\Exception $e) {
            if (config('app.debug')) {
                return response()->json([
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ], 500);
            }
            return $this->sendError(
                'transcription.failed',
                ['error' => 'Unexpected server error'],
                500
            );
        }
    }
}
