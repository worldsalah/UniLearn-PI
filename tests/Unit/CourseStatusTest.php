<?php

namespace App\Tests\Unit;

use App\Enum\CourseStatus;
use PHPUnit\Framework\TestCase;

class CourseStatusTest extends TestCase
{
    public function testStatusValues(): void
    {
        $this->assertEquals('draft', CourseStatus::DRAFT->value);
        $this->assertEquals('in_review', CourseStatus::IN_REVIEW->value);
        $this->assertEquals('published', CourseStatus::PUBLISHED->value);
        $this->assertEquals('archived', CourseStatus::ARCHIVED->value);
        $this->assertEquals('rejected', CourseStatus::REJECTED->value);
        $this->assertEquals('soft_deleted', CourseStatus::SOFT_DELETED->value);
    }

    public function testStatusLabels(): void
    {
        $this->assertEquals('Draft', CourseStatus::DRAFT->getLabel());
        $this->assertEquals('In Review', CourseStatus::IN_REVIEW->getLabel());
        $this->assertEquals('Published', CourseStatus::PUBLISHED->getLabel());
        $this->assertEquals('Archived', CourseStatus::ARCHIVED->getLabel());
        $this->assertEquals('Rejected', CourseStatus::REJECTED->getLabel());
        $this->assertEquals('Deleted', CourseStatus::SOFT_DELETED->getLabel());
    }

    public function testValidTransitions(): void
    {
        // Test DRAFT transitions
        $this->assertTrue(CourseStatus::DRAFT->canTransitionTo(CourseStatus::IN_REVIEW));
        $this->assertTrue(CourseStatus::DRAFT->canTransitionTo(CourseStatus::SOFT_DELETED));
        $this->assertFalse(CourseStatus::DRAFT->canTransitionTo(CourseStatus::PUBLISHED));
        $this->assertFalse(CourseStatus::DRAFT->canTransitionTo(CourseStatus::REJECTED));

        // Test IN_REVIEW transitions
        $this->assertTrue(CourseStatus::IN_REVIEW->canTransitionTo(CourseStatus::PUBLISHED));
        $this->assertTrue(CourseStatus::IN_REVIEW->canTransitionTo(CourseStatus::REJECTED));
        $this->assertTrue(CourseStatus::IN_REVIEW->canTransitionTo(CourseStatus::DRAFT));
        $this->assertFalse(CourseStatus::IN_REVIEW->canTransitionTo(CourseStatus::ARCHIVED));

        // Test PUBLISHED transitions
        $this->assertTrue(CourseStatus::PUBLISHED->canTransitionTo(CourseStatus::ARCHIVED));
        $this->assertTrue(CourseStatus::PUBLISHED->canTransitionTo(CourseStatus::SOFT_DELETED));
        $this->assertFalse(CourseStatus::PUBLISHED->canTransitionTo(CourseStatus::DRAFT));

        // Test REJECTED transitions
        $this->assertTrue(CourseStatus::REJECTED->canTransitionTo(CourseStatus::DRAFT));
        $this->assertTrue(CourseStatus::REJECTED->canTransitionTo(CourseStatus::SOFT_DELETED));
        $this->assertFalse(CourseStatus::REJECTED->canTransitionTo(CourseStatus::PUBLISHED));

        // Test ARCHIVED transitions
        $this->assertTrue(CourseStatus::ARCHIVED->canTransitionTo(CourseStatus::PUBLISHED));
        $this->assertTrue(CourseStatus::ARCHIVED->canTransitionTo(CourseStatus::SOFT_DELETED));
        $this->assertFalse(CourseStatus::ARCHIVED->canTransitionTo(CourseStatus::DRAFT));

        // Test SOFT_DELETED transitions
        $this->assertTrue(CourseStatus::SOFT_DELETED->canTransitionTo(CourseStatus::DRAFT));
        $this->assertFalse(CourseStatus::SOFT_DELETED->canTransitionTo(CourseStatus::PUBLISHED));
    }

    public function testEditableStatuses(): void
    {
        $this->assertTrue(CourseStatus::DRAFT->isEditable());
        $this->assertTrue(CourseStatus::REJECTED->isEditable());
        $this->assertFalse(CourseStatus::IN_REVIEW->isEditable());
        $this->assertFalse(CourseStatus::PUBLISHED->isEditable());
        $this->assertFalse(CourseStatus::ARCHIVED->isEditable());
        $this->assertFalse(CourseStatus::SOFT_DELETED->isEditable());
    }

    public function testVisibilityToStudents(): void
    {
        $this->assertFalse(CourseStatus::DRAFT->isVisibleToStudents());
        $this->assertFalse(CourseStatus::IN_REVIEW->isVisibleToStudents());
        $this->assertTrue(CourseStatus::PUBLISHED->isVisibleToStudents());
        $this->assertFalse(CourseStatus::ARCHIVED->isVisibleToStudents());
        $this->assertFalse(CourseStatus::REJECTED->isVisibleToStudents());
        $this->assertFalse(CourseStatus::SOFT_DELETED->isVisibleToStudents());
    }

    public function testRequiredRoles(): void
    {
        $this->assertEquals('ROLE_INSTRUCTOR', CourseStatus::DRAFT->getRequiredRole());
        $this->assertEquals('ROLE_INSTRUCTOR', CourseStatus::IN_REVIEW->getRequiredRole());
        $this->assertEquals('ROLE_ADMIN', CourseStatus::PUBLISHED->getRequiredRole());
        $this->assertEquals('ROLE_ADMIN', CourseStatus::ARCHIVED->getRequiredRole());
        $this->assertEquals('ROLE_ADMIN', CourseStatus::SOFT_DELETED->getRequiredRole());
        $this->assertEquals('ROLE_INSTRUCTOR', CourseStatus::REJECTED->getRequiredRole());
    }

    public function testGetAllowedTransitions(): void
    {
        $transitions = CourseStatus::getAllowedTransitions();
        
        $this->assertArrayHasKey('draft', $transitions);
        $this->assertArrayHasKey('in_review', $transitions);
        $this->assertArrayHasKey('published', $transitions);
        
        $this->assertContains('in_review', $transitions['draft']);
        $this->assertContains('soft_deleted', $transitions['draft']);
        $this->assertContains('published', $transitions['in_review']);
        $this->assertContains('rejected', $transitions['in_review']);
    }
}
