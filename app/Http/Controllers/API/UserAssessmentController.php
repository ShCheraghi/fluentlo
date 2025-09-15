<?php

namespace App\Http\Controllers\API;

use App\Models\UserAssessment;
use App\Services\AI\AIPromptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

#[OA\Tag(
    name: "User Assessment",
    description: "User language assessment management"
)]
class UserAssessmentController extends BaseController
{
    protected AiPromptService $aiPromptService;

    public function __construct(AiPromptService $aiPromptService)
    {
        $this->aiPromptService = $aiPromptService;
    }

    #[OA\Post(
        path: "/v1/app/assessment/user",
        operationId: "storeUserAssessment",
        summary: "Store user language assessment",
        security: [["sanctum" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["answers"],
                properties: [
                    new OA\Property(
                        property: "answers",
                        type: "array",
                        items: new OA\Items(
                        // هر عضو آرایه پاسخ باید یکی از این ۸ اسکیمـا باشد
                            oneOf: [
                                // 1) q_target_language (تک‌گزینه‌ای)
                                new OA\Schema(
                                    description: "سؤال 1: اینجا برای یادگیری چه زبانی هستی؟ (تک‌گزینه‌ای)",
                                    required: ["question_id", "option_ids"],
                                    properties: [
                                        new OA\Property(
                                            property: "question_id",
                                            type: "string",
                                            enum: ["q_target_language"],
                                            example: "q_target_language"
                                        ),
                                        new OA\Property(
                                            property: "option_ids",
                                            type: "array",
                                            items: new OA\Items(
                                                type: "string",
                                                enum: ["o_english", "o_spanish"],
                                                example: "o_english"
                                            ),
                                            maxItems: 1,
                                            minItems: 1
                                        ),
                                    ],
                                    type: "object"
                                ),

                                // 2) q_native_language (تک‌گزینه‌ای)
                                new OA\Schema(
                                    description: "سؤال 2: زبان مادری تو چیست؟ (تک‌گزینه‌ای)",
                                    required: ["question_id", "option_ids"],
                                    properties: [
                                        new OA\Property(
                                            property: "question_id",
                                            type: "string",
                                            enum: ["q_native_language"],
                                            example: "q_native_language"
                                        ),
                                        new OA\Property(
                                            property: "option_ids",
                                            type: "array",
                                            items: new OA\Items(
                                                type: "string",
                                                enum: [
                                                    "o_english",
                                                    "o_portuguese",
                                                    "o_spanish",
                                                    "o_indonesian",
                                                    "o_japanese",
                                                    "o_thai",
                                                    "o_arabic"
                                                ],
                                                example: "o_portuguese"
                                            ),
                                            maxItems: 1,
                                            minItems: 1
                                        ),
                                    ],
                                    type: "object"
                                ),

                                // 3) q_motivations (چندگزینه‌ای)
                                new OA\Schema(
                                    description: "سؤال 3: چه چیزی بیشتر تو را برای یادگیری انگلیسی انگیزه می‌دهد؟ (چندگزینه‌ای)",
                                    required: ["question_id", "option_ids"],
                                    properties: [
                                        new OA\Property(
                                            property: "question_id",
                                            type: "string",
                                            enum: ["q_motivations"],
                                            example: "q_motivations"
                                        ),
                                        new OA\Property(
                                            property: "option_ids",
                                            type: "array",
                                            items: new OA\Items(
                                                type: "string",
                                                enum: [
                                                    "o_understand_better",   // دیگران را بهتر درک کنم
                                                    "o_express_better",      // خودم را بهتر بیان کنم
                                                    "o_enjoy_reading",       // لذت بردن از مطالعه
                                                    "o_improve_study",       // تقویت درس و تحصیل
                                                    "o_advance_career",      // پیشرفت در شغل
                                                    "o_learn_for_fun",       // یادگیری برای سرگرمی
                                                    "o_teach_child"          // آموزش به فرزند
                                                ],
                                                example: "o_express_better"
                                            ),
                                            minItems: 1,
                                            uniqueItems: true
                                        ),
                                    ],
                                    type: "object"
                                ),

                                // 4) q_topics (چندگزینه‌ای)
                                new OA\Schema(
                                    description: "سؤال 4: به چه موضوعاتی علاقه داری؟ (چندگزینه‌ای)",
                                    required: ["question_id", "option_ids"],
                                    properties: [
                                        new OA\Property(
                                            property: "question_id",
                                            type: "string",
                                            enum: ["q_topics"],
                                            example: "q_topics"
                                        ),
                                        new OA\Property(
                                            property: "option_ids",
                                            type: "array",
                                            items: new OA\Items(
                                                type: "string",
                                                enum: [
                                                    "o_culture",
                                                    "o_dating",
                                                    "o_shopping",
                                                    "o_food",
                                                    "o_family"
                                                ],
                                                example: "o_culture"
                                            ),
                                            minItems: 1,
                                            uniqueItems: true
                                        ),
                                    ],
                                    type: "object"
                                ),

                                // 5) q_self_level (تک‌گزینه‌ای)
                                new OA\Schema(
                                    description: "سؤال 5: سطح انگلیسی خودت را چطور ارزیابی می‌کنی؟ (تک‌گزینه‌ای)",
                                    required: ["question_id", "option_ids"],
                                    properties: [
                                        new OA\Property(
                                            property: "question_id",
                                            type: "string",
                                            enum: ["q_self_level"],
                                            example: "q_self_level"
                                        ),
                                        new OA\Property(
                                            property: "option_ids",
                                            type: "array",
                                            items: new OA\Items(
                                                type: "string",
                                                enum: [
                                                    "o_pre_beginner",
                                                    "o_beginner",
                                                    "o_elementary",
                                                    "o_intermediate",
                                                    "o_upper_intermediate"
                                                ],
                                                example: "o_pre_beginner"
                                            ),
                                            maxItems: 1,
                                            minItems: 1
                                        ),
                                    ],
                                    type: "object"
                                ),

                                // 6) q_improve_areas (چندگزینه‌ای)
                                new OA\Schema(
                                    description: "سؤال 6: در انگلیسی چه چیزی را بیشتر می‌خواهی بهبود بدهی؟ (چندگزینه‌ای)",
                                    required: ["question_id", "option_ids"],
                                    properties: [
                                        new OA\Property(
                                            property: "question_id",
                                            type: "string",
                                            enum: ["q_improve_areas"],
                                            example: "q_improve_areas"
                                        ),
                                        new OA\Property(
                                            property: "option_ids",
                                            type: "array",
                                            items: new OA\Items(
                                                type: "string",
                                                enum: [
                                                    "o_vocabulary",     // یاد گرفتن کلمات و عبارت‌های جدید
                                                    "o_confidence",     // اعتمادبه‌نفس در صحبت
                                                    "o_situations",     // تمرین در موقعیت‌های خاص
                                                    "o_pronunciation",  // بهبود تلفظ
                                                    "o_accuracy",       // کمتر اشتباه کردن
                                                    "o_listening"       // بهبود مهارت شنیداری
                                                ],
                                                example: "o_confidence"
                                            ),
                                            minItems: 1,
                                            uniqueItems: true
                                        ),
                                    ],
                                    type: "object"
                                ),

                                // 7) q_timeline (تک‌گزینه‌ای)
                                new OA\Schema(
                                    description: "سؤال 7: برای رسیدن به اهداف زبانت چه بازه زمانی در نظر داری؟ (تک‌گزینه‌ای)",
                                    required: ["question_id", "option_ids"],
                                    properties: [
                                        new OA\Property(
                                            property: "question_id",
                                            type: "string",
                                            enum: ["q_timeline"],
                                            example: "q_timeline"
                                        ),
                                        new OA\Property(
                                            property: "option_ids",
                                            type: "array",
                                            items: new OA\Items(
                                                type: "string",
                                                enum: [
                                                    "o_months",
                                                    "o_year",
                                                    "o_as_long_as_it_takes"
                                                ],
                                                example: "o_months"
                                            ),
                                            maxItems: 1,
                                            minItems: 1
                                        ),
                                    ],
                                    type: "object"
                                ),

                                // 8) q_daily_words (تک‌گزینه‌ای)
                                new OA\Schema(
                                    description: "سؤال 8: روزانه می‌خواهی چند کلمه جدید یاد بگیری؟ (تک‌گزینه‌ای)",
                                    required: ["question_id", "option_ids"],
                                    properties: [
                                        new OA\Property(
                                            property: "question_id",
                                            type: "string",
                                            enum: ["q_daily_words"],
                                            example: "q_daily_words"
                                        ),
                                        new OA\Property(
                                            property: "option_ids",
                                            type: "array",
                                            items: new OA\Items(
                                                type: "string",
                                                enum: [
                                                    "o_1_2",
                                                    "o_3_5",
                                                    "o_6_10",
                                                    "o_more_the_better"
                                                ],
                                                example: "o_1_2"
                                            ),
                                            maxItems: 1,
                                            minItems: 1
                                        ),
                                    ],
                                    type: "object"
                                ),
                            ]
                        )
                    )
                ],
                type: "object",

                example: [
                    "answers" => [
                        ["question_id" => "q_target_language", "option_ids" => ["o_english"]],
                        ["question_id" => "q_native_language", "option_ids" => ["o_portuguese"]],
                        ["question_id" => "q_motivations", "option_ids" => ["o_express_better", "o_understand_better", "o_enjoy_reading"]],
                        ["question_id" => "q_topics", "option_ids" => ["o_culture", "o_dating"]],
                        ["question_id" => "q_self_level", "option_ids" => ["o_pre_beginner"]],
                        ["question_id" => "q_improve_areas", "option_ids" => ["o_confidence", "o_situations"]],
                        ["question_id" => "q_timeline", "option_ids" => ["o_months"]],
                        ["question_id" => "q_daily_words", "option_ids" => ["o_1_2"]],
                    ]
                ]
            )
        ),
        tags: ["User Assessment"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Assessment saved successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "assessment.saved"),
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(property: "id", type: "integer", example: 1),
                                new OA\Property(property: "user_id", type: "integer", example: 1),
                                new OA\Property(property: "target_language", type: "string", example: "English"),
                                new OA\Property(property: "native_language", type: "string", example: "Persian"),
                                new OA\Property(
                                    property: "motivations",
                                    type: "array",
                                    items: new OA\Items(type: "string", example: "Express myself better")
                                ),
                                new OA\Property(
                                    property: "topics",
                                    type: "array",
                                    items: new OA\Items(type: "string", example: "Culture")
                                ),
                                new OA\Property(property: "self_level", type: "string", example: "Beginner"),
                                new OA\Property(
                                    property: "improve_areas",
                                    type: "array",
                                    items: new OA\Items(type: "string", example: "Build confidence in speaking")
                                ),
                                new OA\Property(property: "timeline", type: "string", example: "Few months"),
                                new OA\Property(property: "daily_words", type: "string", example: "1-2 words"),
                                new OA\Property(property: "created_at", type: "string", format: "date-time"),
                                new OA\Property(property: "updated_at", type: "string", format: "date-time")
                            ],
                            type: "object",
                        )
                    ],
                    type: "object"
                )
            ),
            new OA\Response(
                response: 422,
                description: "Validation error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string", example: "validation.failed"),
                        new OA\Property(
                            property: "errors",
                            type: "object",
                            example: ['answers.0.question_id' => ['The question_id field is required.']]
                        )
                    ],
                    type: "object"
                )
            )
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'answers' => ['required', 'array'],
            'answers.*.question_id' => ['required', 'string'],
            'answers.*.option_ids' => ['required', 'array', 'min:1'],
            'answers.*.option_ids.*' => ['string'],
        ]);

        if ($validator->fails()) {
            return $this->sendValidationError($validator->errors()->toArray());
        }

        $user = $request->user();
        $assessmentData = $this->processAnswers($request->input('answers'));

        $assessment = UserAssessment::updateOrCreate(
            ['user_id' => $user->id],
            $assessmentData
        );

        return $this->sendResponse($assessment, 'assessment.saved');
    }

    private function processAnswers(array $answers): array
    {
        $mapping = [
            'q_target_language' => 'target_language',
            'q_native_language' => 'native_language',
            'q_motivations' => 'motivations',
            'q_topics' => 'topics',
            'q_self_level' => 'self_level',
            'q_improve_areas' => 'improve_areas',
            'q_timeline' => 'timeline',
            'q_daily_words' => 'daily_words',
        ];

        $result = [];

        foreach ($answers as $answer) {
            $field = $mapping[$answer['question_id']] ?? null;
            if (!$field) {
                continue;
            }

            // تک‌گزینه‌ای
            if (in_array($field, ['target_language', 'native_language', 'self_level', 'timeline', 'daily_words'], true)) {
                $result[$field] = $this->mapOptionToValue($answer['option_ids'][0]);
            } // چندگزینه‌ای
            else {
                $result[$field] = array_map([$this, 'mapOptionToValue'], $answer['option_ids']);
            }
        }

        return $result;
    }

    private function mapOptionToValue(string $optionId): string
    {
        $mapping = [
            'o_english' => 'English',
            'o_portuguese' => 'Portuguese',
            'o_spanish' => 'Spanish',
            'o_indonesian' => 'Indonesian',
            'o_japanese' => 'Japanese',
            'o_thai' => 'Thai',
            'o_arabic' => 'Arabic',

            'o_understand_better' => 'Understand others better',
            'o_express_better' => 'Express myself better',
            'o_enjoy_reading' => 'Enjoy reading',
            'o_improve_study' => 'Improve my studies',
            'o_advance_career' => 'Advance my career',
            'o_learn_for_fun' => 'Learn for fun',
            'o_teach_child' => 'Teach my child',

            'o_culture' => 'Culture',
            'o_dating' => 'Dating',
            'o_shopping' => 'Shopping',
            'o_food' => 'Food',
            'o_family' => 'Family',

            'o_pre_beginner' => 'Pre-beginner',
            'o_beginner' => 'Beginner',
            'o_elementary' => 'Elementary',
            'o_intermediate' => 'Intermediate',
            'o_upper_intermediate' => 'Upper-intermediate',

            'o_vocabulary' => 'Learn new words and phrases',
            'o_confidence' => 'Build confidence in speaking',
            'o_situations' => 'Practice in specific situations',
            'o_pronunciation' => 'Improve pronunciation',
            'o_accuracy' => 'Make fewer mistakes when speaking',
            'o_listening' => 'Improve listening skills',

            'o_months' => 'Few months',
            'o_year' => 'One year',
            'o_as_long_as_it_takes' => 'As long as it takes',

            'o_1_2' => '1-2 words',
            'o_3_5' => '3-5 words',
            'o_6_10' => '6-10 words',
            'o_more_the_better' => 'The more the better',
        ];

        return $mapping[$optionId] ?? $optionId;
    }

    #[OA\Get(
        path: "/v1/app/assessment/user",
        operationId: "getUserAssessment",
        summary: "Get user language assessment with AI prompt",
        security: [["sanctum" => []]],
        tags: ["User Assessment"],
        responses: [
            new OA\Response(
                response: 200,
                description: "User assessment retrieved successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "assessment.retrieved"),
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(property: "id", type: "integer", example: 1),
                                new OA\Property(property: "user_id", type: "integer", example: 1),
                                new OA\Property(property: "target_language", type: "string", example: "English"),
                                new OA\Property(property: "native_language", type: "string", example: "Persian"),
                                new OA\Property(
                                    property: "motivations",
                                    type: "array",
                                    items: new OA\Items(type: "string", example: "Express myself better")
                                ),
                                new OA\Property(
                                    property: "topics",
                                    type: "array",
                                    items: new OA\Items(type: "string", example: "Culture")
                                ),
                                new OA\Property(property: "self_level", type: "string", example: "Beginner"),
                                new OA\Property(
                                    property: "improve_areas",
                                    type: "array",
                                    items: new OA\Items(type: "string", example: "Build confidence in speaking")
                                ),
                                new OA\Property(property: "timeline", type: "string", example: "Few months"),
                                new OA\Property(property: "daily_words", type: "string", example: "1-2 words"),
                                new OA\Property(property: "created_at", type: "string", format: "date-time"),
                                new OA\Property(property: "updated_at", type: "string", format: "date-time"),
                                new OA\Property(
                                    property: "ai_prompt",
                                    type: "string",
                                    example: "You are an English language tutor. The user has completed an initial assessment with the following profile:\n\nTarget Language: English\nNative Language: Persian\nProficiency Level: Beginner\nLearning Motivations: Express myself better\nInterests: Culture\nAreas to Improve: Build confidence in speaking\nLearning Timeline: Few months\nDaily Goal: 1-2 words\n\nInstructions:\n- Adapt your teaching style to their proficiency level\n- Incorporate their interests into conversations\n- Focus on their specific improvement areas\n- Keep conversations engaging and relevant to their goals\n- For beginners: Use simple sentences and basic vocabulary"
                                )
                            ],
                            type: "object",
                        )
                    ],
                    type: "object"
                )
            ),
            new OA\Response(
                response: 404,
                description: "Assessment not found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string", example: "assessment.not_found"),
                        new OA\Property(property: "data", type: "object", example: null)
                    ],
                    type: "object"
                )
            )
        ]
    )]
    public function show(Request $request): JsonResponse
    {
        $assessment = $request->user()->assessment;

        if (!$assessment) {
            return $this->sendError('assessment.not_found', [], Response::HTTP_NOT_FOUND);
        }

        $aiPrompt = $this->aiPromptService->buildPromptByAssessment($assessment);

        $responseData = $assessment->toArray();
        $responseData['ai_prompt'] = $aiPrompt;

        return $this->sendResponse($responseData, 'assessment.retrieved');
    }

    #[OA\Delete(
        path: "/v1/app/assessment/user",
        operationId: "deleteUserAssessment",
        summary: "Delete user language assessment",
        security: [["sanctum" => []]],
        tags: ["User Assessment"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Assessment deleted successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "assessment.deleted"),
                        new OA\Property(property: "data", type: "object", example: null)
                    ],
                    type: "object"
                )
            ),
            new OA\Response(
                response: 404,
                description: "Assessment not found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string", example: "assessment.not_found"),
                        new OA\Property(property: "data", type: "object", example: null)
                    ],
                    type: "object"
                )
            )
        ]
    )]
    public function destroy(Request $request): JsonResponse
    {
        $assessment = $request->user()->assessment;

        if (!$assessment) {
            return $this->sendError('assessment.not_found', [], Response::HTTP_NOT_FOUND);
        }

        $assessment->delete();

        return $this->sendResponse(null, 'assessment.deleted');
    }
}
