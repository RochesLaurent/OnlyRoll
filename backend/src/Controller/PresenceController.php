<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\GameRepository;
use App\Service\PresenceService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Contrôleur pour gérer la présence en temps réel des joueurs.
 */
#[Route('/api/games/{gameId}/presence', name: 'api_presence_')]
#[IsGranted('ROLE_USER')]
final class PresenceController extends AbstractController
{
    public function __construct(
        private readonly PresenceService $presenceService,
        private readonly GameRepository $gameRepository,
    ) {
    }

    /**
     * Notifie que l'utilisateur a rejoint la partie (page chargée).
     */
    #[Route('/join', name: 'join', methods: ['POST'])]
    public function join(int $gameId): JsonResponse
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

        $this->presenceService->userJoined($game, $user);

        return $this->json([
            'success' => true,
            'onlineUsers' => $this->presenceService->getOnlineUserIds($gameId),
        ], Response::HTTP_OK);
    }

    /**
     * Notifie que l'utilisateur a quitté la partie (page fermée/navigué ailleurs).
     */
    #[Route('/leave', name: 'leave', methods: ['POST'])]
    public function leave(int $gameId): JsonResponse
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

        // Pas besoin de vérifier l'accès pour leave
        $this->presenceService->userLeft($game, $user);

        return $this->json([
            'success' => true,
        ], Response::HTTP_OK);
    }

    /**
     * Heartbeat pour indiquer que l'utilisateur est toujours actif.
     * À appeler régulièrement (toutes les 30 secondes).
     */
    #[Route('/heartbeat', name: 'heartbeat', methods: ['POST'])]
    public function heartbeat(int $gameId): JsonResponse
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

        $this->presenceService->heartbeat($game, $user);

        return $this->json([
            'success' => true,
            'onlineUsers' => $this->presenceService->getOnlineUserIds($gameId),
            'onlineCount' => $this->presenceService->getOnlineCount($gameId),
        ], Response::HTTP_OK);
    }

    /**
     * Récupère la liste des utilisateurs actuellement en ligne dans la partie.
     */
    #[Route('/online', name: 'online', methods: ['GET'])]
    public function getOnlineUsers(int $gameId): JsonResponse
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

        return $this->json([
            'onlineUsers' => $this->presenceService->getOnlineUserIds($gameId),
            'onlineCount' => $this->presenceService->getOnlineCount($gameId),
        ], Response::HTTP_OK);
    }
}
