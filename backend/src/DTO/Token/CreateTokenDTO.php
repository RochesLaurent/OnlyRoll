<?php

declare(strict_types=1);

namespace App\DTO\Token;

use App\Enum\TokenLayer;
use App\Enum\TokenType;
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
    public TokenType $type;

    #[Assert\Regex(
        pattern: '/^(\/|https?:\/\/)/',
        message: 'L\'URL de l\'image doit être un chemin relatif (commençant par /) ou une URL complète (http/https).',
    )]
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

    public TokenLayer $layer = TokenLayer::TOKENS;

    /**
     * @var array<string, mixed>|null
     */
    #[Assert\Type(type: 'array', message: 'Les paramètres doivent être un tableau.')]
    public ?array $settings = null;
}
