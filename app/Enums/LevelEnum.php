<?php
namespace App\Enums;

enum LevelEnum: string
{
    case BEGINNER = 'beginner';
    case INTERMEDIATE = 'intermediate';
    case ADVANCED = 'advanced';

    public function getPrompt(): string
    {
        return match($this) {
            self::BEGINNER => 'Use simple words and basic grammar. Keep responses very short (5-7 words per sentence). Focus on present simple tense and basic vocabulary.',
            self::INTERMEDIATE => 'Use everyday vocabulary and common expressions. Medium length responses (10-12 words per sentence). Include some phrasal verbs and idioms.',
            self::ADVANCED => 'Use natural, fluent English with varied vocabulary and complex structures. Discuss topics in depth with nuance and sophistication.'
        };
    }

    public function getTemperature(): float
    {
        return match($this) {
            self::BEGINNER => 0.3,
            self::INTERMEDIATE => 0.5,
            self::ADVANCED => 0.7,
        };
    }

    public function getMaxTokens(): int
    {
        return match($this) {
            self::BEGINNER => 80,
            self::INTERMEDIATE => 120,
            self::ADVANCED => 150,
        };
    }
}
