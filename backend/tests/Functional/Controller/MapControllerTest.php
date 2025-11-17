<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\Game;
use App\Entity\GameMap;
use App\Entity\GamePlayer;
use App\Entity\User;
use App\Enum\PlayerStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class MapControllerTest extends WebTestCase
{
    private $client;

    private EntityManagerInterface $entityManager;

    private User $gameMaster;

    private User $player;

    private Game $game;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = $this->client->getContainer()->get('doctrine')->getManager();

        // Clear database before each test
        $connection = $this->entityManager->getConnection();
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS=0');

        // Truncate all relevant tables
        $connection->executeStatement('TRUNCATE TABLE game_player');
        $connection->executeStatement('TRUNCATE TABLE game_map');
        $connection->executeStatement('TRUNCATE TABLE game');
        $connection->executeStatement('TRUNCATE TABLE user');

        $connection->executeStatement('SET FOREIGN_KEY_CHECKS=1');

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
        $this->entityManager->persist($this->game);
        $this->entityManager->flush();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }

    public function testListMapsWithoutAuthentication(): void
    {
        $this->client->request('GET', '/api/games/' . $this->game->getId() . '/maps');
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testListMapsForNonExistentGame(): void
    {
        $this->client->loginUser($this->player);
        $this->client->request('GET', '/api/games/99999/maps');
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testListMapsSuccess(): void
    {
        $map1 = new GameMap();
        $map1->setGame($this->game);
        $map1->setName('Map 1');
        $map1->setWidth(20);
        $map1->setHeight(20);

        $map2 = new GameMap();
        $map2->setGame($this->game);
        $map2->setName('Map 2');
        $map2->setWidth(15);
        $map2->setHeight(15);

        $this->entityManager->persist($map1);
        $this->entityManager->persist($map2);
        $this->entityManager->flush();

        $this->client->loginUser($this->player);
        $this->client->request('GET', '/api/games/' . $this->game->getId() . '/maps');

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(2, $data);
    }

    public function testGetActiveMapWhenNoActiveMap(): void
    {
        $this->client->loginUser($this->player);
        $this->client->request('GET', '/api/games/' . $this->game->getId() . '/maps/active');
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testGetActiveMapSuccess(): void
    {
        $map = new GameMap();
        $map->setGame($this->game);
        $map->setName('Active Map');
        $map->setWidth(20);
        $map->setHeight(20);
        $map->setIsActive(true);

        $this->entityManager->persist($map);
        $this->entityManager->flush();

        $this->client->loginUser($this->player);
        $this->client->request('GET', '/api/games/' . $this->game->getId() . '/maps/active');

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Active Map', $data['name']);
        $this->assertTrue($data['isActive']);
    }

    public function testShowMapSuccess(): void
    {
        $map = new GameMap();
        $map->setGame($this->game);
        $map->setName('Test Map');
        $map->setWidth(20);
        $map->setHeight(20);
        $this->entityManager->persist($map);
        $this->entityManager->flush();

        $this->client->loginUser($this->player);
        $this->client->request('GET', '/api/games/' . $this->game->getId() . '/maps/' . $map->getId());

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Test Map', $data['name']);
    }

    public function testShowMapNotFound(): void
    {
        $this->client->loginUser($this->player);
        $this->client->request('GET', '/api/games/' . $this->game->getId() . '/maps/99999');
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testCreateMapAsPlayerForbidden(): void
    {
        $this->client->loginUser($this->player);
        $this->client->request(
            'POST',
            '/api/games/' . $this->game->getId() . '/maps',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'name' => 'New Map',
                'width' => 20,
                'height' => 20,
            ]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testCreateMapAsGameMasterSuccess(): void
    {
        $this->client->loginUser($this->gameMaster);
        $this->client->request(
            'POST',
            '/api/games/' . $this->game->getId() . '/maps',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'name' => 'New Map',
                'description' => 'Test description',
                'width' => 25,
                'height' => 25,
                'gridSize' => 50,
                'gridType' => 'square',
            ]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('New Map', $data['name']);
        $this->assertEquals(25, $data['width']);
        $this->assertEquals(25, $data['height']);
    }

    public function testCreateMapWithInvalidData(): void
    {
        $this->client->loginUser($this->gameMaster);
        $this->client->request(
            'POST',
            '/api/games/' . $this->game->getId() . '/maps',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'name' => 'A', // Trop court
                'width' => 1,  // Trop petit
                'height' => 250, // Trop grand
            ]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testUpdateMapAsGameMasterSuccess(): void
    {
        $map = new GameMap();
        $map->setGame($this->game);
        $map->setName('Original Name');
        $map->setWidth(20);
        $map->setHeight(20);
        $this->entityManager->persist($map);
        $this->entityManager->flush();

        $this->client->loginUser($this->gameMaster);
        $this->client->request(
            'PUT',
            '/api/games/' . $this->game->getId() . '/maps/' . $map->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'name' => 'Updated Name',
                'description' => 'New description',
            ]),
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Updated Name', $data['name']);
    }

    public function testUpdateMapAsPlayerForbidden(): void
    {
        $map = new GameMap();
        $map->setGame($this->game);
        $map->setName('Test Map');
        $map->setWidth(20);
        $map->setHeight(20);
        $this->entityManager->persist($map);
        $this->entityManager->flush();

        $this->client->loginUser($this->player);
        $this->client->request(
            'PUT',
            '/api/games/' . $this->game->getId() . '/maps/' . $map->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['name' => 'Hacked Name']),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testActivateMapSuccess(): void
    {
        $map1 = new GameMap();
        $map1->setGame($this->game);
        $map1->setName('Map 1');
        $map1->setWidth(20);
        $map1->setHeight(20);
        $map1->setIsActive(true);

        $map2 = new GameMap();
        $map2->setGame($this->game);
        $map2->setName('Map 2');
        $map2->setWidth(20);
        $map2->setHeight(20);
        $map2->setIsActive(false);

        $this->entityManager->persist($map1);
        $this->entityManager->persist($map2);
        $this->entityManager->flush();

        $this->client->loginUser($this->gameMaster);
        $this->client->request(
            'POST',
            '/api/games/' . $this->game->getId() . '/maps/' . $map2->getId() . '/activate',
        );

        $this->assertResponseIsSuccessful();

        // Vérifier que map2 est active et map1 ne l'est plus
        $this->entityManager->refresh($map1);
        $this->entityManager->refresh($map2);
        $this->assertTrue($map2->isActive());
        $this->assertFalse($map1->isActive());
    }

    public function testActivateMapAsPlayerForbidden(): void
    {
        $map = new GameMap();
        $map->setGame($this->game);
        $map->setName('Test Map');
        $map->setWidth(20);
        $map->setHeight(20);
        $this->entityManager->persist($map);
        $this->entityManager->flush();

        $this->client->loginUser($this->player);
        $this->client->request(
            'POST',
            '/api/games/' . $this->game->getId() . '/maps/' . $map->getId() . '/activate',
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testDeleteMapSuccess(): void
    {
        $map = new GameMap();
        $map->setGame($this->game);
        $map->setName('Map to Delete');
        $map->setWidth(20);
        $map->setHeight(20);
        $this->entityManager->persist($map);
        $this->entityManager->flush();

        $mapId = $map->getId();

        $this->client->loginUser($this->gameMaster);
        $this->client->request(
            'DELETE',
            '/api/games/' . $this->game->getId() . '/maps/' . $mapId,
        );

        $this->assertResponseIsSuccessful();

        // Vérifier que la map est supprimée
        $deletedMap = $this->entityManager->getRepository(GameMap::class)->find($mapId);
        $this->assertNull($deletedMap);
    }

    public function testDeleteMapAsPlayerForbidden(): void
    {
        $map = new GameMap();
        $map->setGame($this->game);
        $map->setName('Test Map');
        $map->setWidth(20);
        $map->setHeight(20);
        $this->entityManager->persist($map);
        $this->entityManager->flush();

        $this->client->loginUser($this->player);
        $this->client->request(
            'DELETE',
            '/api/games/' . $this->game->getId() . '/maps/' . $map->getId(),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testMapBelongsToCorrectGame(): void
    {
        // Créer un autre jeu
        $otherGame = new Game();
        $otherGame->setName('Other Game');
        $otherGame->setGameMaster($this->gameMaster);
        $this->entityManager->persist($otherGame);

        $map = new GameMap();
        $map->setGame($otherGame);
        $map->setName('Other Game Map');
        $map->setWidth(20);
        $map->setHeight(20);
        $this->entityManager->persist($map);
        $this->entityManager->flush();

        // Tenter d'accéder à la map via le mauvais jeu
        $this->client->loginUser($this->gameMaster);
        $this->client->request('GET', '/api/games/' . $this->game->getId() . '/maps/' . $map->getId());

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}
