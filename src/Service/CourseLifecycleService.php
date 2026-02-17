<?php

namespace App\Service;

use App\Entity\Course;
use App\Entity\CourseAuditLog;
use App\Entity\CourseVersion;
use App\Entity\User;
use App\Enum\CourseStatus;
use App\Repository\CourseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class CourseLifecycleService
{
    private EntityManagerInterface $entityManager;
    private Security $security;
    private ValidatorInterface $validator;
    private RequestStack $requestStack;
    private EventDispatcherInterface $eventDispatcher;
    private CourseRepository $courseRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        Security $security,
        ValidatorInterface $validator,
        RequestStack $requestStack,
        EventDispatcherInterface $eventDispatcher,
        CourseRepository $courseRepository
    ) {
        $this->entityManager = $entityManager;
        $this->security = $security;
        $this->validator = $validator;
        $this->requestStack = $requestStack;
        $this->eventDispatcher = $eventDispatcher;
        $this->courseRepository = $courseRepository;
    }

    public function canTransitionTo(Course $course, CourseStatus $newStatus): bool
    {
        $currentStatus = CourseStatus::tryFrom($course->getStatus());
        
        if (!$currentStatus) {
            return false;
        }

        return $currentStatus->canTransitionTo($newStatus);
    }

    public function validateCourseForSubmission(Course $course): array
    {
        $errors = [];

        // Basic validation
        if (empty($course->getTitle())) {
            $errors[] = 'Course title is required';
        }

        if (empty($course->getShortDescription()) || strlen($course->getShortDescription()) < 20) {
            $errors[] = 'Short description must be at least 20 characters';
        }

        if (!$course->getCategory()) {
            $errors[] = 'Course category is required';
        }

        if (!$course->getLevel()) {
            $errors[] = 'Course level is required';
        }

        if ($course->getPrice() === null || $course->getPrice() < 0) {
            $errors[] = 'Valid price is required';
        }

        // Content validation
        if (empty($course->getRequirements())) {
            $errors[] = 'Course requirements are required';
        }

        if (empty($course->getLearningOutcomes())) {
            $errors[] = 'Learning outcomes are required';
        }

        if (empty($course->getTargetAudience())) {
            $errors[] = 'Target audience is required';
        }

        // Media validation
        if (!$course->getThumbnailUrl()) {
            $errors[] = 'Course thumbnail is required';
        }

        // Curriculum validation
        $totalLessons = $course->getTotalLessons();
        if ($totalLessons < 3) {
            $errors[] = 'Course must have at least 3 lessons';
        }

        if ($course->getChapters()->count() < 1) {
            $errors[] = 'Course must have at least 1 chapter';
        }

        // Duration validation
        if (!$course->getDuration() || $course->getDuration() < 0.5) {
            $errors[] = 'Course duration must be at least 30 minutes';
        }

        return $errors;
    }

    public function transitionCourseStatus(
        Course $course, 
        CourseStatus $newStatus, 
        ?string $reason = null,
        ?array $metadata = []
    ): array {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedException('User must be logged in');
        }

        $currentStatus = CourseStatus::tryFrom($course->getStatus());
        if (!$currentStatus) {
            throw new \InvalidArgumentException('Invalid current course status');
        }

        // Check transition validity
        if (!$this->canTransitionTo($course, $newStatus)) {
            throw new \InvalidArgumentException(
                sprintf('Cannot transition from %s to %s', $currentStatus->getLabel(), $newStatus->getLabel())
            );
        }

        // Check role permissions
        $requiredRole = $newStatus->getRequiredRole();
        if (!$this->security->isGranted($requiredRole)) {
            throw new AccessDeniedException(
                sprintf('User must have %s role to perform this action', $requiredRole)
            );
        }

        // Additional validation for specific transitions
        if ($newStatus === CourseStatus::IN_REVIEW) {
            $validationErrors = $this->validateCourseForSubmission($course);
            if (!empty($validationErrors)) {
                throw new \InvalidArgumentException('Course validation failed: ' . implode(', ', $validationErrors));
            }
        }

        // Create version snapshot for published courses
        if ($newStatus === CourseStatus::PUBLISHED && $currentStatus !== CourseStatus::PUBLISHED) {
            $this->createCourseVersion($course, $user);
        }

        // Perform the transition
        $oldStatus = $course->getStatus();
        $course->setStatus($newStatus->value);

        // Create audit log
        $this->createAuditLog($course, $oldStatus, $newStatus->value, $user, $reason, $metadata);

        // Save changes
        $this->entityManager->flush();

        // Dispatch event for notifications
        $this->dispatchStatusChangeEvent($course, $currentStatus, $newStatus, $user, $reason);

        return [
            'success' => true,
            'message' => sprintf('Course status changed from %s to %s', $currentStatus->getLabel(), $newStatus->getLabel()),
            'course_id' => $course->getId(),
            'old_status' => $oldStatus,
            'new_status' => $newStatus->value
        ];
    }

    private function createAuditLog(
        Course $course,
        string $fromStatus,
        string $toStatus,
        User $changedBy,
        ?string $reason = null,
        ?array $metadata = []
    ): void {
        $auditLog = new CourseAuditLog();
        $auditLog->setCourse($course);
        $auditLog->setChangedBy($changedBy);
        $auditLog->setFromStatus($fromStatus);
        $auditLog->setToStatus($toStatus);
        $auditLog->setReason($reason);
        $auditLog->setMetadata($metadata);

        // Capture request metadata
        $request = $this->requestStack->getCurrentRequest();
        if ($request) {
            $auditLog->setIpAddress($request->getClientIp());
            $auditLog->setUserAgent($request->headers->get('User-Agent'));
        }

        $this->entityManager->persist($auditLog);
    }

    private function createCourseVersion(Course $course, User $user): void
    {
        $latestVersion = $this->entityManager->getRepository(CourseVersion::class)
            ->findOneBy(['course' => $course], ['versionNumber' => 'DESC']);

        $versionNumber = $latestVersion ? $latestVersion->getVersionNumber() + 1 : 1;

        $version = new CourseVersion();
        $version->setCourse($course);
        $version->setVersionNumber($versionNumber);
        $version->setTitle($course->getTitle());
        $version->setShortDescription($course->getShortDescription());
        $version->setRequirements($course->getRequirements());
        $version->setLearningOutcomes($course->getLearningOutcomes());
        $version->setTargetAudience($course->getTargetAudience());
        $version->setPrice($course->getPrice());
        $version->setThumbnailUrl($course->getThumbnailUrl());
        $version->setVideoUrl($course->getVideoUrl());
        $version->setCreatedBy($user);
        $version->setPublishStatus(CourseStatus::PUBLISHED->value);

        // Create curriculum snapshot
        $curriculumSnapshot = [];
        foreach ($course->getChapters() as $chapter) {
            $chapterData = [
                'id' => $chapter->getId(),
                'title' => $chapter->getTitle(),
                'sort_order' => $chapter->getSortOrder(),
                'lessons' => []
            ];

            foreach ($chapter->getLessons() as $lesson) {
                $chapterData['lessons'][] = [
                    'id' => $lesson->getId(),
                    'title' => $lesson->getTitle(),
                    'type' => $lesson->getType(),
                    'duration' => $lesson->getDuration(),
                    'sort_order' => $lesson->getSortOrder()
                ];
            }

            $curriculumSnapshot[] = $chapterData;
        }
        $version->setCurriculumSnapshot($curriculumSnapshot);

        $this->entityManager->persist($version);
    }

    private function dispatchStatusChangeEvent(
        Course $course,
        CourseStatus $oldStatus,
        CourseStatus $newStatus,
        User $user,
        ?string $reason
    ): void {
        // This would dispatch an event that can be listened to by notification services
        // Implementation would depend on your event system
        $event = new CourseStatusChangeEvent($course, $oldStatus, $newStatus, $user, $reason);
        $this->eventDispatcher->dispatch($event);
    }

    public function getCourseHistory(Course $course): array
    {
        return $this->entityManager->getRepository(CourseAuditLog::class)
            ->findBy(['course' => $course], ['createdAt' => 'DESC']);
    }

    public function getCourseVersions(Course $course): array
    {
        return $this->entityManager->getRepository(CourseVersion::class)
            ->findBy(['course' => $course], ['versionNumber' => 'DESC']);
    }

    public function restoreFromVersion(Course $course, CourseVersion $version, User $user): array
    {
        if ($version->getCourse() !== $course) {
            throw new \InvalidArgumentException('Version does not belong to this course');
        }

        if (!$this->security->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Only administrators can restore course versions');
        }

        // Create audit log for restoration
        $this->createAuditLog(
            $course,
            $course->getStatus(),
            $course->getStatus(),
            $user,
            sprintf('Restored from version %d', $version->getVersionNumber()),
            ['restored_from_version' => $version->getVersionNumber()]
        );

        // Restore course data
        $course->setTitle($version->getTitle());
        $course->setShortDescription($version->getShortDescription());
        $course->setRequirements($version->getRequirements());
        $course->setLearningOutcomes($version->getLearningOutcomes());
        $course->setTargetAudience($version->getTargetAudience());
        $course->setPrice($version->getPrice());
        $course->setThumbnailUrl($version->getThumbnailUrl());
        $course->setVideoUrl($version->getVideoUrl());

        $this->entityManager->flush();

        return [
            'success' => true,
            'message' => sprintf('Course restored from version %d', $version->getVersionNumber()),
            'course_id' => $course->getId(),
            'restored_from_version' => $version->getVersionNumber()
        ];
    }
}
