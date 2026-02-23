<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ResetPasswordController extends AbstractController
{
    #[Route('/forgot-password', name: 'app_forgot_password', methods: ['GET', 'POST'])]
    public function forgotPassword(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $em,
        MailerInterface $mailer,
    ): Response {
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');

            if (!$email) {
                $this->addFlash('error', 'Veuillez entrer votre adresse email.');
                return $this->redirectToRoute('app_forgot_password');
            }

            $user = $userRepository->findOneBy(['email' => $email]);

            if ($user) {
                // Generate a secure token
                $token = bin2hex(random_bytes(32));
                $user->setResetToken($token);
                $user->setResetTokenExpiresAt(new \DateTime('+1 hour'));

                try {
                    $em->flush();
                } catch (\Exception $e) {
                    // Elasticsearch may be down — ignore
                }

                // Generate reset link
                $resetUrl = $this->generateUrl('app_reset_password', [
                    'token' => $token,
                ], UrlGeneratorInterface::ABSOLUTE_URL);

                // Send email
                $emailMessage = (new Email())
                    ->from('omarkaoubi2002@gmail.com')
                    ->to($user->getEmail())
                    ->subject('Réinitialisation de votre mot de passe - UniLearn')
                    ->html($this->renderView('emails/reset_password.html.twig', [
                        'user' => $user,
                        'resetUrl' => $resetUrl,
                    ]));

                try {
                    $mailer->send($emailMessage);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur envoi email: ' . $e->getMessage());
                    error_log('Mailer error: ' . $e->getMessage());
                }
            }

            // Always show success message (don't reveal if email exists or not)
            $this->addFlash('success', 'Si un compte existe avec cette adresse email, un lien de réinitialisation a été envoyé.');
            return $this->redirectToRoute('app_forgot_password');
        }

        return $this->render('auth/forgot-password.html.twig');
    }

    #[Route('/reset-password/{token}', name: 'app_reset_password', methods: ['GET', 'POST'])]
    public function resetPassword(
        string $token,
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
    ): Response {
        $user = $userRepository->findOneBy(['resetToken' => $token]);

        if (!$user || !$user->isResetTokenValid()) {
            $this->addFlash('error', 'Ce lien de réinitialisation est invalide ou a expiré.');
            return $this->redirectToRoute('app_forgot_password');
        }

        if ($request->isMethod('POST')) {
            $password = $request->request->get('password');
            $passwordConfirm = $request->request->get('password_confirm');

            if (!$password || strlen($password) < 8) {
                $this->addFlash('error', 'Le mot de passe doit contenir au moins 8 caractères.');
                return $this->redirectToRoute('app_reset_password', ['token' => $token]);
            }

            if ($password !== $passwordConfirm) {
                $this->addFlash('error', 'Les mots de passe ne correspondent pas.');
                return $this->redirectToRoute('app_reset_password', ['token' => $token]);
            }

            // Update password
            $hashedPassword = $passwordHasher->hashPassword($user, $password);
            $user->setPassword($hashedPassword);

            // Clear the reset token
            $user->setResetToken(null);
            $user->setResetTokenExpiresAt(null);

            try {
                $em->flush();
            } catch (\Exception $e) {
                // Elasticsearch may be down — ignore
            }

            $this->addFlash('success', 'Votre mot de passe a été réinitialisé avec succès. Vous pouvez maintenant vous connecter.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('auth/reset-password.html.twig', [
            'token' => $token,
        ]);
    }
}
