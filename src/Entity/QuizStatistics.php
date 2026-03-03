<?php

namespace App\Entity;

use App\Repository\QuizStatisticsRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: QuizStatisticsRepository::class)]
class QuizStatistics
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Quiz::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Quiz $quiz = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $student = null;

    #[ORM\Column(type: 'integer')]
    #[Assert\Range(min: 0, max: 100)]
    private ?int $score = null;

    #[ORM\Column(type: 'integer')]
    #[Assert\Range(min: 0)]
    private ?int $totalQuestions = null;

    #[ORM\Column(type: 'integer')]
    #[Assert\Range(min: 0)]
    private ?int $correctAnswers = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $completedAt = null;

    #[ORM\Column(type: 'float')]
    private ?float $averageTimePerQuestion = null;

    #[ORM\Column(type: 'json')]
    private array $questionResults = [];

    #[ORM\Column(type: 'integer')]
    private ?int $difficultyLevel = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuiz(): ?Quiz
    {
        return $this->quiz;
    }

    public function setQuiz(?Quiz $quiz): self
    {
        $this->quiz = $quiz;
        return $this;
    }

    public function getStudent(): ?User
    {
        return $this->student;
    }

    public function setStudent(?User $student): self
    {
        $this->student = $student;
        return $this;
    }

    public function getScore(): ?int
    {
        return $this->score;
    }

    public function setScore(?int $score): self
    {
        $this->score = $score;
        return $this;
    }

    public function getTotalQuestions(): ?int
    {
        return $this->totalQuestions;
    }

    public function setTotalQuestions(?int $totalQuestions): self
    {
        $this->totalQuestions = $totalQuestions;
        return $this;
    }

    public function getCorrectAnswers(): ?int
    {
        return $this->correctAnswers;
    }

    public function setCorrectAnswers(?int $correctAnswers): self
    {
        $this->correctAnswers = $correctAnswers;
        return $this;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTimeImmutable $completedAt): self
    {
        $this->completedAt = $completedAt;
        return $this;
    }

    public function getAverageTimePerQuestion(): ?float
    {
        return $this->averageTimePerQuestion;
    }

    public function setAverageTimePerQuestion(?float $averageTimePerQuestion): self
    {
        $this->averageTimePerQuestion = $averageTimePerQuestion;
        return $this;
    }

    public function getQuestionResults(): array
    {
        return $this->questionResults;
    }

    public function setQuestionResults(array $questionResults): self
    {
        $this->questionResults = $questionResults;
        return $this;
    }

    public function getDifficultyLevel(): ?int
    {
        return $this->difficultyLevel;
    }

    public function setDifficultyLevel(?int $difficultyLevel): self
    {
        $this->difficultyLevel = $difficultyLevel;
        return $this;
    }
}
