<?php

namespace App\Controller;

use App\Form\ProfileType;
use App\Repository\CourseRepository;
use App\Repository\OrderRepository;
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
        CourseRepository $courseRepository,
        OrderRepository $orderRepository,
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof \App\Entity\User) {
            return $this->redirectToRoute('app_login');
        }

        // Get dynamic stats for the user
        $courses = $courseRepository->findBy(['user' => $user]);
        $orders = $orderRepository->findBy(['buyer' => $user]);
        
        $stats = [
            'totalCourses' => count($courses),
            'totalOrders' => count($orders),
            'completedOrders' => count(array_filter($orders, fn($o) => $o->getStatus() === 'completed')),
            'totalSpent' => array_sum(array_map(fn($o) => $o->getTotalPrice(), $orders)),
        ];

        $form = $this->createForm(ProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle profile image upload
            $profileImageFile = $form->get('profileImage')->getData();
            if ($profileImageFile !== null) {
                $newFilename = uniqid() . '.' . $profileImageFile->guessExtension();

                $projectDir = $this->getParameter('kernel.project_dir');
                $projectDirString = is_string($projectDir) ? $projectDir : '';
                $profileImageFile->move(
                    $projectDirString . '/public/uploads/profiles',
                    $newFilename
                );

                $user->setProfileImage('/uploads/profiles/' . $newFilename);
            }

            // Handle password change
            $newPassword = $form->get('plainPassword')->get('first')->getData();
            if ($newPassword !== null && $newPassword !== '') {
                $currentPassword = $form->get('currentPassword')->getData();
                if ($currentPassword !== null && $currentPassword !== '' && $passwordHasher->isPasswordValid($user, $currentPassword)) {
                    $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
                    $this->addFlash('success', 'Your password has been updated successfully.');
                } else {
                    $this->addFlash('error', 'Current password is incorrect.');
                    return $this->render('profile/edit.html.twig', [
                        'form' => $form->createView(),
                        'user' => $user,
                        'stats' => $stats,
                    ]);
                }
            }

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Your profile has been updated successfully!');
            return $this->redirectToRoute('app_profile_edit');
        }

        return $this->render('profile/edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
            'stats' => $stats,
        ]);
    }
}
