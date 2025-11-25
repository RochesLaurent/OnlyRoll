<?php

namespace App\Enum;

/**
 * Énumération des types de tokens sur la carte.
 */
enum TokenType: string
{
    case CHARACTER = 'character';  // Personnage joueur
    case MONSTER = 'monster';      // Monstre/ennemi
    case NPC = 'npc';              // Personnage non-joueur
    case OBJECT = 'object';        // Objet/décor

    /**
     * Retourne le label français du type de token.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::CHARACTER => 'Personnage',
            self::MONSTER => 'Monstre',
            self::NPC => 'PNJ',
            self::OBJECT => 'Objet',
        };
    }

    /**
     * Vérifie si ce type de token peut être contrôlé par un joueur.
     */
    public function isPlayerControllable(): bool
    {
        return $this === self::CHARACTER;
    }

    /**
     * Vérifie si ce type de token peut avoir des points de vie.
     */
    public function canHaveHealthPoints(): bool
    {
        return match ($this) {
            self::CHARACTER, self::MONSTER, self::NPC => true,
            self::OBJECT => false,
        };
    }

    /**
     * Vérifie si ce type de token est une créature vivante.
     */
    public function isCreature(): bool
    {
        return match ($this) {
            self::CHARACTER, self::MONSTER, self::NPC => true,
            self::OBJECT => false,
        };
    }

    /**
     * Vérifie si ce type de token est hostile par défaut.
     */
    public function isHostileByDefault(): bool
    {
        return $this === self::MONSTER;
    }

    /**
     * Retourne l'icône par défaut pour ce type de token.
     */
    public function getDefaultIcon(): string
    {
        return match ($this) {
            self::CHARACTER => '🧙',
            self::MONSTER => '👹',
            self::NPC => '👤',
            self::OBJECT => '📦',
        };
    }
}
