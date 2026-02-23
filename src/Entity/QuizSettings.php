<?php

namespace App\Entity;

use App\Repository\QuizSettingsRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: QuizSettingsRepository::class)]
class QuizSettings
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'Les points sont obligatoires.')]
    #[Assert\Positive(message: 'Les points doivent être un nombre positif.')]
    #[Assert\Range(min: 1, max: 1000, minMessage: 'Les points doivent être au moins {{ limit }}.', maxMessage: 'Les points ne peuvent pas dépasser {{ limit }}.')]
    private ?int $points = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Positive(message: 'La limite de temps doit être un nombre positif.')]
    #[Assert\Range(min: 1, max: 1440, minMessage: 'La limite de temps doit être au moins {{ limit }} minute.', maxMessage: 'La limite de temps ne peut pas dépasser {{ limit }} minutes (24h).')]
    private ?int $timeLimit = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'Le score de réussite est obligatoire.')]
    #[Assert\Range(min: 0, max: 100, minMessage: 'Le score de réussite doit être au moins {{ limit }}%.', maxMessage: 'Le score de réussite ne peut pas dépasser {{ limit }}%.')]
    private ?int $passingScore = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'Le nombre maximum de tentatives est obligatoire.')]
    #[Assert\Range(min: 1, max: 10, minMessage: 'Le nombre maximum de tentatives doit être au moins {{ limit }}.', maxMessage: 'Le nombre maximum de tentatives ne peut pas dépasser {{ limit }}.')]
    private ?int $maxAttempts = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(inversedBy: 'quizSettings')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Quiz $quiz = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->points = 1;
        $this->passingScore = 70;
        $this->maxAttempts = 3;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPoints(): ?int
    {
        return $this->points;
    }

    public function setPoints(int $points): static
    {
        $this->points = $points;

        return $this;
    }

    public function getTimeLimit(): ?int
    {
        return $this->timeLimit;
    }

    public function setTimeLimit(?int $timeLimit): static
    {
        $this->timeLimit = $timeLimit;

        return $this;
    }

    public function getPassingScore(): ?int
    {
        return $this->passingScore;
    }

    public function setPassingScore(int $passingScore): static
    {
        $this->passingScore = $passingScore;

        return $this;
    }

    public function getMaxAttempts(): ?int
    {
        return $this->maxAttempts;
    }

    public function setMaxAttempts(int $maxAttempts): static
    {
        $this->maxAttempts = $maxAttempts;

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

    public function getQuiz(): ?Quiz
    {
        return $this->quiz;
    }

    public function setQuiz(?Quiz $quiz): static
    {
        $this->quiz = $quiz;

        return $this;
    }
}
