<?php
namespace App\DTO\Game;

use Symfony\Component\Validator\Constraints as Assert;

class CreateGameDTO
{
    #[Assert\NotBlank(message: 'Le nom du jeu est obligatoire.')]
    #[Assert\Length(
        min: 3,
        max: 250,
        minMessage: 'Le nom du jeu doit faire au moins {{ limit }} caractères.',
        maxMessage: 'Le nom du jeu ne peut pas dépasser {{ limit }} caractères.'
    )]
    private string $name;

    private ?string $description = null;

    #[Assert\Range(
        min: 1,
        max: 20,
        notInRangeMessage: 'Le nombre de joueurs doit être compris entre {{ min }} et {{ max }}.'
    )]
    private int $maxPlayers = 6;

    #[Assert\Type(type: 'bool', message: 'Le champ "isPublic" doit être un booléen.')]
    private bool $isPublic = false;

    #[Assert\Length(
        min: 4,
        max: 50,
        minMessage: 'Le mot de passe doit faire au moins {{ limit }} caractères.',
        maxMessage: 'Le mot de passe ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $password = null;

    public function __construct(string $name, int $maxPlayers = 6)
    {
        $this->name = $name;
        $this->maxPlayers = $maxPlayers;
    }

    // Getters
    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getMaxPlayers(): int
    {
        return $this->maxPlayers;
    }

    public function isPublic(): bool
    {
        return $this->isPublic;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    // Setters
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function setMaxPlayers(int $maxPlayers): self
    {
        $this->maxPlayers = $maxPlayers;
        return $this;
    }

    public function setIsPublic(bool $isPublic): self
    {
        $this->isPublic = $isPublic;
        return $this;
    }

    public function setPassword(?string $password): self
    {
        $this->password = $password;
        return $this;
    }
}
