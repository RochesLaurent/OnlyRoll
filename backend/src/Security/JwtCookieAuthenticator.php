<?php

declare(strict_types=1);

namespace App\Security;

use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class JwtCookieAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private JWTTokenManagerInterface $jwtManager,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        // Ignorer les requêtes OPTIONS (preflight CORS)
        if ($request->isMethod('OPTIONS')) {
            return false;
        }

        // NE PAS supporter les routes de login/register
        $route = $request->attributes->get('_route');
        if (in_array($route, ['api_login', 'api_register'])) {
            return false;
        }

        // Supporter si on a un cookie JWT ou un header Authorization
        return $request->cookies->has('jwt_token') 
            || $request->headers->has('Authorization');
    }

    public function authenticate(Request $request): Passport
    {
        $token = null;

        // Priorité au cookie
        if ($request->cookies->has('jwt_token')) {
            $token = $request->cookies->get('jwt_token');
        } 
        // Sinon, vérifier le header Authorization
        elseif ($request->headers->has('Authorization')) {
            $authHeader = $request->headers->get('Authorization');
            if (preg_match('/Bearer\s+(.+)/', $authHeader, $matches)) {
                $token = $matches[1];
            }
        }

        if (!$token) {
            throw new CustomUserMessageAuthenticationException('No JWT token found');
        }

        try {
            // Décoder le token pour obtenir le username
            $payload = $this->jwtManager->parse($token);
            
            if (!$payload || !isset($payload['username'])) {
                throw new CustomUserMessageAuthenticationException('Invalid JWT token');
            }

            return new SelfValidatingPassport(
                new UserBadge($payload['username'])
            );

        } catch (\Exception $e) {
            throw new CustomUserMessageAuthenticationException('Invalid JWT token: ' . $e->getMessage());
        }
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Laisser la requête continuer normalement
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new Response(json_encode([
            'error' => $exception->getMessageKey()
        ]), Response::HTTP_UNAUTHORIZED, [
            'Content-Type' => 'application/json',
            'Access-Control-Allow-Origin' => 'http://localhost:5173',
            'Access-Control-Allow-Credentials' => 'true'
        ]);
    }
}