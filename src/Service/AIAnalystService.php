<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class AIAnalystService
{
    private string $apiKey;
    private string $apiUrl;
    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;
    
    public function __construct(HttpClientInterface $httpClient, LoggerInterface $logger, string $huggingFaceApiKey = null)
    {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->apiKey = $huggingFaceApiKey ?: $_ENV['HUGGING_FACE_API_KEY'] ?? 'YOUR_HUGGING_FACE_API_KEY';
        $this->apiUrl = 'https://api-inference.huggingface.co/models/mistralai/Mistral-7B-Instruct-v0.1';
    }

    public function analyzeMarketplaceData(array $stats, array $trends = []): array
    {
        $prompt = $this->buildAnalysisPrompt($stats, $trends);
        
        try {
            $response = $this->httpClient->request('POST', $this->apiUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'inputs' => $prompt,
                    'parameters' => [
                        'max_new_tokens' => 500,
                        'temperature' => 0.7,
                        'do_sample' => true,
                    ]
                ]
            ]);

            $data = $response->toArray();
            
            if (isset($data[0]['generated_text'])) {
                $analysis = $data[0]['generated_text'];
                return $this->parseAnalysis($analysis);
            }
        } catch (\Exception $e) {
            $this->logger->error('AI Analyst Service Error: ' . $e->getMessage());
            // Fallback to basic analysis if AI fails
            return $this->generateFallbackAnalysis($stats, $trends);
        }

        return $this->generateFallbackAnalysis($stats, $trends);
    }

    private function buildAnalysisPrompt(array $stats, array $trends): string
    {
        $prompt = "You are an expert business analyst for a freelance marketplace. Analyze these statistics and provide actionable insights:\n\n";
        
        $prompt .= "CURRENT STATISTICS:\n";
        $prompt .= "- Active Freelancers: {$stats['students']}\n";
        $prompt .= "- Total Services: {$stats['products']}\n";
        $prompt .= "- Job Requests: {$stats['jobs']}\n";
        $prompt .= "- Total Orders: {$stats['orders']}\n";
        $prompt .= "- Revenue: \${$stats['revenue']}\n\n";

        if (!empty($trends)) {
            $prompt .= "RECENT TRENDS:\n";
            foreach ($trends as $key => $value) {
                $prompt .= "- {$key}: {$value}\n";
            }
        }

        $prompt .= "\nProvide a concise analysis with:\n";
        $prompt .= "1. Overall performance assessment (Excellent/Good/Fair/Poor)\n";
        $prompt .= "2. Key strengths and weaknesses\n";
        $prompt .= "3. 3 specific actionable recommendations\n";
        $prompt .= "4. Priority level (High/Medium/Low)\n\n";
        
        $prompt .= "Format your response as:\n";
        $prompt .= "ASSESSMENT: [assessment]\n";
        $prompt .= "STRENGTHS: [strengths]\n";
        $prompt .= "WEAKNESSES: [weaknesses]\n";
        $prompt .= "RECOMMENDATIONS:\n1. [recommendation 1]\n2. [recommendation 2]\n3. [recommendation 3]\n";
        $prompt .= "PRIORITY: [priority]";

        return $prompt;
    }

    private function parseAnalysis(string $analysis): array
    {
        $parsed = [
            'assessment' => 'Good',
            'strengths' => 'Solid user base and service offerings',
            'weaknesses' => 'Could improve conversion rates',
            'recommendations' => [
                'Optimize service descriptions for better conversion',
                'Implement targeted marketing campaigns',
                'Enhance user onboarding experience'
            ],
            'priority' => 'Medium',
            'insights' => $analysis
        ];

        // Parse the structured response
        if (preg_match('/ASSESSMENT:\s*(.+)/i', $analysis, $matches) === 1) {
            $parsed['assessment'] = trim($matches[1]);
        }
        
        if (preg_match('/STRENGTHS:\s*(.+)/i', $analysis, $matches) === 1) {
            $parsed['strengths'] = trim($matches[1]);
        }
        
        if (preg_match('/WEAKNESSES:\s*(.+)/i', $analysis, $matches) === 1) {
            $parsed['weaknesses'] = trim($matches[1]);
        }
        
        if (preg_match('/PRIORITY:\s*(.+)/i', $analysis, $matches) === 1) {
            $parsed['priority'] = trim($matches[1]);
        }

        // Extract recommendations
        if (preg_match_all('/\d+\.\s+(.+)/i', $analysis, $matches) > 0) {
            $parsed['recommendations'] = array_map('trim', $matches[1]);
        }

        return $parsed;
    }

    private function generateFallbackAnalysis(array $stats, array $trends): array
    {
        $revenue = $stats['revenue'] ?? 0;
        $orders = $stats['orders'] ?? 0;
        $services = $stats['products'] ?? 0;
        $freelancers = $stats['students'] ?? 0;

        // Basic logic for assessment
        if ($revenue > 10000 && $orders > 100) {
            $assessment = 'Excellent';
            $priority = 'Low';
        } elseif ($revenue > 5000 && $orders > 50) {
            $assessment = 'Good';
            $priority = 'Medium';
        } else {
            $assessment = 'Fair';
            $priority = 'High';
        }

        $avgRevenuePerOrder = $orders > 0 ? $revenue / $orders : 0;
        $servicesPerFreelancer = $freelancers > 0 ? $services / $freelancers : 0;

        return [
            'assessment' => $assessment,
            'strengths' => $servicesPerFreelancer > 2 ? 'Good service variety per freelancer' : 'Solid freelancer base',
            'weaknesses' => $avgRevenuePerOrder < 100 ? 'Low average order value' : 'Could increase order frequency',
            'recommendations' => [
                $avgRevenuePerOrder < 100 ? 'Increase average order value with upselling' : 'Focus on increasing order frequency',
                $servicesPerFreelancer < 2 ? 'Encourage freelancers to offer more services' : 'Optimize service pricing strategy',
                'Implement referral program to grow freelancer base'
            ],
            'priority' => $priority,
            'insights' => 'Basic analysis based on current metrics'
        ];
    }

    public function generateTrendInsights(array $currentStats, array $previousStats): array
    {
        $trends = [];
        
        foreach (['students', 'products', 'jobs', 'orders', 'revenue'] as $key) {
            $current = $currentStats[$key] ?? 0;
            $previous = $previousStats[$key] ?? 0;
            
            if ($previous > 0) {
                $change = (($current - $previous) / $previous) * 100;
                $trends[$key] = [
                    'current' => $current,
                    'previous' => $previous,
                    'change' => round($change, 1),
                    'direction' => $change >= 0 ? 'up' : 'down'
                ];
            }
        }

        return $trends;
    }

    /**
     * Analyze learning patterns and provide recommendations
     *
     * @param array $learningData User learning data
     * @return array Analysis results
     */
    public function analyzeLearningPatterns(array $learningData): array
    {
        $prompt = $this->buildLearningAnalysisPrompt($learningData);
        
        try {
            $response = $this->httpClient->request('POST', $this->apiUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'inputs' => $prompt,
                    'parameters' => [
                        'max_new_tokens' => 500,
                        'temperature' => 0.7,
                        'do_sample' => true,
                    ]
                ]
            ]);

            $data = $response->toArray();
            
            if (isset($data[0]['generated_text'])) {
                $analysis = $data[0]['generated_text'];
                return $this->parseLearningAnalysis($analysis);
            }
        } catch (\Exception $e) {
            $this->logger->error('AI Learning Analysis Error: ' . $e->getMessage());
            return $this->generateFallbackLearningAnalysis($learningData);
        }

        return $this->generateFallbackLearningAnalysis($learningData);
    }

    private function buildLearningAnalysisPrompt(array $learningData): string
    {
        $prompt = "You are an expert educational analyst. Analyze this student's learning data and provide personalized recommendations:\n\n";
        
        $prompt .= "LEARNING DATA:\n";
        $prompt .= "- Courses Completed: {$learningData['courses_completed']}\n";
        $prompt .= "- Average Quiz Score: {$learningData['avg_quiz_score']}%\n";
        $prompt .= "- Study Time: {$learningData['study_hours']} hours\n";
        $prompt .= "- Learning Streak: {$learningData['learning_streak']} days\n";
        $prompt .= "- Preferred Topics: " . implode(', ', $learningData['preferred_topics'] ?? []) . "\n\n";

        $prompt .= "Provide analysis with:\n";
        $prompt .= "1. Learning performance assessment\n";
        $prompt .= "2. Strengths and areas for improvement\n";
        $prompt .= "3. Personalized recommendations\n";
        $prompt .= "4. Suggested next steps\n\n";
        
        $prompt .= "Format your response as:\n";
        $prompt .= "PERFORMANCE: [assessment]\n";
        $prompt .= "STRENGTHS: [strengths]\n";
        $prompt .= "IMPROVEMENTS: [areas needing improvement]\n";
        $prompt .= "RECOMMENDATIONS:\n1. [recommendation 1]\n2. [recommendation 2]\n3. [recommendation 3]\n";
        $prompt .= "NEXT_STEPS: [suggested next steps]";

        return $prompt;
    }

    private function parseLearningAnalysis(string $analysis): array
    {
        $parsed = [
            'performance' => 'Good Progress',
            'strengths' => 'Consistent study habits',
            'improvements' => 'Could improve quiz scores',
            'recommendations' => [
                'Focus on weak areas identified in quizzes',
                'Increase daily study time by 30 minutes',
                'Try different learning modalities'
            ],
            'next_steps' => 'Continue with current learning path',
            'insights' => $analysis
        ];

        // Parse structured response
        if (preg_match('/PERFORMANCE:\s*(.+)/i', $analysis, $matches) === 1) {
            $parsed['performance'] = trim($matches[1]);
        }
        
        if (preg_match('/STRENGTHS:\s*(.+)/i', $analysis, $matches) === 1) {
            $parsed['strengths'] = trim($matches[1]);
        }
        
        if (preg_match('/IMPROVEMENTS:\s*(.+)/i', $analysis, $matches) === 1) {
            $parsed['improvements'] = trim($matches[1]);
        }

        if (preg_match('/NEXT_STEPS:\s*(.+)/i', $analysis, $matches) === 1) {
            $parsed['next_steps'] = trim($matches[1]);
        }

        // Extract recommendations
        if (preg_match_all('/\d+\.\s+(.+)/i', $analysis, $matches) > 0) {
            $parsed['recommendations'] = array_map('trim', $matches[1]);
        }

        return $parsed;
    }

    private function generateFallbackLearningAnalysis(array $learningData): array
    {
        $avgScore = $learningData['avg_quiz_score'] ?? 0;
        $studyHours = $learningData['study_hours'] ?? 0;
        $streak = $learningData['learning_streak'] ?? 0;

        if ($avgScore >= 80) {
            $performance = 'Excellent';
            $recommendations = ['Take on advanced courses', 'Help other students', 'Explore specialized topics'];
        } elseif ($avgScore >= 60) {
            $performance = 'Good Progress';
            $recommendations = ['Review quiz feedback', 'Increase practice time', 'Join study groups'];
        } else {
            $performance = 'Needs Improvement';
            $recommendations = ['Focus on fundamentals', 'Seek additional help', 'Reduce course load'];
        }

        return [
            'performance' => $performance,
            'strengths' => $streak > 7 ? 'Consistent learning habits' : 'Active engagement',
            'improvements' => $avgScore < 70 ? 'Quiz performance needs attention' : 'Could increase study consistency',
            'recommendations' => $recommendations,
            'next_steps' => 'Continue with recommended improvements',
            'insights' => 'Basic analysis based on learning metrics'
        ];
    }
}
