<?php

namespace App\Service;

use App\Repository\SessionRepository;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AiAssistantService
{
    private const GEMINI_URL = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent';
    private const MAX_RETRIES = 2;

    public function __construct(
        private string $geminiApiKey,
        private HttpClientInterface $httpClient,
        private SessionRepository $sessionRepository,
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

        if ($currentName !== null && $currentName !== '') {
            $systemPrompt .= "\nThe user's name is: " . $currentName;
        }

        try {
            $content = $this->callGemini($systemPrompt, $userMessage);

            // Clean potential markdown wrapping
            $content = trim($content);
            $content = preg_replace('/^```json\s*/i', '', $content) ?? '';
            $content = preg_replace('/\s*```$/', '', $content) ?? '';

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
     * Query available tutoring sessions and return formatted results.
     */
    public function querySessions(string $query, ?string $category = null, ?float $maxPrice = null): array
    {
        // Determine query type
        $queryLower = strtolower($query);
        $findBestValue = str_contains($queryLower, 'best') || str_contains($queryLower, 'cheapest') || str_contains($queryLower, 'value');
        $showAll = str_contains($queryLower, 'available') || str_contains($queryLower, 'sessions') || str_contains($queryLower, 'all');

        if ($findBestValue) {
            $sessions = $this->sessionRepository->findBestValueSessions(5);
        } else {
            $sessions = $this->sessionRepository->findAvailableSessions($category, $maxPrice);
        }

        if (empty($sessions)) {
            return [
                'type' => 'no_sessions',
                'message' => 'I couldn\'t find any available tutoring sessions at the moment. Please check back later or try a different search!'
            ];
        }

        // Format sessions for display
        $formattedSessions = [];
        foreach ($sessions as $session) {
            $instructor = $session->getInstructor();
            $categoryObj = $session->getCategory();

            $formattedSessions[] = [
                'id' => $session->getId(),
                'title' => $session->getName(),
                'description' => $session->getSessionDescription(),
                'level' => $session->getLevel(),
                'price' => $session->getHourlyPrice(),
                'duration' => $session->getDuration(),
                'instructor' => $instructor ? $instructor->getFullName() : 'Unknown',
                'category' => $categoryObj ? $categoryObj->getName() : 'General',
                'startDate' => $session->getStartDate() ? $session->getStartDate()->format('M d, Y') : 'TBD',
                'availableFrom' => $session->getAvailableFrom() ? $session->getAvailableFrom()->format('h:i A') : null,
                'availableTo' => $session->getAvailableTo() ? $session->getAvailableTo()->format('h:i A') : null,
            ];
        }

        // Generate AI summary
        $sessionCount = count($formattedSessions);
        $priceRange = $this->calculatePriceRange($formattedSessions);

        return [
            'type' => 'sessions',
            'query_type' => $findBestValue ? 'best_value' : 'all_available',
            'count' => $sessionCount,
            'price_range' => $priceRange,
            'sessions' => $formattedSessions,
            'summary' => $this->generateSessionSummary($formattedSessions, $findBestValue)
        ];
    }

    /**
     * Calculate price range from sessions.
     */
    private function calculatePriceRange(array $sessions): array
    {
        $prices = array_column($sessions, 'price');
        $prices = array_filter($prices, fn($p) => $p !== null);

        if (empty($prices)) {
            return ['min' => 0, 'max' => 0, 'avg' => 0];
        }

        return [
            'min' => min($prices),
            'max' => max($prices),
            'avg' => round(array_sum($prices) / count($prices), 2)
        ];
    }

    /**
     * Generate a summary of the sessions found.
     */
    private function generateSessionSummary(array $sessions, bool $isBestValue): string
    {
        $count = count($sessions);
        if ($count === 0) {
            return 'No sessions available.';
        }

        $firstSession = $sessions[0];
        $price = $firstSession['price'] ?? 'N/A';
        $instructor = $firstSession['instructor'];
        $category = $firstSession['category'];

        if ($isBestValue) {
            return "I found {$count} great value sessions! The best deal is with **{$instructor}** teaching **{$category}** at \${$price}/hour.";
        }

        $priceRange = $this->calculatePriceRange($sessions);
        return "I found {$count} available sessions ranging from \${$priceRange['min']} to \${$priceRange['max']} per hour, with an average of \${$priceRange['avg']}.";
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
