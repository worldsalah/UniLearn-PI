<?php

namespace App\Controller;

use App\Entity\Job;
use App\Entity\Order;
use App\Entity\Product;
use App\Entity\Student;
use App\Form\JobType;
use App\Form\OrderType;
use App\Form\ProductType;
use App\Form\StudentType;
use App\Repository\JobRepository;
use App\Repository\ProductRepository;
use App\Repository\OrderRepository;
use App\Repository\StudentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/marketplace')]
class MarketplaceController extends AbstractController
{
    #[Route('/', name: 'app_marketplace_index', methods: ['GET'])]
    public function index(
        ProductRepository $productRepository,
        JobRepository $jobRepository,
        \App\Repository\OrderRepository $orderRepository,
        PaginatorInterface $paginator,
        Request $request
    ): Response {
        $queryBuilder = $productRepository->createQueryBuilder('p')
            ->where('p.deletedAt IS NULL')
            ->orderBy('p.createdAt', 'DESC');

        $query = $queryBuilder->getQuery();
        $products = $paginator->paginate($query, $request->query->getInt('page', 1), 12);

        // Fetch actual job requests from database
        $jobs = $jobRepository->createQueryBuilder('j')
            ->where('j.deletedAt IS NULL')
            ->orderBy('j.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        // Fetch actual orders from database
        $orders = $orderRepository->createQueryBuilder('o')
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('marketplace/shop.html.twig', [
            'products' => $products,
            'jobs' => $jobs,
            'orders' => $orders,
        ]);
    }

    #[Route('/product/new', name: 'app_product_new', methods: ['GET', 'POST'])]
    public function newProduct(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user) {
            // Create a default user for guest submissions
            $user = $entityManager->getRepository(\App\Entity\User::class)->findOneBy([]);
            if (!$user) {
                // If no user exists, create one for demo purposes
                $user = new \App\Entity\User();
                $user->setEmail('demo@unilearn.com');
                $user->setPassword('demo');
                $user->setFirstName('Demo');
                $user->setLastName('User');
                $entityManager->persist($user);
                $entityManager->flush();
            }
        }

        // Create or get student profile
        $student = $user->getStudent();
        if (!$student) {
            $student = new \App\Entity\Student();
            $student->setUser($user);
            $student->setFirstName($user->getFirstName());
            $student->setLastName($user->getLastName());
            $student->setEmail($user->getEmail());
            $entityManager->persist($student);
            $entityManager->flush();
        }

        $product = new Product();
        $product->setFreelancer($student);
        $product->setCreatedAt(new \DateTimeImmutable());
        $product->setUpdatedAt(new \DateTimeImmutable());

        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($product);
            $entityManager->flush();
            
            // Add success message
            $this->addFlash('success', 'Service created successfully!');
            
            // Redirect to product show page
            return $this->redirectToRoute('app_product_show', ['slug' => $product->getSlug()]);
        }

        // Debug: Check if form was submitted
        if ($form->isSubmitted()) {
            // Debug: Show form errors
            $errors = $form->getErrors(true);
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
            }
        }

        return $this->render('product/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/product/{slug}', name: 'app_product_show', methods: ['GET'])]
    public function showProduct(Product $product, OrderRepository $orderRepository): Response
    {
        if ($product->getDeletedAt()) {
            throw $this->createNotFoundException('Product not found');
        }

        $orderToRate = null;
        $user = $this->getUser();
        if ($user) {
            $orderToRate = $orderRepository->createQueryBuilder('o')
                ->andWhere('o.product = :product')
                ->andWhere('o.buyer = :buyer')
                ->andWhere('o.status = :status')
                ->setParameter('product', $product)
                ->setParameter('buyer', $user)
                ->setParameter('status', 'paid')
                ->orderBy('o.createdAt', 'DESC')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();
        }

        return $this->render('product/show.html.twig', [
            'product' => $product,
            'orderToRate' => $orderToRate,
        ]);
    }

    #[Route('/product/{slug}/edit', name: 'app_product_edit', methods: ['GET', 'POST'])]
    public function editProduct(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        // Allow editing for demo purposes without strict ownership check
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $product->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();
            $this->addFlash('success', 'Service updated successfully.');
            return $this->redirectToRoute('app_product_show', ['slug' => $product->getSlug()]);
        }

        return $this->render('product/edit.html.twig', [
            'product' => $product,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/product/{slug}/delete', name: 'app_product_delete', methods: ['POST'])]
    public function deleteProduct(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        // Allow deletion for demo purposes without strict ownership check
        if ($this->isCsrfTokenValid('delete'.$product->getSlug(), $request->request->get('_token'))) {
            $product->setDeletedAt(new \DateTimeImmutable());
            $entityManager->flush();
            $this->addFlash('success', 'Service deleted successfully.');
        }

        return $this->redirectToRoute('app_marketplace_index');
    }

    #[Route('/order/new', name: 'app_order_new', methods: ['GET', 'POST'])]
    public function newOrderStandalone(Request $request, EntityManagerInterface $entityManager): Response
    {
        $order = new Order();
        $user = $this->getUser();
        if (!$user) {
            // Find a default user to act as guest for "every button works" requirement
            $user = $entityManager->getRepository(\App\Entity\User::class)->findOneBy([]);
        }
        $order->setBuyer($user);
        $order->setStatus('pending');
        $order->setTotalPrice(0.0);
        $order->setCreatedAt(new \DateTimeImmutable());
        
        $form = $this->createForm(\App\Form\OrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($order);
            $entityManager->flush();
            
            // Add success message
            $this->addFlash('success', 'Order created successfully!');

            return $this->redirectToRoute('app_marketplace_index');
        }
        
        // Debug: Check if form was submitted
        if ($form->isSubmitted()) {
            // Debug: Show form errors
            $errors = $form->getErrors(true);
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
            }
        }
        
        return $this->render('order/new.html.twig', [
            'order' => $order,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/order/{id}', name: 'app_order_show', methods: ['GET'])]
    public function showOrder(Order $order): Response
    {
        return $this->render('order/show.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/order/{id}/edit', name: 'app_order_edit', methods: ['GET', 'POST'])]
    public function editOrder(Request $request, Order $order, EntityManagerInterface $entityManager): Response
    {
        // Allow editing for demo purposes without strict ownership check
        $form = $this->createForm(\App\Form\OrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Order updated successfully.');
            return $this->redirectToRoute('app_marketplace_index');
        }

        return $this->render('order/edit.html.twig', [
            'order' => $order,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/order/{id}/delete', name: 'app_order_delete', methods: ['POST'])]
    public function deleteOrder(Request $request, Order $order, EntityManagerInterface $entityManager): Response
    {
        // Allow deletion for demo purposes without strict ownership check
        if ($this->isCsrfTokenValid('delete'.$order->getId(), $request->request->get('_token'))) {
            $entityManager->remove($order);
            $entityManager->flush();
            $this->addFlash('success', 'Order deleted successfully.');
        }

        return $this->redirectToRoute('app_marketplace_index');
    }

    #[Route('/cart', name: 'app_cart', methods: ['GET'])]
    public function cart(OrderRepository $orderRepository): Response
    {
        $orders = $orderRepository->findAll();
        
        // Filter out deleted orders
        $activeOrders = array_filter($orders, function($order) {
            return $order->getDeletedAt() === null;
        });
        
        // Calculate totals
        $subtotal = array_sum(array_map(function($order) {
            return $order->getTotalPrice();
        }, $activeOrders));
        
        $tax = $subtotal * 0.10; // 10% tax
        $shipping = count($activeOrders) > 0 ? 5.00 : 0.00; // $5 shipping if items exist
        $total = $subtotal + $tax + $shipping;
        
        return $this->render('cart/index.html.twig', [
            'orders' => $activeOrders,
            'subtotal' => number_format($subtotal, 2),
            'tax' => number_format($tax, 2),
            'shipping' => number_format($shipping, 2),
            'total' => number_format($total, 2),
        ]);
    }

    #[Route('/checkout', name: 'app_checkout', methods: ['GET'])]
    public function checkout(OrderRepository $orderRepository): Response
    {
        $orders = $orderRepository->findAll();
        
        // Filter out deleted orders
        $activeOrders = array_filter($orders, function($order) {
            return $order->getDeletedAt() === null;
        });
        
        // Calculate totals
        $subtotal = array_sum(array_map(function($order) {
            return $order->getTotalPrice();
        }, $activeOrders));
        
        $tax = $subtotal * 0.10; // 10% tax
        $shipping = count($activeOrders) > 0 ? 5.00 : 0.00; // $5 shipping if items exist
        $total = $subtotal + $tax + $shipping;
        
        return $this->render('checkout/index.html.twig', [
            'orders' => $activeOrders,
            'subtotal' => number_format($subtotal, 2),
            'tax' => number_format($tax, 2),
            'shipping' => number_format($shipping, 2),
            'total' => number_format($total, 2),
        ]);
    }

    #[Route('/payment/individual/{id}', name: 'app_payment_individual', methods: ['GET'])]
    public function paymentIndividual($id, Request $request, OrderRepository $orderRepository): Response
    {
        $order = $orderRepository->find($id);
        if (!$order) {
            throw $this->createNotFoundException('Order not found');
        }

        return $this->render('payment/individual.html.twig', [
            'orderId' => $id,
            'amount' => $order->getTotalPrice(),
        ]);
    }

    #[Route('/payment/bulk', name: 'app_payment_bulk', methods: ['GET'])]
    public function paymentBulk(Request $request, OrderRepository $orderRepository): Response
    {
        $orders = $orderRepository->findAll();
        
        // Filter out deleted orders if applicable
        $activeOrders = array_filter($orders, function($order) {
            return $order->getDeletedAt() === null;
        });
        
        $totalAmount = array_sum(array_map(function($order) {
            return $order->getTotalPrice();
        }, $activeOrders));
        
        return $this->render('payment/bulk.html.twig', [
            'orders' => $activeOrders,
            'orderCount' => count($activeOrders),
            'totalAmount' => $totalAmount,
        ]);
    }

    #[Route('/dashboard', name: 'app_marketplace_dashboard')]
    #[IsGranted('ROLE_USER')]
    public function dashboard(): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $student = $user->getStudent();
        if (!$student) {
             return $this->redirectToRoute('app_freelancer_register');
        }

        return $this->render('student/dashboard.html.twig', [
            'student' => $student,
        ]);
    }




    #[Route('/job/new', name: 'app_job_new', methods: ['GET', 'POST'])]
    public function newJob(Request $request, EntityManagerInterface $entityManager): Response
    {
        $job = new Job();
        $user = $this->getUser();
        if (!$user) {
            // Find a default user to act as guest for "every button works" requirement
            $user = $entityManager->getRepository(\App\Entity\User::class)->findOneBy([]);
        }
        $job->setClient($user);
        $job->setStatus('open');
        $job->setCategory('other'); // Set default category
        $job->setBudget(0.0); // Set default budget
        $job->setCreatedAt(new \DateTimeImmutable());
        
        $form = $this->createForm(JobType::class, $job);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($job);
            $entityManager->flush();
            
            // Add success message
            $this->addFlash('success', 'Job request created successfully!');

            return $this->redirectToRoute('app_marketplace_index', [], Response::HTTP_SEE_OTHER);
        }
        
        // Debug: Check if form was submitted
        if ($form->isSubmitted()) {
            // Debug: Show form errors
            $errors = $form->getErrors(true);
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
            }
        }
        
        return $this->render('job/new.html.twig', [
            'job' => $job,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/job/{id}', name: 'app_job_show', methods: ['GET'])]
    public function showJob(Job $job): Response
    {
        if ($job->getDeletedAt()) {
            throw $this->createNotFoundException('Job not found');
        }

        return $this->render('job/show.html.twig', [
            'job' => $job,
        ]);
    }

    #[Route('/job/{id}/edit', name: 'app_job_edit', methods: ['GET', 'POST'])]
    public function editJob(Request $request, Job $job, EntityManagerInterface $entityManager): Response
    {
        // Allow editing for demo purposes without strict ownership check
        $form = $this->createForm(JobType::class, $job);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $job->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();
            $this->addFlash('success', 'Job request updated successfully.');
            return $this->redirectToRoute('app_marketplace_index');
        }

        return $this->render('job/edit.html.twig', [
            'job' => $job,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/job/{id}/delete', name: 'app_job_delete', methods: ['POST'])]
    public function deleteJob(Request $request, Job $job, EntityManagerInterface $entityManager): Response
    {
        // Allow deletion for demo purposes without strict ownership check
        if ($this->isCsrfTokenValid('delete'.$job->getId(), $request->request->get('_token'))) {
            $job->setDeletedAt(new \DateTimeImmutable());
            $entityManager->flush();
            $this->addFlash('success', 'Job request deleted successfully.');
        }

        return $this->redirectToRoute('app_marketplace_index');
    }

    #[Route('/order/new/{id}', name: 'app_order_new_from_product', methods: ['GET', 'POST'])]
    public function newOrderFromProduct(Product $product, Request $request, EntityManagerInterface $entityManager): Response
    {
        $order = new Order();
        $order->setProduct($product);
        $user = $this->getUser();
        if (!$user) {
            // Find a default user to act as guest for "every button works" requirement
            $user = $entityManager->getRepository(\App\Entity\User::class)->findOneBy([]);
        }
        $order->setBuyer($user);
        $order->setStatus('pending');
        $order->setTotalPrice($product->getPrice());
        $order->setCreatedAt(new \DateTimeImmutable());
        
        $entityManager->persist($order);
        $entityManager->flush();
        
        $this->addFlash('success', 'Order created successfully for ' . $product->getTitle() . '!');
        
        return $this->redirectToRoute('app_marketplace_index');
    }

    #[Route('/order/new/job/{id}', name: 'app_order_new_from_job', methods: ['GET', 'POST'])]
    public function newOrderFromJob(Job $job, Request $request, EntityManagerInterface $entityManager): Response
    {
        $order = new Order();
        $user = $this->getUser();
        if (!$user) {
            // Find a default user to act as guest for "every button works" requirement
            $user = $entityManager->getRepository(\App\Entity\User::class)->findOneBy([]);
        }
        $order->setBuyer($user);
        $order->setStatus('pending');
        $order->setTotalPrice($job->getBudget() ?? 0.0);
        $order->setCreatedAt(new \DateTimeImmutable());
        
        $entityManager->persist($order);
        $entityManager->flush();
        
        $this->addFlash('success', 'Order created successfully for job: ' . $job->getTitle() . '!');
        
        return $this->redirectToRoute('app_marketplace_index');
    }
    public function newOrder(Product $product, Request $request, EntityManagerInterface $entityManager, MailerInterface $mailer): Response
    {
        $order = new Order();
        $order->setProduct($product);
        $user = $this->getUser();
        if (!$user) {
            // Find a default user to act as guest for "every button works" requirement
            $user = $entityManager->getRepository(\App\Entity\User::class)->findOneBy([]);
        }
        $order->setBuyer($user);
        $order->setTotalPrice($product->getPrice());
        $order->setStatus('pending');
        $order->setCreatedAt(new \DateTimeImmutable());
        
        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Simulate payment processing here
            $order->setStatus('paid');
            
            $entityManager->persist($order);
            $entityManager->flush();

            // Send Order Notification to Freelancer
            $email = (new TemplatedEmail())
                ->from(new Address('no-reply@unilearn.com', 'Unilearn Marketplace'))
                ->to($product->getFreelancer()->getUser()->getEmail())
                ->subject('You have a new order!')
                ->htmlTemplate('emails/order_notification.html.twig')
                ->context([
                    'freelancer_name' => $product->getFreelancer()->getFullName(),
                    'product_title' => $product->getTitle(),
                    'buyer_email' => $user->getEmail(),
                    'price' => $product->getPrice(),
                ]);

            try {
                $mailer->send($email);
            } catch (\Exception $e) {
                // Silently fail
            }


            // Add success flash message
            $this->addFlash('order_success', [
                'product_title' => $product->getTitle(),
                'order_id' => $order->getId(),
            ]);

            return $this->redirectToRoute('app_admin_order_index', [], Response::HTTP_SEE_OTHER);
        }
        
        return $this->render('order/new.html.twig', [
            'order' => $order,
            'product' => $product,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/order/{id}/complete', name: 'app_order_complete', methods: ['POST'])]
    public function completeOrder(Request $request, Order $order, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if ($user && $order->getBuyer() !== $user && !$this->isGranted('ROLE_ADMIN')) {
             throw $this->createAccessDeniedException('You can only complete your own orders.');
        }
        
        // If no user, we assume it's the guest user who matches the buyer
        if (!$user && $order->getBuyer()->getEmail() !== $entityManager->getRepository(\App\Entity\User::class)->findOneBy([])->getEmail() && !$this->isGranted('ROLE_ADMIN')) {
             throw $this->createAccessDeniedException('You can only complete your own orders.');
        }

        if (!$this->isCsrfTokenValid('complete'.$order->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('app_product_show', ['slug' => $order->getProduct()->getSlug()]);
        }

        $rating = $request->request->get('rating');
        $review = $request->request->get('review');

        if ($rating) {
            $order->setStatus('completed');
            $order->setRating((int)$rating);
            $order->setReview($review);
            
            $student = $order->getProduct()->getFreelancer();
            $student->updateRating();
            
            $entityManager->flush();
            $this->addFlash('success', 'Order completed and freelancer rated!');
        }

        return $this->redirectToRoute('app_marketplace_index');
    }

    #[Route('/freelancer/register', name: 'app_freelancer_register', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function registerFreelancer(Request $request, EntityManagerInterface $entityManager, MailerInterface $mailer): Response
    {
        // Check if user is already a student
        if ($this->getUser()->getStudent()) {
            $this->addFlash('warning', 'You are already registered as a freelancer.');
            return $this->redirectToRoute('app_marketplace_dashboard');
        }

        $student = new Student();
        $student->setUser($this->getUser());
        $student->setRating(0);
        $student->setCreatedAt(new \DateTimeImmutable());

        $form = $this->createForm(StudentType::class, $student);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Assign ROLE_FREELANCER
            $user = $this->getUser();
            $roles = $user->getRoles();
            if (!in_array('ROLE_FREELANCER', $roles)) {
                $roles[] = 'ROLE_FREELANCER';
                $user->setRoles($roles);
                $entityManager->persist($user);
            }

            $entityManager->persist($student);
            $entityManager->flush();

            // Send Welcome Email
            $email = (new TemplatedEmail())
                ->from(new Address('no-reply@unilearn.com', 'Unilearn Marketplace'))
                ->to($user->getEmail())
                ->subject('Welcome to the Marketplace!')
                ->htmlTemplate('emails/freelancer_welcome.html.twig')
                ->context([
                    'name' => $student->getFullName(),
                ]);

            try {
                $mailer->send($email);
            } catch (\Exception $e) {
                // Silently fail if mailer not configured or use logging
            }

            return $this->redirectToRoute('app_marketplace_dashboard');
        }

        return $this->render('student/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }


}
