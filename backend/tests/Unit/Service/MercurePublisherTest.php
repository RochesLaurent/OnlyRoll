<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\MercurePublisher;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class MercurePublisherTest extends TestCase
{
    private HubInterface&MockObject $hub;

    private LoggerInterface&MockObject $logger;

    private MercurePublisher $mercurePublisher;

    protected function setUp(): void
    {
        $this->hub = $this->createMock(HubInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->mercurePublisher = new MercurePublisher(
            $this->hub,
            $this->logger,
        );
    }

    public function testPublishGameEventSuccess(): void
    {
        $gameId = 1;
        $eventType = 'chat';
        $data = ['message' => 'Hello'];

        $this->hub->expects($this->once())
            ->method('publish')
            ->with($this->callback(function (Update $update) use ($gameId, $eventType) {
                $topics = $update->getTopics();
                $this->assertContains("game/{$gameId}/{$eventType}", $topics);

                $data = json_decode($update->getData(), true);
                $this->assertEquals($eventType, $data['type']);
                $this->assertEquals($gameId, $data['gameId']);
                $this->assertArrayHasKey('timestamp', $data);

                return true;
            }));

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Mercure event published', $this->anything());

        $result = $this->mercurePublisher->publishGameEvent($gameId, $eventType, $data);

        $this->assertTrue($result);
    }

    public function testPublishGameEventWithPrivateFlag(): void
    {
        $this->hub->expects($this->once())
            ->method('publish')
            ->with($this->callback(function (Update $update) {
                $this->assertTrue($update->isPrivate());

                return true;
            }));

        $this->logger->expects($this->once())
            ->method('info');

        $result = $this->mercurePublisher->publishGameEvent(1, 'chat', [], true);

        $this->assertTrue($result);
    }

    public function testPublishGameEventFailure(): void
    {
        $this->hub->expects($this->once())
            ->method('publish')
            ->willThrowException(new Exception('Connection failed'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Failed to publish Mercure event', $this->callback(function ($context) {
                $this->assertArrayHasKey('error', $context);
                $this->assertEquals('Connection failed', $context['error']);

                return true;
            }));

        $result = $this->mercurePublisher->publishGameEvent(1, 'chat', []);

        $this->assertFalse($result);
    }

    public function testPublishChatMessage(): void
    {
        $gameId = 5;
        $messageData = [
            'messageId' => 123,
            'userId' => 1,
            'userName' => 'Player1',
            'content' => 'Hello world',
        ];

        $this->hub->expects($this->once())
            ->method('publish')
            ->with($this->callback(function (Update $update) {
                $topics = $update->getTopics();
                $this->assertContains('game/5/chat', $topics);

                $data = json_decode($update->getData(), true);
                $this->assertEquals('chat', $data['type']);
                $this->assertEquals(123, $data['data']['messageId']);

                return true;
            }));

        $this->logger->expects($this->once())
            ->method('info');

        $result = $this->mercurePublisher->publishChatMessage($gameId, $messageData);

        $this->assertTrue($result);
    }

    public function testPublishTokenMove(): void
    {
        $tokenData = [
            'tokenId' => 10,
            'mapId' => 2,
            'x' => 15,
            'y' => 20,
        ];

        $this->hub->expects($this->once())
            ->method('publish')
            ->with($this->callback(function (Update $update) {
                $topics = $update->getTopics();
                $this->assertContains('game/1/token', $topics);

                return true;
            }));

        $this->logger->expects($this->once())
            ->method('info');

        $result = $this->mercurePublisher->publishTokenMove(1, $tokenData);

        $this->assertTrue($result);
    }

    public function testPublishTokenCreated(): void
    {
        $tokenData = ['tokenId' => 42, 'name' => 'Hero'];

        $this->hub->expects($this->once())
            ->method('publish')
            ->with($this->callback(function (Update $update) {
                $data = json_decode($update->getData(), true);
                $this->assertEquals('created', $data['data']['action']);
                $this->assertEquals(42, $data['data']['token']['tokenId']);

                return true;
            }));

        $this->logger->expects($this->once())
            ->method('info');

        $result = $this->mercurePublisher->publishTokenCreated(1, $tokenData);

        $this->assertTrue($result);
    }

    public function testPublishTokenDeleted(): void
    {
        $this->hub->expects($this->once())
            ->method('publish')
            ->with($this->callback(function (Update $update) {
                $data = json_decode($update->getData(), true);
                $this->assertEquals('deleted', $data['data']['action']);
                $this->assertEquals(99, $data['data']['tokenId']);

                return true;
            }));

        $this->logger->expects($this->once())
            ->method('info');

        $result = $this->mercurePublisher->publishTokenDeleted(1, 99);

        $this->assertTrue($result);
    }

    public function testPublishMapChange(): void
    {
        $mapData = ['mapId' => 3, 'name' => 'Dungeon Map'];

        $this->hub->expects($this->once())
            ->method('publish')
            ->with($this->callback(function (Update $update) {
                $topics = $update->getTopics();
                $this->assertContains('game/1/map', $topics);

                return true;
            }));

        $this->logger->expects($this->once())
            ->method('info');

        $result = $this->mercurePublisher->publishMapChange(1, $mapData);

        $this->assertTrue($result);
    }

    public function testPublishDiceRoll(): void
    {
        $diceData = ['expression' => '2d6', 'total' => 7];

        $this->hub->expects($this->once())
            ->method('publish')
            ->with($this->callback(function (Update $update) {
                $topics = $update->getTopics();
                $this->assertContains('game/1/dice', $topics);

                return true;
            }));

        $this->logger->expects($this->once())
            ->method('info');

        $result = $this->mercurePublisher->publishDiceRoll(1, $diceData);

        $this->assertTrue($result);
    }

    public function testPublishPlayerEvent(): void
    {
        $playerData = ['userId' => 5, 'action' => 'joined'];

        $this->hub->expects($this->once())
            ->method('publish')
            ->with($this->callback(function (Update $update) {
                $topics = $update->getTopics();
                $this->assertContains('game/1/player', $topics);

                return true;
            }));

        $this->logger->expects($this->once())
            ->method('info');

        $result = $this->mercurePublisher->publishPlayerEvent(1, $playerData);

        $this->assertTrue($result);
    }

    public function testPublishSystemEvent(): void
    {
        $systemData = ['status' => 'started'];

        $this->hub->expects($this->once())
            ->method('publish')
            ->with($this->callback(function (Update $update) {
                $topics = $update->getTopics();
                $this->assertContains('game/1/system', $topics);

                return true;
            }));

        $this->logger->expects($this->once())
            ->method('info');

        $result = $this->mercurePublisher->publishSystemEvent(1, $systemData);

        $this->assertTrue($result);
    }

    public function testTopicFormatting(): void
    {
        $this->hub->expects($this->once())
            ->method('publish')
            ->with($this->callback(function (Update $update) {
                $topics = $update->getTopics();
                $this->assertEquals(['game/42/custom'], $topics);

                return true;
            }));

        $this->logger->expects($this->once())
            ->method('info');

        $this->mercurePublisher->publishGameEvent(42, 'custom', []);
    }

    public function testPayloadStructure(): void
    {
        $data = ['key' => 'value'];

        $this->hub->expects($this->once())
            ->method('publish')
            ->with($this->callback(function (Update $update) use ($data) {
                $payload = json_decode($update->getData(), true);

                $this->assertArrayHasKey('type', $payload);
                $this->assertArrayHasKey('gameId', $payload);
                $this->assertArrayHasKey('data', $payload);
                $this->assertArrayHasKey('timestamp', $payload);

                $this->assertEquals('test', $payload['type']);
                $this->assertEquals(1, $payload['gameId']);
                $this->assertEquals($data, $payload['data']);

                // Vérifier format ISO 8601
                $this->assertMatchesRegularExpression(
                    '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\+\d{2}:\d{2}$/',
                    $payload['timestamp'],
                );

                return true;
            }));

        $this->logger->expects($this->once())
            ->method('info');

        $this->mercurePublisher->publishGameEvent(1, 'test', $data);
    }
}
