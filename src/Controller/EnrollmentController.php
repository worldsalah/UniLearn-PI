<?php

namespace App\Controller;

use App\Entity\Course;
use App\Entity\Enrollment;
use App\Entity\Lesson;
use App\Form\EnrollmentType;
use App\Repository\EnrollmentRepository;
use App\Repository\LessonRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/enrollment')]
class EnrollmentController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private EnrollmentRepository $enrollmentRepository;
    private LessonRepository $lessonRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        EnrollmentRepository $enrollmentRepository,
        LessonRepository $lessonRepository
    ) {
        $this->entityManager = $entityManager;
        $this->enrollmentRepository = $enrollmentRepository;
        $this->lessonRepository = $lessonRepository;
    }

    #[Route('/course/{id}/enroll', name: 'app_course_enroll', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function enroll(Course $course, Request $request): Response
    {
        $user = $this->getUser();
        
        // Check if user is already enrolled
        $existingEnrollment = $this->enrollmentRepository->findOneByUserAndCourse($user, $course);

        if ($existingEnrollment) {
            $this->addFlash('info', 'You are already enrolled in this course.');
            return $this->redirectToFirstLesson($course, $user);
        }

        // Create new enrollment
        $enrollment = new Enrollment();
        $enrollment->setUser($user);
        $enrollment->setCourse($course);
        $enrollment->setStatus('active');
        $enrollment->setProgress(0.0);

        // Create and handle form
        $form = $this->createForm(EnrollmentType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($enrollment);
            $this->entityManager->flush();

            $this->addFlash('success', 'Successfully enrolled in ' . $course->getTitle() . '!');

            // Redirect to first lesson or course dashboard
            return $this->redirectToFirstLesson($course, $user);
        }

        // If form is not valid, redirect back to course page
        $this->addFlash('error', 'There was an error enrolling in the course. Please try again.');
        return $this->redirectToRoute('app_course_show', ['id' => $course->getId()]);
    }

    #[Route('/course/{id}/dashboard', name: 'app_course_dashboard', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function dashboard(Course $course): Response
    {
        $user = $this->getUser();
        
        // Check if user is enrolled
        $enrollment = $this->enrollmentRepository->findOneBy([
            'user' => $user,
            'course' => $course
        ]);

        if (!$enrollment) {
            $this->addFlash('error', 'You must enroll in this course first.');
            return $this->redirectToRoute('app_course_show', ['id' => $course->getId()]);
        }

        // Get all lessons for this course
        $lessons = $this->lessonRepository->findByCourse($course);

        return $this->render('enrollment/dashboard.html.twig', [
            'course' => $course,
            'enrollment' => $enrollment,
            'lessons' => $lessons,
            'progress' => $enrollment->getProgress(),
        ]);
    }

    private function redirectToFirstLesson(Course $course, $user): Response
    {
        // Get the first lesson of the course
        $firstLesson = $this->lessonRepository->findFirstLessonByCourse($course);

        if ($firstLesson) {
            // Redirect to first lesson
            return $this->redirectToRoute('app_lesson_show', ['id' => $firstLesson->getId()]);
        } else {
            // If no lessons, redirect to course dashboard
            return $this->redirectToRoute('app_course_dashboard', ['id' => $course->getId()]);
        }
    }

    #[Route('/my-courses', name: 'app_my_courses', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function myCourses(): Response
    {
        $user = $this->getUser();
        $enrollments = $this->enrollmentRepository->findByUser($user);

        return $this->render('enrollment/my-courses.html.twig', [
            'enrollments' => $enrollments,
        ]);
    }
}
