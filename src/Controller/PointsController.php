<?php

namespace App\Controller;

use App\Repository\UserPointsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;

class PointsController extends AbstractController
{
    public function __construct(
        private Security $security,
        private UserPointsRepository $userPointsRepository
    ) {}

    public function getUserPoints(): Response
    {
        $user = $this->security->getUser();
        if ($user === null) {
            return new Response('0');
        }

        $userPoints = $this->userPointsRepository->findByUser($user->getId());
        $points = $userPoints !== null ? $userPoints->getTotalPoints() : 0;

        return new Response((string)$points);
    }
}
