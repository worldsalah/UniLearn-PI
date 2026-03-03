<?php

namespace App\Entity;

use App\Repository\EnrollmentRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EnrollmentRepository::class)]
#[ORM\Table(name: 'enrollment')]
class Enrollment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Course::class)]
    #[ORM\JoinColumn(name: 'course_id', referencedColumnName: 'id', nullable: false)]
    private ?Course $course = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    private ?User $user = null;

    #[ORM\Column(name: 'enrolled_at', type: 'datetime')]
    private ?\DateTimeInterface $enrolledAt = null;

    #[ORM\Column(name: 'completed_at', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $completedAt = null;

    #[ORM\Column(type: 'string', length: 20, options: ['default' => 'active'])]
    private string $status = 'active';

    #[ORM\Column(type: 'float', options: ['default' => 0.0])]
    private float $progress = 0.0;

    #[ORM\Column(name: 'ip_address', type: 'string', length: 45, nullable: true)]
    private ?string $ipAddress = null;

    #[ORM\Column(name: 'user_agent', type: 'text', nullable: true)]
    private ?string $userAgent = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $placementTestTaken = false;

    #[ORM\ManyToOne(targetEntity: Lesson::class)]
    #[ORM\JoinColumn(name: 'starting_lesson_id', referencedColumnName: 'id', nullable: true)]
    private ?Lesson $startingLesson = null;

    #[ORM\ManyToOne(targetEntity: CourseTestResult::class)]
    #[ORM\JoinColumn(name: 'placement_test_result_id', referencedColumnName: 'id', nullable: true)]
    private ?CourseTestResult $placementTestResult = null;

    public function __construct()
    {
        $this->enrolledAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCourse(): ?Course
    {
        return $this->course;
    }

    public function setCourse(?Course $course): self
    {
        $this->course = $course;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getEnrolledAt(): ?\DateTimeInterface
    {
        return $this->enrolledAt;
    }

    public function setEnrolledAt(\DateTimeInterface $enrolledAt): self
    {
        $this->enrolledAt = $enrolledAt;

        return $this;
    }

    public function getCompletedAt(): ?\DateTimeInterface
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTimeInterface $completedAt): self
    {
        $this->completedAt = $completedAt;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getProgress(): float
    {
        return $this->progress;
    }

    public function setProgress(float $progress): self
    {
        $this->progress = $progress;

        return $this;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(?string $ipAddress): self
    {
        $this->ipAddress = $ipAddress;

        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): self
    {
        $this->userAgent = $userAgent;

        return $this;
    }

    public function isPlacementTestTaken(): bool
    {
        return $this->placementTestTaken;
    }

    public function setPlacementTestTaken(bool $placementTestTaken): self
    {
        $this->placementTestTaken = $placementTestTaken;

        return $this;
    }

    public function getStartingLesson(): ?Lesson
    {
        return $this->startingLesson;
    }

    public function setStartingLesson(?Lesson $startingLesson): self
    {
        $this->startingLesson = $startingLesson;

        return $this;
    }

    public function getPlacementTestResult(): ?CourseTestResult
    {
        return $this->placementTestResult;
    }

    public function setPlacementTestResult(?CourseTestResult $placementTestResult): self
    {
        $this->placementTestResult = $placementTestResult;

        return $this;
    }
}
