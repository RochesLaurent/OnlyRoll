<?php

declare(strict_types=1);

namespace App\DTO\Token;

use Symfony\Component\Validator\Constraints as Assert;

class MoveTokenDTO
{
    #[Assert\NotNull(message: 'La position X est obligatoire.')]
    #[Assert\PositiveOrZero(message: 'La position X doit être positive ou zéro.')]
    public int $x;

    #[Assert\NotNull(message: 'La position Y est obligatoire.')]
    #[Assert\PositiveOrZero(message: 'La position Y doit être positive ou zéro.')]
    public int $y;

    #[Assert\Range(
        min: 0,
        max: 359,
        notInRangeMessage: 'La rotation doit être entre {{ min }}° et {{ max }}°.',
    )]
    public ?int $rotation = null;
}
