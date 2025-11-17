<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\Game;
use App\Entity\GameMessage;
use App\Entity\GamePlayer;
use App\Entity\User;
use App\Enum\PlayerStatus;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ChatControllerTest extends WebTestCase
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

        // Nettoyer la base de données avant chaque test
        $this->cleanDatabase();

        // Créer les utilisateurs de test
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

        // Créer une partie de test
        $this->game = new Game();
        $this->game->setName('Test Game');
        $this->game->setGameMaster($this->gameMaster);
        $this->game->setIsPublic(false);
        $this->game->setMaxPlayers(6);

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

    private function cleanDatabase(): void
    {
        // Supprimer toutes les données de test
        $connection = $this->entityManager->getConnection();
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 0');

        $tables = ['game_message', 'game_player', 'game_token', 'game_map', 'game', 'user'];
        foreach ($tables as $table) {
            $connection->executeStatement("TRUNCATE TABLE $table");
        }

        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
    }

    public function testGetMessagesWithoutAuthentication(): void
    {
        $this->client->request('GET', '/api/games/' . $this->game->getId() . '/chat/messages');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testGetMessagesForNonExistentGame(): void
    {
        $this->client->loginUser($this->player);
        $this->client->request('GET', '/api/games/99999/chat/messages');

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testGetMessagesWithoutPermission(): void
    {
        $outsider = new User();
        $outsider->setPseudo('outsider');
        $outsider->setEmail('outsider@test.com');
        $outsider->setPassword('password');
        $this->entityManager->persist($outsider);
        $this->entityManager->flush();

        $this->client->loginUser($outsider);
        $this->client->request('GET', '/api/games/' . $this->game->getId() . '/chat/messages');

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testGetMessagesSuccess(): void
    {
        // Créer quelques messages
        for ($i = 0; $i < 3; ++$i) {
            $message = new GameMessage();
            $message->setGame($this->game);
            $message->setUser($this->player);
            $message->setType(GameMessage::TYPE_CHAT);
            $message->setContent("Test message $i");
            $this->entityManager->persist($message);
        }
        $this->entityManager->flush();

        $this->client->loginUser($this->player);
        $this->client->request('GET', '/api/games/' . $this->game->getId() . '/chat/messages');

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(3, $data);
    }

    public function testGetMessagesWithLimit(): void
    {
        for ($i = 0; $i < 10; ++$i) {
            $message = new GameMessage();
            $message->setGame($this->game);
            $message->setUser($this->player);
            $message->setType(GameMessage::TYPE_CHAT);
            $message->setContent("Test message $i");
            $this->entityManager->persist($message);
        }
        $this->entityManager->flush();

        $this->client->loginUser($this->player);
        $this->client->request('GET', '/api/games/' . $this->game->getId() . '/chat/messages?limit=5');

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(5, $data);
    }

    public function testSendMessageSuccess(): void
    {
        $this->client->loginUser($this->player);
        $this->client->request(
            'POST',
            '/api/games/' . $this->game->getId() . '/chat/messages',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'type' => GameMessage::TYPE_CHAT,
                'content' => 'Hello everyone!',
                'isInCharacter' => false,
            ]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Hello everyone!', $data['content']);
    }

    public function testSendMessageWithInvalidData(): void
    {
        $this->client->loginUser($this->player);
        $this->client->request(
            'POST',
            '/api/games/' . $this->game->getId() . '/chat/messages',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'type' => 'invalid_type',
                'content' => '',
            ]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testGetMessagesByType(): void
    {
        $chatMessage = new GameMessage();
        $chatMessage->setGame($this->game);
        $chatMessage->setUser($this->player);
        $chatMessage->setType(GameMessage::TYPE_CHAT);
        $chatMessage->setContent('Chat message');

        $systemMessage = new GameMessage();
        $systemMessage->setGame($this->game);
        $systemMessage->setUser($this->player);
        $systemMessage->setType(GameMessage::TYPE_SYSTEM);
        $systemMessage->setContent('System message');

        $this->entityManager->persist($chatMessage);
        $this->entityManager->persist($systemMessage);
        $this->entityManager->flush();

        $this->client->loginUser($this->player);
        $this->client->request(
            'GET',
            '/api/games/' . $this->game->getId() . '/chat/messages/type/' . GameMessage::TYPE_CHAT,
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(GameMessage::TYPE_CHAT, $data[0]['type']);
    }

    public function testRollDiceSuccess(): void
    {
        $this->client->loginUser($this->player);
        $this->client->request(
            'POST',
            '/api/games/' . $this->game->getId() . '/chat/roll-dice',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['formula' => '2d6+3']),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(GameMessage::TYPE_DICE_ROLL, $data['type']);
        $this->assertArrayHasKey('diceResult', $data);
        $this->assertArrayHasKey('total', $data['diceResult']);
    }

    public function testRollDiceWithInvalidFormula(): void
    {
        $this->client->loginUser($this->player);
        $this->client->request(
            'POST',
            '/api/games/' . $this->game->getId() . '/chat/roll-dice',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['formula' => 'invalid']),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testRollDiceWithoutFormula(): void
    {
        $this->client->loginUser($this->player);
        $this->client->request(
            'POST',
            '/api/games/' . $this->game->getId() . '/chat/roll-dice',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testRollDiceWithTooManyDice(): void
    {
        $this->client->loginUser($this->player);
        $this->client->request(
            'POST',
            '/api/games/' . $this->game->getId() . '/chat/roll-dice',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['formula' => '101d6']),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testGetStatsAsGameMaster(): void
    {
        $message = new GameMessage();
        $message->setGame($this->game);
        $message->setUser($this->player);
        $message->setType(GameMessage::TYPE_CHAT);
        $message->setContent('Test');
        $this->entityManager->persist($message);
        $this->entityManager->flush();

        $this->client->loginUser($this->gameMaster);
        $this->client->request('GET', '/api/games/' . $this->game->getId() . '/chat/stats');

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('stats', $data);
        $this->assertArrayHasKey('total', $data);
    }

    public function testGetStatsAsPlayerForbidden(): void
    {
        $this->client->loginUser($this->player);
        $this->client->request('GET', '/api/games/' . $this->game->getId() . '/chat/stats');

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testGetMessagesSinceSuccess(): void
    {
        $oldMessage = new GameMessage();
        $oldMessage->setGame($this->game);
        $oldMessage->setUser($this->player);
        $oldMessage->setType(GameMessage::TYPE_CHAT);
        $oldMessage->setContent('Old message');
        $this->entityManager->persist($oldMessage);
        $this->entityManager->flush();

        $since = new DateTimeImmutable();
        sleep(1);

        $newMessage = new GameMessage();
        $newMessage->setGame($this->game);
        $newMessage->setUser($this->player);
        $newMessage->setType(GameMessage::TYPE_CHAT);
        $newMessage->setContent('New message');
        $this->entityManager->persist($newMessage);
        $this->entityManager->flush();

        $this->client->loginUser($this->player);
        $this->client->request(
            'GET',
            '/api/games/' . $this->game->getId() . '/chat/messages/since?since=' . urlencode($since->format('c')),
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertGreaterThanOrEqual(1, \count($data));
    }

    public function testGetMessagesSinceWithoutParameter(): void
    {
        $this->client->loginUser($this->player);
        $this->client->request('GET', '/api/games/' . $this->game->getId() . '/chat/messages/since');

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testGetMessagesSinceWithInvalidDate(): void
    {
        $this->client->loginUser($this->player);
        $this->client->request(
            'GET',
            '/api/games/' . $this->game->getId() . '/chat/messages/since?since=invalid-date',
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testGetDiceRolls(): void
    {
        $diceMessage = new GameMessage();
        $diceMessage->setGame($this->game);
        $diceMessage->setUser($this->player);
        $diceMessage->setType(GameMessage::TYPE_DICE_ROLL);
        $diceMessage->setContent('2d6+3');
        $diceMessage->setDiceResult(['total' => 10, 'rolls' => [3, 4], 'modifier' => 3]);

        $chatMessage = new GameMessage();
        $chatMessage->setGame($this->game);
        $chatMessage->setUser($this->player);
        $chatMessage->setType(GameMessage::TYPE_CHAT);
        $chatMessage->setContent('Chat');

        $this->entityManager->persist($diceMessage);
        $this->entityManager->persist($chatMessage);
        $this->entityManager->flush();

        $this->client->loginUser($this->player);
        $this->client->request('GET', '/api/games/' . $this->game->getId() . '/chat/dice-rolls');

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(1, $data);
        $this->assertEquals(GameMessage::TYPE_DICE_ROLL, $data[0]['type']);
    }
}
