<?php

namespace App\Repository;

use App\Entity\Game;
use App\Entity\User;
use App\Enum\GameStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Game>
 */
class GameRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Game::class);
    }

    /**
     * Trouve les parties publiques disponibles.
     *
     * @return Game[]
     */
    public function findPublicGames(
        ?string $search = null,
        ?GameStatus $status = null,
        bool $excludeArchived = true,
    ): array {
        $qb = $this->createQueryBuilder('g')
            ->where('g.isPublic = :public')
            ->setParameter('public', true)
            ->leftJoin('g.gamePlayers', 'gp')
            ->addSelect('gp')
            ->leftJoin('gp.user', 'u')
            ->addSelect('u')
            ->orderBy('g.createdAt', 'DESC');

        if ($search) {
            $qb->andWhere('g.name LIKE :search OR g.description LIKE :search')
               ->setParameter('search', '%'.$search.'%');
        }

        if ($status) {
            $qb->andWhere('g.status = :status')
               ->setParameter('status', $status);
        }

        if ($excludeArchived) {
            $qb->andWhere('g.status != :archived')
               ->setParameter('archived', GameStatus::ARCHIVED);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Trouve les parties d'un utilisateur.
     *
     * @return Game[]
     */
    public function findUserGames(User $user): array
    {
        return $this->createQueryBuilder('g')
            ->innerJoin('g.gamePlayers', 'gp')
            ->addSelect('gp')
            ->leftJoin('gp.user', 'u')
            ->addSelect('u')
            ->where('gp.user = :user')
            ->setParameter('user', $user)
            ->orderBy('g.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve une partie avec tous ses joueurs (pour éviter N+1).
     */
    public function findGameWithPlayers(int $id): ?Game
    {
        return $this->createQueryBuilder('g')
            ->leftJoin('g.gamePlayers', 'gp')
            ->addSelect('gp')
            ->leftJoin('gp.user', 'u')
            ->addSelect('u')
            ->where('g.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve une partie par son code d'invitation.
     */
    public function findByInviteCode(string $code): ?Game
    {
        return $this->findOneBy(['inviteCode' => $code]);
    }

    /**
     * Compte les parties par statut pour un utilisateur.
     *
     * @return array<string, int>
     */
    public function countUserGamesByStatus(User $user): array
    {
        $result = $this->createQueryBuilder('g')
            ->select('g.status as status, COUNT(g.id) as count')
            ->innerJoin('g.gamePlayers', 'gp')
            ->where('gp.user = :user')
            ->setParameter('user', $user)
            ->groupBy('g.status')
            ->getQuery()
            ->getResult();

        $counts = [];
        foreach ($result as $row) {
            $counts[$row['status']->value] = (int) $row['count'];
        }

        return $counts;
    }
}
