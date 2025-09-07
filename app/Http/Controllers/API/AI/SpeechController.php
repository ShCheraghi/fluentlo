<?php

namespace App\Http\Controllers\API\AI;

use App\Http\Controllers\API\BaseController;
use App\Services\AI\SpeechToTextSimpleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'AI – Speech', description: 'Speech-to-Text (file OR URL)')]
class SpeechController extends BaseController
{
    public function __construct(private SpeechToTextSimpleService $stt)
    {
    }

    #[OA\Post(
        path: '/v1/app/ai/speech/transcribe',
        operationId: 'aiSpeechTranscribe',
        description: 'Send either an audio file (multipart) OR a public audio_url (JSON). If audio_url is provided, the file upload is ignored.',
        summary: 'Transcribe via file upload or public URL (RapidAPI)',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['audio_file'],
                    properties: [
                        new OA\Property(
                            property: 'audio_file',
                            description: 'Allowed: wav, mp3, m4a, ogg, webm, flac (<= 25MB)',
                            type: 'string',
                            format: 'binary'
                        )
                    ]
                )
            )
        ),
        tags: ['AI – Speech'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Transcription success',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Hello world'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'text', type: 'string', example: 'Hello world'),
                                new OA\Property(property: 'language', type: 'string', example: 'en'),
                                new OA\Property(property: 'confidence', type: 'number', format: 'float', example: 0.93),
                                new OA\Property(property: 'duration', type: 'number', format: 'float', nullable: true, example: 12.8),
                                new OA\Property(property: 'word_count', type: 'integer', example: 2),
                                new OA\Property(property: 'provider', type: 'string', example: 'rapidapi'),
                                new OA\Property(property: 'processed_at', type: 'string', example: '2025-09-05T10:00:00Z'),
                            ]
                        )
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 500, description: 'Server/STT error'),
        ]
    )]
    public function transcribe(Request $request): JsonResponse
    {
        // یک اندپوینت منعطف: اگر audio_url باشد، اولویت با URL است
        $validator = Validator::make($request->all(), [
            'audio_file' => 'required_without:audio_url|file|mimes:wav,mp3,m4a,ogg,webm,flac|max:25600',
            'audio_url' => [
                'sometimes',
                'required_without:audio_file',
                'url',
                'regex:/\.(wav|mp3|m4a|ogg|webm|flac)(\?.*)?$/i',
                'not_regex:/^(http:\/\/localhost|http:\/\/127\.0\.0\.1|https:\/\/localhost|https:\/\/127\.0\.0\.1)/i',
            ],
        ], [
            'audio_file.required_without' => 'Either audio_file or audio_url is required.',
            'audio_file.file' => 'Invalid audio file.',
            'audio_file.mimes' => 'Unsupported format. Allowed: wav, mp3, m4a, ogg, webm, flac.',
            'audio_file.max' => 'Max file size is 25MB.',
            'audio_url.required_without' => 'Either audio_url or audio_file is required.',
            'audio_url.url' => 'The audio_url must be a valid URL.',
            'audio_url.regex' => 'The audio_url must end with a supported audio extension.',
            'audio_url.not_regex' => 'Localhost/127.0.0.1 is not reachable by the STT provider.',
        ]);

        if ($validator->fails()) {
            return $this->sendValidationError($validator->errors()->toArray());
        }

        if ($request->filled('audio_url')) {
            $result = $this->stt->transcribeFromPublicUrl($request->string('audio_url'));
        } else {
            $result = $this->stt->transcribe($request->file('audio_file'));
        }

        if (!($result['success'] ?? false)) {
            return $this->sendError('stt.transcribe_failed', ['error' => $result['error'] ?? null], 500);
        }

        return $this->sendResponse([
            'text' => $result['data']['text'] ?? '',
            'language' => $result['data']['language'] ?? 'auto',
            'confidence' => $result['data']['confidence'] ?? 0.0,
            'duration' => $result['data']['duration'] ?? null,
            'word_count' => $result['data']['word_count'] ?? null,
            'provider' => $result['data']['provider'] ?? 'rapidapi',
            'processed_at' => $result['data']['processed_at'] ?? now()->toISOString(),
        ], $result['data']['text'] ?? '');
    }

    #[OA\Post(
        path: '/v1/app/ai/speech/transcribe/url',
        operationId: 'aiSpeechTranscribeFromUrl',
        description: 'Send a public audio_url via JSON. Do NOT send localhost/127.0.0.1.',
        summary: 'Transcribe from a PUBLIC audio URL (RapidAPI)',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['audio_url'],
                properties: [
                    new OA\Property(
                        property: 'audio_url',
                        description: 'Publicly reachable audio URL (mp3, wav, m4a, ogg, webm, flac)',
                        type: 'string',
                        example: 'https://cdn.openai.com/whisper/draft-20220913a/micro-machines.wav'
                    )
                ],
                type: 'object'
            )
        ),
        tags: ['AI – Speech'],
        responses: [
            new OA\Response(response: 200, description: 'Transcription success'),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 500, description: 'Server/STT error'),
        ]
    )]
    public function transcribeUrl(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'audio_url' => [
                'required',
                'url',
                'regex:/\.(wav|mp3|m4a|ogg|webm|flac)(\?.*)?$/i',
                'not_regex:/^(http:\/\/localhost|http:\/\/127\.0\.0\.1|https:\/\/localhost|https:\/\/127\.0\.0\.1)/i',
            ],
        ]);

        if ($validator->fails()) {
            return $this->sendValidationError($validator->errors()->toArray());
        }

        $result = $this->stt->transcribeFromPublicUrl($request->string('audio_url'));

        if (!($result['success'] ?? false)) {
            return $this->sendError('stt.transcribe_failed', ['error' => $result['error'] ?? null], 500);
        }

        return $this->sendResponse($result['data'], $result['data']['text'] ?? '');
    }
}
