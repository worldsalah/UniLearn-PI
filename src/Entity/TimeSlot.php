<?php

namespace App\Entity;

use App\Repository\TimeSlotRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: TimeSlotRepository::class)]
#[ORM\Table(name: 'time_slot')]
#[ORM\HasLifecycleCallbacks]
#[ORM\UniqueConstraint(name: 'unique_slot', columns: ['availability_id', 'date', 'start_time'])]
class TimeSlot
{
    public const STATUS_AVAILABLE = 'available';
    public const STATUS_BOOKED = 'booked';
    public const STATUS_BLOCKED = 'blocked';

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private ?string $id = null;

    public function __construct()
    {
        $this->id = Uuid::v4()->toRfc4122();
    }

    #[ORM\ManyToOne(inversedBy: 'timeSlots', targetEntity: Availability::class)]
    #[ORM\JoinColumn(name: 'availability_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Availability $availability = null;

    #[ORM\Column(type: 'date')]
    private ?\DateTime $date = null;

    #[ORM\Column(type: 'time')]
    private ?\DateTime $startTime = null;

    #[ORM\Column(type: 'time')]
    private ?\DateTime $endTime = null;

    #[ORM\Column(length: 20, options: ['default' => self::STATUS_AVAILABLE])]
    private ?string $status = self::STATUS_AVAILABLE;

    #[ORM\Column(options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\OneToOne(mappedBy: 'timeSlot', targetEntity: Booking::class)]
    private ?Booking $booking = null;

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    // === Getters & Setters ===

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getAvailability(): ?Availability
    {
        return $this->availability;
    }

    public function setAvailability(?Availability $availability): static
    {
        $this->availability = $availability;
        return $this;
    }

    public function getDate(): ?\DateTime
    {
        return $this->date;
    }

    public function setDate(\DateTime $date): static
    {
        $this->date = $date;
        return $this;
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

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        if (!in_array($status, [self::STATUS_AVAILABLE, self::STATUS_BOOKED, self::STATUS_BLOCKED], true)) {
            throw new \InvalidArgumentException('Invalid slot status');
        }
        $this->status = $status;
        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function getBooking(): ?Booking
    {
        return $this->booking;
    }

    // === Business Logic ===

    /**
     * Get full datetime for slot start
     */
    public function getStartDateTime(): \DateTime
    {
        $datetime = $this->date !== null ? clone $this->date : new \DateTime();
        $time = $this->startTime !== null ? $this->startTime->format('H:i:s') : '00:00:00';
        $datetime->setTime((int) substr($time, 0, 2), (int) substr($time, 3, 2));
        return $datetime;
    }

    /**
     * Get full datetime for slot end
     */
    public function getEndDateTime(): \DateTime
    {
        $datetime = $this->date !== null ? clone $this->date : new \DateTime();
        $time = $this->endTime !== null ? $this->endTime->format('H:i:s') : '00:00:00';
        $datetime->setTime((int) substr($time, 0, 2), (int) substr($time, 3, 2));
        return $datetime;
    }

    /**
     * Check if slot is available for booking
     */
    public function isAvailable(): bool
    {
        return $this->status === self::STATUS_AVAILABLE;
    }

    /**
     * Check if slot is in the past
     */
    public function isInPast(): bool
    {
        return $this->getStartDateTime() < new \DateTime();
    }

    /**
     * Check if slot is within cancellation window (24h)
     */
    public function isWithinCancellationWindow(): bool
    {
        $hoursUntil = ($this->getStartDateTime()->getTimestamp() - time()) / 3600;
        return $hoursUntil < 24;
    }

    /**
     * Get duration in minutes
     */
    public function getDurationMinutes(): int
    {
        $start = $this->startTime !== null ? $this->startTime->format('H:i') : '00:00';
        $end = $this->endTime !== null ? $this->endTime->format('H:i') : '00:00';
        
        $startMinutes = (int) substr($start, 0, 2) * 60 + (int) substr($start, 3, 2);
        $endMinutes = (int) substr($end, 0, 2) * 60 + (int) substr($end, 3, 2);
        
        return $endMinutes - $startMinutes;
    }

    /**
     * Book this slot
     */
    public function book(): static
    {
        if (!$this->isAvailable()) {
            throw new \LogicException('Cannot book a slot that is not available');
        }
        $this->status = self::STATUS_BOOKED;
        return $this;
    }

    /**
     * Release this slot (make available again)
     */
    public function release(): static
    {
        $this->status = self::STATUS_AVAILABLE;
        return $this;
    }

    /**
     * Block this slot (unavailable)
     */
    public function block(): static
    {
        $this->status = self::STATUS_BLOCKED;
        return $this;
    }
}
