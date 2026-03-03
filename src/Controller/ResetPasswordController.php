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

            if ($email === null || $email === '') {
                $this->addFlash('error', 'Veuillez entrer votre adresse email.');
                return $this->redirectToRoute('app_forgot_password');
            }

            $user = $userRepository->findOneBy(['email' => $email]);

            if ($user === null) {
                $this->addFlash('error', 'Aucun compte trouvé avec cette adresse email.');
                return $this->redirectToRoute('app_forgot_password');
            }

            // Generate a secure token
            $token = bin2hex(random_bytes(32));
            $user->setResetToken($token);
            $user->setResetTokenExpiresAt(new \DateTime('+1 hour'));

            $em->flush();

            // Generate reset link
            $resetUrl = $this->generateUrl('app_reset_password', [
                'token' => $token,
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            // Send email
            $userEmail = $user->getEmail();
            if ($userEmail === null) {
                $this->addFlash('error', 'User email is not set.');
                return $this->redirectToRoute('app_forgot_password');
            }
            $emailMessage = (new Email())
                ->from('tebourbimalek0@gmail.com')
                ->to($userEmail)
                ->subject('Réinitialisation de votre mot de passe - UniLearn')
                ->html($this->renderView('emails/reset_password.html.twig', [
                    'user' => $user,
                    'resetUrl' => $resetUrl,
                ]));

            $emailSent = false;
            $errorMessage = '';
            
            try {
                error_log('Attempting to send email to: ' . $userEmail);
                error_log('MAILER_DSN check: ' . getenv('MAILER_DSN'));
                $mailer->send($emailMessage);
                $emailSent = true;
                error_log('Password reset email sent successfully via Symfony Mailer');
            } catch (\Exception $e) {
                $errorMessage = $e->getMessage();
                error_log('Symfony Mailer failed: ' . $errorMessage);
                error_log('Exception class: ' . get_class($e));
                
                // Fallback to native PHP mail()
                $subject = 'Réinitialisation de votre mot de passe - UniLearn';
                $message = 'Cliquez sur ce lien pour réinitialiser votre mot de passe: ' . $resetUrl;
                $headers = 'From: tebourbimalek0@gmail.com' . "\r\n" .
                    'Reply-To: tebourbimalek0@gmail.com' . "\r\n" .
                    'X-Mailer: PHP/' . phpversion();
                
                if (mail($userEmail, $subject, $message, $headers)) {
                    $emailSent = true;
                    error_log('Password reset email sent successfully via PHP mail()');
                } else {
                    error_log('PHP mail() also failed');
                    $lastError = error_get_last();
                    $errorMessage .= ' | PHP mail() failed: ' . ($lastError !== null ? $lastError['message'] : 'Unknown error');
                }
            }
            
            if ($emailSent) {
                $this->addFlash('success', 'Un lien de réinitialisation a été envoyé à votre adresse email.');
                return $this->redirectToRoute('app_login');
            } else {
                $this->addFlash('error', 'Erreur lors de l\'envoi de l\'email: ' . $errorMessage);
                return $this->redirectToRoute('app_forgot_password');
            }
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

        if ($user === null || !$user->isResetTokenValid()) {
            $this->addFlash('error', 'Ce lien de réinitialisation est invalide ou a expiré.');
            return $this->redirectToRoute('app_forgot_password');
        }

        if ($request->isMethod('POST')) {
            $password = $request->request->get('password');
            $passwordConfirm = $request->request->get('password_confirm');

            if (!is_string($password) || strlen($password) < 8) {
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

            $em->flush();

            $this->addFlash('success', 'Votre mot de passe a été réinitialisé avec succès. Vous pouvez maintenant vous connecter.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('auth/reset-password.html.twig', [
            'token' => $token,
        ]);
    }
}
