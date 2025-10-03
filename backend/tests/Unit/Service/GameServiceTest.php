<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\DTO\Game\CreateGameDTO;
use App\Entity\Game;
use App\Entity\User;
use App\Enum\GameStatus;
use App\Exception\Game\GameFullException;
use App\Service\GameService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class GameServiceTest extends KernelTestCase
{
    private GameService $gameService;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $container = $kernel->getContainer();

        $this->entityManager = $container->get('doctrine')->getManager();
        $this->gameService = $container->get(GameService::class);
    }

    public function testCreateGame(): void
    {
        // Arrange
        $user = $this->createTestUser('gm@test.com');
        $dto = new CreateGameDTO();
        $dto->name = 'Test Campaign';
        $dto->description = 'A test campaign';
        $dto->maxPlayers = 6;
        $dto->isPublic = true;

        // Act
        $game = $this->gameService->createGame($dto, $user);

        // Assert
        $this->assertInstanceOf(Game::class, $game);
        $this->assertEquals('Test Campaign', $game->getName());
        $this->assertEquals($user->getId(), $game->getGameMaster()->getId());
        $this->assertEquals(GameStatus::PREPARATION, $game->getStatus());
        $this->assertNotNull($game->getInviteCode());

        // Vérifier que le GM est bien ajouté comme joueur
        $this->assertEquals(1, $game->getCurrentPlayersCount());
    }

    public function testJoinGameSuccess(): void
    {
        // Arrange
        $gm = $this->createTestUser('gm@test.com');
        $player = $this->createTestUser('player@test.com');

        $dto = new CreateGameDTO();
        $dto->name = 'Test Game';
        $dto->isPublic = true;
        $dto->maxPlayers = 6;

        $game = $this->gameService->createGame($dto, $gm);

        // Act
        $gamePlayer = $this->gameService->joinGame($game->getId(), $player);

        // Assert
        $this->assertNotNull($gamePlayer);
        $this->assertEquals($player->getId(), $gamePlayer->getUser()->getId());
    }

    public function testJoinFullGameThrowsException(): void
    {
        // Arrange
        $gm = $this->createTestUser('gm@test.com');

        $dto = new CreateGameDTO();
        $dto->name = 'Full Game';
        $dto->maxPlayers = 2; // GM + 1 joueur max
        $dto->isPublic = true;

        $game = $this->gameService->createGame($dto, $gm);

        $player1 = $this->createTestUser('player1@test.com');
        $this->gameService->joinGame($game->getId(), $player1);

        $player2 = $this->createTestUser('player2@test.com');

        // Assert
        $this->expectException(GameFullException::class);

        // Act
        $this->gameService->joinGame($game->getId(), $player2);
    }

    private function createTestUser(string $email): User
    {
        $user = new User();
        $user->setPseudo('testuser_' . uniqid())
             ->setEmail($email)
             ->setPassword(password_hash('password', PASSWORD_BCRYPT))
             ->setRoles(['ROLE_USER'])
             ->setIsVerified(true);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }
}
