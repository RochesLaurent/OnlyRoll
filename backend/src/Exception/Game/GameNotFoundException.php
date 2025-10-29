<?php

declare(strict_types=1);

namespace App\Exception\Game;

use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Exception levée lorsqu'une partie n'est pas trouvée.
 * Code HTTP : 404 (Not Found).
 */
final class GameNotFoundException extends GameException
{
    public function __construct(
        string $message = 'Partie introuvable',
        int $code = Response::HTTP_NOT_FOUND,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
