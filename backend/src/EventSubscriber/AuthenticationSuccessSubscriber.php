<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * Subscriber pour gérer le succès d'authentification JWT.
 * Place le JWT dans un cookie HttpOnly au lieu du body JSON.
 */
class AuthenticationSuccessSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            Events::AUTHENTICATION_SUCCESS => 'onAuthenticationSuccess',
        ];
    }

    public function onAuthenticationSuccess(AuthenticationSuccessEvent $event): void
    {
        $data = $event->getData();
        $response = $event->getResponse();

        // Récupérer le token JWT du body
        $token = $data['token'] ?? null;

        if (!$token) {
            return;
        }

        // Déterminer si on est en production
        $isProduction = ($_ENV['APP_ENV'] ?? 'dev') === 'prod';

        // Créer un cookie HttpOnly avec le JWT
        $cookie = Cookie::create('jwt_token')
            ->withValue($token)
            ->withExpires(new \DateTime('+1 hour')) // Durée de validité du token
            ->withPath('/')
            ->withDomain(null) // Domaine automatique
            ->withSecure($isProduction) // HTTPS uniquement en production
            ->withHttpOnly(true) // Inaccessible en JavaScript (sécurité XSS)
            ->withSameSite(Cookie::SAMESITE_LAX); // LAX au lieu de STRICT pour cross-origin

        $response->headers->setCookie($cookie);

        // Retirer le token du body JSON (optionnel mais recommandé pour sécurité)
        // On laisse juste un message de succès
        $event->setData([
            'success' => true,
            'message' => 'Authentification réussie',
            'user' => $data['user'] ?? null,
        ]);
    }
}
