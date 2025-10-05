<?php

namespace App\Repository;

use App\DTO\Game\GameFilterDTO;
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
     * Trouve les parties publiques avec filtres et pagination.
     *
     * @return array{data: Game[], total: int, page: int, limit: int, totalPages: int}
     */
    public function findPublicGamesWithFilters(GameFilterDTO $filters): array
    {
        $qb = $this->createQueryBuilder('g')
            ->where('g.isPublic = :public')
            ->setParameter('public', true)
            ->leftJoin('g.gamePlayers', 'gp')
            ->addSelect('gp')
            ->leftJoin('gp.user', 'u')
            ->addSelect('u')
            ->leftJoin('g.gameMaster', 'gm')
            ->addSelect('gm');

        // Exclure les parties archivées par défaut
        $qb->andWhere('g.status != :archived')
           ->setParameter('archived', GameStatus::ARCHIVED);

        // Filtre: Recherche globale (search)
        if ($filters->search) {
            $qb->andWhere('g.name LIKE :search OR g.description LIKE :search')
               ->setParameter('search', '%' . $filters->search . '%');
        }

        // Filtre: Titre spécifique
        if ($filters->title) {
            $qb->andWhere('g.name LIKE :title')
               ->setParameter('title', '%' . $filters->title . '%');
        }

        // Filtre: Game Master (pseudo)
        if ($filters->gameMaster) {
            $qb->andWhere('gm.pseudo LIKE :gameMaster')
               ->setParameter('gameMaster', '%' . $filters->gameMaster . '%');
        }

        // Filtre: Status
        if ($filters->status) {
            $status = GameStatus::tryFrom($filters->status);
            if ($status) {
                $qb->andWhere('g.status = :status')
                   ->setParameter('status', $status);
            }
        }

        // Compter le total AVANT pagination
        $countQb = clone $qb;
        $total = (int) $countQb->select('COUNT(DISTINCT g.id)')
                                ->getQuery()
                                ->getSingleScalarResult();

        // Pagination
        $offset = ($filters->page - 1) * $filters->limit;
        $qb->setFirstResult($offset)
           ->setMaxResults($filters->limit)
           ->orderBy('g.createdAt', 'DESC');

        $data = $qb->getQuery()->getResult();

        return [
            'data' => $data,
            'total' => $total,
            'page' => $filters->page,
            'limit' => $filters->limit,
            'totalPages' => (int) ceil($total / $filters->limit),
        ];
    }

    /**
     * Trouve les parties publiques disponibles (ancienne méthode, conservée pour compatibilité).
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
               ->setParameter('search', '%' . $search . '%');
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
