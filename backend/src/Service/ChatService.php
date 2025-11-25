<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\Chat\SendMessageDTO;
use App\Entity\Game;
use App\Entity\GameMessage;
use App\Entity\User;
use App\Enum\MessageType;
use App\Repository\GameMessageRepository;
use App\Repository\UserRepository;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

readonly class ChatService
{
    public function __construct(
        private EntityManagerInterface $em,
        private GameMessageRepository $messageRepository,
        private UserRepository $userRepository,
        private MercurePublisher $mercurePublisher,
    ) {
    }

    public function sendMessage(Game $game, User $user, SendMessageDTO $dto): GameMessage
    {
        // Validation du type whisper
        $recipient = null;
        if (MessageType::WHISPER === $dto->type) {
            if (null === $dto->recipientId) {
                throw new BadRequestHttpException('Un destinataire est requis pour un message privé.');
            }

            $recipient = $this->userRepository->find($dto->recipientId);
            if (null === $recipient) {
                throw new BadRequestHttpException('Destinataire introuvable.');
            }

            // Vérifier que le destinataire est dans la partie
            if (!$game->hasPlayer($recipient)) {
                throw new BadRequestHttpException('Le destinataire doit être membre de la partie.');
            }
        }

        // Validation messages système (seulement pour le GM)
        if (MessageType::SYSTEM === $dto->type) {
            if (!$game->isGameMaster($user)) {
                throw new BadRequestHttpException('Seul le MJ peut envoyer des messages système.');
            }
        }

        // Création du message
        $message = new GameMessage();
        $message->setGame($game);
        $message->setUser($user);
        $message->setContent($dto->content);
        $message->setType($dto->type);
        $message->setIsInCharacter($dto->isInCharacter);

        if ($recipient) {
            $message->setRecipient($recipient);
        }

        // Appel manuel du hook PrePersist pour les tests unitaires
        $message->onPrePersist();

        $this->em->persist($message);
        $this->em->flush();

        // Vérifications pour PHPStan
        $gameId = $game->getId();
        \assert(null !== $gameId, 'Game ID cannot be null after flush');

        $createdAt = $message->getCreatedAt();
        \assert(null !== $createdAt, 'CreatedAt cannot be null after flush');

        // Publication via Mercure
        $this->mercurePublisher->publishChatMessage($gameId, [
            'messageId' => $message->getId(),
            'userId' => $user->getId(),
            'userName' => $user->getPseudo(),
            'content' => $message->getContent(),
            'type' => $message->getType(),
            'isIC' => $message->isInCharacter(),
            'recipientId' => $message->getRecipient()?->getId(),
            'recipientName' => $message->getRecipient()?->getPseudo(),
            'createdAt' => $createdAt->format('c'),
        ]);

        return $message;
    }

    /**
     * Récupère les messages récents d'une partie.
     *
     * @param int $limit Nombre de messages à récupérer (max 200)
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
     * Récupère les messages visibles pour un utilisateur
     * (exclut les whispers dont il n'est pas destinataire).
     *
     * @return GameMessage[]
     */
    public function getVisibleMessagesForUser(Game $game, User $user, ?int $limit = null): array
    {
        return $this->messageRepository->findVisibleForUser($game, $user, $limit);
    }

    /**
     * Récupère les messages d'un type spécifique.
     *
     * @return GameMessage[]
     */
    public function getMessagesByType(Game $game, MessageType $type, ?int $limit = null): array
    {
        return $this->messageRepository->findByType($game, $type);
    }

    /**
     * Crée un message système
     * (réservé au Game Master).
     */
    public function createSystemMessage(Game $game, string $content): GameMessage
    {
        $message = new GameMessage();
        $message->setGame($game);
        $message->setUser($game->getGameMaster());
        $message->setContent($content);
        $message->setType(MessageType::SYSTEM);
        $message->setIsInCharacter(false);

        // Appel manuel du hook PrePersist pour les tests unitaires
        $message->onPrePersist();

        $this->em->persist($message);
        $this->em->flush();

        // Vérifications pour PHPStan
        $gameId = $game->getId();
        \assert(null !== $gameId, 'Game ID cannot be null after flush');

        $createdAt = $message->getCreatedAt();
        \assert(null !== $createdAt, 'CreatedAt cannot be null after flush');

        // Publication Mercure
        $this->mercurePublisher->publishChatMessage($gameId, [
            'messageId' => $message->getId(),
            'userId' => null,
            'userName' => 'Système',
            'content' => $message->getContent(),
            'type' => $message->getType(),
            'isIC' => false,
            'recipientId' => null,
            'recipientName' => null,
            'createdAt' => $createdAt->format('c'),
        ]);

        return $message;
    }

    /**
     * Crée un message de lancer de dés.
     *
     * @param string $diceExpression Expression du lancer (ex: "1d20+5")
     * @param array<string, mixed> $results Résultats du lancer
     * @param bool $isPrivate Si le lancer est privé
     * @param User|null $recipient Destinataire du jet de dés (pour les jets privés)
     */
    public function createDiceRollMessage(
        Game $game,
        User $user,
        string $diceExpression,
        array $results,
        bool $isPrivate = false,
        ?User $recipient = null,
    ): GameMessage {
        $content = \sprintf(
            '%s a lancé %s et obtenu %d',
            $user->getPseudo(),
            $diceExpression,
            $results['total'] ?? 0,
        );

        $message = new GameMessage();
        $message->setGame($game);
        $message->setUser($user);
        $message->setContent($content);
        $message->setType(MessageType::DICE_ROLL);
        $message->setIsInCharacter(true);
        $message->setDiceResult($results);

        // Si c'est un jet privé, définir le destinataire
        if (null !== $recipient) {
            $message->setRecipient($recipient);
        }

        // Appel manuel du hook PrePersist pour les tests unitaires
        $message->onPrePersist();

        $this->em->persist($message);
        $this->em->flush();

        // Vérifications pour PHPStan
        $gameId = $game->getId();
        \assert(null !== $gameId, 'Game ID cannot be null after flush');

        $createdAt = $message->getCreatedAt();
        \assert(null !== $createdAt, 'CreatedAt cannot be null after flush');

        // Publication via Mercure (utilise publishChatMessage pour cohérence avec les autres messages)
        $this->mercurePublisher->publishChatMessage($gameId, [
            'messageId' => $message->getId(),
            'userId' => $user->getId(),
            'userName' => $user->getPseudo(),
            'content' => $content,
            'type' => MessageType::DICE_ROLL,
            'isIC' => $message->isInCharacter(),
            'recipientId' => $recipient?->getId(),
            'recipientName' => $recipient?->getPseudo(),
            'createdAt' => $createdAt->format('c'),
            'diceResult' => $results,
        ]);

        return $message;
    }

    /**
     * Récupère les statistiques des messages d'une partie.
     *
     * @return array{total: int, byType: array<string, int>}
     */
    public function getMessageStats(Game $game): array
    {
        return $this->messageRepository->getStatsByGame($game);
    }

    /**
     * Supprime les messages anciens d'une partie.
     *
     * @param DateTimeInterface $before Supprimer les messages avant cette date
     *
     * @return int Nombre de messages supprimés
     */
    public function deleteOldMessages(Game $game, DateTimeInterface $before): int
    {
        return $this->messageRepository->deleteOlderThan($game, $before);
    }

    /**
     * Récupère les messages créés après une date donnée.
     * Utile pour le polling ou la récupération incrémentale.
     *
     * @return GameMessage[]
     */
    public function getMessagesSince(Game $game, DateTimeInterface $since, ?User $user = null): array
    {
        if ($user) {
            // Si un utilisateur est fourni, on filtre les messages visibles pour lui
            return $this->messageRepository->findVisibleSince($game, $since, $user);
        }

        // Sinon, on retourne tous les messages depuis cette date
        return $this->messageRepository->findSince($game, $since);
    }
}
