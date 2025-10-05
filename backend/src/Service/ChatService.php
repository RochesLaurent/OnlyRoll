<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\Chat\SendMessageDTO;
use App\Entity\Game;
use App\Entity\GameMessage;
use App\Entity\User;
use App\Repository\GameMessageRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ChatService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly GameMessageRepository $messageRepository,
        private readonly UserRepository $userRepository,
    ) {
    }

    /**
     * Envoie un message dans le chat d'un jeu.
     */
    public function sendMessage(Game $game, User $user, SendMessageDTO $dto): GameMessage
    {
        $message = new GameMessage();
        $message->setGame($game);
        $message->setUser($user);
        $message->setType($dto->type);
        $message->setContent($dto->content);
        $message->setIsInCharacter($dto->isInCharacter);

        // Gestion des résultats de dés
        if (null !== $dto->diceResult) {
            $message->setDiceResult($dto->diceResult);
        }

        // Gestion des chuchotements (whispers)
        if ($dto->type === GameMessage::TYPE_WHISPER) {
            if (null === $dto->recipientId) {
                throw new BadRequestHttpException('Un destinataire est requis pour les chuchotements.');
            }

            $recipient = $this->userRepository->find($dto->recipientId);
            
            if (null === $recipient) {
                throw new NotFoundHttpException('Le destinataire n\'existe pas.');
            }

            $message->setRecipient($recipient);
        }

        // Validation: les messages système ne peuvent être envoyés que par le système
        if ($dto->type === GameMessage::TYPE_SYSTEM) {
            // Vous pouvez ajouter une validation ici pour vérifier que l'utilisateur
            // a les permissions pour envoyer des messages système
            // Par exemple: vérifier si c'est le Game Master
            if (!$game->isGameMaster($user)) {
                throw new BadRequestHttpException('Seul le maître du jeu peut envoyer des messages système.');
            }
        }

        $this->entityManager->persist($message);
        $this->entityManager->flush();

        return $message;
    }

    /**
     * Récupère les messages récents d'un jeu.
     *
     * @return GameMessage[]
     */
    public function getRecentMessages(Game $game, int $limit = 50): array
    {
        if ($limit < 1 || $limit > 200) {
            throw new BadRequestHttpException('La limite doit être entre 1 et 200.');
        }

        return $this->messageRepository->findRecentMessages($game, $limit);
    }

    /**
     * Récupère les messages visibles pour un utilisateur.
     *
     * @return GameMessage[]
     */
    public function getVisibleMessagesForUser(Game $game, User $user, int $limit = 50): array
    {
        return $this->messageRepository->findVisibleMessagesForUser($game, $user, $limit);
    }

    /**
     * Récupère les messages par type.
     *
     * @return GameMessage[]
     */
    public function getMessagesByType(Game $game, string $type, int $limit = 50): array
    {
        $validTypes = [
            GameMessage::TYPE_CHAT,
            GameMessage::TYPE_EMOTE,
            GameMessage::TYPE_WHISPER,
            GameMessage::TYPE_SYSTEM,
            GameMessage::TYPE_DICE_ROLL,
        ];

        if (!in_array($type, $validTypes, true)) {
            throw new BadRequestHttpException('Type de message invalide.');
        }

        return $this->messageRepository->findMessagesByType($game, $type, $limit);
    }

    /**
     * Crée un message système.
     */
    public function createSystemMessage(Game $game, string $content): GameMessage
    {
        $message = new GameMessage();
        $message->setGame($game);
        $message->setType(GameMessage::TYPE_SYSTEM);
        $message->setContent($content);
        
        // Pour les messages système, on utilise le Game Master comme expéditeur
        $gameMaster = $game->getGameMaster();
        if (null === $gameMaster) {
            throw new BadRequestHttpException('Le jeu doit avoir un maître de jeu.');
        }
        
        $message->setUser($gameMaster);

        $this->entityManager->persist($message);
        $this->entityManager->flush();

        return $message;
    }

    /**
     * Crée un message de lancer de dés.
     */
    public function createDiceRollMessage(
        Game $game,
        User $user,
        string $diceFormula,
        array $results,
        int $total
    ): GameMessage {
        $message = new GameMessage();
        $message->setGame($game);
        $message->setUser($user);
        $message->setType(GameMessage::TYPE_DICE_ROLL);
        $message->setContent("Lance {$diceFormula}");
        
        $message->setDiceRoll(
            ['formula' => $diceFormula],
            $results,
            $total
        );

        $this->entityManager->persist($message);
        $this->entityManager->flush();

        return $message;
    }

    /**
     * Récupère les statistiques de messages pour un jeu.
     *
     * @return array<string, int>
     */
    public function getMessageStats(Game $game): array
    {
        return $this->messageRepository->countMessagesByType($game);
    }

    /**
     * Supprime les anciens messages d'un jeu.
     */
    public function deleteOldMessages(Game $game, \DateTimeInterface $before): int
    {
        return $this->messageRepository->deleteOldMessages($game, $before);
    }

    /**
     * Récupère les messages depuis une date donnée.
     *
     * @return GameMessage[]
     */
    public function getMessagesSince(Game $game, \DateTimeInterface $since): array
    {
        return $this->messageRepository->findMessagesSince($game, $since);
    }
}
