<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'meeting_feedback')]
#[ORM\HasLifecycleCallbacks]
class MeetingFeedback
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Booking::class)]
    #[ORM\JoinColumn(name: 'booking_id', referencedColumnName: 'id', nullable: false)]
    private ?Booking $booking = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    private ?User $user = null;

    // Overall satisfaction rating (1-5 stars)
    #[ORM\Column(type: 'integer')]
    private ?int $satisfactionRating = null;

    // Call quality rating (1-5 stars)
    #[ORM\Column(type: 'integer')]
    private ?int $callQualityRating = null;

    // Learning style rating (1-5 stars)
    #[ORM\Column(type: 'integer')]
    private ?int $learningStyleRating = null;

    // Text feedback/comments
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $comments = null;

    // User role (student or instructor)
    #[ORM\Column(type: 'string', length: 20)]
    private ?string $userRole = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTime();
    }

    // Getters and Setters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBooking(): ?Booking
    {
        return $this->booking;
    }

    public function setBooking(?Booking $booking): self
    {
        $this->booking = $booking;
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

    public function getSatisfactionRating(): ?int
    {
        return $this->satisfactionRating;
    }

    public function setSatisfactionRating(int $satisfactionRating): self
    {
        $this->satisfactionRating = $satisfactionRating;
        return $this;
    }

    public function getCallQualityRating(): ?int
    {
        return $this->callQualityRating;
    }

    public function setCallQualityRating(int $callQualityRating): self
    {
        $this->callQualityRating = $callQualityRating;
        return $this;
    }

    public function getLearningStyleRating(): ?int
    {
        return $this->learningStyleRating;
    }

    public function setLearningStyleRating(int $learningStyleRating): self
    {
        $this->learningStyleRating = $learningStyleRating;
        return $this;
    }

    public function getComments(): ?string
    {
        return $this->comments;
    }

    public function setComments(?string $comments): self
    {
        $this->comments = $comments;
        return $this;
    }

    public function getUserRole(): ?string
    {
        return $this->userRole;
    }

    public function setUserRole(string $userRole): self
    {
        $this->userRole = $userRole;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    // Helper method to calculate average rating
    public function getAverageRating(): float
    {
        $satisfaction = $this->satisfactionRating ?? 0;
        $callQuality = $this->callQualityRating ?? 0;
        $learningStyle = $this->learningStyleRating ?? 0;
        return round(($satisfaction + $callQuality + $learningStyle) / 3, 1);
    }
}
