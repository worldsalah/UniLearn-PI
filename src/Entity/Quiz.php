<?php

namespace App\Entity;

use App\Repository\QuizRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: QuizRepository::class)]
class Quiz
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Quiz title cannot be empty')]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: 'Quiz title must be at least {{ limit }} characters long',
        maxMessage: 'Quiz title cannot be longer than {{ limit }} characters'
    )]
    private ?string $title = null;

    #[ORM\ManyToOne(targetEntity: Course::class, inversedBy: 'quizzes')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Course $course = null;

    #[ORM\OneToMany(mappedBy: 'quiz', targetEntity: Question::class, cascade: ['persist', 'remove'])]
    private Collection $questions;

    #[ORM\OneToMany(mappedBy: 'quiz', targetEntity: QuizResult::class)]
    private Collection $quizResults;

    #[ORM\OneToMany(mappedBy: 'quiz', targetEntity: QuizSettings::class)]
    private Collection $quizSettings;

    #[ORM\OneToMany(mappedBy: 'quiz', targetEntity: QuizStatistics::class, cascade: ['remove'])]
    private Collection $quizStatistics;

    #[ORM\Column]
    #[Assert\NotNull(message: 'Creation date is required')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(nullable: true)]
    private ?int $duration = null; // Duration in minutes

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $openingDate = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $closingDate = null;

    public function __construct()
    {
        $this->questions = new ArrayCollection();
        $this->quizResults = new ArrayCollection();
        $this->quizSettings = new ArrayCollection();
        $this->quizStatistics = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getCourse(): ?Course
    {
        return $this->course;
    }

    public function setCourse(?Course $course): static
    {
        $this->course = $course;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    /**
     * @return Collection<int, Question>
     */
    public function getQuestions(): Collection
    {
        return $this->questions;
    }

    public function addQuestion(Question $question): static
    {
        if (!$this->questions->contains($question)) {
            $this->questions->add($question);
            $question->setQuiz($this);
        }

        return $this;
    }

    public function removeQuestion(Question $question): static
    {
        if ($this->questions->removeElement($question)) {
            // set the owning side to null (unless already changed)
            if ($question->getQuiz() === $this) {
                $question->setQuiz(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, QuizResult>
     */
    public function getQuizResults(): Collection
    {
        return $this->quizResults;
    }

    public function addQuizResult(QuizResult $quizResult): static
    {
        if (!$this->quizResults->contains($quizResult)) {
            $this->quizResults->add($quizResult);
            $quizResult->setQuiz($this);
        }

        return $this;
    }

    public function removeQuizResult(QuizResult $quizResult): static
    {
        if ($this->quizResults->removeElement($quizResult)) {
            // set the owning side to null (unless already changed)
            if ($quizResult->getQuiz() === $this) {
                $quizResult->setQuiz(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, QuizSettings>
     */
    public function getQuizSettings(): Collection
    {
        return $this->quizSettings;
    }

    public function addQuizSetting(QuizSettings $quizSetting): static
    {
        if (!$this->quizSettings->contains($quizSetting)) {
            $this->quizSettings->add($quizSetting);
            $quizSetting->setQuiz($this);
        }

        return $this;
    }

    public function removeQuizSetting(QuizSettings $quizSetting): static
    {
        if ($this->quizSettings->removeElement($quizSetting)) {
            // set the owning side to null (unless already changed)
            if ($quizSetting->getQuiz() === $this) {
                $quizSetting->setQuiz(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, QuizStatistics>
     */
    public function getQuizStatistics(): Collection
    {
        return $this->quizStatistics;
    }

    public function addQuizStatistic(QuizStatistics $quizStatistic): static
    {
        if (!$this->quizStatistics->contains($quizStatistic)) {
            $this->quizStatistics->add($quizStatistic);
            $quizStatistic->setQuiz($this);
        }

        return $this;
    }

    public function removeQuizStatistic(QuizStatistics $quizStatistic): static
    {
        if ($this->quizStatistics->removeElement($quizStatistic)) {
            // set the owning side to null (unless already changed)
            if ($quizStatistic->getQuiz() === $this) {
                $quizStatistic->setQuiz(null);
            }
        }

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

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function setDuration(?int $duration): static
    {
        $this->duration = $duration;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getOpeningDate(): ?\DateTimeImmutable
    {
        return $this->openingDate;
    }

    public function setOpeningDate(?\DateTimeImmutable $openingDate): static
    {
        $this->openingDate = $openingDate;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getClosingDate(): ?\DateTimeImmutable
    {
        return $this->closingDate;
    }

    public function setClosingDate(?\DateTimeImmutable $closingDate): static
    {
        $this->closingDate = $closingDate;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function isOpen(): bool
    {
        $now = new \DateTimeImmutable();
        
        if ($this->openingDate && $now < $this->openingDate) {
            return false; // Not yet opened
        }
        
        if ($this->closingDate && $now > $this->closingDate) {
            return false; // Already closed
        }
        
        return true; // Open
    }

    public function getStatus(): string
    {
        $now = new \DateTimeImmutable();
        
        if ($this->openingDate && $now < $this->openingDate) {
            return 'upcoming'; // Pas encore ouvert
        }
        
        if ($this->closingDate && $now > $this->closingDate) {
            return 'closed'; // Fermé
        }
        
        return 'open'; // Ouvert
    }
}
