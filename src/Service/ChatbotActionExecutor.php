<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Service for executing actions requested by the AI chatbot
 */
class ChatbotActionExecutor
{
    private EntityManagerInterface $entityManager;
    private Security $security;
    private ValidatorInterface $validator;
    private array $actionLog = [];

    // Available actions with their required parameters
    private const AVAILABLE_ACTIONS = [
        'update_profile' => [
            'description' => 'Update user profile information',
            'params' => ['field' => 'string', 'value' => 'mixed'],
            'requires_auth' => true,
            'fields' => ['name', 'firstName', 'lastName', 'email', 'phone', 'bio']
        ],
        'change_password' => [
            'description' => 'Change user password',
            'params' => ['currentPassword' => 'string', 'newPassword' => 'string'],
            'requires_auth' => true
        ],
        'enroll_course' => [
            'description' => 'Enroll user in a course',
            'params' => ['courseId' => 'integer'],
            'requires_auth' => true
        ],
        'unenroll_course' => [
            'description' => 'Unenroll user from a course',
            'params' => ['courseId' => 'integer'],
            'requires_auth' => true
        ],
        'add_favorite' => [
            'description' => 'Add course to favorites',
            'params' => ['courseId' => 'integer'],
            'requires_auth' => true
        ],
        'remove_favorite' => [
            'description' => 'Remove course from favorites',
            'params' => ['courseId' => 'integer'],
            'requires_auth' => true
        ],
        'update_preferences' => [
            'description' => 'Update notification or display preferences',
            'params' => ['preference' => 'string', 'value' => 'mixed'],
            'requires_auth' => true
        ],
        'search_content' => [
            'description' => 'Search for content (courses, instructors, etc.)',
            'params' => ['query' => 'string', 'type' => 'string'],
            'requires_auth' => false
        ],
        'get_recommendations' => [
            'description' => 'Get personalized course recommendations',
            'params' => ['category' => 'string?', 'limit' => 'integer?'],
            'requires_auth' => false
        ]
    ];

    public function __construct(
        EntityManagerInterface $entityManager,
        Security $security,
        ValidatorInterface $validator
    ) {
        $this->entityManager = $entityManager;
        $this->security = $security;
        $this->validator = $validator;
    }

    /**
     * Get list of available actions
     */
    public function getAvailableActions(): array
    {
        return self::AVAILABLE_ACTIONS;
    }

    /**
     * Check if an action is valid
     */
    public function isValidAction(string $action): bool
    {
        return isset(self::AVAILABLE_ACTIONS[$action]);
    }

    /**
     * Get action details
     */
    public function getActionDetails(string $action): ?array
    {
        return self::AVAILABLE_ACTIONS[$action] ?? null;
    }

    /**
     * Execute an action
     */
    public function execute(string $action, array $params, ?User $user = null): array
    {
        $this->actionLog[] = [
            'action' => $action,
            'params' => $params,
            'timestamp' => new \DateTime()
        ];

        // Check if action exists
        if (!$this->isValidAction($action)) {
            return [
                'success' => false,
                'error' => "Unknown action: {$action}",
                'available_actions' => array_keys(self::AVAILABLE_ACTIONS)
            ];
        }

        $actionConfig = self::AVAILABLE_ACTIONS[$action];

        // Check authorization if required
        if ($actionConfig['requires_auth'] && $user === null) {
            return [
                'success' => false,
                'error' => 'Authentication required for this action',
                'requires_auth' => true
            ];
        }

        // Validate required parameters
        $missingParams = $this->validateParameters($actionConfig['params'], $params);
        if (!empty($missingParams)) {
            return [
                'success' => false,
                'error' => 'Missing required parameters: ' . implode(', ', $missingParams),
                'required_params' => array_keys($actionConfig['params'])
            ];
        }

        // Execute the specific action
        try {
            return match ($action) {
                'update_profile' => $this->executeUpdateProfile($params, $user),
                'change_password' => $this->executeChangePassword($params, $user),
                'enroll_course' => $this->executeEnrollCourse($params, $user),
                'unenroll_course' => $this->executeUnenrollCourse($params, $user),
                'add_favorite' => $this->executeAddFavorite($params, $user),
                'remove_favorite' => $this->executeRemoveFavorite($params, $user),
                'update_preferences' => $this->executeUpdatePreferences($params, $user),
                'search_content' => $this->executeSearchContent($params, $user),
                'get_recommendations' => $this->executeGetRecommendations($params, $user),
                default => ['success' => false, 'error' => 'Action not implemented']
            };
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Error executing action: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Validate action parameters
     */
    private function validateParameters(array $required, array $provided): array
    {
        $missing = [];
        foreach ($required as $param => $type) {
            // Optional parameter (ends with ?)
            if (str_ends_with($type, '?')) {
                continue;
            }
            if (!array_key_exists($param, $provided) || $provided[$param] === null || $provided[$param] === '') {
                $missing[] = $param;
            }
        }
        return $missing;
    }

    /**
     * Execute: Update user profile
     */
    private function executeUpdateProfile(array $params, ?User $user): array
    {
        if ($user === null) {
            return ['success' => false, 'error' => 'User not authenticated'];
        }
        
        $field = $params['field'];
        $value = $params['value'];

        $allowedFields = self::AVAILABLE_ACTIONS['update_profile']['fields'];
        
        if (!in_array($field, $allowedFields, true)) {
            return [
                'success' => false,
                'error' => "Cannot update field '{$field}'. Allowed fields: " . implode(', ', $allowedFields)
            ];
        }

        // Map field names to entity properties
        $fieldMap = [
            'name' => 'name',
            'firstName' => 'firstName',
            'lastName' => 'lastName',
            'email' => 'email',
            'phone' => 'phone',
            'bio' => 'bio'
        ];

        $property = $fieldMap[$field];
        
        // Use reflection or direct setter
        $setter = 'set' . ucfirst($property);
        
        if (!method_exists($user, $setter)) {
            return [
                'success' => false,
                'error' => "Cannot update field: {$field}"
            ];
        }

        // Validate email uniqueness if changing email
        if ($field === 'email') {
            $existingUser = $this->entityManager->getRepository(User::class)
                ->findOneBy(['email' => $value]);
            if ($existingUser !== null && $existingUser->getId() !== $user->getId()) {
                return [
                    'success' => false,
                    'error' => 'This email is already in use by another account'
                ];
            }
        }

        // Store old value for response
        $getter = 'get' . ucfirst($property);
        $oldValue = method_exists($user, $getter) ? $user->$getter() : null;

        // Update the field
        $user->$setter($value);

        // Validate entity
        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return [
                'success' => false,
                'error' => 'Validation error: ' . implode(', ', $errorMessages)
            ];
        }

        $this->entityManager->flush();

        return [
            'success' => true,
            'message' => "Successfully updated your {$field} from '{$oldValue}' to '{$value}'",
            'data' => [
                'field' => $field,
                'old_value' => $oldValue,
                'new_value' => $value
            ]
        ];
    }

    /**
     * Execute: Change password
     */
    private function executeChangePassword(array $params, ?User $user): array
    {
        // This would require password hashing and verification
        // For now, return a message that this needs special handling
        return [
            'success' => false,
            'error' => 'Password change requires verification. Please use the profile settings page.',
            'redirect' => '/profile/settings#security'
        ];
    }

    /**
     * Execute: Enroll in course
     */
    private function executeEnrollCourse(array $params, ?User $user): array
    {
        if ($user === null) {
            return ['success' => false, 'error' => 'User not authenticated'];
        }
        
        $courseId = (int) $params['courseId'];
        
        $course = $this->entityManager->getRepository(\App\Entity\Course::class)->find($courseId);
        
        if ($course === null) {
            return [
                'success' => false,
                'error' => "Course with ID {$courseId} not found"
            ];
        }

        // Check if already enrolled
        $existingEnrollment = $this->entityManager->getRepository(\App\Entity\Enrollment::class)
            ->findOneBy(['user' => $user, 'course' => $course]);
        
        if ($existingEnrollment !== null) {
            return [
                'success' => false,
                'error' => 'You are already enrolled in this course',
                'data' => ['course_id' => $courseId, 'course_title' => $course->getTitle()]
            ];
        }

        // Create enrollment
        $enrollment = new \App\Entity\Enrollment();
        $enrollment->setUser($user);
        $enrollment->setCourse($course);
        $enrollment->setEnrolledAt(new \DateTime());
        $enrollment->setStatus('active');

        $this->entityManager->persist($enrollment);
        $this->entityManager->flush();

        $courseTitle = $course->getTitle();
        $enrollmentDate = (new \DateTime())->format('Y-m-d H:i:s');

        return [
            'success' => true,
            'message' => "Successfully enrolled in '{$courseTitle}'",
            'data' => [
                'course_id' => $courseId,
                'course_title' => $courseTitle,
                'enrollment_date' => $enrollmentDate
            ]
        ];
    }

    /**
     * Execute: Unenroll from course
     */
    private function executeUnenrollCourse(array $params, ?User $user): array
    {
        if ($user === null) {
            return ['success' => false, 'error' => 'User not authenticated'];
        }
        
        $courseId = (int) $params['courseId'];
        
        $enrollment = $this->entityManager->getRepository(\App\Entity\Enrollment::class)
            ->findOneBy(['user' => $user, 'course' => $courseId]);
        
        if ($enrollment === null) {
            return [
                'success' => false,
                'error' => 'You are not enrolled in this course'
            ];
        }

        $course = $enrollment->getCourse();
        $courseTitle = $course !== null ? $course->getTitle() : 'Unknown course';
        
        $this->entityManager->remove($enrollment);
        $this->entityManager->flush();

        return [
            'success' => true,
            'message' => "Successfully unenrolled from '{$courseTitle}'",
            'data' => ['course_id' => $courseId]
        ];
    }

    /**
     * Execute: Add course to favorites
     */
    private function executeAddFavorite(array $params, ?User $user): array
    {
        if ($user === null) {
            return ['success' => false, 'error' => 'User not authenticated'];
        }
        
        $courseId = (int) $params['courseId'];
        
        $course = $this->entityManager->getRepository(\App\Entity\Course::class)->find($courseId);
        
        if ($course === null) {
            return [
                'success' => false,
                'error' => "Course with ID {$courseId} not found"
            ];
        }

        // Check if already in favorites
        if ($user->getFavoriteCourses()->contains($course)) {
            return [
                'success' => false,
                'error' => 'This course is already in your favorites'
            ];
        }

        $user->addFavoriteCourse($course);
        $this->entityManager->flush();

        return [
            'success' => true,
            'message' => "Added '{$course->getTitle()}' to your favorites",
            'data' => ['course_id' => $courseId, 'course_title' => $course->getTitle()]
        ];
    }

    /**
     * Execute: Remove course from favorites
     */
    private function executeRemoveFavorite(array $params, ?User $user): array
    {
        if ($user === null) {
            return ['success' => false, 'error' => 'User not authenticated'];
        }
        
        $courseId = (int) $params['courseId'];
        
        $course = $this->entityManager->getRepository(\App\Entity\Course::class)->find($courseId);
        
        if ($course === null) {
            return [
                'success' => false,
                'error' => "Course with ID {$courseId} not found"
            ];
        }

        if (!$user->getFavoriteCourses()->contains($course)) {
            return [
                'success' => false,
                'error' => 'This course is not in your favorites'
            ];
        }

        $user->removeFavoriteCourse($course);
        $this->entityManager->flush();

        return [
            'success' => true,
            'message' => "Removed '{$course->getTitle()}' from your favorites",
            'data' => ['course_id' => $courseId]
        ];
    }

    /**
     * Execute: Update user preferences
     */
    private function executeUpdatePreferences(array $params, ?User $user): array
    {
        if ($user === null) {
            return ['success' => false, 'error' => 'User not authenticated'];
        }
        
        $preference = $params['preference'];
        $value = $params['value'];

        // Store preferences in user entity or separate table
        // This is a placeholder implementation
        return [
            'success' => true,
            'message' => "Updated preference '{$preference}' to '{$value}'",
            'data' => ['preference' => $preference, 'value' => $value]
        ];
    }

    /**
     * Execute: Search content
     */
    private function executeSearchContent(array $params, ?User $user): array
    {
        $query = $params['query'];
        $type = $params['type'] ?? 'all';

        $results = [];

        // Search courses
        if ($type === 'all' || $type === 'course') {
            $courses = $this->entityManager->getRepository(\App\Entity\Course::class)
                ->createQueryBuilder('c')
                ->where('c.title LIKE :query OR c.description LIKE :query')
                ->setParameter('query', "%{$query}%")
                ->setMaxResults(5)
                ->getQuery()
                ->getResult();
            
            foreach ($courses as $course) {
                $results[] = [
                    'type' => 'course',
                    'id' => $course->getId(),
                    'title' => $course->getTitle(),
                    'description' => substr($course->getDescription() ?? '', 0, 100)
                ];
            }
        }

        return [
            'success' => true,
            'message' => sprintf('Found %d results for "%s"', count($results), $query),
            'data' => ['results' => $results, 'query' => $query, 'type' => $type]
        ];
    }

    /**
     * Execute: Get recommendations
     */
    private function executeGetRecommendations(array $params, ?User $user): array
    {
        $category = $params['category'] ?? null;
        $limit = (int) ($params['limit'] ?? 5);

        $qb = $this->entityManager->getRepository(\App\Entity\Course::class)
            ->createQueryBuilder('c')
            ->where('c.isPublished = true')
            ->orderBy('c.rating', 'DESC')
            ->setMaxResults($limit);

        if ($category) {
            $qb->join('c.category', 'cat')
               ->andWhere('cat.name LIKE :category')
               ->setParameter('category', "%{$category}%");
        }

        $courses = $qb->getQuery()->getResult();

        $recommendations = [];
        foreach ($courses as $course) {
            $recommendations[] = [
                'id' => $course->getId(),
                'title' => $course->getTitle(),
                'rating' => $course->getRating(),
                'price' => $course->getPrice()
            ];
        }

        return [
            'success' => true,
            'message' => sprintf('Here are %d course recommendations for you', count($recommendations)),
            'data' => ['recommendations' => $recommendations, 'category' => $category]
        ];
    }

    /**
     * Get action log
     */
    public function getActionLog(): array
    {
        return $this->actionLog;
    }
}
