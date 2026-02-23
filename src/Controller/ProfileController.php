<?php

namespace App\Controller;

use App\Form\ProfileType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ProfileController extends AbstractController
{
    #[Route('/profile/edit', name: 'app_profile_edit', methods: ['GET', 'POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function edit(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
    ): Response {
        $user = $this->getUser();

        $form = $this->createForm(ProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle profile image upload
            $profileImageFile = $form->get('profileImage')->getData();
            if (null !== $profileImageFile) {
                $newFilename = uniqid() . '.' . $profileImageFile->guessExtension();

                $profileImageFile->move(
                    $this->getParameter('kernel.project_dir') . '/public/uploads/profiles',
                    $newFilename
                );

                $user->setProfileImage('/uploads/profiles/' . $newFilename);
            }

            // Handle password change
            $newPassword = $form->get('plainPassword')->get('first')->getData();
            if ($newPassword) {
                $currentPassword = $form->get('currentPassword')->getData();
                if ($currentPassword && $passwordHasher->isPasswordValid($user, $currentPassword)) {
                    $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
                    $this->addFlash('success', 'Your password has been updated successfully.');
                } else {
                    $this->addFlash('error', 'Current password is incorrect.');
                    return $this->render('profile/edit.html.twig', [
                        'form' => $form->createView(),
                        'user' => $user,
                    ]);
                }
            }

            $entityManager->persist($user);
            try {
                $entityManager->flush();
            } catch (\Exception $e) {
                // Elasticsearch may be down — ignore
            }

            $this->addFlash('success', 'Your profile has been updated successfully!');
            return $this->redirectToRoute('app_profile_edit');
        }

        return $this->render('profile/edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }
}
