<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\Chat\SendMessageDTO;
use App\Entity\GameMessage;
use App\Repository\GameRepository;
use App\Service\ChatService;
use DateTimeImmutable;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Contrôleur de gestion du chat et des lancers de dés.
 */
#[Route('/api/games/{gameId}/chat', name: 'api_chat_')]
#[IsGranted('ROLE_USER')]
final class ChatController extends AbstractController
{
    public function __construct(
        private readonly ChatService $chatService,
        private readonly GameRepository $gameRepository,
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
    ) {
    }

    /**
     * Liste les messages récents du chat.
     */
    #[Route('/messages', name: 'messages', methods: ['GET'])]
    public function getMessages(int $gameId, Request $request): JsonResponse
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

        $limit = (int) $request->query->get('limit', 50);
        $limit = max(1, min($limit, 200)); // Entre 1 et 200

        // Récupérer uniquement les messages visibles pour l'utilisateur
        $messages = $this->chatService->getVisibleMessagesForUser($game, $user, $limit);

        return $this->json(
            $messages,
            Response::HTTP_OK,
            [],
            ['groups' => 'message:list'],
        );
    }

    /**
     * Envoie un message dans le chat.
     */
    #[Route('/messages', name: 'send_message', methods: ['POST'])]
    public function sendMessage(int $gameId, Request $request): JsonResponse
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

        $dto = $this->serializer->deserialize(
            $request->getContent(),
            SendMessageDTO::class,
            'json',
        );

        $errors = $this->validator->validate($dto);
        if (\count($errors) > 0) {
            return $this->json(
                ['errors' => (string) $errors],
                Response::HTTP_BAD_REQUEST,
            );
        }

        try {
            $message = $this->chatService->sendMessage($game, $user, $dto);

            return $this->json(
                $message,
                Response::HTTP_CREATED,
                [],
                ['groups' => 'message:read'],
            );
        }
        catch (Exception $e) {
            return $this->json(
                ['error' => $e->getMessage()],
                Response::HTTP_BAD_REQUEST,
            );
        }
    }

    /**
     * Récupère les messages par type.
     */
    #[Route('/messages/type/{type}', name: 'messages_by_type', methods: ['GET'])]
    public function getMessagesByType(int $gameId, string $type, Request $request): JsonResponse
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

        $limit = (int) $request->query->get('limit', 50);
        $limit = max(1, min($limit, 200));

        try {
            $messages = $this->chatService->getMessagesByType($game, $type, $limit);

            return $this->json(
                $messages,
                Response::HTTP_OK,
                [],
                ['groups' => 'message:list'],
            );
        }
        catch (Exception $e) {
            return $this->json(
                ['error' => $e->getMessage()],
                Response::HTTP_BAD_REQUEST,
            );
        }
    }

    /**
     * Récupère uniquement les lancers de dés.
     */
    #[Route('/dice-rolls', name: 'dice_rolls', methods: ['GET'])]
    public function getDiceRolls(int $gameId, Request $request): JsonResponse
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

        $limit = (int) $request->query->get('limit', 20);
        $limit = max(1, min($limit, 100));

        $messages = $this->chatService->getMessagesByType($game, GameMessage::TYPE_DICE_ROLL, $limit);

        return $this->json(
            $messages,
            Response::HTTP_OK,
            [],
            ['groups' => 'message:list'],
        );
    }

    /**
     * Lancer des dés et publier le résultat.
     */
    #[Route('/roll-dice', name: 'roll_dice', methods: ['POST'])]
    public function rollDice(int $gameId, Request $request): JsonResponse
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

        $data = json_decode($request->getContent(), true);

        if (!isset($data['formula'])) {
            return $this->json(
                ['error' => 'La formule de dés est obligatoire'],
                Response::HTTP_BAD_REQUEST,
            );
        }

        $formula = $data['formula'];

        try {
            // Format attendu: "2d6+3" ou "1d20"
            if (!preg_match('/^(\d+)d(\d+)([+-]\d+)?$/i', $formula, $matches)) {
                return $this->json(
                    ['error' => 'Format de dés invalide. Utilisez le format XdY ou XdY+Z'],
                    Response::HTTP_BAD_REQUEST,
                );
            }

            $numberOfDice = (int) $matches[1];
            $sidesPerDie = (int) $matches[2];
            $modifier = isset($matches[3]) ? (int) $matches[3] : 0;

            if ($numberOfDice < 1 || $numberOfDice > 100) {
                return $this->json(
                    ['error' => 'Le nombre de dés doit être entre 1 et 100'],
                    Response::HTTP_BAD_REQUEST,
                );
            }

            if ($sidesPerDie < 2 || $sidesPerDie > 1000) {
                return $this->json(
                    ['error' => 'Le nombre de faces doit être entre 2 et 1000'],
                    Response::HTTP_BAD_REQUEST,
                );
            }

            // Lancer les dés
            $results = [];
            $total = $modifier;

            for ($i = 0; $i < $numberOfDice; ++$i) {
                $roll = random_int(1, $sidesPerDie);
                $results[] = $roll;
                $total += $roll;
            }

            // Créer le message de lancer de dés
            $message = $this->chatService->createDiceRollMessage(
                $game,
                $user,
                $formula,
                [
                    'rolls' => $results,
                    'total' => $total,
                    'modifier' => $modifier,
                    'formula' => $formula,
                ],
            );

            return $this->json($message, Response::HTTP_CREATED, [], ['groups' => 'message:read']);
        }
        catch (Exception $e) {
            return $this->json(
                ['error' => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }
    }

    /**
     * Récupère les statistiques du chat.
     */
    #[Route('/stats', name: 'stats', methods: ['GET'])]
    public function getStats(int $gameId): JsonResponse
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
                ['error' => 'Seul le maître du jeu peut voir les statistiques'],
                Response::HTTP_FORBIDDEN,
            );
        }

        $stats = $this->chatService->getMessageStats($game);

        return $this->json([
            'stats' => $stats,
            'total' => array_sum($stats),
        ], Response::HTTP_OK);
    }

    /**
     * Récupère les messages depuis une date donnée (polling).
     */
    #[Route('/messages/since', name: 'messages_since', methods: ['GET'])]
    public function getMessagesSince(int $gameId, Request $request): JsonResponse
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

        $since = $request->query->get('since');

        if (!$since || !\is_string($since)) {
            return $this->json(
                ['error' => 'Le paramètre "since" est obligatoire'],
                Response::HTTP_BAD_REQUEST,
            );
        }

        try {
            $sinceDate = new DateTimeImmutable($since);
            $messages = $this->chatService->getMessagesSince($game, $sinceDate, $user);

            return $this->json($messages, Response::HTTP_OK, [], ['groups' => 'message:list']);
        }
        catch (Exception $e) {
            return $this->json(
                ['error' => 'Format de date invalide'],
                Response::HTTP_BAD_REQUEST,
            );
        }
    }
}
