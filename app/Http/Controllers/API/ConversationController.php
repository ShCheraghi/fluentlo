<?php

namespace App\Http\Controllers\API;

use App\Enums\LevelEnum;
use App\Facades\AI;
use App\Http\Controllers\Controller;
use App\Services\AI\ChatService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: 'conversation',
    description: 'English conversation learning API endpoints'
)]
class ConversationController extends Controller
{
    private ChatService $chatService;

    public function __construct(ChatService $chatService)
    {
        $this->chatService = $chatService;
    }

    /**
     * Start a new English conversation
     */
    #[OA\Post(
        path: '/v1/app/conversation/start',
        operationId: 'start_conversation',
        summary: 'Start a new English conversation',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['level'],
                properties: [
                    new OA\Property(
                        property: 'level',
                        type: 'string',
                        enum: ['beginner', 'intermediate', 'advanced'],
                        description: 'English proficiency level',
                        example: 'beginner'
                    ),
                ]
            )
        ),
        tags: ['conversation'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Conversation started successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Conversation started successfully'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'conversation_id', type: 'string', example: 'conv_1_abc123def456'),
                                new OA\Property(property: 'message', type: 'string', example: 'Hi! I\'m your English helper. What do you want to talk about?'),
                                new OA\Property(property: 'translation', type: 'string', example: 'سلام! من کمک‌کننده انگلیسی شما هستم. دوست داری در مورد چی صحبت کنیم؟'),
                                new OA\Property(property: 'level', type: 'string', example: 'beginner'),
                                new OA\Property(property: 'expires_at', type: 'string', format: 'date-time', example: '2025-09-14T12:00:00Z'),
                            ],
                            type: 'object'
                        )
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 500, description: 'Server error')
        ]
    )]
    public function start(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'level' => ['required', Rule::enum(LevelEnum::class)]
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $level = LevelEnum::from($request->level);
            $userId = $request->user()->id;

            $result = $this->chatService->startConversation($userId, $level);

            return response()->json([
                'success' => true,
                'message' => 'Conversation started successfully',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to start conversation', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to start conversation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send a message to the conversation
     */
    #[OA\Post(
        path: '/v1/app/conversation/message',
        operationId: 'send_message',
        summary: 'Send a message to the conversation',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['conversation_id', 'message'],
                properties: [
                    new OA\Property(
                        property: 'conversation_id',
                        description: 'Conversation ID',
                        type: 'string',
                        example: 'conv_1_abc123def456'
                    ),
                    new OA\Property(
                        property: 'message',
                        description: 'Text message',
                        type: 'string',
                        example: 'Hello, how are you today?'
                    ),
                ]
            )
        ),
        tags: ['conversation'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Message sent successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Message sent successfully'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'conversation_id', type: 'string', example: 'conv_1_abc123def456'),
                                new OA\Property(property: 'message', type: 'string', example: 'I\'m doing great! What about you?'),
                                new OA\Property(property: 'translation', type: 'string', example: 'من خیلی خوبم! تو چطوری؟'),
                                new OA\Property(property: 'level', type: 'string', example: 'beginner'),
                            ],
                            type: 'object'
                        )
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Conversation not found or expired'),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 500, description: 'Server error')
        ]
    )]
    public function message(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'conversation_id' => ['required', 'string', 'min:5'],
            'message' => ['required', 'string', 'max:500']
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->chatService->sendMessage(
                $request->conversation_id,
                $request->message
            );

            return response()->json([
                'success' => true,
                'message' => 'Message sent successfully',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send message', [
                'conversation_id' => $request->conversation_id,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send message',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Convert audio to text (URL or file)
     */
    #[OA\Post(
        path: '/v1/app/conversation/transcribe',
        operationId: 'transcribe_audio',
        summary: 'Convert audio to text (URL or file)',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['input_type'],
                    properties: [
                        new OA\Property(
                            property: 'input_type',
                            type: 'string',
                            enum: ['url', 'file'],
                            description: 'Type of audio input',
                            example: 'url'
                        ),
                        new OA\Property(
                            property: 'url',
                            type: 'string',
                            format: 'uri',
                            description: 'Audio file URL (required when input_type is url)',
                            example: 'https://example.com/audio.mp3'
                        ),
                        new OA\Property(
                            property: 'audio',
                            type: 'string',
                            format: 'binary',
                            description: 'Audio file (required when input_type is file)'
                        ),
                        new OA\Property(
                            property: 'lang',
                            type: 'string',
                            description: 'Language code for transcription',
                            example: 'en',
                            default: 'en'
                        ),
                        new OA\Property(
                            property: 'task',
                            type: 'string',
                            enum: ['transcribe', 'translate'],
                            description: 'Task type (transcribe or translate)',
                            example: 'transcribe',
                            default: 'transcribe'
                        ),
                    ]
                )
            )
        ),
        tags: ['conversation'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Audio transcribed successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Audio transcribed successfully'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'text', type: 'string', example: 'Hello, how are you today?'),
                                new OA\Property(property: 'confidence', type: 'number', format: 'float', example: 0.95),
                                new OA\Property(property: 'duration', type: 'number', format: 'float', example: 5.2, nullable: true),
                            ],
                            type: 'object'
                        ),
                        new OA\Property(
                            property: 'meta',
                            properties: [
                                new OA\Property(property: 'driver', type: 'string', example: 'rapidapi'),
                                new OA\Property(property: 'elapsed_ms', type: 'integer', example: 1250),
                                new OA\Property(property: 'started_at', type: 'string', format: 'date-time', example: '2025-09-13T10:30:00Z'),
                                new OA\Property(property: 'finished_at', type: 'string', format: 'date-time', example: '2025-09-13T10:30:01.250Z'),
                                new OA\Property(property: 'language', type: 'string', example: 'en'),
                                new OA\Property(property: 'input_type', type: 'string', example: 'url'),
                            ],
                            type: 'object'
                        )
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 500, description: 'Server error')
        ]
    )]
    public function transcribe(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'input_type' => ['required', 'in:url,file'],
            'url' => ['required_if:input_type,url', 'url', 'max:2048'],
            'audio' => ['required_if:input_type,file', 'file', 'max:10240', 'mimes:mp3,wav,ogg,m4a,flac'], // کاهش حجم فایل به 10MB
            'lang' => ['nullable', 'string', 'max:5'],
            'task' => ['nullable', 'string', 'in:transcribe,translate'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $t0 = microtime(true);
            $startedAt = CarbonImmutable::createFromTimestamp((int)$t0)->toISOString();

            if ($request->input_type === 'url') {
                $result = AI::driver('rapidapi')->transcribe([
                    'url' => $request->url,
                    'lang' => $request->lang ?? 'en',
                    'task' => $request->task ?? 'transcribe',
                ]);
            } else {
                $file = $request->file('audio');

                if (!$file->isValid()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid audio file'
                    ], 422);
                }

                $result = AI::driver('rapidapi')->transcribe([
                    'file' => $file->getRealPath(),
                    'lang' => $request->lang ?? 'en',
                    'task' => $request->task ?? 'transcribe',
                ]);
            }

            $meta = [
                'driver' => 'rapidapi',
                'elapsed_ms' => (int)round((microtime(true) - $t0) * 1000),
                'started_at' => $startedAt,
                'finished_at' => now()->toISOString(),
                'language' => $result['language'] ?? $request->lang ?? 'en',
                'input_type' => $request->input_type,
            ];

            return response()->json([
                'success' => true,
                'message' => 'Audio transcribed successfully',
                'data' => [
                    'text' => $result['text'] ?? '',
                    'confidence' => $result['confidence'] ?? null,
                    'duration' => $result['duration'] ?? null,
                ],
                'meta' => $meta
            ]);

        } catch (\Exception $e) {
            Log::error('Transcription failed', [
                'input_type' => $request->input_type,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Transcription failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
