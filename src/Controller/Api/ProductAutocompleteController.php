<?php

namespace App\Controller\Api;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class ProductAutocompleteController extends AbstractController
{
    private ProductRepository $productRepository;

    public function __construct(ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    /**
     * Autocomplete search for products (database fallback)
     */
    #[Route('/autocomplete/products', name: 'api_autocomplete_products', methods: ['GET'])]
    public function autocompleteProducts(Request $request): JsonResponse
    {
        $query = $request->query->get('q', '');
        $limit = min(10, max(1, (int) $request->query->get('limit', 5)));

        if (strlen((string) $query) < 2) {
            return new JsonResponse(['suggestions' => []]);
        }

        try {
            // Database fallback search
            $products = $this->productRepository->createQueryBuilder('p')
                ->where('p.title LIKE :query')
                ->setParameter('query', $query . '%')
                ->setMaxResults($limit)
                ->getQuery()
                ->getResult();

            $suggestions = [];
            foreach ($products as $product) {
                $suggestions[] = [
                    'id' => $product->getId(),
                    'title' => $product->getTitle(),
                    'description' => substr($product->getDescription() ?? '', 0, 100) . '...',
                    'price' => $product->getPrice(),
                    'url' => $this->generateUrl('app_product_show', ['slug' => $product->getSlug()])
                ];
            }

            return new JsonResponse([
                'success' => true,
                'suggestions' => $suggestions
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage(),
                'suggestions' => []
            ], 500);
        }
    }
}
