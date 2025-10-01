<?php

namespace App\Enum;

enum PlayerStatus: string
{
    case PENDING = 'pending';      // Invitation en attente
    case ACTIVE = 'active';        // Joueur actif
    case INACTIVE = 'inactive';    // Temporairement inactif
    case KICKED = 'kicked';        // Expulsé
    case LEFT = 'left';            // Parti volontairement

    public function isParticipating(): bool
    {
        return match($this) {
            self::ACTIVE, self::INACTIVE => true,
            self::PENDING, self::KICKED, self::LEFT => false,
        };
    }
    
    public function canReactivate(): bool
    {
        // Peut se réactiver sans intervention du GM
        return $this === self::INACTIVE;
    }
    
    public function needsNewInvite(): bool
    {
        return match($this) {
            self::LEFT, self::KICKED => true,
            default => false,
        };
    }
    
    public function hasLeft(): bool
    {
        return match($this) {
            self::KICKED, self::LEFT => true,
            default => false,
        };
    }
    
    public function canAccessGame(): bool
    {
        return $this->isParticipating();
    }
}
