<?php

declare(strict_types=1);

namespace App\DTO\Token;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO pour la création d'un token sur une carte.
 */
final class CreateTokenDTO
{
    #[Assert\NotBlank(message: 'Le nom du token est obligatoire.')]
    #[Assert\Length(
        min: 1,
        max: 250,
        minMessage: 'Le nom doit faire au moins {{ limit }} caractère.',
        maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères.',
    )]
    public string $name;

    #[Assert\NotBlank(message: 'Le type du token est obligatoire.')]
    #[Assert\Choice(
        choices: ['character', 'monster', 'npc', 'object'],
        message: 'Le type doit être "character", "monster", "npc" ou "object".',
    )]
    public string $type;

    #[Assert\Url(message: 'L\'URL de l\'image doit être valide.')]
    #[Assert\Length(
        max: 500,
        maxMessage: 'L\'URL ne peut pas dépasser {{ limit }} caractères.',
    )]
    public ?string $imageUrl = null;

    #[Assert\NotNull(message: 'La position X est obligatoire.')]
    #[Assert\PositiveOrZero(message: 'La position X doit être positive ou nulle.')]
    public int $x = 0;

    #[Assert\NotNull(message: 'La position Y est obligatoire.')]
    #[Assert\PositiveOrZero(message: 'La position Y doit être positive ou nulle.')]
    public int $y = 0;

    #[Assert\NotNull(message: 'La taille est obligatoire.')]
    #[Assert\Range(
        min: 0.1,
        max: 10.0,
        notInRangeMessage: 'La taille doit être entre {{ min }} et {{ max }}.',
    )]
    public float $size = 1.0;

    #[Assert\Range(
        min: 0,
        max: 359,
        notInRangeMessage: 'La rotation doit être entre {{ min }}° et {{ max }}°.',
    )]
    public int $rotation = 0;

    #[Assert\Type(type: 'bool', message: 'Le champ "isVisible" doit être un booléen.')]
    public bool $isVisible = true;

    #[Assert\Type(type: 'bool', message: 'Le champ "isLocked" doit être un booléen.')]
    public bool $isLocked = false;

    #[Assert\Choice(
        choices: ['background', 'objects', 'tokens', 'effects'],
        message: 'Le layer doit être "background", "objects", "tokens" ou "effects".',
    )]
    public string $layer = 'tokens';

    /**
     * @var array<string, mixed>|null
     */
    #[Assert\Type(type: 'array', message: 'Les paramètres doivent être un tableau.')]
    public ?array $settings = null;
}
