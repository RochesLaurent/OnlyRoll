<?php

namespace App\Repository;

use App\Entity\Game;
use App\Entity\GameMessage;
use App\Entity\User;
use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GameMessage>
 */
class GameMessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GameMessage::class);
    }

    /**
     * Trouve les messages récents d'un jeu.
     *
     * @return GameMessage[]
     */
    public function findRecentMessages(Game $game, int $limit = 50): array
    {
        return $this->createQueryBuilder('m')
            ->leftJoin('m.user', 'u')
            ->addSelect('u')
            ->leftJoin('m.recipient', 'r')
            ->addSelect('r')
            ->where('m.game = :game')
            ->setParameter('game', $game)
            ->orderBy('m.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les messages par type.
     *
     * @return GameMessage[]
     */
    public function findMessagesByType(Game $game, string $type, int $limit = 50): array
    {
        return $this->createQueryBuilder('m')
            ->leftJoin('m.user', 'u')
            ->addSelect('u')
            ->where('m.game = :game')
            ->andWhere('m.type = :type')
            ->setParameter('game', $game)
            ->setParameter('type', $type)
            ->orderBy('m.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Alias pour findMessagesByType (requis par ChatService).
     *
     * @return GameMessage[]
     */
    public function findByType(Game $game, string $type): array
    {
        return $this->findMessagesByType($game, $type, 200);
    }

    /**
     * Trouve les chuchotements (whispers) pour un utilisateur.
     *
     * @return GameMessage[]
     */
    public function findWhispersForUser(Game $game, User $user, int $limit = 50): array
    {
        return $this->createQueryBuilder('m')
            ->leftJoin('m.user', 'u')
            ->addSelect('u')
            ->leftJoin('m.recipient', 'r')
            ->addSelect('r')
            ->where('m.game = :game')
            ->andWhere('m.type = :whisper')
            ->andWhere('m.user = :user OR m.recipient = :user')
            ->setParameter('game', $game)
            ->setParameter('whisper', GameMessage::TYPE_WHISPER)
            ->setParameter('user', $user)
            ->orderBy('m.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les lancers de dés récents.
     *
     * @return GameMessage[]
     */
    public function findDiceRolls(Game $game, int $limit = 20): array
    {
        return $this->findMessagesByType($game, GameMessage::TYPE_DICE_ROLL, $limit);
    }

    /**
     * Trouve les messages visibles pour un utilisateur.
     *
     * @return GameMessage[]
     */
    public function findVisibleMessagesForUser(Game $game, User $user, int $limit = 50): array
    {
        return $this->createQueryBuilder('m')
            ->leftJoin('m.user', 'u')
            ->addSelect('u')
            ->leftJoin('m.recipient', 'r')
            ->addSelect('r')
            ->where('m.game = :game')
            ->andWhere(
                'm.type != :whisper OR m.user = :user OR m.recipient = :user',
            )
            ->setParameter('game', $game)
            ->setParameter('whisper', GameMessage::TYPE_WHISPER)
            ->setParameter('user', $user)
            ->orderBy('m.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Alias pour findVisibleMessagesForUser (requis par ChatService).
     *
     * @return GameMessage[]
     */
    public function findVisibleForUser(Game $game, User $user): array
    {
        return $this->findVisibleMessagesForUser($game, $user, 200);
    }

    /**
     * Trouve les messages In Character (IC) ou Out of Character (OOC).
     *
     * @return GameMessage[]
     */
    public function findByCharacterMode(Game $game, bool $isInCharacter, int $limit = 50): array
    {
        return $this->createQueryBuilder('m')
            ->leftJoin('m.user', 'u')
            ->addSelect('u')
            ->where('m.game = :game')
            ->andWhere('m.isInCharacter = :ic')
            ->setParameter('game', $game)
            ->setParameter('ic', $isInCharacter)
            ->orderBy('m.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les messages depuis une certaine date.
     *
     * @return GameMessage[]
     */
    public function findMessagesSince(Game $game, DateTimeInterface $since): array
    {
        return $this->createQueryBuilder('m')
            ->leftJoin('m.user', 'u')
            ->addSelect('u')
            ->leftJoin('m.recipient', 'r')
            ->addSelect('r')
            ->where('m.game = :game')
            ->andWhere('m.createdAt >= :since')
            ->setParameter('game', $game)
            ->setParameter('since', $since)
            ->orderBy('m.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte les messages par type pour un jeu.
     *
     * @return array<string, int>
     */
    public function countMessagesByType(Game $game): array
    {
        $result = $this->createQueryBuilder('m')
            ->select('m.type as type, COUNT(m.id) as count')
            ->where('m.game = :game')
            ->setParameter('game', $game)
            ->groupBy('m.type')
            ->getQuery()
            ->getResult();

        $counts = [];
        foreach ($result as $row) {
            $counts[$row['type']] = (int) $row['count'];
        }

        return $counts;
    }

    /**
     * Compte le total de messages dans un jeu.
     */
    public function countMessagesByGame(Game $game): int
    {
        return (int) $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->where('m.game = :game')
            ->setParameter('game', $game)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Récupère les statistiques des messages d'une partie.
     *
     * @return array{total: int, byType: array<string, int>}
     */
    public function getStatsByGame(Game $game): array
    {
        $total = $this->countMessagesByGame($game);
        $byType = $this->countMessagesByType($game);

        return [
            'total' => $total,
            'byType' => $byType,
        ];
    }

    /**
     * Supprime les vieux messages (nettoyage).
     */
    public function deleteOldMessages(Game $game, DateTimeInterface $before): int
    {
        return $this->createQueryBuilder('m')
            ->delete()
            ->where('m.game = :game')
            ->andWhere('m.createdAt < :before')
            ->setParameter('game', $game)
            ->setParameter('before', $before)
            ->getQuery()
            ->execute();
    }

    /**
     * Alias pour deleteOldMessages (requis par ChatService).
     */
    public function deleteOlderThan(Game $game, DateTimeInterface $before): int
    {
        return $this->deleteOldMessages($game, $before);
    }

    /**
     * Trouve les messages d'un utilisateur spécifique dans un jeu.
     *
     * @return GameMessage[]
     */
    public function findByUserInGame(Game $game, User $user, int $limit = 50): array
    {
        return $this->createQueryBuilder('m')
            ->leftJoin('m.user', 'u')
            ->addSelect('u')
            ->where('m.game = :game')
            ->andWhere('m.user = :user')
            ->setParameter('game', $game)
            ->setParameter('user', $user)
            ->orderBy('m.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les messages créés après une date donnée.
     *
     * @return GameMessage[]
     */
    public function findSince(Game $game, DateTimeInterface $since): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.game = :game')
            ->andWhere('m.createdAt > :since')
            ->setParameter('game', $game)
            ->setParameter('since', $since)
            ->orderBy('m.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les messages visibles pour un utilisateur depuis une date.
     *
     * @return GameMessage[]
     */
    public function findVisibleSince(Game $game, DateTimeInterface $since, User $user): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.game = :game')
            ->andWhere('m.createdAt > :since')
            ->andWhere('m.type != :whisper OR m.user = :user OR m.recipient = :user')
            ->setParameter('game', $game)
            ->setParameter('since', $since)
            ->setParameter('whisper', GameMessage::TYPE_WHISPER)
            ->setParameter('user', $user)
            ->orderBy('m.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
