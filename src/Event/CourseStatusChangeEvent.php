<?php

namespace App\Event;

use App\Entity\Course;
use App\Entity\User;
use App\Enum\CourseStatus;
use Symfony\Contracts\EventDispatcher\Event;

class CourseStatusChangeEvent extends Event
{
    public const NAME = 'course.status.changed';

    public function __construct(
        private Course $course,
        private CourseStatus $oldStatus,
        private CourseStatus $newStatus,
        private User $changedBy,
        private ?string $reason = null
    ) {}

    public function getCourse(): Course
    {
        return $this->course;
    }

    public function getOldStatus(): CourseStatus
    {
        return $this->oldStatus;
    }

    public function getNewStatus(): CourseStatus
    {
        return $this->newStatus;
    }

    public function getChangedBy(): User
    {
        return $this->changedBy;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function toArray(): array
    {
        return [
            'course_id' => $this->course->getId(),
            'course_title' => $this->course->getTitle(),
            'old_status' => $this->oldStatus->value,
            'new_status' => $this->newStatus->value,
            'changed_by' => $this->changedBy->getId(),
            'changed_by_name' => $this->changedBy->getFullName(),
            'reason' => $this->reason,
            'instructor_id' => $this->course->getUser()?->getId(),
            'instructor_email' => $this->course->getUser()?->getEmail(),
        ];
    }
}
