<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class GameControllerTest extends WebTestCase
{
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->token = $this->getAuthToken();
    }

    public function testCreateGame(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/games',
            server: ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->token],
            content: json_encode([
                'name' => 'My Epic Campaign',
                'description' => 'A great adventure awaits!',
                'maxPlayers' => 6,
                'isPublic' => true,
            ])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('My Epic Campaign', $data['name']);
        $this->assertArrayHasKey('inviteCode', $data);
    }

    public function testListPublicGames(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/games',
            server: ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->token]
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');
    }

    public function testJoinGame(): void
    {
        $client = static::createClient();

        // Créer d'abord une partie
        $client->request('POST', '/api/games',
            server: ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->token],
            content: json_encode([
                'name' => 'Joinable Game',
                'isPublic' => true,
                'maxPlayers' => 6,
            ])
        );

        $gameData = json_decode($client->getResponse()->getContent(), true);
        $gameId = $gameData['id'];

        // Créer un second user et le faire rejoindre
        $secondUserToken = $this->createSecondUser();

        $client->request('POST', "/api/games/{$gameId}/join",
            server: ['HTTP_AUTHORIZATION' => 'Bearer ' . $secondUserToken]
        );

        $this->assertResponseIsSuccessful();
    }

    private function getAuthToken(): string
    {
        $client = static::createClient();
        $container = static::getContainer();

        // Créer un user de test
        $em = $container->get('doctrine')->getManager();

        $user = new User();
        $user->setPseudo('testuser')
             ->setEmail('test@example.com')
             ->setPassword(password_hash('password', PASSWORD_BCRYPT))
             ->setRoles(['ROLE_USER'])
             ->setIsVerified(true);

        $em->persist($user);
        $em->flush();

        // Générer le token JWT
        $jwtManager = $container->get(JWTTokenManagerInterface::class);

        return $jwtManager->create($user);
    }

    private function createSecondUser(): string
    {
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();

        $user = new User();
        $user->setPseudo('player2')
             ->setEmail('player2@example.com')
             ->setPassword(password_hash('password', PASSWORD_BCRYPT))
             ->setRoles(['ROLE_USER'])
             ->setIsVerified(true);

        $em->persist($user);
        $em->flush();

        $jwtManager = $container->get(JWTTokenManagerInterface::class);

        return $jwtManager->create($user);
    }
}
