<?php

declare(strict_types=1);

namespace App\Exception\Game;

use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Exception levée lorsqu'un utilisateur n'a pas le droit d'accéder à une partie.
 * Code HTTP : 403 (Forbidden).
 */
final class AccessDeniedException extends GameException
{
    public function __construct(
        string $message = 'Accès refusé à cette partie',
        int $code = Response::HTTP_FORBIDDEN,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
