<?php

namespace App\Enum;

/**
 * Énumération des types de grille pour les cartes.
 */
enum MapGridType: string
{
    case SQUARE = 'square';  // Grille carrée standard
    case HEX = 'hex';        // Grille hexagonale
    case NONE = 'none';      // Pas de grille

    /**
     * Retourne le label français du type de grille.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::SQUARE => 'Carrée',
            self::HEX => 'Hexagonale',
            self::NONE => 'Aucune',
        };
    }

    /**
     * Vérifie si le type de grille nécessite des calculs de distance.
     */
    public function hasDistanceCalculation(): bool
    {
        return match ($this) {
            self::SQUARE, self::HEX => true,
            self::NONE => false,
        };
    }

    /**
     * Retourne le nombre de directions possibles pour ce type de grille.
     */
    public function getDirectionCount(): int
    {
        return match ($this) {
            self::SQUARE => 8,  // 4 cardinales + 4 diagonales
            self::HEX => 6,     // 6 directions hexagonales
            self::NONE => 0,
        };
    }

    /**
     * Vérifie si ce type de grille supporte les mouvements diagonaux.
     */
    public function supportsDiagonalMovement(): bool
    {
        return $this === self::SQUARE;
    }
}
