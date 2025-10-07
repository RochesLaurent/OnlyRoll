<?php

namespace App\Controller;

use App\DTO\Map\CreateMapDTO;
use App\DTO\Map\UpdateMapDTO;
use App\Repository\GameMapRepository;
use App\Repository\GameRepository;
use App\Service\MapService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/games/{gameId}/maps', name: 'api_map_')]
#[IsGranted('ROLE_USER')]
class MapController extends AbstractController
{
    public function __construct(
        private readonly MapService $mapService,
        private readonly GameRepository $gameRepository,
        private readonly GameMapRepository $mapRepository,
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
    ) {
    }

    /**
     * Liste toutes les cartes d'un jeu.
     */
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(int $gameId): JsonResponse
    {
        $game = $this->gameRepository->find($gameId);

        if (!$game) {
            return $this->json(
                ['error' => 'Partie introuvable'],
                Response::HTTP_NOT_FOUND
            );
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$game->canBeViewedBy($user)) {
            return $this->json(
                ['error' => 'Accès refusé'],
                Response::HTTP_FORBIDDEN
            );
        }

        $maps = $this->mapService->getMapsByGame($game);

        return $this->json(
            $maps,
            Response::HTTP_OK,
            [],
            ['groups' => 'map:list']
        );
    }

    /**
     * Récupère la carte active d'un jeu.
     */
    #[Route('/active', name: 'active', methods: ['GET'])]
    public function getActive(int $gameId): JsonResponse
    {
        $game = $this->gameRepository->find($gameId);

        if (!$game) {
            return $this->json(
                ['error' => 'Partie introuvable'],
                Response::HTTP_NOT_FOUND
            );
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$game->canBeViewedBy($user)) {
            return $this->json(
                ['error' => 'Accès refusé'],
                Response::HTTP_FORBIDDEN
            );
        }

        $activeMap = $this->mapService->getActiveMap($game);

        if (!$activeMap) {
            return $this->json(
                ['error' => 'Aucune carte active'],
                Response::HTTP_NOT_FOUND
            );
        }

        return $this->json($activeMap, Response::HTTP_OK, [], ['groups' => 'map:read']);
    }

    /**
     * Détails d'une carte.
     */
    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $gameId, int $id): JsonResponse
    {
        $game = $this->gameRepository->find($gameId);

        if (!$game) {
            return $this->json(
                ['error' => 'Partie introuvable'],
                Response::HTTP_NOT_FOUND
            );
        }

        $map = $this->mapRepository->find($id);

        // Vérification null-safety pour PHPStan
        $mapGame = $map?->getGame();
        if (!$map || !$mapGame || $mapGame->getId() !== $gameId) {
            return $this->json(
                ['error' => 'Carte introuvable'],
                Response::HTTP_NOT_FOUND
            );
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$game->canBeViewedBy($user)) {
            return $this->json(
                ['error' => 'Accès refusé'],
                Response::HTTP_FORBIDDEN
            );
        }

        return $this->json(
            $map,
            Response::HTTP_OK,
            [],
            ['groups' => 'map:read']
        );
    }

    /**
     * Créer une nouvelle carte.
     */
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(int $gameId, Request $request): JsonResponse
    {
        $game = $this->gameRepository->find($gameId);

        if (!$game) {
            return $this->json(
                ['error' => 'Partie introuvable'],
                Response::HTTP_NOT_FOUND
            );
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$game->isGameMaster($user)) {
            return $this->json(
                ['error' => 'Seul le maître du jeu peut créer des cartes'],
                Response::HTTP_FORBIDDEN
            );
        }

        $dto = $this->serializer->deserialize(
            $request->getContent(),
            CreateMapDTO::class,
            'json'
        );

        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            return $this->json(
                ['errors' => (string) $errors],
                Response::HTTP_BAD_REQUEST
            );
        }

        try {
            $map = $this->mapService->createMap($game, $dto);

            return $this->json(
                $map,
                Response::HTTP_CREATED,
                [],
                ['groups' => 'map:read']
            );
        } catch (\Exception $e) {
            return $this->json(
                ['error' => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Mettre à jour une carte.
     */
    #[Route('/{id}', name: 'update', methods: ['PUT', 'PATCH'])]
    public function update(int $gameId, int $id, Request $request): JsonResponse
    {
        $game = $this->gameRepository->find($gameId);

        if (!$game) {
            return $this->json(
                ['error' => 'Partie introuvable'],
                Response::HTTP_NOT_FOUND
            );
        }

        $map = $this->mapRepository->find($id);

        // Vérification null-safety pour PHPStan
        $mapGame = $map?->getGame();
        if (!$map || !$mapGame || $mapGame->getId() !== $gameId) {
            return $this->json(
                ['error' => 'Carte introuvable'],
                Response::HTTP_NOT_FOUND
            );
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$game->isGameMaster($user)) {
            return $this->json(
                ['error' => 'Seul le maître du jeu peut modifier des cartes'],
                Response::HTTP_FORBIDDEN
            );
        }

        $dto = $this->serializer->deserialize(
            $request->getContent(),
            UpdateMapDTO::class,
            'json'
        );

        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            return $this->json(
                ['errors' => (string) $errors],
                Response::HTTP_BAD_REQUEST
            );
        }

        try {
            $map = $this->mapService->updateMap($map, $dto);

            return $this->json(
                $map,
                Response::HTTP_OK,
                [],
                ['groups' => 'map:read']
            );
        } catch (\Exception $e) {
            return $this->json(
                ['error' => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Activer une carte.
     */
    #[Route('/{id}/activate', name: 'activate', methods: ['POST'])]
    public function activate(int $gameId, int $id): JsonResponse
    {
        $game = $this->gameRepository->find($gameId);

        if (!$game) {
            return $this->json(
                ['error' => 'Partie introuvable'],
                Response::HTTP_NOT_FOUND
            );
        }

        $map = $this->mapRepository->find($id);

        // Vérification null-safety pour PHPStan
        $mapGame = $map?->getGame();
        if (!$map || !$mapGame || $mapGame->getId() !== $gameId) {
            return $this->json(
                ['error' => 'Carte introuvable'],
                Response::HTTP_NOT_FOUND
            );
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$game->isGameMaster($user)) {
            return $this->json(
                ['error' => 'Seul le maître du jeu peut activer des cartes'],
                Response::HTTP_FORBIDDEN
            );
        }

        try {
            $map = $this->mapService->activateMap($map);

            return $this->json(
                $map,
                Response::HTTP_OK,
                [],
                ['groups' => 'map:read']
            );
        } catch (\Exception $e) {
            return $this->json(
                ['error' => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Supprimer une carte.
     */
    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $gameId, int $id): JsonResponse
    {
        $game = $this->gameRepository->find($gameId);

        if (!$game) {
            return $this->json(
                ['error' => 'Partie introuvable'],
                Response::HTTP_NOT_FOUND
            );
        }

        $map = $this->mapRepository->find($id);

        // Vérification null-safety pour PHPStan
        $mapGame = $map?->getGame();
        if (!$map || !$mapGame || $mapGame->getId() !== $gameId) {
            return $this->json(
                ['error' => 'Carte introuvable'],
                Response::HTTP_NOT_FOUND
            );
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$game->isGameMaster($user)) {
            return $this->json(
                ['error' => 'Seul le maître du jeu peut supprimer des cartes'],
                Response::HTTP_FORBIDDEN
            );
        }

        try {
            $this->mapService->deleteMap($map);

            return $this->json(
                ['message' => 'Carte supprimée avec succès'],
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            return $this->json(
                ['error' => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
