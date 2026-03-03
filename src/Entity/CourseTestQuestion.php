<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class CourseTestQuestion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: CourseTest::class, inversedBy: 'questions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?CourseTest $courseTest = null;

    #[ORM\Column(type: 'text')]
    private string $question = '';

    #[ORM\Column(type: 'json')]
    private array $options = [];

    #[ORM\Column(type: 'string', length: 255)]
    private string $correctAnswer = '';

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $explanation = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string $difficulty = 'medium';

    #[ORM\Column(type: 'integer')]
    private int $points = 1;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCourseTest(): ?CourseTest
    {
        return $this->courseTest;
    }

    public function setCourseTest(?CourseTest $courseTest): self
    {
        $this->courseTest = $courseTest;
        return $this;
    }

    public function getQuestion(): string
    {
        return $this->question;
    }

    public function setQuestion(string $question): self
    {
        $this->question = $question;
        return $this;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function setOptions(array $options): self
    {
        $this->options = $options;
        return $this;
    }

    public function getCorrectAnswer(): string
    {
        return $this->correctAnswer;
    }

    public function setCorrectAnswer(string $correctAnswer): self
    {
        $this->correctAnswer = $correctAnswer;
        return $this;
    }

    public function getExplanation(): ?string
    {
        return $this->explanation;
    }

    public function setExplanation(?string $explanation): self
    {
        $this->explanation = $explanation;
        return $this;
    }

    public function getDifficulty(): string
    {
        return $this->difficulty;
    }

    public function setDifficulty(string $difficulty): self
    {
        $this->difficulty = $difficulty;
        return $this;
    }

    public function getPoints(): int
    {
        return $this->points;
    }

    public function setPoints(int $points): self
    {
        $this->points = $points;
        return $this;
    }
}
