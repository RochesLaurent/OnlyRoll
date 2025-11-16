<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\Token\CreateTokenDTO;
use App\DTO\Token\MoveTokenDTO;
use App\Entity\GameMap;
use App\Entity\GameToken;
use App\Entity\User;
use App\Repository\GameTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

readonly class TokenService
{
    public function __construct(
        private EntityManagerInterface $em,
        private GameTokenRepository $tokenRepository,
        private MercurePublisher $mercurePublisher,
    ) {
    }

    /**
     * Crée un nouveau token sur une carte.
     */
    public function createToken(GameMap $map, CreateTokenDTO $dto): GameToken
    {
        // Validation des coordonnées dans les limites de la carte
        if (
            $dto->x < 0
            || $dto->x > $map->getWidth()
            || $dto->y < 0
            || $dto->y > $map->getHeight()
        ) {
            throw new BadRequestHttpException('Position invalide pour cette carte.');
        }

        $token = new GameToken();
        $token->setMap($map);
        $token->setName($dto->name);

        $token->setType($dto->type);
        $token->setImageUrl($dto->imageUrl);
        $token->setX($dto->x);
        $token->setY($dto->y);
        $token->setSize($dto->size);
        $token->setRotation($dto->rotation);
        $token->setIsVisible($dto->isVisible ?? true);
        $token->setIsLocked($dto->isLocked ?? false);
        $token->setLayer($dto->layer ?? 'tokens');
        $token->setSettings($dto->settings);

        $this->em->persist($token);
        $this->em->flush();

        // Vérifications pour PHPStan
        $game = $map->getGame();
        \assert(null !== $game, 'Map must have a game');
        $gameId = $game->getId();
        \assert(null !== $gameId, 'Game ID cannot be null after flush');

        // Vérifications pour PHPStan
        $tokenId = $token->getId();
        \assert(null !== $tokenId, 'Token ID cannot be null after flush');
        $createdAt = $token->getCreatedAt();
        \assert(null !== $createdAt, 'CreatedAt cannot be null after flush');

        // Publication via Mercure
        // IMPORTANT: Utiliser 'id' et non 'tokenId' pour compatibilité frontend
        $this->mercurePublisher->publishTokenCreated($gameId, [
            'id' => $tokenId,
            'mapId' => $map->getId(),
            'name' => $token->getName(),
            'type' => $token->getType(),
            'x' => $token->getX(),
            'y' => $token->getY(),
            'size' => $token->getSize(),
            'rotation' => $token->getRotation(),
            'imageUrl' => $token->getImageUrl(),
            'isVisible' => $token->isVisible(),
            'isLocked' => $token->isLocked(),
            'layer' => $token->getLayer(),
            'createdAt' => $createdAt->format('c'),
            'updatedAt' => $token->getUpdatedAt()?->format('c'),
        ]);

        return $token;
    }

    /**
     * Déplace un token.
     */
    public function moveToken(GameToken $token, MoveTokenDTO $dto): GameToken
    {
        // Vérifier que le token n'est pas verrouillé
        if ($token->isLocked()) {
            throw new BadRequestHttpException('Ce token est verrouillé et ne peut pas être déplacé.');
        }

        $map = $token->getMap();
        \assert(null !== $map, 'Token must have a map');

        // Validation des nouvelles coordonnées
        if (
            $dto->x < 0
            || $dto->x > $map->getWidth()
            || $dto->y < 0
            || $dto->y > $map->getHeight()
        ) {
            throw new BadRequestHttpException('Position invalide pour cette carte.');
        }

        $token->setX($dto->x);
        $token->setY($dto->y);

        $this->em->flush();

        // Vérifications pour PHPStan
        $game = $map->getGame();
        \assert(null !== $game, 'Map must have a game');
        $gameId = $game->getId();
        \assert(null !== $gameId, 'Game ID cannot be null');
        $mapId = $map->getId();
        \assert(null !== $mapId, 'Map ID cannot be null');
        $tokenId = $token->getId();
        \assert(null !== $tokenId, 'Token ID cannot be null');
        $createdAt = $token->getCreatedAt();
        \assert(null !== $createdAt, 'CreatedAt cannot be null');

        // Publication via Mercure - Structure complète pour cohérence frontend
        $this->mercurePublisher->publishGameEvent($gameId, 'token', [
            'action' => 'moved',
            'token' => [
                'id' => $tokenId,
                'mapId' => $mapId,
                'name' => $token->getName(),
                'type' => $token->getType(),
                'x' => $token->getX(),
                'y' => $token->getY(),
                'size' => $token->getSize(),
                'rotation' => $token->getRotation(),
                'imageUrl' => $token->getImageUrl(),
                'isVisible' => $token->isVisible(),
                'isLocked' => $token->isLocked(),
                'layer' => $token->getLayer(),
                'settings' => $token->getSettings(),
                'createdAt' => $createdAt->format('c'),
                'updatedAt' => $token->getUpdatedAt()?->format('c'),
            ],
        ]);

        return $token;
    }

    /**
     * Récupère tous les tokens d'une carte.
     *
     * @return GameToken[]
     */
    public function getTokensByMap(GameMap $map, ?User $user = null): array
    {
        if ($user) {
            return $this->tokenRepository->findVisibleByMap($map, $user);
        }

        return $this->tokenRepository->findByMap($map);
    }

    /**
     * Récupère un token par son ID.
     */
    public function getTokenById(int $tokenId): ?GameToken
    {
        return $this->tokenRepository->find($tokenId);
    }

    /**
     * Toggle la visibilité d'un token.
     */
    public function toggleVisibility(GameToken $token): GameToken
    {
        $token->setIsVisible(!$token->isVisible());
        $this->em->flush();

        // Vérifications pour PHPStan
        $map = $token->getMap();
        \assert(null !== $map, 'Token must have a map');
        $game = $map->getGame();
        \assert(null !== $game, 'Map must have a game');
        $gameId = $game->getId();
        \assert(null !== $gameId, 'Game ID cannot be null');
        $tokenId = $token->getId();
        \assert(null !== $tokenId, 'Token ID cannot be null');
        $createdAt = $token->getCreatedAt();
        \assert(null !== $createdAt, 'CreatedAt cannot be null');

        // Publication Mercure - Structure complète avec 'action' = 'updated'
        $this->mercurePublisher->publishGameEvent(
            $gameId,
            'token',
            [
                'action' => 'updated',
                'token' => [
                    'id' => $tokenId,
                    'mapId' => $map->getId(),
                    'name' => $token->getName(),
                    'type' => $token->getType(),
                    'x' => $token->getX(),
                    'y' => $token->getY(),
                    'size' => $token->getSize(),
                    'rotation' => $token->getRotation(),
                    'imageUrl' => $token->getImageUrl(),
                    'isVisible' => $token->isVisible(),
                    'isLocked' => $token->isLocked(),
                    'layer' => $token->getLayer(),
                    'settings' => $token->getSettings(),
                    'createdAt' => $createdAt->format('c'),
                    'updatedAt' => $token->getUpdatedAt()?->format('c'),
                ],
            ],
        );

        return $token;
    }

    /**
     * Toggle le verrouillage d'un token.
     */
    public function toggleLock(GameToken $token): GameToken
    {
        $token->setIsLocked(!$token->isLocked());
        $this->em->flush();

        // Vérifications pour PHPStan
        $map = $token->getMap();
        \assert(null !== $map, 'Token must have a map');
        $game = $map->getGame();
        \assert(null !== $game, 'Map must have a game');
        $gameId = $game->getId();
        \assert(null !== $gameId, 'Game ID cannot be null');
        $tokenId = $token->getId();
        \assert(null !== $tokenId, 'Token ID cannot be null');
        $createdAt = $token->getCreatedAt();
        \assert(null !== $createdAt, 'CreatedAt cannot be null');

        // Publication Mercure - Structure complète avec 'action' = 'updated'
        $this->mercurePublisher->publishGameEvent(
            $gameId,
            'token',
            [
                'action' => 'updated',
                'token' => [
                    'id' => $tokenId,
                    'mapId' => $map->getId(),
                    'name' => $token->getName(),
                    'type' => $token->getType(),
                    'x' => $token->getX(),
                    'y' => $token->getY(),
                    'size' => $token->getSize(),
                    'rotation' => $token->getRotation(),
                    'imageUrl' => $token->getImageUrl(),
                    'isVisible' => $token->isVisible(),
                    'isLocked' => $token->isLocked(),
                    'layer' => $token->getLayer(),
                    'settings' => $token->getSettings(),
                    'createdAt' => $createdAt->format('c'),
                    'updatedAt' => $token->getUpdatedAt()?->format('c'),
                ],
            ],
        );

        return $token;
    }

    /**
     * Supprime un token.
     */
    public function deleteToken(GameToken $token): void
    {
        // Vérifications pour PHPStan
        $map = $token->getMap();
        \assert(null !== $map, 'Token must have a map');
        $game = $map->getGame();
        \assert(null !== $game, 'Map must have a game');
        $gameId = $game->getId();
        \assert(null !== $gameId, 'Game ID cannot be null');
        $tokenId = $token->getId();
        \assert(null !== $tokenId, 'Token ID cannot be null');

        $this->em->remove($token);
        $this->em->flush();

        // Publication Mercure
        $this->mercurePublisher->publishTokenDeleted($gameId, $tokenId);
    }

    /**
     * Récupère les tokens visibles d'une carte.
     *
     * @return GameToken[]
     */
    public function getVisibleTokens(GameMap $map, User $user): array
    {
        return $this->tokenRepository->findVisibleByMap($map, $user);
    }

    /**
     * Compte le nombre de tokens sur une carte.
     */
    public function countTokensOnMap(GameMap $map): int
    {
        return $this->tokenRepository->countByMap($map);
    }

    /**
     * Vérifie si un utilisateur peut contrôler un token.
     * Le MJ peut toujours contrôler tous les tokens.
     * Les joueurs peuvent contrôler si leur userId est dans token_settings.controllableBy
     */
    public function canControlToken(GameToken $token, User $user, \App\Entity\Game $game): bool
    {
        // Le MJ peut toujours contrôler tous les tokens
        if ($game->isGameMaster($user)) {
            return true;
        }

        // Récupérer les settings du token
        $settings = $token->getSettings();
        if (!$settings || !isset($settings['controllableBy'])) {
            return false;
        }

        $controllableBy = $settings['controllableBy'];
        if (!\is_array($controllableBy)) {
            return false;
        }

        // Vérifier si l'utilisateur est dans la liste
        return \in_array($user->getId(), $controllableBy, true);
    }

    /**
     * Ajoute la permission de contrôle à un utilisateur.
     */
    public function addControlPermission(GameToken $token, int $userId): GameToken
    {
        $settings = $token->getSettings() ?? [];
        $controllableBy = $settings['controllableBy'] ?? [];

        if (!\is_array($controllableBy)) {
            $controllableBy = [];
        }

        // Ajouter l'utilisateur s'il n'est pas déjà dans la liste
        if (!\in_array($userId, $controllableBy, true)) {
            $controllableBy[] = $userId;
            $settings['controllableBy'] = $controllableBy;
            $token->setSettings($settings);
            $this->em->flush();

            // Publication Mercure
            $this->publishTokenUpdate($token);
        }

        return $token;
    }

    /**
     * Retire la permission de contrôle à un utilisateur.
     */
    public function removeControlPermission(GameToken $token, int $userId): GameToken
    {
        $settings = $token->getSettings() ?? [];
        $controllableBy = $settings['controllableBy'] ?? [];

        if (!\is_array($controllableBy)) {
            return $token;
        }

        // Retirer l'utilisateur de la liste
        $controllableBy = array_values(array_filter($controllableBy, static fn ($id) => $id !== $userId));
        $settings['controllableBy'] = $controllableBy;
        $token->setSettings($settings);
        $this->em->flush();

        // Publication Mercure
        $this->publishTokenUpdate($token);

        return $token;
    }

    /**
     * Publie une mise à jour de token via Mercure.
     */
    public function publishTokenUpdate(GameToken $token): void
    {
        $map = $token->getMap();
        \assert(null !== $map, 'Token must have a map');
        $game = $map->getGame();
        \assert(null !== $game, 'Map must have a game');
        $gameId = $game->getId();
        \assert(null !== $gameId, 'Game ID cannot be null');
        $tokenId = $token->getId();
        \assert(null !== $tokenId, 'Token ID cannot be null');
        $createdAt = $token->getCreatedAt();
        \assert(null !== $createdAt, 'CreatedAt cannot be null');

        $this->mercurePublisher->publishGameEvent(
            $gameId,
            'token',
            [
                'action' => 'updated',
                'token' => [
                    'id' => $tokenId,
                    'mapId' => $map->getId(),
                    'name' => $token->getName(),
                    'type' => $token->getType(),
                    'x' => $token->getX(),
                    'y' => $token->getY(),
                    'size' => $token->getSize(),
                    'rotation' => $token->getRotation(),
                    'imageUrl' => $token->getImageUrl(),
                    'isVisible' => $token->isVisible(),
                    'isLocked' => $token->isLocked(),
                    'layer' => $token->getLayer(),
                    'settings' => $token->getSettings(),
                    'createdAt' => $createdAt->format('c'),
                    'updatedAt' => $token->getUpdatedAt()?->format('c'),
                ],
            ],
        );
    }
}
