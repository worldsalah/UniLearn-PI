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
    ) {
    }

    #[Route('/connect/facebook', name: 'auth_facebook')]
    public function redirectToFacebook(): Response
    {
        $appId = $_ENV['FACEBOOK_APP_ID'];
        $redirectUri = urlencode($_ENV['FACEBOOK_REDIRECT_URI']);
        $scope = 'email,public_profile';

        $url = "https://www.facebook.com/v19.0/dialog/oauth?client_id={$appId}&redirect_uri={$redirectUri}&scope={$scope}&response_type=code";

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

        if ($error || !$code) {
            $this->addFlash('error', 'Authentification Facebook échouée.');
            return $this->redirectToRoute('app_login');
        }

        try {
            // Exchange code for access token
            $appId = $_ENV['FACEBOOK_APP_ID'];
            $appSecret = $_ENV['FACEBOOK_APP_SECRET'];
            $redirectUri = $_ENV['FACEBOOK_REDIRECT_URI'];

            $tokenUrl = "https://graph.facebook.com/v19.0/oauth/access_token?"
                . http_build_query([
                    'client_id' => $appId,
                    'client_secret' => $appSecret,
                    'redirect_uri' => $redirectUri,
                    'code' => $code,
                ]);

            $tokenResponse = file_get_contents($tokenUrl);
            if ($tokenResponse === false) {
                throw new \Exception('Failed to get access token from Facebook.');
            }

            $tokenData = json_decode($tokenResponse, true);
            if (!isset($tokenData['access_token'])) {
                throw new \Exception($tokenData['error']['message'] ?? 'Failed to get Facebook access token.');
            }

            $accessToken = $tokenData['access_token'];

            // Get user info from Facebook
            $userInfoUrl = "https://graph.facebook.com/v19.0/me?"
                . http_build_query([
                    'fields' => 'id,name,email,picture.type(large)',
                    'access_token' => $accessToken,
                ]);

            $userInfoResponse = file_get_contents($userInfoUrl);
            if ($userInfoResponse === false) {
                throw new \Exception('Failed to get user info from Facebook.');
            }

            $fbUser = json_decode($userInfoResponse, true);

            $facebookId = $fbUser['id'] ?? null;
            $email = $fbUser['email'] ?? null;
            $fullName = $fbUser['name'] ?? null;
            $profileImage = $fbUser['picture']['data']['url'] ?? null;

            if (!$email) {
                // Facebook might not return email if not verified or permission denied
                $this->addFlash('error', 'Impossible de récupérer votre email Facebook. Veuillez vérifier vos permissions.');
                return $this->redirectToRoute('app_login');
            }

            // Find existing user by email or Facebook ID
            $user = $this->userRepository->findOneBy(['email' => $email])
                ?? $this->userRepository->findOneBy(['facebookId' => $facebookId]);

            if (!$user) {
                // Create new user
                $user = new User();
                $user->setEmail($email);
                $user->setFullName($fullName ?? 'Utilisateur Facebook');
                $user->setFacebookId($facebookId);
                $user->setAgreeTerms(true);
                $user->setStatus('active');
                $user->setCreatedAt(new \DateTime());

                // Set a random password (user authenticates via Facebook only)
                $randomPassword = bin2hex(random_bytes(16));
                $user->setPassword($this->passwordHasher->hashPassword($user, $randomPassword));

                // Assign default student role (id = 3)
                $role = $this->em->getRepository(Role::class)->find(3);
                if ($role) {
                    $user->setRole($role);
                }

                $this->em->persist($user);
                try {
                    $this->em->flush();
                } catch (\Exception $e) {
                    // Elasticsearch may be down — ignore, user is already saved
                }

                $this->addFlash('success', 'Compte créé avec succès via Facebook !');
            } else {
                // Update Facebook ID if not already set
                if (!$user->getFacebookId()) {
                    $user->setFacebookId($facebookId);
                    try {
                        $this->em->flush();
                    } catch (\Exception $e) {
                        // Elasticsearch may be down — ignore
                    }
                }
            }

            // Authenticate user into Symfony session
            return $userAuthenticator->authenticateUser(
                $user,
                $googleAuthenticator,
                $request
            );

        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur Facebook : ' . $e->getMessage());
            return $this->redirectToRoute('app_login');
        }
    }
}
