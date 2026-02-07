<?php

namespace App\Entity;

use App\Repository\TrustScoreRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TrustScoreRepository::class)]
class TrustScore
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'trustScore', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Student $seller = null;

    #[ORM\Column(type: Types::FLOAT)]
    private ?float $overallScore = 0.0;

    #[ORM\Column(type: Types::FLOAT)]
    private ?float $behaviorScore = 0.0;

    #[ORM\Column(type: Types::FLOAT)]
    private ?float $contentScore = 0.0;

    #[ORM\Column(type: Types::FLOAT)]
    private ?float $pricingScore = 0.0;

    #[ORM\Column(type: Types::FLOAT)]
    private ?float $reputationScore = 0.0;

    #[ORM\Column(type: Types::JSON)]
    private array $scoreBreakdown = [];

    #[ORM\Column(type: Types::JSON)]
    private array $historicalTrend = [];

    #[ORM\Column]
    private ?\DateTimeImmutable $lastUpdated = null;

    #[ORM\Column(length: 20)]
    private ?string $riskLevel = 'low'; // low, medium, high

    public function __construct()
    {
        $this->lastUpdated = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSeller(): ?Student
    {
        return $this->seller;
    }

    public function setSeller(Student $seller): static
    {
        $this->seller = $seller;
        return $this;
    }

    public function getOverallScore(): ?float
    {
        return $this->overallScore;
    }

    public function setOverallScore(float $overallScore): static
    {
        $this->overallScore = $overallScore;
        return $this;
    }

    public function getBehaviorScore(): ?float
    {
        return $this->behaviorScore;
    }

    public function setBehaviorScore(float $behaviorScore): static
    {
        $this->behaviorScore = $behaviorScore;
        return $this;
    }

    public function getContentScore(): ?float
    {
        return $this->contentScore;
    }

    public function setContentScore(float $contentScore): static
    {
        $this->contentScore = $contentScore;
        return $this;
    }

    public function getPricingScore(): ?float
    {
        return $this->pricingScore;
    }

    public function setPricingScore(float $pricingScore): static
    {
        $this->pricingScore = $pricingScore;
        return $this;
    }

    public function getReputationScore(): ?float
    {
        return $this->reputationScore;
    }

    public function setReputationScore(float $reputationScore): static
    {
        $this->reputationScore = $reputationScore;
        return $this;
    }

    public function getScoreBreakdown(): array
    {
        return $this->scoreBreakdown;
    }

    public function setScoreBreakdown(array $scoreBreakdown): static
    {
        $this->scoreBreakdown = $scoreBreakdown;
        return $this;
    }

    public function getHistoricalTrend(): array
    {
        return $this->historicalTrend;
    }

    public function setHistoricalTrend(array $historicalTrend): static
    {
        $this->historicalTrend = $historicalTrend;
        return $this;
    }

    public function addHistoricalDataPoint(float $score): static
    {
        $this->historicalTrend[] = [
            'score' => $score,
            'timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ];
        
        // Keep only last 30 data points
        if (count($this->historicalTrend) > 30) {
            array_shift($this->historicalTrend);
        }
        
        return $this;
    }

    public function getLastUpdated(): ?\DateTimeImmutable
    {
        return $this->lastUpdated;
    }

    public function setLastUpdated(\DateTimeImmutable $lastUpdated): static
    {
        $this->lastUpdated = $lastUpdated;
        return $this;
    }

    public function getRiskLevel(): ?string
    {
        return $this->riskLevel;
    }

    public function setRiskLevel(string $riskLevel): static
    {
        $this->riskLevel = $riskLevel;
        return $this;
    }

    public function calculateOverallScore(): void
    {
        // Weighted average of component scores
        $weights = [
            'behavior' => 0.30,
            'content' => 0.25,
            'pricing' => 0.20,
            'reputation' => 0.25,
        ];

        $this->overallScore = (
            $this->behaviorScore * $weights['behavior'] +
            $this->contentScore * $weights['content'] +
            $this->pricingScore * $weights['pricing'] +
            $this->reputationScore * $weights['reputation']
        );

        // Determine risk level based on overall score
        if ($this->overallScore >= 75) {
            $this->riskLevel = 'low';
        } elseif ($this->overallScore >= 50) {
            $this->riskLevel = 'medium';
        } else {
            $this->riskLevel = 'high';
        }

        $this->lastUpdated = new \DateTimeImmutable();
        $this->addHistoricalDataPoint($this->overallScore);
    }
}
