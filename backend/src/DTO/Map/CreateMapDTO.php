<?php

declare(strict_types=1);

namespace App\DTO\Map;

use App\Enum\MapGridType;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO pour la création d'une carte de jeu.
 */
final class CreateMapDTO
{
    #[Assert\NotBlank(message: 'Le nom de la carte est obligatoire.')]
    #[Assert\Length(
        min: 3,
        max: 250,
        minMessage: 'Le nom de la carte doit faire au moins {{ limit }} caractères.',
        maxMessage: 'Le nom de la carte ne peut pas dépasser {{ limit }} caractères.',
    )]
    public string $name;

    public ?string $description = null;

    public ?string $imageUrl = null;

    #[Assert\Range(
        min: 10,
        max: 200,
        notInRangeMessage: 'La taille de la grille doit être entre {{ min }} et {{ max }} pixels.',
    )]
    public int $gridSize = 50;

    public MapGridType $gridType = MapGridType::SQUARE;

    #[Assert\Range(
        min: 5,
        max: 200,
        notInRangeMessage: 'La largeur doit être entre {{ min }} et {{ max }} cases.',
    )]
    public int $width = 20;

    #[Assert\Range(
        min: 5,
        max: 200,
        notInRangeMessage: 'La hauteur doit être entre {{ min }} et {{ max }} cases.',
    )]
    public int $height = 20;

    #[Assert\Type(type: 'bool', message: 'Le champ "isActive" doit être un booléen.')]
    public bool $isActive = false;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $settings = null;
}
