<?php

namespace App\Services\AI;

use App\Models\UserAssessment;

class AIPromptService
{
    public function buildPromptByAssessment(UserAssessment $assessment): string
    {
        $prompt = "You are an English language tutor. The user has completed an initial assessment with the following profile:\n\n";

        $prompt .= "Target Language: {$assessment->target_language}\n";
        $prompt .= "Native Language: {$assessment->native_language}\n";
        $prompt .= "Proficiency Level: {$assessment->self_level}\n";
        $prompt .= "Learning Motivations: " . implode(', ', $assessment->motivations) . "\n";
        $prompt .= "Interests: " . implode(', ', $assessment->topics) . "\n";
        $prompt .= "Areas to Improve: " . implode(', ', $assessment->improve_areas) . "\n";
        $prompt .= "Learning Timeline: {$assessment->timeline}\n";
        $prompt .= "Daily Goal: {$assessment->daily_words}\n\n";

        $prompt .= "Instructions:\n";
        $prompt .= "- Adapt your teaching style to their proficiency level\n";
        $prompt .= "- Incorporate their interests into conversations\n";
        $prompt .= "- Focus on their specific improvement areas\n";
        $prompt .= "- Keep conversations engaging and relevant to their goals\n";
        $prompt .= "- For beginners: Use simple sentences and basic vocabulary\n";
        $prompt .= "- For advanced learners: Use more complex structures and idioms\n";

        return $prompt;
    }
}
