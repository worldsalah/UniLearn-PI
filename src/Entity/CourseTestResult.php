<?php

namespace App\Entity;

use App\Entity\CourseTest;
use App\Entity\User;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class CourseTestResult
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: CourseTest::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?CourseTest $courseTest = null;

    #[ORM\Column(type: 'integer')]
    private int $score = 0;

    #[ORM\Column(type: 'integer')]
    private int $totalQuestions = 0;

    #[ORM\Column(type: 'float')]
    private float $percentage = 0.0;

    #[ORM\Column(type: 'boolean')]
    private bool $passed = false;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $timeTaken;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $answers = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->timeTaken = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
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

    public function getScore(): int
    {
        return $this->score;
    }

    public function setScore(int $score): self
    {
        $this->score = $score;
        return $this;
    }

    public function getTotalQuestions(): int
    {
        return $this->totalQuestions;
    }

    public function setTotalQuestions(int $totalQuestions): self
    {
        $this->totalQuestions = $totalQuestions;
        return $this;
    }

    public function getPercentage(): float
    {
        return $this->percentage;
    }

    public function setPercentage(float $percentage): self
    {
        $this->percentage = $percentage;
        return $this;
    }

    public function getPassed(): bool
    {
        return $this->passed;
    }

    public function setPassed(bool $passed): self
    {
        $this->passed = $passed;
        return $this;
    }

    public function getTimeTaken(): \DateTimeImmutable
    {
        return $this->timeTaken;
    }

    public function setTimeTaken(\DateTimeInterface $timeTaken): self
    {
        if ($timeTaken instanceof \DateTime) {
            $immutable = \DateTimeImmutable::createFromMutable($timeTaken);
            $this->timeTaken = $immutable !== false ? $immutable : new \DateTimeImmutable();
        } else {
            $this->timeTaken = $timeTaken;
        }
        return $this;
    }


    public function getAnswers(): ?array
    {
        return $this->answers;
    }

    public function setAnswers(?array $answers): self
    {
        $this->answers = $answers;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}
// Force refresh 03/23/2026 09:53:25
