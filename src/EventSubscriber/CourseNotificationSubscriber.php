<?php

namespace App\EventSubscriber;

use App\Event\CourseStatusChangeEvent;
use App\Enum\CourseStatus;
use App\Service\ApplicationNotificationService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CourseNotificationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ApplicationNotificationService $notificationService
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            CourseStatusChangeEvent::NAME => 'onCourseStatusChange',
        ];
    }

    public function onCourseStatusChange(CourseStatusChangeEvent $event): void
    {
        $course = $event->getCourse();
        $newStatus = $event->getNewStatus();
        $instructor = $course->getUser();

        if (!$instructor) {
            return;
        }

        switch ($newStatus) {
            case CourseStatus::IN_REVIEW:
                $this->notifyInstructorCourseSubmitted($course, $instructor);
                $this->notifyAdminsCoursePendingReview($course);
                break;

            case CourseStatus::PUBLISHED:
                $this->notifyInstructorCoursePublished($course, $instructor);
                $this->notifyEnrolledStudentsCourseLive($course);
                break;

            case CourseStatus::REJECTED:
                $this->notifyInstructorCourseRejected($course, $instructor, $event->getReason());
                break;

            case CourseStatus::ARCHIVED:
                $this->notifyInstructorCourseArchived($course, $instructor);
                $this->notifyEnrolledStudentsCourseArchived($course);
                break;
        }
    }

    private function notifyInstructorCourseSubmitted($course, $instructor): void
    {
        $title = 'Course Submitted for Review';
        $message = sprintf(
            'Your course "%s" has been successfully submitted for review. Our team will review it within 2-3 business days.',
            $course->getTitle()
        );

        $this->notificationService->sendNotification($instructor, $title, $message, [
            'course_id' => $course->getId(),
            'type' => 'course_submitted'
        ]);
    }

    private function notifyAdminsCoursePendingReview($course): void
    {
        $title = 'New Course Pending Review';
        $message = sprintf(
            'A new course "%s" by %s has been submitted for review.',
            $course->getTitle(),
            $course->getUser()?->getFullName() ?? 'Unknown Instructor'
        );

        $this->notificationService->sendAdminNotification($title, $message, [
            'course_id' => $course->getId(),
            'type' => 'course_pending_review'
        ]);
    }

    private function notifyInstructorCoursePublished($course, $instructor): void
    {
        $title = 'Course Published Successfully!';
        $message = sprintf(
            'Congratulations! Your course "%s" has been approved and is now live on the platform.',
            $course->getTitle()
        );

        $this->notificationService->sendNotification($instructor, $title, $message, [
            'course_id' => $course->getId(),
            'type' => 'course_published'
        ]);
    }

    private function notifyEnrolledStudentsCourseLive($course): void
    {
        // This would require enrollment relationship
        // Implementation depends on your enrollment system
        $title = 'New Course Available';
        $message = sprintf(
            'The course "%s" is now available for enrollment!',
            $course->getTitle()
        );

        // Send to interested students or general notifications
        $this->notificationService->sendBroadcastNotification($title, $message, [
            'course_id' => $course->getId(),
            'type' => 'course_live'
        ]);
    }

    private function notifyInstructorCourseRejected($course, $instructor, ?string $reason): void
    {
        $title = 'Course Review Update';
        $message = sprintf(
            'Your course "%s" requires some changes before it can be published. %s',
            $course->getTitle(),
            $reason ? "Reason: " . $reason : "Please review the feedback and make necessary improvements."
        );

        $this->notificationService->sendNotification($instructor, $title, $message, [
            'course_id' => $course->getId(),
            'type' => 'course_rejected',
            'reason' => $reason
        ]);
    }

    private function notifyInstructorCourseArchived($course, $instructor): void
    {
        $title = 'Course Archived';
        $message = sprintf(
            'Your course "%s" has been archived. It is no longer visible to students but can be restored if needed.',
            $course->getTitle()
        );

        $this->notificationService->sendNotification($instructor, $title, $message, [
            'course_id' => $course->getId(),
            'type' => 'course_archived'
        ]);
    }

    private function notifyEnrolledStudentsCourseArchived($course): void
    {
        // Notify enrolled students that the course is archived
        // Implementation depends on your enrollment system
        $title = 'Course Update';
        $message = sprintf(
            'The course "%s" has been archived. You will still have access to your completed materials.',
            $course->getTitle()
        );

        $this->notificationService->sendBroadcastNotification($title, $message, [
            'course_id' => $course->getId(),
            'type' => 'course_archived_students'
        ]);
    }
}
