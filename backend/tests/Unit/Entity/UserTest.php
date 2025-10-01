<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour l'entité User.
 *
 * Ces tests vérifient la logique métier de l'entité User
 * sans interaction avec la base de données
 *
 * @covers \App\Entity\User
 */
class UserTest extends TestCase
{
    /**
     * Un utilisateur nouvellement créé a des valeurs par défaut correctes.
     */
    public function testItHasCorrectDefaultValuesOnCreation(): void
    {
        $user = new User();

        $this->assertEquals(['ROLE_USER'], $user->getRoles());
        $this->assertFalse($user->isVerified());
        $this->assertEquals('UTC', $user->getTimezone());
        $this->assertEquals('en', $user->getLanguage());
        $this->assertInstanceOf(\DateTimeImmutable::class, $user->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $user->getUpdatedAt());
        $this->assertNull($user->getLastLogin());
    }

    /**
     * On peut définir et récupérer l'email.
     */
    public function testItCanSetAndGetEmail(): void
    {
        $user = new User();
        $email = 'test@onlyroll.com';

        $user->setEmail($email);

        $this->assertEquals($email, $user->getEmail());
    }

    /**
     * On peut définir et récupérer le pseudo.
     */
    public function testItCanSetAndGetPseudo(): void
    {
        $user = new User();
        $pseudo = 'TestGamer';

        $user->setPseudo($pseudo);

        $this->assertEquals($pseudo, $user->getPseudo());
    }

    /**
     * On peut définir et récupérer le mot de passe.
     */
    public function testItCanSetAndGetPassword(): void
    {
        $user = new User();
        $hashedPassword = '$2y$13$hashedpassword';

        $user->setPassword($hashedPassword);

        $this->assertEquals($hashedPassword, $user->getPassword());
    }

    /**
     * getUserIdentifier retourne l'email.
     */
    public function testItReturnsEmailAsUserIdentifier(): void
    {
        $user = new User();
        $email = 'identifier@onlyroll.com';
        $user->setEmail($email);

        $this->assertEquals($email, $user->getUserIdentifier());
    }

    /**
     * On peut définir et récupérer les rôles.
     */
    public function testItCanSetAndGetRoles(): void
    {
        $user = new User();
        $roles = ['ROLE_USER', 'ROLE_ADMIN'];

        $user->setRoles($roles);

        $this->assertEquals($roles, $user->getRoles());
    }

    /**
     * getRoles garantit toujours ROLE_USER même si pas défini.
     */
    public function testItAlwaysHasRoleUser(): void
    {
        $user = new User();
        $user->setRoles([]); // Définir un tableau vide

        $roles = $user->getRoles();

        $this->assertContains('ROLE_USER', $roles);
    }

    /**
     * getRoles évite les doublons.
     */
    public function testItRemovesDuplicateRoles(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_USER', 'ROLE_ADMIN', 'ROLE_USER']); // Doublon

        $roles = $user->getRoles();

        $this->assertCount(2, $roles); // Devrait avoir 2 rôles uniques
    }

    /**
     * On peut définir et récupérer isVerified.
     */
    public function testItCanSetAndGetIsVerified(): void
    {
        $user = new User();

        $user->setIsVerified(true);
        $this->assertTrue($user->isVerified());

        $user->setIsVerified(false);
        $this->assertFalse($user->isVerified());
    }

    /**
     * On peut définir et récupérer le timezone.
     */
    public function testItCanSetAndGetTimezone(): void
    {
        $user = new User();
        $timezone = 'Europe/Paris';

        $user->setTimezone($timezone);

        $this->assertEquals($timezone, $user->getTimezone());
    }

    /**
     * On peut définir et récupérer la langue.
     */
    public function testItCanSetAndGetLanguage(): void
    {
        $user = new User();
        $language = 'fr';

        $user->setLanguage($language);

        $this->assertEquals($language, $user->getLanguage());
    }

    /**
     * On peut définir et récupérer l'avatar.
     */
    public function testItCanSetAndGetAvatar(): void
    {
        $user = new User();
        $avatarUrl = 'https://example.com/avatar.png';

        $user->setAvatar($avatarUrl);

        $this->assertEquals($avatarUrl, $user->getAvatar());
    }

    /**
     * On peut définir et récupérer la date de dernière connexion.
     */
    public function testItCanSetAndGetLastLogin(): void
    {
        $user = new User();
        $lastLogin = new \DateTimeImmutable('2025-01-15 10:30:00');

        $user->setLastLogin($lastLogin);

        $this->assertEquals($lastLogin, $user->getLastLogin());
    }

    /**
     * eraseCredentials ne fait rien (optionnel mais recommandé de tester).
     */
    public function testItCanEraseCredentialsSafely(): void
    {
        $user = new User();
        $user->setPassword('hashedpassword');

        $user->eraseCredentials();

        // Le mot de passe doit toujours être présent car cette méthode ne fait rien
        $this->assertEquals('hashedpassword', $user->getPassword());
    }

    /**
     * Les timestamps sont automatiquement définis.
     */
    public function testItSetsTimestampsAutomatically(): void
    {
        $beforeCreation = new \DateTimeImmutable();
        $user = new User();
        $afterCreation = new \DateTimeImmutable();

        $this->assertGreaterThanOrEqual($beforeCreation, $user->getCreatedAt());
        $this->assertLessThanOrEqual($afterCreation, $user->getCreatedAt());

        $this->assertGreaterThanOrEqual($beforeCreation, $user->getUpdatedAt());
        $this->assertLessThanOrEqual($afterCreation, $user->getUpdatedAt());
    }

    /**
     * Test de la méthode setUpdatedAtValue (lifecycle callback).
     */
    public function testItUpdatesUpdatedAtOnPersistAndUpdate(): void
    {
        $user = new User();
        $originalUpdatedAt = $user->getUpdatedAt();

        sleep(1); // Attendre 1 seconde pour voir la différence

        $user->setUpdatedAtValue();

        $this->assertGreaterThan($originalUpdatedAt, $user->getUpdatedAt());
    }

    /**
     * Validation d'un profil utilisateur complet.
     */
    public function testItRepresentsACompleteUserProfile(): void
    {
        $user = new User();

        $user->setEmail('complete@onlyroll.com');
        $user->setPseudo('CompleteUser');
        $user->setPassword('$2y$13$hashedpassword');
        $user->setRoles(['ROLE_USER', 'ROLE_GM']);
        $user->setIsVerified(true);
        $user->setTimezone('Europe/Paris');
        $user->setLanguage('fr');
        $user->setAvatar('https://example.com/avatar.png');
        $user->setLastLogin(new \DateTimeImmutable('2025-01-15 10:30:00'));

        $this->assertEquals('complete@onlyroll.com', $user->getEmail());
        $this->assertEquals('CompleteUser', $user->getPseudo());
        $this->assertEquals('$2y$13$hashedpassword', $user->getPassword());
        $this->assertEquals(['ROLE_USER', 'ROLE_GM'], $user->getRoles());
        $this->assertTrue($user->isVerified());
        $this->assertEquals('Europe/Paris', $user->getTimezone());
        $this->assertEquals('fr', $user->getLanguage());
        $this->assertEquals('https://example.com/avatar.png', $user->getAvatar());
        $this->assertInstanceOf(\DateTimeImmutable::class, $user->getLastLogin());
        $this->assertInstanceOf(\DateTimeImmutable::class, $user->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $user->getUpdatedAt());
    }
}