<?php

namespace App\Services\AI;

use App\Enums\LevelEnum;
use App\Facades\AI;
use App\Models\Conversation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ChatService
{
    public function startConversation(int $userId, LevelEnum $level): array
    {
        $conversationId = 'conv_' . $userId . '_' . Str::random(20);
        $expiresAt = now()->addHours(2);

        $conversationData = [
            'user_id'    => $userId,
            'level'      => $level->value,
            'history'    => [],
            'expires_at' => $expiresAt->toISOString(),
        ];

        Cache::put("conversation:{$conversationId}", $conversationData, $expiresAt);

        Conversation::create([
            'id'         => $conversationId,
            'user_id'    => $userId,
            'level'      => $level->value,
            'history'    => [],
            'expires_at' => $expiresAt,
        ]);

        $greeting = $this->getGreeting($level);

        return [
            'conversation_id' => $conversationId,
            'message'         => $greeting['en'],
            'translation'     => $greeting['fa'],
            'level'           => $level->value,
            'expires_at'      => $expiresAt->toISOString(), // ← NEW
        ];
    }


    private function getGreeting(LevelEnum $level): array
    {
        return match ($level) {
            LevelEnum::BEGINNER => [
                'en' => "Hi! What do you want to talk about?",
                'fa' => "سلام! دوست داری در مورد چی صحبت کنیم؟"
            ],
            LevelEnum::INTERMEDIATE => [
                'en' => "Hello! What topic interests you today?",
                'fa' => "سلام! چه موضوعی امروز بهت علاقه داره؟"
            ],
            LevelEnum::ADVANCED => [
                'en' => "Hello! What shall we discuss today?",
                'fa' => "سلام! امروز چه موضوعی را بحث کنیم؟"
            ]
        };
    }

    public function sendVoiceMessage(string $conversationId, string $audioPath): array
    {
        $transcription = $this->transcribeAudio($audioPath);

        if (!$transcription['success']) {
            throw new \Exception($transcription['error']);
        }

        return $this->sendMessage($conversationId, $transcription['text']);
    }

    private function transcribeAudio(string $audioPath): array
    {
        try {
            $result = AI::driver('rapidapi_stt')->transcribe([
                'file' => $audioPath,
                'lang' => $lang ?? config('ai.drivers.rapidapi_stt.default_lang', 'en'),
            ]);

            return [
                'success' => true,
                'text' => $result['text'] ?? ''
            ];

        } catch (\Exception $e) {
            Log::error('Transcription failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function sendMessage(string $conversationId, string $message): array
    {
        $conversation = $this->getConversation($conversationId);
        $level = LevelEnum::from($conversation['level']);

        $messages = $this->prepareMessages($conversation['history'], $message, $level);
        $aiResponse = $this->callAI($messages);
        $processed = $this->processResponse($aiResponse);

        $newEntry = [
            'user' => $message,
            'ai' => $processed['message'],
            'translation' => $processed['translation'],
            'timestamp' => now()->toISOString()
        ];

        $conversation['history'][] = $newEntry;

        if (count($conversation['history']) > 10) {
            $conversation['history'] = array_slice($conversation['history'], -10);
        }

        $this->saveConversation($conversationId, $conversation);

        return [
            'conversation_id' => $conversationId,
            'message' => $processed['message'],
            'translation' => $processed['translation'],
            'level' => $level->value
        ];
    }

    private function getConversation(string $conversationId): array
    {
        $conversation = Cache::get("conversation:{$conversationId}");

        if (!$conversation) {
            $dbRecord = Conversation::where('id', $conversationId)
                ->where('expires_at', '>', now())
                ->firstOrFail();

            $conversation = [
                'user_id' => $dbRecord->user_id,
                'level' => $dbRecord->level,
                'history' => $dbRecord->history,
                'expires_at' => $dbRecord->expires_at->toISOString()
            ];

            Cache::put("conversation:{$conversationId}", $conversation, $dbRecord->expires_at);
        }

        return $conversation;
    }

    private function prepareMessages(array $history, string $newMessage, LevelEnum $level): array
    {
        $systemPrompt = $this->buildSystemPrompt($level);
        $messages = [['role' => 'system', 'content' => $systemPrompt]];

        // فقط 5 پیام آخر برای کاهش توکن
        foreach (array_slice($history, -5) as $entry) {
            $messages[] = ['role' => 'user', 'content' => $entry['user']];
            $messages[] = ['role' => 'assistant', 'content' => $entry['ai']];
        }

        $messages[] = ['role' => 'user', 'content' => $newMessage];
        return $messages;
    }

    private function buildSystemPrompt(LevelEnum $level): string
    {
        $levelInstructions = match ($level) {
            LevelEnum::BEGINNER => "Use simple words and short sentences. Correct major mistakes only.",
            LevelEnum::INTERMEDIATE => "Use everyday vocabulary. Correct grammar mistakes gently.",
            LevelEnum::ADVANCED => "Use natural, varied language. Provide detailed corrections."
        };

        return "You are an English tutor for Persian speakers.

{$levelInstructions}

Always respond in this format:
[Your English response]
FA: [Complete Persian translation]

Keep responses conversational and encouraging.";
    }

    private function callAI(array $messages): array
    {
        try {
            return AI::driver('chatgpt26')->chat([
                'model' => config('ai.drivers.chatgpt26.default_model'),
                'messages' => $messages,
                'temperature' => 0.7,
            ]);

        } catch (\Exception $e) {
            Log::error('AI call failed', ['error' => $e->getMessage()]);

            return [
                'choices' => [[
                    'message' => [
                        'content' => "Sorry, I'm having trouble right now. Try again!\nFA: ببخشید، الان مشکل دارم. دوباره امتحان کن!"
                    ]
                ]]
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
            'translation' => 'ترجمه موجود نیست'
        ];
    }

    private function saveConversation(string $conversationId, array $conversation): void
    {
        Cache::put("conversation:{$conversationId}", $conversation,
            now()->parse($conversation['expires_at']));

        Conversation::where('id', $conversationId)->update([
            'history' => $conversation['history'],
            'updated_at' => now(),
        ]);
    }
}
