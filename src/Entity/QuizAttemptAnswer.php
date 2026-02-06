<?php

namespace App\Entity;

use App\Repository\QuizAttemptAnswerRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: QuizAttemptAnswerRepository::class)]
#[ORM\Table(uniqueConstraints: [
    new ORM\UniqueConstraint(name: 'uniq_attempt_question', columns: ['attempt_id', 'question_id'])
])]
class QuizAttemptAnswer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'answers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?QuizAttempt $attempt = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?QuizQuestion $question = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?QuizAnswer $selectedAnswer = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAttempt(): ?QuizAttempt
    {
        return $this->attempt;
    }

    public function setAttempt(?QuizAttempt $attempt): static
    {
        $this->attempt = $attempt;
        return $this;
    }

    public function getQuestion(): ?QuizQuestion
    {
        return $this->question;
    }

    public function setQuestion(?QuizQuestion $question): static
    {
        $this->question = $question;
        return $this;
    }

    public function getSelectedAnswer(): ?QuizAnswer
    {
        return $this->selectedAnswer;
    }

    public function setSelectedAnswer(?QuizAnswer $selectedAnswer): static
    {
        $this->selectedAnswer = $selectedAnswer;
        return $this;
    }
}

