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
    public ?string $name = null;

    public ?string $description = null;

    #[Assert\Range(
        min: 1,
        max: 20,
        notInRangeMessage: 'Le nombre de joueurs doit être compris entre {{ min }} et {{ max }}.'
    )]
    public ?int $maxPlayers = null;

    #[Assert\Type(type: 'bool', message: 'Le champ "isPublic" doit être un booléen.')]
    public ?bool $isPublic = null;

    public ?GameStatus $status = null;
}
