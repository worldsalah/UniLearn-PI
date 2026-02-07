<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "User name cannot be empty")]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: "User name must be at least {{ limit }} characters long",
        maxMessage: "User name cannot be longer than {{ limit }} characters"
    )]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Email cannot be empty")]
    #[Assert\Email(message: "Please enter a valid email address")]
    #[Assert\Length(
        max: 255,
        maxMessage: "Email cannot be longer than {{ limit }} characters"
    )]
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(
        max: 255,
        maxMessage: "Avatar path cannot be longer than {{ limit }} characters"
    )]
    private ?string $avatar = null;

    #[ORM\Column]
    #[Assert\NotNull(message: "Creation date is required")]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: QuizResult::class)]
    private Collection $quizResults;

    public function __construct()
    {
        $this->quizResults = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

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
            $quizResult->setUser($this);
        }

        return $this;
    }

    public function removeQuizResult(QuizResult $quizResult): static
    {
        if ($this->quizResults->removeElement($quizResult)) {
            // set the owning side to null (unless already changed)
            if ($quizResult->getUser() === $this) {
                $quizResult->setUser(null);
            }
        }

        return $this;
    }
}
