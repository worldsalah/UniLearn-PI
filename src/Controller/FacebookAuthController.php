<?php

namespace App\Controller;

use App\Entity\Role;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\GoogleAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;

class FacebookAuthController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private string $facebookAppId,
        private string $facebookAppSecret,
    ) {
    }

    #[Route('/connect/facebook', name: 'auth_facebook')]
    public function redirectToFacebook(Request $request): Response
    {
        $redirectUri = $request->getSchemeAndHttpHost() . '/connect/facebook/check';
        $scope = 'email,public_profile';

        $url = "https://www.facebook.com/v19.0/dialog/oauth?" . http_build_query([
            'client_id' => $this->facebookAppId,
            'redirect_uri' => $redirectUri,
            'scope' => $scope,
            'response_type' => 'code',
        ]);

        return $this->redirect($url);
    }

    #[Route('/connect/facebook/check', name: 'auth_facebook_callback')]
    public function handleFacebookCallback(
        Request $request,
        UserAuthenticatorInterface $userAuthenticator,
        GoogleAuthenticator $googleAuthenticator,
    ): Response {
        $code = $request->query->get('code');
        $error = $request->query->get('error');
        $errorDescription = $request->query->get('error_description');

        if ($error !== null && $error !== '') {
            $this->addFlash('error', 'Facebook authentication failed: ' . ($errorDescription ?? $error));
            return $this->redirectToRoute('app_login');
        }

        if ($code === null || $code === '') {
            $this->addFlash('error', 'No authorization code received from Facebook.');
            return $this->redirectToRoute('app_login');
        }

        try {
            // Exchange code for access token
            $redirectUri = $request->getSchemeAndHttpHost() . '/connect/facebook/check';

            $tokenUrl = "https://graph.facebook.com/v19.0/oauth/access_token?" . http_build_query([
                'client_id' => $this->facebookAppId,
                'client_secret' => $this->facebookAppSecret,
                'redirect_uri' => $redirectUri,
                'code' => $code,
            ]);

            $tokenResponse = $this->makeRequest($tokenUrl);
            if ($tokenResponse === false) {
                throw new \Exception('Failed to get access token from Facebook.');
            }

            $tokenData = json_decode($tokenResponse, true);
            if (!isset($tokenData['access_token'])) {
                throw new \Exception($tokenData['error']['message'] ?? 'Failed to get Facebook access token.');
            }

            $accessToken = $tokenData['access_token'];

            // Get user info from Facebook
            $userInfoUrl = "https://graph.facebook.com/v19.0/me?" . http_build_query([
                'fields' => 'id,name,email,picture.type(large)',
                'access_token' => $accessToken,
            ]);

            $userInfoResponse = $this->makeRequest($userInfoUrl);
            if ($userInfoResponse === false) {
                throw new \Exception('Failed to get user info from Facebook.');
            }

            $fbUser = json_decode($userInfoResponse, true);

            $facebookId = $fbUser['id'] ?? null;
            $email = $fbUser['email'] ?? null;
            $fullName = $fbUser['name'] ?? null;
            $profileImage = $fbUser['picture']['data']['url'] ?? null;

            if ($email === null || $email === '') {
                // Facebook might not return email if not verified or permission denied
                // Generate a placeholder email using Facebook ID
                $email = 'fb_' . $facebookId . '@facebook.placeholder';
            }

            // Find existing user by email or Facebook ID
            $user = $this->userRepository->findOneBy(['email' => $email])
                ?? $this->userRepository->findOneBy(['facebookId' => $facebookId]);

            if ($user === null) {
                // Create new user
                $user = new User();
                $user->setEmail($email);
                $user->setFullName($fullName ?? 'Facebook User');
                $user->setFacebookId($facebookId);
                $user->setAgreeTerms(true);
                $user->setStatus('active');
                $user->setCreatedAt(new \DateTime());

                // Set profile image if available
                if ($profileImage !== null && $profileImage !== '') {
                    $user->setProfileImage($profileImage);
                }

                // Set a random password (user authenticates via Facebook only)
                $randomPassword = bin2hex(random_bytes(16));
                $user->setPassword($this->passwordHasher->hashPassword($user, $randomPassword));

                // Assign default student role (id = 3)
                $role = $this->em->getRepository(Role::class)->find(3);
                if ($role !== null) {
                    $user->setRole($role);
                }

                $this->em->persist($user);
                $this->em->flush();

                $this->addFlash('success', 'Account created successfully via Facebook!');
            } else {
                // Update Facebook ID if not already set
                $fbId = $user->getFacebookId();
                if ($fbId === null || $fbId === '') {
                    $user->setFacebookId($facebookId);
                    $this->em->flush();
                }
            }

            // Authenticate user into Symfony session
            $response = $userAuthenticator->authenticateUser(
                $user,
                $googleAuthenticator,
                $request
            );
            
            return $response ?? $this->redirectToRoute('app_home');

        } catch (\Exception $e) {
            $this->addFlash('error', 'Facebook error: ' . $e->getMessage());
            return $this->redirectToRoute('app_login');
        }
    }

    private function makeRequest(string $url): string|false
    {
        // Use cURL for better SSL handling
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        
        if (curl_errno($ch) !== 0) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \Exception('cURL error: ' . $error);
        }
        
        curl_close($ch);
        
        if (!is_string($response)) {
            return false;
        }
        
        return $response;
    }
}
