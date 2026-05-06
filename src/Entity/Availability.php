<?php

namespace App\Entity;

use App\Repository\AvailabilityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AvailabilityRepository::class)]
#[ORM\Table(name: 'availability')]
#[ORM\HasLifecycleCallbacks]
#[ORM\UniqueConstraint(name: 'unique_availability', columns: ['teacher_id', 'day_of_week', 'start_time'])]
class Availability
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    public function __construct()
    {
        $this->timeSlots = new ArrayCollection();
    }

    #[ORM\ManyToOne(inversedBy: 'availabilities', targetEntity: TeacherProfile::class)]
    #[ORM\JoinColumn(name: 'teacher_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?TeacherProfile $teacher = null;

    #[ORM\Column(type: 'smallint')]
    private ?int $dayOfWeek = null; // 0=Monday, 6=Sunday

    #[ORM\Column(type: 'time')]
    private ?\DateTime $startTime = null;

    #[ORM\Column(type: 'time')]
    private ?\DateTime $endTime = null;

    #[ORM\Column(options: ['default' => true])]
    private ?bool $isActive = true;

    #[ORM\Column(options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\OneToMany(mappedBy: 'availability', targetEntity: TimeSlot::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $timeSlots;

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

    public function getId(): ?int
    {
        return $this->id;
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

    public function getDayOfWeek(): ?int
    {
        return $this->dayOfWeek;
    }

    public function setDayOfWeek(int $dayOfWeek): static
    {
        if ($dayOfWeek < 0 || $dayOfWeek > 6) {
            throw new \InvalidArgumentException('Day of week must be between 0 (Monday) and 6 (Sunday)');
        }
        $this->dayOfWeek = $dayOfWeek;
        return $this;
    }

    public function getDayName(): string
    {
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        return $days[$this->dayOfWeek] ?? 'Unknown';
    }

    public function getStartTime(): ?\DateTime
    {
        return $this->startTime;
    }

    public function setStartTime(\DateTime $startTime): static
    {
        $this->startTime = $startTime;
        return $this;
    }

    public function getEndTime(): ?\DateTime
    {
        return $this->endTime;
    }

    public function setEndTime(\DateTime $endTime): static
    {
        $this->endTime = $endTime;
        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
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
     * @return Collection<int, TimeSlot>
     */
    public function getTimeSlots(): Collection
    {
        return $this->timeSlots;
    }

    // === Business Logic ===

    /**
     * Get duration in minutes
     */
    public function getDurationMinutes(): int
    {
        $start = $this->startTime !== null ? $this->startTime->format('H:i:s') : '00:00:00';
        $end = $this->endTime !== null ? $this->endTime->format('H:i:s') : '00:00:00';
        
        $startMinutes = (int) substr($start, 0, 2) * 60 + (int) substr($start, 3, 2);
        $endMinutes = (int) substr($end, 0, 2) * 60 + (int) substr($end, 3, 2);
        
        return $endMinutes - $startMinutes;
    }

    /**
     * Check if time range is valid
     */
    public function isValidTimeRange(): bool
    {
        return $this->startTime < $this->endTime;
    }

    /**
     * Check if this availability overlaps with another
     */
    public function overlapsWith(Availability $other): bool
    {
        if ($this->dayOfWeek !== $other->dayOfWeek) {
            return false;
        }

        $thisStart = $this->startTime !== null ? $this->startTime->format('H:i') : '00:00';
        $thisEnd = $this->endTime !== null ? $this->endTime->format('H:i') : '23:59';
        $otherStart = $other->startTime !== null ? $other->startTime->format('H:i') : '00:00';
        $otherEnd = $other->endTime !== null ? $other->endTime->format('H:i') : '23:59';

        return $thisStart < $otherEnd && $thisEnd > $otherStart;
    }
}
