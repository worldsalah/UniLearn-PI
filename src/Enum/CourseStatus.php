<?php

namespace App\Enum;

enum CourseStatus: string
{
    case DRAFT = 'draft';
    case IN_REVIEW = 'in_review';
    case PUBLISHED = 'published';
    case ARCHIVED = 'archived';
    case REJECTED = 'rejected';
    case SOFT_DELETED = 'soft_deleted';

    public function getLabel(): string
    {
        return match($this) {
            self::DRAFT => 'Draft',
            self::IN_REVIEW => 'In Review',
            self::PUBLISHED => 'Published',
            self::ARCHIVED => 'Archived',
            self::REJECTED => 'Rejected',
            self::SOFT_DELETED => 'Deleted',
        };
    }

    public function getDescription(): string
    {
        return match($this) {
            self::DRAFT => 'Course is being created and is not visible to students',
            self::IN_REVIEW => 'Course is submitted for admin review',
            self::PUBLISHED => 'Course is live and available to students',
            self::ARCHIVED => 'Course is no longer active but preserved',
            self::REJECTED => 'Course was rejected during review',
            self::SOFT_DELETED => 'Course is deleted but can be restored',
        };
    }

    public function isEditable(): bool
    {
        return match($this) {
            self::DRAFT, self::REJECTED => true,
            self::IN_REVIEW, self::PUBLISHED, self::ARCHIVED, self::SOFT_DELETED => false,
        };
    }

    public function isVisibleToStudents(): bool
    {
        return match($this) {
            self::PUBLISHED => true,
            self::DRAFT, self::IN_REVIEW, self::REJECTED, self::ARCHIVED, self::SOFT_DELETED => false,
        };
    }

    public function canTransitionTo(self $newStatus): bool
    {
        return match($this) {
            self::DRAFT => in_array($newStatus, [self::IN_REVIEW, self::SOFT_DELETED]),
            self::IN_REVIEW => in_array($newStatus, [self::PUBLISHED, self::REJECTED, self::DRAFT]),
            self::PUBLISHED => in_array($newStatus, [self::ARCHIVED, self::SOFT_DELETED]),
            self::REJECTED => in_array($newStatus, [self::DRAFT, self::SOFT_DELETED]),
            self::ARCHIVED => in_array($newStatus, [self::PUBLISHED, self::SOFT_DELETED]),
            self::SOFT_DELETED => in_array($newStatus, [self::DRAFT]),
        };
    }

    public function getRequiredRole(): string
    {
        return match($this) {
            self::DRAFT, self::REJECTED => 'ROLE_INSTRUCTOR',
            self::IN_REVIEW => 'ROLE_INSTRUCTOR',
            self::PUBLISHED, self::ARCHIVED, self::SOFT_DELETED => 'ROLE_ADMIN',
        };
    }

    public static function getAllowedTransitions(): array
    {
        return [
            self::DRAFT->value => [self::IN_REVIEW->value, self::SOFT_DELETED->value],
            self::IN_REVIEW->value => [self::PUBLISHED->value, self::REJECTED->value, self::DRAFT->value],
            self::PUBLISHED->value => [self::ARCHIVED->value, self::SOFT_DELETED->value],
            self::REJECTED->value => [self::DRAFT->value, self::SOFT_DELETED->value],
            self::ARCHIVED->value => [self::PUBLISHED->value, self::SOFT_DELETED->value],
            self::SOFT_DELETED->value => [self::DRAFT->value],
        ];
    }
}
