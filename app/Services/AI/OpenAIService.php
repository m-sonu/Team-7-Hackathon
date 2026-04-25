<?php

namespace App\Services\AI;

use Exception;
use OpenAI\Laravel\Facades\OpenAI;

class OpenAIService
{
    /**
     * Extracts JSON data from raw text using a provided prompt.
     */
    public function extractJson(?string $rawText): array
    {
        $systemPrompt = view('ai-prompt.extract-resume-data')->render();

        $result = OpenAI::chat()->create([
            'model' => 'gpt-4o-mini',
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => "Text to process:\n\n".$rawText],
            ],
            'response_format' => ['type' => 'json_object'],
        ]);
        $content = $result->choices[0]->message->content;

        $decoded = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON from OpenAI');
        }

        return $decoded;
    }
}
