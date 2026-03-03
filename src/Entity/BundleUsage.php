<?php

namespace App\Entity;

use App\Repository\BundleUsageRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: BundleUsageRepository::class)]
#[ORM\Table(name: 'bundle_usage')]
class BundleUsage
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private ?string $id = null;

    public function __construct()
    {
        $this->id = Uuid::v4()->toRfc4122();
    }

    #[ORM\ManyToOne(inversedBy: 'usages', targetEntity: Bundle::class)]
    #[ORM\JoinColumn(name: 'bundle_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Bundle $bundle = null;

    #[ORM\OneToOne(inversedBy: 'bundleUsage', targetEntity: Booking::class)]
    #[ORM\JoinColumn(name: 'booking_id', referencedColumnName: 'id', nullable: false)]
    private ?Booking $booking = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $usedAt = null;

    // === Getters & Setters ===

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getBundle(): ?Bundle
    {
        return $this->bundle;
    }

    public function setBundle(?Bundle $bundle): static
    {
        $this->bundle = $bundle;
        return $this;
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

    public function getUsedAt(): ?\DateTime
    {
        return $this->usedAt;
    }

    public function setUsedAt(\DateTime $usedAt): static
    {
        $this->usedAt = $usedAt;
        return $this;
    }

    #[ORM\PrePersist]
    public function setUsedAtValue(): void
    {
        $this->usedAt = new \DateTime();
    }
}
