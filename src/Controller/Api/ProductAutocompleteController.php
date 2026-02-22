<?php

namespace App\Controller\Api;

use App\Entity\Product;
use FOS\ElasticaBundle\Finder\TransformedFinder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class ProductAutocompleteController extends AbstractController
{
    private TransformedFinder $finder;

    public function __construct(
        #[Target('products.finder')]
        TransformedFinder $finder
    ) {
        $this->finder = $finder;
    }

    /**
     * Autocomplete search for products
     */
    #[Route('/autocomplete/products', name: 'api_autocomplete_products', methods: ['GET'])]
    public function autocompleteProducts(Request $request): JsonResponse
    {
        $query = $request->query->get('q', '');
        $limit = min(10, max(1, (int) $request->query->get('limit', 5)));

        if (strlen($query) < 2) {
            return new JsonResponse(['suggestions' => []]);
        }

        try {
            // Use prefix query with proper Elasticsearch syntax
            $searchQuery = [
                'query' => [
                    'prefix' => [
                        'title' => $query
                    ]
                ]
            ];
            $results = $this->finder->find($searchQuery, $limit);

            $suggestions = [];
            foreach ($results as $product) {
                $suggestions[] = [
                    'id' => $product->getId(),
                    'title' => $product->getTitle(),
                    'description' => substr($product->getDescription(), 0, 100) . '...',
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
