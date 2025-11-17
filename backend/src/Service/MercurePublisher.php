<?php

declare(strict_types=1);

namespace App\Service;

use DateTimeImmutable;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

/**
 * Service pour publier des événements temps réel via Mercure.
 *
 * Ce service centralise la publication d'événements Mercure pour OnlyRoll.
 * Il gère automatiquement le formatage des données et la publication sur les topics appropriés.
 */
readonly class MercurePublisher
{
    public function __construct(
        private HubInterface $hub,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Publie un événement pour une partie spécifique.
     *
     * @param int $gameId ID de la partie concernée
     * @param string $eventType Type d'événement (chat, token, map, dice, player)
     * @param array<string, mixed> $data Données de l'événement
     * @param bool $private Si true, seuls les abonnés authentifiés verront l'événement
     *
     * @return bool Succès de la publication
     */
    public function publishGameEvent(
        int $gameId,
        string $eventType,
        array $data,
        bool $private = false,
    ): bool {
        try {
            // Construction du topic au format: game/{gameId}/{eventType}
            $topic = \sprintf('game/%d/%s', $gameId, $eventType);

            // Préparation du payload
            $payload = [
                'type' => $eventType,
                'gameId' => $gameId,
                'data' => $data,
                'timestamp' => (new DateTimeImmutable())->format('c'),
            ];

            // Création de l'update Mercure
            $update = new Update(
                topics: [$topic],
                data: json_encode($payload, \JSON_THROW_ON_ERROR),
                private: $private,
            );

            // Publication via le hub
            $this->hub->publish($update);

            $this->logger->info('Mercure event published', [
                'topic' => $topic,
                'eventType' => $eventType,
                'gameId' => $gameId,
            ]);

            return true;
        }
        catch (Exception $e) {
            $this->logger->error('Failed to publish Mercure event', [
                'error' => $e->getMessage(),
                'eventType' => $eventType,
                'gameId' => $gameId,
            ]);

            return false;
        }
    }

    /**
     * Publie un message de chat.
     *
     * @param int $gameId ID de la partie
     * @param array<string, mixed> $messageData Données du message (messageId, userId, userName, content, type, etc.)
     */
    public function publishChatMessage(int $gameId, array $messageData): bool
    {
        return $this->publishGameEvent($gameId, 'chat', $messageData);
    }

    /**
     * Publie un déplacement de token.
     *
     * @param int $gameId ID de la partie
     * @param array<string, mixed> $tokenData Données du token (tokenId, mapId, x, y, userId)
     */
    public function publishTokenMove(int $gameId, array $tokenData): bool
    {
        return $this->publishGameEvent($gameId, 'token', $tokenData);
    }

    /**
     * Publie la création d'un token.
     *
     * @param int $gameId ID de la partie
     * @param array<string, mixed> $tokenData Données complètes du token
     */
    public function publishTokenCreated(int $gameId, array $tokenData): bool
    {
        return $this->publishGameEvent($gameId, 'token', [
            'action' => 'created',
            'token' => $tokenData,
        ]);
    }

    /**
     * Publie la suppression d'un token.
     *
     * @param int $gameId ID de la partie
     * @param int $tokenId ID du token supprimé
     */
    public function publishTokenDeleted(int $gameId, int $tokenId): bool
    {
        return $this->publishGameEvent($gameId, 'token', [
            'action' => 'deleted',
            'tokenId' => $tokenId,
        ]);
    }

    /**
     * Publie un changement de carte active.
     *
     * @param int $gameId ID de la partie
     * @param array<string, mixed> $mapData Données de la carte (mapId, name, backgroundUrl, etc.)
     */
    public function publishMapChange(int $gameId, array $mapData): bool
    {
        return $this->publishGameEvent($gameId, 'map', $mapData);
    }

    /**
     * Publie un lancer de dés.
     *
     * @param int $gameId ID de la partie
     * @param array<string, mixed> $diceData Données du lancer (userId, userName, expression, results, etc.)
     */
    public function publishDiceRoll(int $gameId, array $diceData): bool
    {
        return $this->publishGameEvent($gameId, 'dice', $diceData);
    }

    /**
     * Publie un événement de connexion/déconnexion de joueur.
     *
     * @param int $gameId ID de la partie
     * @param array<string, mixed> $playerData Données du joueur (userId, userName, action: 'joined'|'left')
     */
    public function publishPlayerEvent(int $gameId, array $playerData): bool
    {
        return $this->publishGameEvent($gameId, 'player', $playerData);
    }

    /**
     * Publie un événement système (changement de status de partie, etc.).
     *
     * @param int $gameId ID de la partie
     * @param array<string, mixed> $systemData Données système
     */
    public function publishSystemEvent(int $gameId, array $systemData): bool
    {
        return $this->publishGameEvent($gameId, 'system', $systemData);
    }

    /**
     * Publie un événement de présence (join/leave/heartbeat).
     *
     * @param int $gameId ID de la partie
     * @param int $userId ID de l'utilisateur
     * @param string $type Type d'événement: 'join', 'leave', ou 'heartbeat'
     * @param array<int> $onlineUsers Liste complète des IDs des utilisateurs actuellement en ligne
     */
    public function publishPresenceEvent(
        int $gameId,
        int $userId,
        string $type,
        array $onlineUsers = [],
    ): bool {
        $data = [
            'userId' => $userId,
            'type' => $type,
            'timestamp' => (new DateTimeImmutable())->format('c'),
            'onlineUsers' => $onlineUsers,
        ];

        return $this->publishGameEvent($gameId, 'presence', $data);
    }
}
