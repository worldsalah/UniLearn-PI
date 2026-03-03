<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Doctrine\ORM\EntityManagerInterface;


class GeminiChatbotService
{
    private HttpClientInterface $httpClient;
    private string $apiKey;
    private ?EntityManagerInterface $entityManager;

    public function __construct(HttpClientInterface $httpClient, string $geminiApiKey, ?EntityManagerInterface $entityManager = null)
    {
        $this->httpClient = $httpClient;
        $this->apiKey = $geminiApiKey;
        $this->entityManager = $entityManager;
    }

    public function sendMessage(string $message, array $context = [], string $language = 'en', ?int $userId = null, array $attachments = []): array
    {
        try {
            // Check for direct database queries first
            $dbResult = $this->handleDatabaseQuery($message);
            if ($dbResult !== null) {
                return $dbResult;
            }
            
            // Build prompt with attachments info
            $prompt = $this->buildPrompt($message, $context, $language, $userId, $attachments);
            
            // OpenRouter API - supports multiple models
            $url = 'https://openrouter.ai/api/v1/chat/completions';
            
            $response = $this->httpClient->request('POST', $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                    'HTTP-Referer' => 'http://127.0.0.1:8000',
                    'X-Title' => 'UniLearn Chatbot',
                ],
                'json' => [
                    'model' => 'openrouter/free',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => $this->getSystemPrompt($language)
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt
                        ]
                    ],
                    'max_tokens' => 1000,
                    'temperature' => 0.7,
                ],
                'timeout' => 60
            ]);

            $statusCode = $response->getStatusCode();
            $data = $response->toArray(false);
            
            if ($statusCode === 200 && isset($data['choices'][0]['message']['content'])) {
                $content = $data['choices'][0]['message']['content'];
                
                // Parse actions from response
                $result = $this->parseResponse($content);
                
                return [
                    'success' => true,
                    'message' => $result['message'],
                    'action' => $result['action'],
                    'courses' => null,
                    'instructors' => null,
                    'speak' => true,
                    'error' => null
                ];
            }
            
            return [
                'success' => false,
                'message' => null,
                'action' => null,
                'courses' => null,
                'instructors' => null,
                'error' => $data['error']['message'] ?? 'API Error: HTTP ' . $statusCode
            ];

        } catch (\Exception $e) {
            // Log the error
            error_log('Chatbot API Error: ' . $e->getMessage());
            
            // Return a fallback response
            return [
                'success' => true,
                'message' => 'I\'m having trouble connecting to my AI service right now. Please try again in a moment, or browse our courses at /courses.',
                'action' => null,
                'courses' => null,
                'instructors' => null,
                'speak' => true,
                'error' => null
            ];
        }
    }
    
    /**
     * Handle direct database queries for courses and instructors
     */
    private function handleDatabaseQuery(string $message): ?array
    {
        if ($this->entityManager === null) {
            return null;
        }
        
        $messageLower = strtolower($message);
        
        // Detect course search queries
        if (preg_match('/(search|find|show|list|what).*(course|courses)/i', $message) !== false && preg_match('/(search|find|show|list|what).*(course|courses)/i', $message) === 1 || 
            preg_match('/courses.*(available|offered)/i', $message) !== false && preg_match('/courses.*(available|offered)/i', $message) === 1 ||
            preg_match('/popular.*course/i', $message) !== false && preg_match('/popular.*course/i', $message) === 1) {
            
            // Extract search term if any
            $searchTerm = null;
            if (preg_match('/(?:search|find|show).*course[s]?.*(?:for|about|in|on)\s+([\w\s]+)/i', $message, $matches) === 1) {
                $searchTerm = trim($matches[1]);
            } elseif (preg_match('/([\w\s]+)\s+course/i', $message, $matches) === 1) {
                $searchTerm = trim($matches[1]);
            }
            
            $courses = $this->searchCourses($searchTerm);
            if (!empty($courses)) {
                return [
                    'success' => true,
                    'message' => $searchTerm !== null ? 
                        "I found " . count($courses) . " course(s) matching '$searchTerm':" :
                        "Here are some popular courses on UniLearn:",
                    'courses' => $courses,
                    'instructors' => null,
                    'action' => null,
                    'speak' => true,
                    'error' => null
                ];
            }
        }
        
        // Detect instructor search queries
        if (preg_match('/(search|find|show|list|who).*(instructor|teacher|tutor)/i', $message) === 1 ||
            preg_match('/instructor[s]?.*(available)/i', $message) === 1) {
            
            $instructors = $this->searchInstructors();
            if (!empty($instructors)) {
                return [
                    'success' => true,
                    'message' => 'Here are our top instructors:',
                    'courses' => null,
                    'instructors' => $instructors,
                    'action' => null,
                    'speak' => true,
                    'error' => null
                ];
            }
        }
        
        // Detect tutoring session queries
        if (preg_match('/(search|find|show|list|what|available|check).*(session|sessions|tutoring|booking)/i', $message) === 1 ||
            preg_match('/(book|schedule).*(session|tutoring)/i', $message) === 1 ||
            preg_match('/(best|cheapest|affordable).*(session|price)/i', $message) === 1) {
            
            $sessions = $this->searchSessions($message);
            if (!empty($sessions)) {
                $isBestValue = preg_match('/(best|cheapest|affordable|value)/i', $message) === 1;
                $message_text = $isBestValue ? 
                    "I found " . count($sessions) . " great value tutoring session(s)! Here are the best options sorted by price:" :
                    "I found " . count($sessions) . " available tutoring session(s). Click below to view all sessions on our sessions page:";
                    
                return [
                    'success' => true,
                    'message' => $message_text,
                    'courses' => null,
                    'instructors' => null,
                    'sessions' => $sessions,
                    'action' => ['type' => 'navigate', 'url' => '/sessions'],
                    'speak' => true,
                    'error' => null
                ];
            } else {
                return [
                    'success' => true,
                    'message' => "I couldn't find any available tutoring sessions at the moment. You can browse all sessions on our dedicated sessions page:",
                    'courses' => null,
                    'instructors' => null,
                    'action' => ['type' => 'navigate', 'url' => '/sessions'],
                    'speak' => true,
                    'error' => null
                ];
            }
        }
        
        return null;
    }
    
    /**
     * Search courses in database
     */
    private function searchCourses(?string $term = null, int $limit = 5): array
    {
        if ($this->entityManager === null) {
            return [];
        }
        
        try {
            $qb = $this->entityManager->createQueryBuilder();
            $qb->select('c')
               ->from('App\Entity\Course', 'c')
               ->where('c.isPublished = :published')
               ->setParameter('published', true)
               ->orderBy('c.enrollmentCount', 'DESC')
               ->setMaxResults($limit);
            
            if ($term !== null && $term !== '') {
                $qb->andWhere('c.title LIKE :term OR c.description LIKE :term')
                   ->setParameter('term', '%' . $term . '%');
            }
            
            $courses = $qb->getQuery()->getResult();
            
            $result = [];
            foreach ($courses as $course) {
                $result[] = [
                    'id' => $course->getId(),
                    'title' => $course->getTitle(),
                    'price' => $course->getPrice(),
                    'level' => $course->getLevel(),
                    'image' => $course->getThumbnail(),
                    'instructor' => $course->getInstructor()?->getName(),
                    'rating' => $course->getAverageRating(),
                    'students' => $course->getEnrollmentCount()
                ];
            }
            
            return $result;
        } catch (\Exception $e) {
            return [];
        }
    }
    
    /**
     * Search instructors in database
     */
    private function searchInstructors(int $limit = 5): array
    {
        if ($this->entityManager === null) {
            return [];
        }
        
        try {
            $qb = $this->entityManager->createQueryBuilder();
            $qb->select('u')
               ->from('App\Entity\User', 'u')
               ->join('u.role', 'r')
               ->where('r.name = :role')
               ->setParameter('role', 'instructor')
               ->setMaxResults($limit);
            
            $instructors = $qb->getQuery()->getResult();
            
            $result = [];
            foreach ($instructors as $instructor) {
                $result[] = [
                    'id' => $instructor->getId(),
                    'name' => $instructor->getFullName(),
                    'avatar' => $instructor->getAvatar(),
                    'specialty' => $instructor->getSpecialty(),
                    'rating' => $instructor->getAverageRating(),
                    'students' => $instructor->getStudentCount()
                ];
            }
            
            return $result;
        } catch (\Exception $e) {
            return [];
        }
    }
    
    /**
     * Search available tutoring sessions
     */
    private function searchSessions(string $message, int $limit = 5): array
    {
        if ($this->entityManager === null) {
            return [];
        }
        
        try {
            // Check if looking for best value/cheapest
            $isBestValue = preg_match('/(best|cheapest|affordable|value)/i', $message);
            
            $qb = $this->entityManager->createQueryBuilder();
            $qb->select('s', 'i', 'c')
               ->from('App\Entity\Session', 's')
               ->leftJoin('s.instructor', 'i')
               ->leftJoin('s.category', 'c')
               ->where('s.startDate > :now')
               ->andWhere('s.hourlyPrice IS NOT NULL')
               ->setParameter('now', new \DateTime())
               ->setMaxResults($limit);
            
            if ($isBestValue === 1) {
                // Sort by price for best value
                $qb->orderBy('s.hourlyPrice', 'ASC')
                   ->addOrderBy('s.startDate', 'ASC');
            } else {
                // Sort by date for availability
                $qb->orderBy('s.startDate', 'ASC')
                   ->addOrderBy('s.hourlyPrice', 'ASC');
            }
            
            $sessions = $qb->getQuery()->getResult();
            
            $result = [];
            foreach ($sessions as $session) {
                $instructor = $session->getInstructor();
                $category = $session->getCategory();
                
                $result[] = [
                    'id' => $session->getId(),
                    'title' => $session->getName(),
                    'description' => $session->getSessionDescription(),
                    'level' => $session->getLevel(),
                    'price' => $session->getHourlyPrice(),
                    'duration' => $session->getDuration(),
                    'instructor' => $instructor ? $instructor->getFullName() : 'Unknown',
                    'category' => $category ? $category->getName() : 'General',
                    'startDate' => $session->getStartDate() ? $session->getStartDate()->format('M d, Y') : 'TBD',
                    'availableFrom' => $session->getAvailableFrom() ? $session->getAvailableFrom()->format('h:i A') : null,
                    'availableTo' => $session->getAvailableTo() ? $session->getAvailableTo()->format('h:i A') : null,
                    'url' => '/booking?session_id=' . $session->getId()
                ];
            }
            
            return $result;
        } catch (\Exception $e) {
            return [];
        }
    }
    
    /**
     * Get personalized recommendations for a user
     */
    public function getPersonalizedRecommendations(int $userId): array
    {
        if ($this->entityManager === null) {
            return ['success' => false, 'message' => 'Database not available'];
        }
        
        try {
            $user = $this->entityManager->find('App\Entity\User', $userId);
            if ($user === null) {
                return ['success' => false, 'message' => 'User not found'];
            }
            
            // Get user's enrolled courses categories
            $enrolledCourses = $this->entityManager->createQueryBuilder()
                ->select('c.category')
                ->from('App\Entity\Enrollment', 'e')
                ->join('e.course', 'c')
                ->where('e.user = :user')
                ->setParameter('user', $user)
                ->getQuery()->getResult();
            
            $categories = array_column($enrolledCourses, 'category');
            
            // Recommend courses from similar categories
            $qb = $this->entityManager->createQueryBuilder();
            $qb->select('c')
               ->from('App\Entity\Course', 'c')
               ->where('c.isPublished = :published')
               ->setParameter('published', true);
            
            if (!empty($categories)) {
                $qb->andWhere('c.category IN (:categories)')
                   ->setParameter('categories', $categories);
            }
            
            $qb->orderBy('c.enrollmentCount', 'DESC')
               ->setMaxResults(5);
            
            $courses = $qb->getQuery()->getResult();
            
            $recommendations = [];
            foreach ($courses as $course) {
                $recommendations[] = [
                    'id' => $course->getId(),
                    'title' => $course->getTitle(),
                    'price' => $course->getPrice(),
                    'level' => $course->getLevel(),
                    'image' => $course->getThumbnail()
                ];
            }
            
            return [
                'success' => true,
                'message' => 'Based on your learning history, here are some recommendations:',
                'courses' => $recommendations
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Error getting recommendations'];
        }
    }

    private function getSystemPrompt(string $language = 'en'): string
    {
        $prompts = [
            'en' => 'You are UniLearn AI Assistant, an intelligent AGENT for UniLearn e-learning platform. You can PERFORM ACTIONS for users.

**AI AGENT ACTIONS:** [ACTION:type:json_params]

**Profile Actions:**
- [ACTION:update_profile:{"field":"name","value":"John"}] - Update profile (fields: name, firstName, lastName, email, phone, bio)

**Course Actions:**
- [ACTION:enroll_course:{"courseId":123}] - Enroll in course
- [ACTION:add_favorite:{"courseId":123}] - Add to favorites

**ALL ROUTES (use EXACTLY for navigation):**
- /profile/edit - User profile
- /courses - Browse courses
- /course/{id} - Course details
- /enrollment/my-courses - My enrolled courses
- /instructor/dashboard - Instructor dashboard
- /marketplace - Marketplace
- /booking - Booking sessions
- /certificates - My certificates
- /cart - Shopping cart
- /orders - My orders
- /wishlist - Wishlist
- /admin - Admin panel
- /login - Login
- /register - Register

**Navigation:** [ACTION:navigate:/path]
Example: "Go to profile" → [ACTION:navigate:/profile/edit]

**Rules:**
1. Confirm before profile changes
2. Use exact routes from list above
3. Be helpful and concise
4. Answer in user\'s language',
            
            'fr' => 'Vous êtes l\'Assistant AI UniLearn pour la plateforme e-learning UniLearn.

**ACTIONS:** [ACTION:type:json_params]

**Profil:** [ACTION:update_profile:{"field":"name","value":"Jean"}]
Champs: name, firstName, lastName, email, phone, bio

**Routes (utilisez EXACTEMENT):**
- /profile/edit - Profil
- /courses - Cours
- /enrollment/my-courses - Mes cours
- /instructor/dashboard - Dashboard instructeur
- /marketplace - Marketplace
- /booking - Réservation
- /certificates - Certificats
- /cart - Panier
- /orders - Commandes
- /admin - Admin

**Navigation:** [ACTION:navigate:/path]
Exemple: "Mon profil" → [ACTION:navigate:/profile/edit]

**Règles:**
1. Confirmer avant modifications profil
2. Utiliser routes exactes
3. Répondre en français',
            
            'ar' => 'أنت مساعد UniLearn الذكي، وكيل ذكي لمنصة تعليمية تسمى UniLearn.

**المواضيع المسموح بها:**
- الدورات والتعلم والمحتوى التعليمي
- سوق UniLearn (شراء/بيع الدورات والمنتجات والخدمات)
- الدعم التقني للمنصة
- ميزات المدرب والطالب
- توصيات الدورات ومسارات التعلم
- حجز الجلسات مع المدربين
- الشهادات وتتبع التقدم

**الإجراءات المتاحة:**
[ACTION:action_type:param1:param2]

الإجراءات:
- [ACTION:navigate:/booking] - صفحة الحجز
- [ACTION:navigate:/courses] - قائمة الدورات
- [ACTION:navigate:/marketplace] - السوق
- [ACTION:search:courses:query] - البحث عن دورات

**القواعد:**
1. للمواضيع العامة، اعتذر بلطف ووجه إلى مواضيع UniLearn.
2. استخدم تنسيق ACTION للتنقلات.
3. أجب باللغة العربية.
4. كن موجزاً ومفيداً.'
        ];
        
        return $prompts[$language] ?? $prompts['en'];
    }

    private function parseResponse(string $content): array
    {
        $action = null;
        $message = $content;
        
        // Check for action pattern [ACTION:type:json_params] or [ACTION:type:param]
        if (preg_match('/\[ACTION:([^\]]+)\]/', $content, $matches) === 1) {
            $actionStr = $matches[1];
            
            // Find the first colon to separate action type from params
            $firstColonPos = strpos($actionStr, ':');
            
            if ($firstColonPos !== false) {
                $actionType = substr($actionStr, 0, $firstColonPos);
                $paramStr = substr($actionStr, $firstColonPos + 1);
                
                // Try to parse as JSON first
                $decoded = json_decode($paramStr, true);
                
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    // It's valid JSON params (for actions like update_profile)
                    $params = $decoded;
                } else {
                    // For navigate actions, treat the rest as a single string (the path)
                    // For other actions, could be colon-separated
                    if ($actionType === 'navigate') {
                        // The entire remaining string is the path
                        $params = [$paramStr];
                    } else {
                        // Legacy format: colon-separated params
                        $params = explode(':', $paramStr);
                    }
                }
            } else {
                // No params, just action type
                $actionType = $actionStr;
                $params = [];
            }
            
            $action = [
                'type' => $actionType,
                'params' => $params
            ];
            
            // Remove action tag from message
            $message = trim(str_replace($matches[0], '', $content));
        }
        
        return [
            'message' => $message,
            'action' => $action
        ];
    }

    private function buildPrompt(string $message, array $context, string $language = 'en', ?int $userId = null, array $attachments = []): string
    {
        $conversationHistory = '';
        foreach ($context as $msg) {
            $role = $msg['role'] === 'user' ? 'User' : 'Assistant';
            $conversationHistory .= "$role: {$msg['content']}\n";
        }

        $prompt = '';
        
        if ($userId !== null) {
            $prompt .= "[User ID: $userId] ";
        }
        
        if ($conversationHistory !== '') {
            $prompt .= "Previous conversation:\n" . $conversationHistory . "\n";
        }
        
        // Add attachment information
        if (!empty($attachments)) {
            $prompt .= "\nUser has attached the following files:\n";
            foreach ($attachments as $att) {
                $prompt .= "- {$att['name']} ({$att['type']}, " . $this->formatFileSize($att['size']) . ")\n";
            }
            $prompt .= "Please acknowledge these attachments and help the user with them.\n";
        }
        
        $prompt .= "User: " . $message;
        
        return $prompt;
    }
    
    private function formatFileSize(int $bytes): string
    {
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1024 * 1024) return round($bytes / 1024, 1) . ' KB';
        return round($bytes / (1024 * 1024), 1) . ' MB';
    }
}
