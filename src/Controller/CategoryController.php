<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Course;
use App\Repository\CategoryRepository;
use App\Repository\CourseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[Route('/admin')]
class CategoryController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    // Admin routes
    #[Route('/categories', name: 'admin_category_list')]
    // #[IsGranted('ROLE_ADMIN')]
    public function list(CategoryRepository $categoryRepository): Response
    {
        $categories = $categoryRepository->findCategoriesWithCourseCount();
        
        return $this->render('admin/category-list.html.twig', [
            'categories' => $categories,
        ]);
    }

    #[Route('/category/new', name: 'admin_category_new')]
    // #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            $category = new Category();
            $category->setName($request->request->get('name'));
            $category->setDescription($request->request->get('description'));
            $category->setIcon($request->request->get('icon'));
            $category->setColor($request->request->get('color'));
            $category->setIsActive($request->request->getBoolean('is_active', true));
            
            $entityManager->persist($category);
            $entityManager->flush();
            
            $this->addFlash('success', 'Category created successfully!');
            return $this->redirectToRoute('admin_category_list');
        }
        
        return $this->render('admin/category-form.html.twig', [
            'category' => null,
        ]);
    }

    #[Route('/category/{id}/edit', name: 'admin_category_edit')]
    // #[IsGranted('ROLE_ADMIN')]
    public function edit(Category $category, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            $category->setName($request->request->get('name'));
            $category->setDescription($request->request->get('description'));
            $category->setIcon($request->request->get('icon'));
            $category->setColor($request->request->get('color'));
            $category->setIsActive($request->request->getBoolean('is_active', true));
            
            $entityManager->flush();
            
            $this->addFlash('success', 'Category updated successfully!');
            return $this->redirectToRoute('admin_category_list');
        }
        
        return $this->render('admin/category-form.html.twig', [
            'category' => $category,
        ]);
    }

    #[Route('/category/{id}/delete', name: 'admin_category_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    // #[IsGranted('ROLE_ADMIN')]
    public function delete(Category $category, CategoryRepository $categoryRepository, EntityManagerInterface $entityManager, Request $request, CsrfTokenManagerInterface $csrfTokenManager): Response
    {
        // Validate CSRF token
        $token = new CsrfToken('delete' . $category->getId(), $request->request->get('_token'));
        if (!$csrfTokenManager->isTokenValid($token)) {
            $this->addFlash('error', 'Invalid CSRF token. Please try again.');
            return $this->redirectToRoute('admin_category_list');
        }
        
        try {
            // Get all courses associated with this category
            $courses = $category->getCourses();
            $courseCount = $courses->count();
            
            if ($courseCount > 0) {
                // Find a default category to move courses to
                $defaultCategory = $categoryRepository->findOneBy(['name' => 'General']);
                
                // If no default category exists, create one
                if (!$defaultCategory) {
                    $defaultCategory = new Category();
                    $defaultCategory->setName('General');
                    $defaultCategory->setDescription('Default category for uncategorized courses');
                    $defaultCategory->setColor('#6c757d');
                    $defaultCategory->setIsActive(true);
                    $entityManager->persist($defaultCategory);
                    $entityManager->flush();
                }
                
                // Move all courses to the default category
                foreach ($courses as $course) {
                    $course->setCategory($defaultCategory);
                    $entityManager->persist($course);
                }
                
                // Flush the changes to courses first
                $entityManager->flush();
            }
            
            // Now delete the category
            $entityManager->remove($category);
            $entityManager->flush();
            
            $message = $courseCount > 0 
                ? "Category deleted successfully! {$courseCount} course(s) were moved to 'General' category."
                : "Category deleted successfully!";
                
            $this->addFlash('success', $message);
            
        } catch (\Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException $e) {
            $this->addFlash('error', 'Cannot delete category: it is still referenced by other records in the database.');
            return $this->redirectToRoute('admin_category_list');
        } catch (\Exception $e) {
            $this->addFlash('error', 'An error occurred while deleting the category. Please try again.');
            return $this->redirectToRoute('admin_category_list');
        }
        
        return $this->redirectToRoute('admin_category_list');
    }

    }
