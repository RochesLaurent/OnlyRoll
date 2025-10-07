<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\Map\CreateMapDTO;
use App\DTO\Map\UpdateMapDTO;
use App\Entity\Game;
use App\Entity\GameMap;
use App\Repository\GameMapRepository;
use Doctrine\ORM\EntityManagerInterface;

class MapService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly GameMapRepository $mapRepository,
    ) {
    }

    /**
     * Crée une nouvelle carte pour un jeu.
     */
    public function createMap(Game $game, CreateMapDTO $dto): GameMap
    {
        $map = new GameMap();
        $map->setGame($game);
        $map->setName($dto->name);
        $map->setDescription($dto->description);
        $map->setImageUrl($dto->imageUrl);
        $map->setGridSize($dto->gridSize);
        $map->setGridType($dto->gridType);
        $map->setWidth($dto->width);
        $map->setHeight($dto->height);
        $map->setIsActive($dto->isActive);
        $map->setSettings($dto->settings);

        // Si cette carte est définie comme active, désactiver les autres
        if ($dto->isActive) {
            $this->deactivateOtherMaps($game);
        }

        $this->entityManager->persist($map);
        $this->entityManager->flush();

        return $map;
    }

    /**
     * Récupère la carte active d'un jeu.
     */
    public function getActiveMap(Game $game): ?GameMap
    {
        return $this->mapRepository->findActiveMapByGame($game);
    }

    /**
     * Met à jour une carte existante.
     */
    public function updateMap(GameMap $map, UpdateMapDTO $dto): GameMap
    {
        if (null !== $dto->name) {
            $map->setName($dto->name);
        }

        if (null !== $dto->description) {
            $map->setDescription($dto->description);
        }

        if (null !== $dto->imageUrl) {
            $map->setImageUrl($dto->imageUrl);
        }

        if (null !== $dto->gridSize) {
            $map->setGridSize($dto->gridSize);
        }

        if (null !== $dto->gridType) {
            $map->setGridType($dto->gridType);
        }

        if (null !== $dto->width) {
            $map->setWidth($dto->width);
        }

        if (null !== $dto->height) {
            $map->setHeight($dto->height);
        }

        if (null !== $dto->isActive) {
            $map->setIsActive($dto->isActive);

            // Si on active cette carte, désactiver les autres
            if ($dto->isActive) {
                $mapGame = $map->getGame();
                if ($mapGame) {
                    $this->deactivateOtherMaps($mapGame);
                }
            }
        }

        if (null !== $dto->settings) {
            $map->setSettings($dto->settings);
        }

        $this->entityManager->flush();

        return $map;
    }

    /**
     * Active une carte et désactive toutes les autres du même jeu.
     */
    public function activateMap(GameMap $map): GameMap
    {
        $this->mapRepository->activateMap($map);

        return $map;
    }

    /**
     * Supprime une carte.
     */
    public function deleteMap(GameMap $map): void
    {
        $this->entityManager->remove($map);
        $this->entityManager->flush();
    }

    /**
     * Récupère toutes les cartes d'un jeu.
     *
     * @return GameMap[]
     */
    public function getMapsByGame(Game $game, bool $activeOnly = false): array
    {
        return $this->mapRepository->findMapsByGame($game, $activeOnly);
    }

    /**
     * Désactive toutes les cartes d'un jeu sauf celle fournie.
     */
    private function deactivateOtherMaps(Game $game): void
    {
        $maps = $this->mapRepository->findMapsByGame($game, true);

        foreach ($maps as $existingMap) {
            $existingMap->deactivate();
            $this->entityManager->persist($existingMap);
        }
    }
}
