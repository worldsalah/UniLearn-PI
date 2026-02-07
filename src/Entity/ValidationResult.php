<?php

namespace App\Entity;

use App\Repository\ValidationResultRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ValidationResultRepository::class)]
class ValidationResult
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Student $seller = null;

    #[ORM\ManyToOne]
    private ?Product $product = null;

    #[ORM\Column(length: 50)]
    private ?string $validationType = null; // seller, product, transaction

    #[ORM\Column(type: Types::FLOAT)]
    private ?float $overallScore = 0.0;

    #[ORM\Column(length: 20)]
    private ?string $riskLevel = 'low';

    #[ORM\Column(type: Types::JSON)]
    private array $componentScores = [];

    #[ORM\Column(type: Types::JSON)]
    private array $findings = [];

    #[ORM\Column(type: Types::JSON)]
    private array $improvementSuggestions = [];

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $passed = true;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSeller(): ?Student
    {
        return $this->seller;
    }

    public function setSeller(?Student $seller): static
    {
        $this->seller = $seller;
        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;
        return $this;
    }

    public function getValidationType(): ?string
    {
        return $this->validationType;
    }

    public function setValidationType(string $validationType): static
    {
        $this->validationType = $validationType;
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

    public function getRiskLevel(): ?string
    {
        return $this->riskLevel;
    }

    public function setRiskLevel(string $riskLevel): static
    {
        $this->riskLevel = $riskLevel;
        return $this;
    }

    public function getComponentScores(): array
    {
        return $this->componentScores;
    }

    public function setComponentScores(array $componentScores): static
    {
        $this->componentScores = $componentScores;
        return $this;
    }

    public function getFindings(): array
    {
        return $this->findings;
    }

    public function setFindings(array $findings): static
    {
        $this->findings = $findings;
        return $this;
    }

    public function addFinding(string $category, string $severity, string $message): static
    {
        $this->findings[] = [
            'category' => $category,
            'severity' => $severity,
            'message' => $message,
            'timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ];
        return $this;
    }

    public function getImprovementSuggestions(): array
    {
        return $this->improvementSuggestions;
    }

    public function setImprovementSuggestions(array $improvementSuggestions): static
    {
        $this->improvementSuggestions = $improvementSuggestions;
        return $this;
    }

    public function addImprovementSuggestion(string $area, string $suggestion, int $priority = 1): static
    {
        $this->improvementSuggestions[] = [
            'area' => $area,
            'suggestion' => $suggestion,
            'priority' => $priority, // 1=high, 2=medium, 3=low
        ];
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function isPassed(): ?bool
    {
        return $this->passed;
    }

    public function setPassed(bool $passed): static
    {
        $this->passed = $passed;
        return $this;
    }
}
