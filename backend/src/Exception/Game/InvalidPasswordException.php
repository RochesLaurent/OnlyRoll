<?php

declare(strict_types=1);

namespace App\Exception\Game;

use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Exception levée lorsqu'un mot de passe de partie est incorrect.
 * Code HTTP : 401 (Unauthorized).
 */
final class InvalidPasswordException extends GameException
{
    public function __construct(
        string $message = 'Mot de passe incorrect',
        int $code = Response::HTTP_UNAUTHORIZED,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
