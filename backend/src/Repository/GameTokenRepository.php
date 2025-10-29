<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\GameMap;
use App\Entity\GameToken;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GameToken>
 */
class GameTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GameToken::class);
    }

    /**
     * Trouve tous les tokens d'une carte (alias pour compatibilité).
     *
     * @return GameToken[]
     */
    public function findByMap(GameMap $map): array
    {
        return $this->findTokensByMap($map, false);
    }

    /**
     * Trouve tous les tokens d'une carte.
     *
     * @return GameToken[]
     */
    public function findTokensByMap(GameMap $map, bool $visibleOnly = false): array
    {
        $qb = $this->createQueryBuilder('t')
            ->where('t.map = :map')
            ->setParameter('map', $map)
            ->orderBy('t.layer', 'ASC')
            ->addOrderBy('t.createdAt', 'ASC');

        if ($visibleOnly) {
            $qb->andWhere('t.isVisible = :visible')
               ->setParameter('visible', true);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Trouve uniquement les tokens visibles d'une carte.
     *
     * @return GameToken[]
     */
    public function findVisibleTokensByMap(GameMap $map): array
    {
        return $this->findTokensByMap($map, true);
    }

    /**
     * Trouve les tokens visibles pour un utilisateur spécifique.
     * Les tokens invisibles sont visibles uniquement pour le propriétaire.
     *
     * @return GameToken[]
     */
    public function findVisibleByMap(GameMap $map, User $user): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.map = :map')
            ->andWhere('t.isVisible = true OR t.owner = :user')
            ->setParameter('map', $map)
            ->setParameter('user', $user)
            ->orderBy('t.layer', 'ASC')
            ->addOrderBy('t.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte le nombre total de tokens sur une carte.
     */
    public function countByMap(GameMap $map): int
    {
        return (int) $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->where('t.map = :map')
            ->setParameter('map', $map)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Trouve les tokens par calque (layer).
     *
     * @return GameToken[]
     */
    public function findTokensByLayer(GameMap $map, string $layer): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.map = :map')
            ->andWhere('t.layer = :layer')
            ->setParameter('map', $map)
            ->setParameter('layer', $layer)
            ->orderBy('t.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les tokens par type (character, monster, npc, object).
     *
     * @return GameToken[]
     */
    public function findTokensByType(GameMap $map, string $type): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.map = :map')
            ->andWhere('t.type = :type')
            ->setParameter('map', $map)
            ->setParameter('type', $type)
            ->orderBy('t.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les tokens dans une zone rectangulaire.
     *
     * @return GameToken[]
     */
    public function findTokensInArea(
        GameMap $map,
        int $x,
        int $y,
        int $width,
        int $height,
    ): array {
        return $this->createQueryBuilder('t')
            ->where('t.map = :map')
            ->andWhere('t.x >= :minX')
            ->andWhere('t.x < :maxX')
            ->andWhere('t.y >= :minY')
            ->andWhere('t.y < :maxY')
            ->setParameter('map', $map)
            ->setParameter('minX', $x)
            ->setParameter('maxX', $x + $width)
            ->setParameter('minY', $y)
            ->setParameter('maxY', $y + $height)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve un token à une position précise.
     */
    public function findTokenAtPosition(GameMap $map, int $x, int $y): ?GameToken
    {
        return $this->createQueryBuilder('t')
            ->where('t.map = :map')
            ->andWhere('t.x = :x')
            ->andWhere('t.y = :y')
            ->setParameter('map', $map)
            ->setParameter('x', $x)
            ->setParameter('y', $y)
            ->orderBy('t.layer', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve les tokens verrouillés d'une carte.
     *
     * @return GameToken[]
     */
    public function findLockedTokens(GameMap $map): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.map = :map')
            ->andWhere('t.isLocked = :locked')
            ->setParameter('map', $map)
            ->setParameter('locked', true)
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte les tokens par type pour une carte.
     *
     * @return array<string, int>
     */
    public function countTokensByType(GameMap $map): array
    {
        $result = $this->createQueryBuilder('t')
            ->select('t.type as type, COUNT(t.id) as count')
            ->where('t.map = :map')
            ->setParameter('map', $map)
            ->groupBy('t.type')
            ->getQuery()
            ->getResult();

        $counts = [];
        foreach ($result as $row) {
            $counts[$row['type']] = (int) $row['count'];
        }

        return $counts;
    }

    /**
     * Supprime tous les tokens d'une carte.
     */
    public function deleteAllTokensByMap(GameMap $map): int
    {
        return $this->createQueryBuilder('t')
            ->delete()
            ->where('t.map = :map')
            ->setParameter('map', $map)
            ->getQuery()
            ->execute();
    }

    /**
     * Déplace plusieurs tokens en une seule opération.
     *
     * @param array<int, array{x: int, y: int}> $positions Format: [tokenId => ['x' => x, 'y' => y]]
     */
    public function moveTokens(array $positions): void
    {
        $em = $this->getEntityManager();

        foreach ($positions as $tokenId => $position) {
            $token = $this->find($tokenId);
            if ($token && !$token->isLocked()) {
                $token->move($position['x'], $position['y']);
                $em->persist($token);
            }
        }

        $em->flush();
    }
}
