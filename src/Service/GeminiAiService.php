<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class GeminiAiService
{
    private HttpClientInterface $httpClient;
    private string $apiKey;
    private LoggerInterface $logger;

    public function __construct(
        HttpClientInterface $httpClient,
        LoggerInterface $logger,
        string $apiKey
    ) {
        $this->httpClient = $httpClient;
        $this->apiKey = $apiKey;
        $this->logger = $logger;
        
        // Log API key status for debugging
        if (empty($this->apiKey) || $this->apiKey === '%env(GEMINI_API_KEY)%') {
            $this->logger->warning('Gemini API key is empty or not resolved. AI features will use fallback mode.');
            $this->logger->warning('API Key value received: ' . substr($apiKey, 0, 10) . '...');
        } else {
            $this->logger->info('Gemini API key is configured. Key starts with: ' . substr($apiKey, 0, 10) . '...');
            $this->logger->info('API key length: ' . strlen($apiKey));
        }
    }

    public function generateLearningRoadmap(array $userData, array $availableCourses, array $userHistory = []): array
    {
        $prompt = $this->buildRoadmapPrompt($userData, $availableCourses, $userHistory);
        
        try {
            $response = $this->makeApiRequest($prompt);
            
            if ($response->getStatusCode() !== 200) {
                $this->logger->error('Gemini API error for roadmap: ' . $response->getStatusCode());
                return $this->getFallbackRoadmap($userData, $availableCourses);
            }

            $content = $response->getContent();
            $data = json_decode($content, true);

            if ($data === null || !isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                $this->logger->error('Invalid Gemini API response structure for roadmap');
                return $this->getFallbackRoadmap($userData, $availableCourses);
            }

            $aiResponse = $data['candidates'][0]['content']['parts'][0]['text'];
            return $this->parseRoadmapResponse($aiResponse, $availableCourses);

        } catch (\Exception $e) {
            $this->logger->error('Gemini API exception for roadmap: ' . $e->getMessage());
            return $this->getFallbackRoadmap($userData, $availableCourses);
        }
    }

    public function generateQuizInsights(array $quizData): string
    {
        if (empty($quizData)) {
            return 'No quiz data available for analysis.';
        }

        $prompt = $this->buildPrompt($quizData);
        
        try {
            $response = $this->makeApiRequest($prompt);
            
            if ($response->getStatusCode() !== 200) {
                $this->logger->error('Gemini API error: ' . $response->getStatusCode());
                return $this->getFallbackMessage($quizData);
            }

            $content = $response->getContent();
            $data = json_decode($content, true);

            if ($data === null || !isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                $this->logger->error('Invalid Gemini API response structure');
                return $this->getFallbackMessage($quizData);
            }

            return $data['candidates'][0]['content']['parts'][0]['text'];

        } catch (\Exception $e) {
            $this->logger->error('Gemini API exception: ' . $e->getMessage());
            return $this->getFallbackMessage($quizData);
        }
    }

    private function buildRoadmapPrompt(array $userData, array $availableCourses, array $userHistory): string
    {
        $goal = $userData['learning_goal'] ?? '';
        $level = $userData['skill_level'] ?? 'beginner';
        $timeCommitment = $userData['time_commitment'] ?? '3-5';
        $learningStyles = $userData['learning_styles'] ?? [];
        
        $coursesJson = json_encode($availableCourses, JSON_PRETTY_PRINT);
        $historyJson = json_encode($userHistory, JSON_PRETTY_PRINT);
        $userDataJson = json_encode($userData, JSON_PRETTY_PRINT);
        
        return "You are an expert educational AI specializing in personalized learning path design and curriculum development.

STUDENT PROFILE:
{$userDataJson}

LEARNING HISTORY:
{$historyJson}

AVAILABLE COURSES:
{$coursesJson}

ROADMAP GENERATION REQUIREMENTS:

1. COMPREHENSIVE STUDENT ANALYSIS:
   - Analyze learning goal complexity and scope
   - Assess current skill level vs goal requirements
   - Evaluate learning style preferences and time availability
   - Review past performance and learning patterns
   - Identify knowledge gaps and strengths

2. INTELLIGENT COURSE SELECTION:
   - Select courses that directly address the learning goal
   - Ensure proper skill progression and prerequisite chaining
   - Match courses to learning style preferences
   - Consider course difficulty vs student's current level
   - Prioritize practical, hands-on content when appropriate

3. ADAPTIVE LEARNING PATH DESIGN:
   - Create logical progression with clear milestones
   - Balance theoretical knowledge with practical application
   - Include buffer time for practice and reinforcement
   - Adapt pace based on time commitment and difficulty
   - Build in review and assessment points

4. PERSONALIZATION ELEMENTS:
   - Customize examples to match student's interests
   - Adapt difficulty progression based on performance history
   - Incorporate preferred learning modalities
   - Consider career objectives and real-world applications
   - Add motivational elements and progress tracking

5. PREREQUISITE AND DEPENDENCY ANALYSIS:
   - Identify and sequence prerequisite knowledge
   - Ensure foundational topics precede advanced concepts
   - Create logical skill-building progression
   - Account for cross-topic dependencies
   - Build flexibility for alternative learning paths

RESPONSE FORMAT:
Return JSON with exactly this structure:

{
  \"roadmap\": {
    \"title\": \"Learning Roadmap for [Goal]\",
    \"description\": \"Personalized learning path description\",
    \"estimated_duration\": \"X weeks\",
    \"difficulty_progression\": \"beginner → intermediate → advanced\"
  },
  \"weeks\": [
    {
      \"week\": 1,
      \"title\": \"Week Title\",
      \"focus\": \"Main learning focus\",
      \"objectives\": [\"Specific learning objective 1\", \"objective 2\"],
      \"course_recommendations\": [
        {
          \"course_id\": \"ID\",
          \"title\": \"Course Title\",
          \"relevance_score\": 0.95,
          \"reason\": \"Why this course is recommended\"
        }
      ],
      \"activities\": [\"Practice exercise\", \"Reading assignment\", \"Project work\"],
      \"estimated_hours\": 10,
      \"difficulty\": \"beginner|intermediate|advanced\",
      \"assessment_type\": \"quiz|project|assignment\"
    }
  ],
  \"milestones\": [
    {
      \"week\": 4,
      \"title\": \"Milestone Title\",
      \"description\": \"What student should have accomplished\",
      \"skills_gained\": [\"skill1\", \"skill2\"]
    }
  ],
  \"adaptation_notes\": \"How the path adapts based on performance\",
  \"success_criteria\": \"How success is measured\",
  \"next_steps\": \"What to do after completing this roadmap\"
}

Generate 4-16 weeks based on time commitment. Be highly specific and practical. Focus on real learning outcomes.";
    }

    private function parseRoadmapResponse(string $aiResponse, array $availableCourses): array
    {
        try {
            // Try to extract JSON from the AI response
            $jsonStart = strpos($aiResponse, '{');
            $jsonEnd = strrpos($aiResponse, '}');
            
            if ($jsonStart !== false && $jsonEnd !== false) {
                $jsonStr = substr($aiResponse, $jsonStart, $jsonEnd - $jsonStart + 1);
                $roadmapData = json_decode($jsonStr, true);
                
                if ($roadmapData !== null && isset($roadmapData['weeks'])) {
                    // Validate and enhance course recommendations
                    foreach ($roadmapData['weeks'] as &$week) {
                        if (isset($week['course_recommendations'])) {
                            $week['course_recommendations'] = $this->validateCourseRecommendations(
                                $week['course_recommendations'], 
                                $availableCourses
                            );
                        }
                    }
                    return $roadmapData;
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Error parsing AI roadmap response: ' . $e->getMessage());
        }
        
        // Fallback to basic structure if parsing fails
        return $this->getFallbackRoadmap([], $availableCourses);
    }
    
    private function validateCourseRecommendations(array $recommendations, array $availableCourses): array
    {
        $validated = [];
        $courseMap = [];
        
        // Create course lookup map
        foreach ($availableCourses as $course) {
            $courseMap[$course['id']] = $course;
        }
        
        foreach ($recommendations as $rec) {
            $courseId = $rec['course_id'] ?? null;
            
            if ($courseId && isset($courseMap[$courseId])) {
                $validated[] = [
                    'course_id' => $courseId,
                    'title' => $courseMap[$courseId]['title'],
                    'relevance_score' => $rec['relevance_score'] ?? 0.8,
                    'reason' => $rec['reason'] ?? 'Matches learning objectives',
                    'level' => $courseMap[$courseId]['level'],
                    'category' => $courseMap[$courseId]['category'] ?? 'General'
                ];
            }
        }
        
        return $validated;
    }
    
    private function getFallbackRoadmap(array $userData, array $availableCourses): array
    {
        $goal = $userData['learning_goal'] ?? 'General Learning';
        $level = $userData['skill_level'] ?? 'beginner';
        $timeCommitment = $userData['time_commitment'] ?? '3-5';
        
        $weeks = max(4, min(16, (int)explode('-', $timeCommitment)[0] * 2));
        
        $roadmap = [
            'roadmap' => [
                'title' => "Learning Roadmap for {$goal}",
                'description' => "Personalized learning path for {$goal} at {$level} level",
                'estimated_duration' => "{$weeks} weeks",
                'difficulty_progression' => "{$level} → intermediate → advanced"
            ],
            'weeks' => [],
            'milestones' => [
                [
                    'week' => floor($weeks / 2),
                    'title' => 'Mid-point Assessment',
                    'description' => 'Evaluate progress and adjust learning path',
                    'skills_gained' => ['Core concepts', 'Practical skills']
                ],
                [
                    'week' => $weeks,
                    'title' => 'Learning Completion',
                    'description' => 'Master fundamental concepts and ready for advanced topics',
                    'skills_gained' => ['Complete understanding', 'Practical application']
                ]
            ],
            'adaptation_notes' => 'Path adjusts based on quiz performance and course completion',
            'success_criteria' => 'Complete all courses with >80% quiz scores',
            'next_steps' => 'Advanced specialization or practical projects'
        ];
        
        // Generate basic weeks
        for ($i = 1; $i <= $weeks; $i++) {
            $weekData = $this->generateBasicWeek($i, $goal, $level, $availableCourses);
            $roadmap['weeks'][] = $weekData;
        }
        
        return $roadmap;
    }
    
    private function generateBasicWeek(int $week, string $goal, string $level, array $availableCourses): array
    {
        $topics = $this->getWeekTopics($goal, $level);
        $topicIndex = ($week - 1) % count($topics);
        
        return [
            'week' => $week,
            'title' => "Week {$week}: " . $topics[$topicIndex],
            'focus' => $topics[$topicIndex],
            'objectives' => [
                "Master {$topics[$topicIndex]} concepts",
                "Practice with hands-on exercises",
                "Complete assessment activities"
            ],
            'course_recommendations' => $this->getWeekCourseRecommendations($week, $goal, $availableCourses),
            'activities' => ['Reading', 'Practice exercises', 'Quiz'],
            'estimated_hours' => 8,
            'difficulty' => $level,
            'assessment_type' => 'quiz'
        ];
    }
    
    private function getWeekTopics(string $goal, string $level): array
    {
        $goalLower = strtolower($goal);
        
        if (strpos($goalLower, 'video editing') !== false || strpos($goalLower, 'video') !== false) {
            return ['Video Editing Fundamentals', 'Software Introduction', 'Basic Cuts & Transitions', 'Audio Fundamentals', 'Color Correction Basics', 'Motion Graphics'];
        } elseif (strpos($goalLower, 'web development') !== false) {
            return ['HTML & CSS Basics', 'JavaScript Fundamentals', 'DOM Manipulation', 'Responsive Design', 'Modern Frameworks', 'Backend Basics'];
        } elseif (strpos($goalLower, 'data science') !== false) {
            return ['Python Basics', 'Data Analysis', 'Statistics', 'Machine Learning Intro', 'Data Visualization', 'Advanced ML'];
        } elseif (strpos($goalLower, 'mobile') !== false) {
            return ['Mobile Basics', 'UI/UX Design', 'Platform Fundamentals', 'App Development', 'Testing', 'Deployment'];
        } else {
            return ['Fundamentals', 'Core Concepts', 'Practical Skills', 'Advanced Topics', 'Specialization', 'Mastery'];
        }
    }
    
    private function getWeekCourseRecommendations(int $week, string $goal, array $availableCourses): array
    {
        $recommendations = [];
        $goalLower = strtolower($goal);
        
        // Filter courses that match the learning goal
        $relevantCourses = [];
        foreach ($availableCourses as $course) {
            $courseTitle = strtolower($course['title'] ?? '');
            $courseDescription = strtolower($course['shortDescription'] ?? '');
            $courseCategory = strtolower($course['category'] ?? '');
            
            // Check if course is relevant to the learning goal
            $isRelevant = false;
            
            // Video editing related keywords
            $videoKeywords = ['video', 'editing', 'film', 'media', 'production', 'adobe', 'premiere', 'final cut', 'after effects', 'motion', 'graphics', 'animation'];
            
            // Check title, description, and category for video editing keywords
            foreach ($videoKeywords as $keyword) {
                if (strpos($goalLower, $keyword) !== false || 
                    strpos($courseTitle, $keyword) !== false || 
                    strpos($courseDescription, $keyword) !== false || 
                    strpos($courseCategory, $keyword) !== false) {
                    $isRelevant = true;
                    break;
                }
            }
            
            // If specifically looking for video editing, be more strict
            if (strpos($goalLower, 'video editing') !== false) {
                $isRelevant = $isRelevant && (
                    strpos($courseTitle, 'video') !== false || 
                    strpos($courseDescription, 'editing') !== false ||
                    strpos($courseCategory, 'video') !== false ||
                    strpos($courseTitle, 'film') !== false ||
                    strpos($courseDescription, 'production') !== false
                );
            }
            
            if ($isRelevant) {
                $relevantCourses[] = $course;
            }
        }
        
        // If no relevant courses found, return empty array
        if (empty($relevantCourses)) {
            return [];
        }
        
        // Select courses for this week (max 2 per week)
        $coursesPerWeek = min(2, ceil(count($relevantCourses) / 4));
        $startIndex = ($week - 1) * $coursesPerWeek;
        
        for ($i = 0; $i < $coursesPerWeek && $startIndex + $i < count($relevantCourses); $i++) {
            $course = $relevantCourses[$startIndex + $i];
            $recommendations[] = [
                'course_id' => $course['id'],
                'title' => $course['title'],
                'relevance_score' => 0.9, // Higher score for relevant courses
                'reason' => 'Directly related to video editing learning path',
                'level' => $course['level'],
                'category' => $course['category'] ?? 'General'
            ];
        }
        
        return $recommendations;
    }

    private function buildPrompt(array $quizData): string
    {
        $json_data = json_encode($quizData, JSON_PRETTY_PRINT);
        
        return "You are an academic AI advisor specializing in learning analytics and performance optimization.

Analyze the following student quiz performance data:

{$json_data}

ANALYSIS REQUIREMENTS:

1. PERFORMANCE ANALYSIS:
   - Calculate overall performance trends
   - Identify patterns in quiz-taking behavior
   - Assess consistency and improvement over time
   - Evaluate difficulty level handling

2. SUBJECT MASTERY ASSESSMENT:
   - Identify weak subjects (performance below 60%)
   - Identify strong subjects (performance above 80%)
   - Categorize by difficulty level and topic
   - Note areas of significant improvement or decline

3. LEARNING INSIGHTS:
   - Analyze learning pace and retention
   - Identify knowledge gaps and misconceptions
   - Assess test-taking strategies effectiveness
   - Evaluate time management if data available

4. PERSONALIZED RECOMMENDATIONS:
   - Suggest specific study focus areas
   - Recommend learning resources or strategies
   - Propose next difficulty level challenges
   - Provide actionable improvement steps

5. MOTIVATIONAL ELEMENTS:
   - Acknowledge achievements and progress
   - Provide encouragement based on performance
   - Set realistic next goals
   - Build confidence for continued learning

RESPONSE FORMAT:
Use exactly this structure:

Weak Areas:
[List specific subjects/topics with performance below 60%, include percentages if available]

Strong Areas:
[List subjects/topics with performance above 80%, highlight exceptional performance]

Performance Summary:
[2-3 sentences summarizing overall academic performance and trends]

Recommended Next Step:
[Specific, actionable recommendation for immediate improvement]

Advice:
[Motivational and strategic advice for continued academic success]

Keep response professional, encouraging, and academically focused. Provide specific, data-driven insights.";
    }

    private function makeApiRequest(string $prompt): \Symfony\Contracts\HttpClient\ResponseInterface
    {
        // Use the correct Generative AI API endpoint
        // Available models: gemini-2.5-flash, gemini-2.5-pro, gemini-2.0-flash
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $this->apiKey;
        
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
                'topK' => 1,
                'topP' => 0.8,
                'maxOutputTokens' => 4096
            ]
        ];

        try {
            $this->logger->info('Making Gemini API request to: ' . $url);
            
            $response = $this->httpClient->request('POST', $url, [
                'json' => $payload,
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'timeout' => 60
            ]);
            
            // Log response status for debugging
            $this->logger->info('Gemini API response status: ' . $response->getStatusCode());
            
            return $response;
        } catch (\Exception $e) {
            $this->logger->error('HTTP request failed: ' . $e->getMessage());
            $this->logger->error('API URL: ' . $url);
            $this->logger->error('API Key length: ' . strlen($this->apiKey));
            throw $e;
        }
    }

    /**
     * Get specific error message based on HTTP status code
     */
    private function getApiErrorMessage(int $statusCode): string
    {
        switch ($statusCode) {
            case 400:
                return 'Bad Request - The request was invalid or malformed';
            case 401:
                return 'Unauthorized - API key is invalid or missing';
            case 403:
                return 'Forbidden - API key does not have permission to access this resource';
            case 404:
                return 'Not Found - The requested resource was not found';
            case 429:
                return 'Too Many Requests - Rate limit exceeded';
            case 500:
                return 'Internal Server Error - Google API server error';
            case 503:
                return 'Service Unavailable - Google API service is temporarily down';
            default:
                return 'Unknown API error';
        }
    }

    private function getFallbackMessage(array $quizData): string
    {
        $totalQuizzes = count($quizData);
        
        if ($totalQuizzes === 0) {
            return "Start taking quizzes to receive personalized AI insights into your learning performance.";
        }

        // Calculate basic statistics for fallback
        $scores = array_column($quizData, 'percentage');
        $averageScore = array_sum($scores) / count($scores);
        
        $weakAreas = [];
        $strongAreas = [];
        
        foreach ($quizData as $quiz) {
            if ($quiz['percentage'] < 60) {
                $weakAreas[] = $quiz['quiz'];
            } elseif ($quiz['percentage'] >= 80) {
                $strongAreas[] = $quiz['quiz'];
            }
        }

        $fallbackMessage = "Weak Areas:\n";
        $fallbackMessage .= empty($weakAreas) ? "No significant weak areas identified. Continue your current learning path." : implode(", ", array_unique($weakAreas));
        
        $fallbackMessage .= "\n\nStrong Areas:\n";
        $fallbackMessage .= empty($strongAreas) ? "Keep practicing to build strong areas." : implode(", ", array_unique($strongAreas));
        
        $fallbackMessage .= "\n\nPerformance Summary:\n";
        $fallbackMessage .= sprintf("Based on %d quizzes, your average performance is %.1f%%. ", $totalQuizzes, $averageScore);
        
        if ($averageScore >= 80) {
            $fallbackMessage .= "You're demonstrating excellent understanding of the material.";
        } elseif ($averageScore >= 60) {
            $fallbackMessage .= "You're making good progress with room for improvement.";
        } else {
            $fallbackMessage .= "Focus on fundamental concepts to build a stronger foundation.";
        }
        
        $fallbackMessage .= "\n\nRecommended Next Step:\n";
        if (!empty($weakAreas)) {
            $fallbackMessage .= "Focus additional study time on " . implode(" and ", array_slice($weakAreas, 0, 2)) . " to strengthen your understanding.";
        } else {
            $fallbackMessage .= "Challenge yourself with more advanced topics to continue your growth.";
        }
        
        $fallbackMessage .= "\n\nAdvice:\n";
        $fallbackMessage .= "Consistent practice and review of incorrect answers will accelerate your learning progress. Stay motivated and track your improvement over time.";

        return $fallbackMessage;
    }

    public function generateQuizQuestions(string $courseTitle, string $courseDescription, int $questionCount = 5, string $difficulty = 'medium', string $language = 'en'): array
    {
        $this->logger->info('Generating questions for course: ' . $courseTitle);
        $this->logger->info('Course description: ' . substr($courseDescription, 0, 200));
        
        $prompt = $this->buildQuestionGenerationPrompt($courseTitle, $courseDescription, $questionCount, $difficulty, $language);
        
        try {
            $response = $this->makeApiRequest($prompt);
            
            if ($response->getStatusCode() !== 200) {
                $statusCode = $response->getStatusCode();
                $this->logger->error('Gemini API error for question generation: HTTP ' . $statusCode);
                
                // Check for quota exceeded (HTTP 429)
                if ($statusCode === 429) {
                    $this->logger->error('API Quota Exceeded');
                    return [
                        'success' => false,
                        'error' => 'quota_exceeded',
                        'message' => 'You have reached your maximum daily quota for AI question generation. Please try again tomorrow.'
                    ];
                }
                
                // Provide more specific error messages based on status code
                $errorMessage = $this->getApiErrorMessage($statusCode);
                $this->logger->error('API Error Details: ' . $errorMessage);
                
                // Try to get response content for more debugging
                try {
                    $responseContent = $response->getContent(false);
                    $this->logger->error('API Response Content: ' . $responseContent);
                } catch (\Exception $e) {
                    $this->logger->error('Could not get response content: ' . $e->getMessage());
                }
                
                return $this->getFallbackQuestions($courseTitle, $questionCount);
            }

            $content = $response->getContent();
            $data = json_decode($content, true);

            if ($data === null || !isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                $this->logger->error('Invalid Gemini API response structure for question generation');
                $this->logger->error('Raw API Response: ' . $content);
                return $this->getFallbackQuestions($courseTitle, $questionCount);
            }

            $aiResponse = $data['candidates'][0]['content']['parts'][0]['text'];
            
            // Debug: log the raw AI response
            $this->logger->info('Raw AI Response for questions: ' . substr($aiResponse, 0, 1000));
            $this->logger->info('Full AI Response length: ' . strlen($aiResponse));
            
            $parsedQuestions = $this->parseQuestionGenerationResponse($aiResponse);
            
            // Debug: log parsing result
            $this->logger->info('Parsed result: ' . json_encode($parsedQuestions));
            
            // Enhanced question formatting with option shuffling
            if ($parsedQuestions['success']) {
                $formattedQuestions = [];
                foreach ($parsedQuestions['questions'] as $question) {
                    $formattedQuestions[] = $this->formatQuestionWithOptions($question);
                }
                return [
                    'success' => true,
                    'questions' => $formattedQuestions
                ];
            }

            return $parsedQuestions;

        } catch (\Exception $e) {
            $this->logger->error('Gemini API exception for question generation: ' . $e->getMessage());
            $this->logger->error('Exception trace: ' . $e->getTraceAsString());
            return $this->getFallbackQuestions($courseTitle, $questionCount);
        }
    }

    /**
     * Format question with shuffled options and correct answer tracking
     */
    private function formatQuestionWithOptions(array $question): array
    {
        // Extract options and correct answer
        $options = [
            $question['option_a'] ?? '',
            $question['option_b'] ?? '',
            $question['option_c'] ?? '',
            $question['option_d'] ?? ''
        ];
        
        $correctOption = $question['correct_option'] ?? 'A';
        $correctIndex = ord($correctOption) - ord('A');
        
        // Ensure we have a valid correct index
        if ($correctIndex < 0 || $correctIndex >= 4) {
            $correctIndex = 0;
        }
        
        // Get the actual correct answer text
        $correctAnswer = $options[$correctIndex];
        
        // Shuffle options while keeping track of correct answer
        $shuffledOptions = $options;
        shuffle($shuffledOptions);
        
        // Find new position of correct answer
        $newCorrectIndex = array_search($correctAnswer, $shuffledOptions, true);
        $newCorrectLetter = $newCorrectIndex !== false ? chr(65 + $newCorrectIndex) : 'A'; // A, B, C, or D
        
        return [
            'question' => $question['question'] ?? '',
            'option_a' => $shuffledOptions[0] ?? '',
            'option_b' => $shuffledOptions[1] ?? '',
            'option_c' => $shuffledOptions[2] ?? '',
            'option_d' => $shuffledOptions[3] ?? '',
            'correct_option' => $newCorrectLetter,
            'correct_answer' => $correctAnswer, // Keep original for reference
            'difficulty' => $question['difficulty'] ?? 'medium',
            'topic' => $question['topic'] ?? 'General',
            'explanation' => $question['explanation'] ?? ''
        ];
    }

    public function translateQuestion(array $questionData, string $targetLanguage): array
    {
        $prompt = $this->buildTranslationPrompt($questionData, $targetLanguage);
        
        try {
            $response = $this->makeApiRequest($prompt);
            
            if ($response->getStatusCode() !== 200) {
                $statusCode = $response->getStatusCode();
                $this->logger->error('Gemini API error for translation: HTTP ' . $statusCode);
                
                // Provide more specific error messages based on status code
                $errorMessage = $this->getApiErrorMessage($statusCode);
                $this->logger->error('API Error Details: ' . $errorMessage);
                
                return $this->getFallbackTranslation($questionData, $targetLanguage);
            }

            $content = $response->getContent();
            $data = json_decode($content, true);

            if ($data === null || !isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                $this->logger->error('Invalid Gemini API response structure for translation');
                $this->logger->error('Raw API Response: ' . $content);
                return $this->getFallbackTranslation($questionData, $targetLanguage);
            }

            $aiResponse = $data['candidates'][0]['content']['parts'][0]['text'];
            return $this->parseTranslationResponse($aiResponse, $questionData);

        } catch (\Exception $e) {
            $this->logger->error('Gemini API exception for translation: ' . $e->getMessage());
            $this->logger->error('Exception trace: ' . $e->getTraceAsString());
            return $this->getFallbackTranslation($questionData, $targetLanguage);
        }
    }

    private function buildQuestionGenerationPrompt(string $courseTitle, string $courseDescription, int $questionCount, string $difficulty, string $language): string
    {
        return "You are an expert educational content creator specializing in creating high-quality quiz questions for online courses.

COURSE INFORMATION:
- Title: {$courseTitle}
- Description: {$courseDescription}
- Target Difficulty: {$difficulty}
- Language: {$language}

QUESTION GENERATION REQUIREMENTS:

1. CONTENT ACCURACY:
   - Generate questions directly related to the course content
   - Ensure all questions are factually correct and relevant
   - Match the specified difficulty level (beginner/intermediate/advanced)
   - Use appropriate terminology for the subject matter

2. QUESTION QUALITY:
   - Create clear, unambiguous questions
   - Provide plausible but distinct incorrect answers
   - Ensure only one correct answer per question
   - Use proper grammar and sentence structure
   - Avoid negative questions unless absolutely necessary

3. MULTIPLE CHOICE FORMAT:
   - Each question must have exactly 4 options (A, B, C, D)
   - Randomize the position of correct answers
   - Make incorrect answers plausible but clearly wrong
   - Keep options similar in length and complexity when possible

4. EDUCATIONAL VALUE:
   - Test important concepts from the course
   - Cover different aspects of the subject matter
   - Progress from basic to more complex concepts
   - Include practical application questions when relevant

RESPONSE FORMAT:
Return JSON with exactly this structure:

{
  \"questions\": [
    {
      \"question\": \"Clear question text here\",
      \"option_a\": \"First option\",
      \"option_b\": \"Second option\",
      \"option_c\": \"Third option\",
      \"option_d\": \"Fourth option\",
      \"correct_option\": \"A\",
      \"difficulty\": \"beginner|intermediate|advanced\",
      \"topic\": \"Main topic covered\",
      \"explanation\": \"Brief explanation of why the correct answer is right\"
    }
  ]
}

Generate exactly {$questionCount} questions. All content must be in {$language}. Focus on practical knowledge that students would gain from this course.";
    }

    private function buildTranslationPrompt(array $questionData, string $targetLanguage): string
    {
        $questionText = $questionData['question'] ?? '';
        $optionA = $questionData['option_a'] ?? '';
        $optionB = $questionData['option_b'] ?? '';
        $optionC = $questionData['option_c'] ?? '';
        $optionD = $questionData['option_d'] ?? '';
        $correctOption = $questionData['correct_option'] ?? '';
        
        return "You are a professional translator specializing in educational content. Translate the following quiz question while maintaining accuracy and educational value.

ORIGINAL QUESTION:
Question: {$questionText}
Option A: {$optionA}
Option B: {$optionB}
Option C: {$optionC}
Option D: {$optionD}
Correct Answer: {$correctOption}

TARGET LANGUAGE: {$targetLanguage}

TRANSLATION REQUIREMENTS:

1. ACCURACY:
   - Translate the exact meaning without adding or removing information
   - Maintain the same difficulty level and complexity
   - Preserve technical accuracy and terminology
   - Ensure the translated question makes logical sense

2. FORMATTING:
   - Keep the same multiple choice structure
   - Maintain option labels (A, B, C, D)
   - Preserve the correct answer designation
   - Use natural, fluent language in the target language

3. EDUCATIONAL INTEGRITY:
   - Ensure all translated options are plausible in the target language
   - Maintain the distinction between correct and incorrect answers
   - Use appropriate educational terminology
   - Keep cultural and contextual relevance

RESPONSE FORMAT:
IMPORTANT: Return ONLY pure JSON without any markdown formatting, code blocks, or extra text. The response must start with { and end with }.

{
  \"translated_question\": \"Translated question text\",
  \"translated_option_a\": \"Translated option A\",
  \"translated_option_b\": \"Translated option B\",
  \"translated_option_c\": \"Translated option C\",
  \"translated_option_d\": \"Translated option D\",
  \"correct_option\": \"A\",
  \"target_language\": \"{$targetLanguage}\"
}

Translate only the content, not the structure. Ensure the translation is accurate and natural-sounding. Return ONLY the JSON object, nothing else.";
    }

    private function parseQuestionGenerationResponse(string $aiResponse): array
    {
        try {
            // Remove markdown code blocks if present
            $cleanResponse = $aiResponse;
            if (strpos($aiResponse, '```json') !== false) {
                $cleanResponse = preg_replace('/```json\s*/', '', $aiResponse);
                $cleanResponse = preg_replace('/```\s*$/', '', $cleanResponse ?? '');
            } elseif (strpos($aiResponse, '```') !== false) {
                $cleanResponse = preg_replace('/```\s*/', '', $aiResponse);
                $cleanResponse = preg_replace('/```\s*$/', '', $cleanResponse ?? '');
            }
            
            // Trim whitespace
            $cleanResponse = trim($cleanResponse ?? '');
            
            // Try to parse as complete JSON object first
            $questionData = json_decode($cleanResponse, true);
            
            if ($questionData !== null && isset($questionData['questions'])) {
                return [
                    'success' => true,
                    'questions' => $questionData['questions']
                ];
            }
            
            // Fallback: look for JSON array
            $jsonStart = strpos($cleanResponse, '[');
            $jsonEnd = strrpos($cleanResponse, ']');
            
            if ($jsonStart !== false && $jsonEnd !== false) {
                $jsonStr = substr($cleanResponse, $jsonStart, $jsonEnd - $jsonStart + 1);
                $questions = json_decode($jsonStr, true);
                
                if ($questions !== null && is_array($questions)) {
                    return [
                        'success' => true,
                        'questions' => $questions
                    ];
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Error parsing AI question generation response: ' . $e->getMessage());
            $this->logger->error('AI Response: ' . $aiResponse);
        }
        
        return $this->getFallbackQuestions('General Course', 5);
    }

    private function parseTranslationResponse(string $aiResponse, array $originalQuestion): array
    {
        try {
            // Remove markdown code blocks if present
            $cleanResponse = $aiResponse;
            if (strpos($aiResponse, '```json') !== false) {
                $cleanResponse = preg_replace('/```json\s*/', '', $aiResponse);
                $cleanResponse = preg_replace('/```\s*$/', '', $cleanResponse ?? '');
            } elseif (strpos($aiResponse, '```') !== false) {
                $cleanResponse = preg_replace('/```\s*/', '', $aiResponse);
                $cleanResponse = preg_replace('/```\s*$/', '', $cleanResponse ?? '');
            }
            
            // Trim whitespace
            $cleanResponse = trim($cleanResponse ?? '');
            
            // Try to parse as complete JSON object
            $jsonStart = strpos($cleanResponse, '{');
            $jsonEnd = strrpos($cleanResponse, '}');
            
            if ($jsonStart !== false && $jsonEnd !== false) {
                $jsonStr = substr($cleanResponse, $jsonStart, $jsonEnd - $jsonStart + 1);
                $translationData = json_decode($jsonStr, true);
                
                if ($translationData !== null && isset($translationData['translated_question'])) {
                    return [
                        'success' => true,
                        'translated_data' => $translationData,
                        'original_data' => $originalQuestion
                    ];
                }
            }
            
            // If JSON parsing fails, try to extract translations manually
            $this->logger->warning('JSON parsing failed, attempting manual extraction');
            return $this->extractTranslationManually($aiResponse, $originalQuestion);
            
        } catch (\Exception $e) {
            $this->logger->error('Error parsing AI translation response: ' . $e->getMessage());
            $this->logger->error('AI Response: ' . $aiResponse);
        }
        
        return $this->getFallbackTranslation($originalQuestion, 'en');
    }

    /**
     * Manually extract translation data when JSON parsing fails
     */
    private function extractTranslationManually(string $aiResponse, array $originalQuestion): array
    {
        // Create a basic translation structure by extracting key-value pairs
        $translationData = [
            'translated_question' => $originalQuestion['question'] ?? '',
            'translated_option_a' => $originalQuestion['option_a'] ?? '',
            'translated_option_b' => $originalQuestion['option_b'] ?? '',
            'translated_option_c' => $originalQuestion['option_c'] ?? '',
            'translated_option_d' => $originalQuestion['option_d'] ?? '',
            'correct_option' => $originalQuestion['correct_option'] ?? 'A',
            'target_language' => 'en'
        ];
        
        // Try to extract translated question
        if (preg_match('/["\']translated_question["\']\s*:\s*["\']([^"\']+)["\']/', $aiResponse, $matches) === 1) {
            $translationData['translated_question'] = $matches[1];
        }
        
        // Try to extract translated options
        $options = ['a', 'b', 'c', 'd'];
        foreach ($options as $option) {
            $pattern = '/["\']translated_option_' . $option . '["\']\s*:\s*["\']([^"\']+)["\']/';
            if (preg_match($pattern, $aiResponse, $matches) === 1) {
                $translationData['translated_option_' . $option] = $matches[1];
            }
        }
        
        // Try to extract correct option
        if (preg_match('/["\']correct_option["\']\s*:\s*["\']([A-D])["\']/', $aiResponse, $matches) === 1) {
            $translationData['correct_option'] = $matches[1];
        }
        
        return [
            'success' => true,
            'translated_data' => $translationData,
            'original_data' => $originalQuestion
        ];
    }

    private function getFallbackQuestions(string $courseTitle, int $questionCount): array
    {
        $questions = [];
        
        for ($i = 1; $i <= $questionCount; $i++) {
            $questions[] = [
                'question' => "Sample question {$i} about {$courseTitle}",
                'option_a' => 'Sample option A',
                'option_b' => 'Sample option B',
                'option_c' => 'Sample option C',
                'option_d' => 'Sample option D',
                'correct_option' => 'A',
                'difficulty' => 'medium',
                'topic' => 'General knowledge',
                'explanation' => 'This is a sample question for demonstration purposes.'
            ];
        }
        
        return [
            'success' => false,
            'questions' => $questions,
            'message' => 'Using sample questions due to AI service unavailability.'
        ];
    }

    private function getFallbackTranslation(array $questionData, string $targetLanguage): array
    {
        return [
            'success' => false,
            'translated_data' => $questionData,
            'original_data' => $questionData,
            'message' => 'Translation service unavailable. Original question maintained.',
            'target_language' => $targetLanguage
        ];
    }

    /**
     * Get available categories for quiz generation
     *
     * @return array List of available categories
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
