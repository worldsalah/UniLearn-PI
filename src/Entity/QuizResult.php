<?php

namespace App\Entity;

use App\Repository\QuizResultRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: QuizResultRepository::class)]
class QuizResult
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'quizResults')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'User is required')]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'quizResults')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Quiz is required')]
    private ?Quiz $quiz = null;

    #[ORM\Column]
    #[Assert\NotNull(message: 'Score is required')]
    #[Assert\PositiveOrZero(message: 'Score must be zero or positive')]
    private ?int $score = null;

    #[ORM\Column]
    #[Assert\NotNull(message: 'Maximum score is required')]
    #[Assert\Positive(message: 'Maximum score must be positive')]
    private ?int $maxScore = null;

    #[Assert\Expression(
        'this.getScore() <= this.getMaxScore()',
        message: 'Score cannot be greater than maximum score'
    )]
    private bool $scoreValidation = true;

    #[ORM\Column]
    #[Assert\NotNull(message: 'Quiz date is required')]
    #[Assert\LessThanOrEqual(
        'today',
        message: 'Quiz date cannot be in the future'
    )]
    private ?\DateTimeImmutable $takenAt = null;

    #[ORM\Column]
    #[Assert\NotNull(message: 'Creation date is required')]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
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

    public function getScore(): ?int
    {
        return $this->score;
    }

    public function setScore(int $score): static
    {
        $this->score = $score;

        return $this;
    }

    public function getMaxScore(): ?int
    {
        return $this->maxScore;
    }

    public function setMaxScore(int $maxScore): static
    {
        $this->maxScore = $maxScore;

        return $this;
    }

    public function getTakenAt(): ?\DateTimeImmutable
    {
        return $this->takenAt;
    }

    public function setTakenAt(\DateTimeImmutable $takenAt): static
    {
        $this->takenAt = $takenAt;

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

    public function getPercentage(): float
    {
        if (0 === $this->maxScore) {
            return 0;
        }

        return round(($this->score / $this->maxScore) * 100, 2);
    }
}
