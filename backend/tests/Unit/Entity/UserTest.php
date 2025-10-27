<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\User;
use DateTimeImmutable;
use LogicException;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        $this->user = new User();
    }

    public function testConstructorSetsDefaultValues(): void
    {
        $this->assertNull($this->user->getId());
        $this->assertSame(['ROLE_USER'], $this->user->getRoles());
        $this->assertInstanceOf(DateTimeImmutable::class, $this->user->getCreatedAt());
        $this->assertInstanceOf(DateTimeImmutable::class, $this->user->getUpdatedAt());
        $this->assertSame('UTC', $this->user->getTimezone());
        $this->assertSame('en', $this->user->getLanguage());
        $this->assertFalse($this->user->isVerified());
        $this->assertNull($this->user->getLastLogin());
        $this->assertNull($this->user->getAvatar());
    }

    public function testEmailGetterAndSetter(): void
    {
        $email = 'test@example.com';
        $result = $this->user->setEmail($email);

        $this->assertSame($this->user, $result);
        $this->assertSame($email, $this->user->getEmail());
    }

    public function testGetUserIdentifier(): void
    {
        $email = 'user@example.com';
        $this->user->setEmail($email);

        $this->assertSame($email, $this->user->getUserIdentifier());
    }

    public function testGetUserIdentifierThrowsExceptionWhenEmailIsEmpty(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('User email cannot be empty');

        $this->user->getUserIdentifier();
    }

    public function testPseudoGetterAndSetter(): void
    {
        $pseudo = 'TestUser';
        $result = $this->user->setPseudo($pseudo);

        $this->assertSame($this->user, $result);
        $this->assertSame($pseudo, $this->user->getPseudo());
    }

    public function testRolesGetterAndSetter(): void
    {
        $roles = ['ROLE_ADMIN', 'ROLE_MODERATOR'];
        $result = $this->user->setRoles($roles);

        $this->assertSame($this->user, $result);
        
        $expectedRoles = ['ROLE_ADMIN', 'ROLE_MODERATOR', 'ROLE_USER'];
        $this->assertEquals($expectedRoles, $this->user->getRoles());
    }

    public function testRolesAlwaysIncludeRoleUser(): void
    {
        $this->user->setRoles(['ROLE_ADMIN']);
        
        $roles = $this->user->getRoles();
        $this->assertContains('ROLE_USER', $roles);
    }

    public function testRolesAreUnique(): void
    {
        $this->user->setRoles(['ROLE_USER', 'ROLE_ADMIN', 'ROLE_USER']);
        
        $roles = $this->user->getRoles();
        $this->assertCount(2, $roles);
        $this->assertContains('ROLE_USER', $roles);
        $this->assertContains('ROLE_ADMIN', $roles);
    }

    public function testPasswordGetterAndSetter(): void
    {
        $password = 'hashed_password';
        $result = $this->user->setPassword($password);

        $this->assertSame($this->user, $result);
        $this->assertSame($password, $this->user->getPassword());
    }

    public function testEraseCredentials(): void
    {
        // Méthode vide, mais on teste qu'elle ne génère pas d'erreur
        $this->user->eraseCredentials();
        $this->assertTrue(true);
    }

    public function testTimezoneGetterAndSetter(): void
    {
        $timezone = 'Europe/Paris';
        $result = $this->user->setTimezone($timezone);

        $this->assertSame($this->user, $result);
        $this->assertSame($timezone, $this->user->getTimezone());
    }

    public function testLanguageGetterAndSetter(): void
    {
        $language = 'fr';
        $result = $this->user->setLanguage($language);

        $this->assertSame($this->user, $result);
        $this->assertSame($language, $this->user->getLanguage());
    }

    public function testAvatarGetterAndSetter(): void
    {
        $avatar = 'avatar.jpg';
        $result = $this->user->setAvatar($avatar);

        $this->assertSame($this->user, $result);
        $this->assertSame($avatar, $this->user->getAvatar());
    }

    public function testAvatarCanBeSetToNull(): void
    {
        $this->user->setAvatar('avatar.jpg');
        $this->user->setAvatar(null);

        $this->assertNull($this->user->getAvatar());
    }

    public function testCreatedAtGetterAndSetter(): void
    {
        $createdAt = new DateTimeImmutable('2024-01-01 10:00:00');
        $result = $this->user->setCreatedAt($createdAt);

        $this->assertSame($this->user, $result);
        $this->assertSame($createdAt, $this->user->getCreatedAt());
    }

    public function testUpdatedAtGetterAndSetter(): void
    {
        $updatedAt = new DateTimeImmutable('2024-01-02 10:00:00');
        $result = $this->user->setUpdatedAt($updatedAt);

        $this->assertSame($this->user, $result);
        $this->assertSame($updatedAt, $this->user->getUpdatedAt());
    }

    public function testLastLoginGetterAndSetter(): void
    {
        $lastLogin = new DateTimeImmutable('2024-01-03 10:00:00');
        $result = $this->user->setLastLogin($lastLogin);

        $this->assertSame($this->user, $result);
        $this->assertSame($lastLogin, $this->user->getLastLogin());
    }

    public function testLastLoginCanBeSetToNull(): void
    {
        $this->user->setLastLogin(new DateTimeImmutable());
        $this->user->setLastLogin(null);

        $this->assertNull($this->user->getLastLogin());
    }

    public function testIsVerified(): void
    {
        $this->assertFalse($this->user->isVerified());

        $result = $this->user->setIsVerified(true);

        $this->assertSame($this->user, $result);
        $this->assertTrue($this->user->isVerified());
    }

    public function testSetUpdatedAtValue(): void
    {
        $originalUpdatedAt = $this->user->getUpdatedAt();
        
        // Attendre un peu pour s'assurer que le temps change
        sleep(1);
        
        $this->user->setUpdatedAtValue();
        
        $this->assertGreaterThan($originalUpdatedAt, $this->user->getUpdatedAt());
    }
}
