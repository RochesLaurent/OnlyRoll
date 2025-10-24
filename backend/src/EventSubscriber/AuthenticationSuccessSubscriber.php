<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use DateTime;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;

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

        $token = $data['token'] ?? null;

        if (!$token) {
            return;
        }

        $isProduction = ($_ENV['APP_ENV'] ?? 'dev') === 'prod';

        $cookie = Cookie::create('jwt_token')
            ->withValue($token)
            ->withExpires(new DateTime('+1 hour'))
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
