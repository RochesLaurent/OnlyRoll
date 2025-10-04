<?php

declare(strict_types=1);

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
    public string $name;

    public ?string $description = null;

    #[Assert\Range(
        min: 1,
        max: 20,
        notInRangeMessage: 'Le nombre de joueurs doit être compris entre {{ min }} et {{ max }}.'
    )]
    public int $maxPlayers = 6;

    #[Assert\Type(type: 'bool', message: 'Le champ "isPublic" doit être un booléen.')]
    public bool $isPublic = false;

    public ?string $password = null;
}
