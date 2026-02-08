<?php
// src/Entity/Booking.php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity]
class Booking
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 1)]
    private ?string $firstName = null;

     #[ORM\Column(type: 'string', length: 255)]
    private ?string $lastName = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: "Email is required.")]
    #[Assert\Email(message: "Please enter a valid email address.")]
    private ?string $userEmail = null;

     #[ORM\Column(type: 'string', length: 255)]
    private ?string $phoneNumber = null;

    
    #[ORM\ManyToOne(targetEntity: Session::class)]
    #[ORM\JoinColumn(name: 'session_id', referencedColumnName: 'id', nullable: false)]
    private ?Session $session = null;

    public function getSession(): ?Session
    {
        return $this->session;
    }

    public function setSession(?Session $session): self
    {
        $this->session = $session;
        return $this;
    }

    public function __construct()
    {
        $this->sessions = new ArrayCollection();
    }

    // -------- Getters et Setters --------

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserName(): ?string
    {
        return $this->userName;
    }

    public function setUserName(string $userName): self
    {
        $this->userName = $userName;
        return $this;
    }

    public function getUserEmail(): ?string
    {
        return $this->userEmail;
    }

    public function setUserEmail(string $userEmail): self
    {
        $this->userEmail = $userEmail;
        return $this;
    }
     public function getFirstName(): ?string
    {
        return $this->firstName;
    }
     public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;
        return $this;
    }
      public function getLastName(): ?string
    {
        return $this->lastName;
    }
     public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;
        return $this;
    }
   public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }
     public function setPhoneNumber(string $phoneNumber): self
    {
        $this->phoneNumber = $phoneNumber;
        return $this;
    }


}
