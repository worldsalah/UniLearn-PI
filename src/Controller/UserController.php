<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\EditProfileFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/user')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class UserController extends AbstractController
{
    #[Route('/edit-profile', name: 'app_user_edit_profile')]
    public function editProfile(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        
        $form = $this->createForm(EditProfileFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle profile picture upload if provided
            $profilePictureFile = $form->get('profilePicture')->getData();
            if ($profilePictureFile) {
                $newFilename = uniqid().'.'.$profilePictureFile->guessExtension();
                
                try {
                    $profilePictureFile->move(
                        $this->getParameter('profile_pictures_directory'),
                        $newFilename
                    );
                    $user->setProfilePicture($newFilename);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Failed to upload profile picture.');
                }
            }

            $entityManager->flush();
            $this->addFlash('success', 'Profile updated successfully!');

            return $this->redirectToRoute('app_user_edit_profile');
        }

        return $this->render('user/edit_profile.html.twig', [
            'editProfileForm' => $form->createView(),
            'user' => $user,
        ]);
    }

    #[Route('/update-email', name: 'app_user_update_email', methods: ['POST'])]
    public function updateEmail(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $newEmail = $request->request->get('newEmail');
        
        if ($newEmail && filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            // Check if email already exists
            $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $newEmail]);
            
            if ($existingUser && $existingUser->getId() !== $user->getId()) {
                $this->addFlash('error', 'This email is already in use!');
            } else {
                $user->setEmail($newEmail);
                $entityManager->flush();
                $this->addFlash('success', 'Email updated successfully!');
            }
        } else {
            $this->addFlash('error', 'Invalid email address!');
        }

        return $this->redirectToRoute('app_user_edit_profile');
    }

    #[Route('/update-password', name: 'app_user_update_password', methods: ['POST'])]
    public function updatePassword(
        Request $request, 
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): Response
    {
        $user = $this->getUser();
        
        $currentPassword = $request->request->get('currentPassword');
        $newPassword = $request->request->get('newPassword');
        $confirmPassword = $request->request->get('confirmPassword');

        // Verify current password
        if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
            $this->addFlash('error', 'Current password is incorrect!');
            return $this->redirectToRoute('app_user_edit_profile');
        }

        // Verify new passwords match
        if ($newPassword !== $confirmPassword) {
            $this->addFlash('error', 'New passwords do not match!');
            return $this->redirectToRoute('app_user_edit_profile');
        }

        // Verify password length
        if (strlen($newPassword) < 6) {
            $this->addFlash('error', 'Password must be at least 6 characters long!');
            return $this->redirectToRoute('app_user_edit_profile');
        }

        // Hash and update password
        $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
        $user->setPassword($hashedPassword);
        $entityManager->flush();

        $this->addFlash('success', 'Password updated successfully!');
        return $this->redirectToRoute('app_user_edit_profile');
    }
}
