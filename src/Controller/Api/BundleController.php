<?php

namespace App\Controller\Api;

use App\Entity\TeacherProfile;
use App\Entity\User;
use App\Enum\BundleType;
use App\Service\Bundle\BundleService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/bundles', name: 'api_bundles_')]
class BundleController extends AbstractController
{
    public function __construct(
        private BundleService $bundleService,
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * Purchase a bundle
     * 
     * POST /api/bundles/purchase
     * Student only
     */
    #[Route('/purchase', name: 'purchase', methods: ['POST'])]
    public function purchase(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_STUDENT');
        
        /** @var User $student */
        $student = $this->getUser();
        
        $data = json_decode($request->getContent(), true);
        
        if (empty($data['type'])) {
            return $this->json([
                'success' => false,
                'error' => 'Bundle type is required'
            ], 400);
        }
        
        try {
            $type = BundleType::from($data['type']);
        } catch (\ValueError $e) {
            return $this->json([
                'success' => false,
                'error' => 'Invalid bundle type. Valid types: single, pack_5, pack_10'
            ], 400);
        }
        
        $teacher = null;
        if (!empty($data['teacherId'])) {
            $teacher = $this->entityManager->getRepository(TeacherProfile::class)->find($data['teacherId']);
        }
        
        $bundle = $this->bundleService->purchaseBundle($student, $type, $teacher);
        
        return $this->json([
            'success' => true,
            'data' => [
                'id' => $bundle->getId(),
                'type' => $bundle->getType() !== null ? $bundle->getType()->value : null,
                'sessionsTotal' => $bundle->getSessionsTotal(),
                'sessionsUsed' => $bundle->getSessionsUsed(),
                'price' => $bundle->getPrice(),
                'expiresAt' => $bundle->getExpiresAt()?->format('c'),
                'status' => $bundle->getStatus()
            ]
        ], 201);
    }

    /**
     * Get my bundles
     * 
     * GET /api/bundles/my
     * Student only
     */
    #[Route('/my', name: 'my', methods: ['GET'])]
    public function myBundles(): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_STUDENT');
        
        /** @var User $student */
        $student = $this->getUser();
        
        $bundles = $this->bundleService->getStudentBundles($student);
        
        $data = array_map(function ($bundle) {
            return [
                'id' => $bundle->getId(),
                'type' => $bundle->getType()->value,
                'sessionsTotal' => $bundle->getSessionsTotal(),
                'sessionsUsed' => $bundle->getSessionsUsed(),
                'sessionsRemaining' => $bundle->getSessionsRemaining(),
                'price' => $bundle->getPrice(),
                'expiresAt' => $bundle->getExpiresAt()?->format('c'),
                'status' => $bundle->getStatus(),
                'usagePercentage' => $bundle->getUsagePercentage(),
                'teacher' => $bundle->getTeacher() ? [
                    'id' => $bundle->getTeacher()->getId(),
                    'name' => $bundle->getTeacher()->getUser()->getFullName()
                ] : null
            ];
        }, $bundles);
        
        return $this->json([
            'success' => true,
            'data' => [
                'bundles' => $data
            ]
        ]);
    }

    /**
     * Get bundle statistics
     * 
     * GET /api/bundles/stats
     * Student only
     */
    #[Route('/stats', name: 'stats', methods: ['GET'])]
    public function stats(): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_STUDENT');
        
        /** @var User $student */
        $student = $this->getUser();
        
        $stats = $this->bundleService->getBundleStats($student);
        
        return $this->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Calculate bundle price preview
     * 
     * POST /api/bundles/calculate
     */
    #[Route('/calculate', name: 'calculate', methods: ['POST'])]
    public function calculate(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (empty($data['type']) || empty($data['hourlyRate'])) {
            return $this->json([
                'success' => false,
                'error' => 'type and hourlyRate are required'
            ], 400);
        }
        
        try {
            $type = BundleType::from($data['type']);
        } catch (\ValueError $e) {
            return $this->json([
                'success' => false,
                'error' => 'Invalid bundle type'
            ], 400);
        }
        
        $priceInfo = $this->bundleService->calculatePrice($type, (float) $data['hourlyRate']);
        
        return $this->json([
            'success' => true,
            'data' => $priceInfo
        ]);
    }
}
