<?php

declare(strict_types=1);

namespace App\DTO\Game;

use Symfony\Component\Validator\Constraints as Assert;

class JoinGameDTO
{
    #[Assert\Length(
        min: 4,
        max: 50,
        minMessage: 'Le mot de passe doit faire au moins {{ limit }} caractères.',
        maxMessage: 'Le mot de passe ne peut pas dépasser {{ limit }} caractères.'
    )]
    public ?string $password = null;
}
