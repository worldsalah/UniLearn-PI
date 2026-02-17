<?php

namespace App\Entity;

use App\Repository\CheckoutRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CheckoutRepository::class)]
class Checkout
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

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'L\'adresse est obligatoire.')]
    #[Assert\Length(min: 5, max: 255, minMessage: 'L\'adresse doit contenir au moins {{ limit }} caractères.', maxMessage: 'L\'adresse ne peut pas dépasser {{ limit }} caractères.')]
    private ?string $address = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'La ville est obligatoire.')]
    #[Assert\Length(min: 2, max: 100, minMessage: 'La ville doit contenir au moins {{ limit }} caractères.', maxMessage: 'La ville ne peut pas dépasser {{ limit }} caractères.')]
    private ?string $city = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'L\'état/province est obligatoire.')]
    #[Assert\Length(min: 2, max: 100, minMessage: 'L\'état doit contenir au moins {{ limit }} caractères.', maxMessage: 'L\'état ne peut pas dépasser {{ limit }} caractères.')]
    private ?string $state = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank(message: 'Le code postal est obligatoire.')]
    #[Assert\Regex(pattern: '/^[A-Za-z0-9\s\-]{3,20}$/', message: 'Le code postal n\'est pas valide.')]
    #[Assert\Length(min: 3, max: 20, minMessage: 'Le code postal doit contenir au moins {{ limit }} caractères.', maxMessage: 'Le code postal ne peut pas dépasser {{ limit }} caractères.')]
    private ?string $zipCode = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le pays est obligatoire.')]
    #[Assert\Length(min: 2, max: 100, minMessage: 'Le pays doit contenir au moins {{ limit }} caractères.', maxMessage: 'Le pays ne peut pas dépasser {{ limit }} caractères.')]
    private ?string $country = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Le numéro de carte est obligatoire.')]
    #[Assert\Regex(pattern: '/^[0-9\s]{13,19}$/', message: 'Le numéro de carte n\'est pas valide.')]
    private ?string $cardNumber = null;

    #[ORM\Column(length: 10)]
    #[Assert\NotBlank(message: 'La date d\'expiration est obligatoire.')]
    #[Assert\Regex(pattern: '/^(0[1-9]|1[0-2])\/\d{2}$/', message: 'La date d\'expiration doit être au format MM/AA.')]
    private ?string $expiryDate = null;

    #[ORM\Column(length: 5)]
    #[Assert\NotBlank(message: 'Le CVV est obligatoire.')]
    #[Assert\Regex(pattern: '/^[0-9]{3,4}$/', message: 'Le CVV doit contenir 3 ou 4 chiffres.')]
    private ?string $cvv = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le nom du titulaire de la carte est obligatoire.')]
    #[Assert\Length(min: 3, max: 100, minMessage: 'Le nom du titulaire doit contenir au moins {{ limit }} caractères.', maxMessage: 'Le nom du titulaire ne peut pas dépasser {{ limit }} caractères.')]
    private ?string $cardholderName = null;

    #[ORM\Column(length: 20, options: ["default" => "credit_card"])]
    #[Assert\Choice(choices: ['credit_card', 'paypal', 'apple_pay'], message: 'Veuillez choisir une méthode de paiement valide.')]
    private ?string $paymentMethod = 'credit_card';

    #[ORM\Column(type: 'boolean')]
    private bool $agreeTerms = false;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(length: 20, options: ["default" => "pending"])]
    private ?string $status = 'pending';

    #[ORM\Column]
    #[Assert\Positive(message: 'Le montant total doit être un nombre positif.')]
    private ?float $totalAmount = null;

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

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): static
    {
        $this->address = $address;
        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): static
    {
        $this->city = $city;
        return $this;
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    public function setState(string $state): static
    {
        $this->state = $state;
        return $this;
    }

    public function getZipCode(): ?string
    {
        return $this->zipCode;
    }

    public function setZipCode(string $zipCode): static
    {
        $this->zipCode = $zipCode;
        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(string $country): static
    {
        $this->country = $country;
        return $this;
    }

    public function getCardNumber(): ?string
    {
        return $this->cardNumber;
    }

    public function setCardNumber(string $cardNumber): static
    {
        $this->cardNumber = $cardNumber;
        return $this;
    }

    public function getExpiryDate(): ?string
    {
        return $this->expiryDate;
    }

    public function setExpiryDate(string $expiryDate): static
    {
        $this->expiryDate = $expiryDate;
        return $this;
    }

    public function getCvv(): ?string
    {
        return $this->cvv;
    }

    public function setCvv(string $cvv): static
    {
        $this->cvv = $cvv;
        return $this;
    }

    public function getCardholderName(): ?string
    {
        return $this->cardholderName;
    }

    public function setCardholderName(string $cardholderName): static
    {
        $this->cardholderName = $cardholderName;
        return $this;
    }

    public function getPaymentMethod(): ?string
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(string $paymentMethod): static
    {
        $this->paymentMethod = $paymentMethod;
        return $this;
    }

    public function isAgreeTerms(): bool
    {
        return $this->agreeTerms;
    }

    public function setAgreeTerms(bool $agreeTerms): static
    {
        $this->agreeTerms = $agreeTerms;
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

    public function getTotalAmount(): ?float
    {
        return $this->totalAmount;
    }

    public function setTotalAmount(float $totalAmount): static
    {
        $this->totalAmount = $totalAmount;
        return $this;
    }
}
