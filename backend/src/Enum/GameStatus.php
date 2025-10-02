<?php

namespace App\Enum;

enum GameStatus: string
{
    case PREPARATION = 'preparation';
    case IN_PROGRESS = 'in_progress';
    case PAUSED = 'paused';
    case COMPLETED = 'completed';
    case ARCHIVED = 'archived';

    public function getLabel(): string
    {
        return match ($this) {
            self::PREPARATION => 'En préparation',
            self::IN_PROGRESS => 'En cours',
            self::PAUSED => 'En pause',
            self::COMPLETED => 'Terminée',
            self::ARCHIVED => 'Archivée',
        };
    }
}
