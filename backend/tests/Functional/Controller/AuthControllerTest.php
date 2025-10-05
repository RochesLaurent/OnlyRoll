<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests fonctionnels pour l'AuthController avec DTOs.
 *
 * Ces tests vérifient le comportement réel de l'API d'authentification
 * avec validation par DTOs et une vraie base de données de test
 *
 * @covers \App\Controller\AuthController
 */
class AuthControllerTest extends WebTestCase
{
    private $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    /**
     * POST /api/register - Inscription avec des données valides.
     */
    public function testItRegistersANewUserSuccessfully(): void
    {
        $this->client->request('POST', '/api/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'newuser@onlyroll.com',
            'password' => 'SecurePass123!',
            'pseudo' => 'NewGamer',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('message', $responseData);
        $this->assertArrayHasKey('user', $responseData);
        $this->assertEquals('User created successfully', $responseData['message']);
        $this->assertEquals('newuser@onlyroll.com', $responseData['user']['email']);
        $this->assertEquals('NewGamer', $responseData['user']['pseudo']);
        $this->assertArrayHasKey('id', $responseData['user']);

        // Vérifier les nouveaux champs du DTO
        $this->assertArrayHasKey('timezone', $responseData['user']);
        $this->assertArrayHasKey('language', $responseData['user']);
        $this->assertArrayHasKey('isVerified', $responseData['user']);
        $this->assertArrayHasKey('createdAt', $responseData['user']);
    }

    /**
     * POST /api/register - Échec avec des champs manquants (validation DTO).
     */
    public function testItFailsToRegisterWithMissingFields(): void
    {
        $this->client->request('POST', '/api/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'incomplete@onlyroll.com',
            // Manque password et pseudo
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        // Nouveau format d'erreur avec DTOs
        $this->assertArrayHasKey('error', $responseData);
        $this->assertEquals('Validation failed', $responseData['error']);
        $this->assertArrayHasKey('violations', $responseData);

        // Vérifier que les violations contiennent les champs manquants
        $violations = $responseData['violations'];
        $this->assertArrayHasKey('pseudo', $violations);
        $this->assertArrayHasKey('password', $violations);
    }

    /**
     * POST /api/register - Échec avec email invalide (validation DTO).
     */
    public function testItFailsToRegisterWithInvalidEmail(): void
    {
        $this->client->request('POST', '/api/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'invalid-email',  // Email invalide
            'password' => 'SecurePass123!',
            'pseudo' => 'TestUser',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals('Validation failed', $responseData['error']);
        $this->assertArrayHasKey('email', $responseData['violations']);
        $this->assertStringContainsString('email', strtolower($responseData['violations']['email']));
    }

    /**
     * POST /api/register - Échec avec mot de passe trop court (validation DTO).
     */
    public function testItFailsToRegisterWithShortPassword(): void
    {
        $this->client->request('POST', '/api/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'test@onlyroll.com',
            'password' => 'Short1',  // Moins de 8 caractères
            'pseudo' => 'TestUser',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals('Validation failed', $responseData['error']);
        $this->assertArrayHasKey('password', $responseData['violations']);
        $this->assertStringContainsString('8', $responseData['violations']['password']);
    }

    /**
     * POST /api/register - Échec avec mot de passe sans majuscule (validation DTO).
     */
    public function testItFailsToRegisterWithPasswordWithoutUppercase(): void
    {
        $this->client->request('POST', '/api/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'test@onlyroll.com',
            'password' => 'lowercase123',  // Pas de majuscule
            'pseudo' => 'TestUser',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals('Validation failed', $responseData['error']);
        $this->assertArrayHasKey('password', $responseData['violations']);
    }

    /**
     * POST /api/register - Échec avec pseudo trop court (validation DTO).
     */
    public function testItFailsToRegisterWithShortPseudo(): void
    {
        $this->client->request('POST', '/api/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'test@onlyroll.com',
            'password' => 'SecurePass123!',
            'pseudo' => 'ab',  // Moins de 3 caractères
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals('Validation failed', $responseData['error']);
        $this->assertArrayHasKey('pseudo', $responseData['violations']);
        $this->assertStringContainsString('3', $responseData['violations']['pseudo']);
    }

    /**
     * POST /api/register - Échec avec email déjà existant.
     */
    public function testItFailsToRegisterWithExistingEmail(): void
    {
        // Créer un premier utilisateur
        $this->client->request('POST', '/api/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'existing@onlyroll.com',
            'password' => 'Password123!',
            'pseudo' => 'ExistingUser',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        // Essayer de créer un deuxième avec le même email
        $this->client->request('POST', '/api/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'existing@onlyroll.com',  // Même email
            'password' => 'AnotherPass123!',
            'pseudo' => 'DifferentPseudo',
        ]));

        // Devrait retourner 409 Conflict avec le nouveau contrôleur
        $this->assertResponseStatusCodeSame(Response::HTTP_CONFLICT);

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertEquals('Email already exists', $responseData['error']);
    }

    /**
     * POST /api/register - Échec avec pseudo déjà existant.
     */
    public function testItFailsToRegisterWithExistingPseudo(): void
    {
        // Créer un premier utilisateur
        $this->client->request('POST', '/api/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'user1@onlyroll.com',
            'password' => 'Password123!',
            'pseudo' => 'SamePseudo',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        // Essayer de créer un deuxième avec le même pseudo
        $this->client->request('POST', '/api/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'user2@onlyroll.com',
            'password' => 'AnotherPass123!',
            'pseudo' => 'SamePseudo',  // Même pseudo
        ]));

        // Devrait retourner 409 Conflict avec le nouveau contrôleur
        $this->assertResponseStatusCodeSame(Response::HTTP_CONFLICT);

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertEquals('Pseudo already exists', $responseData['error']);
    }

    /**
     * POST /api/login - Connexion réussie avec JWT.
     */
    public function testItLogsInSuccessfullyAndReturnsJwtToken(): void
    {
        // D'abord créer un utilisateur
        $this->client->request('POST', '/api/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'testlogin@onlyroll.com',
            'password' => 'LoginPass123!',
            'pseudo' => 'LoginTester',
        ]));

        // Ensuite se connecter
        $this->client->request('POST', '/api/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'testlogin@onlyroll.com',
            'password' => 'LoginPass123!',
        ]));

        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('token', $responseData);
        $this->assertNotEmpty($responseData['token']);

        // Vérifier que c'est un JWT valide (3 parties séparées par des points)
        $tokenParts = explode('.', $responseData['token']);
        $this->assertCount(3, $tokenParts, 'JWT should have 3 parts');
    }

    /**
     * POST /api/login - Échec avec mauvais mot de passe.
     */
    public function testItFailsToLoginWithWrongPassword(): void
    {
        // Créer un utilisateur
        $this->client->request('POST', '/api/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'wrongpass@onlyroll.com',
            'password' => 'CorrectPass123!',
            'pseudo' => 'WrongPassUser',
        ]));

        // Essayer de se connecter avec un mauvais mot de passe
        $this->client->request('POST', '/api/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'wrongpass@onlyroll.com',
            'password' => 'WrongPassword!',  // Mauvais mot de passe
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * POST /api/login - Échec avec email inexistant.
     */
    public function testItFailsToLoginWithNonexistentEmail(): void
    {
        $this->client->request('POST', '/api/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'nonexistent@onlyroll.com',
            'password' => 'SomePassword123!',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * GET /api/me - Récupération du profil utilisateur authentifié.
     */
    public function testItReturnsCurrentUserProfileWhenAuthenticated(): void
    {
        // Créer et connecter un utilisateur
        $this->client->request('POST', '/api/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'profile@onlyroll.com',
            'password' => 'ProfilePass123!',
            'pseudo' => 'ProfileUser',
        ]));

        $this->client->request('POST', '/api/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'profile@onlyroll.com',
            'password' => 'ProfilePass123!',
        ]));

        $loginResponse = json_decode($this->client->getResponse()->getContent(), true);
        $token = $loginResponse['token'];

        // Accéder au profil avec le token
        $this->client->request('GET', '/api/me', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseIsSuccessful();

        $profileData = json_decode($this->client->getResponse()->getContent(), true);

        // Vérifications de base
        $this->assertEquals('profile@onlyroll.com', $profileData['email']);
        $this->assertEquals('ProfileUser', $profileData['pseudo']);
        $this->assertArrayHasKey('id', $profileData);
        $this->assertArrayHasKey('roles', $profileData);
        $this->assertContains('ROLE_USER', $profileData['roles']);

        // Vérifier les nouveaux champs du UserResponseDto
        $this->assertArrayHasKey('timezone', $profileData);
        $this->assertArrayHasKey('language', $profileData);
        $this->assertArrayHasKey('isVerified', $profileData);
        $this->assertArrayHasKey('createdAt', $profileData);
        $this->assertArrayHasKey('avatar', $profileData);

        // Vérifier les valeurs par défaut
        $this->assertEquals('UTC', $profileData['timezone']);
        $this->assertEquals('en', $profileData['language']);
        $this->assertTrue($profileData['isVerified']);
    }

    /**
     * GET /api/me - Échec sans authentification.
     */
    public function testItFailsToGetProfileWithoutAuthentication(): void
    {
        $this->client->request('GET', '/api/me');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * GET /api/me - Échec avec token invalide.
     */
    public function testItFailsToGetProfileWithInvalidToken(): void
    {
        $this->client->request('GET', '/api/me', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer invalid.token.here',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Scénario complet : Register → Login → Access Protected Route.
     */
    public function testItCompletesFullAuthenticationFlow(): void
    {
        // 1. S'inscrire
        $this->client->request('POST', '/api/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'fullflow@onlyroll.com',
            'password' => 'FullFlowPass123!',
            'pseudo' => 'FullFlowUser',
        ]));
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $registerData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('user', $registerData);
        $this->assertArrayHasKey('id', $registerData['user']);

        // 2. Se connecter
        $this->client->request('POST', '/api/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'fullflow@onlyroll.com',
            'password' => 'FullFlowPass123!',
        ]));
        $this->assertResponseIsSuccessful();

        $loginData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $loginData['token'];
        $this->assertNotEmpty($token);

        // 3. Accéder à une route protégée
        $this->client->request('GET', '/api/me', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);
        $this->assertResponseIsSuccessful();

        $profileData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('fullflow@onlyroll.com', $profileData['email']);
        $this->assertEquals('FullFlowUser', $profileData['pseudo']);

        // Vérifier la cohérence des données entre register et profile
        $this->assertEquals($registerData['user']['id'], $profileData['id']);
        $this->assertEquals($registerData['user']['email'], $profileData['email']);
        $this->assertEquals($registerData['user']['pseudo'], $profileData['pseudo']);
    }

    /**
     * Test de validation multiple - plusieurs erreurs en même temps.
     */
    public function testItReturnsMultipleValidationErrors(): void
    {
        $this->client->request('POST', '/api/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'invalid-email',  // Email invalide
            'password' => 'weak',         // Mot de passe trop court et sans majuscule
            'pseudo' => 'ab',             // Pseudo trop court
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals('Validation failed', $responseData['error']);
        $violations = $responseData['violations'];

        // Toutes les erreurs devraient être présentes
        $this->assertArrayHasKey('email', $violations);
        $this->assertArrayHasKey('password', $violations);
        $this->assertArrayHasKey('pseudo', $violations);
    }
}
