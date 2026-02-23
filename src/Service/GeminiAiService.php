<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\Response;
use Psr\Log\LoggerInterface;

class GeminiAiService
{
    private HttpClientInterface $httpClient;
    private string $geminiApiKey;
    private LoggerInterface $logger;

    public function __construct(
        HttpClientInterface $httpClient,
        ParameterBagInterface $parameterBag,
        LoggerInterface $logger,
        string $apiKey
    ) {
        $this->httpClient = $httpClient;
        $this->apiKey = $apiKey;
        $this->logger = $logger;
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

            if (!$data || !isset($data['candidates'][0]['content']['parts'][0]['text'])) {
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

            if (!$data || !isset($data['candidates'][0]['content']['parts'][0]['text'])) {
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
                
                if ($roadmapData && isset($roadmapData['weeks'])) {
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

    private function makeApiRequest(string $prompt): Response
    {
        $url = 'https://generativelanguage.googleapis.com/v1/models/gemini-pro:generateContent?key=' . $this->apiKey;
        
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
                'maxOutputTokens' => 2048
            ]
        ];

        return $this->httpClient->request('POST', $url, [
            'json' => $payload,
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    private function getFallbackMessage(array $quizData): string
    {
        $totalQuizzes = count($quizData);
        
        if ($totalQuizzes === 0) {
            return "Start taking quizzes to receive personalized AI insights into your learning performance.";
        }

        // Calculate basic statistics for fallback
        $scores = array_column($quizData, 'percentage');
        $averageScore = count($scores) > 0 ? array_sum($scores) / count($scores) : 0;
        
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
}
