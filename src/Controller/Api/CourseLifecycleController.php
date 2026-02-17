<?php

namespace App\Controller\Api;

use App\Entity\Course;
use App\Entity\CourseVersion;
use App\Enum\CourseStatus;
use App\Service\CourseLifecycleService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/courses')]
class CourseLifecycleController extends AbstractController
{
    public function __construct(
        private CourseLifecycleService $courseLifecycleService
    ) {}

    #[Route('/{id}/submit', name: 'api_course_submit', methods: ['POST'])]
    #[IsGranted('ROLE_INSTRUCTOR')]
    public function submitCourse(Course $course): JsonResponse
    {
        if ($course->getUser() !== $this->getUser()) {
            throw new AccessDeniedHttpException('You can only submit your own courses');
        }

        try {
            $result = $this->courseLifecycleService->transitionCourseStatus(
                $course,
                CourseStatus::IN_REVIEW
            );

            return new JsonResponse($result);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    #[Route('/{id}/publish', name: 'api_course_publish', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function publishCourse(Course $course): JsonResponse
    {
        try {
            $result = $this->courseLifecycleService->transitionCourseStatus(
                $course,
                CourseStatus::PUBLISHED
            );

            return new JsonResponse($result);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    #[Route('/{id}/reject', name: 'api_course_reject', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function rejectCourse(Request $request, Course $course): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new BadRequestHttpException('Invalid JSON data');
        }

        $reason = $data['reason'] ?? null;
        if (empty($reason)) {
            throw new BadRequestHttpException('Rejection reason is required');
        }

        try {
            $result = $this->courseLifecycleService->transitionCourseStatus(
                $course,
                CourseStatus::REJECTED,
                $reason
            );

            return new JsonResponse($result);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    #[Route('/{id}/archive', name: 'api_course_archive', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function archiveCourse(Course $course): JsonResponse
    {
        try {
            $result = $this->courseLifecycleService->transitionCourseStatus(
                $course,
                CourseStatus::ARCHIVED
            );

            return new JsonResponse($result);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    #[Route('/{id}/delete', name: 'api_course_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function deleteCourse(Course $course): JsonResponse
    {
        try {
            $result = $this->courseLifecycleService->transitionCourseStatus(
                $course,
                CourseStatus::SOFT_DELETED
            );

            return new JsonResponse($result);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    #[Route('/{id}/restore', name: 'api_course_restore', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function restoreCourse(Course $course): JsonResponse
    {
        try {
            $result = $this->courseLifecycleService->transitionCourseStatus(
                $course,
                CourseStatus::DRAFT
            );

            return new JsonResponse($result);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    #[Route('/{id}/validate', name: 'api_course_validate', methods: ['POST'])]
    #[IsGranted('ROLE_INSTRUCTOR')]
    public function validateCourse(Course $course): JsonResponse
    {
        if ($course->getUser() !== $this->getUser()) {
            throw new AccessDeniedHttpException('You can only validate your own courses');
        }

        $errors = $this->courseLifecycleService->validateCourseForSubmission($course);

        return new JsonResponse([
            'valid' => empty($errors),
            'errors' => $errors,
            'course_id' => $course->getId()
        ]);
    }

    #[Route('/{id}/history', name: 'api_course_history', methods: ['GET'])]
    #[IsGranted('ROLE_INSTRUCTOR')]
    public function getCourseHistory(Course $course): JsonResponse
    {
        // Instructors can only see their own course history
        if (!$this->isGranted('ROLE_ADMIN') && $course->getUser() !== $this->getUser()) {
            throw new AccessDeniedHttpException('Access denied');
        }

        $history = $this->courseLifecycleService->getCourseHistory($course);

        $historyData = array_map(function ($log) {
            return [
                'id' => $log->getId(),
                'from_status' => $log->getFromStatus(),
                'to_status' => $log->getToStatus(),
                'reason' => $log->getReason(),
                'changed_by' => $log->getChangedBy()->getFullName(),
                'changed_by_email' => $log->getChangedBy()->getEmail(),
                'created_at' => $log->getCreatedAt()->format('Y-m-d H:i:s'),
                'ip_address' => $log->getIpAddress(),
                'metadata' => $log->getMetadata()
            ];
        }, $history);

        return new JsonResponse([
            'course_id' => $course->getId(),
            'history' => $historyData
        ]);
    }

    #[Route('/{id}/versions', name: 'api_course_versions', methods: ['GET'])]
    #[IsGranted('ROLE_INSTRUCTOR')]
    public function getCourseVersions(Course $course): JsonResponse
    {
        // Instructors can only see their own course versions
        if (!$this->isGranted('ROLE_ADMIN') && $course->getUser() !== $this->getUser()) {
            throw new AccessDeniedHttpException('Access denied');
        }

        $versions = $this->courseLifecycleService->getCourseVersions($course);

        $versionsData = array_map(function ($version) {
            return [
                'id' => $version->getId(),
                'version_number' => $version->getVersionNumber(),
                'title' => $version->getTitle(),
                'price' => $version->getPrice(),
                'created_by' => $version->getCreatedBy()->getFullName(),
                'created_at' => $version->getCreatedAt()->format('Y-m-d H:i:s'),
                'publish_status' => $version->getPublishStatus(),
                'version_notes' => $version->getVersionNotes()
            ];
        }, $versions);

        return new JsonResponse([
            'course_id' => $course->getId(),
            'versions' => $versionsData
        ]);
    }

    #[Route('/{id}/restore-from-version/{versionId}', name: 'api_course_restore_version', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function restoreFromVersion(Course $course, int $versionId): JsonResponse
    {
        $version = $this->courseLifecycleService->getCourseVersion($versionId);
        
        if (!$version || $version->getCourse() !== $course) {
            throw new BadRequestHttpException('Invalid version');
        }

        try {
            $result = $this->courseLifecycleService->restoreFromVersion(
                $course,
                $version,
                $this->getUser()
            );

            return new JsonResponse($result);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    #[Route('/transitions', name: 'api_course_transitions', methods: ['GET'])]
    public function getAllowedTransitions(): JsonResponse
    {
        return new JsonResponse([
            'transitions' => CourseStatus::getAllowedTransitions(),
            'statuses' => array_map(function ($status) {
                return [
                    'value' => $status->value,
                    'label' => $status->getLabel(),
                    'description' => $status->getDescription(),
                    'is_editable' => $status->isEditable(),
                    'is_visible_to_students' => $status->isVisibleToStudents(),
                    'required_role' => $status->getRequiredRole()
                ];
            }, CourseStatus::cases())
        ]);
    }
}
