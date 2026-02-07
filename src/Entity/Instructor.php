<?php

namespace App\Entity;

use App\Repository\InstructorRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: InstructorRepository::class)]
class Instructor
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Instructor name cannot be empty")]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: "Instructor name must be at least {{ limit }} characters long",
        maxMessage: "Instructor name cannot be longer than {{ limit }} characters"
    )]
    private ?string $name = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Assert\NotBlank(message: "Email cannot be empty")]
    #[Assert\Email(message: "Please enter a valid email")]
    private ?string $email = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $bio = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $avatar = null;

    #[ORM\Column(type: 'boolean')]
    private bool $verified = false;

    #[ORM\Column(type: 'decimal', precision: 2, scale: 1)]
    #[Assert\Range(
        min: 0,
        max: 5,
        minMessage: "Rating must be at least {{ limit }}",
        maxMessage: "Rating cannot be more than {{ limit }}"
    )]
    private ?float $rating = 0.0;

    #[ORM\Column(type: 'integer')]
    private int $totalStudents = 0;

    #[ORM\Column(type: 'integer')]
    private int $totalCourses = 0;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $instructor = null;

    #[ORM\OneToMany(mappedBy: 'instructor', targetEntity: Quiz::class)]
    private Collection $quizzes;

    #[ORM\Column]
    #[Assert\NotNull(message: "Creation date is required")]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getBio(): ?string
    {
        return $this->bio;
    }

    public function setBio(?string $bio): static
    {
        $this->bio = $bio;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): static
    {
        $this->avatar = $avatar;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function isVerified(): bool
    {
        return $this->verified;
    }

    public function setVerified(bool $verified): static
    {
        $this->verified = $verified;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getRating(): ?float
    {
        return $this->rating;
    }

    public function setRating(float $rating): static
    {
        $this->rating = $rating;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getTotalStudents(): int
    {
        return $this->totalStudents;
    }

    public function setTotalStudents(int $totalStudents): static
    {
        $this->totalStudents = $totalStudents;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getTotalCourses(): int
    {
        return $this->totalCourses;
    }

    public function setTotalCourses(int $totalCourses): static
    {
        $this->totalCourses = $totalCourses;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getQuizzes(): Collection
    {
        return $this->quizzes;
    }

    public function addQuiz(Quiz $quiz): static
    {
        if (!$this->quizzes->contains($quiz)) {
            $this->quizzes->add($quiz);
            $quiz->setInstructor($this);
        }
        return $this;
    }

    public function removeQuiz(Quiz $quiz): static
    {
        $this->quizzes->removeElement($quiz);
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
}
