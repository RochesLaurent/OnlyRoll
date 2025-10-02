<?php

namespace App\Repository;

use App\Entity\Game;
use App\Entity\GamePlayer;
use App\Entity\User;
use App\Enum\PlayerRole;
use App\Enum\PlayerStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GamePlayer>
 */
class GamePlayerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GamePlayer::class);
    }

    /**
     * Trouve un joueur spécifique dans une partie.
     */
    public function findPlayerInGame(Game $game, User $user): ?GamePlayer
    {
        return $this->findOneBy([
            'game' => $game,
            'user' => $user,
        ]);
    }

    /**
     * Vérifie si un utilisateur est dans une partie.
     */
    public function isUserInGame(Game $game, User $user): bool
    {
        return null !== $this->findPlayerInGame($game, $user);
    }

    /**
     * Trouve les joueurs actifs d'une partie.
     *
     * @return GamePlayer[]
     */
    public function findActivePlayersInGame(Game $game): array
    {
        return $this->createQueryBuilder('gp')
            ->leftJoin('gp.user', 'u')
            ->addSelect('u')
            ->where('gp.game = :game')
            ->andWhere('gp.status = :status')
            ->setParameter('game', $game)
            ->setParameter('status', PlayerStatus::ACTIVE)
            ->orderBy('gp.joinedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les invitations en attente pour une partie.
     *
     * @return GamePlayer[]
     */
    public function findPendingInvitations(Game $game): array
    {
        return $this->createQueryBuilder('gp')
            ->leftJoin('gp.user', 'u')
            ->addSelect('u')
            ->where('gp.game = :game')
            ->andWhere('gp.status = :status')
            ->setParameter('game', $game)
            ->setParameter('status', PlayerStatus::PENDING)
            ->orderBy('gp.joinedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve tous les joueurs participant à une partie (actifs + inactifs).
     *
     * @return GamePlayer[]
     */
    public function findParticipatingPlayers(Game $game): array
    {
        return $this->createQueryBuilder('gp')
            ->leftJoin('gp.user', 'u')
            ->addSelect('u')
            ->where('gp.game = :game')
            ->andWhere('gp.status IN (:statuses)')
            ->setParameter('game', $game)
            ->setParameter('statuses', [PlayerStatus::ACTIVE, PlayerStatus::INACTIVE])
            ->orderBy('gp.joinedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte les joueurs par statut dans une partie.
     *
     * @return array<string, int>
     */
    public function countPlayersByStatus(Game $game): array
    {
        $result = $this->createQueryBuilder('gp')
            ->select('gp.status as status, COUNT(gp.id) as count')
            ->where('gp.game = :game')
            ->setParameter('game', $game)
            ->groupBy('gp.status')
            ->getQuery()
            ->getResult();

        $counts = [];
        foreach ($result as $row) {
            $counts[$row['status']->value] = (int) $row['count'];
        }

        return $counts;
    }

    /**
     * Trouve les parties où l'utilisateur a un rôle spécifique.
     *
     * @return GamePlayer[]
     */
    public function findUserGamesWithRole(User $user, PlayerRole $role): array
    {
        return $this->createQueryBuilder('gp')
            ->leftJoin('gp.game', 'g')
            ->addSelect('g')
            ->where('gp.user = :user')
            ->andWhere('gp.role = :role')
            ->setParameter('user', $user)
            ->setParameter('role', $role)
            ->orderBy('g.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve toutes les parties où l'utilisateur est Game Master.
     *
     * @return GamePlayer[]
     */
    public function findUserHostedGames(User $user): array
    {
        return $this->findUserGamesWithRole($user, PlayerRole::GAME_MASTER);
    }

    /**
     * Vérifie si un utilisateur peut rejoindre une partie.
     */
    public function canUserJoinGame(Game $game, User $user): bool
    {
        // Déjà dans la partie ?
        if ($this->isUserInGame($game, $user)) {
            return false;
        }

        // Partie pleine ?
        $activeCount = $this->createQueryBuilder('gp')
            ->select('COUNT(gp.id)')
            ->where('gp.game = :game')
            ->andWhere('gp.status IN (:statuses)')
            ->setParameter('game', $game)
            ->setParameter('statuses', [PlayerStatus::ACTIVE, PlayerStatus::INACTIVE])
            ->getQuery()
            ->getSingleScalarResult();

        return $activeCount < $game->getMaxPlayers();
    }

    /**
     * Trouve les joueurs qui ont quitté ou été expulsés d'une partie.
     *
     * @return GamePlayer[]
     */
    public function findFormerPlayers(Game $game): array
    {
        return $this->createQueryBuilder('gp')
            ->leftJoin('gp.user', 'u')
            ->addSelect('u')
            ->where('gp.game = :game')
            ->andWhere('gp.status IN (:statuses)')
            ->setParameter('game', $game)
            ->setParameter('statuses', [PlayerStatus::LEFT, PlayerStatus::KICKED])
            ->orderBy('gp.leftAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte le nombre total de joueurs dans une partie (tous statuts confondus).
     */
    public function countTotalPlayers(Game $game): int
    {
        return (int) $this->createQueryBuilder('gp')
            ->select('COUNT(gp.id)')
            ->where('gp.game = :game')
            ->setParameter('game', $game)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
