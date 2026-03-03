<?php

namespace App\Entity;

use App\Repository\ReviewRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ReviewRepository::class)]
#[ORM\Table(name: 'review')]
#[ORM\HasLifecycleCallbacks]
class Review
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private ?string $id = null;

    public function __construct()
    {
        $this->id = Uuid::v4()->toRfc4122();
    }

    #[ORM\OneToOne(inversedBy: 'review', targetEntity: TutoringSession::class)]
    #[ORM\JoinColumn(name: 'tutoring_session_id', referencedColumnName: 'id', nullable: false, unique: true)]
    private ?TutoringSession $tutoringSession = null;

    #[ORM\ManyToOne(inversedBy: 'reviewsGiven', targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'student_id', referencedColumnName: 'id', nullable: false)]
    private ?User $student = null;

    #[ORM\ManyToOne(inversedBy: 'reviewsReceived', targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'teacher_id', referencedColumnName: 'id', nullable: false)]
    private ?User $teacher = null;

    #[ORM\Column(type: 'smallint')]
    private ?int $rating = null; // 1-5

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $comment = null;

    #[ORM\Column(options: ['default' => true])]
    private ?bool $isPublic = true;

    #[ORM\Column(options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    // === Getters & Setters ===

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getSession(): ?TutoringSession
    {
        return $this->tutoringSession;
    }

    public function setSession(?TutoringSession $tutoringSession): static
    {
        $this->tutoringSession = $tutoringSession;
        return $this;
    }

    public function getStudent(): ?User
    {
        return $this->student;
    }

    public function setStudent(?User $student): static
    {
        $this->student = $student;
        return $this;
    }

    public function getTeacher(): ?User
    {
        return $this->teacher;
    }

    public function setTeacher(?User $teacher): static
    {
        $this->teacher = $teacher;
        return $this;
    }

    public function getRating(): ?int
    {
        return $this->rating;
    }

    public function setRating(int $rating): static
    {
        if ($rating < 1 || $rating > 5) {
            throw new \InvalidArgumentException('Rating must be between 1 and 5');
        }
        $this->rating = $rating;
        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): static
    {
        $this->comment = $comment;
        return $this;
    }

    public function isPublic(): ?bool
    {
        return $this->isPublic;
    }

    public function setIsPublic(bool $isPublic): static
    {
        $this->isPublic = $isPublic;
        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    // === Business Logic ===

    /**
     * Get rating label
     */
    public function getRatingLabel(): string
    {
        return match ($this->rating) {
            1 => 'Poor',
            2 => 'Fair',
            3 => 'Good',
            4 => 'Very Good',
            5 => 'Excellent',
            default => 'Unknown',
        };
    }

    /**
     * Get rating stars HTML
     */
    public function getRatingStars(): string
    {
        $rating = $this->rating ?? 0;
        return str_repeat('★', $rating) . str_repeat('☆', 5 - $rating);
    }

    /**
     * Check if review can be edited
     */
    public function canBeEdited(): bool
    {
        // Can edit within 24 hours of creation
        if ($this->createdAt === null) {
            return false;
        }
        $hoursSinceCreation = (time() - $this->createdAt->getTimestamp()) / 3600;
        return $hoursSinceCreation <= 24;
    }
}
