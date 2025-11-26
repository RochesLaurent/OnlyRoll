<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\DTO\Token\CreateTokenDTO;
use App\DTO\Token\MoveTokenDTO;
use App\Entity\Game;
use App\Entity\GameMap;
use App\Entity\GameToken;
use App\Entity\User;
use App\Enum\TokenLayer;
use App\Enum\TokenType;
use App\Repository\GameTokenRepository;
use App\Service\MercurePublisher;
use App\Service\TokenService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class TokenServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;

    private GameTokenRepository&MockObject $tokenRepository;

    private MercurePublisher&MockObject $mercurePublisher;

    private TokenService $tokenService;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->tokenRepository = $this->createMock(GameTokenRepository::class);
        $this->mercurePublisher = $this->createMock(MercurePublisher::class);

        $this->tokenService = new TokenService(
            $this->entityManager,
            $this->tokenRepository,
            $this->mercurePublisher,
        );
    }

    public function testCreateTokenSuccess(): void
    {
        $game = $this->createMock(Game::class);
        $game->method('getId')->willReturn(1);

        $map = $this->createMock(GameMap::class);
        $map->method('getWidth')->willReturn(20);
        $map->method('getHeight')->willReturn(20);
        $map->method('getGame')->willReturn($game);
        $map->method('getId')->willReturn(1);

        $dto = new CreateTokenDTO();
        $dto->name = 'Hero Token';
        $dto->type = TokenType::CHARACTER;
        $dto->x = 5;
        $dto->y = 10;
        $dto->size = 1.0;
        $dto->rotation = 0;
        $dto->isVisible = true;
        $dto->isLocked = false;
        $dto->layer = TokenLayer::TOKENS;

        // Set up entity manager to properly set IDs and timestamps after flush
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->willReturnCallback(function (GameToken $token) {
                // Simulate Doctrine setting IDs and timestamps
                $reflection = new ReflectionClass($token);
                $idProperty = $reflection->getProperty('id');
                $idProperty->setAccessible(true);
                $idProperty->setValue($token, 1);

                $createdAtProperty = $reflection->getProperty('createdAt');
                $createdAtProperty->setAccessible(true);
                $createdAtProperty->setValue($token, new DateTimeImmutable('2024-01-01 10:00:00'));
            });

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->mercurePublisher->expects($this->once())
            ->method('publishTokenCreated')
            ->with(1, $this->callback(fn ($value) => \is_array($value)));

        $token = $this->tokenService->createToken($map, $dto);

        $this->assertInstanceOf(GameToken::class, $token);
    }

    public function testCreateTokenThrowsExceptionWhenOutOfBounds(): void
    {
        $map = $this->createMock(GameMap::class);
        $map->method('getWidth')->willReturn(20);
        $map->method('getHeight')->willReturn(20);

        $dto = new CreateTokenDTO();
        $dto->name = 'Token';
        $dto->type = TokenType::CHARACTER;
        $dto->x = 25; // Out of bounds
        $dto->y = 10;

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Position invalide pour cette carte.');

        $this->tokenService->createToken($map, $dto);
    }

    public function testCreateTokenWithNegativeCoordinates(): void
    {
        $map = $this->createMock(GameMap::class);
        $map->method('getWidth')->willReturn(20);
        $map->method('getHeight')->willReturn(20);

        $dto = new CreateTokenDTO();
        $dto->name = 'Token';
        $dto->type = TokenType::CHARACTER;
        $dto->x = -1;
        $dto->y = 5;

        $this->expectException(BadRequestHttpException::class);

        $this->tokenService->createToken($map, $dto);
    }

    public function testMoveTokenSuccess(): void
    {
        $game = $this->createMock(Game::class);
        $game->method('getId')->willReturn(1);

        $map = $this->createMock(GameMap::class);
        $map->method('getWidth')->willReturn(20);
        $map->method('getHeight')->willReturn(20);
        $map->method('getGame')->willReturn($game);
        $map->method('getId')->willReturn(1);

        $token = $this->createMock(GameToken::class);
        $token->method('isLocked')->willReturn(false);
        $token->method('getMap')->willReturn($map);
        $token->method('getId')->willReturn(1);
        $token->method('getCreatedAt')->willReturn(new DateTimeImmutable('2024-01-01 10:00:00'));
        $token->method('getUpdatedAt')->willReturn(new DateTimeImmutable('2024-01-01 10:00:00'));
        $token->method('getLayer')->willReturn(TokenLayer::TOKENS);

        $dto = new MoveTokenDTO();
        $dto->x = 15;
        $dto->y = 18;

        $token->expects($this->once())->method('setX')->with(15);
        $token->expects($this->once())->method('setY')->with(18);
        $token->expects($this->once())->method('getX')->willReturn(15);
        $token->expects($this->once())->method('getY')->willReturn(18);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->mercurePublisher->expects($this->once())
            ->method('publishGameEvent');

        $result = $this->tokenService->moveToken($token, $dto);

        $this->assertSame($token, $result);
    }

    public function testMoveLockedTokenThrowsException(): void
    {
        $token = $this->createMock(GameToken::class);
        $token->method('isLocked')->willReturn(true);

        $dto = new MoveTokenDTO();
        $dto->x = 15;
        $dto->y = 18;

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Ce token est verrouillé et ne peut pas être déplacé.');

        $this->tokenService->moveToken($token, $dto);
    }

    public function testMoveTokenOutOfBoundsThrowsException(): void
    {
        $map = $this->createMock(GameMap::class);
        $map->method('getWidth')->willReturn(20);
        $map->method('getHeight')->willReturn(20);

        $token = $this->createMock(GameToken::class);
        $token->method('isLocked')->willReturn(false);
        $token->method('getMap')->willReturn($map);

        $dto = new MoveTokenDTO();
        $dto->x = 25;
        $dto->y = 10;

        $this->expectException(BadRequestHttpException::class);

        $this->tokenService->moveToken($token, $dto);
    }

    public function testGetTokensByMapWithUser(): void
    {
        $map = $this->createMock(GameMap::class);
        $user = $this->createMock(User::class);
        $tokens = [$this->createMock(GameToken::class)];

        $this->tokenRepository->expects($this->once())
            ->method('findVisibleByMap')
            ->with($map, $user)
            ->willReturn($tokens);

        $result = $this->tokenService->getTokensByMap($map, $user);

        $this->assertSame($tokens, $result);
    }

    public function testGetTokensByMapWithoutUser(): void
    {
        $map = $this->createMock(GameMap::class);
        $tokens = [
            $this->createMock(GameToken::class),
            $this->createMock(GameToken::class),
        ];

        $this->tokenRepository->expects($this->once())
            ->method('findByMap')
            ->with($map)
            ->willReturn($tokens);

        $result = $this->tokenService->getTokensByMap($map);

        $this->assertCount(2, $result);
    }

    public function testGetTokenById(): void
    {
        $token = $this->createMock(GameToken::class);

        $this->tokenRepository->expects($this->once())
            ->method('find')
            ->with(42)
            ->willReturn($token);

        $result = $this->tokenService->getTokenById(42);

        $this->assertSame($token, $result);
    }

    public function testToggleVisibility(): void
    {
        $game = $this->createMock(Game::class);
        $game->method('getId')->willReturn(1);

        $map = $this->createMock(GameMap::class);
        $map->method('getGame')->willReturn($game);

        $token = $this->createMock(GameToken::class);
        $token->method('isVisible')->willReturn(true);
        $token->method('getMap')->willReturn($map);
        $token->method('getId')->willReturn(1);
        $token->method('getCreatedAt')->willReturn(new DateTimeImmutable('2024-01-01 10:00:00'));
        $token->method('getUpdatedAt')->willReturn(new DateTimeImmutable('2024-01-01 10:00:00'));
        $token->method('getLayer')->willReturn(TokenLayer::TOKENS);

        $token->expects($this->once())
            ->method('setIsVisible')
            ->with(false);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->mercurePublisher->expects($this->once())
            ->method('publishGameEvent')
            ->with(1, 'token', $this->arrayHasKey('action'));

        $result = $this->tokenService->toggleVisibility($token);

        $this->assertSame($token, $result);
    }

    public function testToggleLock(): void
    {
        $game = $this->createMock(Game::class);
        $game->method('getId')->willReturn(1);

        $map = $this->createMock(GameMap::class);
        $map->method('getGame')->willReturn($game);

        $token = $this->createMock(GameToken::class);
        $token->method('isLocked')->willReturn(false);
        $token->method('getMap')->willReturn($map);
        $token->method('getId')->willReturn(1);
        $token->method('getCreatedAt')->willReturn(new DateTimeImmutable('2024-01-01 10:00:00'));
        $token->method('getUpdatedAt')->willReturn(new DateTimeImmutable('2024-01-01 10:00:00'));
        $token->method('getLayer')->willReturn(TokenLayer::TOKENS);

        $token->expects($this->once())
            ->method('setIsLocked')
            ->with(true);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->mercurePublisher->expects($this->once())
            ->method('publishGameEvent');

        $result = $this->tokenService->toggleLock($token);

        $this->assertSame($token, $result);
    }

    public function testDeleteToken(): void
    {
        $game = $this->createMock(Game::class);
        $game->method('getId')->willReturn(1);

        $map = $this->createMock(GameMap::class);
        $map->method('getGame')->willReturn($game);

        $token = $this->createMock(GameToken::class);
        $token->method('getMap')->willReturn($map);
        $token->method('getId')->willReturn(5);

        $this->entityManager->expects($this->once())
            ->method('remove')
            ->with($token);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->mercurePublisher->expects($this->once())
            ->method('publishTokenDeleted')
            ->with(1, 5);

        $this->tokenService->deleteToken($token);
    }

    public function testGetVisibleTokens(): void
    {
        $map = $this->createMock(GameMap::class);
        $user = $this->createMock(User::class);
        $tokens = [$this->createMock(GameToken::class)];

        $this->tokenRepository->expects($this->once())
            ->method('findVisibleByMap')
            ->with($map, $user)
            ->willReturn($tokens);

        $result = $this->tokenService->getVisibleTokens($map, $user);

        $this->assertSame($tokens, $result);
    }

    public function testCountTokensOnMap(): void
    {
        $map = $this->createMock(GameMap::class);

        $this->tokenRepository->expects($this->once())
            ->method('countByMap')
            ->with($map)
            ->willReturn(7);

        $result = $this->tokenService->countTokensOnMap($map);

        $this->assertEquals(7, $result);
    }
}
