<?php

namespace App\Controller;

use App\DTO\Game\CreateGameDTO;
use App\DTO\Game\GameFilterDTO;
use App\DTO\Game\JoinGameDTO;
use App\DTO\Game\UpdateGameDTO;
use App\Repository\GameRepository;
use App\Service\GameService;
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

#[Route('/api/games', name: 'api_game_')]
#[IsGranted('ROLE_USER')]
class GameController extends AbstractController
{
    public function __construct(
        private readonly GameService $gameService,
        private readonly GameRepository $gameRepository,
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
    ) {
    }

    /**
     * Liste toutes les parties publiques avec filtres et pagination.
     */
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        // Créer le DTO à partir des query params
        $filterDTO = new GameFilterDTO();
        $filterDTO->search = $request->query->getString('search') ?: null;
        $filterDTO->title = $request->query->getString('title') ?: null;
        $filterDTO->gameMaster = $request->query->getString('gameMaster') ?: null;
        $filterDTO->status = $request->query->getString('status') ?: null;
        $filterDTO->page = (int) $request->query->get('page', 1);
        $filterDTO->limit = (int) $request->query->get('limit', 12);

        // Validation
        $errors = $this->validator->validate($filterDTO);
        if (\count($errors) > 0) {
            return $this->json(['errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }

        // Récupérer les parties filtrées et paginées
        $result = $this->gameRepository->findPublicGamesWithFilters($filterDTO);

        return $this->json([
            'data' => $result['data'],
            'meta' => [
                'total' => $result['total'],
                'page' => $result['page'],
                'limit' => $result['limit'],
                'totalPages' => $result['totalPages'],
            ],
        ], Response::HTTP_OK, [], ['groups' => 'game:list']);
    }

    /**
     * Liste les parties de l'utilisateur connecté.
     */
    #[Route('/my-games', name: 'my_games', methods: ['GET'])]
    public function myGames(): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $games = $this->gameRepository->findUserGames($user);

        return $this->json($games, Response::HTTP_OK, [], ['groups' => 'game:list']);
    }

    /**
     * Détails d'une partie.
     */
    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $game = $this->gameRepository->findGameWithPlayers($id);

        if (!$game) {
            return $this->json(['error' => 'Partie introuvable'], Response::HTTP_NOT_FOUND);
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$game->canBeViewedBy($user)) {
            return $this->json(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        return $this->json($game, Response::HTTP_OK, [], ['groups' => 'game:read']);
    }

    /**
     * Créer une nouvelle partie.
     */
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $dto = $this->serializer->deserialize(
            $request->getContent(),
            CreateGameDTO::class,
            'json',
        );

        $errors = $this->validator->validate($dto);
        if (\count($errors) > 0) {
            return $this->json(['errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        try {
            $game = $this->gameService->createGame($dto, $user);

            return $this->json($game, Response::HTTP_CREATED, [], ['groups' => 'game:read']);
        } catch (InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Mettre à jour une partie.
     */
    #[Route('/{id}', name: 'update', methods: ['PUT', 'PATCH'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $game = $this->gameRepository->find($id);

        if (!$game) {
            return $this->json(['error' => 'Partie introuvable'], Response::HTTP_NOT_FOUND);
        }

        $dto = $this->serializer->deserialize(
            $request->getContent(),
            UpdateGameDTO::class,
            'json',
        );

        $errors = $this->validator->validate($dto);
        if (\count($errors) > 0) {
            return $this->json(['errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        try {
            $game = $this->gameService->updateGame($game, $dto, $user);

            return $this->json($game, Response::HTTP_OK, [], ['groups' => 'game:read']);
        } catch (Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Rejoindre une partie par code d'invitation
     * Endpoint dédié pour rejoindre avec un code plutôt qu'un ID
     */
    #[Route('/join', name: 'join_by_code', methods: ['POST'])]
    public function joinByCode(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $inviteCode = $data['inviteCode'] ?? null;
        $password = $data['password'] ?? null;

        if (!$inviteCode) {
            return $this->json(
                ['error' => 'Code d\'invitation requis'],
                Response::HTTP_BAD_REQUEST,
            );
        }

        // Chercher la partie par son code d'invitation
        $game = $this->gameRepository->findByInviteCode($inviteCode);

        if (!$game) {
            return $this->json(
                ['error' => 'Code d\'invitation invalide'],
                Response::HTTP_NOT_FOUND,
            );
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        try {
            $gameId = $game->getId();
            if (null === $gameId) {
                return $this->json(['error' => 'ID de partie invalide'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $gamePlayer = $this->gameService->joinGame($gameId, $user, $password);

            return $this->json(
                $gamePlayer,
                Response::HTTP_OK,
                [],
                ['groups' => 'game:read'],
            );
        } catch (Exception $e) {
            return $this->json(['error' => $e->getMessage()], $e->getCode() ?: Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Rejoindre une partie par ID.
     */
    #[Route('/{id}/join', name: 'join', methods: ['POST'])]
    public function join(int $id, Request $request): JsonResponse
    {
        $dto = $this->serializer->deserialize(
            $request->getContent(),
            JoinGameDTO::class,
            'json',
        );

        $errors = $this->validator->validate($dto);
        if (\count($errors) > 0) {
            return $this->json(['errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        try {
            $gamePlayer = $this->gameService->joinGame($id, $user, $dto->password);

            return $this->json($gamePlayer, Response::HTTP_OK, [], ['groups' => 'game:read']);
        } catch (Exception $e) {
            return $this->json(['error' => $e->getMessage()], $e->getCode());
        }
    }

    /**
     * Quitter une partie.
     */
    #[Route('/{id}/leave', name: 'leave', methods: ['POST'])]
    public function leave(int $id): JsonResponse
    {
        $game = $this->gameRepository->find($id);

        if (!$game) {
            return $this->json(['error' => 'Partie introuvable'], Response::HTTP_NOT_FOUND);
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        try {
            $this->gameService->leaveGame($game, $user);

            return $this->json(['message' => 'Vous avez quitté la partie'], Response::HTTP_OK);
        } catch (Exception $e) {
            return $this->json(['error' => $e->getMessage()], $e->getCode());
        }
    }

    /**
     * Supprimer/Archiver une partie.
     */
    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $game = $this->gameRepository->find($id);

        if (!$game) {
            return $this->json(['error' => 'Partie introuvable'], Response::HTTP_NOT_FOUND);
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        try {
            $this->gameService->deleteGame($game, $user);

            return $this->json(['message' => 'Partie archivée avec succès'], Response::HTTP_OK);
        } catch (Exception $e) {
            return $this->json(['error' => $e->getMessage()], $e->getCode());
        }
    }
}
