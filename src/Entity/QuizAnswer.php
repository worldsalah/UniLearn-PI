<?php

namespace App\Entity;

use App\Repository\QuizAnswerRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: QuizAnswerRepository::class)]
#[ORM\Table(name: 'quiz_answer')]
class QuizAnswer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: QuizQuestion::class)]
    #[ORM\JoinColumn(name: 'question_id', referencedColumnName: 'id', nullable: false)]
    private ?QuizQuestion $question = null;

    #[ORM\ManyToOne(targetEntity: QuizResult::class)]
    #[ORM\JoinColumn(name: 'quiz_result_id', referencedColumnName: 'id', nullable: false)]
    private ?QuizResult $quizResult = null;

    #[ORM\Column(type: 'text')]
    private ?string $answer = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isCorrect = false;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuestion(): ?QuizQuestion
    {
        return $this->question;
    }

    public function setQuestion(?QuizQuestion $question): self
    {
        $this->question = $question;

        return $this;
    }

    public function getQuizResult(): ?QuizResult
    {
        return $this->quizResult;
    }

    public function setQuizResult(?QuizResult $quizResult): self
    {
        $this->quizResult = $quizResult;

        return $this;
    }

    public function getAnswer(): ?string
    {
        return $this->answer;
    }

    public function setAnswer(?string $answer): self
    {
        $this->answer = $answer;

        return $this;
    }

    public function isCorrect(): bool
    {
        return $this->isCorrect;
    }

    public function setCorrect(bool $isCorrect): self
    {
        $this->isCorrect = $isCorrect;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
