<?php

namespace App\Enum;

/**
 * Énumération des calques d'affichage des tokens sur la carte.
 */
enum TokenLayer: string
{
    case BACKGROUND = 'background';  // Arrière-plan (sol, terrain)
    case OBJECTS = 'objects';        // Objets et décors
    case TOKENS = 'tokens';          // Tokens principaux (personnages, monstres)
    case EFFECTS = 'effects';        // Effets visuels (sorts, auras)

    /**
     * Retourne le label français du calque.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::BACKGROUND => 'Arrière-plan',
            self::OBJECTS => 'Objets',
            self::TOKENS => 'Personnages',
            self::EFFECTS => 'Effets',
        };
    }

    /**
     * Retourne l'ordre de rendu (z-index) du calque.
     * Plus le nombre est élevé, plus le calque est au-dessus.
     */
    public function getZIndex(): int
    {
        return match ($this) {
            self::BACKGROUND => 1,
            self::OBJECTS => 2,
            self::TOKENS => 3,
            self::EFFECTS => 4,
        };
    }

    /**
     * Vérifie si les éléments de ce calque peuvent être sélectionnés.
     */
    public function isSelectable(): bool
    {
        return match ($this) {
            self::TOKENS, self::OBJECTS => true,
            self::BACKGROUND, self::EFFECTS => false,
        };
    }

    /**
     * Vérifie si les éléments de ce calque peuvent bloquer le mouvement.
     */
    public function blocksMovement(): bool
    {
        return match ($this) {
            self::OBJECTS, self::TOKENS => true,
            self::BACKGROUND, self::EFFECTS => false,
        };
    }

    /**
     * Vérifie si ce calque est visible par tous les joueurs par défaut.
     */
    public function isVisibleByDefault(): bool
    {
        return match ($this) {
            self::BACKGROUND, self::OBJECTS, self::TOKENS => true,
            self::EFFECTS => true,  // Les effets sont généralement visibles
        };
    }

    /**
     * Retourne le calque par défaut pour un type de token donné.
     */
    public static function getDefaultForTokenType(TokenType $tokenType): self
    {
        return match ($tokenType) {
            TokenType::CHARACTER, TokenType::MONSTER, TokenType::NPC => self::TOKENS,
            TokenType::OBJECT => self::OBJECTS,
        };
    }
}
