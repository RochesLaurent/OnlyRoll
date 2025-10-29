<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\Game\CreateGameDTO;
use App\DTO\Game\UpdateGameDTO;
use App\Entity\Game;
use App\Entity\GamePlayer;
use App\Entity\User;
use App\Enum\GameStatus;
use App\Enum\PlayerRole;
use App\Enum\PlayerStatus;
use App\Exception\Game\AccessDeniedException;
use App\Exception\Game\GameFullException;
use App\Exception\Game\GameNotFoundException;
use App\Exception\Game\InvalidPasswordException;
use App\Repository\GamePlayerRepository;
use App\Repository\GameRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

/**
 * Service de gestion des parties de jeu.
 */
final class GameService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly GameRepository $gameRepository,
        private readonly GamePlayerRepository $gamePlayerRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Crée une nouvelle partie.
     *
     * @param CreateGameDTO $dto Données de la partie à créer
     * @param User $gameMaster Utilisateur maître de jeu
     * @return Game La partie créée
     * @throws InvalidArgumentException Si le mot de passe est invalide pour une partie privée
     */
    public function createGame(CreateGameDTO $dto, User $gameMaster): Game
    {
        $this->logger->info('Creating new game', [
            'name' => $dto->name,
            'game_master_id' => $gameMaster->getId(),
        ]);

        if (!$dto->isPublic) {
            if (empty($dto->password)) {
                throw new InvalidArgumentException('Le mot de passe est requis pour une partie privée');
            }

            if (\strlen($dto->password) < 4 || \strlen($dto->password) > 50) {
                throw new InvalidArgumentException('Le mot de passe doit faire entre 4 et 50 caractères');
            }
        }

        $game = new Game();
        $game->setName($dto->name)
            ->setDescription($dto->description)
            ->setGameMaster($gameMaster)
            ->setMaxPlayers($dto->maxPlayers)
            ->setIsPublic($dto->isPublic);

        // Hash du mot de passe si partie privée
        if ($dto->password && !$dto->isPublic) {
            $game->setPassword(password_hash($dto->password, \PASSWORD_ARGON2ID));
        }

        $this->entityManager->persist($game);

        // Ajouter automatiquement le MJ comme joueur
        $gmPlayer = new GamePlayer();
        $gmPlayer->setGame($game)
                ->setUser($gameMaster)
                ->setRole(PlayerRole::GAME_MASTER)
                ->setStatus(PlayerStatus::ACTIVE);

        $this->entityManager->persist($gmPlayer);
        $this->entityManager->flush();

        $this->logger->info('Game created successfully', ['game_id' => $game->getId()]);

        return $game;
    }

    /**
     * Met à jour une partie.
     */
    public function updateGame(Game $game, UpdateGameDTO $dto, User $user): Game
    {
        if (!$game->isGameMaster($user)) {
            throw new AccessDeniedException('Seul le MJ peut modifier cette partie');
        }

        if (null !== $dto->name) {
            $game->setName($dto->name);
        }

        if (null !== $dto->description) {
            $game->setDescription($dto->description);
        }

        if (null !== $dto->maxPlayers) {
            $game->setMaxPlayers($dto->maxPlayers);
        }

        if (null !== $dto->isPublic) {
            $game->setIsPublic($dto->isPublic);
        }

        if (null !== $dto->status) {
            $this->updateGameStatus($game, $dto->status);
        }

        $this->entityManager->flush();

        return $game;
    }

    /**
     * Permet à un joueur de rejoindre une partie.
     */
    public function joinGame(int $gameId, User $user, ?string $password = null): GamePlayer
    {
        $game = $this->gameRepository->findGameWithPlayers($gameId);

        if (!$game) {
            throw new GameNotFoundException();
        }

        // Vérifications
        $this->validateGameJoinability($game, $user, $password);

        // Créer le GamePlayer
        $gamePlayer = new GamePlayer();
        $gamePlayer->setGame($game)
                   ->setUser($user)
                   ->setRole(PlayerRole::PLAYER)
                   ->setStatus(PlayerStatus::ACTIVE);

        $this->entityManager->persist($gamePlayer);
        $this->entityManager->flush();

        $this->logger->info('User joined game', [
            'user_id' => $user->getId(),
            'game_id' => $game->getId(),
        ]);

        return $gamePlayer;
    }

    /**
     * Permet à un joueur de quitter une partie.
     */
    public function leaveGame(Game $game, User $user): void
    {
        $gamePlayer = $this->gamePlayerRepository->findPlayerInGame($game, $user);

        if (!$gamePlayer) {
            throw new GameNotFoundException('Vous ne faites pas partie de cette partie');
        }

        if ($game->isGameMaster($user)) {
            throw new AccessDeniedException('Le MJ ne peut pas quitter sa propre partie');
        }

        $gamePlayer->setStatus(PlayerStatus::LEFT)
                   ->setLeftAt(new DateTimeImmutable());

        $this->entityManager->flush();

        $this->logger->info('User left game', [
            'user_id' => $user->getId(),
            'game_id' => $game->getId(),
        ]);
    }

    /**
     * Supprime une partie (soft delete ou hard selon vos besoins).
     */
    public function deleteGame(Game $game, User $user): void
    {
        if (!$game->isGameMaster($user)) {
            throw new AccessDeniedException();
        }

        // Archiver plutôt que supprimer
        $game->setStatus(GameStatus::ARCHIVED);
        $this->entityManager->flush();

        $this->logger->info('Game archived', ['game_id' => $game->getId()]);
    }

    /**
     * Valide qu'un utilisateur peut rejoindre une partie.
     */
    private function validateGameJoinability(Game $game, User $user, ?string $password): void
    {
        // Déjà dans la partie ?
        if ($this->gamePlayerRepository->isUserInGame($game, $user)) {
            throw new AccessDeniedException('Vous faites déjà partie de cette partie');
        }

        // Partie complète ?
        if ($game->isFull()) {
            throw new GameFullException();
        }

        // Vérification mot de passe pour parties privées
        if (!$game->isPublic()) {
            $gamePassword = $game->getPassword();
            if (!$password || !$gamePassword || !password_verify($password, $gamePassword)) {
                throw new InvalidPasswordException();
            }
        }

        // La partie est-elle en préparation ou en cours ?
        if (!\in_array($game->getStatus(), [GameStatus::PREPARATION, GameStatus::IN_PROGRESS])) {
            throw new AccessDeniedException('Cette partie n\'accepte plus de nouveaux joueurs');
        }
    }

    /**
     * Met à jour le statut avec logique métier.
     */
    private function updateGameStatus(Game $game, GameStatus $newStatus): void
    {
        $oldStatus = $game->getStatus();

        // Logique de transition de statuts
        if (GameStatus::IN_PROGRESS === $newStatus && GameStatus::PREPARATION === $oldStatus) {
            $game->setStartedAt(new DateTimeImmutable());
        }

        if (GameStatus::COMPLETED === $newStatus) {
            $game->setCompletedAt(new DateTimeImmutable());
        }

        $game->setStatus($newStatus);
    }
}
