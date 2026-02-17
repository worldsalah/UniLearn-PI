<?php

namespace App\Controller\Admin;

use App\Entity\Course;
use App\Entity\CourseVersion;
use App\Enum\CourseStatus;
use App\Repository\CourseRepository;
use App\Service\CourseLifecycleService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/courses')]
#[IsGranted('ROLE_ADMIN')]
class CourseManagementController extends AbstractController
{
    public function __construct(
        private CourseLifecycleService $courseLifecycleService,
        private CourseRepository $courseRepository
    ) {}

    #[Route('/', name: 'admin_courses_dashboard')]
    public function dashboard(): Response
    {
        $stats = [
            'total' => $this->courseRepository->count([]),
            'draft' => $this->courseRepository->count(['status' => CourseStatus::DRAFT->value]),
            'in_review' => $this->courseRepository->count(['status' => CourseStatus::IN_REVIEW->value]),
            'published' => $this->courseRepository->count(['status' => CourseStatus::PUBLISHED->value]),
            'rejected' => $this->courseRepository->count(['status' => CourseStatus::REJECTED->value]),
            'archived' => $this->courseRepository->count(['status' => CourseStatus::ARCHIVED->value]),
        ];

        $pendingReview = $this->courseRepository->findBy(
            ['status' => CourseStatus::IN_REVIEW->value],
            ['submittedAt' => 'ASC'],
            10
        );

        return $this->render('admin/courses/dashboard.html.twig', [
            'stats' => $stats,
            'pending_review' => $pendingReview
        ]);
    }

    #[Route('/pending-review', name: 'admin_courses_pending_review')]
    public function pendingReview(): Response
    {
        $courses = $this->courseRepository->findBy(
            ['status' => CourseStatus::IN_REVIEW->value],
            ['submittedAt' => 'ASC']
        );

        return $this->render('admin/courses/pending-review.html.twig', [
            'courses' => $courses
        ]);
    }

    #[Route('/published', name: 'admin_courses_published')]
    public function published(): Response
    {
        $courses = $this->courseRepository->findBy(
            ['status' => CourseStatus::PUBLISHED->value],
            ['publishedAt' => 'DESC']
        );

        return $this->render('admin/courses/published.html.twig', [
            'courses' => $courses
        ]);
    }

    #[Route('/rejected', name: 'admin_courses_rejected')]
    public function rejected(): Response
    {
        $courses = $this->courseRepository->findBy(
            ['status' => CourseStatus::REJECTED->value],
            ['reviewedAt' => 'DESC']
        );

        return $this->render('admin/courses/rejected.html.twig', [
            'courses' => $courses
        ]);
    }

    #[Route('/{id}/review', name: 'admin_course_review')]
    public function reviewCourse(Course $course): Response
    {
        if ($course->getStatus() !== CourseStatus::IN_REVIEW->value) {
            $this->addFlash('error', 'This course is not in review status');
            return $this->redirectToRoute('admin_courses_pending_review');
        }

        $history = $this->courseLifecycleService->getCourseHistory($course);
        $versions = $this->courseLifecycleService->getCourseVersions($course);

        return $this->render('admin/courses/review.html.twig', [
            'course' => $course,
            'history' => $history,
            'versions' => $versions,
            'validation_errors' => $this->courseLifecycleService->validateCourseForSubmission($course)
        ]);
    }

    #[Route('/{id}/versions', name: 'admin_course_versions')]
    public function courseVersions(Course $course): Response
    {
        $versions = $this->courseLifecycleService->getCourseVersions($course);

        return $this->render('admin/courses/versions.html.twig', [
            'course' => $course,
            'versions' => $versions
        ]);
    }

    #[Route('/{id}/version/{versionId}/compare', name: 'admin_course_version_compare')]
    public function compareVersions(Course $course, int $versionId): Response
    {
        $version = $this->courseLifecycleService->getCourseVersion($versionId);
        
        if (!$version || $version->getCourse() !== $course) {
            $this->addFlash('error', 'Version not found');
            return $this->redirectToRoute('admin_course_versions', ['id' => $course->getId()]);
        }

        return $this->render('admin/courses/compare-versions.html.twig', [
            'course' => $course,
            'version' => $version
        ]);
    }

    #[Route('/{id}/audit-log', name: 'admin_course_audit_log')]
    public function auditLog(Course $course): Response
    {
        $history = $this->courseLifecycleService->getCourseHistory($course);

        return $this->render('admin/courses/audit-log.html.twig', [
            'course' => $course,
            'history' => $history
        ]);
    }

    #[Route('/{id}/restore-from-version/{versionId}', name: 'admin_course_restore_version', methods: ['POST'])]
    public function restoreFromVersion(Request $request, Course $course, int $versionId): Response
    {
        if (!$this->isCsrfTokenValid('restore_version', $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token');
            return $this->redirectToRoute('admin_course_versions', ['id' => $course->getId()]);
        }

        $version = $this->courseLifecycleService->getCourseVersion($versionId);
        
        if (!$version || $version->getCourse() !== $course) {
            $this->addFlash('error', 'Version not found');
            return $this->redirectToRoute('admin_course_versions', ['id' => $course->getId()]);
        }

        try {
            $result = $this->courseLifecycleService->restoreFromVersion(
                $course,
                $version,
                $this->getUser()
            );

            $this->addFlash('success', $result['message']);
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('admin_course_versions', ['id' => $course->getId()]);
    }
}
