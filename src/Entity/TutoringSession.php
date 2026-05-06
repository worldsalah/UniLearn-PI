<?php

namespace App\Entity;

use App\Repository\TutoringSessionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TutoringSessionRepository::class)]
#[ORM\Table(name: 'tutoring_session')]
#[ORM\HasLifecycleCallbacks]
class TutoringSession
{
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_NO_SHOW = 'no_show';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    public function __construct()
    {
    }

    #[ORM\OneToOne(inversedBy: 'tutoringSession', targetEntity: Booking::class)]
    #[ORM\JoinColumn(name: 'booking_id', referencedColumnName: 'id', nullable: false, unique: true)]
    private ?Booking $booking = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $startedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $endedAt = null;

    #[ORM\Column(options: ['default' => 0])]
    private ?int $actualDuration = 0; // in minutes

    #[ORM\Column(length: 20, options: ['default' => self::STATUS_SCHEDULED])]
    private ?string $status = self::STATUS_SCHEDULED;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $recordingUrl = null;

    #[ORM\Column(options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\OneToOne(mappedBy: 'tutoringSession', targetEntity: Review::class, cascade: ['persist', 'remove'])]
    private ?Review $review = null;

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    // === Getters & Setters ===

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBooking(): ?Booking
    {
        return $this->booking;
    }

    public function setBooking(?Booking $booking): static
    {
        $this->booking = $booking;
        return $this;
    }

    public function getStartedAt(): ?\DateTime
    {
        return $this->startedAt;
    }

    public function setStartedAt(?\DateTime $startedAt): static
    {
        $this->startedAt = $startedAt;
        return $this;
    }

    public function getEndedAt(): ?\DateTime
    {
        return $this->endedAt;
    }

    public function setEndedAt(?\DateTime $endedAt): static
    {
        $this->endedAt = $endedAt;
        
        // Calculate actual duration
        if ($this->startedAt !== null && $this->endedAt !== null) {
            $this->actualDuration = (int) (($this->endedAt->getTimestamp() - $this->startedAt->getTimestamp()) / 60);
        }
        
        return $this;
    }

    public function getActualDuration(): ?int
    {
        return $this->actualDuration;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        if (!in_array($status, [self::STATUS_SCHEDULED, self::STATUS_IN_PROGRESS, self::STATUS_COMPLETED, self::STATUS_NO_SHOW], true)) {
            throw new \InvalidArgumentException('Invalid session status');
        }
        $this->status = $status;
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;
        return $this;
    }

    public function getRecordingUrl(): ?string
    {
        return $this->recordingUrl;
    }

    public function setRecordingUrl(?string $recordingUrl): static
    {
        $this->recordingUrl = $recordingUrl;
        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function getReview(): ?Review
    {
        return $this->review;
    }

    // === Business Logic ===

    /**
     * Start the session
     */
    public function start(): static
    {
        if ($this->status !== self::STATUS_SCHEDULED) {
            throw new \LogicException('Only scheduled sessions can be started');
        }
        
        $this->status = self::STATUS_IN_PROGRESS;
        $this->startedAt = new \DateTime();
        
        return $this;
    }

    /**
     * Complete the session
     */
    public function complete(): static
    {
        if (!in_array($this->status, [self::STATUS_SCHEDULED, self::STATUS_IN_PROGRESS], true)) {
            throw new \LogicException('Session cannot be completed in its current state');
        }
        
        $this->status = self::STATUS_COMPLETED;
        $this->endedAt = new \DateTime();
        
        if ($this->startedAt !== null) {
            $this->actualDuration = (int) (($this->endedAt->getTimestamp() - $this->startedAt->getTimestamp()) / 60);
        }
        
        return $this;
    }

    /**
     * Mark as no-show
     */
    public function markNoShow(): static
    {
        if ($this->status !== self::STATUS_SCHEDULED) {
            throw new \LogicException('Only scheduled sessions can be marked as no-show');
        }
        
        $this->status = self::STATUS_NO_SHOW;
        
        return $this;
    }

    /**
     * Check if session can be reviewed
     */
    public function canBeReviewed(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if session has been reviewed
     */
    public function hasReview(): bool
    {
        return $this->review !== null;
    }

    /**
     * Get scheduled duration from booking
     */
    public function getScheduledDuration(): int
    {
        return $this->booking?->getTimeSlot()?->getDurationMinutes() ?? 0;
    }

    /**
     * Get student
     */
    public function getStudent(): ?User
    {
        return $this->booking?->getStudent();
    }

    /**
     * Get teacher
     */
    public function getTeacher(): ?User
    {
        return $this->booking?->getTeacher();
    }
}
