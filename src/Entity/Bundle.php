<?php

namespace App\Entity;

use App\Enum\BundleType;
use App\Repository\BundleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: BundleRepository::class)]
#[ORM\Table(name: 'bundle')]
#[ORM\HasLifecycleCallbacks]
class Bundle
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_EXHAUSTED = 'exhausted';
    public const STATUS_EXPIRED = 'expired';

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private ?string $id = null;

    public function __construct()
    {
        $this->id = Uuid::v4()->toRfc4122();
        $this->usages = new ArrayCollection();
        $this->bookings = new ArrayCollection();
    }

    #[ORM\ManyToOne(inversedBy: 'bundles', targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'student_id', referencedColumnName: 'id', nullable: false)]
    private ?User $student = null;

    #[ORM\ManyToOne(targetEntity: TeacherProfile::class)]
    #[ORM\JoinColumn(name: 'teacher_id', referencedColumnName: 'id', nullable: true)]
    private ?TeacherProfile $teacher = null;

    #[ORM\Column(type: 'string', length: 20, enumType: BundleType::class)]
    private ?BundleType $type = null;

    #[ORM\Column]
    private ?int $sessionsTotal = null;

    #[ORM\Column(options: ['default' => 0])]
    private ?int $sessionsUsed = 0;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private ?string $price = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $expiresAt = null;

    #[ORM\Column(length: 20, options: ['default' => self::STATUS_ACTIVE])]
    private ?string $status = self::STATUS_ACTIVE;

    #[ORM\Column(options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeImmutable $purchasedAt = null;

    #[ORM\Column(options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\OneToMany(mappedBy: 'bundle', targetEntity: BundleUsage::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $usages;

    #[ORM\OneToMany(mappedBy: 'bundle', targetEntity: Booking::class)]
    private Collection $bookings;

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->purchasedAt = new \DateTimeImmutable();
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

    public function getStudent(): ?User
    {
        return $this->student;
    }

    public function setStudent(?User $student): static
    {
        $this->student = $student;
        return $this;
    }

    public function getTeacher(): ?TeacherProfile
    {
        return $this->teacher;
    }

    public function setTeacher(?TeacherProfile $teacher): static
    {
        $this->teacher = $teacher;
        return $this;
    }

    public function getType(): ?BundleType
    {
        return $this->type;
    }

    public function setType(BundleType $type): static
    {
        $this->type = $type;
        $this->sessionsTotal = $type->sessions();
        return $this;
    }

    public function getSessionsTotal(): ?int
    {
        return $this->sessionsTotal;
    }

    public function getSessionsUsed(): ?int
    {
        return $this->sessionsUsed;
    }

    public function getSessionsRemaining(): int
    {
        return ($this->sessionsTotal ?? 0) - ($this->sessionsUsed ?? 0);
    }

    public function incrementUsed(): static
    {
        $this->sessionsUsed = ($this->sessionsUsed ?? 0) + 1;
        
        if ($this->getSessionsRemaining() <= 0) {
            $this->status = self::STATUS_EXHAUSTED;
        }
        
        return $this;
    }

    public function decrementUsed(): static
    {
        if (($this->sessionsUsed ?? 0) > 0) {
            $this->sessionsUsed = ($this->sessionsUsed ?? 0) - 1;
            if ($this->status === self::STATUS_EXHAUSTED) {
                $this->status = self::STATUS_ACTIVE;
            }
        }
        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): static
    {
        $this->price = $price;
        return $this;
    }

    public function getPriceFloat(): float
    {
        return (float) $this->price;
    }

    public function getExpiresAt(): ?\DateTime
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(?\DateTime $expiresAt): static
    {
        $this->expiresAt = $expiresAt;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        if (!in_array($status, [self::STATUS_ACTIVE, self::STATUS_EXHAUSTED, self::STATUS_EXPIRED], true)) {
            throw new \InvalidArgumentException('Invalid bundle status');
        }
        $this->status = $status;
        return $this;
    }

    public function getPurchasedAt(): ?\DateTime
    {
        return $this->purchasedAt;
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
     * @return Collection<int, BundleUsage>
     */
    public function getUsages(): Collection
    {
        return $this->usages;
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
     * Check if bundle can be used
     */
    public function canUse(): bool
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }

        if ($this->getSessionsRemaining() <= 0) {
            return false;
        }

        if ($this->expiresAt !== null && $this->expiresAt < new \DateTime()) {
            $this->status = self::STATUS_EXPIRED;
            return false;
        }

        return true;
    }

    /**
     * Check if bundle is expired
     */
    public function isExpired(): bool
    {
        return $this->expiresAt !== null && $this->expiresAt < new \DateTime();
    }

    /**
     * Check if bundle is exhausted
     */
    public function isExhausted(): bool
    {
        return $this->getSessionsRemaining() <= 0;
    }

    /**
     * Check if bundle is active
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE && !$this->isExpired() && !$this->isExhausted();
    }

    /**
     * Get usage percentage
     */
    public function getUsagePercentage(): float
    {
        if ($this->sessionsTotal === 0) {
            return 0.0;
        }
        $sessionsUsed = $this->sessionsUsed ?? 0;
        return round(((float) $sessionsUsed / (float) $this->sessionsTotal) * 100, 1);
    }
}
