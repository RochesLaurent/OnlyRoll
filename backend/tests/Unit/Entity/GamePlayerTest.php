<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\GamePlayer;
use App\Enum\PlayerRole;
use App\Enum\PlayerStatus;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class GamePlayerTest extends TestCase
{
    private GamePlayer $gamePlayer;

    protected function setUp(): void
    {
        $this->gamePlayer = new GamePlayer();
    }

    public function testCanEditAsGameMaster(): void
    {
        $this->gamePlayer->setRole(PlayerRole::GAME_MASTER);

        $this->assertTrue($this->gamePlayer->canEdit());
    }

    public function testCanEditAsPlayer(): void
    {
        $this->gamePlayer->setRole(PlayerRole::PLAYER);

        $this->assertFalse($this->gamePlayer->canEdit());
    }

    public function testIsParticipatingWithActiveStatus(): void
    {
        $this->gamePlayer->setStatus(PlayerStatus::ACTIVE);

        $this->assertTrue($this->gamePlayer->isParticipating());
    }

    public function testIsParticipatingWithPendingStatus(): void
    {
        $this->gamePlayer->setStatus(PlayerStatus::PENDING);

        $this->assertTrue($this->gamePlayer->isParticipating());
    }

    public function testIsParticipatingWithLeftStatus(): void
    {
        $this->gamePlayer->setStatus(PlayerStatus::LEFT);

        $this->assertFalse($this->gamePlayer->isParticipating());
    }

    public function testIsParticipatingWithKickedStatus(): void
    {
        $this->gamePlayer->setStatus(PlayerStatus::KICKED);

        $this->assertFalse($this->gamePlayer->isParticipating());
    }

    public function testCanReactivateWhenLeft(): void
    {
        $this->gamePlayer->setStatus(PlayerStatus::LEFT);

        $this->assertTrue($this->gamePlayer->canReactivate());
    }

    public function testCanReactivateWhenKicked(): void
    {
        $this->gamePlayer->setStatus(PlayerStatus::KICKED);

        $this->assertTrue($this->gamePlayer->canReactivate());
    }

    public function testCanReactivateWhenActive(): void
    {
        $this->gamePlayer->setStatus(PlayerStatus::ACTIVE);

        $this->assertFalse($this->gamePlayer->canReactivate());
    }

    public function testLeave(): void
    {
        $this->gamePlayer->setStatus(PlayerStatus::ACTIVE);
        $this->assertNull($this->gamePlayer->getLeftAt());

        $this->gamePlayer->leave();

        $this->assertEquals(PlayerStatus::LEFT, $this->gamePlayer->getStatus());
        $this->assertInstanceOf(DateTimeImmutable::class, $this->gamePlayer->getLeftAt());
    }

    public function testKick(): void
    {
        $this->gamePlayer->setStatus(PlayerStatus::ACTIVE);
        $this->assertNull($this->gamePlayer->getLeftAt());

        $this->gamePlayer->kick();

        $this->assertEquals(PlayerStatus::KICKED, $this->gamePlayer->getStatus());
        $this->assertInstanceOf(DateTimeImmutable::class, $this->gamePlayer->getLeftAt());
    }

    public function testActivate(): void
    {
        $this->gamePlayer->setStatus(PlayerStatus::LEFT);
        $this->gamePlayer->setLeftAt(new DateTimeImmutable());

        $this->gamePlayer->activate();

        $this->assertEquals(PlayerStatus::ACTIVE, $this->gamePlayer->getStatus());
        $this->assertNull($this->gamePlayer->getLeftAt());
    }

    public function testOnPrePersistSetsJoinedAt(): void
    {
        $this->assertNull($this->gamePlayer->getJoinedAt());

        $this->gamePlayer->onPrePersist();

        $this->assertInstanceOf(DateTimeImmutable::class, $this->gamePlayer->getJoinedAt());
    }

    public function testOnPrePersistDoesNotOverrideExistingJoinedAt(): void
    {
        $originalDate = new DateTimeImmutable('2024-01-01');
        $this->gamePlayer->setJoinedAt($originalDate);

        $this->gamePlayer->onPrePersist();

        $this->assertEquals($originalDate, $this->gamePlayer->getJoinedAt());
    }
}
