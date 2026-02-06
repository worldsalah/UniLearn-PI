<?php

namespace App\Entity;

use App\Repository\BookingSlotRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BookingSlotRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_slot_freelancer_start', fields: ['freelancer', 'startAt'])]
class BookingSlot
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Student $freelancer = null;

    #[ORM\Column]
    #[Assert\NotNull]
    private ?\DateTimeImmutable $startAt = null;

    #[ORM\Column]
    #[Assert\NotNull]
    private ?\DateTimeImmutable $endAt = null;

    #[ORM\Column(options: ['default' => true])]
    private bool $isAvailable = true;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFreelancer(): ?Student
    {
        return $this->freelancer;
    }

    public function setFreelancer(?Student $freelancer): static
    {
        $this->freelancer = $freelancer;
        return $this;
    }

    public function getStartAt(): ?\DateTimeImmutable
    {
        return $this->startAt;
    }

    public function setStartAt(\DateTimeImmutable $startAt): static
    {
        $this->startAt = $startAt;
        return $this;
    }

    public function getEndAt(): ?\DateTimeImmutable
    {
        return $this->endAt;
    }

    public function setEndAt(\DateTimeImmutable $endAt): static
    {
        $this->endAt = $endAt;
        return $this;
    }

    public function isAvailable(): bool
    {
        return $this->isAvailable;
    }

    public function setIsAvailable(bool $isAvailable): static
    {
        $this->isAvailable = $isAvailable;
        return $this;
    }
}

