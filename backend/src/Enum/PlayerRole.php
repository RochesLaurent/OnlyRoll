<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Rôles possibles d'un joueur dans une partie.
 */
enum PlayerRole: string
{
    case GAME_MASTER = 'game_master';
    case PLAYER = 'player';
    case SPECTATOR = 'spectator';

    public function canEdit(): bool
    {
        return self::GAME_MASTER === $this;
    }
}
