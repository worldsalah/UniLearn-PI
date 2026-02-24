<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class GeminiApiService
{
    private HttpClientInterface $client;
    private LoggerInterface $logger;
    private string $apiKey;
    private string $model;

    public function __construct(
        HttpClientInterface $client, 
        LoggerInterface $logger,
        string $geminiApiKey,
        string $model = 'gemini-1.5-flash'
    ) {
        $this->client = $client;
        $this->logger = $logger;
        $this->apiKey = $geminiApiKey;
        $this->model = $model;
    }

    /**
     * Generate questions using Gemini API
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
            // Build the prompt for Gemini
            $prompt = $this->buildPrompt($amount, $category, $difficulty, $type);
            
            // Call Gemini API
            $response = $this->callGeminiAPI($prompt);
            
            // Parse the response
            $questions = $this->parseAIResponse($response, $type);
            
            return [
                'success' => true,
                'results' => $questions,
                'amount' => count($questions)
            ];

        } catch (\Exception $e) {
            $this->logger->error('Gemini API Error: ' . $e->getMessage(), [
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
     * Build the prompt for Gemini based on parameters
     */
    private function buildPrompt(int $amount, string $category = null, string $difficulty = null, string $type = null): string
    {
        $prompt = "Generate exactly {$amount} quiz questions in JSON format.";
        
        if ($category) {
            $prompt .= " Category: {$category}.";
        }
        
        if ($difficulty) {
            $prompt .= " Difficulty: {$difficulty}.";
        }
        
        if ($type) {
            $prompt .= " Type: {$type} choice.";
        }
        
        $prompt .= "

Return ONLY a JSON array like this:
[
  {
    \"question\": \"What is the capital of France?\",
    \"category\": \"Geography\",
    \"difficulty\": \"easy\",
    \"type\": \"multiple\",
    \"correct_option\": \"Paris\",
    \"incorrect_answers\": [\"London\", \"Berlin\", \"Madrid\"]
  }
]

No extra text, just the JSON array.";

        return $prompt;
    }

    /**
     * Call Gemini API
     */
    private function callGeminiAPI(string $prompt): array
    {
        $url = "https://generativelanguage.googleapis.com/v1/models/{$this->model}:generateContent";

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

        try {
            $response = $this->client->request('POST', $url, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'x-goog-api-key' => $this->apiKey
                ],
                'json' => $payload,
                'timeout' => 60
            ]);

            $data = $response->toArray();
            
            // Log the response for debugging
            $this->logger->info('Gemini API Response', ['response' => $data]);
            
            // Check for API errors
            if (isset($data['error'])) {
                throw new \Exception('Gemini API Error: ' . $data['error']['message']);
            }
            
            // Check for expected response structure
            if (!isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                throw new \Exception('Invalid response from Gemini API: missing text content');
            }

            return $data;
            
        } catch (\Exception $e) {
            $this->logger->error('Gemini API call failed', [
                'error' => $e->getMessage(),
                'model' => $this->model,
                'prompt_length' => strlen($prompt)
            ]);
            throw $e;
        }
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
            // Fallback to sample questions if JSON parsing fails
            $this->logger->warning('Could not extract JSON from AI response, using fallback questions');
            return $this->getFallbackQuestions($type);
        }
        
        $jsonText = substr($text, $jsonStart, $jsonEnd - $jsonStart + 1);
        $questions = json_decode($jsonText, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            // Fallback to sample questions if JSON is invalid
            $this->logger->warning('Invalid JSON in AI response: ' . json_last_error_msg() . ', using fallback questions');
            return $this->getFallbackQuestions($type);
        }
        
        // Validate and format questions to match frontend expectations
        $formattedQuestions = [];
        foreach ($questions as $question) {
            if (!isset($question['question']) || !isset($question['correct_option'])) {
                continue;
            }
            
            // Format options to match frontend structure
            $incorrectAnswers = $question['incorrect_answers'] ?? [];
            $correctOption = $question['correct_option'];
            
            // Create options array with correct answer as first option
            $allOptions = array_merge([$correctOption], $incorrectAnswers);
            
            // Shuffle options but keep track of correct answer position
            shuffle($allOptions);
            $correctIndex = array_search($correctOption, $allOptions);
            $correctLetter = chr(65 + $correctIndex); // A, B, C, or D
            
            // Ensure we have exactly 4 options
            while (count($allOptions) < 4) {
                $allOptions[] = "Additional option " . (count($allOptions) + 1);
            }
            $allOptions = array_slice($allOptions, 0, 4);
            
            $formattedQuestion = [
                'question' => $question['question'],
                'category' => $question['category'] ?? 'General Knowledge',
                'difficulty' => $question['difficulty'] ?? 'medium',
                'type' => $question['type'] ?? ($type ?? 'multiple'),
                'option_a' => $allOptions[0] ?? '',
                'option_b' => $allOptions[1] ?? '',
                'option_c' => $allOptions[2] ?? '',
                'option_d' => $allOptions[3] ?? '',
                'correct_option' => $correctLetter,
                'correct_answer' => $correctOption // Keep original for reference
            ];
            
            $formattedQuestions[] = $formattedQuestion;
        }
        
        // If no valid questions found, use fallback
        if (empty($formattedQuestions)) {
            $this->logger->warning('No valid questions found in AI response, using fallback questions');
            return $this->getFallbackQuestions($type);
        }
        
        return $formattedQuestions;
    }
    
    /**
     * Get fallback sample questions
     */
    private function getFallbackQuestions(string $type = null): array
    {
        return [
            [
                'question' => "What is the capital of France?",
                'category' => "Geography",
                'difficulty' => "easy",
                'type' => "multiple",
                'option_a' => "Paris",
                'option_b' => "London",
                'option_c' => "Berlin",
                'option_d' => "Madrid",
                'correct_option' => "A",
                'correct_answer' => "Paris"
            ],
            [
                'question' => "What is 2 + 2?",
                'category' => "Mathematics", 
                'difficulty' => "easy",
                'type' => "multiple",
                'option_a' => "4",
                'option_b' => "3",
                'option_c' => "5",
                'option_d' => "6",
                'correct_option' => "A",
                'correct_answer' => "4"
            ],
            [
                'question' => "Which planet is known as the Red Planet?",
                'category' => "Science",
                'difficulty' => "medium",
                'type' => "multiple", 
                'option_a' => "Mars",
                'option_b' => "Venus",
                'option_c' => "Jupiter",
                'option_d' => "Saturn",
                'correct_option' => "A",
                'correct_answer' => "Mars"
            ],
            [
                'question' => "Who painted the Mona Lisa?",
                'category' => "Art",
                'difficulty' => "medium",
                'type' => "multiple",
                'option_a' => "Leonardo da Vinci",
                'option_b' => "Pablo Picasso",
                'option_c' => "Vincent van Gogh",
                'option_d' => "Michelangelo",
                'correct_option' => "A",
                'correct_answer' => "Leonardo da Vinci"
            ],
            [
                'question' => "What is the largest ocean on Earth?",
                'category' => "Geography",
                'difficulty' => "easy", 
                'type' => "multiple",
                'option_a' => "Pacific Ocean",
                'option_b' => "Atlantic Ocean",
                'option_c' => "Indian Ocean",
                'option_d' => "Arctic Ocean",
                'correct_option' => "A",
                'correct_answer' => "Pacific Ocean"
            ]
        ];
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
