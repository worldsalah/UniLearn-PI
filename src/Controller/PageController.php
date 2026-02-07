<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PageController extends AbstractController
{
    #[Route('/about', name: 'app_about')]
    public function about(): Response
    {
        return $this->render('page/about.html.twig');
    }

    #[Route('/contact', name: 'app_contact')]
    public function contact(): Response
    {
        return $this->render('page/contact.html.twig');
    }

    #[Route('/courses', name: 'app_courses')]
    public function courses(): Response
    {
        return $this->render('course/index.html.twig');
    }

    #[Route('/login', name: 'app_login')]
    public function login(): Response
    {
        return $this->render('auth/login.html.twig');
    }

    #[Route('/register', name: 'app_register')]
    public function register(): Response
    {
        return $this->render('auth/register.html.twig');
    }

    #[Route('/home-variant', name: 'app_home_variant')]
    public function homeVariant(): Response
    {
        return $this->render('home/index-2.html.twig');
    }

    #[Route('/home-variant-3', name: 'app_home_variant_3')]
    public function homeVariant3(): Response
    {
        return $this->render('home/index-3.html.twig');
    }

    #[Route('/course-grid', name: 'app_course_grid')]
    public function courseGrid(): Response
    {
        return $this->render('course/grid.html.twig');
    }

    #[Route('/course-detail', name: 'app_course_detail')]
    public function courseDetail(): Response
    {
        return $this->render('course/detail.html.twig');
    }

    #[Route('/sign-in', name: 'app_sign_in')]
    public function signIn(): Response
    {
        return $this->render('auth/sign-in.html.twig');
    }

    #[Route('/sign-up', name: 'app_sign_up')]
    public function signUp(): Response
    {
        return $this->render('auth/sign-up.html.twig');
    }

    #[Route('/forgot-password', name: 'app_forgot_password')]
    public function forgotPassword(): Response
    {
        return $this->render('auth/forgot-password.html.twig');
    }

    
    #[Route('/student-dashboard', name: 'app_student_dashboard')]
    public function studentDashboard(): Response
    {
        return $this->render('student/dashboard.html.twig');
    }

    #[Route('/admin-dashboard', name: 'app_admin_dashboard')]
    public function adminDashboard(): Response
    {
        return $this->render('admin/dashboard.html.twig');
    }

    #[Route('/blog-grid', name: 'app_blog_grid')]
    public function blogGrid(): Response
    {
        return $this->render('blog/grid.html.twig');
    }

    #[Route('/shop', name: 'app_shop')]
    public function shop(): Response
    {
        return $this->render('shop/index.html.twig');
    }

    #[Route('/pricing', name: 'app_pricing')]
    public function pricing(): Response
    {
        return $this->render('utility/pricing.html.twig');
    }

    #[Route('/error-404', name: 'app_error_404')]
    public function error404(): Response
    {
        return $this->render('utility/error-404.html.twig');
    }

    #[Route('/home-variant-4', name: 'app_home_variant_4')]
    public function homeVariant4(): Response
    {
        return $this->render('home/index-4.html.twig');
    }

    #[Route('/course-list', name: 'app_course_list')]
    public function courseList(): Response
    {
        return $this->render('course/list.html.twig');
    }

    #[Route('/instructor-list', name: 'app_instructor_list')]
    public function instructorList(): Response
    {
        return $this->render('instructor/list.html.twig');
    }

    #[Route('/student-course-list', name: 'app_student_course_list')]
    public function studentCourseList(): Response
    {
        return $this->render('student/course-list.html.twig');
    }

    #[Route('/coming-soon', name: 'app_coming_soon')]
    public function comingSoon(): Response
    {
        return $this->render('utility/coming-soon.html.twig');
    }

    #[Route('/home-variant-5', name: 'app_home_variant_5')]
    public function homeVariant5(): Response
    {
        return $this->render('home/index-5.html.twig');
    }

    #[Route('/faq', name: 'app_faq')]
    public function faq(): Response
    {
        return $this->render('utility/faq.html.twig');
    }

    #[Route('/blog-detail', name: 'app_blog_detail')]
    public function blogDetail(): Response
    {
        return $this->render('blog/detail.html.twig');
    }

    #[Route('/cart', name: 'app_cart')]
    public function cart(): Response
    {
        return $this->render('shop/cart.html.twig');
    }

    #[Route('/checkout', name: 'app_checkout')]
    public function checkout(): Response
    {
        return $this->render('shop/checkout.html.twig');
    }

    #[Route('/course-detail-adv', name: 'app_course_detail_adv')]
    public function courseDetailAdv(): Response
    {
        return $this->render('course/detail-adv.html.twig');
    }

    #[Route('/course-detail-min', name: 'app_course_detail_min')]
    public function courseDetailMin(): Response
    {
        return $this->render('course/detail-min.html.twig');
    }

    #[Route('/course-detail-module', name: 'app_course_detail_module')]
    public function courseDetailModule(): Response
    {
        return $this->render('course/detail-module.html.twig');
    }

    #[Route('/blog-masonry', name: 'app_blog_masonry')]
    public function blogMasonry(): Response
    {
        return $this->render('blog/masonry.html.twig');
    }

    #[Route('/notification-example', name: 'app_notification_example')]
    public function notificationExample(): Response
    {
        return $this->render('examples/notification-example.html.twig');
    }
}
