<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use DateTime;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Subscriber pour gérer le succès d'authentification JWT.
 */
final class AuthenticationSuccessSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly RequestStack $requestStack,
    ) {
    }

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

        $token = $data['token'] ?? null;

        if (!$token) {
            return;
        }

        // Récupérer le paramètre rememberMe depuis la requête
        $request = $this->requestStack->getCurrentRequest();
        $rememberMe = false;

        if ($request) {
            $requestData = json_decode($request->getContent(), true);
            $rememberMe = $requestData['rememberMe'] ?? false;
        }

        // Définir l'expiration du cookie selon rememberMe
        // Si rememberMe est true : 30 jours, sinon : 1 heure
        $expirationTime = $rememberMe ? '+30 days' : '+1 hour';

        $isProduction = ($_ENV['APP_ENV'] ?? 'dev') === 'prod';

        $cookie = Cookie::create('jwt_token')
            ->withValue($token)
            ->withExpires(new DateTime($expirationTime))
            ->withPath('/')
            ->withDomain(null)
            ->withSecure($isProduction)
            ->withHttpOnly(true)
            ->withSameSite(Cookie::SAMESITE_LAX);

        $response->headers->setCookie($cookie);

        $event->setData([
            'success' => true,
            'message' => 'Authentification réussie',
            'user' => $data['user'] ?? null,
        ]);
    }
}
