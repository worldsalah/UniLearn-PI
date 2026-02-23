<?php

namespace App\Controller;

use App\Entity\Role;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\GoogleAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Google\Client as GoogleClient;
use Google\Service\Oauth2;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;

class GoogleAuthController extends AbstractController
{
    private GoogleClient $googleClient;

    public function __construct(
        private EntityManagerInterface $em,
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
        $this->googleClient = new GoogleClient();
        $this->googleClient->setClientId($_ENV['GOOGLE_CLIENT_ID']);
        $this->googleClient->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);
        $this->googleClient->setRedirectUri($_ENV['GOOGLE_REDIRECT_URI']);
        $this->googleClient->addScope('email');
        $this->googleClient->addScope('profile');
    }

    #[Route('/auth/google', name: 'auth_google')]
    public function redirectToGoogle(): Response
    {
        return $this->redirect($this->googleClient->createAuthUrl());
    }

    #[Route('/auth/google/callback', name: 'auth_google_callback')]
    public function handleGoogleCallback(
        Request $request,
        UserAuthenticatorInterface $userAuthenticator,
        GoogleAuthenticator $googleAuthenticator,
    ): Response {
        $code = $request->query->get('code');

        if (!$code) {
            $this->addFlash('error', 'Authentification Google échouée.');
            return $this->redirectToRoute('app_login');
        }

        try {
            // Exchange authorization code for access token
            $token = $this->googleClient->fetchAccessTokenWithAuthCode($code);

            if (isset($token['error'])) {
                throw new \Exception($token['error_description'] ?? $token['error']);
            }

            $this->googleClient->setAccessToken($token);

            // Get user info from Google
            $oauth2 = new Oauth2($this->googleClient);
            $googleUser = $oauth2->userinfo->get();

            $email = $googleUser->getEmail();
            $fullName = $googleUser->getName();
            $googleId = $googleUser->getId();
            $profileImage = $googleUser->getPicture();

            // Find existing user by email or Google ID
            $user = $this->userRepository->findOneBy(['email' => $email])
                ?? $this->userRepository->findOneBy(['googleId' => $googleId]);

            if (!$user) {
                // Create new user
                $user = new User();
                $user->setEmail($email);
                $user->setFullName($fullName ?? 'Utilisateur Google');
                $user->setGoogleId($googleId);
                $user->setAgreeTerms(true);
                $user->setStatus('active');
                $user->setCreatedAt(new \DateTime());

                // Set a random password (user authenticates via Google only)
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
                } catch (\Exception $elasticaException) {
                    // Elasticsearch may be down — ignore, user is already saved in DB
                }

                $this->addFlash('success', 'Compte créé avec succès via Google !');
            } else {
                // Update Google ID if not already set
                if (!$user->getGoogleId()) {
                    $user->setGoogleId($googleId);
                    try {
                        $this->em->flush();
                    } catch (\Exception $elasticaException) {
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
            $this->addFlash('error', 'Erreur Google : ' . $e->getMessage());
            return $this->redirectToRoute('app_login');
        }
    }
}
