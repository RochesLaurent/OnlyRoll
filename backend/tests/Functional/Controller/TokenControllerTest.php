<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\Game;
use App\Entity\GameMap;
use App\Entity\GamePlayer;
use App\Entity\GameToken;
use App\Entity\User;
use App\Enum\PlayerStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class TokenControllerTest extends WebTestCase
{
    private $client;

    private EntityManagerInterface $entityManager;

    private User $gameMaster;

    private User $player;

    private Game $game;

    private GameMap $map;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = $this->client->getContainer()->get('doctrine')->getManager();

        $this->gameMaster = new User();
        $this->gameMaster->setPseudo('gamemaster');
        $this->gameMaster->setEmail('gm@test.com');
        $this->gameMaster->setPassword('password');

        $this->player = new User();
        $this->player->setPseudo('player1');
        $this->player->setEmail('player@test.com');
        $this->player->setPassword('password');

        $this->entityManager->persist($this->gameMaster);
        $this->entityManager->persist($this->player);

        $this->game = new Game();
        $this->game->setName('Test Game');
        $this->game->setGameMaster($this->gameMaster);
        $this->game->setIsPublic(false);

        $gamePlayer = new GamePlayer();
        $gamePlayer->setGame($this->game);
        $gamePlayer->setUser($this->player);
        $gamePlayer->setStatus(PlayerStatus::ACTIVE);

        $this->game->addGamePlayer($gamePlayer);

        $this->map = new GameMap();
        $this->map->setGame($this->game);
        $this->map->setName('Test Map');
        $this->map->setWidth(20);
        $this->map->setHeight(20);
        $this->map->setIsActive(true);

        $this->entityManager->persist($this->game);
        $this->entityManager->persist($this->map);
        $this->entityManager->flush();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }

    public function testListTokensWithoutAuthentication(): void
    {
        $this->client->request('GET', $this->getTokenUrl());
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testListTokensForNonExistentGame(): void
    {
        $this->client->loginUser($this->player);
        $this->client->request('GET', '/api/games/99999/maps/' . $this->map->getId() . '/tokens');
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testListTokensSuccess(): void
    {
        $token1 = $this->createToken('Token 1', true);
        $token2 = $this->createToken('Token 2', true);

        $this->client->loginUser($this->player);
        $this->client->request('GET', $this->getTokenUrl());

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(2, $data);
    }

    public function testListTokensPlayerSeesOnlyVisible(): void
    {
        $visibleToken = $this->createToken('Visible', true);
        $hiddenToken = $this->createToken('Hidden', false);

        $this->client->loginUser($this->player);
        $this->client->request('GET', $this->getTokenUrl());

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(1, $data);
        $this->assertEquals('Visible', $data[0]['name']);
    }

    public function testListTokensGameMasterSeesAll(): void
    {
        $visibleToken = $this->createToken('Visible', true);
        $hiddenToken = $this->createToken('Hidden', false);

        $this->client->loginUser($this->gameMaster);
        $this->client->request('GET', $this->getTokenUrl());

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(2, $data);
    }

    public function testShowTokenSuccess(): void
    {
        $token = $this->createToken('Test Token', true);

        $this->client->loginUser($this->player);
        $this->client->request('GET', $this->getTokenUrl($token->getId()));

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Test Token', $data['name']);
    }

    public function testShowHiddenTokenAsPlayerForbidden(): void
    {
        $token = $this->createToken('Hidden Token', false);

        $this->client->loginUser($this->player);
        $this->client->request('GET', $this->getTokenUrl($token->getId()));

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testShowHiddenTokenAsGameMasterSuccess(): void
    {
        $token = $this->createToken('Hidden Token', false);

        $this->client->loginUser($this->gameMaster);
        $this->client->request('GET', $this->getTokenUrl($token->getId()));

        $this->assertResponseIsSuccessful();
    }

    public function testCreateTokenAsPlayerForbidden(): void
    {
        $this->client->loginUser($this->player);
        $this->client->request(
            'POST',
            $this->getTokenUrl(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'name' => 'New Token',
                'type' => 'character',
                'x' => 5,
                'y' => 5,
            ]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testCreateTokenAsGameMasterSuccess(): void
    {
        $this->client->loginUser($this->gameMaster);
        $this->client->request(
            'POST',
            $this->getTokenUrl(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'name' => 'New Token',
                'type' => 'character',
                'x' => 5,
                'y' => 10,
                'size' => 1.0,
                'isVisible' => true,
            ]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('New Token', $data['name']);
        $this->assertEquals(5, $data['x']);
        $this->assertEquals(10, $data['y']);
    }

    public function testCreateTokenWithInvalidData(): void
    {
        $this->client->loginUser($this->gameMaster);
        $this->client->request(
            'POST',
            $this->getTokenUrl(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'name' => '', // Nom vide
                'type' => 'invalid_type',
                'x' => -1, // Position négative
            ]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testMoveTokenSuccess(): void
    {
        $token = $this->createToken('Movable Token', true);

        $this->client->loginUser($this->player);
        $this->client->request(
            'POST',
            $this->getTokenUrl($token->getId()) . '/move',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['x' => 15, 'y' => 20]),
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(15, $data['x']);
        $this->assertEquals(20, $data['y']);
    }

    public function testMoveLockedTokenFails(): void
    {
        $token = $this->createToken('Locked Token', true, 5, 5, true);

        $this->client->loginUser($this->player);
        $this->client->request(
            'POST',
            $this->getTokenUrl($token->getId()) . '/move',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['x' => 15, 'y' => 20]),
        );

        // Le token ne doit pas bouger
        $this->entityManager->refresh($token);
        $this->assertEquals(5, $token->getX());
        $this->assertEquals(5, $token->getY());
    }

    public function testToggleVisibilityAsGameMasterSuccess(): void
    {
        $token = $this->createToken('Test Token', true);

        $this->client->loginUser($this->gameMaster);
        $this->client->request('POST', $this->getTokenUrl($token->getId()) . '/toggle-visibility');

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertFalse($data['isVisible']);
    }

    public function testToggleVisibilityAsPlayerForbidden(): void
    {
        $token = $this->createToken('Test Token', true);

        $this->client->loginUser($this->player);
        $this->client->request('POST', $this->getTokenUrl($token->getId()) . '/toggle-visibility');

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testToggleLockAsGameMasterSuccess(): void
    {
        $token = $this->createToken('Test Token', true);

        $this->client->loginUser($this->gameMaster);
        $this->client->request('POST', $this->getTokenUrl($token->getId()) . '/toggle-lock');

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($data['isLocked']);
    }

    public function testToggleLockAsPlayerForbidden(): void
    {
        $token = $this->createToken('Test Token', true);

        $this->client->loginUser($this->player);
        $this->client->request('POST', $this->getTokenUrl($token->getId()) . '/toggle-lock');

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testDeleteTokenAsGameMasterSuccess(): void
    {
        $token = $this->createToken('Token to Delete', true);
        $tokenId = $token->getId();

        $this->client->loginUser($this->gameMaster);
        $this->client->request('DELETE', $this->getTokenUrl($tokenId));

        $this->assertResponseIsSuccessful();

        $deletedToken = $this->entityManager->getRepository(GameToken::class)->find($tokenId);
        $this->assertNull($deletedToken);
    }

    public function testDeleteTokenAsPlayerForbidden(): void
    {
        $token = $this->createToken('Test Token', true);

        $this->client->loginUser($this->player);
        $this->client->request('DELETE', $this->getTokenUrl($token->getId()));

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testTokenBelongsToCorrectMap(): void
    {
        $otherMap = new GameMap();
        $otherMap->setGame($this->game);
        $otherMap->setName('Other Map');
        $otherMap->setWidth(20);
        $otherMap->setHeight(20);
        $this->entityManager->persist($otherMap);

        $token = new GameToken();
        $token->setMap($otherMap);
        $token->setName('Other Map Token');
        $token->setType('character');
        $token->setX(5);
        $token->setY(5);
        $this->entityManager->persist($token);
        $this->entityManager->flush();

        $this->client->loginUser($this->gameMaster);
        $this->client->request('GET', $this->getTokenUrl($token->getId()));

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    private function getTokenUrl(?int $tokenId = null): string
    {
        $base = '/api/games/' . $this->game->getId() . '/maps/' . $this->map->getId() . '/tokens';

        return $tokenId ? $base . '/' . $tokenId : $base;
    }

    private function createToken(
        string $name,
        bool $visible,
        int $x = 5,
        int $y = 5,
        bool $locked = false,
    ): GameToken {
        $token = new GameToken();
        $token->setMap($this->map);
        $token->setName($name);
        $token->setType('character');
        $token->setX($x);
        $token->setY($y);
        $token->setIsVisible($visible);
        $token->setIsLocked($locked);
        $this->entityManager->persist($token);
        $this->entityManager->flush();

        return $token;
    }
}
