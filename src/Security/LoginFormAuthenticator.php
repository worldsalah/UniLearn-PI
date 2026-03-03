<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use ReCaptcha\ReCaptcha;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class LoginFormAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    private EntityManagerInterface $entityManager;
    private UrlGeneratorInterface $urlGenerator;
    private CsrfTokenManagerInterface $csrfTokenManager;
    private UserPasswordHasherInterface $passwordHasher;
    private ReCaptcha $reCaptcha;

    public function __construct(
        EntityManagerInterface $entityManager,
        UrlGeneratorInterface $urlGenerator,
        CsrfTokenManagerInterface $csrfTokenManager,
        UserPasswordHasherInterface $passwordHasher,
        ReCaptcha $reCaptcha
    ) {
        $this->entityManager = $entityManager;
        $this->urlGenerator = $urlGenerator;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->passwordHasher = $passwordHasher;
        $this->reCaptcha = $reCaptcha;
    }

    public function supports(Request $request): bool
    {
        return self::LOGIN_ROUTE === $request->attributes->get('_route')
            && $request->isMethod('POST');
    }

    public function authenticate(Request $request): Passport
    {
        $email = $request->request->get('email', '');
        $password = $request->request->get('password', '');
        $csrfToken = $request->request->get('_csrf_token', '');
        $recaptchaResponse = $request->request->get('g-recaptcha-response');

        $request->getSession()->set(Security::LAST_USERNAME, $email);

        // Verify reCAPTCHA
        if ($recaptchaResponse !== null && $recaptchaResponse !== '') {
            $result = $this->reCaptcha->verify((string) $recaptchaResponse, $request->getClientIp());
            if (!$result->isSuccess()) {
                throw new CustomUserMessageAuthenticationException('Please complete the reCAPTCHA verification.');
            }
        }

        return new Passport(
            new UserBadge((string) $email, function ($userIdentifier) {
                $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $userIdentifier]);

                if ($user === null) {
                    throw new CustomUserMessageAuthenticationException('Email could not be found.');
                }

                return $user;
            }),
            new PasswordCredentials((string) $password),
            [new CsrfTokenBadge('authenticate', (string) $csrfToken)]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $providerKey): Response
    {
        $targetPath = $this->getTargetPath($request->getSession(), $providerKey);
        if ($targetPath !== null) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->urlGenerator->generate('app_home'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $request->getSession()->set(Security::AUTHENTICATION_ERROR, $exception);

        return new RedirectResponse($this->urlGenerator->generate(self::LOGIN_ROUTE));
    }

    protected function getLoginUrl(): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }

    /**
     * Called when authentication is needed but user is not authenticated
     * Redirects to the login page
     */
    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        // Save the requested URL so we can redirect back after login
        $request->getSession()->set(Security::AUTHENTICATION_ERROR, $authException);
        
        return new RedirectResponse($this->getLoginUrl());
    }
}
