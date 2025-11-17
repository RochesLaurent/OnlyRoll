<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\Game;
use App\Entity\GamePlayer;
use App\Entity\User;
use App\Enum\GameStatus;
use App\Enum\PlayerRole;
use App\Enum\PlayerStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class GameControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    private EntityManagerInterface $entityManager;

    private User $testUser;

    private User $otherUser;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $this->cleanDatabase();

        $this->testUser = $this->createUser('test@example.com', 'TestUser');
        $this->otherUser = $this->createUser('other@example.com', 'OtherUser');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }

    // ==================== LIST ====================

    public function testListGamesReturnsPublicGames(): void
    {
        $this->createPublicGame('Public Game 1', $this->testUser);
        $this->createPublicGame('Public Game 2', $this->otherUser);
        $this->createPrivateGame('Private Game', $this->testUser, 'password');

        $this->client->loginUser($this->testUser);
        $this->client->request('GET', '/api/games');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('meta', $response);
        $this->assertGreaterThanOrEqual(2, \count($response['data']));
    }

    public function testListGamesWithPagination(): void
    {
        for ($i = 1; $i <= 15; ++$i) {
            $this->createPublicGame("Game $i", $this->testUser);
        }

        $this->client->loginUser($this->testUser);
        $this->client->request('GET', '/api/games?page=1&limit=10');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertCount(10, $response['data']);
        $this->assertSame(15, $response['meta']['total']);
        $this->assertSame(1, $response['meta']['page']);
        $this->assertSame(10, $response['meta']['limit']);
        $this->assertSame(2, $response['meta']['totalPages']);
    }

    public function testListGamesWithSearchFilter(): void
    {
        $this->createPublicGame('Dragon Quest', $this->testUser);
        $this->createPublicGame('Knight Adventure', $this->testUser);
        $this->createPublicGame('Space Explorer', $this->testUser);

        $this->client->loginUser($this->testUser);
        $this->client->request('GET', '/api/games?search=Dragon');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertGreaterThanOrEqual(1, \count($response['data']));
    }

    public function testListGamesRequiresAuthentication(): void
    {
        $this->client->request('GET', '/api/games');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    // ==================== MY GAMES ====================

    public function testMyGamesReturnsUserGames(): void
    {
        $game1 = $this->createPublicGame('My Game 1', $this->testUser);
        $game2 = $this->createPublicGame('Other Game', $this->otherUser);

        // Rejoindre une autre partie
        $this->addPlayerToGame($game2, $this->testUser, PlayerRole::PLAYER);

        $this->client->loginUser($this->testUser);
        $this->client->request('GET', '/api/games/my-games');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertIsArray($response);
        $this->assertGreaterThanOrEqual(2, \count($response));
    }

    public function testMyGamesRequiresAuthentication(): void
    {
        $this->client->request('GET', '/api/games/my-games');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    // ==================== SHOW ====================

    public function testShowGameReturnsGameDetails(): void
    {
        $game = $this->createPublicGame('Test Game', $this->testUser);

        $this->client->loginUser($this->testUser);
        $this->client->request('GET', '/api/games/' . $game->getId());

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('id', $response);
        $this->assertSame('Test Game', $response['name']);
    }

    public function testShowGameReturnsNotFoundForInvalidId(): void
    {
        $this->client->loginUser($this->testUser);
        $this->client->request('GET', '/api/games/99999');

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $response);
        $this->assertSame('Partie introuvable', $response['error']);
    }

    public function testShowGameReturnsForbiddenForPrivateGame(): void
    {
        $game = $this->createPrivateGame('Private Game', $this->otherUser, 'secret');

        $this->client->loginUser($this->testUser);
        $this->client->request('GET', '/api/games/' . $game->getId());

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    // ==================== CREATE ====================

    public function testCreateGameWithValidData(): void
    {
        $this->client->loginUser($this->testUser);
        $this->client->request('POST', '/api/games', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'name' => 'New Game',
            'description' => 'A new test game',
            'maxPlayers' => 6,
            'isPublic' => true,
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('id', $response);
        $this->assertSame('New Game', $response['name']);
        $this->assertSame('A new test game', $response['description']);
    }

    public function testCreatePrivateGameWithPassword(): void
    {
        $this->client->loginUser($this->testUser);
        $this->client->request('POST', '/api/games', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'name' => 'Private Game',
            'description' => 'Secret game',
            'maxPlayers' => 4,
            'isPublic' => false,
            'password' => 'secretpass',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertFalse($response['isPublic']);
    }

    public function testCreateGameWithInvalidDataReturnsBadRequest(): void
    {
        $this->client->loginUser($this->testUser);
        $this->client->request('POST', '/api/games', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'name' => '', // Nom vide
            'maxPlayers' => 6,
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    // ==================== UPDATE ====================

    public function testUpdateGameAsGameMaster(): void
    {
        $game = $this->createPublicGame('Original Name', $this->testUser);

        $this->client->loginUser($this->testUser);
        $this->client->request('PUT', '/api/games/' . $game->getId(), [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'name' => 'Updated Name',
            'description' => 'Updated description',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertSame('Updated Name', $response['name']);
    }

    public function testUpdateGameAsNonGameMasterReturnsForbidden(): void
    {
        $game = $this->createPublicGame('Test Game', $this->testUser);

        $this->client->loginUser($this->otherUser);
        $this->client->request('PUT', '/api/games/' . $game->getId(), [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'name' => 'Hacked Name',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testUpdateGameReturnsNotFoundForInvalidId(): void
    {
        $this->client->loginUser($this->testUser);
        $this->client->request('PUT', '/api/games/99999', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'name' => 'Updated Name',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    // ==================== JOIN BY CODE ====================

    public function testJoinByCodeWithValidInviteCode(): void
    {
        $game = $this->createPublicGame('Test Game', $this->testUser);
        $inviteCode = $game->getInviteCode();

        $this->client->loginUser($this->otherUser);
        $this->client->request('POST', '/api/games/join', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'inviteCode' => $inviteCode,
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testJoinByCodeWithInvalidInviteCode(): void
    {
        $this->client->loginUser($this->testUser);
        $this->client->request('POST', '/api/games/join', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'inviteCode' => 'INVALID123',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame('Code d\'invitation invalide', $response['error']);
    }

    public function testJoinByCodeWithoutInviteCode(): void
    {
        $this->client->loginUser($this->testUser);
        $this->client->request('POST', '/api/games/join', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([]));

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame('Code d\'invitation requis', $response['error']);
    }

    // ==================== JOIN ====================

    public function testJoinPublicGame(): void
    {
        $game = $this->createPublicGame('Test Game', $this->testUser);

        $this->client->loginUser($this->otherUser);
        $this->client->request('POST', '/api/games/' . $game->getId() . '/join', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([]));

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testJoinPrivateGameWithCorrectPassword(): void
    {
        $game = $this->createPrivateGame('Private Game', $this->testUser, 'secret123');

        $this->client->loginUser($this->otherUser);
        $this->client->request('POST', '/api/games/' . $game->getId() . '/join', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'password' => 'secret123',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testJoinPrivateGameWithIncorrectPassword(): void
    {
        $game = $this->createPrivateGame('Private Game', $this->testUser, 'secret123');

        $this->client->loginUser($this->otherUser);
        $this->client->request('POST', '/api/games/' . $game->getId() . '/join', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'password' => 'wrongpassword',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    // ==================== LEAVE ====================

    public function testLeaveGame(): void
    {
        $game = $this->createPublicGame('Test Game', $this->testUser);
        $this->addPlayerToGame($game, $this->otherUser, PlayerRole::PLAYER);

        $this->client->loginUser($this->otherUser);
        $this->client->request('POST', '/api/games/' . $game->getId() . '/leave');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame('Vous avez quitté la partie', $response['message']);
    }

    public function testLeaveGameReturnsNotFoundForInvalidId(): void
    {
        $this->client->loginUser($this->testUser);
        $this->client->request('POST', '/api/games/99999/leave');

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    // ==================== DELETE ====================

    public function testDeleteGameAsGameMaster(): void
    {
        $game = $this->createPublicGame('Test Game', $this->testUser);

        $this->client->loginUser($this->testUser);
        $this->client->request('DELETE', '/api/games/' . $game->getId());

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame('Partie archivée avec succès', $response['message']);
    }

    public function testDeleteGameAsNonGameMasterReturnsForbidden(): void
    {
        $game = $this->createPublicGame('Test Game', $this->testUser);

        $this->client->loginUser($this->otherUser);
        $this->client->request('DELETE', '/api/games/' . $game->getId());

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testDeleteGameReturnsNotFoundForInvalidId(): void
    {
        $this->client->loginUser($this->testUser);
        $this->client->request('DELETE', '/api/games/99999');

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    // ==================== HELPERS ====================

    private function createUser(string $email, string $pseudo): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setPseudo($pseudo);
        $user->setPassword('$2y$13$hashed_password');
        $user->setRoles(['ROLE_USER']);
        $user->setIsVerified(true);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private function createPublicGame(string $name, User $gameMaster): Game
    {
        $game = new Game();
        $game->setName($name);
        $game->setDescription('Test description');
        $game->setGameMaster($gameMaster);
        $game->setMaxPlayers(6);
        $game->setIsPublic(true);
        $game->setStatus(GameStatus::PREPARATION);

        $this->entityManager->persist($game);

        $gamePlayer = new GamePlayer();
        $gamePlayer->setGame($game);
        $gamePlayer->setUser($gameMaster);
        $gamePlayer->setRole(PlayerRole::GAME_MASTER);
        $gamePlayer->setStatus(PlayerStatus::ACTIVE);

        $this->entityManager->persist($gamePlayer);
        $this->entityManager->flush();

        return $game;
    }

    private function createPrivateGame(string $name, User $gameMaster, string $password): Game
    {
        $game = new Game();
        $game->setName($name);
        $game->setDescription('Private test game');
        $game->setGameMaster($gameMaster);
        $game->setMaxPlayers(6);
        $game->setIsPublic(false);
        $game->setPassword(password_hash($password, \PASSWORD_ARGON2ID));
        $game->setStatus(GameStatus::PREPARATION);

        $this->entityManager->persist($game);

        $gamePlayer = new GamePlayer();
        $gamePlayer->setGame($game);
        $gamePlayer->setUser($gameMaster);
        $gamePlayer->setRole(PlayerRole::GAME_MASTER);
        $gamePlayer->setStatus(PlayerStatus::ACTIVE);

        $this->entityManager->persist($gamePlayer);
        $this->entityManager->flush();

        return $game;
    }

    private function addPlayerToGame(Game $game, User $user, PlayerRole $role): void
    {
        $gamePlayer = new GamePlayer();
        $gamePlayer->setGame($game);
        $gamePlayer->setUser($user);
        $gamePlayer->setRole($role);
        $gamePlayer->setStatus(PlayerStatus::ACTIVE);

        $this->entityManager->persist($gamePlayer);
        $this->entityManager->flush();
    }

    private function cleanDatabase(): void
    {
        $gamePlayers = $this->entityManager->getRepository(GamePlayer::class)->findAll();
        foreach ($gamePlayers as $gamePlayer) {
            $this->entityManager->remove($gamePlayer);
        }

        $games = $this->entityManager->getRepository(Game::class)->findAll();
        foreach ($games as $game) {
            $this->entityManager->remove($game);
        }

        $users = $this->entityManager->getRepository(User::class)->findAll();
        foreach ($users as $user) {
            $this->entityManager->remove($user);
        }

        $this->entityManager->flush();
    }
}
