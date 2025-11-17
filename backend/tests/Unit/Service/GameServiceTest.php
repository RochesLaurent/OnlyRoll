<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\DTO\Game\CreateGameDTO;
use App\DTO\Game\UpdateGameDTO;
use App\Entity\Game;
use App\Entity\GamePlayer;
use App\Entity\User;
use App\Enum\GameStatus;
use App\Exception\Game\AccessDeniedException;
use App\Exception\Game\GameFullException;
use App\Exception\Game\GameNotFoundException;
use App\Exception\Game\InvalidPasswordException;
use App\Repository\GamePlayerRepository;
use App\Repository\GameRepository;
use App\Service\GameService;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class GameServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;

    private GameRepository&MockObject $gameRepository;

    private GamePlayerRepository&MockObject $gamePlayerRepository;

    private LoggerInterface&MockObject $logger;

    private GameService $gameService;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->gameRepository = $this->createMock(GameRepository::class);
        $this->gamePlayerRepository = $this->createMock(GamePlayerRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->gameService = new GameService(
            $this->entityManager,
            $this->gameRepository,
            $this->gamePlayerRepository,
            $this->logger,
        );
    }

    // ==================== CREATE GAME ====================

    public function testCreateGameWithPublicGame(): void
    {
        $dto = new CreateGameDTO();
        $dto->name = 'Test Game';
        $dto->description = 'A test game';
        $dto->maxPlayers = 5;
        $dto->isPublic = true;
        $dto->password = null;

        $gameMaster = $this->createUser(1);

        $this->entityManager->expects($this->exactly(2))
            ->method('persist')
            ->with($this->isInstanceOf(Game::class));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->logger->expects($this->exactly(2))
            ->method('info');

        $game = $this->gameService->createGame($dto, $gameMaster);

        $this->assertInstanceOf(Game::class, $game);
        $this->assertSame('Test Game', $game->getName());
        $this->assertSame('A test game', $game->getDescription());
        $this->assertSame(5, $game->getMaxPlayers());
        $this->assertTrue($game->isPublic());
        $this->assertNull($game->getPassword());
    }

    public function testCreateGameWithPrivateGame(): void
    {
        $dto = new CreateGameDTO();
        $dto->name = 'Private Game';
        $dto->description = 'A private game';
        $dto->maxPlayers = 4;
        $dto->isPublic = false;
        $dto->password = 'secret123';

        $gameMaster = $this->createUser(1);

        $this->entityManager->expects($this->exactly(2))
            ->method('persist');

        $this->entityManager->expects($this->once())
            ->method('flush');

        $game = $this->gameService->createGame($dto, $gameMaster);

        $this->assertFalse($game->isPublic());
        $this->assertNotNull($game->getPassword());
        $this->assertTrue(password_verify('secret123', $game->getPassword()));
    }

    public function testCreateGameThrowsExceptionWhenPrivateWithoutPassword(): void
    {
        $dto = new CreateGameDTO();
        $dto->name = 'Private Game';
        $dto->isPublic = false;
        $dto->password = '';

        $gameMaster = $this->createUser(1);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Le mot de passe est requis pour une partie privée');

        $this->gameService->createGame($dto, $gameMaster);
    }

    public function testCreateGameThrowsExceptionWhenPasswordTooShort(): void
    {
        $dto = new CreateGameDTO();
        $dto->name = 'Private Game';
        $dto->isPublic = false;
        $dto->password = '123';

        $gameMaster = $this->createUser(1);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Le mot de passe doit faire entre 4 et 50 caractères');

        $this->gameService->createGame($dto, $gameMaster);
    }

    public function testCreateGameThrowsExceptionWhenPasswordTooLong(): void
    {
        $dto = new CreateGameDTO();
        $dto->name = 'Private Game';
        $dto->isPublic = false;
        $dto->password = str_repeat('a', 51);

        $gameMaster = $this->createUser(1);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Le mot de passe doit faire entre 4 et 50 caractères');

        $this->gameService->createGame($dto, $gameMaster);
    }

    // ==================== UPDATE GAME ====================

    public function testUpdateGameWithAllFields(): void
    {
        $game = $this->createGame(1, 'Old Name');
        $user = $this->createUser(1);

        $game->method('isGameMaster')->willReturn(true);

        $dto = new UpdateGameDTO();
        $dto->name = 'New Name';
        $dto->description = 'New Description';
        $dto->maxPlayers = 10;
        $dto->isPublic = false;
        $dto->status = GameStatus::IN_PROGRESS;

        $this->entityManager->expects($this->once())
            ->method('flush');

        $updatedGame = $this->gameService->updateGame($game, $dto, $user);

        $this->assertSame($game, $updatedGame);
    }

    public function testUpdateGameThrowsExceptionWhenNotGameMaster(): void
    {
        $game = $this->createGame(1, 'Test Game');
        $user = $this->createUser(2);

        $game->method('isGameMaster')->willReturn(false);

        $dto = new UpdateGameDTO();
        $dto->name = 'New Name';

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Seul le MJ peut modifier cette partie');

        $this->gameService->updateGame($game, $dto, $user);
    }

    public function testUpdateGameWithPartialFields(): void
    {
        $game = $this->createGame(1, 'Test Game');
        $user = $this->createUser(1);

        $game->method('isGameMaster')->willReturn(true);

        $dto = new UpdateGameDTO();
        $dto->name = 'Updated Name';
        // Les autres champs restent null

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->gameService->updateGame($game, $dto, $user);
    }

    // ==================== JOIN GAME ====================

    public function testJoinGameSuccessfully(): void
    {
        $game = $this->createGame(1, 'Test Game');
        $user = $this->createUser(2);

        $game->method('isPublic')->willReturn(true);
        $game->method('isFull')->willReturn(false);
        $game->method('getStatus')->willReturn(GameStatus::PREPARATION);

        $this->gameRepository->expects($this->once())
            ->method('findGameWithPlayers')
            ->with(1)
            ->willReturn($game);

        $this->gamePlayerRepository->expects($this->once())
            ->method('isUserInGame')
            ->willReturn(false);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(GamePlayer::class));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $gamePlayer = $this->gameService->joinGame(1, $user);

        $this->assertInstanceOf(GamePlayer::class, $gamePlayer);
    }

    public function testJoinGameThrowsExceptionWhenGameNotFound(): void
    {
        $user = $this->createUser(2);

        $this->gameRepository->expects($this->once())
            ->method('findGameWithPlayers')
            ->with(1)
            ->willReturn(null);

        $this->expectException(GameNotFoundException::class);

        $this->gameService->joinGame(1, $user);
    }

    public function testJoinGameThrowsExceptionWhenUserAlreadyInGame(): void
    {
        $game = $this->createGame(1, 'Test Game');
        $user = $this->createUser(2);

        $this->gameRepository->expects($this->once())
            ->method('findGameWithPlayers')
            ->willReturn($game);

        $this->gamePlayerRepository->expects($this->once())
            ->method('isUserInGame')
            ->willReturn(true);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Vous faites déjà partie de cette partie');

        $this->gameService->joinGame(1, $user);
    }

    public function testJoinGameThrowsExceptionWhenGameFull(): void
    {
        $game = $this->createGame(1, 'Test Game');
        $user = $this->createUser(2);

        $game->method('isFull')->willReturn(true);

        $this->gameRepository->expects($this->once())
            ->method('findGameWithPlayers')
            ->willReturn($game);

        $this->gamePlayerRepository->expects($this->once())
            ->method('isUserInGame')
            ->willReturn(false);

        $this->expectException(GameFullException::class);

        $this->gameService->joinGame(1, $user);
    }

    public function testJoinPrivateGameWithCorrectPassword(): void
    {
        $game = $this->createGame(1, 'Private Game');
        $user = $this->createUser(2);

        $hashedPassword = password_hash('secret123', \PASSWORD_ARGON2ID);

        $game->method('isPublic')->willReturn(false);
        $game->method('isFull')->willReturn(false);
        $game->method('getPassword')->willReturn($hashedPassword);
        $game->method('getStatus')->willReturn(GameStatus::PREPARATION);

        $this->gameRepository->expects($this->once())
            ->method('findGameWithPlayers')
            ->willReturn($game);

        $this->gamePlayerRepository->expects($this->once())
            ->method('isUserInGame')
            ->willReturn(false);

        $this->entityManager->expects($this->once())
            ->method('persist');

        $this->entityManager->expects($this->once())
            ->method('flush');

        $gamePlayer = $this->gameService->joinGame(1, $user, 'secret123');

        $this->assertInstanceOf(GamePlayer::class, $gamePlayer);
    }

    public function testJoinPrivateGameWithIncorrectPassword(): void
    {
        $game = $this->createGame(1, 'Private Game');
        $user = $this->createUser(2);

        $hashedPassword = password_hash('secret123', \PASSWORD_ARGON2ID);

        $game->method('isPublic')->willReturn(false);
        $game->method('isFull')->willReturn(false);
        $game->method('getPassword')->willReturn($hashedPassword);

        $this->gameRepository->expects($this->once())
            ->method('findGameWithPlayers')
            ->willReturn($game);

        $this->gamePlayerRepository->expects($this->once())
            ->method('isUserInGame')
            ->willReturn(false);

        $this->expectException(InvalidPasswordException::class);

        $this->gameService->joinGame(1, $user, 'wrongpassword');
    }

    public function testJoinGameThrowsExceptionWhenGameStatusNotAccepting(): void
    {
        $game = $this->createGame(1, 'Test Game');
        $user = $this->createUser(2);

        $game->method('isPublic')->willReturn(true);
        $game->method('isFull')->willReturn(false);
        $game->method('getStatus')->willReturn(GameStatus::COMPLETED);

        $this->gameRepository->expects($this->once())
            ->method('findGameWithPlayers')
            ->willReturn($game);

        $this->gamePlayerRepository->expects($this->once())
            ->method('isUserInGame')
            ->willReturn(false);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Cette partie n\'accepte plus de nouveaux joueurs');

        $this->gameService->joinGame(1, $user);
    }

    // ==================== LEAVE GAME ====================

    public function testLeaveGameSuccessfully(): void
    {
        $game = $this->createGame(1, 'Test Game');
        $user = $this->createUser(2);
        $gamePlayer = $this->createGamePlayer();

        $game->method('isGameMaster')->willReturn(false);

        $this->gamePlayerRepository->expects($this->once())
            ->method('findPlayerInGame')
            ->with($game, $user)
            ->willReturn($gamePlayer);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->gameService->leaveGame($game, $user);
    }

    public function testLeaveGameThrowsExceptionWhenNotInGame(): void
    {
        $game = $this->createGame(1, 'Test Game');
        $user = $this->createUser(2);

        $this->gamePlayerRepository->expects($this->once())
            ->method('findPlayerInGame')
            ->willReturn(null);

        $this->expectException(GameNotFoundException::class);
        $this->expectExceptionMessage('Vous ne faites pas partie de cette partie');

        $this->gameService->leaveGame($game, $user);
    }

    public function testLeaveGameThrowsExceptionWhenUserIsGameMaster(): void
    {
        $game = $this->createGame(1, 'Test Game');
        $user = $this->createUser(1);
        $gamePlayer = $this->createGamePlayer();

        $game->method('isGameMaster')->willReturn(true);

        $this->gamePlayerRepository->expects($this->once())
            ->method('findPlayerInGame')
            ->willReturn($gamePlayer);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Le MJ ne peut pas quitter sa propre partie');

        $this->gameService->leaveGame($game, $user);
    }

    // ==================== DELETE GAME ====================

    public function testDeleteGameSuccessfully(): void
    {
        $game = $this->createGame(1, 'Test Game');
        $user = $this->createUser(1);

        $game->method('isGameMaster')->willReturn(true);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->gameService->deleteGame($game, $user);
    }

    public function testDeleteGameThrowsExceptionWhenNotGameMaster(): void
    {
        $game = $this->createGame(1, 'Test Game');
        $user = $this->createUser(2);

        $game->method('isGameMaster')->willReturn(false);

        $this->expectException(AccessDeniedException::class);

        $this->gameService->deleteGame($game, $user);
    }

    // ==================== HELPERS ====================

    private function createUser(int $id): User&MockObject
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($id);

        return $user;
    }

    private function createGame(int $id, string $name): Game&MockObject
    {
        $game = $this->createMock(Game::class);
        $game->method('getId')->willReturn($id);
        $game->method('getName')->willReturn($name);
        $game->method('getStatus')->willReturn(GameStatus::PREPARATION);

        return $game;
    }

    private function createGamePlayer(): GamePlayer&MockObject
    {
        return $this->createMock(GamePlayer::class);
    }
}
