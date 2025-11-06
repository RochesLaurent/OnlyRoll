<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Game;
use App\Entity\User;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;

/**
 * Service pour gérer la présence en temps réel des joueurs dans les parties.
 *
 * Ce service maintient en mémoire la liste des utilisateurs connectés à chaque partie
 * et publie les événements de présence via Mercure.
 */
class PresenceService
{
    /**
     * @var array<int, array<int, DateTimeImmutable>> Mapping gameId -> userId -> lastSeen
     */
    private array $onlineUsers = [];

    /**
     * Durée avant qu'un utilisateur soit considéré comme déconnecté (en secondes).
     */
    private const TIMEOUT_SECONDS = 60;

    public function __construct(
        private readonly MercurePublisher $mercurePublisher,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Marque un utilisateur comme connecté à une partie.
     */
    public function userJoined(Game $game, User $user): void
    {
        $gameId = $game->getId();
        $userId = $user->getId();

        if (null === $gameId || null === $userId) {
            return;
        }

        if (!isset($this->onlineUsers[$gameId])) {
            $this->onlineUsers[$gameId] = [];
        }

        $wasOnline = isset($this->onlineUsers[$gameId][$userId]);
        $this->onlineUsers[$gameId][$userId] = new DateTimeImmutable();

        // Ne publier que si c'était une nouvelle connexion
        if (!$wasOnline) {
            $this->mercurePublisher->publishPresenceEvent(
                $gameId,
                $userId,
                'join',
                $this->getOnlineUserIds($gameId),
            );

            $this->logger->info('User joined game', [
                'gameId' => $gameId,
                'userId' => $userId,
                'userName' => $user->getPseudo(),
            ]);
        }
    }

    /**
     * Marque un utilisateur comme déconnecté d'une partie.
     */
    public function userLeft(Game $game, User $user): void
    {
        $gameId = $game->getId();
        $userId = $user->getId();

        if (null === $gameId || null === $userId) {
            return;
        }

        if (isset($this->onlineUsers[$gameId][$userId])) {
            unset($this->onlineUsers[$gameId][$userId]);

            $this->mercurePublisher->publishPresenceEvent(
                $gameId,
                $userId,
                'leave',
                $this->getOnlineUserIds($gameId),
            );

            $this->logger->info('User left game', [
                'gameId' => $gameId,
                'userId' => $userId,
                'userName' => $user->getPseudo(),
            ]);

            // Nettoyer si aucun utilisateur n'est connecté
            if (empty($this->onlineUsers[$gameId])) {
                unset($this->onlineUsers[$gameId]);
            }
        }
    }

    /**
     * Met à jour le heartbeat d'un utilisateur.
     * Nettoie automatiquement les utilisateurs inactifs.
     */
    public function heartbeat(Game $game, User $user): void
    {
        $gameId = $game->getId();
        $userId = $user->getId();

        if (null === $gameId || null === $userId) {
            return;
        }

        // Nettoyer les utilisateurs inactifs
        $this->cleanupInactiveUsers($gameId);

        // Mettre à jour le timestamp
        if (!isset($this->onlineUsers[$gameId])) {
            $this->onlineUsers[$gameId] = [];
        }

        $this->onlineUsers[$gameId][$userId] = new DateTimeImmutable();

        // Publier la liste mise à jour
        $this->mercurePublisher->publishPresenceEvent(
            $gameId,
            $userId,
            'heartbeat',
            $this->getOnlineUserIds($gameId),
        );

        $this->logger->debug('Heartbeat received', [
            'gameId' => $gameId,
            'userId' => $userId,
            'onlineCount' => \count($this->onlineUsers[$gameId]),
        ]);
    }

    /**
     * Récupère la liste des IDs des utilisateurs connectés à une partie.
     *
     * @return array<int>
     */
    public function getOnlineUserIds(int $gameId): array
    {
        if (!isset($this->onlineUsers[$gameId])) {
            return [];
        }

        // Nettoyer les utilisateurs inactifs avant de retourner la liste
        $this->cleanupInactiveUsers($gameId);

        return array_keys($this->onlineUsers[$gameId]);
    }

    /**
     * Récupère le nombre d'utilisateurs connectés à une partie.
     */
    public function getOnlineCount(int $gameId): int
    {
        return \count($this->getOnlineUserIds($gameId));
    }

    /**
     * Vérifie si un utilisateur est connecté à une partie.
     */
    public function isUserOnline(int $gameId, int $userId): bool
    {
        if (!isset($this->onlineUsers[$gameId][$userId])) {
            return false;
        }

        // Vérifier si le timestamp n'est pas trop ancien
        $lastSeen = $this->onlineUsers[$gameId][$userId];
        $now = new DateTimeImmutable();

        if (($now->getTimestamp() - $lastSeen->getTimestamp()) > self::TIMEOUT_SECONDS) {
            unset($this->onlineUsers[$gameId][$userId]);

            return false;
        }

        return true;
    }

    /**
     * Nettoie les utilisateurs inactifs d'une partie.
     */
    private function cleanupInactiveUsers(int $gameId): void
    {
        if (!isset($this->onlineUsers[$gameId])) {
            return;
        }

        $now = new DateTimeImmutable();
        $removedUsers = [];

        foreach ($this->onlineUsers[$gameId] as $userId => $lastSeen) {
            if (($now->getTimestamp() - $lastSeen->getTimestamp()) > self::TIMEOUT_SECONDS) {
                unset($this->onlineUsers[$gameId][$userId]);
                $removedUsers[] = $userId;
            }
        }

        // Publier des événements de déconnexion pour les utilisateurs expirés
        foreach ($removedUsers as $userId) {
            $this->mercurePublisher->publishPresenceEvent(
                $gameId,
                $userId,
                'leave',
                $this->getOnlineUserIds($gameId),
            );

            $this->logger->info('User timed out', [
                'gameId' => $gameId,
                'userId' => $userId,
            ]);
        }

        // Nettoyer la partie si vide
        if (empty($this->onlineUsers[$gameId])) {
            unset($this->onlineUsers[$gameId]);
        }
    }

    /**
     * Nettoie toutes les données de présence (utile pour les tests).
     */
    public function clearAll(): void
    {
        $this->onlineUsers = [];
    }
}
