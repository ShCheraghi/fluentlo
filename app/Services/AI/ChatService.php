<?php
namespace App\Services\AI;

use App\Enums\LevelEnum;
use App\Facades\AI;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class ChatService
{
    public function startConversation(int $userId, LevelEnum $level): array
    {
        $conversationId = uniqid('conv_' . $userId . '_');
        $conversationData = [
            'user_id' => $userId,
            'level' => $level->value,
            'history' => [],
            'created_at' => now()->toISOString(),
            'expires_at' => now()->addHours(2)->toISOString()
        ];

        // ذخیره در Redis با TTL خودکار
        Redis::setex(
            "conversation:{$conversationId}",
            7200, // 2 ساعت به ثانیه
            json_encode($conversationData)
        );

        $greeting = $this->getGreeting($level);
        return [
            'conversation_id' => $conversationId,
            'message' => $greeting['en'],
            'translation' => $greeting['fa'],
            'level' => $level->value,
            'expires_at' => $conversationData['expires_at']
        ];
    }

    public function sendMessage(string $conversationId, string $message): array
    {
        // بازیابی از Redis
        $conversationJson = Redis::get("conversation:{$conversationId}");
        if (!$conversationJson) {
            throw new \Exception('Conversation not found or expired');
        }

        $conversation = json_decode($conversationJson, true);
        $level = LevelEnum::from($conversation['level']);

        // بهینه‌سازی: کاهش تعداد پیام‌های ارسالی به AI
        $messages = $this->prepareMessages($conversation['history'], $message, $level);

        // استفاده از کش برای پاسخ‌های تکراری
        $cacheKey = 'ai_response:' . md5(json_encode($messages));
        if (Redis::exists($cacheKey)) {
            $aiResponse = json_decode(Redis::get($cacheKey), true);
        } else {
            $aiResponse = $this->callAI($messages, $level);
            Redis::setex($cacheKey, 300, json_encode($aiResponse)); // کش 5 دقیقه‌ای
        }

        $processed = $this->processResponse($aiResponse);

        // بهینه‌سازی: محدود کردن تاریخچه
        $newEntry = [
            'user' => $message,
            'ai' => $processed['message'],
            'translation' => $processed['translation'],
            'timestamp' => now()->toISOString()
        ];

        $conversation['history'][] = $newEntry;
        if (count($conversation['history']) > 10) { // کاهش از 20 به 10
            $conversation['history'] = array_slice($conversation['history'], -10);
        }

        // به‌روزرسانی Redis با تمدید TTL
        Redis::setex(
            "conversation:{$conversationId}",
            7200,
            json_encode($conversation)
        );

        return [
            'conversation_id' => $conversationId,
            'message' => $processed['message'],
            'translation' => $processed['translation'],
            'level' => $level->value
        ];
    }

    public function sendVoiceMessage(string $conversationId, string $audioPath): array
    {
        $transcription = $this->transcribeAudio($audioPath);

        if (!$transcription['success']) {
            throw new \Exception('Could not transcribe audio: ' . $transcription['error']);
        }

        return $this->sendMessage($conversationId, $transcription['text']);
    }

    private function transcribeAudio(string $audioPath): array
    {
        try {
            $fileHash = md5_file($audioPath);
            $cacheKey = "voice_transcription:{$fileHash}";

            if (Cache::has($cacheKey)) {
                return Cache::get($cacheKey);
            }

            // استفاده از AI Manager بجای مستقیم API call
            $result = AI::driver('rapidapi')->transcribe([
                'file' => $audioPath,
                'lang' => 'en',
                'task' => 'transcribe'
            ]);

            $response = [
                'success' => true,
                'text' => $result['text'] ?? ''
            ];

            Cache::put($cacheKey, $response, now()->addHour());

            return $response;

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    private function getGreeting(LevelEnum $level): array
    {
        return match($level) {
            LevelEnum::BEGINNER => [
                'en' => "Hi! I'm your English helper. What do you want to talk about?",
                'fa' => "سلام! من کمک‌کننده انگلیسی شما هستم. دوست داری در مورد چی صحبت کنیم؟"
            ],
            LevelEnum::INTERMEDIATE => [
                'en' => "Hello! I'm your English tutor. What topic interests you today?",
                'fa' => "سلام! من معلم انگلیسی شما هستم. چه موضوعی امروز بهت علاقه داره؟"
            ],
            LevelEnum::ADVANCED => [
                'en' => "Greetings! I'm your English conversation partner. What shall we discuss today?",
                'fa' => "درود! من شریک مکالمه انگلیسی شما هستم. امروز چه موضوعی را بحث کنیم؟"
            ]
        };
    }

    private function prepareMessages(array $history, string $newMessage, LevelEnum $level): array
    {
        $systemPrompt = $this->buildSystemPrompt($level);

        $messages = [['role' => 'system', 'content' => $systemPrompt]];

        foreach (array_slice($history, -10) as $entry) {
            $messages[] = ['role' => 'user', 'content' => $entry['user']];
            $messages[] = ['role' => 'assistant', 'content' => $entry['ai']];
        }

        $messages[] = ['role' => 'user', 'content' => $newMessage];

        return $messages;
    }

    private function buildSystemPrompt(LevelEnum $level): string
    {
        $levelPrompt = $level->getPrompt();

        return <<<PROMPT
You are a friendly and patient English conversation tutor helping Persian speakers improve their English.

**Your Role:**
- Engage in natural, flowing conversation
- Help the user practice speaking English
- Be encouraging and positive
- Correct important mistakes gently

**Instructions for {$level->value} level:**
{$levelPrompt}

**Response Format:**
1. Always respond in natural English first
2. Keep your response appropriate for the user's level
3. If the user makes a significant mistake, gently correct it like this:
   "Suggested improvement: [corrected sentence]"
4. After your English response, always add a Persian translation in this exact format:
   FA: [complete Persian translation of your response]

**Important:**
- Never skip the Persian translation
- Keep the conversation engaging and natural
- Focus on helping the user improve, not just correcting
- Adapt your vocabulary and complexity to the user's level

**Example Response:**
That sounds interesting! Tell me more about it.
FA: جالب به نظر می‌رسه! بیشتر بهم بگو.
PROMPT;
    }

    private function callAI(array $messages, LevelEnum $level): array
    {
        try {
            // استفاده از AI Manager
            $response = AI::driver('rapidapi')->chat([
                'model' => 'gpt-4o',
                'messages' => $messages,
                'max_tokens' => $level->getMaxTokens(),
                'temperature' => $level->getTemperature(),
            ]);

            return $response;

        } catch (\Exception $e) {
            Log::error('AI API error', [
                'error' => $e->getMessage(),
                'level' => $level->value
            ]);

            return [
                'choices' => [
                    [
                        'message' => [
                            'content' => "I'm having trouble responding right now. Let's try again later.\nFA: الان مشکل در پاسخگویی دارم. لطفاً بعداً دوباره امتحان کن."
                        ]
                    ]
                ]
            ];
        }
    }

    private function processResponse(array $response): array
    {
        $content = $response['choices'][0]['message']['content'] ?? '';

        if (strpos($content, 'FA:') !== false) {
            $parts = explode('FA:', $content, 2);
            return [
                'message' => trim($parts[0]),
                'translation' => trim($parts[1] ?? '')
            ];
        }

        return [
            'message' => trim($content),
            'translation' => 'ترجمه در دسترس نیست'
        ];
    }
}
