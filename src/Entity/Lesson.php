<?php

namespace App\Entity;

use App\Repository\LessonRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: LessonRepository::class)]
#[ORM\Index(name: 'idx_lesson_chapter', columns: ['chapter_id'])]
#[ORM\Index(name: 'idx_lesson_sort', columns: ['chapter_id', 'sort_order'])]
class Lesson
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le titre de la leçon est obligatoire.')]
    #[Assert\Length(min: 3, max: 100, minMessage: 'Le titre de la leçon doit contenir au moins {{ limit }} caractères', maxMessage: 'Le titre de la leçon ne peut pas dépasser {{ limit }} caractères')]
    private ?string $title = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'La durée de la leçon est obligatoire.')]
    #[Assert\Regex(pattern: '/^\d+:[0-5]\d$/', message: 'Format invalide. Utilisez HH:MM (ex: 0:45 ou 1:30).')]
    private ?string $duration = null;

    #[ORM\Column(length: 50)]
    private ?string $type = 'video';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $content = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $attachmentUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $videoUrl = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $difficulty = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $learningObjectives = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $prerequisites = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $materials = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $estimatedTime = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $transcript = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $thumbnailUrl = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $resources = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $assessment = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isCompleted = false;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $views = 0;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $publishedAt = null;

    #[ORM\Column]
    private bool $isPreview = false;

    #[ORM\Column(length: 20)]
    private ?string $status = 'active';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private int $sortOrder = 0;

    #[ORM\ManyToOne(inversedBy: 'lessons')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Chapter $chapter = null;

    #[ORM\OneToMany(mappedBy: 'lesson', targetEntity: LessonCompletion::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $lessonCompletions;

    public function __construct()
    {
        $this->lessonCompletions = new ArrayCollection();
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

    public function getDuration(): ?string
    {
        return $this->duration;
    }

    public function setDuration(string $duration): static
    {
        $this->duration = $duration;

        return $this;
    }

    public function getChapter(): ?Chapter
    {
        return $this->chapter;
    }

    public function setChapter(?Chapter $chapter): static
    {
        $this->chapter = $chapter;

        return $this;
    }

    public function getCourse(): ?Course
    {
        return $this->chapter?->getCourse();
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getAttachmentUrl(): ?string
    {
        return $this->attachmentUrl;
    }

    public function setAttachmentUrl(?string $attachmentUrl): static
    {
        $this->attachmentUrl = $attachmentUrl;

        return $this;
    }

    public function isPreview(): bool
    {
        return $this->isPreview;
    }

    public function setIsPreview(bool $isPreview): static
    {
        $this->isPreview = $isPreview;

        return $this;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): static
    {
        $this->sortOrder = $sortOrder;

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

    public function getDifficulty(): ?string
    {
        return $this->difficulty;
    }

    public function setDifficulty(?string $difficulty): static
    {
        $this->difficulty = $difficulty;

        return $this;
    }

    public function getLearningObjectives(): ?string
    {
        return $this->learningObjectives;
    }

    public function setLearningObjectives(?string $learningObjectives): static
    {
        $this->learningObjectives = $learningObjectives;

        return $this;
    }

    public function getPrerequisites(): ?string
    {
        return $this->prerequisites;
    }

    public function setPrerequisites(?string $prerequisites): static
    {
        $this->prerequisites = $prerequisites;

        return $this;
    }

    public function getMaterials(): ?array
    {
        return $this->materials;
    }

    public function setMaterials(?array $materials): static
    {
        $this->materials = $materials;

        return $this;
    }

    public function getEstimatedTime(): ?int
    {
        return $this->estimatedTime;
    }

    public function setEstimatedTime(?int $estimatedTime): static
    {
        $this->estimatedTime = $estimatedTime;

        return $this;
    }

    public function getTranscript(): ?string
    {
        return $this->transcript;
    }

    public function setTranscript(?string $transcript): static
    {
        $this->transcript = $transcript;

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

    public function getResources(): ?string
    {
        return $this->resources;
    }

    public function setResources(?string $resources): static
    {
        $this->resources = $resources;

        return $this;
    }

    public function getAssessment(): ?string
    {
        return $this->assessment;
    }

    public function setAssessment(?string $assessment): static
    {
        $this->assessment = $assessment;

        return $this;
    }

    public function isCompleted(): bool
    {
        return $this->isCompleted;
    }

    public function setIsCompleted(bool $isCompleted): static
    {
        $this->isCompleted = $isCompleted;

        return $this;
    }

    public function getViews(): ?int
    {
        return $this->views;
    }

    public function setViews(?int $views): static
    {
        $this->views = $views;

        return $this;
    }

    public function getPublishedAt(): ?\DateTimeInterface
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(?\DateTimeInterface $publishedAt): static
    {
        $this->publishedAt = $publishedAt;

        return $this;
    }
}
