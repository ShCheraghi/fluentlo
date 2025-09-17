<?php
namespace App\Services\AI;

use App\Enums\LevelEnum;
use App\Facades\AI;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

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

        // ذخیره در کش با TTL خودکار (استفاده از Cache facade به جای Redis)
        Cache::put(
            "conversation:{$conversationId}",
            $conversationData,
            now()->addHours(2) // 2 ساعت
        );

        // همچنین می‌توانید در پایگاه داده ذخیره کنید برای پایداری بیشتر
        DB::table('conversations')->insert([
            'id' => $conversationId,
            'user_id' => $userId,
            'level' => $level->value,
            'data' => json_encode($conversationData),
            'created_at' => now(),
            'updated_at' => now(),
            'expires_at' => now()->addHours(2)
        ]);

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
        // بازیابی از کش (استفاده از Cache facade به جای Redis)
        $conversation = Cache::get("conversation:{$conversationId}");

        // اگر در کش نبود، از پایگاه داده بازیابی کن
        if (!$conversation) {
            $dbRecord = DB::table('conversations')
                ->where('id', $conversationId)
                ->where('expires_at', '>', now())
                ->first();

            if (!$dbRecord) {
                throw new \Exception('Conversation not found or expired');
            }

            $conversation = json_decode($dbRecord->data, true);
            // دوباره در کش قرار بده برای دسترسی سریع‌تر در آینده
            Cache::put(
                "conversation:{$conversationId}",
                $conversation,
                now()->addHours(2)
            );
        }

        $level = LevelEnum::from($conversation['level']);

        // بهینه‌سازی: کاهش تعداد پیام‌های ارسالی به AI
        $messages = $this->prepareMessages($conversation['history'], $message, $level);

        // استفاده از کش برای پاسخ‌های تکراری (استفاده از Cache facade به جای Redis)
        $cacheKey = 'ai_response:' . md5(json_encode($messages));
        $aiResponse = Cache::remember($cacheKey, now()->addMinutes(5), function() use ($messages, $level) {
            return $this->callAI($messages, $level);
        });

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

        // به‌روزرسانی کش با تمدید TTL (استفاده از Cache facade به جای Redis)
        Cache::put(
            "conversation:{$conversationId}",
            $conversation,
            now()->addHours(2)
        );

        // به‌روزرسانی پایگاه داده
        DB::table('conversations')
            ->where('id', $conversationId)
            ->update([
                'data' => json_encode($conversation),
                'updated_at' => now()
            ]);

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

            // استفاده از Cache facade با remember برای کش کردن نتایج
            return Cache::remember($cacheKey, now()->addHour(), function() use ($audioPath) {
                // استفاده از AI Manager بجای مستقیم API call
                $result = AI::driver('rapidapi')->transcribe([
                    'file' => $audioPath,
                    'lang' => 'en',
                    'task' => 'transcribe'
                ]);

                return [
                    'success' => true,
                    'text' => $result['text'] ?? ''
                ];
            });

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
