<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Statuts possibles d'un joueur dans une partie.
 */
enum PlayerStatus: string
{
    case PENDING = 'pending';      // Invitation en attente
    case ACTIVE = 'active';        // Joueur actif
    case INACTIVE = 'inactive';    // Temporairement inactif
    case KICKED = 'kicked';        // Expulsé
    case LEFT = 'left';            // Parti volontairement

    public function isParticipating(): bool
    {
        return match ($this) {
            self::ACTIVE, self::INACTIVE, self::PENDING => true,
            self::KICKED, self::LEFT => false,
        };
    }

    public function canReactivate(): bool
    {
        // Can reactivate without GM intervention
        return match ($this) {
            self::INACTIVE, self::LEFT, self::KICKED => true,
            default => false,
        };
    }

    public function needsNewInvite(): bool
    {
        return match ($this) {
            self::LEFT, self::KICKED => true,
            default => false,
        };
    }

    public function hasLeft(): bool
    {
        return match ($this) {
            self::KICKED, self::LEFT => true,
            default => false,
        };
    }

    public function canAccessGame(): bool
    {
        return $this->isParticipating();
    }
}
