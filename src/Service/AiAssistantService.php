<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class AiAssistantService
{
    private const GEMINI_URL = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent';
    private const MAX_RETRIES = 2;

    public function __construct(
        private string $geminiApiKey,
        private HttpClientInterface $httpClient,
    ) {
    }

    /**
     * Generate bio suggestions based on user-provided information.
     *
     * @return string[] Array of bio suggestions
     */
    public function generateBioSuggestions(string $userMessage, ?string $currentName = null): array
    {
        $systemPrompt = "You are a helpful assistant that generates professional bio suggestions for an education platform called UniLearn. "
            . "The user will describe themselves (skills, interests, experience, goals, etc.). "
            . "Based on their input, generate exactly 3 short, engaging bios (max 150 characters each). "
            . "Each bio should have a different tone: 1) Professional, 2) Friendly/Casual, 3) Creative. "
            . "Return ONLY a JSON array of 3 strings, no other text. Example: [\"Bio 1\", \"Bio 2\", \"Bio 3\"]";

        if ($currentName) {
            $systemPrompt .= "\nThe user's name is: " . $currentName;
        }

        try {
            $content = $this->callGemini($systemPrompt, $userMessage);

            // Clean potential markdown wrapping
            $content = trim($content);
            $content = preg_replace('/^```json\s*/i', '', $content);
            $content = preg_replace('/\s*```$/', '', $content);

            $suggestions = json_decode($content, true);

            if (is_array($suggestions) && count($suggestions) >= 1) {
                return array_slice($suggestions, 0, 3);
            }

            return [$content];
        } catch (\Exception $e) {
            return ['Unable to generate suggestions: ' . $e->getMessage()];
        }
    }

    /**
     * General chat response for profile assistance.
     */
    public function chat(string $userMessage, array $conversationHistory = []): string
    {
        $systemPrompt = "You are a friendly AI assistant on UniLearn, an education platform. "
            . "You help users edit their profile. You can help them write a bio, suggest improvements, "
            . "or answer questions about their profile. Keep responses concise and helpful. "
            . "If the user provides personal info (skills, interests, etc.), offer to generate bio suggestions. "
            . "When you suggest bios, always format them as a numbered list so the user can pick one.";

        try {
            return $this->callGemini($systemPrompt, $userMessage, $conversationHistory);
        } catch (\Exception $e) {
            return 'Sorry, I encountered an error. Please try again. (' . $e->getMessage() . ')';
        }
    }

    /**
     * Call the Gemini API (v1beta, gemini-2.0-flash) with retry on rate limit.
     */
    private function callGemini(string $systemPrompt, string $userMessage, array $conversationHistory = []): string
    {
        $contents = [];

        // Add conversation history
        foreach ($conversationHistory as $msg) {
            $role = ($msg['role'] === 'assistant') ? 'model' : 'user';
            $contents[] = [
                'role' => $role,
                'parts' => [['text' => $msg['content']]],
            ];
        }

        // Add current user message
        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => $userMessage]],
        ];

        $payload = [
            'system_instruction' => [
                'parts' => [['text' => $systemPrompt]],
            ],
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => 0.8,
                'maxOutputTokens' => 500,
            ],
        ];

        $lastException = null;

        for ($attempt = 0; $attempt <= self::MAX_RETRIES; $attempt++) {
            if ($attempt > 0) {
                sleep(5 * $attempt); // 5s, 10s backoff
            }

            try {
                $response = $this->httpClient->request('POST', self::GEMINI_URL, [
                    'query' => ['key' => $this->geminiApiKey],
                    'json' => $payload,
                    'timeout' => 30,
                ]);

                $statusCode = $response->getStatusCode();
            } catch (\Exception $e) {
                throw new \RuntimeException('Network error: ' . $e->getMessage());
            }

            if ($statusCode === 429) {
                $lastException = new \RuntimeException('AI service is temporarily busy. Please wait a moment and try again.');
                continue;
            }

            if ($statusCode >= 400) {
                $body = $response->getContent(false);
                throw new \RuntimeException("Gemini API error ($statusCode): $body");
            }

            $data = $response->toArray();

            if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                return $data['candidates'][0]['content']['parts'][0]['text'];
            }

            throw new \RuntimeException('Unexpected Gemini API response.');
        }

        throw $lastException ?? new \RuntimeException('Gemini API unavailable. Please try again later.');
    }
}
