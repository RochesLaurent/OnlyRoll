<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\GameMap;
use App\Entity\GameToken;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class GameMapTest extends TestCase
{
    private GameMap $map;

    protected function setUp(): void
    {
        $this->map = new GameMap();
        $this->map->setWidth(20);
        $this->map->setHeight(15);
    }

    public function testGetTotalCells(): void
    {
        $this->assertEquals(300, $this->map->getTotalCells());
    }

    public function testGetTotalCellsWithDifferentDimensions(): void
    {
        $this->map->setWidth(10);
        $this->map->setHeight(5);

        $this->assertEquals(50, $this->map->getTotalCells());
    }

    public function testActivate(): void
    {
        $this->map->setIsActive(false);

        $result = $this->map->activate();

        $this->assertTrue($this->map->isActive());
        $this->assertSame($this->map, $result);
    }

    public function testDeactivate(): void
    {
        $this->map->setIsActive(true);

        $result = $this->map->deactivate();

        $this->assertFalse($this->map->isActive());
        $this->assertSame($this->map, $result);
    }

    public function testGetDimensions(): void
    {
        $this->map->setWidth(20);
        $this->map->setHeight(15);

        $this->assertEquals('20x15', $this->map->getDimensions());
    }

    public function testGetTokensCountWithNoTokens(): void
    {
        $this->assertEquals(0, $this->map->getTokensCount());
    }

    public function testGetTokensCountWithTokens(): void
    {
        $token1 = new GameToken();
        $token2 = new GameToken();

        $this->map->addToken($token1);
        $this->map->addToken($token2);

        $this->assertEquals(2, $this->map->getTokensCount());
    }

    public function testAddTokenAddsToCollection(): void
    {
        $token = new GameToken();

        $this->map->addToken($token);

        $this->assertTrue($this->map->getTokens()->contains($token));
        $this->assertSame($this->map, $token->getMap());
    }

    public function testAddTokenDoesNotAddDuplicate(): void
    {
        $token = new GameToken();

        $this->map->addToken($token);
        $this->map->addToken($token);

        $this->assertEquals(1, $this->map->getTokens()->count());
    }

    public function testRemoveTokenRemovesFromCollection(): void
    {
        $token = new GameToken();
        $this->map->addToken($token);

        $this->map->removeToken($token);

        $this->assertFalse($this->map->getTokens()->contains($token));
    }

    public function testOnPrePersistSetsTimestamps(): void
    {
        $this->assertNull($this->map->getCreatedAt());
        $this->assertNull($this->map->getUpdatedAt());

        $this->map->onPrePersist();

        $this->assertInstanceOf(DateTimeImmutable::class, $this->map->getCreatedAt());
        $this->assertInstanceOf(DateTimeImmutable::class, $this->map->getUpdatedAt());
    }

    public function testOnPreUpdateSetsUpdatedAt(): void
    {
        $originalCreatedAt = new DateTimeImmutable('2024-01-01');
        $this->setPrivateProperty($this->map, 'createdAt', $originalCreatedAt);

        $this->map->onPreUpdate();

        $this->assertEquals($originalCreatedAt, $this->map->getCreatedAt());
        $this->assertInstanceOf(DateTimeImmutable::class, $this->map->getUpdatedAt());
        $this->assertNotEquals($originalCreatedAt, $this->map->getUpdatedAt());
    }

    private function setPrivateProperty(object $object, string $property, mixed $value): void
    {
        $reflection = new ReflectionClass($object);
        $property = $reflection->getProperty($property);
        $property->setAccessible(true);
        $property->setValue($object, $value);
    }
}
