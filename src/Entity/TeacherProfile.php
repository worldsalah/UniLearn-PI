<?php

namespace App\Entity;

use App\Repository\TeacherProfileRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: TeacherProfileRepository::class)]
#[ORM\Table(name: 'teacher_profile')]
#[ORM\HasLifecycleCallbacks]
class TeacherProfile
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private ?string $id = null;

    public function __construct()
    {
        $this->id = Uuid::v4()->toRfc4122();
        $this->availabilities = new ArrayCollection();
        $this->bookings = new ArrayCollection();
    }

    #[ORM\OneToOne(inversedBy: 'teacherProfile', targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    private ?User $user = null;

    #[ORM\Column(type: 'json')]
    private array $subjects = [];

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private ?string $hourlyRate = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $bio = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $education = null;

    #[ORM\Column(options: ['default' => 0])]
    private ?int $experienceYears = 0;

    #[ORM\Column(type: 'decimal', precision: 3, scale: 2)]
    private ?string $ratingAvg = '0.00';

    #[ORM\Column(options: ['default' => 0])]
    private ?int $reviewCount = 0;

    #[ORM\Column(options: ['default' => false])]
    private ?bool $isVerified = false;

    #[ORM\Column(options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\OneToMany(mappedBy: 'teacher', targetEntity: Availability::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $availabilities;

    #[ORM\OneToMany(mappedBy: 'teacher', targetEntity: Booking::class)]
    private Collection $bookings;

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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getSubjects(): array
    {
        return $this->subjects;
    }

    public function setSubjects(array $subjects): static
    {
        $this->subjects = $subjects;
        return $this;
    }

    public function addSubject(string $subject): static
    {
        if (!in_array($subject, $this->subjects, true)) {
            $this->subjects[] = $subject;
        }
        return $this;
    }

    public function removeSubject(string $subject): static
    {
        $this->subjects = array_values(array_diff($this->subjects, [$subject]));
        return $this;
    }

    public function getHourlyRate(): ?string
    {
        return $this->hourlyRate;
    }

    public function setHourlyRate(string $hourlyRate): static
    {
        $this->hourlyRate = $hourlyRate;
        return $this;
    }

    public function getHourlyRateFloat(): float
    {
        return (float) $this->hourlyRate;
    }

    public function getBio(): ?string
    {
        return $this->bio;
    }

    public function setBio(?string $bio): static
    {
        $this->bio = $bio;
        return $this;
    }

    public function getEducation(): ?string
    {
        return $this->education;
    }

    public function setEducation(?string $education): static
    {
        $this->education = $education;
        return $this;
    }

    public function getExperienceYears(): ?int
    {
        return $this->experienceYears;
    }

    public function setExperienceYears(int $experienceYears): static
    {
        $this->experienceYears = $experienceYears;
        return $this;
    }

    public function getRatingAvg(): ?string
    {
        return $this->ratingAvg;
    }

    public function getRatingAvgFloat(): float
    {
        return (float) $this->ratingAvg;
    }

    public function setRatingAvg(string $ratingAvg): static
    {
        $this->ratingAvg = $ratingAvg;
        return $this;
    }

    public function getReviewCount(): ?int
    {
        return $this->reviewCount;
    }

    public function setReviewCount(int $reviewCount): static
    {
        $this->reviewCount = $reviewCount;
        return $this;
    }

    public function incrementReviewCount(): static
    {
        $this->reviewCount = ($this->reviewCount ?? 0) + 1;
        return $this;
    }

    public function isVerified(): ?bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;
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

    /**
     * @return Collection<int, Availability>
     */
    public function getAvailabilities(): Collection
    {
        return $this->availabilities;
    }

    /**
     * @return Collection<int, Booking>
     */
    public function getBookings(): Collection
    {
        return $this->bookings;
    }

    // === Business Logic ===

    /**
     * Update average rating based on new review
     */
    public function updateRating(int $newRating): static
    {
        $currentAvg = $this->getRatingAvgFloat();
        $count = $this->reviewCount ?? 0;
        
        // Calculate new average
        $totalPoints = $currentAvg * $count + $newRating;
        $newCount = $count + 1;
        $newAvg = round($totalPoints / $newCount, 2);
        
        $this->ratingAvg = (string) $newAvg;
        $this->reviewCount = $newCount;
        
        return $this;
    }

    /**
     * Recalculate average after review update
     */
    public function recalculateRating(float $oldRating, float $newRating): static
    {
        $currentAvg = $this->getRatingAvgFloat();
        $count = $this->reviewCount ?? 1;
        
        $totalPoints = ($currentAvg * $count) - $oldRating + $newRating;
        $newAvg = round($totalPoints / $count, 2);
        
        $this->ratingAvg = (string) $newAvg;
        
        return $this;
    }

    /**
     * Check if teacher teaches a specific subject
     */
    public function teachesSubject(string $subject): bool
    {
        return in_array(strtolower($subject), array_map('strtolower', $this->subjects), true);
    }
}
