<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\FaceAuthService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

#[Route('/face-auth')]
class FaceAuthController extends AbstractController
{
    private FaceAuthService $faceAuthService;
    private EntityManagerInterface $entityManager;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        FaceAuthService $faceAuthService,
        EntityManagerInterface $entityManager,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->faceAuthService = $faceAuthService;
        $this->entityManager = $entityManager;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Register face for logged-in user
     */
    #[Route('/register', name: 'face_auth_register', methods: ['POST'])]
    public function registerFace(Request $request): JsonResponse
    {
        $user = $this->getUser();
        
        if ($user === null) {
            return $this->json(['success' => false, 'error' => 'User not authenticated'], 401);
        }

        if (!$user instanceof \App\Entity\User) {
            return $this->json(['success' => false, 'error' => 'Invalid user type'], 401);
        }

        $data = json_decode($request->getContent(), true);
        $imageData = $data['image'] ?? null;

        if ($imageData === null) {
            return $this->json(['success' => false, 'error' => 'No image provided'], 400);
        }

        $success = $this->faceAuthService->registerFace($user, $imageData);

        if ($success === true) {
            return $this->json([
                'success' => true,
                'message' => 'Face registered successfully! You can now log in using face recognition.'
            ]);
        }

        return $this->json([
            'success' => false,
            'error' => 'Could not detect face in image. Please ensure your face is clearly visible and well-lit.'
        ], 400);
    }

    /**
     * Verify face for login
     */
    #[Route('/verify', name: 'face_auth_verify', methods: ['POST'])]
    public function verifyFace(Request $request): JsonResponse
    {
        try {
            $content = $request->getContent();
            $debugFile = 'C:/xampp/htdocs/UniLearn-PI-main/var/log/faceauth_debug.log';
            file_put_contents($debugFile, "\n=== VERIFY REQUEST ===\n", FILE_APPEND);
            file_put_contents($debugFile, "Raw content length: " . strlen($content) . "\n", FILE_APPEND);
            
            // Check if JSON is valid
            /** @var array<string, mixed>|null $data */
            $data = json_decode($content, true);
            $jsonError = json_last_error_msg();
            file_put_contents($debugFile, "JSON decode error: $jsonError\n", FILE_APPEND);
            file_put_contents($debugFile, "Decoded data keys: " . implode(', ', array_keys($data ?? [])) . "\n", FILE_APPEND);
            file_put_contents($debugFile, "Email value: " . ($data['email'] ?? 'NOT SET') . "\n", FILE_APPEND);
            file_put_contents($debugFile, "recaptchaToken: " . (isset($data['recaptchaToken']) ? 'present' : 'NOT SET') . "\n", FILE_APPEND);
            
            // Try to find email in raw content
            if (strpos($content, '"email"') !== false) {
                file_put_contents($debugFile, "Found 'email' key in raw content\n", FILE_APPEND);
            } else {
                file_put_contents($debugFile, "'email' key NOT found in raw content\n", FILE_APPEND);
            }
            
            $imageData = $data['image'] ?? null;
            $email = $data['email'] ?? null;
            $recaptchaToken = $data['recaptchaToken'] ?? null;

            if ($imageData === null) {
                return $this->json(['success' => false, 'error' => 'No image provided'], 400);
            }

            if ($email === null) {
                return $this->json(['success' => false, 'error' => 'Email is required for face verification'], 400);
            }

            // Verify reCAPTCHA first
            if ($recaptchaToken === null) {
                return $this->json(['success' => false, 'error' => 'Security verification required. Please complete reCAPTCHA.'], 400);
            }

            $recaptchaValid = $this->verifyRecaptcha($recaptchaToken);
            if ($recaptchaValid === false) {
                return $this->json(['success' => false, 'error' => 'Security verification failed. Please try again.'], 400);
            }

            // Find user by email
            $user = $this->entityManager->getRepository(User::class)
                ->findOneBy(['email' => $email]);

            if ($user === null) {
                return $this->json([
                    'success' => false,
                    'error' => 'No account found with this email address.'
                ], 401);
            }

            // Check if face is enabled for this user
            if ($user->isFaceEnabled() === false || $user->getFaceEncoding() === null) {
                return $this->json([
                    'success' => false,
                    'error' => 'Face login is not enabled for this account. Please use password login.'
                ], 400);
            }

            // Verify the face matches
            $verified = $this->faceAuthService->verifyFace($user, $imageData);

            if ($verified === false) {
                return $this->json([
                    'success' => false,
                    'error' => 'Face verification failed. Please try again.'
                ], 401);
            }

            // Check if user is active
            if ($user->getStatus() !== 'active') {
                return $this->json([
                    'success' => false,
                    'error' => 'Your account is not active. Please contact support.'
                ], 403);
            }

            // Manually authenticate the user
            $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
            $this->container->get('security.token_storage')->setToken($token);
            
            // Fire the login event
            $event = new InteractiveLoginEvent($request, $token);
            $this->eventDispatcher->dispatch($event, SecurityEvents::INTERACTIVE_LOGIN);
            
            // Return JSON response with redirect
            return $this->json([
                'success' => true,
                'redirect' => $this->generateUrl('app_home')
            ]);
            
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify reCAPTCHA token with Google
     */
    private function verifyRecaptcha(string $token): bool
    {
        $secret = $_ENV['GOOGLE_RECAPTCHA_SECRET'] ?? '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe';
        
        $url = 'https://www.google.com/recaptcha/api/siteverify';
        $data = [
            'secret' => $secret,
            'response' => $token
        ];

        $options = [
            'http' => [
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data)
            ]
        ];

        $context = stream_context_create($options);
        /** @var string|false $result */
        $result = file_get_contents($url, false, $context);
        if ($result === false) {
            return false;
        }
        /** @var array<string, mixed> $response */
        $response = json_decode($result, true);

        return isset($response['success']) && $response['success'] === true;
    }

    /**
     * Check if face authentication is available
     */
    #[Route('/status', name: 'face_auth_status', methods: ['GET'])]
    public function getStatus(): JsonResponse
    {
        return $this->json([
            'available' => $this->faceAuthService->isAvailable(),
            'enabled' => $this->getUser() !== null ? $this->getUser()->isFaceEnabled() : false
        ]);
    }

    /**
     * Enable/disable face authentication for user
     */
    #[Route('/toggle', name: 'face_auth_toggle', methods: ['POST'])]
    public function toggleFaceAuth(Request $request): JsonResponse
    {
        $user = $this->getUser();
        
        if ($user === null) {
            return $this->json(['success' => false, 'error' => 'User not authenticated'], 401);
        }

        $data = json_decode($request->getContent(), true);
        $enabled = $data['enabled'] ?? !$user->isFaceEnabled();

        // If enabling and no face encoding exists, require registration first
        if ($enabled === true && $user->getFaceEncoding() === null) {
            return $this->json([
                'success' => false,
                'error' => 'Please register your face first before enabling face authentication.'
            ], 400);
        }

        $user->setFaceEnabled($enabled);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $enabledBool = $enabled === true;
        return $this->json([
            'success' => true,
            'enabled' => $enabledBool,
            'message' => $enabledBool ? 'Face authentication enabled' : 'Face authentication disabled'
        ]);
    }

    /**
     * Check if current user has face registered
     */
    #[Route('/check', name: 'face_auth_check', methods: ['GET'])]
    public function checkFaceRegistration(): JsonResponse
    {
        $user = $this->getUser();
        
        if ($user === null) {
            return $this->json(['registered' => false, 'enabled' => false], 401);
        }

        return $this->json([
            'registered' => $user->getFaceEncoding() !== null,
            'enabled' => $user->isFaceEnabled()
        ]);
    }
}
