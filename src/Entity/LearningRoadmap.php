<?php

namespace App\Entity;

use App\Repository\LearningRoadmapRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LearningRoadmapRepository::class)]
class LearningRoadmap
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 255)]
    private ?string $learningGoal = null;

    #[ORM\Column(length: 50)]
    private ?string $skillLevel = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $timeCommitment = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $learningStyles = [];

    #[ORM\Column(type: Types::JSON)]
    private array $roadmapContent = [];

    #[ORM\Column]
    private ?\DateTime $generatedAt = null;

    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\Column]
    private ?\DateTime $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
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
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getLearningGoal(): ?string
    {
        return $this->learningGoal;
    }

    public function setLearningGoal(string $learningGoal): static
    {
        $this->learningGoal = $learningGoal;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getSkillLevel(): ?string
    {
        return $this->skillLevel;
    }

    public function setSkillLevel(string $skillLevel): static
    {
        $this->skillLevel = $skillLevel;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getTimeCommitment(): ?string
    {
        return $this->timeCommitment;
    }

    public function setTimeCommitment(?string $timeCommitment): static
    {
        $this->timeCommitment = $timeCommitment;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getLearningStyles(): ?array
    {
        return $this->learningStyles;
    }

    public function setLearningStyles(?array $learningStyles): static
    {
        $this->learningStyles = $learningStyles;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getRoadmapContent(): array
    {
        return $this->roadmapContent;
    }

    public function setRoadmapContent(array $roadmapContent): static
    {
        $this->roadmapContent = $roadmapContent;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getGeneratedAt(): ?\DateTime
    {
        return $this->generatedAt;
    }

    public function setGeneratedAt(\DateTime $generatedAt): static
    {
        $this->generatedAt = $generatedAt;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function isIsActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTime $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    /**
     * Get a readable title for the roadmap
     */
    public function getTitle(): string
    {
        return sprintf('Learning Roadmap: %s (%s)', $this->learningGoal, $this->skillLevel);
    }

    /**
     * Get the duration in weeks based on time commitment
     */
    public function getEstimatedWeeks(): int
    {
        return match($this->timeCommitment) {
            '1-2' => 4,
            '3-5' => 8,
            '6-10' => 12,
            '10+' => 16,
            default => 8
        };
    }

    /**
     * Check if roadmap was created recently (within last 24 hours)
     */
    public function isRecent(): bool
    {
        $now = new \DateTime();
        $interval = $now->diff($this->createdAt);
        return $interval->days < 1;
    }
}
