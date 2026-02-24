<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Psr\Log\LoggerInterface;

class TriviaApiService
{
    private HttpClientInterface $client;
    private LoggerInterface $logger;
    private string $apiUrl = 'https://opentdb.com/api.php';

    public function __construct(HttpClientInterface $client, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->logger = $logger;
    }

    /**
     * Fetch questions from Open Trivia Database API
     *
     * @param int $amount Number of questions to fetch (max 50)
     * @param string $category Category ID (optional)
     * @param string $difficulty Difficulty level (easy, medium, hard) (optional)
     * @param string $type Question type (multiple, boolean) (optional)
     * @return array Response with success status and questions data
     */
    public function fetchQuestions(int $amount = 10, ?string $category = null, ?string $difficulty = null, ?string $type = null): array
    {
        try {
            // Validate parameters
            $amount = max(1, min(50, $amount)); // Ensure amount is between 1 and 50
            
            $params = [
                'amount' => $amount,
            ];

            if ($category) {
                $params['category'] = $category;
            }

            if ($difficulty && in_array($difficulty, ['easy', 'medium', 'hard'])) {
                $params['difficulty'] = $difficulty;
            }

            if ($type && in_array($type, ['multiple', 'boolean'])) {
                $params['type'] = $type;
            }

            // Make API request
            $response = $this->client->request('GET', $this->apiUrl, [
                'query' => $params,
                'timeout' => 30,
            ]);

            $data = $response->toArray();

            if ($data['response_code'] === 0) {
                return [
                    'success' => true,
                    'questions' => $this->formatQuestions($data['results']),
                    'count' => count($data['results'])
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $this->getErrorMessage($data['response_code']),
                    'response_code' => $data['response_code']
                ];
            }

        } catch (\Exception $e) {
            $this->logger->error('Trivia API Error: ' . $e->getMessage(), [
                'exception' => $e,
                'params' => $params ?? []
            ]);

            return [
                'success' => false,
                'error' => 'Failed to fetch questions from external API. Please try again later.',
                'details' => $e->getMessage()
            ];
        }
    }

    /**
     * Get available categories from Open Trivia Database
     *
     * @return array Response with success status and categories data
     */
    public function getCategories(): array
    {
        try {
            $response = $this->client->request('GET', 'https://opentdb.com/api_category.php', [
                'timeout' => 10,
            ]);

            $data = $response->toArray();

            return [
                'success' => true,
                'categories' => $data['trivia_categories'] ?? []
            ];

        } catch (\Exception $e) {
            $this->logger->error('Trivia Categories API Error: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => 'Failed to fetch categories from external API.',
                'details' => $e->getMessage()
            ];
        }
    }

    /**
     * Format API questions to match our Question entity structure
     *
     * @param array $apiQuestions Raw questions from API
     * @return array Formatted questions
     */
    private function formatQuestions(array $apiQuestions): array
    {
        $formattedQuestions = [];

        foreach ($apiQuestions as $question) {
            $formattedQuestion = [
                'question' => html_entity_decode($question['question'], ENT_QUOTES | ENT_HTML5),
                'difficulty' => $question['difficulty'],
                'category' => html_entity_decode($question['category'], ENT_QUOTES | ENT_HTML5),
                'type' => $question['type']
            ];

            if ($question['type'] === 'multiple') {
                // Multiple choice question
                $options = $question['incorrect_answers'];
                $options[] = $question['correct_answer'];
                
                // Shuffle options
                shuffle($options);
                
                // Find correct answer index
                $correctIndex = array_search($question['correct_answer'], $options);
                $correctOption = chr(65 + $correctIndex); // A, B, C, D

                $formattedQuestion['option_a'] = html_entity_decode($options[0] ?? '', ENT_QUOTES | ENT_HTML5);
                $formattedQuestion['option_b'] = html_entity_decode($options[1] ?? '', ENT_QUOTES | ENT_HTML5);
                $formattedQuestion['option_c'] = html_entity_decode($options[2] ?? '', ENT_QUOTES | ENT_HTML5);
                $formattedQuestion['option_d'] = html_entity_decode($options[3] ?? '', ENT_QUOTES | ENT_HTML5);
                $formattedQuestion['correct_option'] = $correctOption;
                
            } elseif ($question['type'] === 'boolean') {
                // True/False question - convert to multiple choice
                $correctAnswer = $question['correct_answer'] === 'True';
                
                $formattedQuestion['option_a'] = 'True';
                $formattedQuestion['option_b'] = 'False';
                $formattedQuestion['option_c'] = 'Neither';
                $formattedQuestion['option_d'] = 'Both';
                $formattedQuestion['correct_option'] = $correctAnswer ? 'A' : 'B';
            }

            $formattedQuestions[] = $formattedQuestion;
        }

        return $formattedQuestions;
    }

    /**
     * Get error message based on API response code
     *
     * @param int $responseCode API response code
     * @return string Error message
     */
    private function getErrorMessage(int $responseCode): string
    {
        return match ($responseCode) {
            1 => 'No Results: Could not return results. The API doesn\'t have enough questions for your query.',
            2 => 'Invalid Parameter: Contains an invalid parameter. Arguments passed in aren\'t valid.',
            3 => 'Token Not Found: Session Token does not exist.',
            4 => 'Token Empty: Session Token has returned all possible questions for the specified query. Resetting the Token is necessary.',
            5 => 'Rate Limit: Too many requests have occurred. Each IP can only access the API once every 5 seconds.',
            default => 'Unknown error occurred while fetching questions.'
        };
    }

    /**
     * Get category count for a specific category
     *
     * @param string $categoryId Category ID
     * @return array Response with category question count
     */
    public function getCategoryQuestionCount(string $categoryId): array
    {
        try {
            $response = $this->client->request('GET', 'https://opentdb.com/api_count.php', [
                'query' => ['category' => $categoryId],
                'timeout' => 10,
            ]);

            $data = $response->toArray();

            return [
                'success' => true,
                'category_id' => $categoryId,
                'question_count' => $data['category_question_count']['total_question_count'] ?? 0
            ];

        } catch (\Exception $e) {
            $this->logger->error('Trivia Category Count API Error: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => 'Failed to fetch category question count.',
                'details' => $e->getMessage()
            ];
        }
    }
}
