<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Game;
use App\Entity\GamePlayer;
use App\Entity\User;
use App\Enum\PlayerStatus;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class GameTest extends TestCase
{
    private Game $game;

    private User $gameMaster;

    private User $player1;

    private User $player2;

    protected function setUp(): void
    {
        $this->game = new Game();
        $this->gameMaster = new User();
        $this->player1 = new User();
        $this->player2 = new User();

        $this->setPrivateProperty($this->gameMaster, 'id', 1);
        $this->setPrivateProperty($this->player1, 'id', 2);
        $this->setPrivateProperty($this->player2, 'id', 3);

        $this->game->setGameMaster($this->gameMaster);
    }

    public function testConstructorGeneratesInviteCode(): void
    {
        $game = new Game();

        $this->assertNotNull($game->getInviteCode());
        $this->assertEquals(8, \strlen($game->getInviteCode()));
    }

    public function testCanBeViewedByWhenPublic(): void
    {
        $this->game->setIsPublic(true);

        $this->assertTrue($this->game->canBeViewedBy($this->player1));
    }

    public function testCanBeViewedByWhenPrivateAndIsPlayer(): void
    {
        $this->game->setIsPublic(false);

        $gamePlayer = new GamePlayer();
        $gamePlayer->setUser($this->player1);
        $gamePlayer->setStatus(PlayerStatus::ACTIVE);
        $this->game->addGamePlayer($gamePlayer);

        $this->assertTrue($this->game->canBeViewedBy($this->player1));
    }

    public function testCanBeViewedByWhenPrivateAndNotPlayer(): void
    {
        $this->game->setIsPublic(false);

        $this->assertFalse($this->game->canBeViewedBy($this->player1));
    }

    public function testCanBeViewedByWithNullUserId(): void
    {
        $this->game->setIsPublic(false);
        $userWithoutId = new User();

        $this->assertFalse($this->game->canBeViewedBy($userWithoutId));
    }

    public function testIsGameMasterReturnsTrueForGM(): void
    {
        $this->assertTrue($this->game->isGameMaster($this->gameMaster));
    }

    public function testIsGameMasterReturnsFalseForOtherUser(): void
    {
        $this->assertFalse($this->game->isGameMaster($this->player1));
    }

    public function testIsGameMasterWithNullIds(): void
    {
        $userWithoutId = new User();

        $this->assertFalse($this->game->isGameMaster($userWithoutId));
    }

    public function testGetPlayerByUserReturnsPlayer(): void
    {
        $gamePlayer = new GamePlayer();
        $gamePlayer->setUser($this->player1);
        $this->game->addGamePlayer($gamePlayer);

        $result = $this->game->getPlayerByUser($this->player1);

        $this->assertSame($gamePlayer, $result);
    }

    public function testGetPlayerByUserReturnsNullWhenNotFound(): void
    {
        $result = $this->game->getPlayerByUser($this->player1);

        $this->assertNull($result);
    }

    public function testGetPlayerByUserWithNullUserId(): void
    {
        $userWithoutId = new User();

        $result = $this->game->getPlayerByUser($userWithoutId);

        $this->assertNull($result);
    }

    public function testHasPlayerReturnsTrueWhenUserIsPlayer(): void
    {
        $gamePlayer = new GamePlayer();
        $gamePlayer->setUser($this->player1);
        $this->game->addGamePlayer($gamePlayer);

        $this->assertTrue($this->game->hasPlayer($this->player1));
    }

    public function testHasPlayerReturnsFalseWhenUserIsNotPlayer(): void
    {
        $this->assertFalse($this->game->hasPlayer($this->player1));
    }

    public function testGetActivePlayersCountWithActivePlayers(): void
    {
        $activePlayer1 = new GamePlayer();
        $activePlayer1->setUser($this->player1);
        $activePlayer1->setStatus(PlayerStatus::ACTIVE);

        $activePlayer2 = new GamePlayer();
        $activePlayer2->setUser($this->player2);
        $activePlayer2->setStatus(PlayerStatus::ACTIVE);

        $this->game->addGamePlayer($activePlayer1);
        $this->game->addGamePlayer($activePlayer2);

        $this->assertEquals(2, $this->game->getActivePlayersCount());
    }

    public function testGetActivePlayersCountExcludesInactivePlayers(): void
    {
        $activePlayer = new GamePlayer();
        $activePlayer->setUser($this->player1);
        $activePlayer->setStatus(PlayerStatus::ACTIVE);

        $leftPlayer = new GamePlayer();
        $leftPlayer->setUser($this->player2);
        $leftPlayer->setStatus(PlayerStatus::LEFT);

        $this->game->addGamePlayer($activePlayer);
        $this->game->addGamePlayer($leftPlayer);

        $this->assertEquals(1, $this->game->getActivePlayersCount());
    }

    public function testGetActivePlayersCountIncludesPendingPlayers(): void
    {
        $activePlayer = new GamePlayer();
        $activePlayer->setUser($this->player1);
        $activePlayer->setStatus(PlayerStatus::ACTIVE);

        $pendingPlayer = new GamePlayer();
        $pendingPlayer->setUser($this->player2);
        $pendingPlayer->setStatus(PlayerStatus::PENDING);

        $this->game->addGamePlayer($activePlayer);
        $this->game->addGamePlayer($pendingPlayer);

        $this->assertEquals(2, $this->game->getActivePlayersCount());
    }

    public function testIsFullReturnsTrueWhenMaxReached(): void
    {
        $this->game->setMaxPlayers(2);

        for ($i = 0; $i < 2; ++$i) {
            $player = new GamePlayer();
            $user = new User();
            $this->setPrivateProperty($user, 'id', 10 + $i);
            $player->setUser($user);
            $player->setStatus(PlayerStatus::ACTIVE);
            $this->game->addGamePlayer($player);
        }

        $this->assertTrue($this->game->isFull());
    }

    public function testIsFullReturnsFalseWhenNotMaxReached(): void
    {
        $this->game->setMaxPlayers(3);

        $player = new GamePlayer();
        $player->setUser($this->player1);
        $player->setStatus(PlayerStatus::ACTIVE);
        $this->game->addGamePlayer($player);

        $this->assertFalse($this->game->isFull());
    }

    public function testIsFullExcludesLeftPlayers(): void
    {
        $this->game->setMaxPlayers(2);

        $activePlayer = new GamePlayer();
        $activePlayer->setUser($this->player1);
        $activePlayer->setStatus(PlayerStatus::ACTIVE);

        $leftPlayer = new GamePlayer();
        $leftPlayer->setUser($this->player2);
        $leftPlayer->setStatus(PlayerStatus::LEFT);

        $this->game->addGamePlayer($activePlayer);
        $this->game->addGamePlayer($leftPlayer);

        $this->assertFalse($this->game->isFull());
    }

    public function testGetCurrentPlayersCountReturnsCorrectCount(): void
    {
        $player1 = new GamePlayer();
        $player1->setUser($this->player1);
        $player1->setStatus(PlayerStatus::ACTIVE);

        $player2 = new GamePlayer();
        $player2->setUser($this->player2);
        $player2->setStatus(PlayerStatus::ACTIVE);

        $this->game->addGamePlayer($player1);
        $this->game->addGamePlayer($player2);

        $this->assertEquals(2, $this->game->getCurrentPlayersCount());
    }

    public function testOnPrePersistSetsTimestamps(): void
    {
        $this->assertNull($this->game->getCreatedAt());
        $this->assertNull($this->game->getUpdatedAt());

        $this->game->onPrePersist();

        $this->assertInstanceOf(DateTimeImmutable::class, $this->game->getCreatedAt());
        $this->assertInstanceOf(DateTimeImmutable::class, $this->game->getUpdatedAt());
    }

    public function testOnPreUpdateSetsUpdatedAt(): void
    {
        $originalCreatedAt = new DateTimeImmutable('2024-01-01');
        $this->setPrivateProperty($this->game, 'createdAt', $originalCreatedAt);

        $this->game->onPreUpdate();

        $this->assertEquals($originalCreatedAt, $this->game->getCreatedAt());
        $this->assertInstanceOf(DateTimeImmutable::class, $this->game->getUpdatedAt());
    }

    public function testAddGamePlayerAddsPlayerToCollection(): void
    {
        $gamePlayer = new GamePlayer();

        $this->game->addGamePlayer($gamePlayer);

        $this->assertTrue($this->game->getGamePlayers()->contains($gamePlayer));
        $this->assertSame($this->game, $gamePlayer->getGame());
    }

    public function testAddGamePlayerDoesNotAddDuplicate(): void
    {
        $gamePlayer = new GamePlayer();

        $this->game->addGamePlayer($gamePlayer);
        $this->game->addGamePlayer($gamePlayer);

        $this->assertEquals(1, $this->game->getGamePlayers()->count());
    }

    public function testRemoveGamePlayerRemovesFromCollection(): void
    {
        $gamePlayer = new GamePlayer();
        $this->game->addGamePlayer($gamePlayer);

        $this->game->removeGamePlayer($gamePlayer);

        $this->assertFalse($this->game->getGamePlayers()->contains($gamePlayer));
    }

    private function setPrivateProperty(object $object, string $property, mixed $value): void
    {
        $reflection = new ReflectionClass($object);
        $property = $reflection->getProperty($property);
        $property->setAccessible(true);
        $property->setValue($object, $value);
    }
}
