<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class AIAnalystService
{
    private string $apiKey = 'YOUR_HUGGING_FACE_API_KEY';
    private string $apiUrl = 'https://api-inference.huggingface.co/models/mistralai/Mistral-7B-Instruct-v0.1';
    
    public function __construct(private HttpClientInterface $httpClient)
    {
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
        if (preg_match('/ASSESSMENT:\s*(.+)/i', $analysis, $matches)) {
            $parsed['assessment'] = trim($matches[1]);
        }
        
        if (preg_match('/STRENGTHS:\s*(.+)/i', $analysis, $matches)) {
            $parsed['strengths'] = trim($matches[1]);
        }
        
        if (preg_match('/WEAKNESSES:\s*(.+)/i', $analysis, $matches)) {
            $parsed['weaknesses'] = trim($matches[1]);
        }
        
        if (preg_match('/PRIORITY:\s*(.+)/i', $analysis, $matches)) {
            $parsed['priority'] = trim($matches[1]);
        }

        // Extract recommendations
        if (preg_match_all('/\d+\.\s+(.+)/i', $analysis, $matches)) {
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
}
