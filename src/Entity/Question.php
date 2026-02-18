<?php

namespace App\Entity;

use App\Repository\QuestionRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: QuestionRepository::class)]
class Question
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Question text cannot be empty')]
    #[Assert\Length(
        min: 5,
        max: 255,
        minMessage: 'Question must be at least {{ limit }} characters long',
        maxMessage: 'Question cannot be longer than {{ limit }} characters'
    )]
    private ?string $question = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Option A cannot be empty')]
    #[Assert\Length(
        min: 1,
        max: 255,
        minMessage: 'Option A must be at least {{ limit }} character long',
        maxMessage: 'Option A cannot be longer than {{ limit }} characters'
    )]
    private ?string $optionA = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Option B cannot be empty')]
    #[Assert\Length(
        min: 1,
        max: 255,
        minMessage: 'Option B must be at least {{ limit }} character long',
        maxMessage: 'Option B cannot be longer than {{ limit }} characters'
    )]
    private ?string $optionB = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Option C cannot be empty')]
    #[Assert\Length(
        min: 1,
        max: 255,
        minMessage: 'Option C must be at least {{ limit }} character long',
        maxMessage: 'Option C cannot be longer than {{ limit }} characters'
    )]
    private ?string $optionC = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Option D cannot be empty')]
    #[Assert\Length(
        min: 1,
        max: 255,
        minMessage: 'Option D must be at least {{ limit }} character long',
        maxMessage: 'Option D cannot be longer than {{ limit }} characters'
    )]
    private ?string $optionD = null;

    #[ORM\Column(length: 1)]
    #[Assert\NotBlank(message: 'Correct option must be specified')]
    #[Assert\Choice(
        choices: ['A', 'B', 'C', 'D'],
        message: 'Correct option must be one of: A, B, C, or D'
    )]
    private ?string $correctOption = null;

    #[ORM\ManyToOne(inversedBy: 'questions')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Quiz is required')]
    private ?Quiz $quiz = null;

    #[ORM\Column]
    #[Assert\NotNull(message: 'Creation date is required')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuestion(): ?string
    {
        return $this->question;
    }

    public function setQuestion(string $question): static
    {
        $this->question = $question;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getOptionA(): ?string
    {
        return $this->optionA;
    }

    public function setOptionA(string $optionA): static
    {
        $this->optionA = $optionA;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getOptionB(): ?string
    {
        return $this->optionB;
    }

    public function setOptionB(string $optionB): static
    {
        $this->optionB = $optionB;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getOptionC(): ?string
    {
        return $this->optionC;
    }

    public function setOptionC(string $optionC): static
    {
        $this->optionC = $optionC;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getOptionD(): ?string
    {
        return $this->optionD;
    }

    public function setOptionD(string $optionD): static
    {
        $this->optionD = $optionD;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getCorrectOption(): ?string
    {
        return $this->correctOption;
    }

    public function setCorrectOption(string $correctOption): static
    {
        $this->correctOption = $correctOption;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getQuiz(): ?Quiz
    {
        return $this->quiz;
    }

    public function setQuiz(?Quiz $quiz): static
    {
        $this->quiz = $quiz;
        $this->updatedAt = new \DateTimeImmutable();

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

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getOptions(): array
    {
        return [
            'A' => $this->optionA,
            'B' => $this->optionB,
            'C' => $this->optionC,
            'D' => $this->optionD,
        ];
    }
}
