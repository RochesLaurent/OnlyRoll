<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\GameMessage;
use App\Entity\User;
use App\Enum\MessageType;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class GameMessageTest extends TestCase
{
    private GameMessage $message;

    private User $sender;

    private User $recipient;

    protected function setUp(): void
    {
        $this->message = new GameMessage();
        $this->sender = new User();
        $this->recipient = new User();

        // Simuler des IDs via reflection pour les tests
        $this->setPrivateProperty($this->sender, 'id', 1);
        $this->setPrivateProperty($this->recipient, 'id', 2);
    }

    public function testIsDiceRoll(): void
    {
        $this->message->setType(MessageType::DICE_ROLL);
        $this->assertTrue($this->message->isDiceRoll());

        $this->message->setType(MessageType::CHAT);
        $this->assertFalse($this->message->isDiceRoll());
    }

    public function testIsSystemMessage(): void
    {
        $this->message->setType(MessageType::SYSTEM);
        $this->assertTrue($this->message->isSystemMessage());

        $this->message->setType(MessageType::CHAT);
        $this->assertFalse($this->message->isSystemMessage());
    }

    public function testIsWhisper(): void
    {
        $this->message->setType(MessageType::WHISPER);
        $this->assertTrue($this->message->isWhisper());

        $this->message->setType(MessageType::CHAT);
        $this->assertFalse($this->message->isWhisper());
    }

    public function testIsEmote(): void
    {
        $this->message->setType(MessageType::EMOTE);
        $this->assertTrue($this->message->isEmote());

        $this->message->setType(MessageType::CHAT);
        $this->assertFalse($this->message->isEmote());
    }

    public function testIsChat(): void
    {
        $this->message->setType(MessageType::CHAT);
        $this->assertTrue($this->message->isChat());

        $this->message->setType(MessageType::SYSTEM);
        $this->assertFalse($this->message->isChat());
    }

    public function testCanBeSeenByWithSystemMessage(): void
    {
        $this->message->setType(MessageType::SYSTEM);
        $randomUser = new User();

        $this->assertTrue($this->message->canBeSeenBy($randomUser));
    }

    public function testCanBeSeenByWithWhisperForSender(): void
    {
        $this->message->setType(MessageType::WHISPER);
        $this->message->setUser($this->sender);
        $this->message->setRecipient($this->recipient);

        $this->assertTrue($this->message->canBeSeenBy($this->sender));
    }

    public function testCanBeSeenByWithWhisperForRecipient(): void
    {
        $this->message->setType(MessageType::WHISPER);
        $this->message->setUser($this->sender);
        $this->message->setRecipient($this->recipient);

        $this->assertTrue($this->message->canBeSeenBy($this->recipient));
    }

    public function testCanBeSeenByWithWhisperForOtherUser(): void
    {
        $this->message->setType(MessageType::WHISPER);
        $this->message->setUser($this->sender);
        $this->message->setRecipient($this->recipient);

        $otherUser = new User();
        $this->setPrivateProperty($otherUser, 'id', 3);

        $this->assertFalse($this->message->canBeSeenBy($otherUser));
    }

    public function testCanBeSeenByWithWhisperAndNullIds(): void
    {
        $this->message->setType(MessageType::WHISPER);
        $this->message->setUser($this->sender);

        $userWithoutId = new User();

        $this->assertFalse($this->message->canBeSeenBy($userWithoutId));
    }

    public function testCanBeSeenByWithRegularMessage(): void
    {
        $this->message->setType(MessageType::CHAT);
        $randomUser = new User();

        $this->assertTrue($this->message->canBeSeenBy($randomUser));
    }

    public function testGetDiceTotalWithDiceRoll(): void
    {
        $this->message->setType(MessageType::DICE_ROLL);
        $this->message->setDiceResult(['total' => 15, 'rolls' => [4, 5, 6]]);

        $this->assertEquals(15, $this->message->getDiceTotal());
    }

    public function testGetDiceTotalWithoutDiceRoll(): void
    {
        $this->message->setType(MessageType::CHAT);

        $this->assertNull($this->message->getDiceTotal());
    }

    public function testGetDiceTotalWithNullResult(): void
    {
        $this->message->setType(MessageType::DICE_ROLL);
        $this->message->setDiceResult(null);

        $this->assertNull($this->message->getDiceTotal());
    }

    public function testGetFormattedContentForChat(): void
    {
        $this->message->setType(MessageType::CHAT);
        $this->message->setContent('Hello world');

        $this->assertEquals('Hello world', $this->message->getFormattedContent());
    }

    public function testGetFormattedContentForEmote(): void
    {
        $this->message->setType(MessageType::EMOTE);
        $this->message->setContent('waves hand');

        $this->assertEquals('*waves hand*', $this->message->getFormattedContent());
    }

    public function testGetFormattedContentForWhisper(): void
    {
        $this->message->setType(MessageType::WHISPER);
        $this->message->setContent('secret message');

        $this->assertEquals('[Chuchotement] secret message', $this->message->getFormattedContent());
    }

    public function testGetFormattedContentForSystem(): void
    {
        $this->message->setType(MessageType::SYSTEM);
        $this->message->setContent('User joined');

        $this->assertEquals('[Système] User joined', $this->message->getFormattedContent());
    }

    public function testGetFormattedContentWithNullContent(): void
    {
        $this->message->setType(MessageType::CHAT);

        $this->assertNull($this->message->getFormattedContent());
    }

    public function testOnPrePersistSetsCreatedAt(): void
    {
        $this->assertNull($this->message->getCreatedAt());

        $this->message->onPrePersist();

        $this->assertInstanceOf(DateTimeImmutable::class, $this->message->getCreatedAt());
    }

    /**
     * Méthode helper pour modifier les propriétés privées via reflection
     */
    private function setPrivateProperty(object $object, string $property, mixed $value): void
    {
        $reflection = new ReflectionClass($object);
        $property = $reflection->getProperty($property);
        $property->setAccessible(true);
        $property->setValue($object, $value);
    }
}
