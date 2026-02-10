<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GoogleAuthService
{
    private HttpClientInterface $client;
    private UserRepository $userRepository;
    private EntityManagerInterface $em;

    public function __construct(
        HttpClientInterface $client,
        UserRepository $userRepository,
        EntityManagerInterface $em
    ) {
        $this->client = $client;
        $this->userRepository = $userRepository;
        $this->em = $em;
    }

    public function getAuthorizationUrl(): string
    {
        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
            'client_id' => $_ENV['GOOGLE_CLIENT_ID'],
            'redirect_uri' => $_ENV['GOOGLE_REDIRECT_URI'],
            'response_type' => 'code',
            'scope' => 'email profile',
            'access_type' => 'online',
        ]);
    }

    public function authenticateUser(Request $request): ?User
    {
        $code = $request->query->get('code');
        if (!$code) {
            return null;
        }

        // Exchange code for token
        $tokenResponse = $this->client->request('POST', 'https://oauth2.googleapis.com/token', [
            'body' => [
                'code' => $code,
                'client_id' => $_ENV['GOOGLE_CLIENT_ID'],
                'client_secret' => $_ENV['GOOGLE_CLIENT_SECRET'],
                'redirect_uri' => $_ENV['GOOGLE_REDIRECT_URI'],
                'grant_type' => 'authorization_code',
            ],
        ]);

        $tokenData = $tokenResponse->toArray();
        $accessToken = $tokenData['access_token'];

        // Get user info
        $userInfo = $this->client->request(
            'GET',
            'https://www.googleapis.com/oauth2/v2/userinfo',
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                ],
            ]
        )->toArray();

        $email = $userInfo['email'];

        $user = $this->userRepository->findOneBy(['email' => $email]);

        if (!$user) {
            $user = new User();
            $user->setEmail($email);
            $user->setFullName($userInfo['name'] ?? 'Google User');
            $user->setUsername($email);
            $user->setGoogleId($userInfo['id']);
            $user->setAgreeTerms(true);
            
            // Set a random password for OAuth users
            $randomPassword = bin2hex(random_bytes(16));
            $user->setPassword($randomPassword);
            
            // Set default role
            $role = $this->em->getRepository(\App\Entity\Role::class)
                ->findOneBy(['name' => 'student']);
            if ($role) {
                $user->setRole($role);
            }

            $this->em->persist($user);
            $this->em->flush();
        }

        return $user;
    }
}