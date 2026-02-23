<?php

namespace App\Entity;

use App\Repository\ContactRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ContactRepository::class)]
class Contact
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le prénom est obligatoire.')]
    #[Assert\Length(min: 2, max: 100, minMessage: 'Le prénom doit contenir au moins {{ limit }} caractères.', maxMessage: 'Le prénom ne peut pas dépasser {{ limit }} caractères.')]
    #[Assert\Regex(pattern: '/^[a-zA-ZÀ-ÿ\s\'-]+$/', message: 'Le prénom ne peut contenir que des lettres, espaces, tirets et apostrophes.')]
    private ?string $firstName = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le nom de famille est obligatoire.')]
    #[Assert\Length(min: 2, max: 100, minMessage: 'Le nom de famille doit contenir au moins {{ limit }} caractères.', maxMessage: 'Le nom de famille ne peut pas dépasser {{ limit }} caractères.')]
    #[Assert\Regex(pattern: '/^[a-zA-ZÀ-ÿ\s\'-]+$/', message: 'Le nom de famille ne peut contenir que des lettres, espaces, tirets et apostrophes.')]
    private ?string $lastName = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'L\'adresse email est obligatoire.')]
    #[Assert\Email(message: 'L\'adresse email n\'est pas valide.')]
    #[Assert\Length(max: 255, maxMessage: 'L\'email ne peut pas dépasser {{ limit }} caractères.')]
    private ?string $email = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Assert\Regex(pattern: '/^[\+]?[0-9\s\-\(\)]+$/', message: 'Le numéro de téléphone n\'est pas valide.')]
    #[Assert\Length(max: 20, maxMessage: 'Le téléphone ne peut pas dépasser {{ limit }} caractères.')]
    private ?string $phone = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le sujet est obligatoire.')]
    #[Assert\Choice(choices: ['General Inquiry', 'Technical Support', 'Billing Question', 'Course Information', 'Partnership'], message: 'Veuillez choisir un sujet valide.')]
    private ?string $subject = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Le message est obligatoire.')]
    #[Assert\Length(min: 10, minMessage: 'Le message doit contenir au moins {{ limit }} caractères.')]
    #[Assert\Length(max: 2000, maxMessage: 'Le message ne peut pas dépasser {{ limit }} caractères.')]
    private ?string $message = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(length: 20, options: ['default' => 'pending'])]
    private ?string $status = 'pending';

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): static
    {
        $this->subject = $subject;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): static
    {
        $this->message = $message;

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

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }
}
