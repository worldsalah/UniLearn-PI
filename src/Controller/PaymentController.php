<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Annotation\Route;

class PaymentController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RequestStack $requestStack
    ) {}

    #[Route('/payment', name: 'app_payment')]
    public function index(): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }
        
        $session = $this->requestStack->getSession();
        $cart = $session->get('cart', []);
        $cartItems = [];
        $total = 0;
        $subtotal = 0;
        $tax = 0;
        $shipping = 0;

        foreach ($cart as $productId => $quantity) {
            $product = $this->entityManager->getRepository(\App\Entity\Product::class)->find($productId);
            if ($product) {
                $itemTotal = $product->getPrice() * $quantity;
                $cartItems[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'subtotal' => $itemTotal
                ];
                $subtotal += $itemTotal;
            }
        }

        // Calculate tax (10% for example)
        $tax = $subtotal * 0.1;
        $shipping = $subtotal > 0 ? 15.00 : 0; // Flat shipping rate
        $total = $subtotal + $tax + $shipping;

        return $this->render('payment/index.html.twig', [
            'cartItems' => $cartItems,
            'subtotal' => $subtotal,
            'tax' => $tax,
            'shipping' => $shipping,
            'total' => $total,
            'count' => array_sum($cart)
        ]);
    }

    #[Route('/payment/process', name: 'app_payment_process')]
    public function process(Request $request): JsonResponse
    {
        if (!$this->getUser()) {
            return new JsonResponse([
                'success' => false,
                'message' => 'You must be logged in to make a payment'
            ], 401);
        }
        
        $session = $this->requestStack->getSession();
        $cart = $session->get('cart', []);
        
        if (empty($cart)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Your cart is empty'
            ], 400);
        }

        $paymentMethod = $request->request->get('payment_method');
        $amount = $request->request->get('amount');
        $currency = $request->request->get('currency', 'USD');

        // Validate payment method
        $allowedMethods = ['stripe', 'paypal', 'credit_card', 'bank_transfer'];
        if (!in_array($paymentMethod, $allowedMethods)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Invalid payment method'
            ], 400);
        }

        try {
            // Process payment based on method
            $paymentResult = $this->processPayment($paymentMethod, $amount, $currency, $request->request->all());

            if ($paymentResult['success']) {
                // Create order
                $order = $this->createOrder($cart, $paymentMethod, $paymentResult, $amount);

                // Clear cart
                $session->remove('cart');

                return new JsonResponse([
                    'success' => true,
                    'message' => 'Payment successful!',
                    'order_id' => $order->getId(),
                    'payment_id' => $paymentResult['payment_id'],
                    'redirect_url' => $this->generateUrl('app_payment_success', ['id' => $order->getId()])
                ]);
            } else {
                return new JsonResponse([
                    'success' => false,
                    'message' => $paymentResult['message'] ?? 'Payment failed'
                ], 400);
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Payment processing error: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/payment/success/{id}', name: 'app_payment_success')]
    public function success(Order $order): Response
    {
        return $this->render('payment/success.html.twig', [
            'order' => $order
        ]);
    }

    #[Route('/payment/cancel', name: 'app_payment_cancel')]
    public function cancel(): Response
    {
        return $this->render('payment/cancel.html.twig');
    }

    #[Route('/payment/webhook/stripe', name: 'app_payment_webhook_stripe')]
    public function stripeWebhook(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $sig_header = $request->headers->get('stripe-signature');
        $webhook_secret = $_ENV['STRIPE_WEBHOOK_SECRET'] ?? '';

        // Verify webhook signature (implement proper verification)
        $event = json_decode($payload, true);

        if ($event && isset($event['type'])) {
            switch ($event['type']) {
                case 'payment_intent.succeeded':
                    $this->handleSuccessfulPayment($event['data']['object']);
                    break;
                case 'payment_intent.payment_failed':
                    $this->handleFailedPayment($event['data']['object']);
                    break;
            }
        }

        return new JsonResponse(['status' => 'success']);
    }

    #[Route('/payment/methods', name: 'app_payment_methods')]
    public function getPaymentMethods(): JsonResponse
    {
        return new JsonResponse([
            'methods' => [
                [
                    'id' => 'stripe',
                    'name' => 'Credit Card (Stripe)',
                    'icon' => 'fab fa-cc-stripe',
                    'type' => 'card',
                    'fee' => 2.9,
                    'description' => 'Pay securely with credit/debit cards'
                ],
                [
                    'id' => 'paypal',
                    'name' => 'PayPal',
                    'icon' => 'fab fa-paypal',
                    'type' => 'wallet',
                    'fee' => 3.4,
                    'description' => 'Pay with your PayPal account'
                ],
                [
                    'id' => 'credit_card',
                    'name' => 'Credit Card',
                    'icon' => 'fas fa-credit-card',
                    'type' => 'card',
                    'fee' => 2.5,
                    'description' => 'Direct credit card payment'
                ],
                [
                    'id' => 'bank_transfer',
                    'name' => 'Bank Transfer',
                    'icon' => 'fas fa-university',
                    'type' => 'bank',
                    'fee' => 0,
                    'description' => 'Direct bank transfer (1-3 business days)'
                ]
            ]
        ]);
    }

    private function processPayment(string $method, float $amount, string $currency, array $data): array
    {
        switch ($method) {
            case 'stripe':
                return $this->processStripePayment($amount, $currency, $data);
            case 'paypal':
                return $this->processPayPalPayment($amount, $currency, $data);
            case 'credit_card':
                return $this->processCreditCardPayment($amount, $currency, $data);
            case 'bank_transfer':
                return $this->processBankTransfer($amount, $currency, $data);
            default:
                return ['success' => false, 'message' => 'Unsupported payment method'];
        }
    }

    private function processStripePayment(float $amount, string $currency, array $data): array
    {
        // Simulate Stripe payment processing
        // In production, use Stripe SDK: \Stripe\Stripe::setApiKey($secret)
        
        $paymentIntent = [
            'id' => 'pi_' . uniqid(),
            'status' => 'succeeded',
            'amount' => $amount * 100, // Stripe uses cents
            'currency' => strtolower($currency),
            'payment_method' => $data['payment_method_id'] ?? 'pm_card_' . uniqid()
        ];

        return [
            'success' => true,
            'payment_id' => $paymentIntent['id'],
            'status' => $paymentIntent['status'],
            'data' => $paymentIntent
        ];
    }

    private function processPayPalPayment(float $amount, string $currency, array $data): array
    {
        // Simulate PayPal payment processing
        // In production, use PayPal SDK
        
        $payment = [
            'id' => 'PAY-' . uniqid(),
            'status' => 'completed',
            'amount' => $amount,
            'currency' => $currency,
            'payer_id' => 'payer_' . uniqid()
        ];

        return [
            'success' => true,
            'payment_id' => $payment['id'],
            'status' => $payment['status'],
            'data' => $payment
        ];
    }

    private function processCreditCardPayment(float $amount, string $currency, array $data): array
    {
        // Simulate credit card processing
        // In production, use payment gateway like Braintree, Authorize.net, etc.
        
        $payment = [
            'id' => 'CC-' . uniqid(),
            'status' => 'approved',
            'amount' => $amount,
            'currency' => $currency,
            'auth_code' => rand(100000, 999999),
            'last4' => substr($data['card_number'] ?? '4242424242424242', -4)
        ];

        return [
            'success' => true,
            'payment_id' => $payment['id'],
            'status' => $payment['status'],
            'data' => $payment
        ];
    }

    private function processBankTransfer(float $amount, string $currency, array $data): array
    {
        // Bank transfer doesn't process immediately
        $transfer = [
            'id' => 'BT-' . uniqid(),
            'status' => 'pending',
            'amount' => $amount,
            'currency' => $currency,
            'reference' => 'REF-' . strtoupper(uniqid()),
            'estimated_completion' => date('Y-m-d', strtotime('+3 business days'))
        ];

        return [
            'success' => true,
            'payment_id' => $transfer['id'],
            'status' => $transfer['status'],
            'data' => $transfer
        ];
    }

    private function createOrder(array $cart, string $paymentMethod, array $paymentResult, float $total): Order
    {
        // For now, create a simple order with the first product
        // In production, you'd want to create multiple orders or use OrderItem entity
        $firstProductId = array_key_first($cart);
        $product = $this->entityManager->getRepository(\App\Entity\Product::class)->find($firstProductId);
        
        if (!$product) {
            throw new \Exception('Product not found');
        }
        
        $order = new Order();
        $order->setBuyer($this->getUser());
        $order->setProduct($product);
        $order->setStatus('paid');
        $order->setTotalPrice($total);
        $order->setCreatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($order);
        $this->entityManager->flush();

        return $order;
    }

    private function handleSuccessfulPayment(array $paymentData): void
    {
        // Handle successful payment webhook
        // Update order status, send confirmation email, etc.
    }

    private function handleFailedPayment(array $paymentData): void
    {
        // Handle failed payment webhook
        // Update order status, send notification, etc.
    }
}
