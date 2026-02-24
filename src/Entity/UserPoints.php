<?php

namespace App\Entity;

use App\Repository\UserPointsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserPointsRepository::class)]
#[ORM\Table(name: 'user_points')]
class UserPoints
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, unique: true)]
    private ?User $user = null;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $totalPoints = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $currentLevelPoints = 0;

    #[ORM\ManyToOne(targetEntity: UserLevel::class)]
    #[ORM\JoinColumn(name: 'current_level_id', referencedColumnName: 'id')]
    private ?UserLevel $currentLevel = null;

    #[ORM\Column(name: 'last_updated', type: 'datetime')]
    private ?\DateTimeInterface $lastUpdated = null;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $rankPosition = 0;

    public function __construct()
    {
        $this->lastUpdated = new \DateTimeImmutable();
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

    public function getTotalPoints(): ?int
    {
        return $this->totalPoints;
    }

    public function setTotalPoints(int $totalPoints): self
    {
        $this->totalPoints = $totalPoints;

        return $this;
    }

    public function getCurrentLevelPoints(): ?int
    {
        return $this->currentLevelPoints;
    }

    public function setCurrentLevelPoints(int $currentLevelPoints): self
    {
        $this->currentLevelPoints = $currentLevelPoints;

        return $this;
    }

    public function getCurrentLevel(): ?UserLevel
    {
        return $this->currentLevel;
    }

    public function setCurrentLevel(?UserLevel $currentLevel): self
    {
        $this->currentLevel = $currentLevel;

        return $this;
    }

    public function getLastUpdated(): ?\DateTimeInterface
    {
        return $this->lastUpdated;
    }

    public function setLastUpdated(\DateTimeInterface $lastUpdated): self
    {
        $this->lastUpdated = $lastUpdated;

        return $this;
    }

    public function getRankPosition(): ?int
    {
        return $this->rankPosition;
    }

    public function setRankPosition(int $rankPosition): self
    {
        $this->rankPosition = $rankPosition;

        return $this;
    }

    public function addPoints(int $points): self
    {
        $this->totalPoints += $points;
        $this->currentLevelPoints += $points;
        $this->lastUpdated = new \DateTimeImmutable();

        return $this;
    }

    public function getProgressToNextLevel(): float
    {
        if (!$this->currentLevel) {
            return 0;
        }

        $levelRange = $this->currentLevel->getMaxXp() - $this->currentLevel->getMinXp();
        if ($levelRange <= 0) {
            return 100;
        }

        $progress = ($this->currentLevelPoints - $this->currentLevel->getMinXp()) / $levelRange * 100;
        return min(100, max(0, $progress));
    }

    public function getPointsToNextLevel(): int
    {
        if (!$this->currentLevel) {
            return 0;
        }

        return $this->currentLevel->getMaxXp() - $this->currentLevelPoints;
    }

    public function __toString(): string
    {
        return $this->user ? $this->user->getFullName() . ' - ' . $this->totalPoints . ' XP' : '';
    }
}
