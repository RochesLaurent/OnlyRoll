<?php

namespace App\Exception\Game;

use Symfony\Component\HttpFoundation\Response;

/**
 * Exception levée lorsqu'une partie est déjà complète (nombre maximal de joueurs atteint).
 * Code HTTP : 409 (Conflict)
 */
final class GameFullException extends GameException
{
    public function __construct(
        string $message = 'Cette partie est complète',
        int $code = Response::HTTP_CONFLICT,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}