<?php

namespace App\Entity;

use App\Repository\UserBadgeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserBadgeRepository::class)]
#[ORM\Table(name: 'user_badge')]
class UserBadge
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'userBadges')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Badge::class, inversedBy: 'userBadges')]
    #[ORM\JoinColumn(name: 'badge_id', referencedColumnName: 'id', nullable: false)]
    private ?Badge $badge = null;

    #[ORM\Column(name: 'earned_at', type: 'datetime')]
    private ?\DateTimeInterface $earnedAt = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $earnedReason = null;

    #[ORM\Column(type: 'integer', options: ['default' => 1])]
    private int $progress = 1;

    public function __construct()
    {
        $this->earnedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getBadge(): ?Badge
    {
        return $this->badge;
    }

    public function setBadge(?Badge $badge): self
    {
        $this->badge = $badge;

        return $this;
    }

    public function getEarnedAt(): ?\DateTimeInterface
    {
        return $this->earnedAt;
    }

    public function setEarnedAt(\DateTimeInterface $earnedAt): self
    {
        $this->earnedAt = $earnedAt;

        return $this;
    }

    public function getEarnedReason(): ?string
    {
        return $this->earnedReason;
    }

    public function setEarnedReason(?string $earnedReason): self
    {
        $this->earnedReason = $earnedReason;

        return $this;
    }

    public function getProgress(): ?int
    {
        return $this->progress;
    }

    public function setProgress(int $progress): self
    {
        $this->progress = $progress;

        return $this;
    }

    public function __toString(): string
    {
        return $this->badge ? $this->badge->getName() : '';
    }
}
