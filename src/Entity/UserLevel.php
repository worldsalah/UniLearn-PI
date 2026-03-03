<?php

namespace App\Entity;

use App\Repository\UserLevelRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserLevelRepository::class)]
#[ORM\Table(name: 'user_level')]
class UserLevel
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 50)]
    private ?string $name = null;

    #[ORM\Column(type: 'string', length: 50, unique: true)]
    private ?string $code = null;

    #[ORM\Column(type: 'integer')]
    private ?int $minXp = 0;

    #[ORM\Column(type: 'integer')]
    private ?int $maxXp = 0;

    #[ORM\Column(type: 'string', length: 7)]
    private ?string $color = '#6366f1';

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $icon = null;

    #[ORM\Column(type: 'integer')]
    private ?int $levelOrder = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getMinXp(): ?int
    {
        return $this->minXp;
    }

    public function setMinXp(int $minXp): self
    {
        $this->minXp = $minXp;

        return $this;
    }

    public function getMaxXp(): ?int
    {
        return $this->maxXp;
    }

    public function setMaxXp(int $maxXp): self
    {
        $this->maxXp = $maxXp;

        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(string $color): self
    {
        $this->color = $color;

        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    public function getLevelOrder(): ?int
    {
        return $this->levelOrder;
    }

    public function setLevelOrder(int $levelOrder): self
    {
        $this->levelOrder = $levelOrder;

        return $this;
    }

    public function __toString(): string
    {
        return $this->name ?? '';
    }
}
