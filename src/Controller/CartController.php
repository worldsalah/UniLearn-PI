<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\User;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Annotation\Route;

class CartController extends AbstractController
{
    public function __construct(
        private ProductRepository $productRepository,
        private EntityManagerInterface $entityManager,
        private RequestStack $requestStack
    ) {}

    #[Route('/cart', name: 'app_cart')]
    public function index(): Response
    {
        $session = $this->requestStack->getSession();
        $cart = $session->get('cart', []);
        $cartItems = [];
        $total = 0;

        foreach ($cart as $productId => $quantity) {
            $product = $this->productRepository->find($productId);
            if ($product) {
                $cartItems[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'subtotal' => $product->getPrice() * $quantity
                ];
                $total += $product->getPrice() * $quantity;
            }
        }

        return $this->render('cart/index.html.twig', [
            'cartItems' => $cartItems,
            'total' => $total,
            'count' => array_sum($cart)
        ]);
    }

    #[Route('/cart/add/{id}', name: 'app_cart_add')]
    public function add(Product $product, Request $request): JsonResponse
    {
        $session = $this->requestStack->getSession();
        $cart = $session->get('cart', []);
        $productId = $product->getId();
        
        if (isset($cart[$productId])) {
            $cart[$productId]++;
        } else {
            $cart[$productId] = 1;
        }

        $session->set('cart', $cart);

        $cartCount = array_sum($cart);
        
        return new JsonResponse([
            'success' => true,
            'message' => 'Product added to cart!',
            'cartCount' => $cartCount,
            'product' => [
                'id' => $product->getId(),
                'title' => $product->getTitle(),
                'price' => $product->getPrice()
            ]
        ]);
    }

    #[Route('/cart/update/{id}', name: 'app_cart_update')]
    public function update(Product $product, Request $request): JsonResponse
    {
        $session = $this->requestStack->getSession();
        $quantity = $request->request->getInt('quantity', 1);
        $cart = $session->get('cart', []);
        $productId = $product->getId();

        if ($quantity > 0) {
            $cart[$productId] = $quantity;
        } else {
            unset($cart[$productId]);
        }

        $session->set('cart', $cart);

        // Recalculate totals
        $total = 0;
        foreach ($cart as $pid => $qty) {
            $p = $this->productRepository->find($pid);
            if ($p) {
                $total += $p->getPrice() * $qty;
            }
        }

        return new JsonResponse([
            'success' => true,
            'total' => $total,
            'cartCount' => array_sum($cart)
        ]);
    }

    #[Route('/cart/remove/{id}', name: 'app_cart_remove')]
    public function remove(Product $product): JsonResponse
    {
        $session = $this->requestStack->getSession();
        $cart = $session->get('cart', []);
        $productId = $product->getId();

        unset($cart[$productId]);
        $session->set('cart', $cart);

        return new JsonResponse([
            'success' => true,
            'message' => 'Product removed from cart',
            'cartCount' => array_sum($cart)
        ]);
    }

    #[Route('/cart/clear', name: 'app_cart_clear')]
    public function clear(): JsonResponse
    {
        $session = $this->requestStack->getSession();
        $session->remove('cart');
        
        return new JsonResponse([
            'success' => true,
            'message' => 'Cart cleared'
        ]);
    }

    #[Route('/cart/count', name: 'app_cart_count')]
    public function count(): JsonResponse
    {
        $session = $this->requestStack->getSession();
        $cart = $session->get('cart', []);
        $cartCount = array_sum($cart);

        return new JsonResponse([
            'count' => $cartCount
        ]);
    }
}
