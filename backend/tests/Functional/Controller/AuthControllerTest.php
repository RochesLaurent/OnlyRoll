<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests fonctionnels pour l'AuthController.
 *
 * Ces tests vérifient le comportement réel de l'API d'authentification
 * avec une vraie base de données de test
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
    }

    /**
     * POST /api/register - Échec avec des champs manquants.
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
        $this->assertArrayHasKey('error', $responseData);
        $this->assertEquals('Missing fields', $responseData['error']);
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

        // Essayer de créer un deuxième avec le même email
        $this->client->request('POST', '/api/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'existing@onlyroll.com',  // Même email
            'password' => 'AnotherPass123!',
            'pseudo' => 'DifferentPseudo',
        ]));

        // Devrait échouer (contrainte unique sur l'email)
        $this->assertResponseStatusCodeSame(Response::HTTP_INTERNAL_SERVER_ERROR);
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

        // Essayer de créer un deuxième avec le même pseudo
        $this->client->request('POST', '/api/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'user2@onlyroll.com',
            'password' => 'AnotherPass123!',
            'pseudo' => 'SamePseudo',  // Même pseudo
        ]));

        // Devrait échouer (contrainte unique sur le pseudo)
        $this->assertResponseStatusCodeSame(Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * POST /api/login_check - Connexion réussie avec JWT.
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
        $this->client->request('POST', '/api/login_check', [], [], [
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
     * POST /api/login_check - Échec avec mauvais mot de passe.
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
        $this->client->request('POST', '/api/login_check', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'wrongpass@onlyroll.com',
            'password' => 'WrongPassword!',  // Mauvais mot de passe
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * POST /api/login_check - Échec avec email inexistant.
     */
    public function testItFailsToLoginWithNonexistentEmail(): void
    {
        $this->client->request('POST', '/api/login_check', [], [], [
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

        $this->client->request('POST', '/api/login_check', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'profile@onlyroll.com',
            'password' => 'ProfilePass123!',
        ]));

        $loginResponse = json_decode($this->client->getResponse()->getContent(), true);
        $token = $loginResponse['token'];

        // Accéder au profil avec le token
        $this->client->request('GET', '/api/me', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer '.$token,
        ]);

        $this->assertResponseIsSuccessful();

        $profileData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals('profile@onlyroll.com', $profileData['email']);
        $this->assertEquals('ProfileUser', $profileData['pseudo']);
        $this->assertArrayHasKey('id', $profileData);
        $this->assertArrayHasKey('roles', $profileData);
        $this->assertContains('ROLE_USER', $profileData['roles']);
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
     * POST /api/debug-login - Debug login fonctionne.
     */
    public function testItDebugsLoginSuccessfully(): void
    {
        // Créer un utilisateur
        $this->client->request('POST', '/api/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'debug@onlyroll.com',
            'password' => 'DebugPass123!',
            'pseudo' => 'DebugUser',
        ]));

        // Tester le debug login
        $this->client->request('POST', '/api/debug-login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'debug@onlyroll.com',
            'password' => 'DebugPass123!',
        ]));

        $this->assertResponseIsSuccessful();
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

        // 2. Se connecter
        $this->client->request('POST', '/api/login_check', [], [], [
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
            'HTTP_AUTHORIZATION' => 'Bearer '.$token,
        ]);
        $this->assertResponseIsSuccessful();

        $profileData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('fullflow@onlyroll.com', $profileData['email']);
        $this->assertEquals('FullFlowUser', $profileData['pseudo']);
    }
}
