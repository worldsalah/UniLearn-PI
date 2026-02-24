<?php

namespace App\Controller\Api;

use App\Entity\Product;
use App\Entity\Job;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/api/chatbot')]
class ChatbotController extends AbstractController
{
    private HttpClientInterface $httpClient;
    private EntityManagerInterface $entityManager;
    private string $geminiApiKey;

    public function __construct(
        HttpClientInterface $httpClient,
        EntityManagerInterface $entityManager,
        string $geminiApiKey
    ) {
        $this->httpClient = $httpClient;
        $this->entityManager = $entityManager;
        $this->geminiApiKey = $geminiApiKey;
    }

    #[Route('/chat', name: 'api_chatbot_chat', methods: ['POST'])]
    public function chat(Request $request): JsonResponse
    {
        $userMessage = $request->get('message', '');
        $conversationHistory = $request->get('history', []);

        if (empty($userMessage)) {
            return new JsonResponse(['error' => 'Message is required'], 400);
        }

        try {
            // Get services and job requests from database
            $services = $this->getServicesFromDB();
            $jobRequests = $this->getJobRequestsFromDB();
            
            // Get user context if logged in
            $userContext = $this->getUserContext();
            
            // Build smart prompt
            $prompt = $this->buildSmartPrompt($userMessage, $services, $jobRequests, $userContext, $conversationHistory);
            
            // Call Gemini API
            $aiResponse = $this->callGeminiAPI($prompt);
            
            // Extract recommendations from response
            $recommendations = $this->extractRecommendations($aiResponse, $services, $jobRequests);
            
            return new JsonResponse([
                'reply' => $aiResponse,
                'recommendations' => $recommendations,
                'timestamp' => new \DateTime()
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Chatbot service temporarily unavailable',
                'reply' => 'I apologize, but I\'m having trouble connecting right now. Please try again later or browse our services directly.'
            ], 500);
        }
    }

    /**
     * Query database for services (Products)
     */
    private function getServicesFromDB(): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        
        $products = $qb->select('p', 'c', 'u')
            ->from(Product::class, 'p')
            ->leftJoin('p.category', 'c')
            ->leftJoin('p.freelancer', 'u')
            ->where('p.deletedAt IS NULL')
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults(50)
            ->getQuery()
            ->getResult();

        $services = [];
        foreach ($products as $product) {
            $services[] = [
                'id' => $product->getId(),
                'title' => $product->getTitle(),
                'description' => substr($product->getDescription(), 0, 200) . '...',
                'price' => $product->getPrice(),
                'category' => $product->getCategory()?->getName(),
                'freelancer' => $product->getFreelancer()?->getFullName(),
                'slug' => $product->getSlug(),
                'type' => 'service'
            ];
        }

        return $services;
    }

    /**
     * Query database for job requests
     */
    private function getJobRequestsFromDB(): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        
        $jobs = $qb->select('j', 'c')
            ->from(Job::class, 'j')
            ->leftJoin('j.client', 'c')
            ->where('j.deletedAt IS NULL')
            ->andWhere('j.status = :status')
            ->setParameter('status', 'open')
            ->orderBy('j.createdAt', 'DESC')
            ->setMaxResults(30)
            ->getQuery()
            ->getResult();

        $jobRequests = [];
        foreach ($jobs as $job) {
            $jobRequests[] = [
                'id' => $job->getId(),
                'title' => $job->getTitle(),
                'description' => substr($job->getDescription(), 0, 200) . '...',
                'budget' => $job->getBudget(),
                'status' => $job->getStatus(),
                'skills' => $job->getSkills(),
                'location' => $job->getLocation(),
                'type' => 'job',
                'slug' => $job->getSlug()
            ];
        }

        return $jobRequests;
    }

    /**
     * Get user context if logged in
     */
    private function getUserContext(): array
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return [];
        }

        // Get user's order history
        $qb = $this->entityManager->createQueryBuilder();
        $orders = $qb->select('o', 'p')
            ->from('App\Entity\Order', 'o')
            ->leftJoin('o.product', 'p')
            ->where('o.user = :user')
            ->setParameter('user', $user)
            ->orderBy('o.createdAt', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        $orderHistory = [];
        foreach ($orders as $order) {
            $orderHistory[] = [
                'product_title' => $order->getProduct()?->getTitle(),
                'price' => $order->getProduct()?->getPrice(),
                'created_at' => $order->getCreatedAt()?->format('Y-m-d')
            ];
        }

        return [
            'id' => $user->getId(),
            'name' => $user->getFullName(),
            'email' => $user->getEmail(),
            'order_history' => $orderHistory,
            'is_freelancer' => in_array('ROLE_FREELANCER', $user->getRoles()),
            'is_client' => in_array('ROLE_CLIENT', $user->getRoles())
        ];
    }

    /**
     * Build smart prompt with context
     */
    private function buildSmartPrompt(string $userMessage, array $services, array $jobRequests, array $userContext, array $conversationHistory): string
    {
        // Analyze user intent and needs
        $userNeeds = $this->analyzeUserNeeds($userMessage);
        $priceSensitivity = $this->detectPriceSensitivity($userMessage);
        $urgencyLevel = $this->detectUrgency($userMessage);
        $qualityPreference = $this->detectQualityPreference($userMessage);
        
        $prompt = "You are MAX, an expert sales consultant and success coach for UniLearn Marketplace. You have an impressive 95% success rate in helping customers find perfect services that transform their businesses and careers.

Your personality traits:
- Confident and knowledgeable, but never arrogant
- Enthusiastic and motivating
- Results-oriented and action-driven
- Builds trust through expertise and empathy
- Uses persuasive language that creates urgency without pressure
- Always focuses on benefits and outcomes, not just features

Your sales approach:
1. ACKNOWLEDGE & VALIDATE: Show you understand their specific needs
2. EDUCATE & POSITION: Briefly explain why your solution is perfect
3. SOCIAL PROOF: Mention success stories and results
4. HANDLE OBJECTIONS: Proactively address concerns about price, time, quality
5. CREATE URGENCY: Explain why acting now is beneficial
6. CALL TO ACTION: Clear next steps with multiple options

Available Services:
" . $this->formatServices($services) . "

Recent Job Requests:
" . $this->formatJobRequests($jobRequests) . "";

        // Add user context for personalization
        if (!empty($userContext)) {
            $prompt .= "
USER PROFILE:
- Name: " . $userContext['name'] . "
- Account Type: " . ($userContext['is_freelancer'] ? 'Freelancer Provider' : 'Service Buyer') . "
- Experience Level: " . ($userContext['order_history'] ? 'Returning Customer' : 'New Customer') . "
- Past Interests: " . (!empty($userContext['order_history']) ? 'Previously purchased: ' . implode(', ', array_column($userContext['order_history'], 'product_title')) : 'No previous purchases') . "";
        }

        // Add conversation context for continuity
        if (!empty($conversationHistory)) {
            $prompt .= "
PREVIOUS CONVERSATION:
" . $this->formatConversationHistory($conversationHistory) . "";
        }

        $prompt .= "
CURRENT INTERACTION:
Customer says: \"{$userMessage}\"

ANALYSIS:
- User Needs: " . implode(', ', $userNeeds) . "
- Price Sensitivity: {$priceSensitivity}
- Urgency Level: {$urgencyLevel}
- Quality Preference: {$qualityPreference}

YOUR TASK:
1. Start with an enthusiastic, personalized greeting
2. Acknowledge their specific needs and show you understand
3. Recommend 2-3 perfect services/jobs that match their needs
4. For each recommendation, explain:
   - Why it's perfect for THEIR specific situation
   - What results/outcomes they can expect
   - How it compares to alternatives (favorably)
   - Address potential objections (price, time, quality)
5. Use persuasive language that creates excitement and urgency
6. Include social proof elements (success stories, popularity)
7. End with a clear call to action with options

PERSUASION TECHNIQUES TO USE:
- \"Imagine yourself completing this project with this solution\"
- \"Join hundreds of successful customers who chose this\"
- \"This is exactly what [successful people in their field] use\"
- \"The cost of NOT doing this is much higher\"
- \"Limited time offer\" or \"Special discount for quick action\"
- \"Risk-free guarantee\" or \"Satisfaction guaranteed\"

RESPONSE STRUCTURE:
1. ENTHUSIASTIC GREETING: Personalized and energetic
2. NEEDS ACKNOWLEDGMENT: Show you understand their goals
3. RECOMMENDATIONS: 2-3 perfect matches with detailed explanations
4. BENEFITS-FOCUSED SELLING: Focus on outcomes, not features
5. OBJECTION HANDLING: Proactively address price/quality/time concerns
6. SOCIAL PROOF: Success stories, testimonials, popularity
7. URGENCY CREATION: Limited time, special offers, opportunity cost
8. CLEAR CALL TO ACTION: Multiple options, next steps

REMEMBER: You're not just helping - you're ADVISING them toward success. Be their trusted expert guide who wants them to win!

Now provide your expert recommendation:";

        return $prompt;
    }

    /**
     * Analyze user needs from message
     */
    private function analyzeUserNeeds(string $message): array
    {
        $needs = [];
        $message = strtolower($message);
        
        // Detect specific needs
        if (preg_match('/web|site|app|development|dev|programming|coding/i', $message)) {
            $needs[] = 'web development';
        }
        if (preg_match('/design|ui|ux|graphic|logo|brand/i', $message)) {
            $needs[] = 'design services';
        }
        if (preg_match('/writing|content|blog|article|copy/i', $message)) {
            $needs[] = 'content creation';
        }
        if (preg_match('/marketing|seo|social|ads|promotion/i', $message)) {
            $needs[] = 'digital marketing';
        }
        if (preg_match('/video|course|tutorial|education|learn/i', $message)) {
            $needs[] = 'educational content';
        }
        if (preg_match('/cheap|budget|affordable|low cost|inexpensive/i', $message)) {
            $needs[] = 'budget-friendly options';
        }
        if (preg_match('/urgent|quick|fast|asap|immediate/i', $message)) {
            $needs[] = 'quick delivery';
        }
        if (preg_match('/professional|expert|premium|quality|best/i', $message)) {
            $needs[] = 'high-quality services';
        }
        
        return empty($needs) ? ['general assistance'] : $needs;
    }

    /**
     * Detect price sensitivity
     */
    private function detectPriceSensitivity(string $message): string
    {
        $message = strtolower($message);
        
        if (preg_match('/cheap|budget|affordable|low cost|inexpensive|discount|save/i', $message)) {
            return 'HIGHLY PRICE SENSITIVE';
        } elseif (preg_match('/reasonable|moderate|mid-range/i', $message)) {
            return 'PRICE CONSCIOUS';
        } elseif (preg_match('/premium|expensive|quality|best|professional/i', $message)) {
            return 'SEEKS PREMIUM QUALITY';
        } else {
            return 'PRICE NEUTRAL';
        }
    }

    /**
     * Detect urgency level
     */
    private function detectUrgency(string $message): string
    {
        $message = strtolower($message);
        
        if (preg_match('/urgent|asap|immediate|quick|fast|today|now|emergency/i', $message)) {
            return 'EXTREME URGENCY';
        } elseif (preg_match('/soon|this week|next few days/i', $message)) {
            return 'HIGH URGENCY';
        } elseif (preg_match('/considering|thinking|maybe|planning/i', $message)) {
            return 'MODERATE URGENCY';
        } else {
            return 'LOW URGENCY';
        }
    }

    /**
     * Detect quality preference
     */
    private function detectQualityPreference(string $message): string
    {
        $message = strtolower($message);
        
        if (preg_match('/professional|expert|premium|quality|best|top-rated|experienced/i', $message)) {
            return 'SEEKS PREMIUM QUALITY';
        } elseif (preg_match('/good|reliable|solid|decent/i', $message)) {
            return 'WANTS GOOD QUALITY';
        } elseif (preg_match('/basic|simple|beginner|starter/i', $message)) {
            return 'SEEKS BASIC FUNCTIONALITY';
        } else {
            return 'QUALITY NEUTRAL';
        }
    }

    /**
     * Format services for prompt
     */
    private function formatServices(array $services): string
    {
        if (empty($services)) {
            return "No services available at the moment.";
        }

        $formatted = "";
        foreach ($services as $service) {
            $formatted .= "- {$service['title']} (\${$service['price']}) - {$service['category']} - By {$service['freelancer']}\n";
        }
        
        return $formatted;
    }

    /**
     * Format job requests for prompt
     */
    private function formatJobRequests(array $jobs): string
    {
        if (empty($jobs)) {
            return "No job requests available at the moment.";
        }

        $formatted = "";
        foreach ($jobs as $job) {
            $formatted .= "- {$job['title']} (Budget: \${$job['budget']}) - {$job['skills']}\n";
        }
        
        return $formatted;
    }

    /**
     * Format conversation history for prompt
     */
    private function formatConversationHistory(array $history): string
    {
        $formatted = "";
        foreach ($history as $turn) {
            $formatted .= "User: {$turn['user']}\nAssistant: {$turn['assistant']}\n";
        }
        return $formatted;
    }

    /**
     * Call Gemini API
     */
    private function callGeminiAPI(string $prompt): string
    {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$this->geminiApiKey}";
        
        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.7,
                'topK' => 40,
                'topP' => 0.95,
                'maxOutputTokens' => 1000,
            ]
        ];

        try {
            $response = $this->httpClient->request('POST', $url, [
                'json' => $payload,
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'timeout' => 30
            ]);

            $statusCode = $response->getStatusCode();
            $content = $response->getContent(false);

            if ($statusCode !== 200) {
                throw new \Exception("Gemini API returned status {$statusCode}: {$content}");
            }

            $data = json_decode($content, true);

            if (!isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                throw new \Exception("Invalid Gemini API response format");
            }

            return $data['candidates'][0]['content']['parts'][0]['text'];

        } catch (\Exception $e) {
            // Log the error for debugging
            error_log('Gemini API Error: ' . $e->getMessage());
            
            // Return a fallback response
            return "I'm your AI assistant for the UniLearn Marketplace! I can help you find the perfect services or job opportunities. Based on your needs, I recommend checking out our web development, design, and content writing services. What specific type of service are you looking for today?";
        }
    }

    /**
     * Extract recommendations from AI response
     */
    private function extractRecommendations(string $aiResponse, array $services, array $jobRequests): array
    {
        $recommendations = [];
        
        // Extract service recommendations
        foreach ($services as $service) {
            if (stripos($aiResponse, $service['title']) !== false) {
                $recommendations[] = [
                    'type' => 'service',
                    'id' => $service['id'],
                    'title' => $service['title'],
                    'price' => $service['price'],
                    'category' => $service['category'],
                    'slug' => $service['slug']
                ];
            }
        }
        
        // Extract job recommendations
        foreach ($jobRequests as $job) {
            if (stripos($aiResponse, $job['title']) !== false) {
                $recommendations[] = [
                    'type' => 'job',
                    'id' => $job['id'],
                    'title' => $job['title'],
                    'budget' => $job['budget'],
                    'slug' => $job['slug']
                ];
            }
        }
        
        return array_unique($recommendations, SORT_REGULAR);
    }
}
