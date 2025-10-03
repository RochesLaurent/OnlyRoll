<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\DTO\Game\CreateGameDTO;
use App\Entity\Game;
use App\Entity\GamePlayer;
use App\Entity\User;
use App\Enum\GameStatus;
use App\Enum\PlayerRole;
use App\Enum\PlayerStatus;
use App\Exception\Game\GameFullException;
use App\Exception\Game\GameNotFoundException;
use App\Repository\GamePlayerRepository;
use App\Repository\GameRepository;
use App\Service\GameService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class GameServiceTest extends TestCase
{
    private GameService $gameService;

    /** @var EntityManagerInterface&MockObject */
    private EntityManagerInterface $entityManager;

    /** @var GameRepository&MockObject */
    private GameRepository $gameRepository;

    /** @var GamePlayerRepository&MockObject */
    private GamePlayerRepository $gamePlayerRepository;

    /** @var LoggerInterface&MockObject */
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        // Créer les mocks
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->gameRepository = $this->createMock(GameRepository::class);
        $this->gamePlayerRepository = $this->createMock(GamePlayerRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        // Instancier le service avec les mocks
        $this->gameService = new GameService(
            $this->entityManager,
            $this->gameRepository,
            $this->gamePlayerRepository,
            $this->logger
        );
    }

    public function testCreateGame(): void
    {
        // Arrange
        $user = $this->createTestUser('gm@test.com', 1);

        $dto = new CreateGameDTO();
        $dto->name = 'Test Campaign';
        $dto->description = 'A test campaign';
        $dto->maxPlayers = 6;
        $dto->isPublic = true;

        // Mock EntityManager - doit persister le Game ET le GamePlayer (GM)
        $this->entityManager
            ->expects($this->exactly(2))
            ->method('persist')
            ->withConsecutive(
                [$this->isInstanceOf(Game::class)],
                [$this->isInstanceOf(GamePlayer::class)]
            );

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        // Mock logger
        $this->logger
            ->expects($this->exactly(2))
            ->method('info');

        // Act
        $game = $this->gameService->createGame($dto, $user);

        // Assert
        $this->assertInstanceOf(Game::class, $game);
        $this->assertEquals('Test Campaign', $game->getName());
        $this->assertEquals('A test campaign', $game->getDescription());
        $this->assertEquals(6, $game->getMaxPlayers());
        $this->assertTrue($game->isPublic());
        $this->assertEquals($user->getId(), $game->getGameMaster()->getId());
        $this->assertEquals(GameStatus::PREPARATION, $game->getStatus());
        $this->assertNotNull($game->getInviteCode());
        $this->assertEquals(8, strlen($game->getInviteCode())); // Code généré = 8 caractères
    }

    public function testJoinGameSuccess(): void
    {
        // Arrange
        $gm = $this->createTestUser('gm@test.com', 1);
        $player = $this->createTestUser('player@test.com', 2);

        $game = new Game();
        $game->setName('Test Game')
             ->setGameMaster($gm)
             ->setMaxPlayers(6)
             ->setIsPublic(true)
             ->setStatus(GameStatus::PREPARATION);

        // Simuler l'ID du jeu
        $this->setEntityId($game, 1);

        // Mock du repository pour retourner le jeu avec findGameWithPlayers
        $this->gameRepository
            ->expects($this->once())
            ->method('findGameWithPlayers')
            ->with(1)
            ->willReturn($game);

        // Mock pour vérifier que le joueur n'est pas déjà dans la partie
        $this->gamePlayerRepository
            ->expects($this->once())
            ->method('isUserInGame')
            ->with($game, $player)
            ->willReturn(false);

        // Mock EntityManager
        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->callback(function ($gamePlayer) use ($player, $game) {
                return $gamePlayer instanceof GamePlayer
                    && $gamePlayer->getUser()->getId() === $player->getId()
                    && $gamePlayer->getGame() === $game
                    && PlayerRole::PLAYER === $gamePlayer->getRole()
                    && PlayerStatus::ACTIVE === $gamePlayer->getStatus();
            }));

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        // Mock logger
        $this->logger
            ->expects($this->once())
            ->method('info');

        // Act
        $gamePlayer = $this->gameService->joinGame(1, $player);

        // Assert
        $this->assertInstanceOf(GamePlayer::class, $gamePlayer);
        $this->assertEquals($player->getId(), $gamePlayer->getUser()->getId());
        $this->assertEquals(PlayerRole::PLAYER, $gamePlayer->getRole());
        $this->assertEquals(PlayerStatus::ACTIVE, $gamePlayer->getStatus());
    }

    public function testJoinFullGameThrowsException(): void
    {
        // Arrange
        $gm = $this->createTestUser('gm@test.com', 1);
        $player3 = $this->createTestUser('player3@test.com', 4);

        $game = new Game();
        $game->setName('Full Game')
             ->setGameMaster($gm)
             ->setMaxPlayers(2) // Maximum 2 joueurs
             ->setIsPublic(true)
             ->setStatus(GameStatus::PREPARATION);

        $this->setEntityId($game, 1);

        // Ajouter 2 joueurs actifs pour remplir la partie
        $player1 = $this->createTestUser('player1@test.com', 2);
        $player2 = $this->createTestUser('player2@test.com', 3);

        $gamePlayer1 = new GamePlayer();
        $gamePlayer1->setUser($player1)
                    ->setGame($game)
                    ->setRole(PlayerRole::PLAYER)
                    ->setStatus(PlayerStatus::ACTIVE);

        $gamePlayer2 = new GamePlayer();
        $gamePlayer2->setUser($player2)
                    ->setGame($game)
                    ->setRole(PlayerRole::PLAYER)
                    ->setStatus(PlayerStatus::ACTIVE);

        $game->addGamePlayer($gamePlayer1);
        $game->addGamePlayer($gamePlayer2);

        // Mock du repository
        $this->gameRepository
            ->expects($this->once())
            ->method('findGameWithPlayers')
            ->with(1)
            ->willReturn($game);

        // Mock pour vérifier que le joueur n'est pas dans la partie
        $this->gamePlayerRepository
            ->expects($this->once())
            ->method('isUserInGame')
            ->with($game, $player3)
            ->willReturn(false);

        // Assert l'exception
        $this->expectException(GameFullException::class);

        // Act
        $this->gameService->joinGame(1, $player3);
    }

    public function testJoinGameNotFoundThrowsException(): void
    {
        // Arrange
        $player = $this->createTestUser('player@test.com', 2);

        // Mock du repository pour retourner null
        $this->gameRepository
            ->expects($this->once())
            ->method('findGameWithPlayers')
            ->with(999)
            ->willReturn(null);

        // Assert l'exception
        $this->expectException(GameNotFoundException::class);

        // Act
        $this->gameService->joinGame(999, $player);
    }

    private function createTestUser(string $email, int $id): User
    {
        $user = new User();
        $user->setPseudo('testuser_' . $id)
             ->setEmail($email)
             ->setPassword(password_hash('password', PASSWORD_BCRYPT))
             ->setRoles(['ROLE_USER'])
             ->setIsVerified(true);

        $this->setEntityId($user, $id);

        return $user;
    }

    private function setEntityId(object $entity, int $id): void
    {
        $reflection = new \ReflectionClass($entity);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($entity, $id);
    }
}
