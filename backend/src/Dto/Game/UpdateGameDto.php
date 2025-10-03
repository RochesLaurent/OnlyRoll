<?php
namespace App\DTO\Game;

use App\Enum\GameStatus;
use Symfony\Component\Validator\Constraints as Assert;

class UpdateGameDTO
{
    #[Assert\Length(
        min: 3,
        max: 250,
        minMessage: 'Le nom du jeu doit faire au moins {{ limit }} caractères.',
        maxMessage: 'Le nom du jeu ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $name = null;

    private ?string $description = null;

    #[Assert\Range(
        min: 1,
        max: 20,
        notInRangeMessage: 'Le nombre de joueurs doit être compris entre {{ min }} et {{ max }}.'
    )]
    private ?int $maxPlayers = null;

    #[Assert\Type(type: 'bool', message: 'Le champ "isPublic" doit être un booléen.')]
    private ?bool $isPublic = null;

    private ?GameStatus $status = null;

    // Getters
    public function getName(): ?string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getMaxPlayers(): ?int
    {
        return $this->maxPlayers;
    }

    public function isPublic(): ?bool
    {
        return $this->isPublic;
    }

    public function getStatus(): ?GameStatus
    {
        return $this->status;
    }

    // Setters
    public function setName(?string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function setMaxPlayers(?int $maxPlayers): self
    {
        $this->maxPlayers = $maxPlayers;
        return $this;
    }

    public function setIsPublic(?bool $isPublic): self
    {
        $this->isPublic = $isPublic;
        return $this;
    }

    public function setStatus(?GameStatus $status): self
    {
        $this->status = $status;
        return $this;
    }
}
