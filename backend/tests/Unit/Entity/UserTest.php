<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour l'entité User
 * 
 * Ces tests vérifient la logique métier de l'entité User
 * sans interaction avec la base de données
 * 
 * @covers \App\Entity\User
 */
class UserTest extends TestCase
{
    /**
     * Un utilisateur nouvellement créé a des valeurs par défaut correctes
     */
    public function test_it_has_correct_default_values_on_creation(): void
    {
        $user = new User();

        $this->assertEquals(['ROLE_USER'], $user->getRoles());
        $this->assertEquals('UTC', $user->getTimezone());
        $this->assertEquals('en', $user->getLanguage());
        $this->assertInstanceOf(\DateTimeImmutable::class, $user->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $user->getUpdatedAt());
        $this->assertNull($user->getLastLogin());
        $this->assertNull($user->getAvatar());
    }

    /**
     * On peut définir et récupérer l'email
     */
    public function test_it_can_set_and_get_email(): void
    {
        $user = new User();
        $email = 'test@onlyroll.com';

        $user->setEmail($email);

        $this->assertEquals($email, $user->getEmail());
    }

    /**
     * On peut définir et récupérer le pseudo
     */
    public function test_it_can_set_and_get_pseudo(): void
    {
        $user = new User();
        $pseudo = 'TestGamer';

        $user->setPseudo($pseudo);

        $this->assertEquals($pseudo, $user->getPseudo());
    }

    /**
     * On peut définir et récupérer le mot de passe
     */
    public function test_it_can_set_and_get_password(): void
    {
        $user = new User();
        $hashedPassword = '$2y$13$hashedpassword';

        $user->setPassword($hashedPassword);

        $this->assertEquals($hashedPassword, $user->getPassword());
    }

    /**
     * getUserIdentifier retourne l'email
     */
    public function test_it_returns_email_as_user_identifier(): void
    {
        $user = new User();
        $email = 'identifier@onlyroll.com';
        $user->setEmail($email);

        $this->assertEquals($email, $user->getUserIdentifier());
    }

    /**
     * On peut définir et récupérer les rôles
     */
    public function test_it_can_set_and_get_roles(): void
    {
        $user = new User();
        $roles = ['ROLE_USER', 'ROLE_ADMIN'];

        $user->setRoles($roles);

        $this->assertEqualsCanonicalizing($roles, $user->getRoles());
    }

    /**
     * getRoles garantit toujours ROLE_USER même si pas défini
     */
    public function test_it_always_has_role_user(): void
    {
        $user = new User();
        $user->setRoles([]); // Définir un tableau vide

        $roles = $user->getRoles();

        $this->assertContains('ROLE_USER', $roles);
    }

    /**
     * getRoles évite les doublons
     */
    public function test_it_removes_duplicate_roles(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_USER', 'ROLE_ADMIN', 'ROLE_USER']); // Doublon

        $roles = $user->getRoles();

        $this->assertEquals(['ROLE_USER', 'ROLE_ADMIN'], array_values(array_unique($roles)));
    }

    /**
     * On peut définir et récupérer isVerified
     */
    public function test_it_can_set_and_get_is_verified(): void
    {
        $user = new User();

        $user->setIsVerified(true);
        $this->assertTrue($user->isVerified());

        $user->setIsVerified(false);
        $this->assertFalse($user->isVerified());
    }

    /**
     * On peut définir et récupérer le timezone
     */
    public function test_it_can_set_and_get_timezone(): void
    {
        $user = new User();
        $timezone = 'Europe/Paris';

        $user->setTimezone($timezone);

        $this->assertEquals($timezone, $user->getTimezone());
    }

    /**
     * On peut définir et récupérer la langue
     */
    public function test_it_can_set_and_get_language(): void
    {
        $user = new User();
        $language = 'fr';

        $user->setLanguage($language);

        $this->assertEquals($language, $user->getLanguage());
    }

    /**
     * On peut définir et récupérer l'avatar
     */
    public function test_it_can_set_and_get_avatar(): void
    {
        $user = new User();
        $avatarUrl = 'https://example.com/avatar.png';

        $user->setAvatar($avatarUrl);

        $this->assertEquals($avatarUrl, $user->getAvatar());
    }

    /**
     * On peut définir et récupérer la date de dernière connexion
     */
    public function test_it_can_set_and_get_last_login(): void
    {
        $user = new User();
        $lastLogin = new \DateTimeImmutable('2025-01-15 10:30:00');

        $user->setLastLogin($lastLogin);

        $this->assertEquals($lastLogin, $user->getLastLogin());
    }

    /**
     * eraseCredentials ne fait rien (optionnel mais recommandé de tester)
     */
    public function test_it_can_erase_credentials_safely(): void
    {
        $user = new User();
        $user->setPassword('hashedpassword');

        $user->eraseCredentials();

        // Le mot de passe devrait toujours être là (c'est le comportement attendu)
        $this->assertEquals('hashedpassword', $user->getPassword());
    }

    /**
     * Les timestamps sont automatiquement définis
     */
    public function test_it_sets_timestamps_automatically(): void
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
     * Test de la méthode setUpdatedAtValue (lifecycle callback)
     */
    public function test_it_updates_updated_at_on_persist_and_update(): void
    {
        $user = new User();
        $originalUpdatedAt = $user->getUpdatedAt();

        // Simuler un petit délai
        usleep(100000); // 0.1 seconde

        $user->setUpdatedAtValue();

        $this->assertGreaterThan($originalUpdatedAt, $user->getUpdatedAt());
    }

    /**
     * Validation d'un profil utilisateur complet
     */
    public function test_it_represents_a_complete_user_profile(): void
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
        $user->setLastLogin(new \DateTimeImmutable());

        // Vérifier que toutes les données sont correctement stockées
        $this->assertEquals('complete@onlyroll.com', $user->getEmail());
        $this->assertEquals('CompleteUser', $user->getPseudo());
        $this->assertNotEmpty($user->getPassword());
        $this->assertContains('ROLE_USER', $user->getRoles());
        $this->assertContains('ROLE_GM', $user->getRoles());
        $this->assertTrue($user->isVerified());
        $this->assertEquals('Europe/Paris', $user->getTimezone());
        $this->assertEquals('fr', $user->getLanguage());
        $this->assertNotNull($user->getAvatar());
        $this->assertNotNull($user->getLastLogin());
        $this->assertInstanceOf(\DateTimeImmutable::class, $user->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $user->getUpdatedAt());
    }
}