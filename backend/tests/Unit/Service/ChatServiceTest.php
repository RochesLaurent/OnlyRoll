<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\DTO\Chat\SendMessageDTO;
use App\Entity\Game;
use App\Entity\GameMessage;
use App\Entity\User;
use App\Enum\MessageType;
use App\Repository\GameMessageRepository;
use App\Repository\UserRepository;
use App\Service\ChatService;
use App\Service\MercurePublisher;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ChatServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;

    private GameMessageRepository&MockObject $messageRepository;

    private UserRepository&MockObject $userRepository;

    private MercurePublisher&MockObject $mercurePublisher;

    private ChatService $chatService;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->messageRepository = $this->createMock(GameMessageRepository::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->mercurePublisher = $this->createMock(MercurePublisher::class);

        $this->chatService = new ChatService(
            $this->entityManager,
            $this->messageRepository,
            $this->userRepository,
            $this->mercurePublisher,
        );
    }

    public function testSendMessageSuccess(): void
    {
        $game = $this->createMock(Game::class);
        $game->method('getId')->willReturn(1);

        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(1);
        $user->method('getPseudo')->willReturn('Player1');

        $dto = new SendMessageDTO();
        $dto->type = MessageType::CHAT;
        $dto->content = 'Hello everyone!';
        $dto->isInCharacter = false;

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(GameMessage::class));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->mercurePublisher->expects($this->once())
            ->method('publishChatMessage');

        $message = $this->chatService->sendMessage($game, $user, $dto);

        $this->assertInstanceOf(GameMessage::class, $message);
    }

    public function testSendWhisperWithoutRecipientThrowsException(): void
    {
        $game = $this->createMock(Game::class);
        $user = $this->createMock(User::class);

        $dto = new SendMessageDTO();
        $dto->type = MessageType::WHISPER;
        $dto->content = 'Secret message';
        $dto->recipientId = null;

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Un destinataire est requis pour un message privé.');

        $this->chatService->sendMessage($game, $user, $dto);
    }

    public function testSendWhisperWithInvalidRecipientThrowsException(): void
    {
        $game = $this->createMock(Game::class);
        $user = $this->createMock(User::class);

        $dto = new SendMessageDTO();
        $dto->type = MessageType::WHISPER;
        $dto->content = 'Secret';
        $dto->recipientId = 999;

        $this->userRepository->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Destinataire introuvable.');

        $this->chatService->sendMessage($game, $user, $dto);
    }

    public function testSendWhisperWithRecipientNotInGameThrowsException(): void
    {
        $game = $this->createMock(Game::class);
        $user = $this->createMock(User::class);
        $recipient = $this->createMock(User::class);

        $dto = new SendMessageDTO();
        $dto->type = MessageType::WHISPER;
        $dto->content = 'Secret';
        $dto->recipientId = 2;

        $this->userRepository->expects($this->once())
            ->method('find')
            ->willReturn($recipient);

        $game->expects($this->once())
            ->method('hasPlayer')
            ->with($recipient)
            ->willReturn(false);

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Le destinataire doit être membre de la partie.');

        $this->chatService->sendMessage($game, $user, $dto);
    }

    public function testSendSystemMessageAsNonGMThrowsException(): void
    {
        $game = $this->createMock(Game::class);
        $user = $this->createMock(User::class);

        $dto = new SendMessageDTO();
        $dto->type = MessageType::SYSTEM;
        $dto->content = 'System message';

        $game->expects($this->once())
            ->method('isGameMaster')
            ->with($user)
            ->willReturn(false);

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Seul le MJ peut envoyer des messages système.');

        $this->chatService->sendMessage($game, $user, $dto);
    }

    public function testGetRecentMessages(): void
    {
        $game = $this->createMock(Game::class);
        $messages = [
            $this->createMock(GameMessage::class),
            $this->createMock(GameMessage::class),
        ];

        $this->messageRepository->expects($this->once())
            ->method('findRecentMessages')
            ->with($game, 50)
            ->willReturn($messages);

        $result = $this->chatService->getRecentMessages($game, 50);

        $this->assertCount(2, $result);
    }

    public function testGetRecentMessagesWithInvalidLimitThrowsException(): void
    {
        $game = $this->createMock(Game::class);

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('La limite doit être entre 1 et 200.');

        $this->chatService->getRecentMessages($game, 250);
    }

    public function testGetVisibleMessagesForUser(): void
    {
        $game = $this->createMock(Game::class);
        $user = $this->createMock(User::class);
        $messages = [$this->createMock(GameMessage::class)];

        $this->messageRepository->expects($this->once())
            ->method('findVisibleForUser')
            ->with($game, $user)
            ->willReturn($messages);

        $result = $this->chatService->getVisibleMessagesForUser($game, $user);

        $this->assertSame($messages, $result);
    }

    public function testGetMessagesByType(): void
    {
        $game = $this->createMock(Game::class);
        $messages = [$this->createMock(GameMessage::class)];

        $this->messageRepository->expects($this->once())
            ->method('findByType')
            ->with($game, MessageType::CHAT)
            ->willReturn($messages);

        $result = $this->chatService->getMessagesByType($game, MessageType::CHAT);

        $this->assertSame($messages, $result);
    }

    public function testGetMessagesByTypeWithInvalidTypeThrowsException(): void
    {
        $game = $this->createMock(Game::class);

        $this->expectException(\TypeError::class);

        /** @phpstan-ignore-next-line */
        $this->chatService->getMessagesByType($game, 'invalid_type');
    }

    public function testCreateSystemMessage(): void
    {
        $gameMaster = $this->createMock(User::class);
        $game = $this->createMock(Game::class);
        $game->method('getGameMaster')->willReturn($gameMaster);
        $game->method('getId')->willReturn(1);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(GameMessage::class));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->mercurePublisher->expects($this->once())
            ->method('publishChatMessage');

        $message = $this->chatService->createSystemMessage($game, 'System notification');

        $this->assertInstanceOf(GameMessage::class, $message);
    }

    public function testCreateDiceRollMessage(): void
    {
        $game = $this->createMock(Game::class);
        $game->method('getId')->willReturn(1);

        $user = $this->createMock(User::class);
        $user->method('getPseudo')->willReturn('Player1');

        $results = [
            'rolls' => [4, 5, 6],
            'total' => 15,
            'modifier' => 0,
        ];

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(GameMessage::class));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->mercurePublisher->expects($this->once())
            ->method('publishChatMessage');

        $message = $this->chatService->createDiceRollMessage($game, $user, '3d6', $results);

        $this->assertInstanceOf(GameMessage::class, $message);
    }

    public function testGetMessageStats(): void
    {
        $game = $this->createMock(Game::class);
        $stats = ['total' => 50, 'byType' => ['chat' => 40, 'system' => 10]];

        $this->messageRepository->expects($this->once())
            ->method('getStatsByGame')
            ->with($game)
            ->willReturn($stats);

        $result = $this->chatService->getMessageStats($game);

        $this->assertEquals($stats, $result);
    }

    public function testDeleteOldMessages(): void
    {
        $game = $this->createMock(Game::class);
        $before = new DateTimeImmutable('-30 days');

        $this->messageRepository->expects($this->once())
            ->method('deleteOlderThan')
            ->with($game, $before)
            ->willReturn(15);

        $result = $this->chatService->deleteOldMessages($game, $before);

        $this->assertEquals(15, $result);
    }

    public function testGetMessagesSinceWithUser(): void
    {
        $game = $this->createMock(Game::class);
        $user = $this->createMock(User::class);
        $since = new DateTimeImmutable('-1 hour');
        $messages = [$this->createMock(GameMessage::class)];

        $this->messageRepository->expects($this->once())
            ->method('findVisibleSince')
            ->with($game, $since, $user)
            ->willReturn($messages);

        $result = $this->chatService->getMessagesSince($game, $since, $user);

        $this->assertSame($messages, $result);
    }

    public function testGetMessagesSinceWithoutUser(): void
    {
        $game = $this->createMock(Game::class);
        $since = new DateTimeImmutable('-1 hour');
        $messages = [$this->createMock(GameMessage::class)];

        $this->messageRepository->expects($this->once())
            ->method('findSince')
            ->with($game, $since)
            ->willReturn($messages);

        $result = $this->chatService->getMessagesSince($game, $since);

        $this->assertSame($messages, $result);
    }
}
