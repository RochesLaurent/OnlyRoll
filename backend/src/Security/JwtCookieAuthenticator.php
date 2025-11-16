<?php

declare(strict_types=1);

namespace App\Security;

use Exception;
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

/**
 * Authenticateur JWT via cookie httpOnly.
 */
final class JwtCookieAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private readonly JWTTokenManagerInterface $jwtManager,
    ) {
    }

    public function supports(Request $request): bool
    {
        if ($request->isMethod('OPTIONS')) {
            return false;
        }

        $route = $request->attributes->get('_route');
        if (\in_array($route, ['api_login', 'api_register'])) {
            return false;
        }

        return $request->cookies->has('jwt_token')
            || $request->headers->has('Authorization');
    }

    public function authenticate(Request $request): Passport
    {
        $token = null;

        if ($request->cookies->has('jwt_token')) {
            $token = $request->cookies->get('jwt_token');
        }
        elseif ($request->headers->has('Authorization')) {
            $authHeader = $request->headers->get('Authorization');
            if (\is_string($authHeader) && preg_match('/Bearer\s+(.+)/', $authHeader, $matches)) {
                $token = $matches[1];
            }
        }

        if (!\is_string($token) || empty($token)) {
            throw new CustomUserMessageAuthenticationException('No JWT token found');
        }

        try {
            $payload = $this->jwtManager->parse($token);
            if (!$payload || !isset($payload['username'])) {
                throw new CustomUserMessageAuthenticationException('Invalid JWT token');
            }

            return new SelfValidatingPassport(
                new UserBadge($payload['username']),
            );
        }
        catch (Exception $e) {
            throw new CustomUserMessageAuthenticationException('Invalid JWT token: ' . $e->getMessage());
        }
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $content = json_encode([
            'error' => $exception->getMessageKey(),
        ]);

        if (false === $content) {
            $content = '{"error":"Authentication failed"}';
        }

        return new Response($content, Response::HTTP_UNAUTHORIZED, [
            'Content-Type' => 'application/json',
            'Access-Control-Allow-Origin' => 'http://localhost:5173',
            'Access-Control-Allow-Credentials' => 'true',
        ]);
    }
}
