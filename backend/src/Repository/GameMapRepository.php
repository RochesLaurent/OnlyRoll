<?php

namespace App\Repository;

use App\Entity\Game;
use App\Entity\GameMap;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GameMap>
 */
class GameMapRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GameMap::class);
    }

    /**
     * Trouve la carte active d'un jeu.
     */
    public function findActiveMapByGame(Game $game): ?GameMap
    {
        return $this->createQueryBuilder('m')
            ->where('m.game = :game')
            ->andWhere('m.isActive = :active')
            ->setParameter('game', $game)
            ->setParameter('active', true)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve toutes les cartes d'un jeu.
     *
     * @return GameMap[]
     */
    public function findMapsByGame(Game $game, bool $activeOnly = false): array
    {
        $qb = $this->createQueryBuilder('m')
            ->where('m.game = :game')
            ->setParameter('game', $game)
            ->orderBy('m.createdAt', 'DESC');

        if ($activeOnly) {
            $qb->andWhere('m.isActive = :active')
               ->setParameter('active', true);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Trouve une carte avec ses tokens (évite le problème N+1).
     */
    public function findMapWithTokens(int $id): ?GameMap
    {
        return $this->createQueryBuilder('m')
            ->leftJoin('m.tokens', 't')
            ->addSelect('t')
            ->where('m.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Active une carte et désactive toutes les autres du même jeu.
     */
    public function activateMap(GameMap $map): void
    {
        $game = $map->getGame();
        if (!$game) {
            throw new \LogicException('La carte doit être associée à un jeu.');
        }

        $em = $this->getEntityManager();

        // Désactiver toutes les cartes du jeu
        $em->createQueryBuilder()
            ->update(GameMap::class, 'm')
            ->set('m.isActive', ':inactive')
            ->where('m.game = :game')
            ->setParameter('inactive', false)
            ->setParameter('game', $game)
            ->getQuery()
            ->execute();

        // Activer la carte sélectionnée
        $map->activate();
        $em->persist($map);
        $em->flush();
    }

    /**
     * Compte le nombre de cartes par jeu.
     *
     * @return array<int, int> Tableau [game_id => count]
     */
    public function countMapsByGame(): array
    {
        $result = $this->createQueryBuilder('m')
            ->select('IDENTITY(m.game) as game_id, COUNT(m.id) as count')
            ->groupBy('m.game')
            ->getQuery()
            ->getResult();

        $counts = [];
        foreach ($result as $row) {
            $counts[(int) $row['game_id']] = (int) $row['count'];
        }

        return $counts;
    }

    /**
     * Trouve les cartes par type de grille.
     *
     * @return GameMap[]
     */
    public function findByGridType(Game $game, string $gridType): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.game = :game')
            ->andWhere('m.gridType = :gridType')
            ->setParameter('game', $game)
            ->setParameter('gridType', $gridType)
            ->orderBy('m.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
