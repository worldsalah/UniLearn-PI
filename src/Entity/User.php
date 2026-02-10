<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
#[UniqueEntity(fields: ['username'], message: 'This username is already taken.')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'full_name', type: 'string', length: 100)]
    #[Assert\NotBlank(message: 'Please enter your full name.')]
    #[Assert\Length(min: 3, minMessage: 'Your full name must be at least {{ limit }} characters long.')]
    private ?string $fullName = null;

    #[ORM\Column(name: 'first_name', type: 'string', length: 50, nullable: true)]
    private ?string $firstName = null;

    #[ORM\Column(name: 'last_name', type: 'string', length: 50, nullable: true)]
    private ?string $lastName = null;

    #[ORM\Column(name: 'profile_picture', type: 'string', length: 255, nullable: true)]
    private ?string $profilePicture = null;

    #[ORM\Column(type: 'string', length: 50, unique: true)]
    #[Assert\NotBlank(message: 'Please enter a username.')]
    #[Assert\Length(min: 3, minMessage: 'Your username must be at least {{ limit }} characters long.')]
    private ?string $username = null;

    #[ORM\Column(type: 'string', length: 100, unique: true)]
    #[Assert\NotBlank(message: 'Please enter an email.')]
    #[Assert\Email(message: 'The email "{{ value }}" is not a valid email.')]
    private ?string $email = null;

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $location = null;

    #[ORM\Column(name: 'about_me', type: 'text', nullable: true)]
    private ?string $aboutMe = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $education = [];

    #[ORM\Column(name: 'facebook_username', type: 'string', length: 100, nullable: true)]
    private ?string $facebookUsername = null;

    #[ORM\Column(name: 'twitter_username', type: 'string', length: 100, nullable: true)]
    private ?string $twitterUsername = null;

    #[ORM\Column(name: 'instagram_username', type: 'string', length: 100, nullable: true)]
    private ?string $instagramUsername = null;

    #[ORM\Column(name: 'youtube_url', type: 'string', length: 255, nullable: true)]
    private ?string $youtubeUrl = null;

    #[ORM\ManyToOne(targetEntity: Role::class, inversedBy: 'users')]
    #[ORM\JoinColumn(name: 'role_id', referencedColumnName: 'id', nullable: false)]
    private ?Role $role = null;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: 'boolean')]
    private bool $agreeTerms = false;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $googleId = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function updateFullName(): void
    {
        // This can be left empty or removed if fullName is set directly.
    }

    /* ================= SECURITY ================= */

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getRoles(): array
    {
        if ($this->role) {
            return ['ROLE_' . strtoupper($this->role->getName())];
        }
        return ['ROLE_USER'];
    }

    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
    }

    /* ================= GETTERS / SETTERS ================= */

    public function getId(): ?int { return $this->id; }

    public function getFullName(): ?string { return $this->fullName; }

    public function setFullName(string $fullName): self
    {
        $this->fullName = $fullName;
        return $this;
    }

    public function getFirstName(): ?string { return $this->firstName; }

    public function setFirstName(?string $firstName): self
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): ?string { return $this->lastName; }

    public function setLastName(?string $lastName): self
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getUsername(): ?string { return $this->username; }

    public function setUsername(?string $username): self
    {
        $this->username = $username;
        return $this;
    }

    public function getEmail(): ?string { return $this->email; }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getPassword(): string { return $this->password; }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function getPhone(): ?string { return $this->phone; }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;
        return $this;
    }

    public function getLocation(): ?string { return $this->location; }

    public function setLocation(?string $location): self
    {
        $this->location = $location;
        return $this;
    }

    public function getAboutMe(): ?string { return $this->aboutMe; }

    public function setAboutMe(?string $aboutMe): self
    {
        $this->aboutMe = $aboutMe;
        return $this;
    }

    public function getEducation(): ?array { return $this->education ?? []; }

    public function setEducation(?array $education): self
    {
        $this->education = $education;
        return $this;
    }

    public function getProfilePicture(): ?string { return $this->profilePicture; }

    public function setProfilePicture(?string $profilePicture): self
    {
        $this->profilePicture = $profilePicture;
        return $this;
    }

    public function getFacebookUsername(): ?string { return $this->facebookUsername; }

    public function setFacebookUsername(?string $facebookUsername): self
    {
        $this->facebookUsername = $facebookUsername;
        return $this;
    }

    public function getTwitterUsername(): ?string { return $this->twitterUsername; }

    public function setTwitterUsername(?string $twitterUsername): self
    {
        $this->twitterUsername = $twitterUsername;
        return $this;
    }

    public function getInstagramUsername(): ?string { return $this->instagramUsername; }

    public function setInstagramUsername(?string $instagramUsername): self
    {
        $this->instagramUsername = $instagramUsername;
        return $this;
    }

    public function getYoutubeUrl(): ?string { return $this->youtubeUrl; }

    public function setYoutubeUrl(?string $youtubeUrl): self
    {
        $this->youtubeUrl = $youtubeUrl;
        return $this;
    }

    public function getRole(): ?Role { return $this->role; }

    public function setRole(?Role $role): self
    {
        $this->role = $role;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function isAgreeTerms(): bool
    {
        return $this->agreeTerms;
    }

    public function setAgreeTerms(bool $agreeTerms): self
    {
        $this->agreeTerms = $agreeTerms;
        return $this;
    }

    public function getGoogleId(): ?string
    {
        return $this->googleId;
    }

    public function setGoogleId(?string $googleId): self
    {
        $this->googleId = $googleId;
        return $this;
    }
}