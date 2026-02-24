<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class VertexApiService
{
    private HttpClientInterface $client;
    private LoggerInterface $logger;
    private string $apiKey;
    private string $projectId;
    private string $location;
    private string $model;

    public function __construct(
        HttpClientInterface $client, 
        LoggerInterface $logger,
        string $vertexApiKey,
        string $vertexProjectId,
        string $vertexLocation = 'us-central1',
        string $vertexModel = 'gemini-1.5-flash'
    ) {
        $this->client = $client;
        $this->logger = $logger;
        $this->apiKey = $vertexApiKey;
        $this->projectId = $vertexProjectId;
        $this->location = $vertexLocation;
        $this->model = $vertexModel;
    }

    /**
     * Generate questions using Vertex AI
     *
     * @param int $amount Number of questions to generate
     * @param string $category Category of questions
     * @param string $difficulty Difficulty level
     * @param string $type Question type (multiple/boolean)
     * @return array Response with success status and generated questions
     */
    public function generateQuestions(int $amount, string $category = null, string $difficulty = null, string $type = null): array
    {
        try {
            // Build the prompt for Vertex AI
            $prompt = $this->buildPrompt($amount, $category, $difficulty, $type);
            
            // Call Vertex AI API
            $response = $this->callVertexAI($prompt);
            
            // Parse the response
            $questions = $this->parseAIResponse($response, $type);
            
            return [
                'success' => true,
                'results' => $questions,
                'amount' => count($questions)
            ];

        } catch (\Exception $e) {
            $this->logger->error('Vertex AI Error: ' . $e->getMessage(), [
                'exception' => $e,
                'amount' => $amount,
                'category' => $category,
                'difficulty' => $difficulty,
                'type' => $type
            ]);

            return [
                'success' => false,
                'error' => 'Failed to generate questions using AI. Please try again later.',
                'details' => $e->getMessage()
            ];
        }
    }

    /**
     * Build the prompt for Vertex AI based on parameters
     */
    private function buildPrompt(int $amount, string $category = null, string $difficulty = null, string $type = null): string
    {
        $prompt = "Generate {$amount} educational quiz questions";
        
        if ($category) {
            $prompt .= " in the category of {$category}";
        }
        
        if ($difficulty) {
            $prompt .= " with {$difficulty} difficulty level";
        }
        
        if ($type) {
            $prompt .= " in {$type} choice format";
        }
        
        $prompt .= ". 

Please format each question as a JSON object with the following structure:
{
    \"question\": \"The question text\",
    \"category\": \"Category name\",
    \"difficulty\": \"easy|medium|hard\",
    \"type\": \"multiple|boolean\",
    \"correct_option\": \"Correct answer\",
    \"incorrect_answers\": [\"Wrong answer 1\", \"Wrong answer 2\", \"Wrong answer 3\"]
}

For boolean questions, use \"True\" or \"False\" as correct_option and an empty array for incorrect_answers.

Return only a valid JSON array of these question objects. No additional text or explanation.";

        return $prompt;
    }

    /**
     * Call Vertex AI API using Gemini API (simplified)
     */
    private function callVertexAI(string $prompt): array
    {
        // Use Gemini API directly (simpler authentication)
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        [
                            'text' => $prompt
                        ]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.7,
                'maxOutputTokens' => 2048,
                'candidateCount' => 1
            ]
        ];

        $response = $this->client->request('POST', $url, [
            'headers' => [
                'Content-Type' => 'application/json'
            ],
            'json' => $payload,
            'timeout' => 60
        ]);

        $data = $response->toArray();
        
        if (!isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            throw new \Exception('Invalid response from Gemini API');
        }

        return $data;
    }

    /**
     * Parse AI response and format questions
     */
    private function parseAIResponse(array $response, string $type = null): array
    {
        $text = $response['candidates'][0]['content']['parts'][0]['text'];
        
        // Try to extract JSON from the response
        $jsonStart = strpos($text, '[');
        $jsonEnd = strrpos($text, ']');
        
        if ($jsonStart === false || $jsonEnd === false) {
            throw new \Exception('Could not extract JSON from AI response');
        }
        
        $jsonText = substr($text, $jsonStart, $jsonEnd - $jsonStart + 1);
        $questions = json_decode($jsonText, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid JSON in AI response: ' . json_last_error_msg());
        }
        
        // Validate and format questions
        $formattedQuestions = [];
        foreach ($questions as $question) {
            if (!isset($question['question']) || !isset($question['correct_option'])) {
                continue;
            }
            
            $formattedQuestion = [
                'question' => $question['question'],
                'category' => $question['category'] ?? 'General Knowledge',
                'difficulty' => $question['difficulty'] ?? 'medium',
                'type' => $question['type'] ?? ($type ?? 'multiple'),
                'correct_option' => $question['correct_option'],
                'incorrect_answers' => $question['incorrect_answers'] ?? []
            ];
            
            $formattedQuestions[] = $formattedQuestion;
        }
        
        return $formattedQuestions;
    }

    /**
     * Get available categories (suggested for AI generation)
     */
    public function getAvailableCategories(): array
    {
        return [
            'General Knowledge',
            'Science & Nature',
            'Mathematics',
            'History',
            'Geography',
            'Literature',
            'Computer Science',
            'Physics',
            'Chemistry',
            'Biology',
            'Psychology',
            'Economics',
            'Politics',
            'Art',
            'Music',
            'Sports',
            'Technology',
            'Business',
            'Philosophy',
            'Languages'
        ];
    }
}
