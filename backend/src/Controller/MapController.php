<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\Map\CreateMapDTO;
use App\DTO\Map\UpdateMapDTO;
use App\Repository\GameMapRepository;
use App\Repository\GameRepository;
use App\Service\FileUploader;
use App\Service\MapService;
use Exception;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Contrôleur de gestion des cartes de jeu.
 */
#[Route('/api/games/{gameId}/maps', name: 'api_map_')]
#[IsGranted('ROLE_USER')]
final class MapController extends AbstractController
{
    public function __construct(
        private readonly MapService $mapService,
        private readonly GameRepository $gameRepository,
        private readonly GameMapRepository $mapRepository,
        private readonly FileUploader $fileUploader,
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
    ) {
    }

    /**
     * Liste toutes les cartes d'une partie.
     */
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(int $gameId): JsonResponse
    {
        $game = $this->gameRepository->find($gameId);

        if (!$game) {
            return $this->json(
                ['error' => 'Partie introuvable'],
                Response::HTTP_NOT_FOUND,
            );
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$game->canBeViewedBy($user)) {
            return $this->json(
                ['error' => 'Accès refusé'],
                Response::HTTP_FORBIDDEN,
            );
        }

        $maps = $this->mapService->getMapsByGame($game);

        return $this->json(
            $maps,
            Response::HTTP_OK,
            [],
            ['groups' => 'map:list'],
        );
    }

    /**
     * Récupère la carte active d'une partie.
     */
    #[Route('/active', name: 'active', methods: ['GET'])]
    public function getActive(int $gameId): JsonResponse
    {
        $game = $this->gameRepository->find($gameId);

        if (!$game) {
            return $this->json(
                ['error' => 'Partie introuvable'],
                Response::HTTP_NOT_FOUND,
            );
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$game->canBeViewedBy($user)) {
            return $this->json(
                ['error' => 'Accès refusé'],
                Response::HTTP_FORBIDDEN,
            );
        }

        $activeMap = $this->mapService->getActiveMap($game);

        if (!$activeMap) {
            return $this->json(
                ['error' => 'Aucune carte active'],
                Response::HTTP_NOT_FOUND,
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
                Response::HTTP_NOT_FOUND,
            );
        }

        $map = $this->mapRepository->find($id);

        $mapGame = $map?->getGame();
        if (!$map || !$mapGame || $mapGame->getId() !== $gameId) {
            return $this->json(
                ['error' => 'Carte introuvable'],
                Response::HTTP_NOT_FOUND,
            );
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$game->canBeViewedBy($user)) {
            return $this->json(
                ['error' => 'Accès refusé'],
                Response::HTTP_FORBIDDEN,
            );
        }

        return $this->json(
            $map,
            Response::HTTP_OK,
            [],
            ['groups' => 'map:read'],
        );
    }

    /**
     * Créer une nouvelle carte avec upload d'image.
     * Supporte à la fois JSON et multipart/form-data.
     */
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(int $gameId, Request $request): JsonResponse
    {
        $game = $this->gameRepository->find($gameId);

        if (!$game) {
            return $this->json(
                ['error' => 'Partie introuvable'],
                Response::HTTP_NOT_FOUND,
            );
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$game->isGameMaster($user)) {
            return $this->json(
                ['error' => 'Seul le maître du jeu peut créer des cartes'],
                Response::HTTP_FORBIDDEN,
            );
        }

        try {
            $imageUrl = null;
            $imageFile = $request->files->get('image');

            if ($imageFile) {
                $imageUrl = $this->fileUploader->uploadMapImage($imageFile);
            }

            $contentType = $request->headers->get('Content-Type') ?? '';

            if (str_contains($contentType, 'multipart/form-data')) {
                $dto = new CreateMapDTO();

                $name = $request->request->get('name');
                if (!\is_string($name)) {
                    return $this->json(
                        ['error' => 'Le nom est requis et doit être une chaîne de caractères'],
                        Response::HTTP_BAD_REQUEST,
                    );
                }
                $dto->name = $name;

                $description = $request->request->get('description');
                $dto->description = \is_string($description) ? $description : null;

                $dto->gridSize = (int) $request->request->get('gridSize', 50);

                $gridType = $request->request->get('gridType', 'square');
                $dto->gridType = \is_string($gridType) ? $gridType : 'square';

                $dto->width = (int) $request->request->get('width', 20);
                $dto->height = (int) $request->request->get('height', 20);

                $dto->imageUrl = $imageUrl;
            }
            else {
                $dto = $this->serializer->deserialize(
                    $request->getContent(),
                    CreateMapDTO::class,
                    'json',
                );

                if ($imageUrl) {
                    $dto->imageUrl = $imageUrl;
                }
            }

            $errors = $this->validator->validate($dto);
            if (\count($errors) > 0) {
                return $this->json(
                    ['errors' => (string) $errors],
                    Response::HTTP_BAD_REQUEST,
                );
            }

            $map = $this->mapService->createMap($game, $dto);

            return $this->json(
                $map,
                Response::HTTP_CREATED,
                [],
                ['groups' => 'map:read'],
            );
        }
        catch (InvalidArgumentException $e) {
            return $this->json(
                ['error' => $e->getMessage()],
                Response::HTTP_BAD_REQUEST,
            );
        }
        catch (Exception $e) {
            return $this->json(
                ['error' => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR,
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
                Response::HTTP_NOT_FOUND,
            );
        }

        $map = $this->mapRepository->find($id);

        // Vérification null-safety pour PHPStan
        $mapGame = $map?->getGame();
        if (!$map || !$mapGame || $mapGame->getId() !== $gameId) {
            return $this->json(
                ['error' => 'Carte introuvable'],
                Response::HTTP_NOT_FOUND,
            );
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$game->isGameMaster($user)) {
            return $this->json(
                ['error' => 'Seul le maître du jeu peut modifier des cartes'],
                Response::HTTP_FORBIDDEN,
            );
        }

        try {
            $imageFile = $request->files->get('image');

            if ($imageFile) {
                if ($map->getImageUrl()) {
                    $this->fileUploader->deleteFile($map->getImageUrl());
                }

                $imageUrl = $this->fileUploader->uploadMapImage($imageFile);
            }

            $dto = $this->serializer->deserialize(
                $request->getContent(),
                UpdateMapDTO::class,
                'json',
            );

            if (isset($imageUrl)) {
                $dto->imageUrl = $imageUrl;
            }

            $errors = $this->validator->validate($dto);
            if (\count($errors) > 0) {
                return $this->json(
                    ['errors' => (string) $errors],
                    Response::HTTP_BAD_REQUEST,
                );
            }

            $map = $this->mapService->updateMap($map, $dto);

            return $this->json(
                $map,
                Response::HTTP_OK,
                [],
                ['groups' => 'map:read'],
            );
        }
        catch (Exception $e) {
            return $this->json(
                ['error' => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR,
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
                Response::HTTP_NOT_FOUND,
            );
        }

        $map = $this->mapRepository->find($id);

        $mapGame = $map?->getGame();
        if (!$map || !$mapGame || $mapGame->getId() !== $gameId) {
            return $this->json(
                ['error' => 'Carte introuvable'],
                Response::HTTP_NOT_FOUND,
            );
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$game->isGameMaster($user)) {
            return $this->json(
                ['error' => 'Seul le maître du jeu peut activer des cartes'],
                Response::HTTP_FORBIDDEN,
            );
        }

        try {
            $map = $this->mapService->activateMap($map);

            return $this->json(
                $map,
                Response::HTTP_OK,
                [],
                ['groups' => 'map:read'],
            );
        }
        catch (Exception $e) {
            return $this->json(
                ['error' => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR,
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
                Response::HTTP_NOT_FOUND,
            );
        }

        $map = $this->mapRepository->find($id);

        $mapGame = $map?->getGame();
        if (!$map || !$mapGame || $mapGame->getId() !== $gameId) {
            return $this->json(
                ['error' => 'Carte introuvable'],
                Response::HTTP_NOT_FOUND,
            );
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$game->isGameMaster($user)) {
            return $this->json(
                ['error' => 'Seul le maître du jeu peut supprimer des cartes'],
                Response::HTTP_FORBIDDEN,
            );
        }

        try {
            if ($map->getImageUrl()) {
                $this->fileUploader->deleteFile($map->getImageUrl());
            }

            $this->mapService->deleteMap($map);

            return $this->json(
                ['message' => 'Carte supprimée avec succès'],
                Response::HTTP_OK,
            );
        }
        catch (Exception $e) {
            return $this->json(
                ['error' => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }
    }
}
