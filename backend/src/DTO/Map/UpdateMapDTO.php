<?php

declare(strict_types=1);

namespace App\DTO\Map;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO pour la mise à jour d'une carte de jeu.
 */
final class UpdateMapDTO
{
    #[Assert\Length(
        min: 3,
        max: 250,
        minMessage: 'Le nom de la carte doit faire au moins {{ limit }} caractères.',
        maxMessage: 'Le nom de la carte ne peut pas dépasser {{ limit }} caractères.',
    )]
    public ?string $name = null;

    public ?string $description = null;

    #[Assert\Url(message: 'L\'URL de l\'image n\'est pas valide.')]
    public ?string $imageUrl = null;

    #[Assert\Range(
        min: 10,
        max: 200,
        notInRangeMessage: 'La taille de la grille doit être entre {{ min }} et {{ max }} pixels.',
    )]
    public ?int $gridSize = null;

    #[Assert\Choice(
        choices: ['square', 'hex', 'none'],
        message: 'Le type de grille doit être "square", "hex" ou "none".',
    )]
    public ?string $gridType = null;

    #[Assert\Range(
        min: 5,
        max: 200,
        notInRangeMessage: 'La largeur doit être entre {{ min }} et {{ max }} cases.',
    )]
    public ?int $width = null;

    #[Assert\Range(
        min: 5,
        max: 200,
        notInRangeMessage: 'La hauteur doit être entre {{ min }} et {{ max }} cases.',
    )]
    public ?int $height = null;

    #[Assert\Type(type: 'bool', message: 'Le champ "isActive" doit être un booléen.')]
    public ?bool $isActive = null;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $settings = null;
}
