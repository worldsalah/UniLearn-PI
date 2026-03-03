<?php

// src/Entity/Booking.php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
class Booking
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $firstName = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $lastName = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Email is required.')]
    #[Assert\Email(message: 'Please enter a valid email address.')]
    private ?string $userEmail = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $phoneNumber = null;

    #[ORM\ManyToOne(targetEntity: Session::class, inversedBy: 'bookings')]
    #[ORM\JoinColumn(name: 'session_id', referencedColumnName: 'id', nullable: true)]
    private ?Session $session = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: true)]
    private ?User $user = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTime $preferredDate = null;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $status = 'pending';

    #[ORM\Column(type: 'time', nullable: true)]
    private ?\DateTimeInterface $startTime = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $durationMinutes = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $totalPrice = null;

    #[ORM\ManyToOne(targetEntity: Bundle::class, inversedBy: 'bookings')]
    #[ORM\JoinColumn(name: 'bundle_id', referencedColumnName: 'id', nullable: true)]
    private ?Bundle $bundle = null;

    #[ORM\OneToOne(targetEntity: BundleUsage::class, mappedBy: 'booking')]
    private ?BundleUsage $bundleUsage = null;

    #[ORM\ManyToOne(targetEntity: TeacherProfile::class, inversedBy: 'bookings')]
    #[ORM\JoinColumn(name: 'teacher_id', referencedColumnName: 'id', nullable: true)]
    private ?TeacherProfile $teacher = null;

    #[ORM\OneToOne(targetEntity: TimeSlot::class, inversedBy: 'booking')]
    #[ORM\JoinColumn(name: 'time_slot_id', referencedColumnName: 'id', nullable: true)]
    private ?TimeSlot $timeSlot = null;

    #[ORM\OneToOne(targetEntity: TutoringSession::class, mappedBy: 'booking')]
    private ?TutoringSession $tutoringSession = null;

    public function getSession(): ?Session
    {
        return $this->session;
    }

    public function setSession(?Session $session): self
    {
        $this->session = $session;

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

    public function getPreferredDate(): ?\DateTime
    {
        return $this->preferredDate;
    }

    public function setPreferredDate(?\DateTime $preferredDate): self
    {
        $this->preferredDate = $preferredDate;

        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getStartTime(): ?\DateTimeInterface
    {
        return $this->startTime;
    }

    public function setStartTime(?\DateTimeInterface $startTime): self
    {
        $this->startTime = $startTime;

        return $this;
    }

    public function getDurationMinutes(): ?int
    {
        return $this->durationMinutes;
    }

    public function setDurationMinutes(?int $durationMinutes): self
    {
        $this->durationMinutes = $durationMinutes;

        return $this;
    }

    public function getTotalPrice(): ?string
    {
        return $this->totalPrice;
    }

    public function setTotalPrice(?string $totalPrice): self
    {
        $this->totalPrice = $totalPrice;

        return $this;
    }

    // -------- Getters et Setters --------

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getUserEmail(): ?string
    {
        return $this->userEmail;
    }

    public function setUserEmail(string $userEmail): self
    {
        $this->userEmail = $userEmail;

        return $this;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(string $phoneNumber): self
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }
}
