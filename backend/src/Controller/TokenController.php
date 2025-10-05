<?php

namespace App\Controller;

use App\DTO\Token\CreateTokenDTO;
use App\DTO\Token\MoveTokenDTO;
use App\Repository\GameMapRepository;
use App\Repository\GameRepository;
use App\Repository\GameTokenRepository;
use App\Service\TokenService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/games/{gameId}/maps/{mapId}/tokens', name: 'api_token_')]
#[IsGranted('ROLE_USER')]
class TokenController extends AbstractController
{
    public function __construct(
        private readonly TokenService $tokenService,
        private readonly GameRepository $gameRepository,
        private readonly GameMapRepository $mapRepository,
        private readonly GameTokenRepository $tokenRepository,
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
    ) {
    }

    /**
     * Liste tous les tokens d'une carte.
     */
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(int $gameId, int $mapId, Request $request): JsonResponse
    {
        $game = $this->gameRepository->find($gameId);

        if (!$game) {
            return $this->json(['error' => 'Partie introuvable'], Response::HTTP_NOT_FOUND);
        }

        $map = $this->mapRepository->find($mapId);

        if (!$map || $map->getGame()->getId() !== $gameId) {
            return $this->json(['error' => 'Carte introuvable'], Response::HTTP_NOT_FOUND);
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$game->canBeViewedBy($user)) {
            return $this->json(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        // Les joueurs normaux ne voient que les tokens visibles
        $visibleOnly = !$game->isGameMaster($user);
        $tokens = $this->tokenService->getTokensByMap($map, $visibleOnly);

        return $this->json($tokens, Response::HTTP_OK, [], ['groups' => 'token:list']);
    }

    /**
     * Détails d'un token.
     */
    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $gameId, int $mapId, int $id): JsonResponse
    {
        $game = $this->gameRepository->find($gameId);

        if (!$game) {
            return $this->json(['error' => 'Partie introuvable'], Response::HTTP_NOT_FOUND);
        }

        $token = $this->tokenRepository->find($id);

        if (!$token || $token->getMap()->getId() !== $mapId) {
            return $this->json(['error' => 'Token introuvable'], Response::HTTP_NOT_FOUND);
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$game->canBeViewedBy($user)) {
            return $this->json(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        // Vérifier si le token est visible pour les joueurs normaux
        if (!$game->isGameMaster($user) && !$token->isVisible()) {
            return $this->json(['error' => 'Token introuvable'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($token, Response::HTTP_OK, [], ['groups' => 'token:read']);
    }

    /**
     * Créer un nouveau token.
     */
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(int $gameId, int $mapId, Request $request): JsonResponse
    {
        $game = $this->gameRepository->find($gameId);

        if (!$game) {
            return $this->json(['error' => 'Partie introuvable'], Response::HTTP_NOT_FOUND);
        }

        $map = $this->mapRepository->find($mapId);

        if (!$map || $map->getGame()->getId() !== $gameId) {
            return $this->json(['error' => 'Carte introuvable'], Response::HTTP_NOT_FOUND);
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$game->isGameMaster($user)) {
            return $this->json(['error' => 'Seul le maître du jeu peut créer des tokens'], Response::HTTP_FORBIDDEN);
        }

        $dto = $this->serializer->deserialize(
            $request->getContent(),
            CreateTokenDTO::class,
            'json'
        );

        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            return $this->json(['errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }

        try {
            $token = $this->tokenService->createToken($map, $dto);

            return $this->json($token, Response::HTTP_CREATED, [], ['groups' => 'token:read']);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Déplacer un token.
     */
    #[Route('/{id}/move', name: 'move', methods: ['POST'])]
    public function move(int $gameId, int $mapId, int $id, Request $request): JsonResponse
    {
        $game = $this->gameRepository->find($gameId);

        if (!$game) {
            return $this->json(['error' => 'Partie introuvable'], Response::HTTP_NOT_FOUND);
        }

        $token = $this->tokenRepository->find($id);

        if (!$token || $token->getMap()->getId() !== $mapId) {
            return $this->json(['error' => 'Token introuvable'], Response::HTTP_NOT_FOUND);
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$game->canBeViewedBy($user)) {
            return $this->json(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $dto = $this->serializer->deserialize(
            $request->getContent(),
            MoveTokenDTO::class,
            'json'
        );

        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            return $this->json(['errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }

        try {
            $token = $this->tokenService->moveTokenWithDTO($token, $dto);

            return $this->json($token, Response::HTTP_OK, [], ['groups' => 'token:read']);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Basculer la visibilité d'un token.
     */
    #[Route('/{id}/toggle-visibility', name: 'toggle_visibility', methods: ['POST'])]
    public function toggleVisibility(int $gameId, int $mapId, int $id): JsonResponse
    {
        $game = $this->gameRepository->find($gameId);

        if (!$game) {
            return $this->json(['error' => 'Partie introuvable'], Response::HTTP_NOT_FOUND);
        }

        $token = $this->tokenRepository->find($id);

        if (!$token || $token->getMap()->getId() !== $mapId) {
            return $this->json(['error' => 'Token introuvable'], Response::HTTP_NOT_FOUND);
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$game->isGameMaster($user)) {
            return $this->json(['error' => 'Seul le maître du jeu peut modifier la visibilité'], Response::HTTP_FORBIDDEN);
        }

        try {
            $token = $this->tokenService->toggleVisibility($token);

            return $this->json($token, Response::HTTP_OK, [], ['groups' => 'token:read']);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Verrouiller/déverrouiller un token.
     */
    #[Route('/{id}/toggle-lock', name: 'toggle_lock', methods: ['POST'])]
    public function toggleLock(int $gameId, int $mapId, int $id): JsonResponse
    {
        $game = $this->gameRepository->find($gameId);

        if (!$game) {
            return $this->json(['error' => 'Partie introuvable'], Response::HTTP_NOT_FOUND);
        }

        $token = $this->tokenRepository->find($id);

        if (!$token || $token->getMap()->getId() !== $mapId) {
            return $this->json(['error' => 'Token introuvable'], Response::HTTP_NOT_FOUND);
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$game->isGameMaster($user)) {
            return $this->json(['error' => 'Seul le maître du jeu peut verrouiller des tokens'], Response::HTTP_FORBIDDEN);
        }

        try {
            $token = $this->tokenService->toggleLock($token);

            return $this->json($token, Response::HTTP_OK, [], ['groups' => 'token:read']);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Supprimer un token.
     */
    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $gameId, int $mapId, int $id): JsonResponse
    {
        $game = $this->gameRepository->find($gameId);

        if (!$game) {
            return $this->json(['error' => 'Partie introuvable'], Response::HTTP_NOT_FOUND);
        }

        $token = $this->tokenRepository->find($id);

        if (!$token || $token->getMap()->getId() !== $mapId) {
            return $this->json(['error' => 'Token introuvable'], Response::HTTP_NOT_FOUND);
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$game->isGameMaster($user)) {
            return $this->json(['error' => 'Seul le maître du jeu peut supprimer des tokens'], Response::HTTP_FORBIDDEN);
        }

        try {
            $this->tokenService->deleteToken($token);

            return $this->json(['message' => 'Token supprimé avec succès'], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
