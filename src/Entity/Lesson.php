<?php

namespace App\Entity;

use App\Repository\LessonRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: LessonRepository::class)]
class Lesson
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le titre de la leçon est obligatoire.")]
    #[Assert\Length(min: 3, max: 100, minMessage: 'Le titre de la leçon doit contenir au moins {{ limit }} caractères', maxMessage: 'Le titre de la leçon ne peut pas dépasser {{ limit }} caractères')]
    private ?string $title = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "La durée de la leçon est obligatoire.")]
    #[Assert\Regex(pattern: '/^\d+:[0-5]\d$/', message: "Format invalide. Utilisez HH:MM (ex: 0:45 ou 1:30).")]
    private ?string $duration = null;

    #[ORM\Column(length: 50)]
    private ?string $type = 'video';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $content = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $attachmentUrl = null;

    #[ORM\Column]
    private bool $isPreview = false;

    #[ORM\Column(length: 20)]
    private ?string $status = 'active';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private int $sortOrder = 0;

    #[ORM\ManyToOne(inversedBy: 'lessons')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Chapter $chapter = null;

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
}
