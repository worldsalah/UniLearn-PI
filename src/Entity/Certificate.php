<?php

namespace App\Entity;

use App\Repository\CertificateRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CertificateRepository::class)]
class Certificate
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'certificates')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'User is required')]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: QuizResult::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Quiz result is required')]
    private ?QuizResult $quizResult = null;

    #[ORM\ManyToOne(targetEntity: Course::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Course is required')]
    private ?Course $course = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Certificate filename is required')]
    #[Assert\Length(max: 255, maxMessage: 'Filename cannot be longer than {{ limit }} characters')]
    private ?string $filename = null;

    #[ORM\Column(length: 500)]
    #[Assert\NotBlank(message: 'Certificate file path is required')]
    #[Assert\Length(max: 500, maxMessage: 'File path cannot be longer than {{ limit }} characters')]
    private ?string $filePath = null;

    #[ORM\Column]
    #[Assert\NotNull(message: 'File size is required')]
    #[Assert\Positive(message: 'File size must be positive')]
    private ?int $fileSize = null;

    #[ORM\Column]
    #[Assert\NotNull(message: 'Generated date is required')]
    #[Assert\LessThanOrEqual('today', message: 'Generated date cannot be in the future')]
    private ?\DateTimeImmutable $generatedAt = null;

    #[ORM\Column]
    private ?bool $isDownloaded = false;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastDownloadedAt = null;

    #[ORM\Column]
    private ?int $downloadCount = 0;

    public function __construct()
    {
        $this->generatedAt = new \DateTimeImmutable();
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

    public function getQuizResult(): ?QuizResult
    {
        return $this->quizResult;
    }

    public function setQuizResult(?QuizResult $quizResult): self
    {
        $this->quizResult = $quizResult;
        return $this;
    }

    public function getCourse(): ?Course
    {
        return $this->course;
    }

    public function setCourse(?Course $course): self
    {
        $this->course = $course;
        return $this;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): self
    {
        $this->filename = $filename;
        return $this;
    }

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    public function setFilePath(string $filePath): self
    {
        $this->filePath = $filePath;
        return $this;
    }

    public function getFileSize(): ?int
    {
        return $this->fileSize;
    }

    public function setFileSize(int $fileSize): self
    {
        $this->fileSize = $fileSize;
        return $this;
    }

    public function getGeneratedAt(): ?\DateTimeImmutable
    {
        return $this->generatedAt;
    }

    public function setGeneratedAt(\DateTimeImmutable $generatedAt): self
    {
        $this->generatedAt = $generatedAt;
        return $this;
    }

    public function isIsDownloaded(): ?bool
    {
        return $this->isDownloaded;
    }

    public function setIsDownloaded(bool $isDownloaded): self
    {
        $this->isDownloaded = $isDownloaded;
        return $this;
    }

    public function getLastDownloadedAt(): ?\DateTimeImmutable
    {
        return $this->lastDownloadedAt;
    }

    public function setLastDownloadedAt(?\DateTimeImmutable $lastDownloadedAt): self
    {
        $this->lastDownloadedAt = $lastDownloadedAt;
        return $this;
    }

    public function getDownloadCount(): ?int
    {
        return $this->downloadCount;
    }

    public function setDownloadCount(int $downloadCount): self
    {
        $this->downloadCount = $downloadCount;
        return $this;
    }

    public function incrementDownloadCount(): self
    {
        $this->downloadCount = ($this->downloadCount ?? 0) + 1;
        $this->isDownloaded = true;
        $this->lastDownloadedAt = new \DateTimeImmutable();
        return $this;
    }
}
