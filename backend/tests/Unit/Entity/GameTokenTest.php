<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\GameToken;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class GameTokenTest extends TestCase
{
    private GameToken $token;

    protected function setUp(): void
    {
        $this->token = new GameToken();
        $this->token->setX(5);
        $this->token->setY(10);
        $this->token->setSize(2.0);
        $this->token->setRotation(45);
    }

    public function testMoveWhenNotLocked(): void
    {
        $this->token->setIsLocked(false);

        $result = $this->token->move(15, 20);

        $this->assertEquals(15, $this->token->getX());
        $this->assertEquals(20, $this->token->getY());
        $this->assertSame($this->token, $result);
    }

    public function testMoveWhenLocked(): void
    {
        $this->token->setIsLocked(true);

        $this->token->move(15, 20);

        $this->assertEquals(5, $this->token->getX());
        $this->assertEquals(10, $this->token->getY());
    }

    public function testRotateWhenNotLocked(): void
    {
        $this->token->setIsLocked(false);
        $this->token->setRotation(45);

        $result = $this->token->rotate(90);

        $this->assertEquals(135, $this->token->getRotation());
        $this->assertSame($this->token, $result);
    }

    public function testRotateWhenLocked(): void
    {
        $this->token->setIsLocked(true);
        $this->token->setRotation(45);

        $this->token->rotate(90);

        $this->assertEquals(45, $this->token->getRotation());
    }

    public function testRotateWrapsAround360(): void
    {
        $this->token->setIsLocked(false);
        $this->token->setRotation(350);

        $this->token->rotate(30);

        $this->assertEquals(20, $this->token->getRotation());
    }

    public function testRotateWithNegativeValue(): void
    {
        $this->token->setIsLocked(false);
        $this->token->setRotation(10);

        $this->token->rotate(-30);

        $this->assertEquals(340, $this->token->getRotation());
    }

    public function testShow(): void
    {
        $this->token->setIsVisible(false);

        $result = $this->token->show();

        $this->assertTrue($this->token->isVisible());
        $this->assertSame($this->token, $result);
    }

    public function testHide(): void
    {
        $this->token->setIsVisible(true);

        $result = $this->token->hide();

        $this->assertFalse($this->token->isVisible());
        $this->assertSame($this->token, $result);
    }

    public function testLock(): void
    {
        $this->token->setIsLocked(false);

        $result = $this->token->lock();

        $this->assertTrue($this->token->isLocked());
        $this->assertSame($this->token, $result);
    }

    public function testUnlock(): void
    {
        $this->token->setIsLocked(true);

        $result = $this->token->unlock();

        $this->assertFalse($this->token->isLocked());
        $this->assertSame($this->token, $result);
    }

    public function testIsAtReturnsTrueWhenAtPosition(): void
    {
        $this->token->setX(5);
        $this->token->setY(10);

        $this->assertTrue($this->token->isAt(5, 10));
    }

    public function testIsAtReturnsFalseWhenNotAtPosition(): void
    {
        $this->token->setX(5);
        $this->token->setY(10);

        $this->assertFalse($this->token->isAt(6, 10));
        $this->assertFalse($this->token->isAt(5, 11));
    }

    public function testGetPosition(): void
    {
        $this->token->setX(5);
        $this->token->setY(10);

        $position = $this->token->getPosition();

        $this->assertEquals(['x' => 5, 'y' => 10], $position);
    }

    public function testGetCenterPosition(): void
    {
        $this->token->setX(5);
        $this->token->setY(10);
        $this->token->setSize(2.0);

        $center = $this->token->getCenterPosition();

        $this->assertEquals(['x' => 6.0, 'y' => 11.0], $center);
    }

    public function testOccupiesCellWithSingleCell(): void
    {
        $this->token->setX(5);
        $this->token->setY(10);
        $this->token->setSize(1.0);

        $this->assertTrue($this->token->occupiesCell(5, 10));
        $this->assertFalse($this->token->occupiesCell(6, 10));
        $this->assertFalse($this->token->occupiesCell(5, 11));
    }

    public function testOccupiesCellWithMultipleCells(): void
    {
        $this->token->setX(5);
        $this->token->setY(10);
        $this->token->setSize(2.0);

        // Les 4 cellules occupées: (5,10), (6,10), (5,11), (6,11)
        $this->assertTrue($this->token->occupiesCell(5, 10));
        $this->assertTrue($this->token->occupiesCell(6, 10));
        $this->assertTrue($this->token->occupiesCell(5, 11));
        $this->assertTrue($this->token->occupiesCell(6, 11));

        // Cellules non occupées
        $this->assertFalse($this->token->occupiesCell(4, 10));
        $this->assertFalse($this->token->occupiesCell(7, 10));
        $this->assertFalse($this->token->occupiesCell(5, 9));
        $this->assertFalse($this->token->occupiesCell(5, 12));
    }

    public function testOccupiesCellWithFractionalSize(): void
    {
        $this->token->setX(5);
        $this->token->setY(10);
        $this->token->setSize(1.5);

        // ceil(1.5) = 2, donc occupe 2x2 cellules
        $this->assertTrue($this->token->occupiesCell(5, 10));
        $this->assertTrue($this->token->occupiesCell(6, 10));
        $this->assertTrue($this->token->occupiesCell(5, 11));
        $this->assertTrue($this->token->occupiesCell(6, 11));
        $this->assertFalse($this->token->occupiesCell(7, 10));
    }

    public function testOnPrePersistSetsTimestamps(): void
    {
        $this->assertNull($this->token->getCreatedAt());
        $this->assertNull($this->token->getUpdatedAt());

        $this->token->onPrePersist();

        $this->assertInstanceOf(DateTimeImmutable::class, $this->token->getCreatedAt());
        $this->assertInstanceOf(DateTimeImmutable::class, $this->token->getUpdatedAt());
    }

    public function testOnPreUpdateSetsUpdatedAt(): void
    {
        $originalCreatedAt = new DateTimeImmutable('2024-01-01');
        $this->setPrivateProperty($this->token, 'createdAt', $originalCreatedAt);

        $this->token->onPreUpdate();

        $this->assertEquals($originalCreatedAt, $this->token->getCreatedAt());
        $this->assertInstanceOf(DateTimeImmutable::class, $this->token->getUpdatedAt());
        $this->assertNotEquals($originalCreatedAt, $this->token->getUpdatedAt());
    }

    private function setPrivateProperty(object $object, string $property, mixed $value): void
    {
        $reflection = new ReflectionClass($object);
        $property = $reflection->getProperty($property);
        $property->setAccessible(true);
        $property->setValue($object, $value);
    }
}
