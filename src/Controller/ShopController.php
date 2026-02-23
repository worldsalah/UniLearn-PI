<?php

namespace App\Controller;

use App\Form\PriceFilterType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/shop')]
class ShopController extends AbstractController
{
    #[Route('/', name: 'app_shop', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        $filterForm = $this->createForm(PriceFilterType::class);
        $filterForm->handleRequest($request);

        // Get filter values
        $minPrice = $filterForm->get('minPrice')->getData();
        $maxPrice = $filterForm->get('maxPrice')->getData();

        // For now, just render the template with the form
        // In a real application, you would filter products based on these values
        return $this->render('shop/index.html.twig', [
            'filterForm' => $filterForm->createView(),
            'minPrice' => $minPrice,
            'maxPrice' => $maxPrice,
        ]);
    }
}
