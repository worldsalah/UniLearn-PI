<?php

namespace App\Controller;

use App\Entity\Favorite;
use App\Entity\Job;
use App\Repository\FavoriteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/favorite')]
#[IsGranted('ROLE_USER')]
class FavoriteController extends AbstractController
{
    #[Route('/toggle/{id}', name: 'app_favorite_toggle', methods: ['POST'])]
    public function toggle(Job $job, FavoriteRepository $favoriteRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $this->getUser();
        $existingFavorite = $favoriteRepository->findByUserAndJob($user, $job);

        if ($existingFavorite) {
            // Remove from favorites
            $entityManager->remove($existingFavorite);
            $entityManager->flush();
            return new JsonResponse([
                'status' => 'removed',
                'message' => 'Job removed from favorites'
            ]);
        } else {
            // Add to favorites
            $favorite = new Favorite();
            $favorite->setUser($user instanceof \App\Entity\User ? $user : null);
            $favorite->setJob($job);
            $entityManager->persist($favorite);
            $entityManager->flush();
            return new JsonResponse([
                'status' => 'added',
                'message' => 'Job added to favorites'
            ]);
        }
    }

    #[Route('/', name: 'app_favorite_index', methods: ['GET'])]
    public function index(FavoriteRepository $favoriteRepository): Response
    {
        $user = $this->getUser();
        $favorites = $favoriteRepository->findByUser($user);
        
        $jobs = array_map(function($favorite) {
            return $favorite->getJob();
        }, $favorites);

        return $this->render('favorite/index.html.twig', [
            'jobs' => $jobs,
        ]);
    }
}
