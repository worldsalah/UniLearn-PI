<?php

namespace App\Entity;

use App\Repository\CourseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CourseRepository::class)]
class Course
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le titre du cours est obligatoire.')]
    #[Assert\Length(min: 5, max: 200, minMessage: 'Le titre du cours doit contenir au moins {{ limit }} caractères', maxMessage: 'Le titre du cours ne peut pas dépasser {{ limit }} caractères')]
    #[Assert\Regex(pattern: '/^(?!\d+$).+$/', message: 'Le titre ne peut pas être composé uniquement de chiffres.')]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'La description courte est obligatoire.')]
    #[Assert\Length(min: 20, max: 1000, minMessage: 'La description courte doit contenir au moins {{ limit }} caractères', maxMessage: 'La description courte ne peut pas dépasser {{ limit }} caractères')]
    private ?string $shortDescription = null;

    #[ORM\ManyToOne(inversedBy: 'courses')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Category $category = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le niveau du cours est obligatoire.')]
    private ?string $level = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'Le prix est obligatoire.')]
    #[Assert\Positive(message: 'Le prix doit être un nombre positif.')]
    #[Assert\LessThanOrEqual(value: 9999.99, message: 'Le prix ne peut pas dépasser {{ value }}')]
    private ?float $price = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $thumbnailUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $videoUrl = null;

    #[ORM\OneToMany(mappedBy: 'course', targetEntity: Chapter::class, cascade: ['persist', 'remove'])]
    private Collection $chapters;

    #[ORM\OneToMany(mappedBy: 'course', targetEntity: Quiz::class, cascade: ['persist', 'remove'])]
    private Collection $quizzes;

    #[ORM\ManyToOne(inversedBy: 'courses')]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $user = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\NotBlank(message: 'La langue est obligatoire.')]
    private ?string $language = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Positive(message: 'La durée doit être un nombre positif')]
    #[Assert\LessThanOrEqual(value: 1000, message: 'La durée ne peut pas dépasser {{ value }} heures')]
    private ?float $duration = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\Length(max: 2000, maxMessage: 'Les prérequis ne peuvent pas dépasser {{ limit }} caractères')]
    private ?string $requirements = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\Length(max: 2000, maxMessage: 'Les objectifs ne peuvent pas dépasser {{ limit }} caractères')]
    private ?string $learningOutcomes = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\Length(max: 2000, maxMessage: 'L\'audience ne peut pas dépasser {{ limit }} caractères')]
    private ?string $targetAudience = null;

    #[ORM\Column(length: 20, options: ['default' => 'inactive'])]
    private ?string $status = 'inactive';

    #[ORM\Column(length: 20, options: ['default' => 'pending'])]
    private string $imageStatus = 'pending';

    #[ORM\Column(length: 20, options: ['default' => 'pending'])]
    private string $videoStatus = 'pending';

    #[ORM\Column(options: ['default' => 0])]
    private float $imageProgress = 0;

    #[ORM\Column(options: ['default' => 0])]
    private float $videoProgress = 0;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->chapters = new ArrayCollection();
        $this->quizzes = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getImageStatus(): string
    {
        return $this->imageStatus;
    }

    public function setImageStatus(string $imageStatus): static
    {
        $this->imageStatus = $imageStatus;

        return $this;
    }

    public function getVideoStatus(): string
    {
        return $this->videoStatus;
    }

    public function setVideoStatus(string $videoStatus): static
    {
        $this->videoStatus = $videoStatus;

        return $this;
    }

    public function getImageProgress(): float
    {
        return $this->imageProgress;
    }

    public function setImageProgress(float $imageProgress): static
    {
        $this->imageProgress = $imageProgress;

        return $this;
    }

    public function getVideoProgress(): float
    {
        return $this->videoProgress;
    }

    public function setVideoProgress(float $videoProgress): static
    {
        $this->videoProgress = $videoProgress;

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

        return $this;
    }

    public function getShortDescription(): ?string
    {
        return $this->shortDescription;
    }

    public function setShortDescription(string $shortDescription): static
    {
        $this->shortDescription = $shortDescription;

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getLevel(): ?string
    {
        return $this->level;
    }

    public function setLevel(string $level): static
    {
        $this->level = $level;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getThumbnailUrl(): ?string
    {
        return $this->thumbnailUrl;
    }

    public function setThumbnailUrl(?string $thumbnailUrl): static
    {
        $this->thumbnailUrl = $thumbnailUrl;

        return $this;
    }

    public function getVideoUrl(): ?string
    {
        return $this->videoUrl;
    }

    public function setVideoUrl(?string $videoUrl): static
    {
        $this->videoUrl = $videoUrl;

        return $this;
    }

    /**
     * @return Collection<int, Chapter>
     */
    public function getChapters(): Collection
    {
        return $this->chapters;
    }

    public function addChapter(Chapter $chapter): static
    {
        if (!$this->chapters->contains($chapter)) {
            $this->chapters->add($chapter);
            $chapter->setCourse($this);
        }

        return $this;
    }

    public function removeChapter(Chapter $chapter): static
    {
        if ($this->chapters->removeElement($chapter)) {
            // set the owning side to null (unless already changed)
            if ($chapter->getCourse() === $this) {
                $chapter->setCourse(null);
            }
        }

        return $this;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function setLanguage(?string $language): static
    {
        $this->language = $language;

        return $this;
    }

    public function getDuration(): ?float
    {
        return $this->duration;
    }

    public function setDuration(?float $duration): static
    {
        $this->duration = $duration;

        return $this;
    }

    public function getRequirements(): ?string
    {
        return $this->requirements;
    }

    public function setRequirements(?string $requirements): static
    {
        $this->requirements = $requirements;

        return $this;
    }

    public function getLearningOutcomes(): ?string
    {
        return $this->learningOutcomes;
    }

    public function setLearningOutcomes(?string $learningOutcomes): static
    {
        $this->learningOutcomes = $learningOutcomes;

        return $this;
    }

    public function getTargetAudience(): ?string
    {
        return $this->targetAudience;
    }

    public function setTargetAudience(?string $targetAudience): static
    {
        $this->targetAudience = $targetAudience;

        return $this;
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

    public function getTotalLessons(): int
    {
        $total = 0;
        foreach ($this->chapters as $chapter) {
            $total += $chapter->getLessons()->count();
        }

        return $total;
    }

    /**
     * @return Collection<int, Quiz>
     */
    public function getQuizzes(): Collection
    {
        return $this->quizzes;
    }

    public function addQuiz(Quiz $quiz): static
    {
        if (!$this->quizzes->contains($quiz)) {
            $this->quizzes->add($quiz);
            $quiz->setCourse($this);
        }

        return $this;
    }

    public function removeQuiz(Quiz $quiz): static
    {
        if ($this->quizzes->removeElement($quiz)) {
            // set the owning side to null (unless already changed)
            if ($quiz->getCourse() === $this) {
                $quiz->setCourse(null);
            }
        }

        return $this;
    }

    // Virtual file handling methods for thumbnail
    public function getThumbnailFile(): ?string
    {
        return null; // This is a virtual field
    }

    public function setThumbnailFile(mixed $file): self
    {
        // This method is handled by the controller, not the entity
        return $this;
    }

    // Virtual file handling methods for video
    public function getVideoFile(): ?string
    {
        return null; // This is a virtual field
    }

    public function setVideoFile(mixed $file): self
    {
        // This method is handled by the controller, not the entity
        return $this;
    }

    public function __toString(): string
    {
        return $this->title ?? '';
    }

    /**
     * Get description for Elasticsearch (alias for shortDescription)
     */
    public function getDescription(): ?string
    {
        return $this->shortDescription;
    }

    /**
     * Set description for Elasticsearch (alias for shortDescription)
     */
    public function setDescription(?string $description): static
    {
        $this->shortDescription = $description;

        return $this;
    }
}
