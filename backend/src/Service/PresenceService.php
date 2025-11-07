<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Game;
use App\Entity\User;
use DateTimeImmutable;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;

/**
 * Service pour gérer la présence en temps réel des joueurs dans les parties.
 *
 * Ce service utilise Redis (via le cache Symfony) pour stocker la liste des utilisateurs connectés
 * à chaque partie et publie les événements de présence via Mercure.
 */
class PresenceService
{
    /**
     * Durée avant qu'un utilisateur soit considéré comme déconnecté (en secondes).
     */
    private const TIMEOUT_SECONDS = 60;

    /**
     * Préfixe pour les clés de cache.
     */
    private const CACHE_PREFIX = 'presence_game_';

    public function __construct(
        private readonly MercurePublisher $mercurePublisher,
        private readonly LoggerInterface $logger,
        private readonly CacheItemPoolInterface $presenceCache,
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

        $onlineUsers = $this->getOnlineUsersFromCache($gameId);
        $wasOnline = isset($onlineUsers[$userId]);
        $onlineUsers[$userId] = new DateTimeImmutable();

        $this->saveOnlineUsersToCache($gameId, $onlineUsers);

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

        $onlineUsers = $this->getOnlineUsersFromCache($gameId);

        if (isset($onlineUsers[$userId])) {
            unset($onlineUsers[$userId]);
            $this->saveOnlineUsersToCache($gameId, $onlineUsers);

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
        $onlineUsers = $this->getOnlineUsersFromCache($gameId);
        $onlineUsers[$userId] = new DateTimeImmutable();
        $this->saveOnlineUsersToCache($gameId, $onlineUsers);

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
            'onlineCount' => \count($onlineUsers),
        ]);
    }

    /**
     * Récupère la liste des IDs des utilisateurs connectés à une partie.
     *
     * @return array<int>
     */
    public function getOnlineUserIds(int $gameId): array
    {
        // Nettoyer les utilisateurs inactifs avant de retourner la liste
        $this->cleanupInactiveUsers($gameId);

        $onlineUsers = $this->getOnlineUsersFromCache($gameId);

        return array_keys($onlineUsers);
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
        $onlineUsers = $this->getOnlineUsersFromCache($gameId);

        if (!isset($onlineUsers[$userId])) {
            return false;
        }

        // Vérifier si le timestamp n'est pas trop ancien
        $lastSeen = $onlineUsers[$userId];
        $now = time();

        if (($now - $lastSeen) > self::TIMEOUT_SECONDS) {
            // L'utilisateur a expiré
            unset($onlineUsers[$userId]);
            // Recréer avec DateTimeImmutable pour la sauvegarde
            $onlineUsersWithDates = [];
            foreach ($onlineUsers as $uid => $timestamp) {
                $onlineUsersWithDates[$uid] = (new DateTimeImmutable())->setTimestamp($timestamp);
            }
            $this->saveOnlineUsersToCache($gameId, $onlineUsersWithDates);

            return false;
        }

        return true;
    }

    /**
     * Nettoie les utilisateurs inactifs d'une partie.
     */
    private function cleanupInactiveUsers(int $gameId): void
    {
        $onlineUsers = $this->getOnlineUsersFromCache($gameId);

        if (empty($onlineUsers)) {
            return;
        }

        $now = time();
        $removedUsers = [];

        foreach ($onlineUsers as $userId => $lastSeen) {
            if (($now - $lastSeen) > self::TIMEOUT_SECONDS) {
                unset($onlineUsers[$userId]);
                $removedUsers[] = $userId;
            }
        }

        // Sauvegarder la liste mise à jour
        if (!empty($removedUsers)) {
            // Recréer avec DateTimeImmutable pour la sauvegarde
            $onlineUsersWithDates = [];
            foreach ($onlineUsers as $uid => $timestamp) {
                $onlineUsersWithDates[$uid] = (new DateTimeImmutable())->setTimestamp($timestamp);
            }
            $this->saveOnlineUsersToCache($gameId, $onlineUsersWithDates);

            // Publier des événements de déconnexion pour les utilisateurs expirés
            foreach ($removedUsers as $userId) {
                $this->mercurePublisher->publishPresenceEvent(
                    $gameId,
                    $userId,
                    'leave',
                    array_keys($onlineUsers),
                );

                $this->logger->info('User timed out', [
                    'gameId' => $gameId,
                    'userId' => $userId,
                ]);
            }
        }
    }

    /**
     * Récupère les utilisateurs en ligne depuis le cache Redis.
     *
     * @return array<int, int> Mapping userId -> timestamp
     */
    private function getOnlineUsersFromCache(int $gameId): array
    {
        $cacheKey = self::CACHE_PREFIX.$gameId;

        try {
            $item = $this->presenceCache->getItem($cacheKey);

            if (!$item->isHit()) {
                return [];
            }

            $data = $item->get();

            return is_array($data) ? $data : [];
        } catch (\Exception $e) {
            $this->logger->error('Error reading presence from cache', [
                'gameId' => $gameId,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Sauvegarde les utilisateurs en ligne dans le cache Redis.
     *
     * @param array<int, DateTimeImmutable> $onlineUsers Mapping userId -> lastSeen
     */
    private function saveOnlineUsersToCache(int $gameId, array $onlineUsers): void
    {
        $cacheKey = self::CACHE_PREFIX.$gameId;

        try {
            // Si plus personne n'est connecté, on supprime la clé
            if (empty($onlineUsers)) {
                $this->presenceCache->deleteItem($cacheKey);

                return;
            }

            // Convertir les DateTimeImmutable en timestamps pour la sérialisation
            $data = [];
            foreach ($onlineUsers as $userId => $lastSeen) {
                $data[$userId] = $lastSeen instanceof DateTimeImmutable ? $lastSeen->getTimestamp() : $lastSeen;
            }

            // Sauvegarder dans Redis
            $item = $this->presenceCache->getItem($cacheKey);
            $item->set($data);
            $item->expiresAfter(self::TIMEOUT_SECONDS * 2); // Expiration de sécurité
            $this->presenceCache->save($item);

            $this->logger->debug('Saved presence to cache', [
                'gameId' => $gameId,
                'users' => array_keys($data),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Error saving presence to cache', [
                'gameId' => $gameId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Nettoie toutes les données de présence (utile pour les tests).
     */
    public function clearAll(): void
    {
        try {
            // Supprimer toutes les clés de présence
            // Note: CacheItemPoolInterface n'a pas de méthode clear() globale
            // Pour simplifier, on log juste un avertissement
            $this->logger->warning('clearAll() called but not fully implemented - use Redis FLUSHDB if needed');
        } catch (\Exception $e) {
            $this->logger->error('Error clearing presence cache', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
