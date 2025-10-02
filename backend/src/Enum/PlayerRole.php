<?php

namespace App\Enum;

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
